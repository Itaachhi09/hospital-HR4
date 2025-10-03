<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin','Manager']);

try { global $pdo;
    $summary = [];
    $summary['providers'] = (int)$pdo->query("SELECT COUNT(*) FROM HMOProviders WHERE COALESCE(IsActive,0)=1")->fetchColumn();
    $summary['plans'] = (int)$pdo->query("SELECT COUNT(*) FROM HMOPlans WHERE COALESCE(IsActive,0)=1")->fetchColumn();
    $summary['active_enrollments'] = (int)$pdo->query("SELECT COUNT(*) FROM EmployeeHMOEnrollments WHERE Status='Active'")->fetchColumn();
    $summary['pending_claims'] = (int)$pdo->query("SELECT COUNT(*) FROM HMOClaims WHERE Status IN ('Submitted','Under Review')")->fetchColumn();
    $summary['approved_claims'] = (int)$pdo->query("SELECT COUNT(*) FROM HMOClaims WHERE Status='Approved'")->fetchColumn();
    $summary['monthly_premiums_total'] = (float)$pdo->query("SELECT COALESCE(SUM(MonthlyPremium),0) FROM HMOPlans WHERE COALESCE(IsActive,0)=1")->fetchColumn();
    echo json_encode(['success'=>true,'summary'=>$summary]);
} catch (Throwable $e) { error_log('API hmo_dashboard error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


