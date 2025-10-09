<?php
/**
 * HR Reports Scheduler
 * Handles automated report generation and email delivery
 */

require_once __DIR__ . '/../config.php';

class HRReportsScheduler {
    private $pdo;
    private $reportsIntegration;
    private $exportHandler;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->reportsIntegration = new HRReportsIntegration();
        $this->exportHandler = new HRReportsExportHandler();
    }

    /**
     * Create a scheduled report
     */
    public function createScheduledReport($reportType, $scheduleType, $emailRecipients, $filters, $userId) {
        try {
            $sql = "INSERT INTO scheduled_reports 
                    (report_type, schedule_type, email_recipients, filters, created_by, next_run, is_active) 
                    VALUES (:report_type, :schedule_type, :email_recipients, :filters, :created_by, :next_run, 1)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':report_type', $reportType);
            $stmt->bindValue(':schedule_type', $scheduleType);
            $stmt->bindValue(':email_recipients', json_encode($emailRecipients));
            $stmt->bindValue(':filters', json_encode($filters));
            $stmt->bindValue(':created_by', $userId);
            $stmt->bindValue(':next_run', $this->calculateNextRun($scheduleType));
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Create scheduled report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process all scheduled reports
     */
    public function processScheduledReports() {
        try {
            $sql = "SELECT * FROM scheduled_reports 
                    WHERE is_active = 1 AND next_run <= NOW()";
            
            $stmt = $this->pdo->query($sql);
            $scheduledReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            foreach ($scheduledReports as $schedule) {
                $result = $this->processScheduledReport($schedule);
                $results[] = $result;
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Process scheduled reports error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process a single scheduled report
     */
    private function processScheduledReport($schedule) {
        try {
            $reportType = $schedule['report_type'];
            $filters = json_decode($schedule['filters'], true);
            $emailRecipients = json_decode($schedule['email_recipients'], true);
            
            // Generate report data using the export handler
            $exportResult = $this->exportHandler->exportReport($reportType, 'PDF', $filters);
            
            // Send email (placeholder - would integrate with email system)
            $emailSent = $this->sendReportEmail($emailRecipients, $exportResult, $reportType);
            
            // Update next run time
            $this->updateNextRun($schedule['id'], $schedule['schedule_type']);
            
            return [
                'schedule_id' => $schedule['id'],
                'report_type' => $reportType,
                'status' => 'success',
                'email_sent' => $emailSent,
                'processed_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Process scheduled report error: " . $e->getMessage());
            return [
                'schedule_id' => $schedule['id'],
                'report_type' => $schedule['report_type'],
                'status' => 'error',
                'error' => $e->getMessage(),
                'processed_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Send report email
     */
    private function sendReportEmail($recipients, $exportResult, $reportType) {
        // TODO: Implement actual email sending using PHPMailer or similar
        // For now, just log the action
        error_log("Email would be sent to: " . implode(', ', $recipients) . " for report: $reportType");
        return true;
    }

    /**
     * Test email delivery
     */
    public function testEmailDelivery($email) {
        // TODO: Implement actual email test
        // For now, just validate email format
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Calculate next run time
     */
    private function calculateNextRun($scheduleType) {
        $now = new DateTime();
        
        switch ($scheduleType) {
            case 'daily':
                $now->modify('+1 day');
                break;
            case 'weekly':
                $now->modify('+1 week');
                break;
            case 'monthly':
                $now->modify('+1 month');
                break;
            case 'quarterly':
                $now->modify('+3 months');
                break;
            default:
                $now->modify('+1 month');
        }

        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Update next run time for a schedule
     */
    private function updateNextRun($scheduleId, $scheduleType) {
        $sql = "UPDATE scheduled_reports 
                SET next_run = :next_run, last_run = NOW() 
                WHERE id = :schedule_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':schedule_id', $scheduleId);
        $stmt->bindValue(':next_run', $this->calculateNextRun($scheduleType));
        
        return $stmt->execute();
    }
}
?>