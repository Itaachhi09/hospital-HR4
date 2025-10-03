<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin','HR Manager']);

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id<=0) { echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }

    require_once __DIR__ . '/../../api/utils/Request.php';
    require_once __DIR__ . '/../../api/utils/Response.php';
    require_once __DIR__ . '/../../api/routes/benefits.php';

    $controller = new BenefitsController();
    ob_start();
    $controller->handleRequest('DELETE', $id, null);
    $out = ob_get_clean();
    header('Content-Type: application/json');
    echo $out;
} catch (Throwable $e) { error_log('delete_employee_benefit error: '.$e->getMessage()); echo json_encode(['success'=>false,'error'=>'Server error']); }
