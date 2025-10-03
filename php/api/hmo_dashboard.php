<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin','Manager']);

try { global $pdo;
    $mode = $_GET['mode'] ?? '';
    if ($mode === 'monthly_claims') {
        // return last 12 months claim counts grouped by month
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(ClaimDate, '%Y-%m') as ym, COUNT(*) as cnt FROM hmoclaims WHERE ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym");
        $stmt->execute(); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'monthly_claims'=>$rows]); exit;
    }
    if ($mode === 'top_hospitals') {
    $stmt = $pdo->prepare("SELECT HospitalClinic as hospital, COUNT(*) as cnt FROM hmoclaims GROUP BY HospitalClinic ORDER BY cnt DESC LIMIT 10"); $stmt->execute(); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'top_hospitals'=>$rows]); exit;
    }
    if ($mode === 'plan_utilization') {
    $stmt = $pdo->prepare("SELECT hp.PlanName, COUNT(ehe.EnrollmentID) as enrolled, COALESCE(SUM(CASE WHEN hc.ClaimID IS NOT NULL THEN 1 ELSE 0 END),0) as claims FROM hmoplans hp LEFT JOIN employeehmoenrollments ehe ON hp.PlanID=ehe.PlanID LEFT JOIN hmoclaims hc ON ehe.EnrollmentID=hc.EnrollmentID GROUP BY hp.PlanID ORDER BY enrolled DESC");
        $stmt->execute(); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'plan_utilization'=>$rows]); exit;
    }

    $summary = [];
    // total active providers/plans using Status='Active'
    $summary['total_active_providers'] = (int)$pdo->query("SELECT COUNT(*) FROM hmoproviders WHERE COALESCE(IsActive,1)=1")->fetchColumn();
    $summary['total_active_plans'] = (int)$pdo->query("SELECT COUNT(*) FROM hmoplans WHERE COALESCE(IsActive,1)=1")->fetchColumn();
    $summary['total_enrolled_employees'] = (int)$pdo->query("SELECT COUNT(*) FROM employeehmoenrollments WHERE Status='Active'")->fetchColumn();
    // claims breakdown
    $summary['claims'] = [
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM hmoclaims WHERE Status IN ('Pending','Submitted','Under Review')")->fetchColumn(),
        'approved' => (int)$pdo->query("SELECT COUNT(*) FROM hmoclaims WHERE Status='Approved'")->fetchColumn(),
        'denied' => (int)$pdo->query("SELECT COUNT(*) FROM hmoclaims WHERE Status='Denied'")->fetchColumn(),
    ];
    $summary['monthly_premiums_total'] = (float)$pdo->query("SELECT COALESCE(SUM(MonthlyPremium),0) FROM hmoplans WHERE COALESCE(IsActive,1)=1")->fetchColumn();
    // keep legacy keys for compatibility
    $summary['providers'] = $summary['total_active_providers'];
    $summary['plans'] = $summary['total_active_plans'];
    $summary['active_enrollments'] = $summary['total_enrolled_employees'];
    $summary['pending_claims'] = $summary['claims']['pending'];
    $summary['approved_claims'] = $summary['claims']['approved'];
    $summary['denied_claims'] = $summary['claims']['denied'];
    echo json_encode(['success'=>true,'summary'=>$summary]);
} catch (Throwable $e) { error_log('API hmo_dashboard error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


