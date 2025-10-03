<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin']);

try {
    global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT hp.*, hpr.ProviderName FROM HMOPlans hp JOIN HMOProviders hpr ON hp.ProviderID=hpr.ProviderID WHERE PlanID=:id");
            $stmt->execute([':id' => (int)$_GET['id']]);
            echo json_encode(['success'=>true, 'plan'=>$stmt->fetch(PDO::FETCH_ASSOC)]); exit;
        }
        $stmt = $pdo->query("SELECT hp.*, hpr.ProviderName FROM HMOPlans hp JOIN HMOProviders hpr ON hp.ProviderID=hpr.ProviderID WHERE COALESCE(hp.IsActive,0)=1 ORDER BY hpr.ProviderName, hp.PlanName");
        echo json_encode(['success'=>true, 'plans'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
    }

    if ($method === 'POST') {
        $d = api_read_json();
        $sql = "INSERT INTO HMOPlans (ProviderID, PlanName, PlanCode, Description, MonthlyPremium, AnnualLimit, MaximumBenefitLimit, EligibilityRequirements, IsActive, EffectiveDate, EndDate)
                VALUES (:ProviderID,:PlanName,:PlanCode,:Description,:MonthlyPremium,:AnnualLimit,:MaximumBenefitLimit,:EligibilityRequirements,:IsActive,:EffectiveDate,:EndDate)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderID' => (int)($d['provider_id'] ?? 0),
            ':PlanName' => trim($d['plan_name'] ?? ''),
            ':PlanCode' => $d['plan_code'] ?? null,
            ':Description' => $d['description'] ?? null,
            ':MonthlyPremium' => (float)($d['monthly_premium'] ?? 0),
            ':AnnualLimit' => $d['annual_limit'] ?? null,
            ':MaximumBenefitLimit' => $d['maximum_benefit_limit'] ?? null,
            ':EligibilityRequirements' => $d['eligibility'] ?? null,
            ':IsActive' => isset($d['is_active']) ? (int)$d['is_active'] : 1,
            ':EffectiveDate' => $d['effective_date'] ?? null,
            ':EndDate' => $d['end_date'] ?? null,
        ]);
        echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $d = api_read_json();
        $sql = "UPDATE HMOPlans SET ProviderID=:ProviderID, PlanName=:PlanName, PlanCode=:PlanCode, Description=:Description, MonthlyPremium=:MonthlyPremium, AnnualLimit=:AnnualLimit, MaximumBenefitLimit=:MaximumBenefitLimit, EligibilityRequirements=:EligibilityRequirements, IsActive=:IsActive, EffectiveDate=:EffectiveDate, EndDate=:EndDate WHERE PlanID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderID' => (int)($d['provider_id'] ?? 0),
            ':PlanName' => trim($d['plan_name'] ?? ''),
            ':PlanCode' => $d['plan_code'] ?? null,
            ':Description' => $d['description'] ?? null,
            ':MonthlyPremium' => (float)($d['monthly_premium'] ?? 0),
            ':AnnualLimit' => $d['annual_limit'] ?? null,
            ':MaximumBenefitLimit' => $d['maximum_benefit_limit'] ?? null,
            ':EligibilityRequirements' => $d['eligibility'] ?? null,
            ':IsActive' => isset($d['is_active']) ? (int)$d['is_active'] : 1,
            ':EffectiveDate' => $d['effective_date'] ?? null,
            ':EndDate' => $d['end_date'] ?? null,
            ':id' => $id,
        ]);
        echo json_encode(['success'=>true]); exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $stmt = $pdo->prepare("UPDATE HMOPlans SET IsActive=0 WHERE PlanID=:id");
        $stmt->execute([':id'=>$id]);
        echo json_encode(['success'=>true]); exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) {
    error_log('API hmo_plans error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']);
}
?>


