<?php
/**
 * Auth Module Entry Point
 * Handles routing for authentication and user management
 */

require_once __DIR__ . '/../../common/config/config.php';
require_once __DIR__ . '/../../common/session/session_config_stable.php';
require_once __DIR__ . '/../../common/db/db_connect.php';
require_once __DIR__ . '/../../common/utils/Response.php';
require_once __DIR__ . '/../../common/utils/Request.php';

header('Content-Type: application/json');

// Route to appropriate handler based on request
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Remove 'modules/auth' from segments if present
if (isset($segments[0]) && $segments[0] === 'modules') {
    array_shift($segments);
}
if (isset($segments[0]) && $segments[0] === 'auth') {
    array_shift($segments);
}

// Route to API file or route handler
$action = $segments[0] ?? 'index';

if (file_exists(__DIR__ . '/api/' . $action . '.php')) {
    require_once __DIR__ . '/api/' . $action . '.php';
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}

