<?php
/**
 * API Endpoint: Get HMO Plans
 * Retrieves all active HMO plans with provider information
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

try {
    // Ensure required tables exist; if missing, return empty plans gracefully
    $plansTable = $pdo->query("SHOW TABLES LIKE 'HMOPlans'");
    if ($plansTable->rowCount() === 0) {
        echo json_encode(['success' => true, 'plans' => []]);
        exit;
    }

    $sql = "SELECT 
                hp.PlanID AS id,
                hp.ProviderID AS provider_id,
                hp.PlanName AS name,
                hp.PlanCode AS plan_code,
                hp.Description AS description,
                hp.CoverageType AS coverage_type,
                hp.PlanCategory AS plan_category,
                hp.MonthlyPremium AS premium_amount,
                hp.MaximumBenefitLimit AS maximum_benefit_limit,
                hp.AnnualLimit AS annual_limit,
                hp.RoomAndBoardLimit AS room_board_limit,
                hp.OutpatientLimit AS outpatient_limit,
                hp.InpatientLimit AS inpatient_limit,
                hp.EmergencyLimit AS emergency_limit,
                hp.MaternityLimit AS maternity_limit,
                hp.DentalLimit AS dental_limit,
                hp.PreventiveCareLimit AS preventive_care_limit,
                hp.CoverageInpatient AS coverage_inpatient,
                hp.CoverageOutpatient AS coverage_outpatient,
                hp.CoverageEmergency AS coverage_emergency,
                hp.CoveragePreventive AS coverage_preventive,
                hp.CoverageMaternity AS coverage_maternity,
                hp.CoverageDental AS coverage_dental,
                hp.CoverageOptical AS coverage_optical,
                hp.AccreditedHospitals AS accredited_hospitals,
                hp.ExclusionsLimitations AS exclusions_limitations,
                hp.EligibilityRequirements AS eligibility_requirements,
                hp.WaitingPeriod AS waiting_period,
                hp.CashlessLimit AS cashless_limit,
                hp.IsActive AS is_active,
                hp.EffectiveDate AS effective_date,
                hp.EndDate AS end_date,
                hp.CreatedAt AS created_at,
                hp.UpdatedAt AS updated_at,
                hpr.ProviderName AS provider_name,
                hpr.CompanyName AS provider_company_name
            FROM HMOPlans hp
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            WHERE COALESCE(hp.IsActive, 0) = 1
            ORDER BY hpr.ProviderName ASC, hp.PlanName ASC";

    $stmt = $pdo->query($sql);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'plans' => $plans
    ]);

} catch (PDOException $e) {
    error_log("Get HMO Plans Error: " . $e->getMessage());
    // Return empty list gracefully to avoid breaking UI
    echo json_encode(['success' => true, 'plans' => []]);
} catch (Exception $e) {
    error_log("Get HMO Plans Error: " . $e->getMessage());
    echo json_encode(['success' => true, 'plans' => []]);
}

exit;

?>
