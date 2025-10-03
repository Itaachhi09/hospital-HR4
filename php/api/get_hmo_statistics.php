<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db_connect.php';

try {
    // Get total enrolled employees
    $sql = "SELECT COUNT(DISTINCT eh.EmployeeID) as total_enrolled
            FROM EmployeeHMOEnrollments eh
            WHERE eh.Status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalEnrolled = $stmt->fetch(PDO::FETCH_ASSOC)['total_enrolled'];

    // Get total active plans
    $sql = "SELECT COUNT(*) as total_plans
            FROM HMOPlans hp
            WHERE hp.IsActive = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalPlans = $stmt->fetch(PDO::FETCH_ASSOC)['total_plans'];

    // Get total active providers
    $sql = "SELECT COUNT(*) as total_providers
            FROM HMOProviders hpr
            WHERE hpr.IsActive = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalProviders = $stmt->fetch(PDO::FETCH_ASSOC)['total_providers'];

    // Get enrollments by provider
    $sql = "SELECT 
                hpr.ProviderName as provider_name,
                COUNT(eh.EnrollmentID) as enrollment_count,
                SUM(eh.MonthlyDeduction) as total_monthly_premium
            FROM EmployeeHMOEnrollments eh
            LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            WHERE eh.Status = 'Active'
            GROUP BY hpr.ProviderID, hpr.ProviderName
            ORDER BY enrollment_count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $enrollmentsByProvider = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get enrollments by plan category
    $sql = "SELECT 
                hp.PlanCategory as plan_category,
                COUNT(eh.EnrollmentID) as enrollment_count
            FROM EmployeeHMOEnrollments eh
            LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID
            WHERE eh.Status = 'Active'
            GROUP BY hp.PlanCategory
            ORDER BY enrollment_count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $enrollmentsByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent enrollments (last 30 days)
    $sql = "SELECT 
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                hp.PlanName as plan_name,
                hpr.ProviderName as provider_name,
                eh.EnrollmentDate as enrollment_date,
                eh.EffectiveDate as effective_date,
                eh.MonthlyDeduction as monthly_deduction
            FROM EmployeeHMOEnrollments eh
            LEFT JOIN employees e ON eh.EmployeeID = e.EmployeeID
            LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            WHERE eh.EnrollmentDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY eh.EnrollmentDate DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recentEnrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total monthly premiums
    $sql = "SELECT SUM(eh.MonthlyDeduction) as total_monthly_premiums
            FROM EmployeeHMOEnrollments eh
            WHERE eh.Status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $totalMonthlyPremiums = $stmt->fetch(PDO::FETCH_ASSOC)['total_monthly_premiums'];

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_enrolled' => (int)$totalEnrolled,
                'total_plans' => (int)$totalPlans,
                'total_providers' => (int)$totalProviders,
                'total_monthly_premiums' => (float)$totalMonthlyPremiums
            ],
            'enrollments_by_provider' => $enrollmentsByProvider,
            'enrollments_by_category' => $enrollmentsByCategory,
            'recent_enrollments' => $recentEnrollments
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_hmo_statistics.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General Error in get_hmo_statistics.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>
