<?php
// Central REST router that delegates to existing endpoint scripts to avoid breaking logic
// Safe-by-default: read-only mapping layer, consistent JSON responses, OPTIONS support, and CORS headers

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// Helper: send JSON error
function send_json_error(int $status, string $message, array $extra = []) {
    if (!headers_sent()) { http_response_code($status); }
    echo json_encode(array_merge(['error' => $message], $extra));
    exit;
}

// Parse path relative to /php/api/rest.php
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Compute base path (e.g., /php/api/rest.php -> /php/api)
$baseDir = rtrim(str_replace('/rest.php', '', $scriptName), '/');

// Extract the path after baseDir
$path = $requestUri;
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

// Normalize to relative like /users, /employees/123, etc., removing /php/api prefix if present
if ($baseDir && strpos($path, $baseDir) === 0) {
    $path = substr($path, strlen($baseDir));
}
$path = '/' . ltrim($path, '/');

// Map REST resources to existing scripts (non-destructive delegation)
// Minimal initial mapping; extend as needed
$routes = [
    // Users
    ['method' => 'GET',    'pattern' => '#^/users$#',                 'script' => __DIR__ . '/get_users.php'],
    // Employees
    ['method' => 'GET',    'pattern' => '#^/employees$#',             'script' => __DIR__ . '/get_employees.php'],
    // Salaries (current)
    ['method' => 'GET',    'pattern' => '#^/salaries$#',              'script' => __DIR__ . '/get_salaries.php'],
    // Login
    ['method' => 'POST',   'pattern' => '#^/login$#',                 'script' => __DIR__ . '/login.php'],
    // Leaves
    ['method' => 'GET',    'pattern' => '#^/leave-types$#',           'script' => __DIR__ . '/get_leave_types.php'],
    ['method' => 'POST',   'pattern' => '#^/leave-types$#',           'script' => __DIR__ . '/add_leave_type.php'],
    ['method' => 'PUT',    'pattern' => '#^/leave-types$#',           'script' => __DIR__ . '/update_leave_type.php'],
    ['method' => 'PATCH',  'pattern' => '#^/leave-requests/status$#', 'script' => __DIR__ . '/update_leave_request_status.php'],
    // Timesheets
    ['method' => 'GET',    'pattern' => '#^/timesheets$#',            'script' => __DIR__ . '/get_timesheets.php'],
    ['method' => 'GET',    'pattern' => '#^/timesheets/([0-9]+)$#',   'script' => __DIR__ . '/get_timesheet_details.php', 'param_to_get' => ['timesheet_id' => 1]],
    ['method' => 'PATCH',  'pattern' => '#^/timesheets/status$#',     'script' => __DIR__ . '/update_timesheet_status.php'],
    // HMO
    ['method' => 'GET',    'pattern' => '#^/hmo/providers$#',         'script' => __DIR__ . '/get_hmo_providers.php'],
    ['method' => 'GET',    'pattern' => '#^/hmo/plans$#',             'script' => __DIR__ . '/get_hmo_plans.php'],
    ['method' => 'GET',    'pattern' => '#^/hmo/claims$#',            'script' => __DIR__ . '/get_hmo_claims.php'],
    ['method' => 'PATCH',  'pattern' => '#^/hmo/claims/status$#',     'script' => __DIR__ . '/update_claims_status.php'],
    ['method' => 'GET',    'pattern' => '#^/hmo/enrollments$#',       'script' => __DIR__ . '/get_hmo_enrollments.php'],
    ['method' => 'GET',    'pattern' => '#^/hmo/benefits$#',          'script' => __DIR__ . '/get_benefits.php'],
    // Payroll
    ['method' => 'GET',    'pattern' => '#^/payroll/runs$#',          'script' => __DIR__ . '/get_payroll_runs.php'],
    ['method' => 'GET',    'pattern' => '#^/payslips$#',              'script' => __DIR__ . '/get_payslips.php'],
    ['method' => 'GET',    'pattern' => '#^/payslips/details$#',      'script' => __DIR__ . '/get_payslip_details.php'],
    // Dashboard/Analytics
    ['method' => 'GET',    'pattern' => '#^/dashboard/summary$#',    'script' => __DIR__ . '/get_dashboard_summary.php'],
    ['method' => 'GET',    'pattern' => '#^/analytics/summary$#',    'script' => __DIR__ . '/get_hr_analytics_summary.php'],
    ['method' => 'GET',    'pattern' => '#^/analytics/key-metrics$#','script' => __DIR__ . '/get_key_metrics.php'],
    // Misc
    ['method' => 'GET',    'pattern' => '#^/documents$#',             'script' => __DIR__ . '/get_documents.php'],
    ['method' => 'POST',   'pattern' => '#^/documents$#',             'script' => __DIR__ . '/upload_document.php'],
];

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Try to match a route
foreach ($routes as $route) {
    if ($route['method'] !== $requestMethod) {
        continue;
    }
    if (preg_match($route['pattern'], $path, $matches)) {
        // Inject path parameters into $_GET for compatibility when declared
        if (!empty($route['param_to_get']) && is_array($route['param_to_get'])) {
            foreach ($route['param_to_get'] as $paramName => $matchIndex) {
                if (isset($matches[$matchIndex])) {
                    $_GET[$paramName] = $matches[$matchIndex];
                }
            }
        }

        // Delegate to the existing script
        if (!is_file($route['script'])) {
            send_json_error(500, 'Mapped script not found', ['script' => basename($route['script'])]);
        }

        // Ensure session behavior in delegated scripts remains intact
        require $route['script'];
        // If the script did not exit, ensure we stop here
        exit;
    }
}

// If we reach here, no route matched
send_json_error(404, 'Endpoint not found', ['path' => $path, 'method' => $requestMethod]);
?>

