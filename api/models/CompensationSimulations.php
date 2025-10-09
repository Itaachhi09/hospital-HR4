<?php

namespace App\Models;

use PDO;
use Exception;

class CompensationSimulations
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all simulations with optional filters
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    cs.SimulationID,
                    cs.SimulationName,
                    cs.Description,
                    cs.SimulationType,
                    cs.Parameters,
                    cs.Results,
                    cs.TotalImpact,
                    cs.AffectedEmployees,
                    cs.CreatedBy,
                    cs.CreatedAt,
                    cs.ExpiresAt,
                    CONCAT(e.FirstName, ' ', e.LastName) as CreatedByName
                FROM compensation_simulations cs
                LEFT JOIN employees e ON cs.CreatedBy = e.EmployeeID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['simulation_type'])) {
            $sql .= " AND cs.SimulationType = :simulation_type";
            $params['simulation_type'] = $filters['simulation_type'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND cs.CreatedBy = :created_by";
            $params['created_by'] = $filters['created_by'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (cs.SimulationName LIKE :search OR cs.Description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Filter out expired simulations unless specifically requested
        if (empty($filters['include_expired'])) {
            $sql .= " AND (cs.ExpiresAt IS NULL OR cs.ExpiresAt > NOW())";
        }

        $sql .= " ORDER BY cs.CreatedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get simulation by ID
     */
    public function getById($simulationId)
    {
        $sql = "SELECT 
                    cs.SimulationID,
                    cs.SimulationName,
                    cs.Description,
                    cs.SimulationType,
                    cs.Parameters,
                    cs.Results,
                    cs.TotalImpact,
                    cs.AffectedEmployees,
                    cs.CreatedBy,
                    cs.CreatedAt,
                    cs.ExpiresAt,
                    CONCAT(e.FirstName, ' ', e.LastName) as CreatedByName
                FROM compensation_simulations cs
                LEFT JOIN employees e ON cs.CreatedBy = e.EmployeeID
                WHERE cs.SimulationID = :simulation_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['simulation_id' => $simulationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new simulation
     */
    public function create($data)
    {
        $sql = "INSERT INTO compensation_simulations 
                (SimulationName, Description, SimulationType, Parameters, Results, 
                 TotalImpact, AffectedEmployees, CreatedBy, ExpiresAt) 
                VALUES 
                (:simulation_name, :description, :simulation_type, :parameters, :results,
                 :total_impact, :affected_employees, :created_by, :expires_at)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'simulation_name' => $data['simulation_name'],
            'description' => $data['description'] ?? null,
            'simulation_type' => $data['simulation_type'],
            'parameters' => json_encode($data['parameters']),
            'results' => json_encode($data['results'] ?? []),
            'total_impact' => $data['total_impact'] ?? null,
            'affected_employees' => $data['affected_employees'] ?? null,
            'created_by' => $data['created_by'],
            'expires_at' => $data['expires_at'] ?? null
        ]);

        if ($result) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update simulation
     */
    public function update($simulationId, $data)
    {
        $sql = "UPDATE compensation_simulations SET 
                SimulationName = :simulation_name,
                Description = :description,
                SimulationType = :simulation_type,
                Parameters = :parameters,
                Results = :results,
                TotalImpact = :total_impact,
                AffectedEmployees = :affected_employees,
                ExpiresAt = :expires_at
                WHERE SimulationID = :simulation_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'simulation_name' => $data['simulation_name'],
            'description' => $data['description'] ?? null,
            'simulation_type' => $data['simulation_type'],
            'parameters' => json_encode($data['parameters']),
            'results' => json_encode($data['results'] ?? []),
            'total_impact' => $data['total_impact'] ?? null,
            'affected_employees' => $data['affected_employees'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'simulation_id' => $simulationId
        ]);
    }

    /**
     * Delete simulation
     */
    public function delete($simulationId)
    {
        $sql = "DELETE FROM compensation_simulations WHERE SimulationID = :simulation_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['simulation_id' => $simulationId]);
    }

    /**
     * Run grade adjustment simulation
     */
    public function runGradeAdjustmentSimulation($parameters)
    {
        $gradeIds = $parameters['grade_ids'] ?? [];
        $adjustmentValue = $parameters['adjustment_value'] ?? 0;
        $adjustmentType = $parameters['adjustment_type'] ?? 'Percentage';

        if (empty($gradeIds)) {
            throw new Exception('Grade IDs are required for grade adjustment simulation');
        }

        $placeholders = str_repeat('?,', count($gradeIds) - 1) . '?';
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Position,
                    e.Department,
                    e.BaseSalary as CurrentSalary,
                    sg.GradeCode,
                    sg.GradeName,
                    ss.StepNumber,
                    ss.StepName
                FROM employees e
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.EmployeeID 
                    AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                WHERE e.Status = 'Active' 
                AND sg.GradeID IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($gradeIds);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $totalImpact = 0;

        foreach ($employees as $employee) {
            $currentSalary = $employee['CurrentSalary'];
            $newSalary = $this->calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType);
            $adjustmentAmount = $newSalary - $currentSalary;
            $adjustmentPercentage = ($adjustmentAmount / $currentSalary) * 100;

            $results[] = [
                'employee_id' => $employee['EmployeeID'],
                'employee_name' => $employee['EmployeeName'],
                'position' => $employee['Position'],
                'department' => $employee['Department'],
                'current_salary' => $currentSalary,
                'new_salary' => $newSalary,
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_percentage' => $adjustmentPercentage,
                'grade_code' => $employee['GradeCode'],
                'grade_name' => $employee['GradeName'],
                'step_number' => $employee['StepNumber'],
                'step_name' => $employee['StepName']
            ];

            $totalImpact += $adjustmentAmount;
        }

        return [
            'results' => $results,
            'total_impact' => $totalImpact,
            'affected_employees' => count($employees),
            'parameters' => $parameters
        ];
    }

    /**
     * Run department adjustment simulation
     */
    public function runDepartmentAdjustmentSimulation($parameters)
    {
        $departmentIds = $parameters['department_ids'] ?? [];
        $adjustmentValue = $parameters['adjustment_value'] ?? 0;
        $adjustmentType = $parameters['adjustment_type'] ?? 'Percentage';

        if (empty($departmentIds)) {
            throw new Exception('Department IDs are required for department adjustment simulation');
        }

        $placeholders = str_repeat('?,', count($departmentIds) - 1) . '?';
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Position,
                    e.Department,
                    e.BaseSalary as CurrentSalary,
                    d.DepartmentName
                FROM employees e
                LEFT JOIN departments d ON e.Department = d.DepartmentName
                WHERE e.Status = 'Active' 
                AND d.DepartmentID IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($departmentIds);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $totalImpact = 0;

        foreach ($employees as $employee) {
            $currentSalary = $employee['CurrentSalary'];
            $newSalary = $this->calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType);
            $adjustmentAmount = $newSalary - $currentSalary;
            $adjustmentPercentage = ($adjustmentAmount / $currentSalary) * 100;

            $results[] = [
                'employee_id' => $employee['EmployeeID'],
                'employee_name' => $employee['EmployeeName'],
                'position' => $employee['Position'],
                'department' => $employee['Department'],
                'current_salary' => $currentSalary,
                'new_salary' => $newSalary,
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_percentage' => $adjustmentPercentage
            ];

            $totalImpact += $adjustmentAmount;
        }

        return [
            'results' => $results,
            'total_impact' => $totalImpact,
            'affected_employees' => count($employees),
            'parameters' => $parameters
        ];
    }

    /**
     * Run position adjustment simulation
     */
    public function runPositionAdjustmentSimulation($parameters)
    {
        $positions = $parameters['positions'] ?? [];
        $adjustmentValue = $parameters['adjustment_value'] ?? 0;
        $adjustmentType = $parameters['adjustment_type'] ?? 'Percentage';

        if (empty($positions)) {
            throw new Exception('Positions are required for position adjustment simulation');
        }

        $placeholders = str_repeat('?,', count($positions) - 1) . '?';
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Position,
                    e.Department,
                    e.BaseSalary as CurrentSalary
                FROM employees e
                WHERE e.Status = 'Active' 
                AND e.Position IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($positions);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $totalImpact = 0;

        foreach ($employees as $employee) {
            $currentSalary = $employee['CurrentSalary'];
            $newSalary = $this->calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType);
            $adjustmentAmount = $newSalary - $currentSalary;
            $adjustmentPercentage = ($adjustmentAmount / $currentSalary) * 100;

            $results[] = [
                'employee_id' => $employee['EmployeeID'],
                'employee_name' => $employee['EmployeeName'],
                'position' => $employee['Position'],
                'department' => $employee['Department'],
                'current_salary' => $currentSalary,
                'new_salary' => $newSalary,
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_percentage' => $adjustmentPercentage
            ];

            $totalImpact += $adjustmentAmount;
        }

        return [
            'results' => $results,
            'total_impact' => $totalImpact,
            'affected_employees' => count($employees),
            'parameters' => $parameters
        ];
    }

    /**
     * Calculate new salary based on adjustment type
     */
    private function calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType)
    {
        switch ($adjustmentType) {
            case 'Percentage':
                return $currentSalary * (1 + ($adjustmentValue / 100));
            case 'Fixed Amount':
                return $currentSalary + $adjustmentValue;
            default:
                return $currentSalary;
        }
    }

    /**
     * Clean up expired simulations
     */
    public function cleanupExpired()
    {
        $sql = "DELETE FROM compensation_simulations 
                WHERE ExpiresAt IS NOT NULL 
                AND ExpiresAt < NOW()";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Get simulation statistics
     */
    public function getStatistics()
    {
        $sql = "SELECT 
                    SimulationType,
                    COUNT(*) as Count,
                    AVG(TotalImpact) as AverageImpact,
                    SUM(AffectedEmployees) as TotalAffectedEmployees
                FROM compensation_simulations 
                WHERE ExpiresAt IS NULL OR ExpiresAt > NOW()
                GROUP BY SimulationType";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
