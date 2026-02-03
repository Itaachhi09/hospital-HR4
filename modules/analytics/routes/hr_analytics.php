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
        // TEMPORARILY DISABLED FOR DEBUGGING - RE-ENABLE IN PRODUCTION
        // // Authentication check
        // if (!$this->authMiddleware->authenticate()) {
        //     Response::unauthorized('Authentication required');
        //     return;
        // }

        // $currentUser = $this->authMiddleware->getCurrentUser();
        // $allowedRoles = ['System Admin', 'HR Manager', 'HR Staff', 'Finance Manager'];
        // if (!in_array($currentUser['role_name'] ?? '', $allowedRoles)) {
        //     Response::forbidden('Insufficient permissions for analytics access');
        //     return;
        // }

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
        
        // $id contains the actual endpoint when URL is /api/hr-analytics/executive-summary
        // So we use $id as the route selector
        $endpoint = $id ?? $subResource;
        
        try {
            switch ($endpoint) {
                // ===== NEW FRONTEND ENDPOINTS =====
                
                case 'executive-summary':
                    // Executive summary for Overview tab (8 KPI cards + basic data)
                    $data = $this->analytics->getExecutiveSummary($filters);
                    Response::success($data, 'Executive summary retrieved successfully');
                    break;
                
                case 'headcount-trend':
                    // 12-month headcount trend chart data
                    $data = $this->analytics->getHeadcountTrendData($filters);
                    Response::success($data, 'Headcount trend retrieved successfully');
                    break;
                
                case 'turnover-by-department':
                    // Turnover percentage by department chart data
                    $data = $this->analytics->getTurnoverByDepartmentData($filters);
                    Response::success($data, 'Turnover by department retrieved successfully');
                    break;
                
                case 'payroll-trend':
                    // Payroll cost trend with breakdown (Basic, OT, Bonuses)
                    $data = $this->analytics->getPayrollTrendData($filters);
                    Response::success($data, 'Payroll trend retrieved successfully');
                    break;
                
                case 'employee-demographics':
                    // Complete demographics for Workforce Analytics tab
                    $data = $this->analytics->getEmployeeDemographicsComplete($filters);
                    Response::success($data, 'Employee demographics retrieved successfully');
                    break;
                
                case 'payroll-compensation':
                    // Complete payroll data for Payroll Insights tab
                    $data = $this->analytics->getPayrollCompensationComplete($filters);
                    Response::success($data, 'Payroll compensation data retrieved successfully');
                    break;
                
                case 'benefits-hmo':
                    // Complete HMO/Benefits data for Benefits Utilization tab
                    $data = $this->analytics->getBenefitsHMOComplete($filters);
                    Response::success($data, 'Benefits HMO data retrieved successfully');
                    break;
                
                case 'training-development':
                    // Complete training data for Training & Performance tab
                    $data = $this->analytics->getTrainingDevelopmentComplete($filters);
                    Response::success($data, 'Training development data retrieved successfully');
                    break;
                
                // ===== LEGACY ENDPOINTS =====
                
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

        // Generate PDF using basic HTML to PDF conversion
        $html = $this->generateReportHTML($data, $reportType);
        $filename = 'hr_analytics_' . $reportType . '_' . date('Y-m-d_H-i-s') . '.html';
        
        // For now, return HTML content that can be printed to PDF
        Response::success([
            'report_type' => $reportType,
            'format' => 'PDF',
            'html_content' => $html,
            'filename' => $filename,
            'generated_at' => date('Y-m-d H:i:s'),
            'message' => 'PDF export ready - HTML content generated'
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

    /**
     * Generate HTML report content
     */
    private function generateReportHTML($data, $reportType) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HR Analytics Report - ' . ucfirst($reportType) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .kpi-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .kpi-value { font-size: 24px; font-weight: bold; color: #333; }
        .kpi-label { font-size: 14px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HR Analytics Report</h1>
        <p>Report Type: ' . ucfirst($reportType) . '</p>
        <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>';

        // Add overview metrics
        if (isset($data['overview'])) {
            $html .= '<div class="section">
                <h2>Overview Metrics</h2>
                <div class="kpi-grid">';
            
            foreach ($data['overview'] as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $html .= '<div class="kpi-card">
                    <div class="kpi-value">' . (is_numeric($value) ? number_format($value) : $value) . '</div>
                    <div class="kpi-label">' . $label . '</div>
                </div>';
            }
            
            $html .= '</div></div>';
        }

        // Add workforce data
        if (isset($data['workforce'])) {
            $html .= '<div class="section">
                <h2>Workforce Analytics</h2>';
            
            if (isset($data['workforce']['headcount_by_department'])) {
                $html .= '<h3>Headcount by Department</h3>
                <table>
                    <tr><th>Department</th><th>Headcount</th><th>Male</th><th>Female</th><th>Avg Salary</th></tr>';
                
                foreach ($data['workforce']['headcount_by_department'] as $dept) {
                    $html .= '<tr>
                        <td>' . htmlspecialchars($dept['department_name']) . '</td>
                        <td>' . $dept['headcount'] . '</td>
                        <td>' . $dept['male_count'] . '</td>
                        <td>' . $dept['female_count'] . '</td>
                        <td>₱' . number_format($dept['avg_salary'], 2) . '</td>
                    </tr>';
                }
                
                $html .= '</table>';
            }
            
            $html .= '</div>';
        }

        $html .= '</body></html>';
        
        return $html;
    }
}
?>

