<?php
/**
 * API Endpoint: Get HMO Claims
 * Retrieves all HMO claims with employee and dependent information
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
    // Optional status filter mapping for frontend values
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $where = '';
    $params = [];
    if ($statusFilter !== '') {
        if (strtolower($statusFilter) === 'pending') {
            // Map "pending" to Submitted or Under Review in our schema
            $where = "WHERE hc.Status IN ('Submitted','Under Review')";
        } else {
            $where = "WHERE hc.Status = :status";
            $params[':status'] = $statusFilter;
        }
    }

    $sql = "SELECT 
                hc.ClaimID,
                hc.EnrollmentID,
                hc.EmployeeID,
                hc.ClaimNumber,
                DATE(hc.ClaimDate) AS ClaimDate,
                hc.ProviderName,
                hc.ClaimType,
                hc.Description,
                hc.Amount AS Amount,
                hc.Status,
                hc.SubmittedDate AS SubmittedAt,
                hc.ApprovedDate AS ApprovedAt,
                CONCAT(e.FirstName, ' ', e.LastName) AS employee_name,
                hp.PlanName,
                hpr.ProviderName AS hmo_provider_name
            FROM HMOClaims hc
            LEFT JOIN EmployeeHMOEnrollments ehe ON hc.EnrollmentID = ehe.EnrollmentID
            LEFT JOIN Employees e ON hc.EmployeeID = e.EmployeeID
            LEFT JOIN HMOPlans hp ON ehe.PlanID = hp.PlanID
            LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID
            $where
            ORDER BY hc.SubmittedDate DESC";

    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($sql);
    }

    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'claims' => $claims
    ]);

} catch (PDOException $e) {
    error_log("Get HMO Claims Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred while fetching HMO claims.']);
} catch (Exception $e) {
    error_log("Get HMO Claims Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred.']);
}
exit;
?>
