<?php
/**
 * HR Reports Routes
 * Comprehensive reporting endpoints for all HR modules
 * Provides interactive dashboards and downloadable reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../integrations/HRReportsIntegration.php';
require_once __DIR__ . '/../integrations/HRReportsExportHandler.php';
require_once __DIR__ . '/../integrations/HRReportsScheduler.php';
require_once __DIR__ . '/../integrations/HRReportsAccessControl.php';
require_once __DIR__ . '/../utils/Response.php';

class HRReportsController {
    private $pdo;
    private $authMiddleware;
    private $reportsIntegration;
    private $exportHandler;
    private $scheduler;
    private $accessControl;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
        $this->reportsIntegration = new HRReportsIntegration();
        $this->exportHandler = new HRReportsExportHandler();
        $this->scheduler = new HRReportsScheduler();
        $this->accessControl = new HRReportsAccessControl();
    }

    /**
     * Handle HR Reports requests
     */
    public function handleRequest($method, $id = null, $subResource = null) {
        // TODO: Re-enable authentication after fixing session sharing
        // For now, temporarily disable authentication for testing
        /*
        if (!$this->authMiddleware->authenticate()) {
            Response::unauthorized('Authentication required');
            return;
        }

        $currentUser = $this->authMiddleware->getCurrentUser();
        $allowedRoles = ['System Admin', 'HR Manager', 'HR Staff', 'Finance Manager', 'Executive'];
        if (!in_array($currentUser['role_name'] ?? '', $allowedRoles)) {
            Response::forbidden('Insufficient permissions for reports access');
            return;
        }
        */

        switch ($method) {
            case 'GET':
                $this->handleGet($id, $subResource);
                break;
            case 'POST':
                $this->handlePost($id, $subResource);
                break;
            case 'OPTIONS':
                Response::success('OK', ['methods' => ['GET', 'POST', 'OPTIONS']]);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function handleGet($id, $subResource) {
        $filters = $_GET;
        
        // Remove pagination and other non-filter parameters
        unset($filters['page'], $filters['limit'], $filters['sort'], $filters['order']);

        try {
            switch ($id) {
                case 'dashboard':
                    $this->getReportsDashboard($filters);
                    break;
                case 'employee-demographics':
                    $this->getEmployeeDemographicsReport($filters);
                    break;
                case 'recruitment-application':
                    $this->getRecruitmentApplicationReport($filters);
                    break;
                case 'payroll-compensation':
                    $this->getPayrollCompensationReport($filters);
                    break;
                case 'attendance-leave':
                    $this->getAttendanceLeaveReport($filters);
                    break;
                case 'benefits-hmo-utilization':
                    $this->getBenefitsHMOUtilizationReport($filters);
                    break;
                case 'training-development':
                    $this->getTrainingDevelopmentReport($filters);
                    break;
                case 'employee-relations-engagement':
                    $this->getEmployeeRelationsEngagementReport($filters);
                    break;
                case 'turnover-retention':
                    $this->getTurnoverRetentionReport($filters);
                    break;
                case 'compliance-document':
                    $this->getComplianceDocumentReport($filters);
                    break;
                case 'executive-summary':
                    $this->getExecutiveSummaryReport($filters);
                    break;
                case 'scheduled':
                    $this->getScheduledReports($filters);
                    break;
                case 'audit-trail':
                    $this->getAuditTrail($filters);
                    break;
                default:
                    Response::notFound('Report not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to generate report: ' . $e->getMessage());
        }
    }

    private function handlePost($id, $subResource) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            switch ($id) {
                case 'export':
                    $this->exportReport($input);
                    break;
                case 'schedule':
                    $this->scheduleReport($input);
                    break;
                case 'generate':
                    $this->generateCustomReport($input);
                    break;
                case 'process-scheduled':
                    $this->processScheduledReports();
                    break;
                case 'test-email':
                    $this->testEmailDelivery($input);
                    break;
                case 'user-access':
                    $this->getUserAccessSummary();
                    break;
                default:
                    Response::notFound('Action not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to process request: ' . $e->getMessage());
        }
    }

    /**
     * Get comprehensive HR Reports dashboard
     */
    private function getReportsDashboard($filters) {
        $data = $this->reportsIntegration->getHRReportsDashboard($filters);
        
        Response::success($data, 'HR Reports dashboard data retrieved successfully');
    }

    /**
     * Get Employee Demographics Report
     */
    private function getEmployeeDemographicsReport($filters) {
        $userId = $_SESSION['user_id'] ?? 'system';
        
        try {
            $this->accessControl->validateReportAccess($userId, 'employee-demographics', $filters);
            
            $data = $this->reportsIntegration->getEmployeeDemographicsReport($filters);
            $filteredData = $this->accessControl->filterDataByAccess($userId, 'employee-demographics', $data);
            
            Response::success($filteredData, 'Employee demographics report generated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * Get Recruitment & Application Report
     */
    private function getRecruitmentApplicationReport($filters) {
        $data = $this->reportsIntegration->getRecruitmentApplicationReport($filters);
        
        Response::success($data, 'Recruitment application report generated successfully');
    }

    /**
     * Get Payroll & Compensation Report
     */
    private function getPayrollCompensationReport($filters) {
        $data = $this->reportsIntegration->getPayrollCompensationReport($filters);
        
        Response::success($data, 'Payroll compensation report generated successfully');
    }

    /**
     * Get Attendance & Leave Report
     */
    private function getAttendanceLeaveReport($filters) {
        $data = $this->reportsIntegration->getAttendanceLeaveReport($filters);
        
        Response::success($data, 'Attendance leave report generated successfully');
    }

    /**
     * Get Benefits & HMO Utilization Report
     */
    private function getBenefitsHMOUtilizationReport($filters) {
        $data = $this->reportsIntegration->getBenefitsHMOUtilizationReport($filters);
        
        Response::success($data, 'Benefits HMO utilization report generated successfully');
    }

    /**
     * Get Training & Development Report
     */
    private function getTrainingDevelopmentReport($filters) {
        $data = $this->reportsIntegration->getTrainingDevelopmentReport($filters);
        
        Response::success($data, 'Training development report generated successfully');
    }

    /**
     * Get Employee Relations & Engagement Report
     */
    private function getEmployeeRelationsEngagementReport($filters) {
        $data = $this->reportsIntegration->getEmployeeRelationsEngagementReport($filters);
        
        Response::success($data, 'Employee relations engagement report generated successfully');
    }

    /**
     * Get Turnover & Retention Report
     */
    private function getTurnoverRetentionReport($filters) {
        $data = $this->reportsIntegration->getTurnoverRetentionReport($filters);
        
        Response::success($data, 'Turnover retention report generated successfully');
    }

    /**
     * Get Compliance & Document Report
     */
    private function getComplianceDocumentReport($filters) {
        $data = $this->reportsIntegration->getComplianceDocumentReport($filters);
        
        Response::success($data, 'Compliance document report generated successfully');
    }

    /**
     * Get Executive Summary Report
     */
    private function getExecutiveSummaryReport($filters) {
        $data = $this->reportsIntegration->getExecutiveSummaryReport($filters);
        
        Response::success($data, 'Executive summary report generated successfully');
    }

    /**
     * Export report in specified format
     */
    private function exportReport($input) {
        $reportType = $input['report_type'] ?? '';
        $format = $input['format'] ?? 'CSV';
        $filters = $input['filters'] ?? [];
        
        if (empty($reportType)) {
            Response::error('Report type is required');
            return;
        }
        
        if (!in_array($format, ['CSV', 'Excel', 'PDF'])) {
            Response::error('Invalid export format');
            return;
        }
        
        try {
            $data = $this->exportHandler->exportReport($reportType, $format, $filters);
            
            // Log the export for audit trail
            $userId = $_SESSION['user_id'] ?? 'system';
            $this->reportsIntegration->logReportGeneration($reportType, $format, $filters, $userId);
            
            Response::success("Report exported successfully in $format format", $data);
        } catch (Exception $e) {
            Response::error('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Get scheduled reports
     */
    private function getScheduledReports($filters) {
        $scheduleType = $filters['schedule_type'] ?? 'weekly';
        
        $data = $this->reportsIntegration->generateScheduledReports($scheduleType);
        
        Response::success($data, 'Scheduled reports generated successfully');
    }

    /**
     * Schedule a report for automatic generation
     */
    private function scheduleReport($input) {
        $reportType = $input['report_type'] ?? '';
        $scheduleType = $input['schedule_type'] ?? 'weekly';
        $emailRecipients = $input['email_recipients'] ?? [];
        $filters = $input['filters'] ?? [];
        $userId = $_SESSION['user_id'] ?? 'system';
        
        if (empty($reportType)) {
            Response::error('Report type is required');
            return;
        }
        
        if (!in_array($scheduleType, ['daily', 'weekly', 'monthly', 'quarterly'])) {
            Response::error('Invalid schedule type');
            return;
        }
        
        if (empty($emailRecipients)) {
            Response::error('Email recipients are required');
            return;
        }
        
        try {
            $result = $this->scheduler->createScheduledReport(
                $reportType, 
                $scheduleType, 
                $emailRecipients, 
                $filters, 
                $userId
            );
            
            if ($result) {
                Response::success('Report scheduled successfully', [
                    'report_type' => $reportType,
                    'schedule_type' => $scheduleType,
                    'email_recipients' => $emailRecipients,
                    'filters' => $filters,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                Response::error('Failed to create scheduled report');
            }
        } catch (Exception $e) {
            Response::error('Failed to schedule report: ' . $e->getMessage());
        }
    }

    /**
     * Generate custom report with specific parameters
     */
    private function generateCustomReport($input) {
        $reportType = $input['report_type'] ?? '';
        $customFilters = $input['filters'] ?? [];
        $customizations = $input['customizations'] ?? [];
        
        if (empty($reportType)) {
            Response::error('Report type is required');
            return;
        }
        
        // Apply custom filters and generate report
        $data = $this->reportsIntegration->exportReportData($reportType, 'JSON', $customFilters);
        
        // Apply customizations if any
        if (!empty($customizations)) {
            $data = $this->applyCustomizations($data, $customizations);
        }
        
        Response::success($data, 'Custom report generated successfully');
    }

    /**
     * Get audit trail for report generation
     */
    private function getAuditTrail($filters) {
        $sql = "SELECT 
                    rgl.log_id,
                    rgl.report_type,
                    rgl.format,
                    rgl.filters,
                    rgl.generated_by,
                    rgl.generated_at,
                    u.Username as generated_by_name
                FROM report_generation_log rgl
                LEFT JOIN users u ON rgl.generated_by = u.UserID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['report_type'])) {
            $sql .= " AND rgl.report_type = :report_type";
            $params[':report_type'] = $filters['report_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND rgl.generated_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND rgl.generated_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY rgl.generated_at DESC
                  LIMIT 100";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $auditTrail = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success($auditTrail, 'Audit trail retrieved successfully');
    }

    /**
     * Apply customizations to report data
     */
    private function applyCustomizations($data, $customizations) {
        // Apply various customizations based on the customization parameters
        if (isset($customizations['date_range'])) {
            // Filter data by date range
            $data = $this->filterByDateRange($data, $customizations['date_range']);
        }
        
        if (isset($customizations['group_by'])) {
            // Group data by specified field
            $data = $this->groupData($data, $customizations['group_by']);
        }
        
        if (isset($customizations['sort_by'])) {
            // Sort data by specified field
            $data = $this->sortData($data, $customizations['sort_by']);
        }
        
        if (isset($customizations['aggregations'])) {
            // Apply aggregations
            $data = $this->applyAggregations($data, $customizations['aggregations']);
        }
        
        return $data;
    }

    /**
     * Filter data by date range
     */
    private function filterByDateRange($data, $dateRange) {
        // Implementation would depend on the data structure
        // This is a placeholder for date filtering logic
        return $data;
    }

    /**
     * Group data by specified field
     */
    private function groupData($data, $groupBy) {
        // Implementation would depend on the data structure
        // This is a placeholder for grouping logic
        return $data;
    }

    /**
     * Sort data by specified field
     */
    private function sortData($data, $sortBy) {
        // Implementation would depend on the data structure
        // This is a placeholder for sorting logic
        return $data;
    }

    /**
     * Apply aggregations to data
     */
    private function applyAggregations($data, $aggregations) {
        // Implementation would depend on the data structure
        // This is a placeholder for aggregation logic
        return $data;
    }

    /**
     * Process scheduled reports
     */
    private function processScheduledReports() {
        try {
            $result = $this->scheduler->processScheduledReports();
            Response::success($result, 'Scheduled reports processed');
        } catch (Exception $e) {
            Response::error('Failed to process scheduled reports: ' . $e->getMessage());
        }
    }

    /**
     * Test email delivery
     */
    private function testEmailDelivery($input) {
        $email = $input['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Valid email address is required');
            return;
        }
        
        try {
            $result = $this->scheduler->testEmailDelivery($email);
            
            if ($result) {
                Response::success('Test email sent successfully', [
                    'email' => $email,
                    'sent_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                Response::error('Failed to send test email');
            }
        } catch (Exception $e) {
            Response::error('Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Get user access summary
     */
    private function getUserAccessSummary() {
        $userId = $_SESSION['user_id'] ?? 'system';
        
        try {
            $accessSummary = $this->accessControl->getUserAccessSummary($userId);
            Response::success($accessSummary, 'User access summary retrieved successfully');
        } catch (Exception $e) {
            Response::error('Failed to get user access summary: ' . $e->getMessage());
        }
    }
}
?>
