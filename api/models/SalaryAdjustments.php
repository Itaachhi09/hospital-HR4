<?php

namespace App\Models;

use PDO;

class SalaryAdjustments
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Ensure tables exist (idempotent)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS adjustment_reasons (
            ReasonID INT AUTO_INCREMENT PRIMARY KEY,
            ReasonCode VARCHAR(50) UNIQUE,
            ReasonLabel VARCHAR(150) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS salary_adjustments (
            AdjustmentID INT AUTO_INCREMENT PRIMARY KEY,
            EmployeeID INT NOT NULL,
            DepartmentID INT NULL,
            OldSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
            NewSalary DECIMAL(12,2) NOT NULL DEFAULT 0,
            GradeID INT NULL,
            StepID INT NULL,
            ReasonID INT NULL,
            Justification TEXT NULL,
            AttachmentURL VARCHAR(255) NULL,
            EffectiveDate DATE NOT NULL,
            Status ENUM('Draft','Pending Review','Approved','Rejected','Implemented') NOT NULL DEFAULT 'Draft',
            InitiatedBy INT NULL,
            ReviewedBy INT NULL,
            ApprovedBy INT NULL,
            ImplementedBy INT NULL,
            CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_emp (EmployeeID), INDEX idx_status (Status), INDEX idx_effective (EffectiveDate)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function list($filters = [])
    {
        $sql = "SELECT sa.*, CONCAT(e.FirstName,' ',e.LastName) AS EmployeeName, d.DepartmentName,
                       ar.ReasonLabel
                FROM salary_adjustments sa
                LEFT JOIN employees e ON sa.EmployeeID=e.EmployeeID
                LEFT JOIN departments d ON sa.DepartmentID=d.DepartmentID
                LEFT JOIN adjustment_reasons ar ON sa.ReasonID=ar.ReasonID
                WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) { $sql .= " AND sa.Status=:s"; $params[':s']=$filters['status']; }
        if (!empty($filters['department_id'])) { $sql .= " AND sa.DepartmentID=:d"; $params[':d']=$filters['department_id']; }
        if (!empty($filters['start_date'])) { $sql .= " AND sa.EffectiveDate>=:sd"; $params[':sd']=$filters['start_date']; }
        if (!empty($filters['end_date'])) { $sql .= " AND sa.EffectiveDate<=:ed"; $params[':ed']=$filters['end_date']; }
        $sql .= " ORDER BY sa.CreatedAt DESC";
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO salary_adjustments (EmployeeID, DepartmentID, OldSalary, NewSalary, GradeID, StepID, ReasonID, Justification, AttachmentURL, EffectiveDate, Status, InitiatedBy)
                VALUES (:emp,:dept,:old,:new,:g,:st,:r,:j,:att,:eff,'Pending Review',:by)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':emp'=>$data['employee_id'], ':dept'=>$data['department_id']??null,
            ':old'=>$data['old_salary'], ':new'=>$data['new_salary'], ':g'=>$data['grade_id']??null,
            ':st'=>$data['step_id']??null, ':r'=>$data['reason_id']??null,
            ':j'=>$data['justification']??null, ':att'=>$data['attachment_url']??null,
            ':eff'=>$data['effective_date'], ':by'=>$data['initiated_by']??null
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data)
    {
        $sql = "UPDATE salary_adjustments SET NewSalary=:new, GradeID=:g, StepID=:st, ReasonID=:r, Justification=:j, AttachmentURL=:att, EffectiveDate=:eff WHERE AdjustmentID=:id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':new'=>$data['new_salary'], ':g'=>$data['grade_id']??null, ':st'=>$data['step_id']??null, ':r'=>$data['reason_id']??null, ':j'=>$data['justification']??null, ':att'=>$data['attachment_url']??null, ':eff'=>$data['effective_date'], ':id'=>$id]);
    }

    public function setStatus(int $id, string $status, ?int $userId)
    {
        $fields = ['Status'=>$status];
        if ($status==='Pending Review') { $fields['ReviewedBy']=$userId; }
        if ($status==='Approved') { $fields['ApprovedBy']=$userId; }
        if ($status==='Implemented') { $fields['ImplementedBy']=$userId; }
        $set=[]; $params=[':id'=>$id]; foreach($fields as $k=>$v){ $set[]="$k=:{$k}"; $params[":{$k}"]=$v; }
        $sql = "UPDATE salary_adjustments SET ".implode(',', $set).", UpdatedAt=NOW() WHERE AdjustmentID=:id";
        $stmt = $this->pdo->prepare($sql); return $stmt->execute($params);
    }

    public function reasons()
    {
        $stmt = $this->pdo->query("SELECT ReasonID, ReasonCode, ReasonLabel FROM adjustment_reasons ORDER BY ReasonLabel");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function seedReasons()
    {
        $defaults = [
            ['Promotion','Promotion'],['Merit','Merit Increase (Performance-Based)'],['Annual','Annual / Periodic Adjustment'],
            ['Market','Market Rate Realignment'],['Correction','Correction of Previous Discrepancy'],['Management','Management-Approved Adjustment']
        ];
        foreach ($defaults as [$code,$label]) {
            $stmt=$this->pdo->prepare("INSERT IGNORE INTO adjustment_reasons (ReasonCode, ReasonLabel) VALUES (:c,:l)");
            $stmt->execute([':c'=>$code,':l'=>$label]);
        }
        return true;
    }
}


