<?php

namespace App\Models;

use PDO;
use Exception;

class SalaryGrades
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all salary grades with optional filters
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    sg.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    sg.Description,
                    sg.DepartmentID,
                    d.DepartmentName,
                    sg.PositionCategory,
                    sg.BranchID,
                    b.BranchName,
                    sg.EffectiveDate,
                    sg.EndDate,
                    sg.Status,
                    sg.CreatedBy,
                    sg.ApprovedBy,
                    sg.ApprovedAt,
                    sg.CreatedAt,
                    sg.UpdatedAt,
                    CONCAT(e1.FirstName, ' ', e1.LastName) as CreatedByName,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as ApprovedByName
                FROM salary_grades sg
                LEFT JOIN departments d ON sg.DepartmentID = d.DepartmentID
                LEFT JOIN branches b ON sg.BranchID = b.BranchID
                LEFT JOIN employees e1 ON sg.CreatedBy = e1.EmployeeID
                LEFT JOIN employees e2 ON sg.ApprovedBy = e2.EmployeeID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND sg.Status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND sg.DepartmentID = :department_id";
            $params['department_id'] = $filters['department_id'];
        }

        if (!empty($filters['branch_id'])) {
            $sql .= " AND sg.BranchID = :branch_id";
            $params['branch_id'] = $filters['branch_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (sg.GradeCode LIKE :search OR sg.GradeName LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY sg.GradeCode, sg.EffectiveDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get salary grade by ID
     */
    public function getById($gradeId)
    {
        $sql = "SELECT 
                    sg.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    sg.Description,
                    sg.DepartmentID,
                    d.DepartmentName,
                    sg.PositionCategory,
                    sg.BranchID,
                    b.BranchName,
                    sg.EffectiveDate,
                    sg.EndDate,
                    sg.Status,
                    sg.CreatedBy,
                    sg.ApprovedBy,
                    sg.ApprovedAt,
                    sg.CreatedAt,
                    sg.UpdatedAt,
                    CONCAT(e1.FirstName, ' ', e1.LastName) as CreatedByName,
                    CONCAT(e2.FirstName, ' ', e2.LastName) as ApprovedByName
                FROM salary_grades sg
                LEFT JOIN departments d ON sg.DepartmentID = d.DepartmentID
                LEFT JOIN branches b ON sg.BranchID = b.BranchID
                LEFT JOIN employees e1 ON sg.CreatedBy = e1.EmployeeID
                LEFT JOIN employees e2 ON sg.ApprovedBy = e2.EmployeeID
                WHERE sg.GradeID = :grade_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['grade_id' => $gradeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new salary grade
     */
    public function create($data)
    {
        $sql = "INSERT INTO salary_grades 
                (GradeCode, GradeName, Description, DepartmentID, PositionCategory, BranchID, 
                 EffectiveDate, EndDate, Status, CreatedBy) 
                VALUES 
                (:grade_code, :grade_name, :description, :department_id, :position_category, :branch_id,
                 :effective_date, :end_date, :status, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'grade_code' => $data['grade_code'],
            'grade_name' => $data['grade_name'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_category' => $data['position_category'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'Draft',
            'created_by' => $data['created_by']
        ]);

        if ($result) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update salary grade
     */
    public function update($gradeId, $data)
    {
        $sql = "UPDATE salary_grades SET 
                GradeCode = :grade_code,
                GradeName = :grade_name,
                Description = :description,
                DepartmentID = :department_id,
                PositionCategory = :position_category,
                BranchID = :branch_id,
                EffectiveDate = :effective_date,
                EndDate = :end_date,
                Status = :status,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE GradeID = :grade_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'grade_code' => $data['grade_code'],
            'grade_name' => $data['grade_name'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_category' => $data['position_category'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'],
            'grade_id' => $gradeId
        ]);
    }

    /**
     * Delete salary grade
     */
    public function delete($gradeId)
    {
        $sql = "DELETE FROM salary_grades WHERE GradeID = :grade_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['grade_id' => $gradeId]);
    }

    /**
     * Approve salary grade
     */
    public function approve($gradeId, $approvedBy)
    {
        $sql = "UPDATE salary_grades SET 
                Status = 'Active',
                ApprovedBy = :approved_by,
                ApprovedAt = CURRENT_TIMESTAMP,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE GradeID = :grade_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'approved_by' => $approvedBy,
            'grade_id' => $gradeId
        ]);
    }

    /**
     * Get salary steps for a grade
     */
    public function getSteps($gradeId)
    {
        $sql = "SELECT 
                    StepID,
                    GradeID,
                    StepNumber,
                    StepName,
                    MinRate,
                    MaxRate,
                    BaseRate,
                    EffectiveDate,
                    EndDate,
                    Status,
                    CreatedAt,
                    UpdatedAt
                FROM salary_steps 
                WHERE GradeID = :grade_id 
                ORDER BY StepNumber";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['grade_id' => $gradeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add salary step to grade
     */
    public function addStep($gradeId, $data)
    {
        $sql = "INSERT INTO salary_steps 
                (GradeID, StepNumber, StepName, MinRate, MaxRate, BaseRate, EffectiveDate, EndDate) 
                VALUES 
                (:grade_id, :step_number, :step_name, :min_rate, :max_rate, :base_rate, :effective_date, :end_date)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'grade_id' => $gradeId,
            'step_number' => $data['step_number'],
            'step_name' => $data['step_name'] ?? null,
            'min_rate' => $data['min_rate'],
            'max_rate' => $data['max_rate'],
            'base_rate' => $data['base_rate'],
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null
        ]);
    }

    /**
     * Update salary step
     */
    public function updateStep($stepId, $data)
    {
        $sql = "UPDATE salary_steps SET 
                StepNumber = :step_number,
                StepName = :step_name,
                MinRate = :min_rate,
                MaxRate = :max_rate,
                BaseRate = :base_rate,
                EffectiveDate = :effective_date,
                EndDate = :end_date,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE StepID = :step_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'step_number' => $data['step_number'],
            'step_name' => $data['step_name'] ?? null,
            'min_rate' => $data['min_rate'],
            'max_rate' => $data['max_rate'],
            'base_rate' => $data['base_rate'],
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'step_id' => $stepId
        ]);
    }

    /**
     * Delete salary step
     */
    public function deleteStep($stepId)
    {
        $sql = "DELETE FROM salary_steps WHERE StepID = :step_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['step_id' => $stepId]);
    }

    /**
     * Check if grade code exists
     */
    public function gradeCodeExists($gradeCode, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM salary_grades WHERE GradeCode = :grade_code";
        $params = ['grade_code' => $gradeCode];

        if ($excludeId) {
            $sql .= " AND GradeID != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
