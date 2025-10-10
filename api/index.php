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
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include configuration and establish database connection
require_once __DIR__ . '/config.php';

// Use the same session configuration as the legacy system
require_once __DIR__ . '/../php/session_config_stable.php';

// Set headers for CORS and JSON responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Request.php';
require_once __DIR__ . '/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/middlewares/ErrorHandler.php';

// Initialize error handler
$errorHandler = new ErrorHandler();

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

// Remove 'api' from the beginning if present
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
} elseif (isset($segments[1]) && $segments[1] === 'api') {
    // Handle case where path includes application directory
    array_shift($segments); // Remove application directory
    array_shift($segments); // Remove 'api'
}

// Route the request
try {
    // Authentication routes (no auth required)
    if ($segments[0] === 'auth') {
        switch ($segments[1]) {
            case 'login':
                if ($method === 'POST') {
                    require_once __DIR__ . '/routes/auth.php';
                    $authController = new AuthController();
                    $authController->login();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'logout':
                if ($method === 'POST') {
                    require_once __DIR__ . '/routes/auth.php';
                    $authController = new AuthController();
                    $authController->logout();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'verify-2fa':
                if ($method === 'POST') {
                    require_once __DIR__ . '/routes/auth.php';
                    $authController = new AuthController();
                    $authController->verify2FA();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'check-session':
                if ($method === 'GET') {
                    require_once __DIR__ . '/routes/auth.php';
                    $authController = new AuthController();
                    $authController->checkSession();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            case 'reset-password':
                if ($method === 'POST') {
                    require_once __DIR__ . '/routes/auth.php';
                    $authController = new AuthController();
                    $authController->resetPassword();
                } else {
                    Response::methodNotAllowed();
                }
                break;
            default:
                Response::notFound();
        }
    }
    // Protected routes (require authentication)
    else {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        // Check authentication
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
                require_once __DIR__ . '/routes/users.php';
                $controller = new UsersController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'documents':
                require_once __DIR__ . '/routes/documents.php';
                $controller = new DocumentsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'hrcore':
                require_once __DIR__ . '/routes/hrcore.php';
                $controller = new HRCoreController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'employees':
                require_once __DIR__ . '/routes/employees.php';
                $controller = new EmployeesController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'org-structure':
                require_once __DIR__ . '/routes/org_structure.php';
                $controller = new OrgStructureController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'positions':
                require_once __DIR__ . '/routes/positions.php';
                $controller = new PositionsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'departments':
                require_once __DIR__ . '/routes/departments.php';
                $controller = new DepartmentsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'benefits':
                require_once __DIR__ . '/routes/benefits.php';
                $controller = new BenefitsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'payroll':
                require_once __DIR__ . '/routes/payroll.php';
                $controller = new PayrollController();
                $controller->handleRequest($method, $id, $subResource);
                break;
        case 'payroll-v2':
            require_once __DIR__ . '/routes/payroll_v2.php';
            $controller = new PayrollV2Controller();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'salaries':
            require_once __DIR__ . '/routes/salaries.php';
            $controller = new SalariesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'bonuses':
            require_once __DIR__ . '/routes/bonuses.php';
            $controller = new BonusesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'deductions':
            require_once __DIR__ . '/routes/deductions.php';
            $controller = new DeductionsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'payslips':
            require_once __DIR__ . '/routes/payslips.php';
            $controller = new PayslipsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'branches':
            require_once __DIR__ . '/routes/branches.php';
            $controller = new BranchesController();
            $controller->handleRequest($method, $id, $subResource);
            break;
            case 'attendance':
                require_once __DIR__ . '/routes/attendance.php';
                $controller = new AttendanceController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'leave':
                require_once __DIR__ . '/routes/leave.php';
                $controller = new LeaveController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'hmo':
                require_once __DIR__ . '/routes/hmo.php';
                $controller = new HMOController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'reports':
                Response::notFound('Reports endpoint not implemented');
                break;
            case 'integrations':
                require_once __DIR__ . '/routes/integrations.php';
                // The integrations.php file handles its own routing
                break;
            case 'dashboard':
                require_once __DIR__ . '/routes/dashboard.php';
                $controller = new DashboardController();
                $controller->handleRequest($method, $id, $subResource);
                break;
            case 'compensation-planning':
                require_once __DIR__ . '/routes/compensation_planning.php';
                // The compensation_planning.php file handles its own routing
                break;
            case 'hr-analytics':
                require_once __DIR__ . '/routes/hr_analytics.php';
                $controller = new HRAnalyticsController();
                $controller->handleRequest($method, $id, $subResource);
                break;
        case 'hr-reports':
            require_once __DIR__ . '/routes/hr_reports.php';
            $controller = new HRReportsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        case 'hr-analytics-metrics':
            require_once __DIR__ . '/routes/hr_analytics_metrics.php';
            $controller = new HRAnalyticsMetricsController();
            $controller->handleRequest($method, $id, $subResource);
            break;
        default:
            Response::notFound();
        }
    }
} catch (Exception $e) {
    $errorHandler->handle($e);
}
