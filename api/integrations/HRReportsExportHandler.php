<?php
/**
 * HR Reports Export Handler
 * Handles PDF, Excel, and CSV export functionality for HR reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../integrations/HRReportsIntegration.php';

class HRReportsExportHandler {
    private $pdo;
    private $reportsIntegration;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->reportsIntegration = new HRReportsIntegration();
    }

    /**
     * Export report in specified format
     */
    public function exportReport($reportType, $format, $filters = []) {
        try {
            switch ($format) {
                case 'PDF':
                    return $this->exportToPDF($reportType, $filters);
                case 'Excel':
                    return $this->exportToExcel($reportType, $filters);
                case 'CSV':
                    return $this->exportToCSV($reportType, $filters);
                default:
                    throw new Exception('Unsupported export format');
            }
        } catch (Exception $e) {
            throw new Exception('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export to PDF format
     */
    private function exportToPDF($reportType, $filters) {
        // Get report data
        $data = $this->getReportData($reportType, $filters);
        
        // Generate PDF content
        $pdfContent = $this->generatePDFContent($reportType, $data);
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="hr_report_' . $reportType . '_' . date('Y-m-d') . '.pdf"');
        
        // For now, return a placeholder - in production, use TCPDF or similar
        return [
            'success' => true,
            'message' => 'PDF export prepared',
            'data' => [
                'content' => $pdfContent,
                'filename' => 'hr_report_' . $reportType . '_' . date('Y-m-d') . '.pdf'
            ]
        ];
    }

    /**
     * Export to Excel format
     */
    private function exportToExcel($reportType, $filters) {
        // Get report data
        $data = $this->getReportData($reportType, $filters);
        
        // Generate Excel content
        $excelContent = $this->generateExcelContent($reportType, $data);
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="hr_report_' . $reportType . '_' . date('Y-m-d') . '.xlsx"');
        
        // For now, return a placeholder - in production, use PhpSpreadsheet
        return [
            'success' => true,
            'message' => 'Excel export prepared',
            'data' => [
                'content' => $excelContent,
                'filename' => 'hr_report_' . $reportType . '_' . date('Y-m-d') . '.xlsx'
            ]
        ];
    }

    /**
     * Export to CSV format
     */
    private function exportToCSV($reportType, $filters) {
        // Get report data
        $data = $this->getReportData($reportType, $filters);
        
        // Generate CSV content
        $csvContent = $this->generateCSVContent($reportType, $data);
        
        return [
            'success' => true,
            'message' => 'CSV export prepared',
            'data' => [
                'csv_data' => $csvContent,
                'filename' => 'hr_report_' . $reportType . '_' . date('Y-m-d') . '.csv'
            ]
        ];
    }

    /**
     * Get report data based on type
     */
    private function getReportData($reportType, $filters) {
        switch ($reportType) {
            case 'employee-demographics':
                return $this->reportsIntegration->getEmployeeDemographicsReport($filters);
            case 'recruitment-application':
                return $this->reportsIntegration->getRecruitmentApplicationReport($filters);
            case 'payroll-compensation':
                return $this->reportsIntegration->getPayrollCompensationReport($filters);
            case 'attendance-leave':
                return $this->reportsIntegration->getAttendanceLeaveReport($filters);
            case 'benefits-hmo-utilization':
                return $this->reportsIntegration->getBenefitsHMOUtilizationReport($filters);
            case 'training-development':
                return $this->reportsIntegration->getTrainingDevelopmentReport($filters);
            case 'employee-relations-engagement':
                return $this->reportsIntegration->getEmployeeRelationsEngagementReport($filters);
            case 'turnover-retention':
                return $this->reportsIntegration->getTurnoverRetentionReport($filters);
            case 'compliance-document':
                return $this->reportsIntegration->getComplianceDocumentReport($filters);
            case 'executive-summary':
                return $this->reportsIntegration->getExecutiveSummaryReport($filters);
            default:
                return $this->reportsIntegration->getHRReportsDashboard($filters);
        }
    }

    /**
     * Generate PDF content
     */
    private function generatePDFContent($reportType, $data) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>HR Report - ' . ucwords(str_replace('-', ' ', $reportType)) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #594423; margin: 0; }
                .header p { color: #666; margin: 5px 0; }
                .section { margin-bottom: 25px; }
                .section h2 { color: #4E3B2A; border-bottom: 2px solid #F7E6CA; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #F7E6CA; font-weight: bold; }
                .kpi-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; }
                .kpi-value { font-size: 24px; font-weight: bold; color: #594423; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>HR Report - ' . ucwords(str_replace('-', ' ', $reportType)) . '</h1>
                <p>Generated on: ' . date('F j, Y \a\t g:i A') . '</p>
                <p>Hospital HR Management System</p>
            </div>';

        // Add report-specific content
        $html .= $this->generateReportSpecificPDFContent($reportType, $data);

        $html .= '
            <div class="footer">
                <p>This report was generated by the Hospital HR Management System</p>
                <p>For questions or concerns, please contact the HR Department</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Generate report-specific PDF content
     */
    private function generateReportSpecificPDFContent($reportType, $data) {
        $html = '';

        switch ($reportType) {
            case 'executive-summary':
                $html .= $this->generateExecutiveSummaryPDFContent($data);
                break;
            case 'employee-demographics':
                $html .= $this->generateEmployeeDemographicsPDFContent($data);
                break;
            case 'payroll-compensation':
                $html .= $this->generatePayrollCompensationPDFContent($data);
                break;
            case 'compliance-document':
                $html .= $this->generateComplianceDocumentPDFContent($data);
                break;
            default:
                $html .= $this->generateGenericPDFContent($data);
        }

        return $html;
    }

    /**
     * Generate Executive Summary PDF content
     */
    private function generateExecutiveSummaryPDFContent($data) {
        $kpis = $data['kpi_metrics'] ?? [];
        $trends = $data['trend_indicators'] ?? [];
        $alerts = $data['alerts'] ?? [];

        $html = '
            <div class="section">
                <h2>Key Performance Indicators</h2>
                <div class="kpi-card">
                    <strong>Total Active Employees:</strong> <span class="kpi-value">' . number_format($kpis['total_active_employees'] ?? 0) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Annual Turnover Rate:</strong> <span class="kpi-value">' . number_format($kpis['annual_turnover_rate'] ?? 0, 2) . '%</span>
                </div>
                <div class="kpi-card">
                    <strong>Monthly Payroll Cost:</strong> <span class="kpi-value">₱' . number_format($kpis['monthly_payroll_cost'] ?? 0, 2) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Monthly Benefits Cost:</strong> <span class="kpi-value">₱' . number_format($kpis['monthly_benefits_cost'] ?? 0, 2) . '</span>
                </div>
            </div>

            <div class="section">
                <h2>Trend Indicators</h2>
                <table>
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Headcount Change</td><td>' . ($trends['headcount_change'] ?? 0) . '</td></tr>
                    <tr><td>Attendance Trend</td><td>' . ($trends['attendance_trend'] ?? 'Stable') . '</td></tr>
                    <tr><td>Training Completion Rate</td><td>' . number_format($trends['training_completion_rate'] ?? 0, 1) . '%</td></tr>
                </table>
            </div>';

        if (!empty($alerts)) {
            $html .= '
                <div class="section">
                    <h2>Alerts & Notifications</h2>
                    <ul>';
            foreach ($alerts as $key => $value) {
                $html .= '<li><strong>' . ucwords(str_replace('_', ' ', $key)) . ':</strong> ' . (is_array($value) ? count($value) : $value) . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html;
    }

    /**
     * Generate Employee Demographics PDF content
     */
    private function generateEmployeeDemographicsPDFContent($data) {
        $overview = $data['overview'] ?? [];
        $genderDist = $data['gender_distribution'] ?? [];
        $employmentDist = $data['employment_type_distribution'] ?? [];
        $ageDist = $data['age_distribution'] ?? [];

        $html = '
            <div class="section">
                <h2>Overview</h2>
                <div class="kpi-card">
                    <strong>Total Headcount:</strong> <span class="kpi-value">' . number_format($overview['total_headcount'] ?? 0) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Average Age:</strong> <span class="kpi-value">' . number_format($overview['avg_age'] ?? 0, 1) . ' years</span>
                </div>
                <div class="kpi-card">
                    <strong>Average Tenure:</strong> <span class="kpi-value">' . number_format($overview['avg_tenure_years'] ?? 0, 1) . ' years</span>
                </div>
            </div>

            <div class="section">
                <h2>Gender Distribution</h2>
                <table>
                    <tr><th>Gender</th><th>Count</th><th>Percentage</th></tr>';
        
        $total = array_sum(array_column($genderDist, 'count'));
        foreach ($genderDist as $item) {
            $percentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
            $html .= '<tr><td>' . $item['gender'] . '</td><td>' . number_format($item['count']) . '</td><td>' . number_format($percentage, 1) . '%</td></tr>';
        }
        $html .= '</table></div>';

        $html .= '
            <div class="section">
                <h2>Employment Type Distribution</h2>
                <table>
                    <tr><th>Type</th><th>Count</th><th>Percentage</th></tr>';
        
        $total = array_sum(array_column($employmentDist, 'count'));
        foreach ($employmentDist as $item) {
            $percentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
            $html .= '<tr><td>' . $item['type'] . '</td><td>' . number_format($item['count']) . '</td><td>' . number_format($percentage, 1) . '%</td></tr>';
        }
        $html .= '</table></div>';

        return $html;
    }

    /**
     * Generate Payroll Compensation PDF content
     */
    private function generatePayrollCompensationPDFContent($data) {
        $summary = $data['summary_metrics'] ?? [];
        $payrollTrend = $data['payroll_trend'] ?? [];
        $gradeDist = $data['salary_grade_distribution'] ?? [];

        $html = '
            <div class="section">
                <h2>Payroll Summary</h2>
                <div class="kpi-card">
                    <strong>Monthly Payroll:</strong> <span class="kpi-value">₱' . number_format($summary['total_monthly_payroll'] ?? 0, 2) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Average Employee Pay:</strong> <span class="kpi-value">₱' . number_format($summary['avg_employee_pay'] ?? 0, 2) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Employees Paid:</strong> <span class="kpi-value">' . number_format($summary['total_employees_paid'] ?? 0) . '</span>
                </div>
            </div>';

        if (!empty($payrollTrend)) {
            $html .= '
                <div class="section">
                    <h2>Payroll Trend (Last 12 Months)</h2>
                    <table>
                        <tr><th>Month</th><th>Gross Pay</th><th>Net Pay</th><th>Employees</th></tr>';
            foreach ($payrollTrend as $item) {
                $html .= '<tr><td>' . $item['month'] . '</td><td>₱' . number_format($item['total_gross_pay'] ?? 0, 2) . '</td><td>₱' . number_format($item['total_net_pay'] ?? 0, 2) . '</td><td>' . number_format($item['employees_paid'] ?? 0) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($gradeDist)) {
            $html .= '
                <div class="section">
                    <h2>Salary Grade Distribution</h2>
                    <table>
                        <tr><th>Grade</th><th>Name</th><th>Employees</th><th>Avg Salary</th></tr>';
            foreach ($gradeDist as $item) {
                $html .= '<tr><td>' . $item['grade_code'] . '</td><td>' . $item['grade_name'] . '</td><td>' . number_format($item['employee_count']) . '</td><td>₱' . number_format($item['avg_salary'] ?? 0, 2) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        return $html;
    }

    /**
     * Generate Compliance Document PDF content
     */
    private function generateComplianceDocumentPDFContent($data) {
        $summary = $data['compliance_summary'] ?? [];
        $documentStatus = $data['document_status'] ?? [];
        $complianceIssues = $data['compliance_issues'] ?? [];

        $html = '
            <div class="section">
                <h2>Compliance Summary</h2>
                <div class="kpi-card">
                    <strong>Total Documents:</strong> <span class="kpi-value">' . number_format($summary['total_documents'] ?? 0) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Expiring Soon (30 days):</strong> <span class="kpi-value">' . number_format($summary['expiring_30_days'] ?? 0) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Expired Documents:</strong> <span class="kpi-value">' . number_format($summary['expired_documents'] ?? 0) . '</span>
                </div>
                <div class="kpi-card">
                    <strong>Employees with Issues:</strong> <span class="kpi-value">' . number_format($summary['employees_with_compliance_issues'] ?? 0) . '</span>
                </div>
            </div>';

        if (!empty($documentStatus)) {
            $html .= '
                <div class="section">
                    <h2>Document Status by Type</h2>
                    <table>
                        <tr><th>Document Type</th><th>Total</th><th>Expiring 30 Days</th><th>Expired</th></tr>';
            foreach ($documentStatus as $item) {
                $html .= '<tr><td>' . $item['DocumentType'] . '</td><td>' . number_format($item['total_documents']) . '</td><td>' . number_format($item['expiring_30_days']) . '</td><td>' . number_format($item['expired_documents']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($complianceIssues)) {
            $html .= '
                <div class="section">
                    <h2>Compliance Issues</h2>
                    <table>
                        <tr><th>Employee</th><th>Department</th><th>Compliance Rate</th><th>Expired Docs</th></tr>';
            foreach ($complianceIssues as $item) {
                $html .= '<tr><td>' . $item['employee_name'] . '</td><td>' . $item['DepartmentName'] . '</td><td>' . number_format($item['compliance_rate'], 1) . '%</td><td>' . number_format($item['expired_documents']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        return $html;
    }

    /**
     * Generate generic PDF content
     */
    private function generateGenericPDFContent($data) {
        $html = '<div class="section"><h2>Report Data</h2>';
        
        if (is_array($data)) {
            $html .= '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
        } else {
            $html .= '<p>' . htmlspecialchars($data) . '</p>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Generate Excel content
     */
    private function generateExcelContent($reportType, $data) {
        // This would use PhpSpreadsheet in production
        // For now, return a placeholder
        return [
            'sheets' => [
                'Summary' => $this->flattenDataForExcel($data),
                'Details' => $this->getDetailedDataForExcel($reportType, $data)
            ]
        ];
    }

    /**
     * Generate CSV content
     */
    private function generateCSVContent($reportType, $data) {
        $csv = "Report Type,Generated At\n";
        $csv .= "$reportType," . date('Y-m-d H:i:s') . "\n\n";
        
        // Flatten the data structure for CSV
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= "\n$key\n";
                if (!empty($value) && is_array($value[0])) {
                    // Array of objects
                    $headers = array_keys($value[0]);
                    $csv .= implode(',', $headers) . "\n";
                    foreach ($value as $row) {
                        $csv .= implode(',', array_map(function($val) {
                            return '"' . str_replace('"', '""', $val) . '"';
                        }, array_values($row))) . "\n";
                    }
                } else {
                    // Simple array
                    $csv .= implode(',', array_map(function($val) {
                        return '"' . str_replace('"', '""', $val) . '"';
                    }, array_values($value))) . "\n";
                }
            } else {
                $csv .= "$key,\"$value\"\n";
            }
        }
        
        return $csv;
    }

    /**
     * Flatten data for Excel export
     */
    private function flattenDataForExcel($data) {
        $flattened = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (!empty($value) && is_array($value[0])) {
                    // Array of objects - create rows
                    foreach ($value as $row) {
                        $flattened[] = array_merge(['section' => $key], $row);
                    }
                } else {
                    // Simple array
                    $flattened[] = ['section' => $key, 'value' => implode(', ', $value)];
                }
            } else {
                $flattened[] = ['section' => $key, 'value' => $value];
            }
        }
        
        return $flattened;
    }

    /**
     * Get detailed data for Excel export
     */
    private function getDetailedDataForExcel($reportType, $data) {
        // This would return more detailed data based on report type
        return $this->flattenDataForExcel($data);
    }
}
?>
