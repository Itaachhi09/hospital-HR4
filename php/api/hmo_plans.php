<?php
require_once __DIR__ . '/_api_bootstrap.php';
// Plans: Admins manage; employees can view active plans
api_require_auth();

try {
    global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Use actual schema column names and alias Status/fields expected by frontend
            $stmt = $pdo->prepare("SELECT hp.PlanID, hp.ProviderID, hp.PlanName, hp.CoverageType AS Coverage, hp.AccreditedHospitals, hp.EligibilityRequirements AS Eligibility, hp.MaximumBenefitLimit, hp.MonthlyPremium AS PremiumCost, CASE WHEN COALESCE(hp.IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status, hpr.ProviderName FROM hmoplans hp JOIN hmoproviders hpr ON hp.ProviderID=hpr.ProviderID WHERE hp.PlanID=:id");
            $stmt->execute([':id' => (int)$_GET['id']]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($plan) {
                // Normalize Coverage to array
                $cov = $plan['Coverage'] ?? null;
                if ($cov === null || $cov === '') {
                    $plan['Coverage'] = [];
                } else {
                    $decoded = json_decode($cov, true);
                    if (is_array($decoded)) {
                        $plan['Coverage'] = $decoded;
                    } else {
                        // maybe CSV string
                        $arr = array_filter(array_map('trim', explode(',', $cov)));
                        $plan['Coverage'] = array_values($arr);
                    }
                }
            }
            // normalize accredited hospitals
            $ah = $plan['AccreditedHospitals'] ?? null;
            if ($ah === null || $ah === '') { $plan['AccreditedHospitals'] = []; } else { $dec = json_decode($ah, true); if (is_array($dec)) $plan['AccreditedHospitals'] = $dec; else { $plan['AccreditedHospitals'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $ah)))); } }
            $plan['Eligibility'] = $plan['Eligibility'] ?? 'Individual';
            echo json_encode(['success'=>true, 'plan'=>$plan]); exit;
        }
        $role = $_SESSION['role_name'] ?? '';
            if (in_array($role, ['System Admin','HR Admin'], true)) {
            $stmt = $pdo->query("SELECT hp.PlanID, hp.ProviderID, hp.PlanName, hp.CoverageType AS Coverage, hp.AccreditedHospitals, hp.EligibilityRequirements AS Eligibility, hp.MaximumBenefitLimit, hp.MonthlyPremium AS PremiumCost, CASE WHEN COALESCE(hp.IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status, hpr.ProviderName FROM hmoplans hp JOIN hmoproviders hpr ON hp.ProviderID=hpr.ProviderID ORDER BY hpr.ProviderName, hp.PlanName");
        } else {
            $stmt = $pdo->query("SELECT hp.PlanID, hp.ProviderID, hp.PlanName, hp.CoverageType AS Coverage, hp.AccreditedHospitals, hp.EligibilityRequirements AS Eligibility, hp.MaximumBenefitLimit, hp.MonthlyPremium AS PremiumCost, CASE WHEN COALESCE(hp.IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status, hpr.ProviderName FROM hmoplans hp JOIN hmoproviders hpr ON hp.ProviderID=hpr.ProviderID WHERE COALESCE(hp.IsActive,1)=1 ORDER BY hpr.ProviderName, hp.PlanName");
        }
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Normalize Coverage for each plan
        foreach ($plans as &$plan) {
            $cov = $plan['Coverage'] ?? null;
            if ($cov === null || $cov === '') { $plan['Coverage'] = []; }
            else { $decoded = json_decode($cov, true); if (is_array($decoded)) $plan['Coverage'] = $decoded; else { $arr = array_filter(array_map('trim', explode(',', $cov))); $plan['Coverage'] = array_values($arr); } }
            $ah = $plan['AccreditedHospitals'] ?? null;
            if ($ah === null || $ah === '') { $plan['AccreditedHospitals'] = []; } else { $dec = json_decode($ah, true); if (is_array($dec)) $plan['AccreditedHospitals'] = $dec; else { $plan['AccreditedHospitals'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $ah)))); } }
            $plan['Eligibility'] = $plan['Eligibility'] ?? 'Individual';
        }
        echo json_encode(['success'=>true, 'plans'=>$plans]); exit;
    }

    if ($method === 'POST') {
        // Admin only
        api_require_auth(['System Admin','HR Admin']);
        $d = api_read_json();
    // Map API fields to actual columns: EligibilityRequirements, MonthlyPremium, IsActive
    $sql = "INSERT INTO hmoplans (ProviderID, PlanName, Coverage, AccreditedHospitals, EligibilityRequirements, MaximumBenefitLimit, MonthlyPremium, IsActive) VALUES (:ProviderID,:PlanName,:Coverage,:AccreditedHospitals,:Eligibility,:MaximumBenefitLimit,:PremiumCost,:IsActive)";
        $stmt = $pdo->prepare($sql);
        $coverage = null;
        if (!empty($d['coverage'])) {
            // Accept array or CSV/string; normalize to array then json_encode
            if (is_array($d['coverage'])) {
                $covArr = array_values(array_filter(array_map('trim', $d['coverage'])));
            } else {
                // string: could be CSV or JSON
                $maybe = trim($d['coverage']);
                if ($maybe === '') {
                    $covArr = [];
                } elseif (($json = json_decode($maybe, true)) && is_array($json)) {
                    $covArr = array_values(array_filter(array_map('trim', $json)));
                } else {
                    $covArr = array_values(array_filter(array_map('trim', explode(',', $maybe))));
                }
            }
            $coverage = json_encode($covArr);
        }
        // Accredited hospitals normalization
        $acch = null;
        if (!empty($d['accredited_hospitals'])) {
            if (is_array($d['accredited_hospitals'])) {
                $ah = array_values(array_filter(array_map('trim', $d['accredited_hospitals'])));
            } else {
                $maybe = trim($d['accredited_hospitals']);
                if ($maybe === '') $ah = [];
                elseif (($json = json_decode($maybe, true)) && is_array($json)) $ah = array_values(array_filter(array_map('trim', $json)));
                else { $lines = preg_split('/[\r\n]+/', $maybe); if (count($lines) <= 1) $lines = explode(',', $maybe); $ah = array_values(array_filter(array_map('trim', $lines))); }
            }
            $acch = json_encode($ah);
        }
        $stmt->execute([
            ':ProviderID' => (int)($d['provider_id'] ?? 0),
            ':PlanName' => trim($d['plan_name'] ?? ''),
            ':Coverage' => $coverage,
            ':AccreditedHospitals' => $acch,
            ':Eligibility' => trim($d['eligibility'] ?? 'Individual'),
            ':MaximumBenefitLimit' => $d['maximum_benefit_limit'] ?? null,
            ':PremiumCost' => $d['premium_cost'] ?? $d['monthly_premium'] ?? null,
            ':IsActive' => (isset($d['status']) && strtolower($d['status'])==='inactive') ? 0 : 1,
        ]);
        echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        // Admin only
        api_require_auth(['System Admin','HR Admin']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $d = api_read_json();
    $sql = "UPDATE hmoplans SET ProviderID=:ProviderID, PlanName=:PlanName, Coverage=:Coverage, AccreditedHospitals=:AccreditedHospitals, EligibilityRequirements=:Eligibility, MaximumBenefitLimit=:MaximumBenefitLimit, MonthlyPremium=:PremiumCost, IsActive=:IsActive WHERE PlanID=:id";
        $stmt = $pdo->prepare($sql);
        $coverage = null;
        if (!empty($d['coverage'])) {
            if (is_array($d['coverage'])) {
                $covArr = array_values(array_filter(array_map('trim', $d['coverage'])));
            } else {
                $maybe = trim($d['coverage']);
                if ($maybe === '') {
                    $covArr = [];
                } elseif (($json = json_decode($maybe, true)) && is_array($json)) {
                    $covArr = array_values(array_filter(array_map('trim', $json)));
                } else {
                    $covArr = array_values(array_filter(array_map('trim', explode(',', $maybe))));
                }
            }
            $coverage = json_encode($covArr);
        }
        $acch = null;
        if (!empty($d['accredited_hospitals'])) {
            if (is_array($d['accredited_hospitals'])) {
                $ah = array_values(array_filter(array_map('trim', $d['accredited_hospitals'])));
            } else {
                $maybe = trim($d['accredited_hospitals']);
                if ($maybe === '') $ah = [];
                elseif (($json = json_decode($maybe, true)) && is_array($json)) $ah = array_values(array_filter(array_map('trim', $json)));
                else { $lines = preg_split('/[\r\n]+/', $maybe); if (count($lines) <= 1) $lines = explode(',', $maybe); $ah = array_values(array_filter(array_map('trim', $lines))); }
            }
            $acch = json_encode($ah);
        }
        $stmt->execute([
            ':ProviderID' => (int)($d['provider_id'] ?? 0),
            ':PlanName' => trim($d['plan_name'] ?? ''),
            ':Coverage' => $coverage,
            ':AccreditedHospitals' => $acch,
            ':Eligibility' => trim($d['eligibility'] ?? 'Individual'),
            ':MaximumBenefitLimit' => $d['maximum_benefit_limit'] ?? null,
            ':PremiumCost' => $d['premium_cost'] ?? $d['monthly_premium'] ?? null,
            ':IsActive' => (isset($d['status']) && strtolower($d['status'])==='inactive') ? 0 : 1,
            ':id' => $id,
        ]);
        echo json_encode(['success'=>true]); exit;
    }

    if ($method === 'DELETE') {
        // Admin only - soft delete via Status
        api_require_auth(['System Admin','HR Admin']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
    $stmt = $pdo->prepare("UPDATE hmoplans SET IsActive=0 WHERE PlanID=:id");
        $stmt->execute([':id'=>$id]);
        echo json_encode(['success'=>true]); exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) {
    error_log('API hmo_plans error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']);
}
?>


