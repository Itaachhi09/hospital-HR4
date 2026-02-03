<?php
/**
 * HR Analytics Metrics Automation System
 * Handles automated metric recalculation and scheduling
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/HRAnalyticsMetricsFramework.php';
require_once __DIR__ . '/HRAnalyticsMetricsStorage.php';

class HRAnalyticsMetricsAutomation {
    private $pdo;
    private $metricsFramework;
    private $metricsStorage;
    private $isRunning = false;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->metricsFramework = new HRAnalyticsMetricsFramework();
        $this->metricsStorage = new HRAnalyticsMetricsStorage();
    }

    /**
     * Process all scheduled metric calculations
     */
    public function processScheduledCalculations() {
        if ($this->isRunning) {
            return ['status' => 'already_running'];
        }

        $this->isRunning = true;
        $startTime = microtime(true);
        
        try {
            $results = [];
            
            // Get all metric definitions
            $definitions = $this->metricsFramework->getAllMetricDefinitions();
            
            foreach ($definitions as $category => $metrics) {
                foreach ($metrics as $metricName => $metric) {
                    try {
                        $result = $this->calculateAndStoreMetric($category, $metricName);
                        $results[] = [
                            'category' => $category,
                            'metric_name' => $metricName,
                            'status' => 'success',
                            'result' => $result
                        ];
                    } catch (Exception $e) {
                        $results[] = [
                            'category' => $category,
                            'metric_name' => $metricName,
                            'status' => 'error',
                            'error' => $e->getMessage()
                        ];
                        
                        $this->logCalculationError($category, $metricName, $e->getMessage());
                    }
                }
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logCalculationSummary($results, $executionTime);
            
            return [
                'status' => 'completed',
                'execution_time_ms' => $executionTime,
                'total_metrics' => count($results),
                'successful' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
                'failed' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->logCalculationError('system', 'batch_calculation', $e->getMessage());
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        } finally {
            $this->isRunning = false;
        }
    }

    /**
     * Calculate and store a specific metric
     */
    public function calculateAndStoreMetric($category, $metricName, $filters = []) {
        $metricId = $category . '.' . $metricName;
        $period = date('Y-m');
        
        // Check if metric is already calculated for this period
        if ($this->metricsStorage->isMetricDataFresh($metricId, 3600)) {
            return $this->metricsStorage->getStoredMetricData($metricId, $period, $filters);
        }
        
        // Calculate metric
        $result = $this->metricsFramework->calculateMetric($category, $metricName, $filters);
        
        // Store result
        $this->metricsStorage->storeMetricData($metricId, $category, $metricName, $result, $period, $filters);
        
        // Cache result
        $cacheKey = $this->metricsStorage->generateCacheKey($category, $metricName, $filters);
        $this->metricsStorage->setCachedMetric($cacheKey, $result);
        
        return $result;
    }

    /**
     * Process metric alerts
     */
    public function processMetricAlerts() {
        $alerts = $this->getActiveAlerts();
        $triggeredAlerts = [];
        
        foreach ($alerts as $alert) {
            try {
                $metricValue = $this->getMetricValue($alert['metric_id']);
                
                if ($this->evaluateAlertCondition($alert, $metricValue)) {
                    $this->triggerAlert($alert, $metricValue);
                    $triggeredAlerts[] = $alert;
                }
            } catch (Exception $e) {
                error_log("Error processing alert {$alert['id']}: " . $e->getMessage());
            }
        }
        
        return $triggeredAlerts;
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts() {
        $sql = "SELECT * FROM metrics_alerts WHERE is_active = TRUE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get metric value
     */
    private function getMetricValue($metricId) {
        $parts = explode('.', $metricId);
        if (count($parts) < 2) {
            throw new Exception("Invalid metric ID: $metricId");
        }
        
        $category = $parts[0];
        $metricName = $parts[1];
        
        $result = $this->metricsFramework->calculateMetric($category, $metricName);
        
        return $result['value'] ?? 0;
    }

    /**
     * Evaluate alert condition
     */
    private function evaluateAlertCondition($alert, $metricValue) {
        $conditionValue = $alert['condition_value'];
        $operator = $alert['operator'];
        
        switch ($operator) {
            case '>':
                return $metricValue > $conditionValue;
            case '<':
                return $metricValue < $conditionValue;
            case '>=':
                return $metricValue >= $conditionValue;
            case '<=':
                return $metricValue <= $conditionValue;
            case '=':
                return $metricValue == $conditionValue;
            case '!=':
                return $metricValue != $conditionValue;
            default:
                return false;
        }
    }

    /**
     * Trigger alert
     */
    private function triggerAlert($alert, $metricValue) {
        // Update last triggered timestamp
        $sql = "UPDATE metrics_alerts SET last_triggered = NOW() WHERE id = :alert_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':alert_id', $alert['id']);
        $stmt->execute();
        
        // Log alert trigger
        $this->logAlertTrigger($alert, $metricValue);
        
        // Send notification (implement based on your notification system)
        $this->sendAlertNotification($alert, $metricValue);
    }

    /**
     * Log alert trigger
     */
    private function logAlertTrigger($alert, $metricValue) {
        $sql = "INSERT INTO metrics_calculation_log 
                (metric_id, calculation_type, status, error_message) 
                VALUES (:metric_id, 'triggered', 'success', :message)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $alert['metric_id']);
        $stmt->bindValue(':message', "Alert triggered: {$alert['alert_name']} - Value: $metricValue");
        $stmt->execute();
    }

    /**
     * Send alert notification
     */
    private function sendAlertNotification($alert, $metricValue) {
        // Implementation depends on your notification system
        // This could be email, Slack, SMS, etc.
        
        $message = "HR Metrics Alert: {$alert['alert_name']}\n";
        $message .= "Metric: {$alert['metric_id']}\n";
        $message .= "Current Value: $metricValue\n";
        $message .= "Condition: {$alert['operator']} {$alert['condition_value']}\n";
        $message .= "Severity: {$alert['severity']}\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        
        // Log notification
        error_log("HR Metrics Alert: $message");
        
        // Here you would integrate with your notification system
        // Example: sendEmail(), sendSlackMessage(), etc.
    }

    /**
     * Clean old data
     */
    public function cleanOldData($daysToKeep = 365) {
        $cleaned = $this->metricsStorage->cleanOldMetricData($daysToKeep);
        
        // Clean calculation logs
        $sql = "DELETE FROM metrics_calculation_log 
                WHERE calculated_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $daysToKeep);
        $stmt->execute();
        
        // Clean export logs
        $sql = "DELETE FROM metrics_export_log 
                WHERE exported_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $daysToKeep);
        $stmt->execute();
        
        return $cleaned;
    }

    /**
     * Warm up cache with frequently accessed metrics
     */
    public function warmUpCache() {
        $frequentlyAccessedMetrics = [
            ['category' => 'employee_demographics', 'metric_name' => 'total_headcount', 'metric_id' => 'employee_demographics.total_headcount'],
            ['category' => 'recruitment', 'metric_name' => 'time_to_hire', 'metric_id' => 'recruitment.time_to_hire'],
            ['category' => 'payroll_compensation', 'metric_name' => 'total_payroll_cost', 'metric_id' => 'payroll_compensation.total_payroll_cost'],
            ['category' => 'attendance_leave', 'metric_name' => 'attendance_rate', 'metric_id' => 'attendance_leave.attendance_rate'],
            ['category' => 'benefits_hmo', 'metric_name' => 'hmo_utilization_rate', 'metric_id' => 'benefits_hmo.hmo_utilization_rate'],
            ['category' => 'training_development', 'metric_name' => 'training_participation_rate', 'metric_id' => 'training_development.training_participation_rate'],
            ['category' => 'employee_relations_engagement', 'metric_name' => 'engagement_index', 'metric_id' => 'employee_relations_engagement.engagement_index'],
            ['category' => 'turnover_retention', 'metric_name' => 'turnover_rate', 'metric_id' => 'turnover_retention.turnover_rate'],
            ['category' => 'compliance_audit', 'metric_name' => 'license_compliance_rate', 'metric_id' => 'compliance_audit.license_compliance_rate'],
            ['category' => 'executive_kpi', 'metric_name' => 'headcount_trend', 'metric_id' => 'executive_kpi.headcount_trend']
        ];
        
        $this->metricsStorage->warmUpCache($frequentlyAccessedMetrics);
    }

    /**
     * Get automation status
     */
    public function getAutomationStatus() {
        $sql = "SELECT 
                    COUNT(*) as total_calculations,
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_calculations,
                    COUNT(CASE WHEN status = 'error' THEN 1 END) as failed_calculations,
                    AVG(execution_time_ms) as avg_execution_time,
                    MAX(calculated_at) as last_calculation
                FROM metrics_calculation_log 
                WHERE calculated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add cache status
        $cacheStats = $this->metricsStorage->getPerformanceStats();
        
        return array_merge($status, $cacheStats);
    }

    /**
     * Log calculation error
     */
    private function logCalculationError($category, $metricName, $errorMessage) {
        $sql = "INSERT INTO metrics_calculation_log 
                (metric_id, calculation_type, status, error_message) 
                VALUES (:metric_id, 'scheduled', 'error', :error_message)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $category . '.' . $metricName);
        $stmt->bindValue(':error_message', $errorMessage);
        $stmt->execute();
    }

    /**
     * Log calculation summary
     */
    private function logCalculationSummary($results, $executionTime) {
        $successful = count(array_filter($results, fn($r) => $r['status'] === 'success'));
        $failed = count(array_filter($results, fn($r) => $r['status'] === 'error'));
        
        $sql = "INSERT INTO metrics_calculation_log 
                (metric_id, calculation_type, status, execution_time_ms, records_processed) 
                VALUES (:metric_id, 'scheduled', :status, :execution_time, :records_processed)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', 'batch_calculation');
        $stmt->bindValue(':status', $failed > 0 ? 'partial' : 'success');
        $stmt->bindValue(':execution_time', $executionTime);
        $stmt->bindValue(':records_processed', count($results));
        $stmt->execute();
    }

    /**
     * Schedule metric calculation
     */
    public function scheduleMetricCalculation($category, $metricName, $scheduleType = 'daily') {
        // Implementation for scheduling specific metrics
        // This could integrate with cron jobs or task schedulers
        
        $scheduleConfig = [
            'category' => $category,
            'metric_name' => $metricName,
            'schedule_type' => $scheduleType,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Store schedule configuration
        $sql = "INSERT INTO metrics_schedules 
                (category, metric_name, schedule_type, created_at) 
                VALUES (:category, :metric_name, :schedule_type, :created_at)
                ON DUPLICATE KEY UPDATE 
                schedule_type = VALUES(schedule_type),
                updated_at = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':metric_name', $metricName);
        $stmt->bindValue(':schedule_type', $scheduleType);
        $stmt->bindValue(':created_at', $scheduleConfig['created_at']);
        $stmt->execute();
        
        return $scheduleConfig;
    }

    /**
     * Get scheduled metrics
     */
    public function getScheduledMetrics() {
        $sql = "SELECT * FROM metrics_schedules ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Process scheduled metrics
     */
    public function processScheduledMetrics() {
        $scheduledMetrics = $this->getScheduledMetrics();
        $results = [];
        
        foreach ($scheduledMetrics as $schedule) {
            try {
                $result = $this->calculateAndStoreMetric(
                    $schedule['category'], 
                    $schedule['metric_name']
                );
                
                $results[] = [
                    'schedule_id' => $schedule['id'],
                    'category' => $schedule['category'],
                    'metric_name' => $schedule['metric_name'],
                    'status' => 'success',
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'schedule_id' => $schedule['id'],
                    'category' => $schedule['category'],
                    'metric_name' => $schedule['metric_name'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
?>
