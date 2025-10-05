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
                       d.DocumentType, d.DocumentName, d.FilePath, d.UploadedAt
                FROM EmployeeDocuments d
                JOIN Employees e ON d.EmployeeID = e.EmployeeID
                WHERE 1=1";
        $params = [];
        if (!empty($filters['employee_id'])) { $sql .= " AND d.EmployeeID = :eid"; $params[':eid'] = $filters['employee_id']; }
        if (!empty($filters['document_type'])) { $sql .= " AND d.DocumentType = :dtype"; $params[':dtype'] = $filters['document_type']; }
        $sql .= " ORDER BY d.UploadedAt DESC";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($employeeId, $documentType, $documentName, $filePath) {
        $sql = "INSERT INTO EmployeeDocuments (EmployeeID, DocumentType, DocumentName, FilePath)
                VALUES (:eid, :dtype, :dname, :fpath)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':dtype', $documentType, PDO::PARAM_STR);
        $stmt->bindParam(':dname', $documentName, PDO::PARAM_STR);
        $stmt->bindParam(':fpath', $filePath, PDO::PARAM_STR);
        $stmt->execute();
        return $this->pdo->lastInsertId();
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
