<?php
/**
 * HR Reports Export Handler
 * Handles export functionality for HR reports in various formats
 */

require_once __DIR__ . '/../config.php';

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
            // Get report data
            $data = $this->getReportData($reportType, $filters);
            
            switch (strtoupper($format)) {
                case 'CSV':
                    return $this->exportToCSV($data, $reportType);
                case 'EXCEL':
                    return $this->exportToExcel($data, $reportType);
                case 'PDF':
                    return $this->exportToPDF($data, $reportType);
                default:
                    throw new Exception("Unsupported export format: $format");
            }
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get report data based on type
     */
    private function getReportData($reportType, $filters) {
        switch ($reportType) {
            case 'executive-summary':
                return $this->reportsIntegration->getExecutiveSummaryReport($filters);
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
            default:
                throw new Exception("Unknown report type: $reportType");
        }
    }

    /**
     * Export to CSV format
     */
    private function exportToCSV($data, $reportType) {
        $csvData = $this->convertToCSV($data);
        
        return [
            'format' => 'CSV',
            'report_type' => $reportType,
            'csv_data' => $csvData,
            'filename' => "hr_report_{$reportType}_" . date('Y-m-d') . ".csv",
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Export to Excel format
     */
    private function exportToExcel($data, $reportType) {
        // TODO: Implement Excel export using PhpSpreadsheet
        // For now, return CSV data with Excel headers
        $csvData = $this->convertToCSV($data);
        
        return [
            'format' => 'Excel',
            'report_type' => $reportType,
            'csv_data' => $csvData, // Temporary - will be replaced with actual Excel data
            'filename' => "hr_report_{$reportType}_" . date('Y-m-d') . ".xlsx",
            'generated_at' => date('Y-m-d H:i:s'),
            'message' => 'Excel export ready (implementation pending)'
        ];
    }

    /**
     * Export to PDF format
     */
    private function exportToPDF($data, $reportType) {
        // TODO: Implement PDF export using TCPDF or mPDF
        // For now, return data structure
        return [
            'format' => 'PDF',
            'report_type' => $reportType,
            'data' => $data,
            'filename' => "hr_report_{$reportType}_" . date('Y-m-d') . ".pdf",
            'generated_at' => date('Y-m-d H:i:s'),
            'message' => 'PDF export ready (implementation pending)'
        ];
    }

    /**
     * Convert data array to CSV format
     */
    private function convertToCSV($data) {
        if (empty($data)) {
            return '';
        }

        $csv = [];
        
        // Handle different data structures
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
            // Single object or nested structure
            $this->flattenDataToCSV($data, $csv);
        }

        return implode("\n", $csv);
    }

    /**
     * Flatten nested data structure to CSV
     */
    private function flattenDataToCSV($data, &$csv, $prefix = '') {
        foreach ($data as $key => $value) {
            $newKey = $prefix ? "{$prefix}_{$key}" : $key;
            
            if (is_array($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    // Array of objects
                    $headers = array_keys($value[0]);
                    $csv[] = implode(',', array_map(function($h) use ($newKey) {
                        return '"' . str_replace('"', '""', "{$newKey}_{$h}") . '"';
                    }, $headers));

                    foreach ($value as $row) {
                        $csv[] = implode(',', array_map(function($v) {
                            return '"' . str_replace('"', '""', $v) . '"';
                        }, array_values($row)));
                    }
                } else {
                    // Nested object
                    $this->flattenDataToCSV($value, $csv, $newKey);
                }
            } else {
                $csv[] = '"' . str_replace('"', '""', $newKey) . '","' . str_replace('"', '""', $value) . '"';
            }
        }
    }
}
?>