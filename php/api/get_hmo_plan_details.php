<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db_connect.php';

// Check if plan ID is provided
if (!isset($_GET['plan_id'])) {
    echo json_encode(['error' => 'Plan ID is required']);
    exit;
}

$planId = $_GET['plan_id'];

try {
    // Get plan details with provider information
    $sql = "SELECT 
                hp.PlanID as id,
                hp.ProviderID as provider_id,
                hp.PlanName as name,
                hp.PlanCode as plan_code,
                hp.Description as description,
                hp.CoverageType as coverage_type,
                hp.PlanCategory as plan_category,
                hp.MonthlyPremium as premium_amount,
                hp.MaximumBenefitLimit as maximum_benefit_limit,
                hp.AnnualLimit as annual_limit,
                hp.RoomAndBoardLimit as room_board_limit,
                hp.OutpatientLimit as outpatient_limit,
                hp.InpatientLimit as inpatient_limit,
                hp.EmergencyLimit as emergency_limit,
                hp.MaternityLimit as maternity_limit,
                hp.DentalLimit as dental_limit,
                hp.PreventiveCareLimit as preventive_care_limit,
                hp.CoverageInpatient as coverage_inpatient,
                hp.CoverageOutpatient as coverage_outpatient,
                hp.CoverageEmergency as coverage_emergency,
                hp.CoveragePreventive as coverage_preventive,
                hp.CoverageMaternity as coverage_maternity,
                hp.CoverageDental as coverage_dental,
                hp.CoverageOptical as coverage_optical,
                hp.AccreditedHospitals as accredited_hospitals,
                hp.ExclusionsLimitations as exclusions_limitations,
                hp.EligibilityRequirements as eligibility_requirements,
                hp.WaitingPeriod as waiting_period,
                hp.CashlessLimit as cashless_limit,
                hp.IsActive as is_active,
                hp.EffectiveDate as effective_date,
                hp.EndDate as end_date,
                hp.CreatedAt as created_at,
                hp.UpdatedAt as updated_at,
                hpr.ProviderName as provider_name,
                hpr.CompanyName as provider_company_name,
                hpr.ContactPerson as provider_contact_person,
                hpr.ContactEmail as provider_contact_email,
                hpr.ContactPhone as provider_contact_phone,
                hpr.Address as provider_address,
                hpr.Website as provider_website,
                hpr.Logo as provider_logo,
                hpr.Description as provider_description,
                hpr.ServiceAreas as provider_service_areas
            FROM HMOPlans hp
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            WHERE hp.PlanID = :plan_id AND hp.IsActive = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
    $stmt->execute();
    
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(['error' => 'Plan not found']);
        exit;
    }
    
    // Parse accredited hospitals JSON
    if ($plan['accredited_hospitals']) {
        $plan['accredited_hospitals'] = json_decode($plan['accredited_hospitals'], true);
    } else {
        $plan['accredited_hospitals'] = [];
    }
    
    // Format coverage benefits
    $coverage_benefits = [
        'inpatient' => (bool)$plan['coverage_inpatient'],
        'outpatient' => (bool)$plan['coverage_outpatient'],
        'emergency' => (bool)$plan['coverage_emergency'],
        'preventive' => (bool)$plan['coverage_preventive'],
        'maternity' => (bool)$plan['coverage_maternity'],
        'dental' => (bool)$plan['coverage_dental'],
        'optical' => (bool)$plan['coverage_optical']
    ];
    
    $plan['coverage_benefits'] = $coverage_benefits;
    
    // Format limits
    $limits = [
        'annual_limit' => (float)$plan['annual_limit'],
        'maximum_benefit_limit' => (float)$plan['maximum_benefit_limit'],
        'room_board_limit' => (float)$plan['room_board_limit'],
        'outpatient_limit' => (float)$plan['outpatient_limit'],
        'inpatient_limit' => (float)$plan['inpatient_limit'],
        'emergency_limit' => (float)$plan['emergency_limit'],
        'maternity_limit' => (float)$plan['maternity_limit'],
        'dental_limit' => (float)$plan['dental_limit'],
        'preventive_care_limit' => (float)$plan['preventive_care_limit'],
        'cashless_limit' => (float)$plan['cashless_limit']
    ];
    
    $plan['limits'] = $limits;
    
    echo json_encode([
        'success' => true,
        'data' => $plan
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_hmo_plan_details.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General Error in get_hmo_plan_details.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>
