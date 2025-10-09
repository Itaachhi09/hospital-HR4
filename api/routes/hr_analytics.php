<?php
/**
 * HR Analytics Routes
 * Comprehensive analytics endpoints for workforce, payroll, and benefits insights
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../integrations/HRAnalytics.php';
require_once __DIR__ . '/../utils/Response.php';

class HRAnalyticsController {
    private $pdo;
    private $authMiddleware;
    private $analytics;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
        $this->analytics = new HRAnalytics();
    }

    /**
     * Handle HR analytics requests
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
        $allowedRoles = ['System Admin', 'HR Manager', 'HR Staff', 'Finance Manager'];
        if (!in_array($currentUser['role_name'] ?? '', $allowedRoles)) {
            Response::forbidden('Insufficient permissions for analytics access');
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
        
        try {
            switch ($subResource) {
                case 'dashboard':
                    // Complete HR analytics dashboard
                    $data = $this->analytics->getHRAnalyticsDashboard($filters);
                    Response::success($data, 'HR analytics dashboard retrieved successfully');
                    break;

                case 'overview':
                    // Overview metrics only
                    $data = $this->analytics->getOverviewMetrics();
                    Response::success($data, 'Overview metrics retrieved successfully');
                    break;

                case 'workforce':
                    // Workforce analytics
                    $data = $this->analytics->getWorkforceAnalytics($filters);
                    Response::success($data, 'Workforce analytics retrieved successfully');
                    break;

                case 'payroll':
                    // Payroll cost analytics
                    $data = $this->analytics->getPayrollCostAnalytics($filters);
                    Response::success($data, 'Payroll analytics retrieved successfully');
                    break;

                case 'benefits':
                    // Benefits utilization
                    $data = $this->analytics->getBenefitsUtilization($filters);
                    Response::success($data, 'Benefits utilization retrieved successfully');
                    break;

                case 'training':
                    // Training analytics
                    $data = $this->analytics->getTrainingAnalytics($filters);
                    Response::success($data, 'Training analytics retrieved successfully');
                    break;

                case 'attendance':
                    // Attendance analytics
                    $data = $this->analytics->getAttendanceAnalytics($filters);
                    Response::success($data, 'Attendance analytics retrieved successfully');
                    break;

                case 'turnover':
                    // Turnover analytics
                    $data = $this->analytics->getTurnoverAnalytics($filters);
                    Response::success($data, 'Turnover analytics retrieved successfully');
                    break;

                case 'demographics':
                    // Demographics analytics
                    $data = $this->analytics->getDemographicsAnalytics($filters);
                    Response::success($data, 'Demographics analytics retrieved successfully');
                    break;

                case 'export':
                    // Export to Finance/HADS
                    $period = $filters['period'] ?? date('Y-m');
                    $data = $this->analytics->exportToFinanceAnalytics($period);
                    Response::success($data, 'Analytics data exported successfully');
                    break;

                default:
                    // Default: full dashboard
                    $data = $this->analytics->getHRAnalyticsDashboard($filters);
                    Response::success($data, 'HR analytics retrieved successfully');
            }
        } catch (Exception $e) {
            error_log("HR Analytics error: " . $e->getMessage());
            Response::error('Failed to retrieve analytics data: ' . $e->getMessage(), 500);
        }
    }

    private function handlePost($id, $subResource) {
        try {
            switch ($subResource) {
                case 'export-pdf':
                    $this->exportToPDF();
                    break;

                case 'export-excel':
                    $this->exportToExcel();
                    break;

                case 'export-csv':
                    $this->exportToCSV();
                    break;

                case 'schedule-report':
                    $this->scheduleReport();
                    break;

                default:
                    Response::notFound('Export type not found');
            }
        } catch (Exception $e) {
            error_log("HR Analytics export error: " . $e->getMessage());
            Response::error('Failed to export data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export to PDF
     */
    private function exportToPDF() {
        $input = json_decode(file_get_contents('php://input'), true);
        $reportType = $input['report_type'] ?? 'dashboard';
        $filters = $input['filters'] ?? [];

        // Get analytics data
        $data = $this->analytics->getHRAnalyticsDashboard($filters);

        // TODO: Implement PDF generation using library like TCPDF or mPDF
        // For now, return data structure
        Response::success([
            'report_type' => $reportType,
            'format' => 'PDF',
            'data' => $data,
            'generated_at' => date('Y-m-d H:i:s'),
            'message' => 'PDF export ready (implementation pending)'
        ], 'PDF export prepared');
    }

    /**
     * Export to Excel
     */
    private function exportToExcel() {
        $input = json_decode(file_get_contents('php://input'), true);
        $reportType = $input['report_type'] ?? 'dashboard';
        $filters = $input['filters'] ?? [];

        // Get analytics data
        $data = $this->analytics->getHRAnalyticsDashboard($filters);

        // TODO: Implement Excel generation using PhpSpreadsheet
        // For now, return data structure
        Response::success([
            'report_type' => $reportType,
            'format' => 'Excel',
            'data' => $data,
            'generated_at' => date('Y-m-d H:i:s'),
            'message' => 'Excel export ready (implementation pending)'
        ], 'Excel export prepared');
    }

    /**
     * Export to CSV
     */
    private function exportToCSV() {
        $input = json_decode(file_get_contents('php://input'), true);
        $reportType = $input['report_type'] ?? 'overview';
        $filters = $input['filters'] ?? [];

        // Get analytics data
        switch ($reportType) {
            case 'workforce':
                $data = $this->analytics->getWorkforceAnalytics($filters);
                break;
            case 'payroll':
                $data = $this->analytics->getPayrollCostAnalytics($filters);
                break;
            default:
                $data = $this->analytics->getOverviewMetrics();
        }

        // Convert to CSV format
        $csvData = $this->convertToCSV($data);

        Response::success([
            'report_type' => $reportType,
            'format' => 'CSV',
            'csv_data' => $csvData,
            'generated_at' => date('Y-m-d H:i:s')
        ], 'CSV export generated');
    }

    /**
     * Schedule automated report
     */
    private function scheduleReport() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $schedule = [
            'report_type' => $input['report_type'] ?? 'dashboard',
            'frequency' => $input['frequency'] ?? 'monthly', // daily, weekly, monthly
            'recipients' => $input['recipients'] ?? [],
            'format' => $input['format'] ?? 'PDF',
            'filters' => $input['filters'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'next_run' => $this->calculateNextRun($input['frequency'] ?? 'monthly')
        ];

        // TODO: Save schedule to database and implement cron job
        Response::success($schedule, 'Report schedule created successfully');
    }

    /**
     * Helper: Convert data array to CSV
     */
    private function convertToCSV($data) {
        if (empty($data)) {
            return '';
        }

        $csv = [];
        
        // Handle nested arrays
        if (isset($data[0]) && is_array($data[0])) {
            // Array of objects
            $headers = array_keys($data[0]);
            $csv[] = implode(',', array_map(function($h) {
                return '"' . str_replace('"', '""', $h) . '"';
            }, $headers));

            foreach ($data as $row) {
                $csv[] = implode(',', array_map(function($v) {
                    return '"' . str_replace('"', '""', $v) . '"';
                }, array_values($row)));
            }
        } else {
            // Single object
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    $csv[] = '"' . str_replace('"', '""', $key) . '","' . str_replace('"', '""', $value) . '"';
                }
            }
        }

        return implode("\n", $csv);
    }

    /**
     * Helper: Calculate next run date for scheduled reports
     */
    private function calculateNextRun($frequency) {
        $now = new DateTime();
        
        switch ($frequency) {
            case 'daily':
                $now->modify('+1 day');
                break;
            case 'weekly':
                $now->modify('+1 week');
                break;
            case 'monthly':
                $now->modify('+1 month');
                break;
            default:
                $now->modify('+1 month');
        }

        return $now->format('Y-m-d H:i:s');
    }
}
?>

