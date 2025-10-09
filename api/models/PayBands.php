<?php

namespace App\Models;

use PDO;
use Exception;

class PayBands
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all pay bands with optional filters
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT 
                    pb.BandID,
                    pb.BandName,
                    pb.MinSalary,
                    pb.MaxSalary,
                    pb.Description,
                    pb.DepartmentID,
                    d.DepartmentName,
                    pb.PositionCategory,
                    pb.EffectiveDate,
                    pb.EndDate,
                    pb.Status,
                    pb.CreatedAt,
                    pb.UpdatedAt
                FROM pay_bands pb
                LEFT JOIN departments d ON pb.DepartmentID = d.DepartmentID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pb.Status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND pb.DepartmentID = :department_id";
            $params['department_id'] = $filters['department_id'];
        }

        if (!empty($filters['position_category'])) {
            $sql .= " AND pb.PositionCategory = :position_category";
            $params['position_category'] = $filters['position_category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (pb.BandName LIKE :search OR pb.Description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY pb.BandName, pb.EffectiveDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pay band by ID
     */
    public function getById($bandId)
    {
        $sql = "SELECT 
                    pb.BandID,
                    pb.BandName,
                    pb.MinSalary,
                    pb.MaxSalary,
                    pb.Description,
                    pb.DepartmentID,
                    d.DepartmentName,
                    pb.PositionCategory,
                    pb.EffectiveDate,
                    pb.EndDate,
                    pb.Status,
                    pb.CreatedAt,
                    pb.UpdatedAt
                FROM pay_bands pb
                LEFT JOIN departments d ON pb.DepartmentID = d.DepartmentID
                WHERE pb.BandID = :band_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['band_id' => $bandId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new pay band
     */
    public function create($data)
    {
        $sql = "INSERT INTO pay_bands 
                (BandName, MinSalary, MaxSalary, Description, DepartmentID, PositionCategory, 
                 EffectiveDate, EndDate, Status) 
                VALUES 
                (:band_name, :min_salary, :max_salary, :description, :department_id, :position_category,
                 :effective_date, :end_date, :status)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'band_name' => $data['band_name'],
            'min_salary' => $data['min_salary'],
            'max_salary' => $data['max_salary'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_category' => $data['position_category'] ?? null,
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'Active'
        ]);

        if ($result) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update pay band
     */
    public function update($bandId, $data)
    {
        $sql = "UPDATE pay_bands SET 
                BandName = :band_name,
                MinSalary = :min_salary,
                MaxSalary = :max_salary,
                Description = :description,
                DepartmentID = :department_id,
                PositionCategory = :position_category,
                EffectiveDate = :effective_date,
                EndDate = :end_date,
                Status = :status,
                UpdatedAt = CURRENT_TIMESTAMP
                WHERE BandID = :band_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'band_name' => $data['band_name'],
            'min_salary' => $data['min_salary'],
            'max_salary' => $data['max_salary'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_category' => $data['position_category'] ?? null,
            'effective_date' => $data['effective_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'],
            'band_id' => $bandId
        ]);
    }

    /**
     * Delete pay band
     */
    public function delete($bandId)
    {
        $sql = "DELETE FROM pay_bands WHERE BandID = :band_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['band_id' => $bandId]);
    }

    /**
     * Get pay bands for salary range
     */
    public function getBySalaryRange($salary)
    {
        $sql = "SELECT 
                    pb.BandID,
                    pb.BandName,
                    pb.MinSalary,
                    pb.MaxSalary,
                    pb.Description,
                    pb.DepartmentID,
                    d.DepartmentName,
                    pb.PositionCategory
                FROM pay_bands pb
                LEFT JOIN departments d ON pb.DepartmentID = d.DepartmentID
                WHERE pb.Status = 'Active' 
                AND pb.MinSalary <= :salary 
                AND pb.MaxSalary >= :salary
                ORDER BY pb.MinSalary";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['salary' => $salary]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overlapping pay bands
     */
    public function getOverlappingBands($minSalary, $maxSalary, $excludeId = null)
    {
        $sql = "SELECT 
                    pb.BandID,
                    pb.BandName,
                    pb.MinSalary,
                    pb.MaxSalary
                FROM pay_bands pb
                WHERE pb.Status = 'Active' 
                AND (
                    (pb.MinSalary <= :min_salary AND pb.MaxSalary >= :min_salary) OR
                    (pb.MinSalary <= :max_salary AND pb.MaxSalary >= :max_salary) OR
                    (pb.MinSalary >= :min_salary AND pb.MaxSalary <= :max_salary)
                )";

        $params = [
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary
        ];

        if ($excludeId) {
            $sql .= " AND pb.BandID != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " ORDER BY pb.MinSalary";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
