<?php
/**
 * API Gateway - Version 2
 * 
 * Centralized entry point for all v2 API requests.
 * Handles:
 * - Authentication & Authorization
 * - Rate Limiting
 * - Request Routing
 * - Error Handling
 * - Logging
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Include configuration
require_once __DIR__ . '/../../../common/config/config.php';
require_once __DIR__ . '/../../../common/session/session_config_stable.php';
require_once __DIR__ . '/../../../common/db/db_connect.php';

// Set CORS headers
header('Content-Type: application/json');
$allowedOriginsEnv = getenv('ALLOWED_ORIGINS') ?: 'http://localhost';
$allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include utilities
require_once __DIR__ . '/../../../common/utils/Response.php';
require_once __DIR__ . '/../../../common/utils/Request.php';
require_once __DIR__ . '/../../../common/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../../common/middlewares/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../common/middlewares/RBACMiddleware.php';

// Initialize request
$request = new Request();
$method = $_SERVER['REQUEST_METHOD'];
$path = $request->getPath();

// Remove query string
$path = strtok($path, '?');

// Parse path: /api/v2/{module}/{resource}/{id}
$path = ltrim($path, '/');
$segments = explode('/', $path);

// Find 'v2' in path
$v2Index = array_search('v2', $segments);
if ($v2Index === false) {
    Response::error('Invalid API version', 400);
    exit;
}

// Get segments after v2
$segments = array_slice($segments, $v2Index + 1);

// Route request
try {
    // Health check
    if (empty($segments) || (count($segments) === 1 && $segments[0] === '')) {
        Response::success([
            'status' => 'ok',
            'version' => '2.0',
            'message' => 'API v2 is running'
        ]);
        exit;
    }
    
    $module = $segments[0] ?? '';
    $resource = $segments[1] ?? '';
    $id = $segments[2] ?? null;
    
    // Apply middleware
    $authMiddleware = new AuthMiddleware();
    if (!$authMiddleware->authenticate()) {
        Response::unauthorized('Authentication required');
        exit;
    }
    
    // Rate limiting
    $rateLimitMiddleware = new RateLimitMiddleware();
    if (!$rateLimitMiddleware->check()) {
        Response::error('Rate limit exceeded', 429);
        exit;
    }
    
    // RBAC check (will be done in controllers)
    
    // Route to module
    switch ($module) {
        case 'hrcore':
            require_once __DIR__ . '/../../../modules/hrcore/api/v2/router.php';
            routeHRCoreV2($method, $resource, $id, $request);
            break;
            
        case 'payroll':
            require_once __DIR__ . '/../../../modules/payroll/api/v2/router.php';
            routePayrollV2($method, $resource, $id, $request);
            break;
            
        case 'hmo':
            require_once __DIR__ . '/../../../modules/hmo/api/v2/router.php';
            routeHMOV2($method, $resource, $id, $request);
            break;
            
        case 'analytics':
            require_once __DIR__ . '/../../../modules/analytics/api/v2/router.php';
            routeAnalyticsV2($method, $resource, $id, $request);
            break;
            
        case 'compensation':
            require_once __DIR__ . '/../../../modules/integrations/api/v2/router.php';
            routeCompensationV2($method, $resource, $id, $request);
            break;
            
        default:
            Response::notFound("Module '{$module}' not found");
    }
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    error_log("API Gateway Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your request',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
