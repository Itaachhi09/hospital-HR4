<?php
/**
 * HR Reports Audit Trail Manager
 * Manages and provides audit trail functionality for HR reports
 */

require_once __DIR__ . '/../config.php';

class HRReportsAuditTrail {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Log report generation
     */
    public function logReportGeneration($reportType, $format, $filters, $userId, $additionalData = []) {
        $sql = "INSERT INTO report_generation_log 
                (report_type, format, filters, generated_by, generated_at, additional_data) 
                VALUES (:report_type, :format, :filters, :generated_by, NOW(), :additional_data)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':report_type', $reportType);
        $stmt->bindValue(':format', $format);
        $stmt->bindValue(':filters', json_encode($filters));
        $stmt->bindValue(':generated_by', $userId);
        $stmt->bindValue(':additional_data', json_encode($additionalData));
        
        return $stmt->execute();
    }

    /**
     * Get audit trail with filters
     */
    public function getAuditTrail($filters = []) {
        $sql = "SELECT 
                    rgl.log_id,
                    rgl.report_type,
                    rgl.format,
                    rgl.filters,
                    rgl.generated_by,
                    rgl.generated_at,
                    rgl.additional_data,
                    u.Username as generated_by_name,
                    u.FirstName,
                    u.LastName,
                    r.RoleName as user_role
                FROM report_generation_log rgl
                LEFT JOIN users u ON rgl.generated_by = u.UserID
                LEFT JOIN roles r ON u.RoleID = r.RoleID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['report_type'])) {
            $sql .= " AND rgl.report_type = :report_type";
            $params[':report_type'] = $filters['report_type'];
        }
        
        if (!empty($filters['format'])) {
            $sql .= " AND rgl.format = :format";
            $params[':format'] = $filters['format'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND rgl.generated_by = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND rgl.generated_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND rgl.generated_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY rgl.generated_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit trail statistics
     */
    public function getAuditStatistics($days = 30) {
        $sql = "SELECT 
                    DATE(rgl.generated_at) as date,
                    rgl.report_type,
                    rgl.format,
                    COUNT(*) as count,
                    COUNT(DISTINCT rgl.generated_by) as unique_users
                FROM report_generation_log rgl
                WHERE rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(rgl.generated_at), rgl.report_type, rgl.format
                ORDER BY date DESC, count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($userId, $days = 30) {
        $sql = "SELECT 
                    rgl.report_type,
                    rgl.format,
                    COUNT(*) as count,
                    MAX(rgl.generated_at) as last_generated,
                    MIN(rgl.generated_at) as first_generated
                FROM report_generation_log rgl
                WHERE rgl.generated_by = :user_id
                AND rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY rgl.report_type, rgl.format
                ORDER BY count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get most accessed reports
     */
    public function getMostAccessedReports($days = 30, $limit = 10) {
        $sql = "SELECT 
                    rgl.report_type,
                    COUNT(*) as access_count,
                    COUNT(DISTINCT rgl.generated_by) as unique_users,
                    MAX(rgl.generated_at) as last_accessed
                FROM report_generation_log rgl
                WHERE rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY rgl.report_type
                ORDER BY access_count DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days);
        $stmt->bindValue(':limit', $limit);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get export format usage
     */
    public function getExportFormatUsage($days = 30) {
        $sql = "SELECT 
                    rgl.format,
                    COUNT(*) as usage_count,
                    COUNT(DISTINCT rgl.report_type) as report_types,
                    COUNT(DISTINCT rgl.generated_by) as unique_users
                FROM report_generation_log rgl
                WHERE rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY rgl.format
                ORDER BY usage_count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit trail for specific report
     */
    public function getReportAuditTrail($reportType, $days = 30) {
        $sql = "SELECT 
                    rgl.log_id,
                    rgl.format,
                    rgl.filters,
                    rgl.generated_by,
                    rgl.generated_at,
                    u.Username as generated_by_name,
                    r.RoleName as user_role
                FROM report_generation_log rgl
                LEFT JOIN users u ON rgl.generated_by = u.UserID
                LEFT JOIN roles r ON u.RoleID = r.RoleID
                WHERE rgl.report_type = :report_type
                AND rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                ORDER BY rgl.generated_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':report_type', $reportType);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit trail summary
     */
    public function getAuditSummary($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_generations,
                    COUNT(DISTINCT rgl.generated_by) as unique_users,
                    COUNT(DISTINCT rgl.report_type) as unique_reports,
                    COUNT(DISTINCT rgl.format) as unique_formats,
                    AVG(DATEDIFF(NOW(), rgl.generated_at)) as avg_days_since_generation
                FROM report_generation_log rgl
                WHERE rgl.generated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Clean old audit trail entries
     */
    public function cleanOldAuditTrail($daysToKeep = 365) {
        $sql = "DELETE FROM report_generation_log 
                WHERE generated_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $daysToKeep);
        
        return $stmt->execute();
    }

    /**
     * Export audit trail
     */
    public function exportAuditTrail($filters = [], $format = 'CSV') {
        $auditData = $this->getAuditTrail($filters);
        
        switch ($format) {
            case 'CSV':
                return $this->exportAuditTrailAsCSV($auditData);
            case 'JSON':
                return $auditData;
            default:
                throw new Exception('Unsupported export format');
        }
    }

    /**
     * Export audit trail as CSV
     */
    private function exportAuditTrailAsCSV($auditData) {
        $csv = "Log ID,Report Type,Format,Generated By,User Role,Generated At,Filters\n";
        
        foreach ($auditData as $row) {
            $csv .= implode(',', [
                $row['log_id'],
                $row['report_type'],
                $row['format'],
                '"' . str_replace('"', '""', $row['generated_by_name']) . '"',
                '"' . str_replace('"', '""', $row['user_role']) . '"',
                $row['generated_at'],
                '"' . str_replace('"', '""', $row['filters']) . '"'
            ]) . "\n";
        }
        
        return $csv;
    }

    /**
     * Get audit trail dashboard data
     */
    public function getAuditDashboardData($days = 30) {
        return [
            'summary' => $this->getAuditSummary($days),
            'statistics' => $this->getAuditStatistics($days),
            'most_accessed' => $this->getMostAccessedReports($days),
            'format_usage' => $this->getExportFormatUsage($days),
            'recent_activity' => $this->getAuditTrail(['limit' => 10])
        ];
    }

    /**
     * Check if user has audit trail access
     */
    public function hasAuditAccess($userId) {
        $sql = "SELECT r.RoleName 
                FROM users u
                LEFT JOIN roles r ON u.RoleID = r.RoleID
                WHERE u.UserID = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = $result['RoleName'] ?? 'Guest';
        
        $allowedRoles = ['System Admin', 'HR Manager'];
        return in_array($role, $allowedRoles);
    }
}
?>
