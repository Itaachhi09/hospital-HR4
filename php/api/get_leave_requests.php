<?php
/**
 * Get Leave Requests API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for leave requests
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Leave Management module is disabled for HR3 integration',
    'module' => 'leave_management',
    'endpoint' => 'GET /php/api/get_leave_requests.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original leave requests implementation has been disabled
// It is preserved here for future HR3 integration reference

// --- Error Reporting & Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) { throw new Exception('DB connection failed'); }
} catch (Throwable $e) {
    error_log("PHP Error in get_leave_requests.php (db_connect include): " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Server configuration error.']); exit;
}

// --- Filters ---
$employee_id_filter = isset($_GET['employee_id']) ? filter_var($_GET['employee_id'], FILTER_VALIDATE_INT) : null;
$status_filter = isset($_GET['status']) ? trim(htmlspecialchars($_GET['status'])) : null;
// --- End Filters ---

// --- Fetch Logic ---
$sql = '';
$params = [];
try {
    $sql = "SELECT
                lr.RequestID,
                lr.EmployeeID,
                CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                lr.LeaveTypeID,
                lt.LeaveTypeName,
                lr.StartDate,
                lr.EndDate,
                lr.DaysRequested,
                lr.Reason,
                lr.Status,
                lr.ApprovedBy,
                lr.ApprovedDate,
                lr.Comments,
                lr.CreatedDate,
                lr.UpdatedDate
            FROM LeaveRequests lr
            JOIN Employees e ON lr.EmployeeID = e.EmployeeID
            JOIN LeaveTypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
            WHERE 1=1";

    if ($employee_id_filter !== null) {
        $sql .= " AND lr.EmployeeID = :employee_id";
        $params[':employee_id'] = $employee_id_filter;
    }

    if ($status_filter !== null) {
        $sql .= " AND lr.Status = :status";
        $params[':status'] = $status_filter;
    }

    $sql .= " ORDER BY lr.CreatedDate DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $leaveRequests,
        'count' => count($leaveRequests)
    ]);

} catch (Exception $e) {
    error_log("Error fetching leave requests: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch leave requests']);
}
*/
?>