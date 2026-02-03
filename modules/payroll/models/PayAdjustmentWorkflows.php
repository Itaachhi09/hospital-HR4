<?php

namespace App\Models;

use PDO;
use Exception;

class PayAdjustmentWorkflows
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all pay adjustment workflows with optional filters
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    paw.WorkflowID,
                    paw.WorkflowName,
                    paw.Description,
                    paw.AdjustmentType,
                    paw.AdjustmentValue,
                    paw.TargetGrades,
                    paw.TargetDepartments,
                    paw.TargetPositions,
                    paw.EffectiveDate,
                    paw.Status,
                    paw.TotalImpact,
                    paw.AffectedEmployees,
                    paw.CreatedBy,
                    paw.ApprovedBy,
                    paw.ApprovedAt,
                    paw.ImplementedBy,
                    paw.ImplementedAt,
                    paw.CreatedAt,
                    paw.UpdatedAt,
                    CONCAT(e1.FirstName, ' ', e1.LastName) as CreatedByName,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as ApprovedByName,
                    CONCAT(e3.FirstName, ' ', e3.LastName) as ImplementedByName
                FROM pay_adjustment_workflows paw
                LEFT JOIN employees e1 ON paw.CreatedBy = e1.EmployeeID
                LEFT JOIN employees e2 ON paw.ApprovedBy = e2.EmployeeID
                LEFT JOIN employees e3 ON paw.ImplementedBy = e3.EmployeeID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND paw.Status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['adjustment_type'])) {
            $sql .= " AND paw.AdjustmentType = :adjustment_type";
            $params['adjustment_type'] = $filters['adjustment_type'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND paw.CreatedBy = :created_by";
            $params['created_by'] = $filters['created_by'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (paw.WorkflowName LIKE :search OR paw.Description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY paw.CreatedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get workflow by ID
     */
    public function getById($workflowId)
    {
        $sql = "SELECT 
                    paw.WorkflowID,
                    paw.WorkflowName,
                    paw.Description,
                    paw.AdjustmentType,
                    paw.AdjustmentValue,
                    paw.TargetGrades,
                    paw.TargetDepartments,
                    paw.TargetPositions,
                    paw.EffectiveDate,
                    paw.Status,
                    paw.TotalImpact,
                    paw.AffectedEmployees,
                    paw.CreatedBy,
                    paw.ApprovedBy,
                    paw.ApprovedAt,
                    paw.ImplementedBy,
                    paw.ImplementedAt,
                    paw.CreatedAt,
                    paw.UpdatedAt,
                    CONCAT(e1.FirstName, ' ', e1.LastName) as CreatedByName,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as ApprovedByName,
                    CONCAT(e3.FirstName, ' ', e3.LastName) as ImplementedByName
                FROM pay_adjustment_workflows paw
                LEFT JOIN employees e1 ON paw.CreatedBy = e1.EmployeeID
                LEFT JOIN employees e2 ON paw.ApprovedBy = e2.EmployeeID
                LEFT JOIN employees e3 ON paw.ImplementedBy = e3.EmployeeID
                WHERE paw.WorkflowID = :workflow_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['workflow_id' => $workflowId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new workflow
     */
    public function create($data)
    {
        $sql = "INSERT INTO pay_adjustment_workflows 
                (WorkflowName, Description, AdjustmentType, AdjustmentValue, TargetGrades, 
                 TargetDepartments, TargetPositions, EffectiveDate, Status, CreatedBy) 
                VALUES 
                (:workflow_name, :description, :adjustment_type, :adjustment_value, :target_grades,
                 :target_departments, :target_positions, :effective_date, :status, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'workflow_name' => $data['workflow_name'],
            'description' => $data['description'] ?? null,
            'adjustment_type' => $data['adjustment_type'],
            'adjustment_value' => $data['adjustment_value'],
            'target_grades' => json_encode($data['target_grades'] ?? []),
            'target_departments' => json_encode($data['target_departments'] ?? []),
            'target_positions' => json_encode($data['target_positions'] ?? []),
            'effective_date' => $data['effective_date'],
            'status' => $data['status'] ?? 'Draft',
            'created_by' => $data['created_by']
        ]);

        if ($result) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update workflow
     */
    public function update($workflowId, $data)
    {
        $sql = "UPDATE pay_adjustment_workflows SET 
                WorkflowName = :workflow_name,
                Description = :description,
                AdjustmentType = :adjustment_type,
                AdjustmentValue = :adjustment_value,
                TargetGrades = :target_grades,
                TargetDepartments = :target_departments,
                TargetPositions = :target_positions,
                EffectiveDate = :effective_date,
                Status = :status,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE WorkflowID = :workflow_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'workflow_name' => $data['workflow_name'],
            'description' => $data['description'] ?? null,
            'adjustment_type' => $data['adjustment_type'],
            'adjustment_value' => $data['adjustment_value'],
            'target_grades' => json_encode($data['target_grades'] ?? []),
            'target_departments' => json_encode($data['target_departments'] ?? []),
            'target_positions' => json_encode($data['target_positions'] ?? []),
            'effective_date' => $data['effective_date'],
            'status' => $data['status'],
            'workflow_id' => $workflowId
        ]);
    }

    /**
     * Delete workflow
     */
    public function delete($workflowId)
    {
        $sql = "DELETE FROM pay_adjustment_workflows WHERE WorkflowID = :workflow_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['workflow_id' => $workflowId]);
    }

    /**
     * Approve workflow
     */
    public function approve($workflowId, $approvedBy)
    {
        $sql = "UPDATE pay_adjustment_workflows SET 
                Status = 'Approved',
                ApprovedBy = :approved_by,
                ApprovedAt = CURRENT_TIMESTAMP,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE WorkflowID = :workflow_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'approved_by' => $approvedBy,
            'workflow_id' => $workflowId
        ]);
    }

    /**
     * Implement workflow
     */
    public function implement($workflowId, $implementedBy)
    {
        $sql = "UPDATE pay_adjustment_workflows SET 
                Status = 'Implemented',
                ImplementedBy = :implemented_by,
                ImplementedAt = CURRENT_TIMESTAMP,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE WorkflowID = :workflow_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'implemented_by' => $implementedBy,
            'workflow_id' => $workflowId
        ]);
    }

    /**
     * Calculate workflow impact
     */
    public function calculateImpact($workflowId)
    {
        $workflow = $this->getById($workflowId);
        if (!$workflow) {
            return false;
        }

        $targetGrades = json_decode($workflow['TargetGrades'], true) ?? [];
        $targetDepartments = json_decode($workflow['TargetDepartments'], true) ?? [];
        $targetPositions = json_decode($workflow['TargetPositions'], true) ?? [];

        $sql = "SELECT 
                    e.EmployeeID,
                    e.FirstName,
                    e.LastName,
                    e.Position,
                    e.Department,
                    e.BaseSalary,
                    egm.GradeID,
                    sg.GradeCode,
                    sg.GradeName
                FROM employees e
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.EmployeeID 
                    AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                WHERE e.Status = 'Active'";

        $params = [];
        $conditions = [];

        if (!empty($targetGrades)) {
            $placeholders = str_repeat('?,', count($targetGrades) - 1) . '?';
            $conditions[] = "sg.GradeID IN ($placeholders)";
            $params = array_merge($params, $targetGrades);
        }

        if (!empty($targetDepartments)) {
            $placeholders = str_repeat('?,', count($targetDepartments) - 1) . '?';
            $conditions[] = "e.Department IN ($placeholders)";
            $params = array_merge($params, $targetDepartments);
        }

        if (!empty($targetPositions)) {
            $placeholders = str_repeat('?,', count($targetPositions) - 1) . '?';
            $conditions[] = "e.Position IN ($placeholders)";
            $params = array_merge($params, $targetPositions);
        }

        if (!empty($conditions)) {
            $sql .= " AND (" . implode(' OR ', $conditions) . ")";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalImpact = 0;
        $affectedEmployees = count($employees);

        foreach ($employees as $employee) {
            $currentSalary = $employee['BaseSalary'];
            $adjustmentValue = $workflow['AdjustmentValue'];
            $adjustmentType = $workflow['AdjustmentType'];

            $newSalary = $this->calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType);
            $impact = $newSalary - $currentSalary;
            $totalImpact += $impact;
        }

        // Update workflow with calculated impact
        $updateSql = "UPDATE pay_adjustment_workflows SET 
                      TotalImpact = :total_impact,
                      AffectedEmployees = :affected_employees,
                      UpdatedAt = CURRENT_TIMESTAMP
                      WHERE WorkflowID = :workflow_id";

        $updateStmt = $this->pdo->prepare($updateSql);
        $updateStmt->execute([
            'total_impact' => $totalImpact,
            'affected_employees' => $affectedEmployees,
            'workflow_id' => $workflowId
        ]);

        return [
            'total_impact' => $totalImpact,
            'affected_employees' => $affectedEmployees,
            'employees' => $employees
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
            case 'Grade Based':
            case 'Position Based':
                return $currentSalary * (1 + ($adjustmentValue / 100));
            default:
                return $currentSalary;
        }
    }

    /**
     * Get workflow details (affected employees)
     */
    public function getWorkflowDetails($workflowId)
    {
        $sql = "SELECT 
                    pad.DetailID,
                    pad.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Position,
                    e.Department,
                    pad.CurrentSalary,
                    pad.NewSalary,
                    pad.AdjustmentAmount,
                    pad.AdjustmentPercentage,
                    pad.Status,
                    pad.Notes,
                    pad.CreatedAt
                FROM pay_adjustment_details pad
                LEFT JOIN employees e ON pad.EmployeeID = e.EmployeeID
                WHERE pad.WorkflowID = :workflow_id
                ORDER BY e.LastName, e.FirstName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['workflow_id' => $workflowId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create workflow details
     */
    public function createWorkflowDetails($workflowId, $employees, $adjustmentValue, $adjustmentType)
    {
        $sql = "INSERT INTO pay_adjustment_details 
                (WorkflowID, EmployeeID, CurrentSalary, NewSalary, AdjustmentAmount, 
                 AdjustmentPercentage, Status, Notes) 
                VALUES 
                (:workflow_id, :employee_id, :current_salary, :new_salary, :adjustment_amount,
                 :adjustment_percentage, :status, :notes)";

        $stmt = $this->pdo->prepare($sql);
        $this->pdo->beginTransaction();

        try {
            foreach ($employees as $employee) {
                $currentSalary = $employee['BaseSalary'];
                $newSalary = $this->calculateNewSalary($currentSalary, $adjustmentValue, $adjustmentType);
                $adjustmentAmount = $newSalary - $currentSalary;
                $adjustmentPercentage = ($adjustmentAmount / $currentSalary) * 100;

                $stmt->execute([
                    'workflow_id' => $workflowId,
                    'employee_id' => $employee['EmployeeID'],
                    'current_salary' => $currentSalary,
                    'new_salary' => $newSalary,
                    'adjustment_amount' => $adjustmentAmount,
                    'adjustment_percentage' => $adjustmentPercentage,
                    'status' => 'Pending',
                    'notes' => null
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
