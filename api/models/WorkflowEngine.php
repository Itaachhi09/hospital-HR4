<?php

namespace App\Models;

use PDO;

class WorkflowEngine
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS workflow_definitions (
            DefinitionID INT AUTO_INCREMENT PRIMARY KEY,
            Name VARCHAR(150) NOT NULL,
            Type VARCHAR(50) NOT NULL,
            StepsJSON JSON NOT NULL,
            ConditionsJSON JSON NULL,
            Status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
            CreatedBy INT NULL,
            CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS workflow_instances (
            InstanceID INT AUTO_INCREMENT PRIMARY KEY,
            DefinitionID INT NOT NULL,
            EntityType VARCHAR(50) NOT NULL,
            EntityID INT NOT NULL,
            Status ENUM('Pending','Under Review','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
            CurrentStepIndex INT NOT NULL DEFAULT 0,
            InitiatedBy INT NULL,
            CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_entity (EntityType, EntityID),
            INDEX idx_def (DefinitionID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS workflow_actions (
            ActionID INT AUTO_INCREMENT PRIMARY KEY,
            InstanceID INT NOT NULL,
            StepIndex INT NOT NULL,
            Action ENUM('approve','reject','return') NOT NULL,
            ActorID INT NULL,
            Comment TEXT NULL,
            CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_inst (InstanceID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Definitions
    public function listDefinitions(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM workflow_definitions ORDER BY UpdatedAt DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createDefinition(array $data)
    {
        $sql = "INSERT INTO workflow_definitions (Name, Type, StepsJSON, ConditionsJSON, Status, CreatedBy)
                VALUES (:n, :t, :s, :c, :st, :by)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':n'=>$data['name'], ':t'=>$data['type'], ':s'=>json_encode($data['steps'] ?? []),
            ':c'=>!empty($data['conditions'])? json_encode($data['conditions']) : null,
            ':st'=>$data['status'] ?? 'Active', ':by'=>$data['created_by'] ?? null
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function updateDefinition(int $id, array $data): bool
    {
        $sql = "UPDATE workflow_definitions SET Name=:n, Type=:t, StepsJSON=:s, ConditionsJSON=:c, Status=:st, UpdatedAt=NOW() WHERE DefinitionID=:id";
        $stmt=$this->pdo->prepare($sql);
        return $stmt->execute([
            ':n'=>$data['name'], ':t'=>$data['type'], ':s'=>json_encode($data['steps'] ?? []),
            ':c'=>!empty($data['conditions'])? json_encode($data['conditions']) : null,
            ':st'=>$data['status'] ?? 'Active', ':id'=>$id
        ]);
    }

    public function deleteDefinition(int $id): bool
    {
        $stmt=$this->pdo->prepare("DELETE FROM workflow_definitions WHERE DefinitionID=:id");
        return $stmt->execute([':id'=>$id]);
    }

    // Instances
    public function startInstance(int $definitionId, string $entityType, int $entityId, ?int $initiatedBy)
    {
        $sql = "INSERT INTO workflow_instances (DefinitionID, EntityType, EntityID, Status, CurrentStepIndex, InitiatedBy) VALUES (:d, :e, :i, 'Pending', 0, :by)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([':d'=>$definitionId, ':e'=>$entityType, ':i'=>$entityId, ':by'=>$initiatedBy]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function myApprovals(int $userId, array $roles = []): array
    {
        // Minimal: return all pending instances; a full implementation would parse steps_json and match roles/users for current step
        $stmt = $this->pdo->query("SELECT wi.*, wd.Name, wd.Type FROM workflow_instances wi LEFT JOIN workflow_definitions wd ON wi.DefinitionID=wd.DefinitionID WHERE wi.Status IN ('Pending','Under Review') ORDER BY wi.UpdatedAt DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function act(int $instanceId, string $action, ?int $actorId, ?string $comment)
    {
        $this->pdo->beginTransaction();
        try {
            // Record action
            $this->pdo->prepare("INSERT INTO workflow_actions (InstanceID, StepIndex, Action, ActorID, Comment) SELECT :id, CurrentStepIndex, :a, :u, :c FROM workflow_instances WHERE InstanceID=:id")
                ->execute([':id'=>$instanceId, ':a'=>$action, ':u'=>$actorId, ':c'=>$comment]);
            // Advance or finish
            $row = $this->pdo->query("SELECT wi.*, wd.StepsJSON FROM workflow_instances wi LEFT JOIN workflow_definitions wd ON wi.DefinitionID=wd.DefinitionID WHERE wi.InstanceID=".(int)$instanceId)->fetch(PDO::FETCH_ASSOC);
            $steps = json_decode($row['StepsJSON'] ?? '[]', true) ?: [];
            $cur = (int)$row['CurrentStepIndex'];
            if ($action === 'approve') {
                if ($cur + 1 < count($steps)) {
                    $this->pdo->prepare("UPDATE workflow_instances SET CurrentStepIndex=CurrentStepIndex+1, Status='Under Review', UpdatedAt=NOW() WHERE InstanceID=:id")->execute([':id'=>$instanceId]);
                } else {
                    $this->pdo->prepare("UPDATE workflow_instances SET Status='Approved', UpdatedAt=NOW() WHERE InstanceID=:id")->execute([':id'=>$instanceId]);
                }
            } elseif ($action === 'reject') {
                $this->pdo->prepare("UPDATE workflow_instances SET Status='Rejected', UpdatedAt=NOW() WHERE InstanceID=:id")->execute([':id'=>$instanceId]);
            } else { // return
                $this->pdo->prepare("UPDATE workflow_instances SET Status='Pending', UpdatedAt=NOW() WHERE InstanceID=:id")->execute([':id'=>$instanceId]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack(); throw $e;
        }
    }
}


