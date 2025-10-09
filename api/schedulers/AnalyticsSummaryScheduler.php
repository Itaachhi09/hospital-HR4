<?php
/**
 * Analytics Summary Tables Refresh Scheduler
 * Handles automated refresh of analytics summary tables
 */

require_once __DIR__ . '/../config.php';

class AnalyticsSummaryScheduler {
    private $pdo;
    private $logFile;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->logFile = __DIR__ . '/../logs/analytics_scheduler.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Refresh all analytics summary tables
     */
    public function refreshAllSummaries($reportDate = null) {
        if (!$reportDate) {
            $reportDate = date('Y-m-d');
        }

        $this->log("Starting analytics summary refresh for date: $reportDate");

        try {
            // Refresh headcount summary
            $this->refreshHeadcountSummary($reportDate);
            
            // Refresh payroll summary
            $this->refreshPayrollSummary($reportDate);
            
            // Refresh benefits costs summary
            $this->refreshBenefitsCostsSummary($reportDate);
            
            // Refresh attendance summary
            $this->refreshAttendanceSummary($reportDate);
            
            // Refresh training summary
            $this->refreshTrainingSummary($reportDate);

            $this->log("Successfully refreshed all analytics summaries for date: $reportDate");
            return true;

        } catch (Exception $e) {
            $this->log("Error refreshing analytics summaries: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh headcount summary using stored procedure
     */
    private function refreshHeadcountSummary($reportDate) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_RefreshHeadcountSummary(?)");
            $stmt->execute([$reportDate]);
            $this->log("Headcount summary refreshed for date: $reportDate");
        } catch (Exception $e) {
            $this->log("Error refreshing headcount summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh payroll summary using stored procedure
     */
    private function refreshPayrollSummary($reportDate) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_RefreshPayrollSummary(?)");
            $stmt->execute([$reportDate]);
            $this->log("Payroll summary refreshed for date: $reportDate");
        } catch (Exception $e) {
            $this->log("Error refreshing payroll summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh benefits costs summary using stored procedure
     */
    private function refreshBenefitsCostsSummary($reportDate) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_RefreshBenefitsCostsSummary(?)");
            $stmt->execute([$reportDate]);
            $this->log("Benefits costs summary refreshed for date: $reportDate");
        } catch (Exception $e) {
            $this->log("Error refreshing benefits costs summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh attendance summary
     */
    private function refreshAttendanceSummary($reportDate) {
        try {
            // Delete existing data for the report date
            $stmt = $this->pdo->prepare("DELETE FROM analytics_attendance_summary WHERE ReportDate = ?");
            $stmt->execute([$reportDate]);

            // Insert new summary data
            $sql = "INSERT INTO analytics_attendance_summary (
                ReportDate, DepartmentID, BranchID,
                TotalWorkDays, TotalPresentDays, TotalAbsentDays, TotalLateDays, TotalOvertimeHours,
                AttendanceRate, AbsenteeismRate, PunctualityRate, EmployeeCount
            )
            SELECT 
                ? as ReportDate,
                e.DepartmentID,
                e.BranchID,
                COUNT(DISTINCT a.AttendanceDate) as TotalWorkDays,
                SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) as TotalPresentDays,
                SUM(CASE WHEN a.Status = 'Absent' THEN 1 ELSE 0 END) as TotalAbsentDays,
                SUM(CASE WHEN a.Status = 'Late' THEN 1 ELSE 0 END) as TotalLateDays,
                SUM(COALESCE(a.OvertimeHours, 0)) as TotalOvertimeHours,
                (SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as AttendanceRate,
                (SUM(CASE WHEN a.Status = 'Absent' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as AbsenteeismRate,
                (SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as PunctualityRate,
                COUNT(DISTINCT a.EmployeeID) as EmployeeCount
            FROM attendance a
            JOIN employees e ON a.EmployeeID = e.EmployeeID
            WHERE a.AttendanceDate >= DATE_SUB(?, INTERVAL 1 MONTH)
              AND a.AttendanceDate <= ?
            GROUP BY e.DepartmentID, e.BranchID";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reportDate, $reportDate, $reportDate]);
            
            $this->log("Attendance summary refreshed for date: $reportDate");
        } catch (Exception $e) {
            $this->log("Error refreshing attendance summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh training summary
     */
    private function refreshTrainingSummary($reportDate) {
        try {
            // Delete existing data for the report date
            $stmt = $this->pdo->prepare("DELETE FROM analytics_training_summary WHERE ReportDate = ?");
            $stmt->execute([$reportDate]);

            // Insert new summary data
            $sql = "INSERT INTO analytics_training_summary (
                ReportDate, DepartmentID, BranchID, TrainingType,
                TotalTrainingHours, CompletedSessions, TotalSessions,
                CompletionRate, ParticipantCount, AverageScore, TotalCost
            )
            SELECT 
                ? as ReportDate,
                e.DepartmentID,
                e.BranchID,
                t.TrainingType,
                SUM(t.DurationHours) as TotalTrainingHours,
                SUM(CASE WHEN tr.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedSessions,
                COUNT(tr.TrainingRecordID) as TotalSessions,
                (SUM(CASE WHEN tr.Status = 'Completed' THEN 1 ELSE 0 END) / COUNT(tr.TrainingRecordID)) * 100 as CompletionRate,
                COUNT(DISTINCT tr.EmployeeID) as ParticipantCount,
                AVG(tr.Score) as AverageScore,
                SUM(t.CostPerParticipant) as TotalCost
            FROM trainings t
            LEFT JOIN trainingrecords tr ON t.TrainingID = tr.TrainingID
            LEFT JOIN employees e ON tr.EmployeeID = e.EmployeeID
            WHERE t.TrainingDate >= DATE_SUB(?, INTERVAL 1 MONTH)
              AND t.TrainingDate <= ?
            GROUP BY e.DepartmentID, e.BranchID, t.TrainingType";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reportDate, $reportDate, $reportDate]);
            
            $this->log("Training summary refreshed for date: $reportDate");
        } catch (Exception $e) {
            $this->log("Error refreshing training summary: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get summary table statistics
     */
    public function getSummaryStats() {
        try {
            $stats = [];

            // Get headcount summary stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as count, MAX(ReportDate) as latest_date FROM analytics_headcount_summary");
            $stats['headcount'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get payroll summary stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as count, MAX(ReportDate) as latest_date FROM analytics_payroll_summary");
            $stats['payroll'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get benefits costs summary stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as count, MAX(ReportDate) as latest_date FROM analytics_benefits_costs");
            $stats['benefits'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get attendance summary stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as count, MAX(ReportDate) as latest_date FROM analytics_attendance_summary");
            $stats['attendance'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get training summary stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as count, MAX(ReportDate) as latest_date FROM analytics_training_summary");
            $stats['training'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            $this->log("Error getting summary stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old summary data
     */
    public function cleanupOldData($daysToKeep = 365) {
        try {
            $cutoffDate = date('Y-m-d', strtotime("-$daysToKeep days"));
            
            $tables = [
                'analytics_headcount_summary',
                'analytics_payroll_summary',
                'analytics_benefits_costs',
                'analytics_attendance_summary',
                'analytics_training_summary'
            ];

            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM $table WHERE ReportDate < ?");
                $stmt->execute([$cutoffDate]);
                $deletedRows = $stmt->rowCount();
                $this->log("Cleaned up $deletedRows rows from $table older than $cutoffDate");
            }

            return true;
        } catch (Exception $e) {
            $this->log("Error cleaning up old data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log messages to file
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get recent log entries
     */
    public function getRecentLogs($lines = 50) {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = file($this->logFile);
        return array_slice($logs, -$lines);
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $scheduler = new AnalyticsSummaryScheduler();
    
    $command = $argv[1] ?? 'refresh';
    $date = $argv[2] ?? null;
    
    switch ($command) {
        case 'refresh':
            $success = $scheduler->refreshAllSummaries($date);
            echo $success ? "Summary refresh completed successfully\n" : "Summary refresh failed\n";
            break;
            
        case 'stats':
            $stats = $scheduler->getSummaryStats();
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'cleanup':
            $days = $argv[2] ?? 365;
            $success = $scheduler->cleanupOldData($days);
            echo $success ? "Cleanup completed successfully\n" : "Cleanup failed\n";
            break;
            
        case 'logs':
            $lines = $argv[2] ?? 50;
            $logs = $scheduler->getRecentLogs($lines);
            echo implode('', $logs);
            break;
            
        default:
            echo "Usage: php AnalyticsSummaryScheduler.php [refresh|stats|cleanup|logs] [date|days|lines]\n";
            break;
    }
}
?>
