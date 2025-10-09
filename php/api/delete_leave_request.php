<?php
/**
 * API Endpoint: Delete Leave Request
 * Allows deletion of leave requests (with proper authorization)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'DELETE method required'
    ]);
    exit;
}

// Database connection
try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('Database connection not available');
    }
} catch (Exception $e) {
    error_log("Delete Leave Request Error (DB Connection): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error'
    ]);
    exit;
}

try {
    // Get leave request ID from query parameter
    $leaveRequestId = $_GET['id'] ?? null;
    
    if (!$leaveRequestId || !is_numeric($leaveRequestId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Valid leave request ID is required'
        ]);
        exit;
    }
    
    // Fetch leave request details to check ownership and status
    $sql = "SELECT lr.*, e.EmployeeID 
            FROM LeaveRequests lr
            JOIN Employees e ON lr.EmployeeID = e.EmployeeID
            WHERE lr.LeaveRequestID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $leaveRequestId]);
    $leaveRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leaveRequest) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Leave request not found'
        ]);
        exit;
    }
    
    // Authorization check
    $userEmployeeId = $_SESSION['employee_id'] ?? null;
    $userRole = $_SESSION['role_name'] ?? '';
    
    $canDelete = false;
    
    // Admin can delete any request
    if ($userRole === 'System Admin' || $userRole === 'HR Admin') {
        $canDelete = true;
    }
    // Employee can only delete their own pending requests
    elseif ($leaveRequest['EmployeeID'] == $userEmployeeId) {
        if ($leaveRequest['Status'] === 'Pending') {
            $canDelete = true;
        } else {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Cannot delete leave request. Only pending requests can be deleted.'
            ]);
            exit;
        }
    } else {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. You can only delete your own leave requests.'
        ]);
        exit;
    }
    
    if (!$canDelete) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied'
        ]);
        exit;
    }
    
    // If leave was approved, restore the leave balance
    if ($leaveRequest['Status'] === 'Approved') {
        $updateBalanceSql = "UPDATE LeaveBalances 
                             SET Balance = Balance + :days_used
                             WHERE EmployeeID = :employee_id 
                             AND LeaveTypeID = :leave_type_id";
        
        $updateStmt = $pdo->prepare($updateBalanceSql);
        $updateStmt->execute([
            ':days_used' => $leaveRequest['DaysRequested'] ?? $leaveRequest['NumberOfDays'] ?? 0,
            ':employee_id' => $leaveRequest['EmployeeID'],
            ':leave_type_id' => $leaveRequest['LeaveTypeID']
        ]);
        
        error_log("Restored {$leaveRequest['DaysRequested']} days to employee {$leaveRequest['EmployeeID']} leave balance");
    }
    
    // Delete the leave request
    $deleteSql = "DELETE FROM LeaveRequests WHERE LeaveRequestID = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([':id' => $leaveRequestId]);
    
    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Leave request deleted successfully',
            'balance_restored' => ($leaveRequest['Status'] === 'Approved')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete leave request'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Delete Leave Request Error (Database): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Delete Leave Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while deleting the leave request'
    ]);
}
?>

