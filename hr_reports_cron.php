#!/usr/bin/env php
<?php
/**
 * HR Reports Cron Job
 * Processes scheduled reports automatically
 * 
 * Usage: php hr_reports_cron.php
 * 
 * This script should be run via cron job:
 * # Run every hour
 * 0 * * * * /usr/bin/php /path/to/hr_reports_cron.php
 */

// Set the working directory to the script's directory
chdir(__DIR__);

// Include the scheduler
require_once 'api/integrations/HRReportsScheduler.php';

// Initialize the scheduler
$scheduler = new HRReportsScheduler();

// Log the start of processing
echo "[" . date('Y-m-d H:i:s') . "] Starting scheduled reports processing...\n";

try {
    // Process scheduled reports
    $result = $scheduler->processScheduledReports();
    
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Successfully processed {$result['processed']} scheduled reports\n";
        
        if (!empty($result['errors'])) {
            echo "[" . date('Y-m-d H:i:s') . "] Errors encountered:\n";
            foreach ($result['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error processing scheduled reports: {$result['error']}\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Scheduled reports processing completed\n";
exit(0);
?>
