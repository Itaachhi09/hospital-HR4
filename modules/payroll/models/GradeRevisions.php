<?php

namespace App\Models;

use PDO;

class GradeRevisions
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Ensure table exists
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS grade_revisions (
            RevisionID INT AUTO_INCREMENT PRIMARY KEY,
            GradeID INT NOT NULL,
            BeforeMin DECIMAL(12,2) NULL,
            BeforeMid DECIMAL(12,2) NULL,
            BeforeMax DECIMAL(12,2) NULL,
            AfterMin DECIMAL(12,2) NULL,
            AfterMid DECIMAL(12,2) NULL,
            AfterMax DECIMAL(12,2) NULL,
            Percentage DECIMAL(6,2) NULL,
            Reason VARCHAR(255) NULL,
            Status ENUM('Draft','Pending Review','Approved','Implemented','Rejected') NOT NULL DEFAULT 'Draft',
            EffectiveDate DATE NULL,
            CreatedBy INT NULL,
            ReviewedBy INT NULL,
            ApprovedBy INT NULL,
            ImplementedBy INT NULL,
            CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grade (GradeID), INDEX idx_status (Status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function list(array $filters = [])
    {
        $sql = "SELECT gr.*, sg.GradeCode, sg.GradeName FROM grade_revisions gr LEFT JOIN salary_grades sg ON gr.GradeID=sg.GradeID WHERE 1=1";
        $params = [];
        if (!empty($filters['grade_id'])) { $sql .= " AND gr.GradeID=:g"; $params[':g']=(int)$filters['grade_id']; }
        if (!empty($filters['status'])) { $sql .= " AND gr.Status=:s"; $params[':s']=$filters['status']; }
        $sql .= " ORDER BY gr.CreatedAt DESC";
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO grade_revisions (GradeID, BeforeMin, BeforeMid, BeforeMax, AfterMin, AfterMid, AfterMax, Percentage, Reason, Status, EffectiveDate, CreatedBy)
                VALUES (:g,:bmin,:bmid,:bmax,:amin,:amid,:amax,:pct,:r,'Pending Review',:eff,:by)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':g'=>$data['grade_id'], ':bmin'=>$data['before_min']??null, ':bmid'=>$data['before_mid']??null, ':bmax'=>$data['before_max']??null,
            ':amin'=>$data['after_min']??null, ':amid'=>$data['after_mid']??null, ':amax'=>$data['after_max']??null,
            ':pct'=>$data['percentage']??null, ':r'=>$data['reason']??null, ':eff'=>$data['effective_date']??null, ':by'=>$data['created_by']??null
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function setStatus(int $id, string $status, ?int $userId)
    {
        $col = $status==='Approved' ? 'ApprovedBy' : ($status==='Implemented' ? 'ImplementedBy' : ($status==='Pending Review' ? 'ReviewedBy' : null));
        $params = [':id'=>$id, ':status'=>$status];
        $sql = "UPDATE grade_revisions SET Status=:status";
        if ($col) { $sql .= ", {$col}=:uid"; $params[':uid']=$userId; }
        $sql .= ", UpdatedAt=NOW() WHERE RevisionID=:id";
        $stmt = $this->pdo->prepare($sql); return $stmt->execute($params);
    }

    public function implement(int $revisionId, int $implementedBy)
    {
        // Fetch revision
        $stmt = $this->pdo->prepare("SELECT * FROM grade_revisions WHERE RevisionID=:id");
        $stmt->execute([':id'=>$revisionId]);
        $rev = $stmt->fetch(PDO::FETCH_ASSOC); if (!$rev) return false;

        $this->pdo->beginTransaction();
        try {
            // Update salary_grades ranges
            $ug = $this->pdo->prepare("UPDATE salary_grades SET EffectiveDate = COALESCE(:eff, EffectiveDate), UpdatedAt=NOW() WHERE GradeID=:g");
            $ug->execute([':eff'=>$rev['EffectiveDate'], ':g'=>$rev['GradeID']]);

            // If we have range columns in salary_grades, update them; otherwise update steps base via percentage
            $hasRange = false;
            // Attempt update columns if they exist
            try {
                $set = [];
                $params = [':g'=>$rev['GradeID']];
                if ($rev['AfterMin']!==null) { $set[]='MinRate=:min'; $params[':min']=$rev['AfterMin']; $hasRange=true; }
                if ($rev['AfterMid']!==null) { $set[]='MidRate=:mid'; $params[':mid']=$rev['AfterMid']; $hasRange=true; }
                if ($rev['AfterMax']!==null) { $set[]='MaxRate=:max'; $params[':max']=$rev['AfterMax']; $hasRange=true; }
                if ($set) {
                    $sql = 'UPDATE salary_grades SET '.implode(',', $set).', UpdatedAt=NOW() WHERE GradeID=:g';
                    $this->pdo->prepare($sql)->execute($params);
                }
            } catch (\Throwable $e) { /* ignore */ }

            // If percentage provided, update salary_steps.BaseRate by that percentage
            if (!$hasRange && $rev['Percentage']!==null) {
                $pct = (float)$rev['Percentage'];
                $this->pdo->prepare("UPDATE salary_steps SET BaseRate = ROUND(BaseRate * (1 + (:pct/100)),2), UpdatedAt=NOW() WHERE GradeID=:g")
                    ->execute([':pct'=>$pct, ':g'=>$rev['GradeID']]);
            }

            // Auto-create salary adjustment drafts for employees in this grade
            $emps = $this->pdo->prepare("SELECT egm.EmployeeID, egm.CurrentSalary FROM employee_grade_mapping egm WHERE egm.GradeID=:g AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())");
            $emps->execute([':g'=>$rev['GradeID']]);
            $rows = $emps->fetchAll(PDO::FETCH_ASSOC);

            // Compute new salary by percentage if provided; otherwise leave same for recalculation by HR
            $pct = $rev['Percentage']!==null ? (float)$rev['Percentage'] : null;
            foreach ($rows as $row) {
                $new = $pct!==null ? round((float)$row['CurrentSalary'] * (1 + $pct/100), 2) : (float)$row['CurrentSalary'];
                $sqlAdj = "INSERT INTO salary_adjustments (EmployeeID, DepartmentID, OldSalary, NewSalary, GradeID, StepID, ReasonID, Justification, EffectiveDate, Status, InitiatedBy, CreatedAt, UpdatedAt)
                           VALUES (:emp, NULL, :old, :new, :g, NULL, NULL, :just, :eff, 'Pending Review', :by, NOW(), NOW())";
                $this->pdo->prepare($sqlAdj)->execute([
                    ':emp'=>$row['EmployeeID'], ':old'=>$row['CurrentSalary'], ':new'=>$new, ':g'=>$rev['GradeID'], ':just'=>'Auto-generated from Grade Revision #'.$revisionId, ':eff'=>$rev['EffectiveDate'] ?? date('Y-m-d'), ':by'=>$implementedBy
                ]);
            }

            // Mark revision implemented
            $this->setStatus($revisionId, 'Implemented', $implementedBy);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}


