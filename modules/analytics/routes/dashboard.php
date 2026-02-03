<?php
/**
 * Dashboard Routes
 * Handles dashboard data and analytics
 */

require_once __DIR__ . '/../../../common/utils/Response.php';
require_once __DIR__ . '/../../../common/utils/Request.php';
require_once __DIR__ . '/../../../common/middlewares/AuthMiddleware.php';

class DashboardController {
    private $pdo;
    private $authMiddleware;

    public function __construct() {
        global $pdo;
        if (!$pdo || !($pdo instanceof PDO)) {
            error_log("DashboardController: Database connection not available");
            Response::error('Database connection not available', 500);
            return;
        }
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Handle dashboard requests
     */
    public function handleRequest($method, $id = null, $subResource = null) {
        switch ($method) {
            case 'GET':
                $this->getDashboardData();
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    /**
     * Get dashboard summary data
     * Directly implements dashboard logic to avoid conflicts with legacy file
     */
    private function getDashboardData() {
        // Check if database connection is available
        if (!$this->pdo || !($this->pdo instanceof PDO)) {
            Response::error('Database connection not available', 500);
            return;
        }

        // Check if session is available
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name'])) {
            Response::unauthorized('Authentication required');
            return;
        }

        try {
            $role = $_GET['role'] ?? $_SESSION['role_name'] ?? 'System Admin';
            $loggedInUserId = $_SESSION['user_id'];
            $loggedInEmployeeId = $_SESSION['employee_id'] ?? null;
            
            $summaryData = [
                'charts' => []
            ];

            if ($role === 'System Admin' || $role === 'HR Admin') {
                // Total Employees
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Employees");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $summaryData['total_employees'] = isset($result['count']) ? (int)$result['count'] : 0;
                } catch (PDOException $e) {
                    error_log("Dashboard: Error getting total employees: " . $e->getMessage());
                    $summaryData['total_employees'] = 0;
                }

                // Active Employees
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Employees WHERE IsActive = 1");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $summaryData['active_employees'] = isset($result['count']) ? (int)$result['count'] : 0;
                } catch (PDOException $e) {
                    error_log("Dashboard: Error getting active employees: " . $e->getMessage());
                    $summaryData['active_employees'] = 0;
                }
                
                $inactive_employees = max(0, $summaryData['total_employees'] - $summaryData['active_employees']);
                $summaryData['charts']['employee_status_distribution'] = [
                    'labels' => ['Active', 'Inactive'],
                    'data' => [$summaryData['active_employees'], $inactive_employees]
                ];

                // Pending Leave Requests
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM LeaveRequests WHERE Status = 'Pending'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $summaryData['pending_leave_requests'] = isset($result['count']) ? (int)$result['count'] : 0;
                } catch (PDOException $e) {
                    // Table might not exist - set to 0
                    $summaryData['pending_leave_requests'] = 0;
                }

                // Recent Hires (Last 30 days)
                try {
                    $stmt = $this->pdo->query("
                        SELECT COUNT(*) as count 
                        FROM Employees 
                        WHERE IsActive = 1 AND HireDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $summaryData['recent_hires_last_30_days'] = isset($result['count']) ? (int)$result['count'] : 0;
                } catch (PDOException $e) {
                    error_log("Dashboard: Error getting recent hires: " . $e->getMessage());
                    $summaryData['recent_hires_last_30_days'] = 0;
                }

            } else {
                // Employee role - minimal data
                $summaryData['message'] = "Dashboard data for role: " . htmlspecialchars($role);
                $summaryData['total_employees'] = 0;
                $summaryData['active_employees'] = 0;
                $summaryData['pending_leave_requests'] = 0;
                $summaryData['recent_hires_last_30_days'] = 0;
            }

            Response::success($summaryData, 'Dashboard data retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Dashboard Database Error: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            Response::error('Database error occurred', 500);
        } catch (Throwable $e) {
            error_log("Dashboard API Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::error('An error occurred while retrieving dashboard data', 500);
        }
    }

}


