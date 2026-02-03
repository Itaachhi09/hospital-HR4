<?php
/**
 * Document Model
 * Handles employee document CRUD and secure file storage metadata
 */

class Document {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function listByEmployee($employeeId) {
        $sql = "SELECT d.DocumentID, d.EmployeeID,
                       CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                       d.DocumentType, d.DocumentName, d.FilePath, d.UploadedAt
                FROM EmployeeDocuments d
                JOIN Employees e ON d.EmployeeID = e.EmployeeID
                WHERE d.EmployeeID = :eid
                ORDER BY d.UploadedAt DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAll($filters = []) {
        $sql = "SELECT d.DocumentID, d.EmployeeID,
                       CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                       d.DocumentType, d.Category, d.DocumentName,
                       d.FilePath, d.UploadedAt, d.ExpiresOn, d.Version
                FROM EmployeeDocuments d
                JOIN Employees e ON d.EmployeeID = e.EmployeeID
                WHERE 1=1";
        $params = [];
        if (!empty($filters['employee_id'])) { $sql .= " AND d.EmployeeID = :eid"; $params[':eid'] = $filters['employee_id']; }
        if (!empty($filters['document_type'])) { $sql .= " AND d.DocumentType = :dtype"; $params[':dtype'] = $filters['document_type']; }
        if (!empty($filters['category'])) { $sql .= " AND d.Category = :cat"; $params[':cat'] = $filters['category']; }
        if (!empty($filters['search'])) { $sql .= " AND (d.DocumentName LIKE :q OR e.FirstName LIKE :q OR e.LastName LIKE :q)"; $params[':q'] = '%' . $filters['search'] . '%'; }
        if (!empty($filters['expiring_within_days'])) { $sql .= " AND d.ExpiresOn IS NOT NULL AND d.ExpiresOn <= DATE_ADD(CURDATE(), INTERVAL :days DAY)"; $params[':days'] = (int)$filters['expiring_within_days']; }
        $sql .= " ORDER BY d.UploadedAt DESC";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($employeeId, $documentType, $documentName, $filePath, $category = null, $expiresOn = null, $uploadedBy = null, $checksum = null, $version = 1) {
        $sql = "INSERT INTO EmployeeDocuments (EmployeeID, DocumentType, Category, DocumentName, FilePath, UploadedBy, Checksum, ExpiresOn, Version)
                VALUES (:eid, :dtype, :cat, :dname, :fpath, :ub, :chk, :exp, :ver)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':dtype', $documentType, PDO::PARAM_STR);
        $stmt->bindParam(':cat', $category, PDO::PARAM_STR);
        $stmt->bindParam(':dname', $documentName, PDO::PARAM_STR);
        $stmt->bindParam(':fpath', $filePath, PDO::PARAM_STR);
        if ($uploadedBy === null) {
            $stmt->bindValue(':ub', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':ub', $uploadedBy, PDO::PARAM_INT);
        }
        $stmt->bindParam(':chk', $checksum, PDO::PARAM_STR);
        $stmt->bindParam(':exp', $expiresOn, PDO::PARAM_STR);
        $stmt->bindParam(':ver', $version, PDO::PARAM_INT);
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    public function nextVersion($employeeId, $documentType, $category) {
        $sql = "SELECT COALESCE(MAX(Version),0)+1 AS nextVer FROM EmployeeDocuments WHERE EmployeeID = :eid AND DocumentType = :dtype AND (Category = :cat OR (:cat IS NULL AND Category IS NULL))";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':dtype', $documentType, PDO::PARAM_STR);
        $stmt->bindParam(':cat', $category, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['nextVer'] ?? 1);
    }

    public function createAccessToken($documentId, $ttlSeconds = 900, $createdBy = null) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (int)$ttlSeconds);
        $sql = "INSERT INTO DocumentAccessTokens (DocumentID, Token, ExpiresAt, CreatedBy) VALUES (:did, :tok, :exp, :uid)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':did', $documentId, PDO::PARAM_INT);
        $stmt->bindParam(':tok', $token, PDO::PARAM_STR);
        $stmt->bindParam(':exp', $expires, PDO::PARAM_STR);
        $stmt->bindParam(':uid', $createdBy, PDO::PARAM_INT);
        $stmt->execute();
        return [ 'token' => $token, 'expires_at' => $expires ];
    }

    public function resolveAccessToken($token) {
        $sql = "SELECT t.DocumentID, t.ExpiresAt, d.FilePath, d.DocumentName FROM DocumentAccessTokens t JOIN EmployeeDocuments d ON t.DocumentID = d.DocumentID WHERE t.Token = :tok AND t.ExpiresAt > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':tok', $token, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAudit($documentId, $actorUserId, $action, $details = null) {
        try {
            $sql = "INSERT INTO DocumentAuditLogs (DocumentID, ActorUserID, Action, Details) VALUES (:did, :uid, :act, :det)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':did', $documentId, PDO::PARAM_INT);
            if ($actorUserId === null) {
                $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':uid', $actorUserId, PDO::PARAM_INT);
            }
            $stmt->bindParam(':act', $action, PDO::PARAM_STR);
            $stmt->bindParam(':det', $details, PDO::PARAM_STR);
            $stmt->execute();
        } catch (\Throwable $e) {
            error_log('Document audit log failed: ' . $e->getMessage());
        }
    }

    public function getById($documentId) {
        $sql = "SELECT d.*, CONCAT(e.FirstName,' ',e.LastName) AS EmployeeName
                FROM EmployeeDocuments d
                JOIN Employees e ON d.EmployeeID = e.EmployeeID
                WHERE d.DocumentID = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $documentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($documentId) {
        $sql = "DELETE FROM EmployeeDocuments WHERE DocumentID = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $documentId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
