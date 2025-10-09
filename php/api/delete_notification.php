<?php
/**
 * API Endpoint: Delete Notification
 * Allows deletion of notifications (only own notifications or all if admin)
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
    error_log("Delete Notification Error (DB Connection): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error'
    ]);
    exit;
}

try {
    $loggedInUserId = $_SESSION['user_id'];
    $userRole = $_SESSION['role_name'] ?? '';
    $isAdmin = in_array($userRole, ['System Admin', 'HR Admin']);
    
    // Get notification ID from query parameter or use 'all' to delete all
    $notificationId = $_GET['id'] ?? null;
    
    if ($notificationId === 'all') {
        // Delete all read notifications for the current user
        $sql = "DELETE FROM Notifications WHERE UserID = :user_id AND IsRead = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $loggedInUserId]);
        $deletedCount = $stmt->rowCount();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Deleted {$deletedCount} read notification(s)",
            'deleted_count' => $deletedCount
        ]);
        exit;
    }
    
    if (!$notificationId || !is_numeric($notificationId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Valid notification ID is required'
        ]);
        exit;
    }
    
    // Fetch notification to check ownership
    $sql = "SELECT NotificationID, UserID FROM Notifications WHERE NotificationID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $notificationId]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$notification) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Notification not found'
        ]);
        exit;
    }
    
    // Authorization check
    if (!$isAdmin && $notification['UserID'] != $loggedInUserId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. You can only delete your own notifications.'
        ]);
        exit;
    }
    
    // Delete the notification
    $deleteSql = "DELETE FROM Notifications WHERE NotificationID = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([':id' => $notificationId]);
    
    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete notification'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Delete Notification Error (Database): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Delete Notification Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while deleting the notification'
    ]);
}
?>

