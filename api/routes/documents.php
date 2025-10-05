<?php
/**
 * Documents Routes (REST)
 * - GET /api/documents?employee_id=... => list documents (HR/Admin restricted, own for Employee)
 * - GET /api/employees/{id}/documents => list documents for an employee
 * - POST /api/employees/{id}/documents => upload document (multipart)
 * - GET /api/documents/{id}/download => secure download (auth required)
 * - DELETE /api/documents/{id} => delete document (RBAC enforced)
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Document.php';

class DocumentsController {
    private $pdo;
    private $auth;
    private $docModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->docModel = new Document();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Support subresource mapping when called under /api/employees/{id}/documents
        $path = $this->getPathSegments();
        if (!empty($path) && $path[0] === 'employees' && isset($path[1]) && isset($path[2]) && $path[2] === 'documents') {
            $employeeId = (int)$path[1];
            if ($method === 'GET') return $this->listEmployeeDocuments($employeeId);
            if ($method === 'POST') return $this->uploadEmployeeDocument($employeeId);
            return Response::methodNotAllowed();
        }

        switch ($method) {
            case 'GET':
                if ($id && $subResource === 'download') {
                    return $this->downloadDocument((int)$id);
                }
                return $this->listDocuments();
            case 'DELETE':
                if (!$id) return Response::methodNotAllowed();
                return $this->deleteDocument((int)$id);
            default:
                return Response::methodNotAllowed();
        }
    }

    private function listDocuments() {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $request = new Request();
        $filters = ['employee_id' => $request->getData('employee_id')];
        $current = $this->auth->getCurrentUser();
        if (($current['role_name'] ?? '') === 'Employee') {
            // Employees can only see own docs
            $filters['employee_id'] = $current['employee_id'];
        }
        $docs = $this->docModel->listAll($filters);
        Response::success($docs);
    }

    private function listEmployeeDocuments($employeeId) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $current = $this->auth->getCurrentUser();
        if ($current['employee_id'] != $employeeId && !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }
        $docs = $this->docModel->listByEmployee($employeeId);
        Response::success($docs);
    }

    private function uploadEmployeeDocument($employeeId) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $current = $this->auth->getCurrentUser();
        if ($current['employee_id'] != $employeeId && !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        // Expect multipart form-data
        $documentType = $_POST['document_type'] ?? '';
        $category = $_POST['category'] ?? null; // auto-tagging category
        $expiresOn = $_POST['expires_on'] ?? null; // optional expiry date (YYYY-MM-DD)
        if (empty($documentType)) {
            Response::validationError(['document_type' => 'Document type is required']);
        }
        if (!isset($_FILES['document_file'])) {
            Response::validationError(['document_file' => 'Document file is required']);
        }
        $file = $_FILES['document_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('File upload error: ' . (string)$file['error'], 400);
        }

        $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            Response::validationError(['document_file' => 'Unsupported file type']);
        }
        if ($file['size'] > 5*1024*1024) {
            Response::validationError(['document_file' => 'File too large (max 5MB)']);
        }

        // Store under per-employee directory
        $storageDir = __DIR__ . '/../../storage/documents/employees/' . $employeeId . '/docs/';
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true)) {
                Response::error('Failed to create storage directory', 500);
            }
        }

        $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $unique = $employeeId . '_' . time() . '_' . bin2hex(random_bytes(4));
        $finalName = $safeBase . '_' . $unique . '.' . $ext;
        $absoluteTarget = $storageDir . $finalName;

        if (!move_uploaded_file($file['tmp_name'], $absoluteTarget)) {
            Response::error('Failed to move uploaded file', 500);
        }

        // Store relative path (non-web root); serve via download endpoint only
        $dbPath = 'storage/documents/employees/' . $employeeId . '/docs/' . $finalName;
        $checksum = hash_file('sha256', $absoluteTarget);
        $uploaderUserId = ($this->auth->getCurrentUser()['user_id'] ?? null);
        $version = $this->docModel->nextVersion($employeeId, $documentType, $category);
        $newId = $this->docModel->create($employeeId, $documentType, $originalName, $dbPath, $category, $expiresOn, $uploaderUserId, $checksum, $version);
        $this->docModel->createAudit($newId, $uploaderUserId, 'Upload', json_encode(['file'=>$originalName,'version'=>$version]));
        Response::created(['document_id' => $newId, 'file_path' => $dbPath, 'version' => $version], 'Document uploaded successfully');
    }

    private function deleteDocument($documentId) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $doc = $this->docModel->getById($documentId);
        if (!$doc) {
            Response::notFound('Document not found');
        }
        $current = $this->auth->getCurrentUser();
        if ($current['employee_id'] != $doc['EmployeeID'] && !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }
        // Try deleting file first (best effort)
        $absolute = __DIR__ . '/../../' . $doc['FilePath'];
        if (is_file($absolute)) { @unlink($absolute); }
        $this->docModel->delete($documentId);
        $this->docModel->createAudit($documentId, $current['user_id'] ?? null, 'Delete');
        Response::success(null, 'Document deleted');
    }

    private function downloadDocument($documentId) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $token = $_GET['token'] ?? null;
        if ($token) {
            $resolved = $this->docModel->resolveAccessToken($token);
            if (!$resolved) { Response::forbidden('Invalid or expired token'); }
            $absolute = __DIR__ . '/../../' . $resolved['FilePath'];
            if (!is_file($absolute)) { Response::notFound('File missing on server'); }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($resolved['DocumentName']) . '"');
            header('Content-Length: ' . filesize($absolute));
            readfile($absolute);
            exit;
        }

        $doc = $this->docModel->getById($documentId);
        if (!$doc) {
            Response::notFound('Document not found');
        }
        $current = $this->auth->getCurrentUser();
        if ($current['employee_id'] != $doc['EmployeeID'] && !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }
        $absolute = __DIR__ . '/../../' . $doc['FilePath'];
        if (!is_file($absolute)) {
            Response::notFound('File missing on server');
        }
        // Stream file securely
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['DocumentName']) . '"');
        header('Content-Length: ' . filesize($absolute));
        readfile($absolute);
        exit;
    }

    // Optional: issue a short-lived tokenized URL for secure sharing
    private function issueDownloadToken($documentId, $ttlSeconds = 600) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $current = $this->auth->getCurrentUser();
        if (!$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin']) && !$current['employee_id']) {
            Response::forbidden('Insufficient permissions');
        }
        $tokenInfo = $this->docModel->createAccessToken($documentId, $ttlSeconds, $current['user_id'] ?? null);
        Response::success($tokenInfo, 'Access token created');
    }

    private function getPathSegments() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($uri, '?');
        $path = ltrim($path, '/');
        $segments = explode('/', $path);
        if (isset($segments[0]) && $segments[0] === 'api') array_shift($segments);
        return $segments;
    }
}
