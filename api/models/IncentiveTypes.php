<?php

namespace App\Models;

use PDO;
use Exception;

class IncentiveTypes
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT IncentiveTypeID, Name, Category, Description, EligibilityJSON, ValueType, ValueAmount, Frequency, DepartmentID, PositionCategory, Status, Taxable, CreatedAt, UpdatedAt FROM incentive_types WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) { $sql .= " AND Status = :status"; $params[':status'] = $filters['status']; }
        if (!empty($filters['category'])) { $sql .= " AND Category = :cat"; $params[':cat'] = $filters['category']; }
        if (!empty($filters['department_id'])) { $sql .= " AND DepartmentID = :dept"; $params[':dept'] = $filters['department_id']; }
        $sql .= " ORDER BY Name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO incentive_types (Name, Category, Description, EligibilityJSON, ValueType, ValueAmount, Frequency, DepartmentID, PositionCategory, Status, Taxable, CreatedAt, UpdatedAt)
                VALUES (:n, :c, :d, :e, :vt, :va, :f, :dept, :pc, :s, :tax, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':n' => $data['name'],
            ':c' => $data['category'],
            ':d' => $data['description'] ?? null,
            ':e' => !empty($data['eligibility']) ? json_encode($data['eligibility']) : null,
            ':vt' => $data['value_type'] ?? 'Amount',
            ':va' => $data['value_amount'] ?? 0,
            ':f' => $data['frequency'] ?? 'One-time',
            ':dept' => $data['department_id'] ?? null,
            ':pc' => $data['position_category'] ?? null,
            ':s' => $data['status'] ?? 'Active',
            ':tax' => !empty($data['taxable']) ? 1 : 0,
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data)
    {
        $sql = "UPDATE incentive_types SET Name=:n, Category=:c, Description=:d, EligibilityJSON=:e, ValueType=:vt, ValueAmount=:va, Frequency=:f, DepartmentID=:dept, PositionCategory=:pc, Status=:s, Taxable=:tax, UpdatedAt=NOW() WHERE IncentiveTypeID=:id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':n' => $data['name'],
            ':c' => $data['category'],
            ':d' => $data['description'] ?? null,
            ':e' => !empty($data['eligibility']) ? json_encode($data['eligibility']) : null,
            ':vt' => $data['value_type'] ?? 'Amount',
            ':va' => $data['value_amount'] ?? 0,
            ':f' => $data['frequency'] ?? 'One-time',
            ':dept' => $data['department_id'] ?? null,
            ':pc' => $data['position_category'] ?? null,
            ':s' => $data['status'] ?? 'Active',
            ':tax' => !empty($data['taxable']) ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM incentive_types WHERE IncentiveTypeID=:id");
        return $stmt->execute([':id' => $id]);
    }
}


