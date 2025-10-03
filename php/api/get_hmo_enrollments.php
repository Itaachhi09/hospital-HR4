<?php
/**
 * API Endpoint: Get HMO Enrollments
 * Retrieves all HMO enrollments with employee and plan details
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

try {
    $sql = "SELECT
                eh.EnrollmentID as id,
                eh.EmployeeID as employee_id,
                eh.PlanID as plan_id,
                eh.EnrollmentDate as enrollment_date,
                eh.EffectiveDate as effective_date,
                eh.EndDate as end_date,
                eh.Status as status,
                eh.MonthlyDeduction as monthly_deduction,
                eh.CreatedAt as created_at,
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                e.Email as employee_email,
                hp.PlanName as plan_name,
                hp.CoverageType as coverage_type,
                hp.MonthlyPremium as plan_premium,
                hpr.ProviderName as provider_name
            FROM EmployeeHMOEnrollments eh
            LEFT JOIN employees e ON eh.EmployeeID = e.EmployeeID
            LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            ORDER BY eh.CreatedAt DESC";

    $stmt = $pdo->query($sql);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'enrollments' => $enrollments
    ]);

} catch (Exception $e) {
    // Log and return a consistent JSON error response
    error_log("Get HMO Enrollments Error: " . $e->getMessage());
    http_response_code(500);
    // If debug=1 is present, include exception message in response for easier local debugging
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo json_encode(['success' => false, 'error' => 'An error occurred while fetching HMO enrollments.', 'exception' => $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'An error occurred while fetching HMO enrollments.']);
    }
}
exit;
?>
