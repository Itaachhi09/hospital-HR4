<?php
/**
 * HR Analytics Metrics Cron Job
 * Automated processing of HR metrics calculations and alerts
 */

// Ensure this script is not accessed directly via web
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Define BASE_DIR if not already defined
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

require_once BASE_DIR . '/api/config.php';
require_once BASE_DIR . '/api/integrations/HRAnalyticsMetricsAutomation.php';
require_once BASE_DIR . '/api/utils/Response.php';

// Initialize database connection
global $pdo;

try {
    $automation = new HRAnalyticsMetricsAutomation();
    
    echo "HR Analytics Metrics Automation started at " . date('Y-m-d H:i:s') . "\n";
    echo "==========================================\n\n";
    
    // Process scheduled calculations
    echo "Processing scheduled metric calculations...\n";
    $calculationResults = $automation->processScheduledCalculations();
    
    if ($calculationResults['status'] === 'completed') {
        echo "✓ Calculations completed successfully\n";
        echo "  - Total metrics: {$calculationResults['total_metrics']}\n";
        echo "  - Successful: {$calculationResults['successful']}\n";
        echo "  - Failed: {$calculationResults['failed']}\n";
        echo "  - Execution time: {$calculationResults['execution_time_ms']}ms\n";
        
        if ($calculationResults['failed'] > 0) {
            echo "  - Failed metrics:\n";
            foreach ($calculationResults['results'] as $result) {
                if ($result['status'] === 'error') {
                    echo "    * {$result['category']}.{$result['metric_name']}: {$result['error']}\n";
                }
            }
        }
    } else {
        echo "✗ Calculations failed: {$calculationResults['error']}\n";
    }
    
    echo "\n";
    
    // Process metric alerts
    echo "Processing metric alerts...\n";
    $alertResults = $automation->processMetricAlerts();
    
    if (count($alertResults) > 0) {
        echo "✓ " . count($alertResults) . " alerts triggered:\n";
        foreach ($alertResults as $alert) {
            echo "  - {$alert['alert_name']} ({$alert['severity']}): {$alert['metric_id']}\n";
        }
    } else {
        echo "✓ No alerts triggered\n";
    }
    
    echo "\n";
    
    // Warm up cache
    echo "Warming up cache...\n";
    $automation->warmUpCache();
    echo "✓ Cache warmed up successfully\n";
    
    echo "\n";
    
    // Get automation status
    echo "Automation Status:\n";
    $status = $automation->getAutomationStatus();
    echo "  - Total calculations (24h): {$status['total_calculations']}\n";
    echo "  - Successful: {$status['successful_calculations']}\n";
    echo "  - Failed: {$status['failed_calculations']}\n";
    echo "  - Average execution time: " . round($status['avg_execution_time'], 2) . "ms\n";
    echo "  - Last calculation: {$status['last_calculation']}\n";
    echo "  - Cache metrics: {$status['total_metrics']}\n";
    echo "  - Cache categories: {$status['categories']}\n";
    
    echo "\n";
    
    // Clean old data (weekly)
    if (date('N') == 1) { // Monday
        echo "Cleaning old data (weekly maintenance)...\n";
        $cleaned = $automation->cleanOldData(365); // Keep 1 year
        echo "✓ Old data cleaned successfully\n";
    }
    
    echo "\n";
    echo "HR Analytics Metrics Automation completed at " . date('Y-m-d H:i:s') . "\n";
    echo "==========================================\n";
    
    // Log success
    error_log("HR Metrics Cron: Successfully processed " . $calculationResults['total_metrics'] . " metrics, " . count($alertResults) . " alerts triggered.");
    
} catch (Exception $e) {
    echo "Error running HR Analytics Metrics Automation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log error
    error_log("HR Metrics Cron Error: " . $e->getMessage());
    error_log("HR Metrics Cron Stack Trace: " . $e->getTraceAsString());
    
    exit(1); // Indicate failure
}

exit(0); // Indicate success
