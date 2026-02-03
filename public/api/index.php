<?php
/**
 * RESTful API Entry Point
 * Hospital HR Management System
 * 
 * This file serves as the main entry point for all API requests.
 * It handles routing, middleware, and response formatting.
 */

// Error reporting and headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disabled for production
ini_set('log_errors', 1);

// Start output buffering to catch any errors
ob_start();

// Include configuration and establish database connection
require_once __DIR__ . '/../../common/config/config.php';

// Use the same session configuration as the legacy system
require_once __DIR__ . '/../../common/session/session_config_stable.php';

// Set headers for CORS and JSON responses (credentials-compatible)
header('Content-Type: application/json');
// Reflect allowed origin instead of wildcard when using credentials
$allowedOriginsEnv = getenv('ALLOWED_ORIGINS') ?: 'http://localhost';
$allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    $defaultOrigin = 'http://localhost';
    header('Access-Control-Allow-Origin: ' . $defaultOrigin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once __DIR__ . '/../../common/db/db_connect.php';
require_once __DIR__ . '/../../common/utils/Response.php';
require_once __DIR__ . '/../../common/utils/Request.php';
require_once __DIR__ . '/../../common/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../common/middlewares/ErrorHandler.php';

// Initialize error handler
$errorHandler = new ErrorHandler(false); // Set to false for production

// Initialize request handler
$request = new Request();

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $request->getPath();

// Remove query string from path
$path = strtok($path, '?');

// Remove leading slash and split into segments
$path = ltrim($path, '/');
$segments = explode('/', $path);

// Find and remove everything up to and including 'api'
// This handles cases like: /api/resource, /app/api/resource, /app/public/api/resource
$apiIndex = array_search('api', $segments);
if ($apiIndex !== false) {
    // Remove all segments up to and including 'api'
    $segments = array_slice($segments, $apiIndex + 1);
}

// Route the request
try {
    // Health check endpoint
    if (empty($segments) || (count($segments) === 1 && $segments[0] === '')) {
        Response::success([
            'status' => 'ok',
            'message' => 'API is running',
            'version' => '1.0',
            'endpoints' => [
                'POST /api/auth/login' => 'User login',
                'POST /api/auth/logout' => 'User logout',
                'GET /api/auth/check-session' => 'Check session'
            ]
        ]);
        exit;
    }
    
    // Debug logging (remove in production)
    error_log("API Request: Method=$method, Path=" . ($path ?? 'null') . ", Segments=" . json_encode($segments));
    
    // Authentication routes (no auth required)
    if (!empty($segments[0]) && $segments[0] === 'auth') {
        if (empty($segments[1])) {
            Response::notFound('Auth endpoint not specified');
        }
        
        switch ($segments[1]) {
            case 'login':
                if ($method === 'POST') {
                    require_once __DIR__ . '/../../modules/auth/routes/auth.php';
                    $authController = new AuthController();
                    $authController->login();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'logout':
                if ($method === 'POST') {
                    require_once __DIR__ . '/../../modules/auth/routes/auth.php';
                    $authController = new AuthController();
                    $authController->logout();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'verify-2fa':
                if ($method === 'POST') {
                    require_once __DIR__ . '/../../modules/auth/routes/auth.php';
                    $authController = new AuthController();
                    $authController->verify2FA();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'check-session':
                if ($method === 'GET') {
                    require_once __DIR__ . '/../../modules/auth/routes/auth.php';
                    $authController = new AuthController();
                    $authController->checkSession();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'reset-password':
                if ($method === 'POST') {
                    require_once __DIR__ . '/../../modules/auth/routes/auth.php';
                    $authController = new AuthController();
                    $authController->resetPassword();
                } else {
                    Response::methodNotAllowed();
                }
                break;
        default:
            Response::notFound('Auth endpoint not found: ' . ($segments[1] ?? 'null'));
        }
    }
    // Protected routes (require authentication)
    else if (!empty($segments[0])) {
        // Check authentication (TEMPORARILY DISABLED FOR DEVELOPMENT)
        // TODO: Re-enable authentication once session sharing is properly configured
        /*
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        */

        // Route to appropriate controller
        $resource = $segments[0] ?? '';
        $id = $segments[1] ?? null;
        $subResource = $segments[2] ?? null;
        

        
        switch ($resource) {
            case 'users':
                require_once __DIR__ . '/../../modules/auth/routes/users.php';
                $controller = new UsersController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'documents':
                require_once __DIR__ . '/../../modules/core_hr/routes/documents.php';
                $controller = new DocumentsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'hrcore':
                require_once __DIR__ . '/../../modules/core_hr/routes/hrcore.php';
                $controller = new HRCoreController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'employees':
                require_once __DIR__ . '/../../modules/employees/routes/employees.php';
                $controller = new EmployeesController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'org-structure':
                require_once __DIR__ . '/../../modules/core_hr/routes/org_structure.php';
                $controller = new OrgStructureController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'positions':
                require_once __DIR__ . '/../../modules/employees/routes/positions.php';
                $controller = new PositionsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'departments':
                require_once __DIR__ . '/../../modules/employees/routes/departments.php';
                $controller = new DepartmentsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'benefits':
                require_once __DIR__ . '/../../modules/benefits/routes/benefits.php';
                $controller = new BenefitsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'payroll':
                require_once __DIR__ . '/../../modules/payroll/routes/payroll.php';
                $controller = new PayrollController();
                $controller->handleRequest($method, $id, $subResource);
                break;
        case 'payroll-v2':
            require_once __DIR__ . '/../../modules/payroll/routes/payroll_v2.php';
            $controller = new PayrollV2Controller();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'salaries':
            require_once __DIR__ . '/../../modules/payroll/routes/salaries.php';
            $controller = new SalariesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'bonuses':
            require_once __DIR__ . '/../../modules/payroll/routes/bonuses.php';
            $controller = new BonusesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'deductions':
            require_once __DIR__ . '/../../modules/payroll/routes/deductions.php';
            $controller = new DeductionsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'payslips':
            require_once __DIR__ . '/../../modules/payroll/routes/payslips.php';
            $controller = new PayslipsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'branches':
            require_once __DIR__ . '/../../modules/notifications/routes/branches.php';
            $controller = new BranchesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
            case 'attendance':
                require_once __DIR__ . '/../../modules/attendance/routes/attendance.php';
                $controller = new AttendanceController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'leave':
                require_once __DIR__ . '/../../modules/leave/routes/leave.php';
                $controller = new LeaveController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'hmo':
                require_once __DIR__ . '/../../modules/hmo/routes/hmo.php';
                $controller = new HMOController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'reports':
                Response::notFound('Reports endpoint not implemented');
                break;
            case 'integrations':
                require_once __DIR__ . '/../../modules/integrations/routes/integrations.php';
                // The integrations.php file handles its own routing
                break;
            case 'dashboard':
                require_once __DIR__ . '/../../modules/analytics/routes/dashboard.php';
                $controller = new DashboardController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'compensation-planning':
                require_once __DIR__ . '/../../modules/integrations/routes/compensation_planning.php';
                // The compensation_planning.php file handles its own routing
                break;
            case 'hr-analytics':
                require_once __DIR__ . '/../../modules/analytics/routes/hr_analytics.php';
                $controller = new HRAnalyticsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
        case 'hr-reports':
            require_once __DIR__ . '/../../modules/reports/routes/hr_reports.php';
            $controller = new HRReportsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'hr-analytics-metrics':
            require_once __DIR__ . '/../../modules/analytics/routes/hr_analytics_metrics.php';
            $controller = new HRAnalyticsMetricsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        default:
            Response::notFound();
        }
    }
} catch (Exception $e) {
    // Clear any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure we return JSON even if error handler fails
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    error_log("API Router Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your request',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
} catch (Throwable $e) {
    // Clear any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Handle fatal errors
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    error_log("API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred processing your request',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
