<?php
// Common API bootstrap: JSON headers, session auth, DB

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
// CORS: allow a configured set of origins (comma-separated in ALLOWED_ORIGINS env var)
$allowedOriginsEnv = getenv('ALLOWED_ORIGINS') ?: 'http://localhost';
$allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Default to localhost only to reduce risk (avoid wildcard in production)
    $defaultOrigin = 'http://localhost';
    header('Access-Control-Allow-Origin: ' . $defaultOrigin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Use centralized session configuration
require_once __DIR__ . '/../session_config.php';

require_once __DIR__ . '/../db_connect.php';

function api_require_auth(array $roles = []) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required.']);
        exit;
    }
    if ($roles) {
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, $roles, true)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden.']);
            exit;
        }
    }
}

function api_read_json() {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

?>


