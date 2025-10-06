<?php
/**
 * Get Claims API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for claims
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Claims and Reimbursement module is disabled for HR3 integration',
    'module' => 'claims_reimbursement',
    'endpoint' => 'GET /php/api/get_claims.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original claims implementation has been disabled
// It is preserved here for future HR3 integration reference

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php';
     if (!isset($pdo) || !$pdo instanceof PDO) {
         throw new Exception('$pdo object not created by db_connect.php');
    }
} catch (Throwable $e) {
    error_log("CRITICAL PHP Error: Failed to include or connect via db_connect.php in get_claims.php: " . $e->getMessage());
     if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
     }
    echo json_encode(['error' => 'Server configuration error: Cannot connect to database.']);
    exit;
}

// --- Optional Filters ---
$employee_id_filter = isset($_GET['employee_id']) ? filter_var($_GET['employee_id'], FILTER_VALIDATE_INT) : null;
$status_filter = isset($_GET['status']) ? trim(htmlspecialchars($_GET['status'])) : null;

// --- Debugging: Log received parameters ---
error_log("[get_claims.php] Received Params - employee_id: " . ($employee_id_filter ?? 'NULL') . ", status: " . ($status_filter ?? 'NULL'));

try {
    // Base SQL query
    $sql = "SELECT
                c.ClaimID,
                c.EmployeeID,
                CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                c.ClaimType,
                c.Amount,
                c.SubmissionDate,
                c.Status,
                c.Description,
                c.ApprovedBy,
                c.ApprovedDate,
                c.Comments
            FROM Claims c
            JOIN Employees e ON c.EmployeeID = e.EmployeeID
            WHERE 1=1";

    $params = [];

    if ($employee_id_filter !== null) {
        $sql .= " AND c.EmployeeID = :employee_id";
        $params[':employee_id'] = $employee_id_filter;
    }

    if ($status_filter !== null) {
        $sql .= " AND c.Status = :status";
        $params[':status'] = $status_filter;
    }

    $sql .= " ORDER BY c.SubmissionDate DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $claims,
        'count' => count($claims)
    ]);

} catch (Exception $e) {
    error_log("Error fetching claims: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch claims']);
}
*/
?>