<?php

namespace App\Models;

use PDO;
use Exception;

class EmployeeGradeMapping
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all employee grade mappings with optional filters
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    egm.MappingID,
                    egm.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.JobTitle,
                    e.DepartmentID,
                    egm.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    egm.StepID,
                    ss.StepNumber,
                    ss.StepName,
                    egm.CurrentSalary,
                    egm.GradeMinRate,
                    egm.GradeMaxRate,
                    egm.Status,
                    egm.EffectiveDate,
                    egm.EndDate,
                    egm.Notes,
                    egm.CreatedBy,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as CreatedByName,
                    egm.CreatedAt,
                    egm.UpdatedAt
                FROM employee_grade_mapping egm
                LEFT JOIN employees e ON egm.EmployeeID = e.EmployeeID
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                LEFT JOIN employees e2 ON egm.CreatedBy = e2.EmployeeID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= " AND egm.EmployeeID = :employee_id";
            $params['employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['grade_id'])) {
            $sql .= " AND egm.GradeID = :grade_id";
            $params['grade_id'] = $filters['grade_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND egm.Status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department']) || !empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department";
            $params['department'] = $filters['department'] ?? $filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND egm.Status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search OR e.JobTitle LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY e.LastName, e.FirstName, egm.EffectiveDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee grade mapping by ID
     */
    public function getById($mappingId)
    {
        $sql = "SELECT 
                    egm.MappingID,
                    egm.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.JobTitle,
                    e.DepartmentID,
                    egm.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    egm.StepID,
                    ss.StepNumber,
                    ss.StepName,
                    egm.CurrentSalary,
                    egm.GradeMinRate,
                    egm.GradeMaxRate,
                    egm.Status,
                    egm.EffectiveDate,
                    egm.EndDate,
                    egm.Notes,
                    egm.CreatedBy,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as CreatedByName,
                    egm.CreatedAt,
                    egm.UpdatedAt
                FROM employee_grade_mapping egm
                LEFT JOIN employees e ON egm.EmployeeID = e.EmployeeID
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                LEFT JOIN employees e2 ON egm.CreatedBy = e2.EmployeeID
                WHERE egm.MappingID = :mapping_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mapping_id' => $mappingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get current mapping for employee
     */
    public function getCurrentByEmployee($employeeId)
    {
        $sql = "SELECT 
                    egm.MappingID,
                    egm.EmployeeID,
                    egm.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    egm.StepID,
                    ss.StepNumber,
                    ss.StepName,
                    egm.CurrentSalary,
                    egm.GradeMinRate,
                    egm.GradeMaxRate,
                    egm.Status,
                    egm.EffectiveDate,
                    egm.EndDate,
                    egm.Notes
                FROM employee_grade_mapping egm
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                WHERE egm.EmployeeID = :employee_id 
                AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                ORDER BY egm.EffectiveDate DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Approve a pending mapping and end any previous active mapping
     */
    public function approve($mappingId, $approvedBy)
    {
        // Set this mapping as approved/active and end previous ones
        $sql = "SELECT EmployeeID, EffectiveDate FROM employee_grade_mapping WHERE MappingID = :id";
        $stmt = $this->pdo->prepare($sql); $stmt->execute(['id'=>$mappingId]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $employeeId = (int)$row['EmployeeID']; $eff = $row['EffectiveDate'];

        $this->pdo->beginTransaction();
        try {
            // end previous overlapping mappings
            $this->endPreviousMapping($employeeId, $eff);
            // approve this mapping
            $up = $this->pdo->prepare("UPDATE employee_grade_mapping SET Status='Within Band', UpdatedAt=CURRENT_TIMESTAMP WHERE MappingID=:id");
            $up->execute(['id'=>$mappingId]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
    }

    /**
     * Mapping overview stats for dashboard
     */
    public function getOverviewStats()
    {
        $stats = [
            'total_mapped' => 0,
            'percent_within' => 0,
            'needs_adjustment' => 0,
            'recent_updates' => []
        ];
        // totals
        $q = $this->pdo->query("SELECT COUNT(*) AS c FROM employee_grade_mapping WHERE EndDate IS NULL OR EndDate > CURDATE()");
        $stats['total_mapped'] = (int)$q->fetchColumn();
        // within band
        $q = $this->pdo->query("SELECT COUNT(*) FROM employee_grade_mapping WHERE (EndDate IS NULL OR EndDate > CURDATE()) AND Status='Within Band'");
        $within = (int)$q->fetchColumn();
        $stats['percent_within'] = $stats['total_mapped'] ? round(($within / $stats['total_mapped']) * 100, 1) : 0;
        // needs adjustment (below/above)
        $q = $this->pdo->query("SELECT COUNT(*) FROM employee_grade_mapping WHERE (EndDate IS NULL OR EndDate > CURDATE()) AND Status IN ('Below Band','Above Band','Pending Review')");
        $stats['needs_adjustment'] = (int)$q->fetchColumn();
        // recent updates
        $q = $this->pdo->query("SELECT egm.MappingID, egm.EmployeeID, CONCAT(e.FirstName,' ',e.LastName) AS EmployeeName, egm.GradeID, sg.GradeCode, egm.EffectiveDate, egm.Status FROM employee_grade_mapping egm LEFT JOIN employees e ON egm.EmployeeID=e.EmployeeID LEFT JOIN salary_grades sg ON egm.GradeID=sg.GradeID ORDER BY egm.UpdatedAt DESC LIMIT 10");
        $stats['recent_updates'] = $q->fetchAll(PDO::FETCH_ASSOC);
        return $stats;
    }

    /**
     * History for an employee
     */
    public function getHistory($employeeId)
    {
        $sql = "SELECT egm.*, sg.GradeCode, sg.GradeName, ss.StepNumber
                FROM employee_grade_mapping egm
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                WHERE egm.EmployeeID = :emp ORDER BY egm.EffectiveDate DESC, egm.CreatedAt DESC";
        $stmt=$this->pdo->prepare($sql); $stmt->execute(['emp'=>$employeeId]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new employee grade mapping
     */
    public function create($data)
    {
        // End previous mapping if exists
        if (!empty($data['end_previous'])) {
            $this->endPreviousMapping($data['employee_id'], $data['effective_date']);
        }

        $sql = "INSERT INTO employee_grade_mapping 
                (EmployeeID, GradeID, StepID, CurrentSalary, GradeMinRate, GradeMaxRate, 
                 Status, EffectiveDate, EndDate, Notes, CreatedBy) 
                VALUES 
                (:employee_id, :grade_id, :step_id, :current_salary, :grade_min_rate, :grade_max_rate,
                 :status, :effective_date, :end_date, :notes, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'employee_id' => $data['employee_id'],
            'grade_id' => $data['grade_id'],
            'step_id' => $data['step_id'],
            'current_salary' => $data['current_salary'],
            'grade_min_rate' => $data['grade_min_rate'],
            'grade_max_rate' => $data['grade_max_rate'],
            'status' => $data['status'] ?? 'Pending Review',
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by']
        ]);

        if ($result) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update employee grade mapping
     */
    public function update($mappingId, $data)
    {
        $sql = "UPDATE employee_grade_mapping SET 
                GradeID = :grade_id,
                StepID = :step_id,
                CurrentSalary = :current_salary,
                GradeMinRate = :grade_min_rate,
                GradeMaxRate = :grade_max_rate,
                Status = :status,
                EffectiveDate = :effective_date,
                EndDate = :end_date,
                Notes = :notes,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE MappingID = :mapping_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'grade_id' => $data['grade_id'],
            'step_id' => $data['step_id'],
            'current_salary' => $data['current_salary'],
            'grade_min_rate' => $data['grade_min_rate'],
            'grade_max_rate' => $data['grade_max_rate'],
            'status' => $data['status'],
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'mapping_id' => $mappingId
        ]);
    }

    /**
     * Delete employee grade mapping
     */
    public function delete($mappingId)
    {
        $sql = "DELETE FROM employee_grade_mapping WHERE MappingID = :mapping_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['mapping_id' => $mappingId]);
    }

    /**
     * End previous mapping for employee
     */
    private function endPreviousMapping($employeeId, $effectiveDate)
    {
        $sql = "UPDATE employee_grade_mapping SET 
                EndDate = :end_date,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE EmployeeID = :employee_id 
                AND (EndDate IS NULL OR EndDate > :effective_date)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'employee_id' => $employeeId,
            'end_date' => date('Y-m-d', strtotime($effectiveDate . ' -1 day')),
            'effective_date' => $effectiveDate
        ]);
    }

    /**
     * Calculate salary status
     */
    public function calculateSalaryStatus($currentSalary, $minRate, $maxRate)
    {
        if ($currentSalary < $minRate) {
            return 'Below Band';
        } elseif ($currentSalary > $maxRate) {
            return 'Above Band';
        } else {
            return 'Within Band';
        }
    }

    /**
     * Get employees by grade
     */
    public function getEmployeesByGrade($gradeId)
    {
        $sql = "SELECT 
                    egm.MappingID,
                    egm.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.JobTitle,
                    e.DepartmentID,
                    egm.CurrentSalary,
                    egm.GradeMinRate,
                    egm.GradeMaxRate,
                    egm.Status,
                    egm.EffectiveDate
                FROM employee_grade_mapping egm
                LEFT JOIN employees e ON egm.EmployeeID = e.EmployeeID
                WHERE egm.GradeID = :grade_id
                AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                ORDER BY e.LastName, e.FirstName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['grade_id' => $gradeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get grade distribution statistics
     */
    public function getGradeDistribution()
    {
        $sql = "SELECT 
                    sg.GradeCode,
                    sg.GradeName,
                    COUNT(egm.EmployeeID) as EmployeeCount,
                    AVG(egm.CurrentSalary) as AverageSalary,
                    MIN(egm.CurrentSalary) as MinSalary,
                    MAX(egm.CurrentSalary) as MaxSalary
                FROM salary_grades sg
                LEFT JOIN employee_grade_mapping egm ON sg.GradeID = egm.GradeID 
                    AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                WHERE sg.Status = 'Active'
                GROUP BY sg.GradeID, sg.GradeCode, sg.GradeName
                ORDER BY sg.GradeCode";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
