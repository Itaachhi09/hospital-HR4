<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin','HR Manager']);

try {
    $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
    if ($employeeId<=0) { echo json_encode(['success'=>false,'error'=>'Missing employee_id']); exit; }

    // Call internal REST API route
    $url = __DIR__ . '/../../api/index.php';
    // Instead of HTTP call, include routes directly to reuse models
    require_once __DIR__ . '/../../api/utils/Request.php';
    require_once __DIR__ . '/../../api/utils/Response.php';
    require_once __DIR__ . '/../../api/routes/benefits.php';

    // Build controller and call method
    $controller = new BenefitsController();
    // Internally call the method
    ob_start();
    $controller->handleRequest('GET', $employeeId, 'employees');
    $out = ob_get_clean();
    header('Content-Type: application/json');
    echo $out;
} catch (Throwable $e) { error_log('get_employee_benefits error: '.$e->getMessage()); echo json_encode(['success'=>false,'error'=>'Server error']); }



