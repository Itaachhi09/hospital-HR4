<?php
/**
 * Org Structure Routes (REST)
 * - GET /api/org-structure => hierarchical org data
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Department.php';

class OrgStructureController {
    private $pdo;
    private $auth;
    private $departmentModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->departmentModel = new Department();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        $res = $_GET['view'] ?? null; // e.g., roles, positions
        if ($method === 'GET') {
            if ($id === 'snapshot') return $this->getSnapshot();
            return $this->getHierarchy();
        }
        if ($method === 'POST' && $id === 'snapshot') {
            return $this->createSnapshot();
        }
        return Response::methodNotAllowed();
    }

    private function getHierarchy() {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $data = $this->departmentModel->getHospitalOrgHierarchy();
        Response::success($data);
    }

    private function createSnapshot() {
        if (!$this->auth->authenticate() || !$this->auth->hasAnyRole(['System Admin','HR Manager'])) {
            Response::forbidden('Insufficient permissions');
        }
        $request = new Request();
        $name = $request->getData('name');
        $notes = $request->getData('notes');
        try{
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO OrgStructureSnapshots (SnapshotName, TakenAt, TakenBy, Notes) VALUES (:n, NOW(), :uid, :notes)");
            $uid = $this->auth->getCurrentUser()['user_id'] ?? null;
            $stmt->bindParam(':n', $name, PDO::PARAM_STR);
            if ($uid === null) { $stmt->bindValue(':uid', null, PDO::PARAM_NULL); } else { $stmt->bindParam(':uid', $uid, PDO::PARAM_INT); }
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();
            $snapshotId = (int)$this->pdo->lastInsertId();
            // capture current hierarchy
            $rows = $this->departmentModel->getHospitalOrgHierarchy();
            $ins = $this->pdo->prepare("INSERT INTO OrgStructureSnapshotNodes (SnapshotID, DepartmentID, ParentDepartmentID, DepartmentName, DepartmentType, ManagerID, EmployeeCount) VALUES (:sid,:did,:pdid,:name,:type,:mgr,:ec)");
            foreach ($rows as $r){
                $ins->bindValue(':sid', $snapshotId, PDO::PARAM_INT);
                $ins->bindValue(':did', $r['DepartmentID'], PDO::PARAM_INT);
                $ins->bindValue(':pdid', $r['ParentDepartmentID'], PDO::PARAM_INT);
                $ins->bindValue(':name', $r['DepartmentName'], PDO::PARAM_STR);
                $ins->bindValue(':type', $r['DepartmentType'] ?? null, PDO::PARAM_STR);
                $ins->bindValue(':mgr', $r['ManagerID'] ?? null, PDO::PARAM_INT);
                $ins->bindValue(':ec', $r['EmployeeCount'] ?? 0, PDO::PARAM_INT);
                $ins->execute();
            }
            $this->pdo->commit();
            Response::created(['snapshot_id' => $snapshotId], 'Snapshot created');
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('Snapshot error: '.$e->getMessage());
            Response::error('Failed to create snapshot', 500);
        }
    }

    private function getSnapshot() {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $sid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($sid <= 0) Response::validationError(['id' => 'Snapshot id required']);
        $stmt = $this->pdo->prepare("SELECT * FROM OrgStructureSnapshotNodes WHERE SnapshotID = :sid ORDER BY DepartmentName");
        $stmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success($rows);
    }
}
