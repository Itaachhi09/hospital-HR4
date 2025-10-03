<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin','HR Manager']);

try {
    // Read JSON or form
    $data = $_POST;
    if (empty($data)) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
    }

    require_once __DIR__ . '/../../api/utils/Request.php';
    require_once __DIR__ . '/../../api/utils/Response.php';
    require_once __DIR__ . '/../../api/routes/benefits.php';

    $controller = new BenefitsController();
    // Simulate request
    // The controller expects POST to /benefits/assign -> subResource 'assign'
    ob_start();
    $controller->handleRequest('POST', null, 'assign');
    $out = ob_get_clean();
    header('Content-Type: application/json');
    echo $out;

} catch (Throwable $e) { error_log('assign_employee_benefit error: '.$e->getMessage()); echo json_encode(['success'=>false,'error'=>'Server error']); }
