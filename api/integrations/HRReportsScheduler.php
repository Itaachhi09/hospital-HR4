<?php
/**
 * HR Reports Scheduler
 * Handles scheduled report generation and email delivery
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/HRReportsIntegration.php';
require_once __DIR__ . '/HRReportsExportHandler.php';

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
     * Process scheduled reports
     */
    public function processScheduledReports() {
        try {
            // Get all active scheduled reports
            $scheduledReports = $this->getActiveScheduledReports();
            
            $processed = 0;
            $errors = [];
            
            foreach ($scheduledReports as $schedule) {
                try {
                    if ($this->shouldGenerateReport($schedule)) {
                        $this->generateAndSendReport($schedule);
                        $this->updateScheduleLastGenerated($schedule['schedule_id']);
                        $processed++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Schedule ID {$schedule['schedule_id']}: " . $e->getMessage();
                    error_log("Scheduled report error: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'message' => "Processed $processed scheduled reports"
            ];
            
        } catch (Exception $e) {
            error_log("Scheduled reports processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get active scheduled reports
     */
    private function getActiveScheduledReports() {
        $sql = "SELECT 
                    schedule_id,
                    report_type,
                    schedule_type,
                    email_recipients,
                    filters,
                    last_generated_at,
                    next_generation_at
                FROM scheduled_reports 
                WHERE is_active = 1 
                AND (next_generation_at IS NULL OR next_generation_at <= NOW())
                ORDER BY next_generation_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if report should be generated
     */
    private function shouldGenerateReport($schedule) {
        $scheduleType = $schedule['schedule_type'];
        $lastGenerated = $schedule['last_generated_at'];
        $nextGeneration = $schedule['next_generation_at'];
        
        // If next_generation_at is set and it's time
        if ($nextGeneration && strtotime($nextGeneration) <= time()) {
            return true;
        }
        
        // Check based on schedule type and last generated
        if (!$lastGenerated) {
            return true; // Never generated before
        }
        
        $lastGeneratedTime = strtotime($lastGenerated);
        $now = time();
        
        switch ($scheduleType) {
            case 'daily':
                return ($now - $lastGeneratedTime) >= 86400; // 24 hours
            case 'weekly':
                return ($now - $lastGeneratedTime) >= 604800; // 7 days
            case 'monthly':
                return ($now - $lastGeneratedTime) >= 2592000; // 30 days
            case 'quarterly':
                return ($now - $lastGeneratedTime) >= 7776000; // 90 days
            default:
                return false;
        }
    }

    /**
     * Generate and send report
     */
    private function generateAndSendReport($schedule) {
        $reportType = $schedule['report_type'];
        $filters = json_decode($schedule['filters'], true) ?? [];
        $emailRecipients = json_decode($schedule['email_recipients'], true) ?? [];
        
        // Generate report data
        $reportData = $this->reportsIntegration->exportReportData($reportType, 'CSV', $filters);
        
        // Create email content
        $emailContent = $this->createEmailContent($reportType, $reportData);
        
        // Send email to recipients
        if (!empty($emailRecipients)) {
            $this->sendEmailReport($emailRecipients, $reportType, $emailContent, $reportData);
        }
        
        // Log the generation
        $this->reportsIntegration->logReportGeneration(
            $reportType, 
            'SCHEDULED', 
            $filters, 
            'system'
        );
    }

    /**
     * Create email content for report
     */
    private function createEmailContent($reportType, $reportData) {
        $reportTitle = ucwords(str_replace('-', ' ', $reportType));
        $generatedAt = date('F j, Y \a\t g:i A');
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Scheduled HR Report - $reportTitle</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { background: #594423; color: white; padding: 20px; border-radius: 5px; }
                .header h1 { margin: 0; }
                .content { margin: 20px 0; }
                .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Scheduled HR Report - $reportTitle</h1>
                <p>Generated on: $generatedAt</p>
            </div>
            
            <div class='content'>
                <p>This is an automated report generated by the Hospital HR Management System.</p>
                
                <div class='summary'>
                    <h3>Report Summary</h3>
                    <p>The $reportTitle report has been generated successfully and is attached to this email.</p>
                </div>
                
                <div class='alert'>
                    <strong>Note:</strong> This is an automated email. Please do not reply to this message.
                    For questions about this report, please contact the HR Department.
                </div>
            </div>
            
            <div class='footer'>
                <p>Hospital HR Management System</p>
                <p>This report was generated automatically based on your scheduled report preferences.</p>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Send email with report attachment
     */
    private function sendEmailReport($recipients, $reportType, $emailContent, $reportData) {
        $subject = "Scheduled HR Report - " . ucwords(str_replace('-', ' ', $reportType));
        $fromEmail = "noreply@hospital-hr.com";
        $fromName = "Hospital HR Management System";
        
        // Create CSV attachment
        $csvContent = $reportData['csv_data'] ?? '';
        $filename = "hr_report_{$reportType}_" . date('Y-m-d') . ".csv";
        
        // For now, we'll use a simple mail function
        // In production, use PHPMailer or similar
        foreach ($recipients as $email) {
            $this->sendEmail($email, $subject, $emailContent, $csvContent, $filename, $fromEmail, $fromName);
        }
    }

    /**
     * Send individual email
     */
    private function sendEmail($to, $subject, $htmlContent, $csvContent, $filename, $fromEmail, $fromName) {
        // Generate boundary
        $boundary = md5(time());
        
        // Email headers
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        // Email body
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $htmlContent . "\r\n\r\n";
        
        // CSV attachment
        if ($csvContent) {
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: text/csv; name=\"$filename\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($csvContent)) . "\r\n";
        }
        
        $body .= "--$boundary--\r\n";
        
        // Send email
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Update schedule last generated timestamp
     */
    private function updateScheduleLastGenerated($scheduleId) {
        $sql = "UPDATE scheduled_reports 
                SET last_generated_at = NOW(),
                    next_generation_at = CASE 
                        WHEN schedule_type = 'daily' THEN DATE_ADD(NOW(), INTERVAL 1 DAY)
                        WHEN schedule_type = 'weekly' THEN DATE_ADD(NOW(), INTERVAL 1 WEEK)
                        WHEN schedule_type = 'monthly' THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        WHEN schedule_type = 'quarterly' THEN DATE_ADD(NOW(), INTERVAL 3 MONTH)
                        ELSE NULL
                    END
                WHERE schedule_id = :schedule_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':schedule_id', $scheduleId);
        
        return $stmt->execute();
    }

    /**
     * Create a new scheduled report
     */
    public function createScheduledReport($reportType, $scheduleType, $emailRecipients, $filters = [], $createdBy = 'system') {
        $sql = "INSERT INTO scheduled_reports 
                (report_type, schedule_type, email_recipients, filters, created_by, next_generation_at) 
                VALUES (:report_type, :schedule_type, :email_recipients, :filters, :created_by, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':report_type', $reportType);
        $stmt->bindValue(':schedule_type', $scheduleType);
        $stmt->bindValue(':email_recipients', json_encode($emailRecipients));
        $stmt->bindValue(':filters', json_encode($filters));
        $stmt->bindValue(':created_by', $createdBy);
        
        return $stmt->execute();
    }

    /**
     * Update scheduled report
     */
    public function updateScheduledReport($scheduleId, $updates) {
        $allowedFields = ['report_type', 'schedule_type', 'email_recipients', 'filters', 'is_active'];
        $setParts = [];
        $params = [':schedule_id' => $scheduleId];
        
        foreach ($updates as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $setParts[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE scheduled_reports SET " . implode(', ', $setParts) . " WHERE schedule_id = :schedule_id";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    /**
     * Delete scheduled report
     */
    public function deleteScheduledReport($scheduleId) {
        $sql = "UPDATE scheduled_reports SET is_active = 0 WHERE schedule_id = :schedule_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':schedule_id', $scheduleId);
        
        return $stmt->execute();
    }

    /**
     * Get scheduled reports for a user
     */
    public function getScheduledReports($userId = null) {
        $sql = "SELECT 
                    sr.schedule_id,
                    sr.report_type,
                    sr.schedule_type,
                    sr.email_recipients,
                    sr.filters,
                    sr.is_active,
                    sr.created_at,
                    sr.last_generated_at,
                    sr.next_generation_at,
                    u.Username as created_by_name
                FROM scheduled_reports sr
                LEFT JOIN users u ON sr.created_by = u.UserID
                WHERE 1=1";
        
        $params = [];
        
        if ($userId) {
            $sql .= " AND sr.created_by = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " ORDER BY sr.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Test email delivery
     */
    public function testEmailDelivery($email, $reportType = 'test') {
        $subject = "Test Email - HR Reports System";
        $htmlContent = "
        <html>
        <body>
            <h2>Test Email from HR Reports System</h2>
            <p>This is a test email to verify that the email delivery system is working correctly.</p>
            <p>If you received this email, the system is functioning properly.</p>
            <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $htmlContent, '', '', 'noreply@hospital-hr.com', 'HR Reports System');
    }

    /**
     * Get report generation statistics
     */
    public function getReportStatistics($days = 30) {
        $sql = "SELECT 
                    DATE(generated_at) as date,
                    report_type,
                    format,
                    COUNT(*) as count
                FROM report_generation_log 
                WHERE generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(generated_at), report_type, format
                ORDER BY date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
