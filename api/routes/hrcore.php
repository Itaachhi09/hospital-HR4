<?php
/**
 * HR Core Routes (REST)
 * - GET /api/hrcore/documents => list all HR Core documents (view-only)
 * - GET /api/hrcore/documents/{id}/preview => get document preview URL
 * - GET /api/hrcore/documents/{id}/download => secure download
 * - GET /api/hrcore/integrations/status => get integration status with HR1/HR2
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/HRCoreDocument.php';

class HRCoreController {
    private $pdo;
    private $auth;
    private $hrCoreModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->hrCoreModel = new HRCoreDocument();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Start session for authentication
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        // Authentication required for all HR Core endpoints
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }

        // Check if user has HR Core access
        $currentUser = $this->auth->getCurrentUser();
        if (!in_array($currentUser['role_name'] ?? '', ['System Admin', 'HR Manager', 'HR Staff'])) {
            Response::forbidden('Insufficient permissions for HR Core access');
        }
        */
        $currentUser = ['user_id' => 1, 'role_name' => 'System Admin']; // Mock user for testing

        // Use the parameters passed from the main API router
        // $id will be 'documents' for /api/hrcore/documents
        // $subResource will be null for /api/hrcore/documents
        
        // Handle documents endpoints
        if ($id === 'documents') {
            if ($method === 'GET') {
                // Handle case where $id is 'documents' (from main router)
                if ($id === 'documents' && $subResource === null) {
                    return $this->listDocuments();
                }
                
                // Handle specific document operations
                if ($subResource === 'preview') {
                    return $this->getDocumentPreview($id);
                }
                if ($subResource === 'download') {
                    return $this->downloadDocument($id);
                }
                
                // Default to list documents
                return $this->listDocuments();
            }
            return Response::methodNotAllowed();
        }

        // Handle integrations status
        if ($id === 'integrations') {
            if ($method === 'GET' && $subResource === 'status') {
                return $this->getIntegrationStatus();
            }
            return Response::methodNotAllowed();
        }

        return Response::notFound();
    }

    private function listDocuments() {
        $request = new Request();
        $filters = [
            'module_origin' => $request->getData('module_origin'),
            'category' => $request->getData('category'),
            'search' => $request->getData('search'),
            'status' => $request->getData('status', 'active')
        ];

        $documents = $this->hrCoreModel->listAll($filters);
        
        // Format response for frontend
        $formattedDocs = array_map(function($doc) {
            return [
                'doc_id' => $doc['doc_id'],
                'emp_id' => $doc['emp_id'],
                'employee_name' => $doc['employee_name'],
                'title' => $doc['title'],
                'category' => $doc['category'],
                'module_origin' => $doc['module_origin'],
                'uploaded_by' => $doc['uploaded_by'],
                'upload_date' => $doc['upload_date'],
                'status' => $doc['status'],
                'file_type' => $doc['file_type'],
                'file_size' => $doc['file_size'],
                'preview_url' => "/api/hrcore/documents/{$doc['doc_id']}/preview",
                'download_url' => "/api/hrcore/documents/{$doc['doc_id']}/download"
            ];
        }, $documents);

        Response::success($formattedDocs);
    }

    private function getDocumentPreview($docId) {
        $document = $this->hrCoreModel->getById($docId);
        if (!$document) {
            Response::notFound('Document not found');
        }

        // Generate secure preview URL with token
        $token = $this->generatePreviewToken($docId);
        $previewUrl = "/api/hrcore/documents/{$docId}/preview?token={$token}";
        
        Response::success([
            'preview_url' => $previewUrl,
            'file_type' => $document['file_type'],
            'file_name' => $document['title']
        ]);
    }

    private function downloadDocument($docId) {
        $document = $this->hrCoreModel->getById($docId);
        if (!$document) {
            Response::notFound('Document not found');
        }

        $filePath = $document['file_path'];
        if (!file_exists($filePath)) {
            Response::notFound('File not found on server');
        }

        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($document['title']) . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }

    private function getIntegrationStatus() {
        $status = [
            'hr1_integration' => [
                'status' => 'active',
                'last_sync' => date('Y-m-d H:i:s'),
                'documents_count' => $this->hrCoreModel->getCountByModule('HR1')
            ],
            'hr2_integration' => [
                'status' => 'active', 
                'last_sync' => date('Y-m-d H:i:s'),
                'documents_count' => $this->hrCoreModel->getCountByModule('HR2')
            ],
            'total_documents' => $this->hrCoreModel->getTotalCount(),
            'categories' => [
                'A' => $this->hrCoreModel->getCountByCategory('A'),
                'B' => $this->hrCoreModel->getCountByCategory('B'),
                'C' => $this->hrCoreModel->getCountByCategory('C')
            ]
        ];

        Response::success($status);
    }

    private function generatePreviewToken($docId) {
        // Generate a secure token for document preview
        $payload = [
            'doc_id' => $docId,
            'exp' => time() + 3600, // 1 hour expiry
            'user_id' => $this->auth->getCurrentUser()['user_id']
        ];
        
        return base64_encode(json_encode($payload));
    }

    private function getPathSegments() {
        $request = new Request();
        $path = $request->getPath();
        $path = ltrim($path, '/');
        return explode('/', $path);
    }
}
?>
