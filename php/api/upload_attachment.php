<?php
// Simple local file upload endpoint for attachments
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error.']);
        exit;
    }

    $baseDir = dirname(__DIR__, 1); // php/api -> php
    $uploadDir = $baseDir . '/uploads/comp_attachments';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    $orig = basename($_FILES['file']['name']);
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
    $name = $safe . '_' . time() . ($ext ? ('.' . $ext) : '');
    $dest = $uploadDir . '/' . $name;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Build URL relative to web root (assuming project root contains uploads)
    $url = '/hospital-HR4/uploads/comp_attachments/' . $name; // adjust base path as needed
    echo json_encode(['success' => true, 'url' => $url, 'filename' => $name]);
} catch (Throwable $e) {
    error_log('upload_attachment error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error during upload']);
}
exit;
?>
