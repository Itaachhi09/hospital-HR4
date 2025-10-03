<?php
/**
 * API Endpoint: Delete Document
 * Deletes an EmployeeDocuments record and its file from disk.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

require_once '../db_connect.php';
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Parse JSON body
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

$documentId = isset($payload['document_id']) ? (int)$payload['document_id'] : 0;
if ($documentId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid document_id is required.']);
    exit;
}

try {
    // Fetch file path
    $select = $pdo->prepare("SELECT FilePath FROM EmployeeDocuments WHERE DocumentID = :id");
    $select->execute([':id' => $documentId]);
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Document not found.']);
        exit;
    }

    $dbFilePath = $row['FilePath']; // e.g., uploads/documents/filename.ext

    // Begin transaction
    $pdo->beginTransaction();

    // Delete DB record
    $del = $pdo->prepare("DELETE FROM EmployeeDocuments WHERE DocumentID = :id");
    $del->execute([':id' => $documentId]);

    // Attempt to delete file on disk (best effort)
    if ($dbFilePath) {
        $absolutePath = realpath(__DIR__ . '/../../' . $dbFilePath);
        if ($absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Document deleted successfully.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Delete Document Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete document.']);
}

?>


