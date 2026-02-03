<?php
/**
 * HR Analytics Metrics Export & Integration Handler
 * Handles export functionality and integration with HADS/Finance systems
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/HRAnalyticsMetricsFramework.php';
require_once __DIR__ . '/HRAnalyticsMetricsStorage.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Dompdf\Dompdf;
use Dompdf\Options;

class HRAnalyticsMetricsExportHandler {
    private $pdo;
    private $metricsFramework;
    private $metricsStorage;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->metricsFramework = new HRAnalyticsMetricsFramework();
        $this->metricsStorage = new HRAnalyticsMetricsStorage();
    }

    /**
     * Export metrics in various formats
     */
    public function exportMetrics($metrics, $format, $filters = []) {
        switch (strtoupper($format)) {
            case 'EXCEL':
                return $this->exportToExcel($metrics, $filters);
            case 'PDF':
                return $this->exportToPdf($metrics, $filters);
            case 'CSV':
                return $this->exportToCsv($metrics, $filters);
            case 'JSON':
                return $this->exportToJson($metrics, $filters);
            default:
                throw new Exception("Unsupported export format: $format");
        }
    }

    /**
     * Export to Excel format
     */
    public function exportToExcel($metrics, $filters = []) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('HR Analytics System')
            ->setLastModifiedBy('HR Analytics System')
            ->setTitle('HR Analytics Metrics Report')
            ->setSubject('HR Analytics Metrics')
            ->setDescription('Comprehensive HR Analytics Metrics Report')
            ->setKeywords('HR, Analytics, Metrics, Report')
            ->setCategory('HR Reports');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('HR Metrics Summary');

        // Header
        $sheet->setCellValue('A1', 'HR Analytics Metrics Report');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Filters: ' . json_encode($filters));
        $sheet->mergeCells('A3:F3');

        $row = 5;

        // Process each metric
        foreach ($metrics as $metricKey => $metricData) {
            $parts = explode('.', $metricKey);
            $category = $parts[0];
            $metricName = $parts[1];

            // Category header
            $sheet->setCellValue("A$row", $this->formatCategoryName($category));
            $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("A$row")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E3F2FD');
            $row++;

            // Metric name and value
            $sheet->setCellValue("A$row", $this->formatMetricName($metricName));
            $sheet->setCellValue("B$row", $this->getMetricValue($metricData));
            $sheet->setCellValue("C$row", $this->getMetricDescription($metricData));
            $sheet->getStyle("A$row")->getFont()->setBold(true);
            $row++;

            // If metric has detailed data, add it
            if (isset($metricData['data']) && is_array($metricData['data'])) {
                $this->addDetailedDataToSheet($sheet, $metricData['data'], $row);
                $row += count($metricData['data']) + 2;
            } else {
                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Save to temporary file
        $filename = 'hr_metrics_' . date('Ymd_His') . '.xlsx';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return [
            'file_path' => $filepath,
            'filename' => $filename,
            'format' => 'excel',
            'size' => filesize($filepath)
        ];
    }

    /**
     * Export to PDF format
     */
    public function exportToPdf($metrics, $filters = []) {
        $html = $this->generatePdfHtml($metrics, $filters);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'hr_metrics_' . date('Ymd_His') . '.pdf';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        file_put_contents($filepath, $dompdf->output());
        
        return [
            'file_path' => $filepath,
            'filename' => $filename,
            'format' => 'pdf',
            'size' => filesize($filepath)
        ];
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv($metrics, $filters = []) {
        $filename = 'hr_metrics_' . date('Ymd_His') . '.csv';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        // CSV header
        fputcsv($file, ['Category', 'Metric Name', 'Value', 'Description', 'Generated Date']);
        
        // Add metrics data
        foreach ($metrics as $metricKey => $metricData) {
            $parts = explode('.', $metricKey);
            $category = $parts[0];
            $metricName = $parts[1];
            
            fputcsv($file, [
                $this->formatCategoryName($category),
                $this->formatMetricName($metricName),
                $this->getMetricValue($metricData),
                $this->getMetricDescription($metricData),
                date('Y-m-d H:i:s')
            ]);
        }
        
        fclose($file);
        
        return [
            'file_path' => $filepath,
            'filename' => $filename,
            'format' => 'csv',
            'size' => filesize($filepath)
        ];
    }

    /**
     * Export to JSON format
     */
    public function exportToJson($metrics, $filters = []) {
        $data = [
            'export_info' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'format' => 'json',
                'filters' => $filters,
                'total_metrics' => count($metrics)
            ],
            'metrics' => $metrics
        ];
        
        $filename = 'hr_metrics_' . date('Ymd_His') . '.json';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        
        return [
            'file_path' => $filepath,
            'filename' => $filename,
            'format' => 'json',
            'size' => filesize($filepath)
        ];
    }

    /**
     * Generate PDF HTML content
     */
    private function generatePdfHtml($metrics, $filters) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>HR Analytics Metrics Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #333; margin-bottom: 10px; }
                .header p { color: #666; margin: 5px 0; }
                .category { margin-bottom: 20px; }
                .category-title { background-color: #f0f0f0; padding: 10px; font-weight: bold; margin-bottom: 10px; }
                .metric { margin-bottom: 15px; padding: 10px; border-left: 4px solid #007bff; }
                .metric-name { font-weight: bold; color: #333; }
                .metric-value { font-size: 18px; color: #007bff; margin: 5px 0; }
                .metric-description { color: #666; font-size: 14px; }
                .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>HR Analytics Metrics Report</h1>
                <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
                <p>Filters: ' . json_encode($filters) . '</p>
            </div>
        ';
        
        foreach ($metrics as $metricKey => $metricData) {
            $parts = explode('.', $metricKey);
            $category = $parts[0];
            $metricName = $parts[1];
            
            $html .= '<div class="category">';
            $html .= '<div class="category-title">' . $this->formatCategoryName($category) . '</div>';
            $html .= '<div class="metric">';
            $html .= '<div class="metric-name">' . $this->formatMetricName($metricName) . '</div>';
            $html .= '<div class="metric-value">' . $this->getMetricValue($metricData) . '</div>';
            $html .= '<div class="metric-description">' . $this->getMetricDescription($metricData) . '</div>';
            
            if (isset($metricData['data']) && is_array($metricData['data'])) {
                $html .= $this->generateTableHtml($metricData['data']);
            }
            
            $html .= '</div></div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Generate table HTML for detailed data
     */
    private function generateTableHtml($data) {
        if (empty($data)) {
            return '';
        }
        
        $html = '<table class="table">';
        $html .= '<thead><tr>';
        
        $columns = array_keys($data[0]);
        foreach ($columns as $column) {
            $html .= '<th>' . $this->formatColumnName($column) . '</th>';
        }
        
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $html .= '<td>' . $this->formatValue($row[$column]) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Add detailed data to Excel sheet
     */
    private function addDetailedDataToSheet($sheet, $data, &$row) {
        if (empty($data)) {
            return;
        }
        
        $columns = array_keys($data[0]);
        
        // Add headers
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . $row, $this->formatColumnName($column));
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        // Add data
        foreach ($data as $dataRow) {
            $col = 'A';
            foreach ($columns as $column) {
                $sheet->setCellValue($col . $row, $this->formatValue($dataRow[$column]));
                $col++;
            }
            $row++;
        }
    }

    /**
     * Push metrics to HADS (Healthcare Analytics and Dashboard System)
     */
    public function pushToHADS($metrics, $filters = []) {
        $hadsData = $this->formatMetricsForHADS($metrics, $filters);
        
        // Simulate HADS API call
        $hadsEndpoint = 'https://hads.example.com/api/metrics';
        $hadsApiKey = 'your-hads-api-key';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $hadsApiKey,
            'X-Source: HR-Analytics-System'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $hadsEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($hadsData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $this->logIntegration('HADS', 'success', $hadsData);
            return ['status' => 'success', 'message' => 'Metrics pushed to HADS successfully'];
        } else {
            $this->logIntegration('HADS', 'error', $hadsData, $response);
            return ['status' => 'error', 'message' => 'Failed to push metrics to HADS'];
        }
    }

    /**
     * Push metrics to Finance system
     */
    public function pushToFinance($metrics, $filters = []) {
        $financeData = $this->formatMetricsForFinance($metrics, $filters);
        
        // Simulate Finance API call
        $financeEndpoint = 'https://finance.example.com/api/hr-metrics';
        $financeApiKey = 'your-finance-api-key';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $financeApiKey,
            'X-Source: HR-Analytics-System'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $financeEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($financeData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $this->logIntegration('Finance', 'success', $financeData);
            return ['status' => 'success', 'message' => 'Metrics pushed to Finance successfully'];
        } else {
            $this->logIntegration('Finance', 'error', $financeData, $response);
            return ['status' => 'error', 'message' => 'Failed to push metrics to Finance'];
        }
    }

    /**
     * Format metrics for HADS
     */
    private function formatMetricsForHADS($metrics, $filters) {
        $hadsData = [
            'timestamp' => date('c'),
            'source' => 'HR-Analytics-System',
            'version' => '1.0',
            'filters' => $filters,
            'metrics' => []
        ];
        
        foreach ($metrics as $metricKey => $metricData) {
            $parts = explode('.', $metricKey);
            $category = $parts[0];
            $metricName = $parts[1];
            
            $hadsData['metrics'][] = [
                'id' => $metricKey,
                'category' => $category,
                'name' => $metricName,
                'value' => $this->getMetricValue($metricData),
                'description' => $this->getMetricDescription($metricData),
                'display_type' => $metricData['display_type'] ?? 'KPI_CARD',
                'data' => $metricData['data'] ?? null
            ];
        }
        
        return $hadsData;
    }

    /**
     * Format metrics for Finance system
     */
    private function formatMetricsForFinance($metrics, $filters) {
        $financeData = [
            'timestamp' => date('c'),
            'source' => 'HR-Analytics-System',
            'version' => '1.0',
            'filters' => $filters,
            'financial_metrics' => []
        ];
        
        // Only include financial-related metrics
        $financialCategories = ['payroll_compensation', 'benefits_hmo', 'executive_kpi'];
        
        foreach ($metrics as $metricKey => $metricData) {
            $parts = explode('.', $metricKey);
            $category = $parts[0];
            $metricName = $parts[1];
            
            if (in_array($category, $financialCategories)) {
                $financeData['financial_metrics'][] = [
                    'id' => $metricKey,
                    'category' => $category,
                    'name' => $metricName,
                    'value' => $this->getMetricValue($metricData),
                    'description' => $this->getMetricDescription($metricData),
                    'currency' => $this->getCurrencyForMetric($metricName),
                    'data' => $metricData['data'] ?? null
                ];
            }
        }
        
        return $financeData;
    }

    /**
     * Get currency for metric
     */
    private function getCurrencyForMetric($metricName) {
        $currencyMetrics = [
            'total_payroll_cost' => 'USD',
            'avg_salary_per_grade' => 'USD',
            'payroll_cost_per_department' => 'USD',
            'overtime_cost_ratio' => 'USD',
            'total_benefits_cost' => 'USD',
            'average_claim_cost' => 'USD',
            'training_cost_per_employee' => 'USD'
        ];
        
        return $currencyMetrics[$metricName] ?? 'USD';
    }

    /**
     * Log integration activity
     */
    private function logIntegration($system, $status, $data, $response = null) {
        $sql = "INSERT INTO metrics_integration_log 
                (system, status, data_sent, response_received, timestamp) 
                VALUES (:system, :status, :data_sent, :response_received, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':system', $system);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':data_sent', json_encode($data));
        $stmt->bindValue(':response_received', $response);
        $stmt->execute();
    }

    /**
     * Get integration logs
     */
    public function getIntegrationLogs($system = null, $limit = 50) {
        $sql = "SELECT * FROM metrics_integration_log WHERE 1=1";
        $params = [];
        
        if ($system) {
            $sql .= " AND system = :system";
            $params[':system'] = $system;
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT :limit";
        $params[':limit'] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper methods for formatting
     */
    private function formatCategoryName($category) {
        return str_replace('_', ' ', ucwords($category, '_'));
    }

    private function formatMetricName($metricName) {
        return str_replace('_', ' ', ucwords($metricName, '_'));
    }

    private function formatColumnName($columnName) {
        return str_replace('_', ' ', ucwords($columnName, '_'));
    }

    private function getMetricValue($metricData) {
        if (isset($metricData['value'])) {
            return $this->formatValue($metricData['value']);
        } elseif (isset($metricData['formatted_value'])) {
            return $metricData['formatted_value'];
        } elseif (isset($metricData['data']) && is_array($metricData['data'])) {
            return count($metricData['data']) . ' records';
        }
        
        return 'N/A';
    }

    private function getMetricDescription($metricData) {
        return $metricData['description'] ?? 'No description available';
    }

    private function formatValue($value) {
        if (is_numeric($value)) {
            if ($value >= 1000000) {
                return number_format($value / 1000000, 1) . 'M';
            } elseif ($value >= 1000) {
                return number_format($value / 1000, 1) . 'K';
            } else {
                return number_format($value, 2);
            }
        }
        
        return $value;
    }
}
?>
