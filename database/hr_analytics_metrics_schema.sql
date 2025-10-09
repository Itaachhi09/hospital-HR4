-- HR Analytics Metrics Storage Database Schema
-- Pre-aggregated data storage for performance optimization

-- Create metrics_summary table for storing pre-calculated metrics
CREATE TABLE IF NOT EXISTS metrics_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    value JSON NOT NULL,
    period VARCHAR(20) NOT NULL,
    filters JSON,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_metric_id (metric_id),
    INDEX idx_category (category),
    INDEX idx_period (period),
    INDEX idx_last_updated (last_updated),
    UNIQUE KEY unique_metric_period (metric_id, period, filters(100))
);

-- Create metrics_definitions table for storing metric configurations
CREATE TABLE IF NOT EXISTS metrics_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    formula TEXT NOT NULL,
    sql_query TEXT NOT NULL,
    display_type VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_metric_name (metric_name),
    UNIQUE KEY unique_metric (category, metric_name)
);

-- Create metrics_calculation_log table for tracking calculation history
CREATE TABLE IF NOT EXISTS metrics_calculation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id VARCHAR(100) NOT NULL,
    calculation_type ENUM('manual', 'scheduled', 'triggered') NOT NULL,
    status ENUM('success', 'error', 'partial') NOT NULL,
    execution_time_ms INT,
    records_processed INT,
    error_message TEXT,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_id (metric_id),
    INDEX idx_calculated_at (calculated_at),
    INDEX idx_status (status)
);

-- Create metrics_alerts table for storing metric-based alerts
CREATE TABLE IF NOT EXISTS metrics_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id VARCHAR(100) NOT NULL,
    alert_name VARCHAR(100) NOT NULL,
    condition_type ENUM('threshold', 'trend', 'anomaly') NOT NULL,
    condition_value DECIMAL(10,4),
    operator ENUM('>', '<', '>=', '<=', '=', '!=') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_id (metric_id),
    INDEX idx_severity (severity),
    INDEX idx_is_active (is_active)
);

-- Create metrics_dashboard_config table for storing dashboard configurations
CREATE TABLE IF NOT EXISTS metrics_dashboard_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dashboard_name VARCHAR(100) NOT NULL,
    user_id INT,
    config JSON NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_dashboard_name (dashboard_name)
);

-- Create metrics_export_log table for tracking export activities
CREATE TABLE IF NOT EXISTS metrics_export_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    export_type ENUM('pdf', 'excel', 'csv', 'json') NOT NULL,
    metrics_requested JSON NOT NULL,
    filters JSON,
    file_path VARCHAR(500),
    file_size_bytes INT,
    export_status ENUM('success', 'error', 'processing') NOT NULL,
    exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_exported_at (exported_at),
    INDEX idx_export_status (export_status)
);

-- Insert default metric definitions
INSERT INTO metrics_definitions (category, metric_name, formula, sql_query, display_type, description) VALUES
('employee_demographics', 'total_headcount', 'COUNT(Active Employees)', 'SELECT COUNT(*) as value FROM employees WHERE IsActive = 1', 'KPI_CARD', 'Total active workforce size'),
('employee_demographics', 'average_age', 'AVG(CurrentDate - Birthdate)', 'SELECT AVG(TIMESTAMPDIFF(YEAR, DateOfBirth, CURDATE())) as value FROM employees WHERE IsActive = 1', 'LINE_CHART', 'Average workforce age'),
('recruitment', 'time_to_hire', 'AVG(DateHired - DatePosted)', 'SELECT AVG(DATEDIFF(DateHired, ApplicationDate)) as value FROM jobapplications WHERE Status = "Hired" AND DateHired IS NOT NULL', 'KPI_CARD', 'Average days from application to hire'),
('payroll_compensation', 'total_payroll_cost', 'SUM(Gross Pay)', 'SELECT SUM(ps.GrossPay) as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)', 'LINE_CHART', 'Monthly total payroll expenditure'),
('attendance_leave', 'attendance_rate', '(Days Present / Work Days) × 100', 'SELECT (SUM(CASE WHEN Status = "Present" THEN 1 ELSE 0 END) / COUNT(*)) * 100 as value FROM attendance WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)', 'LINE_CHART', 'Monthly attendance rate trend'),
('benefits_hmo', 'hmo_utilization_rate', '(Claims / Enrolled Employees) × 100', 'SELECT (COUNT(DISTINCT hc.EmployeeID) / COUNT(DISTINCT he.EmployeeID)) * 100 as value FROM hmoenrollments he LEFT JOIN hmoclaims hc ON he.EmployeeID = hc.EmployeeID WHERE he.IsActive = 1 AND hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'GAUGE', 'HMO utilization rate'),
('training_development', 'training_participation_rate', '(Attendees / Invited) × 100', 'SELECT (COUNT(CASE WHEN te.CompletionStatus = "Completed" THEN 1 END) / COUNT(*)) * 100 as value FROM trainingenrollments te WHERE te.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'DONUT_CHART', 'Training participation rate'),
('employee_relations_engagement', 'engagement_index', 'AVG(Survey Scores)', 'SELECT AVG(esr.Score) as value FROM engagementsurveyresponses esr WHERE esr.ResponseDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'GAUGE', 'Overall engagement score'),
('turnover_retention', 'turnover_rate', '(Exits / Avg. Headcount) × 100', 'SELECT (COUNT(e.EmployeeID) / AVG((SELECT COUNT(*) FROM employees WHERE IsActive = 1))) * 100 as value FROM employees e WHERE e.DateSeparated IS NOT NULL AND e.DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'LINE_CHART', 'Monthly turnover rate trend'),
('compliance_audit', 'license_compliance_rate', '(Valid Licenses / Required) × 100', 'SELECT (COUNT(CASE WHEN ed.ExpiryDate > CURDATE() THEN 1 END) / COUNT(*)) * 100 as value FROM employeedocuments ed WHERE ed.DocumentType = "License" AND ed.IsActive = 1', 'GAUGE', 'License compliance rate'),
('executive_kpi', 'headcount_trend', 'Monthly headcount', 'SELECT COUNT(*) as value FROM employees WHERE DateHired >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', 'LINE_CHART', 'Monthly headcount growth trend'),
('executive_kpi', 'engagement_score', 'Weighted average', 'SELECT AVG(esr.Score) as value FROM engagementsurveyresponses esr WHERE esr.ResponseDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)', 'GAUGE', 'Overall organizational engagement score');

-- Create indexes for better performance
CREATE INDEX idx_metrics_summary_composite ON metrics_summary (category, metric_name, period);
CREATE INDEX idx_metrics_calculation_log_composite ON metrics_calculation_log (metric_id, calculated_at);
CREATE INDEX idx_metrics_alerts_composite ON metrics_alerts (metric_id, is_active, severity);

-- Create views for common queries
CREATE VIEW v_metrics_summary_current AS
SELECT 
    ms.category,
    ms.metric_name,
    ms.value,
    ms.last_updated,
    md.formula,
    md.display_type,
    md.description
FROM metrics_summary ms
LEFT JOIN metrics_definitions md ON ms.category = md.category AND ms.metric_name = md.metric_name
WHERE ms.period = DATE_FORMAT(NOW(), '%Y-%m')
AND md.is_active = TRUE;

CREATE VIEW v_metrics_trends AS
SELECT 
    ms.category,
    ms.metric_name,
    ms.period,
    ms.value,
    ms.last_updated,
    md.display_type
FROM metrics_summary ms
LEFT JOIN metrics_definitions md ON ms.category = md.category AND ms.metric_name = md.metric_name
WHERE ms.period >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
AND md.is_active = TRUE
ORDER BY ms.category, ms.metric_name, ms.period;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE sp_CalculateMetric(IN p_category VARCHAR(50), IN p_metric_name VARCHAR(100), IN p_period VARCHAR(20))
BEGIN
    DECLARE v_sql_query TEXT;
    DECLARE v_result JSON;
    DECLARE v_metric_id VARCHAR(100);
    
    -- Get the SQL query for the metric
    SELECT sql_query INTO v_sql_query
    FROM metrics_definitions
    WHERE category = p_category AND metric_name = p_metric_name AND is_active = TRUE;
    
    -- Generate metric ID
    SET v_metric_id = CONCAT(p_category, '.', p_metric_name);
    
    -- Execute the query and store result
    -- Note: This is a simplified version - actual implementation would need dynamic SQL
    INSERT INTO metrics_summary (metric_id, category, metric_name, value, period, last_updated)
    VALUES (v_metric_id, p_category, p_metric_name, '{}', p_period, NOW())
    ON DUPLICATE KEY UPDATE 
        value = VALUES(value),
        last_updated = NOW();
END //

CREATE PROCEDURE sp_CleanOldMetrics(IN p_days_to_keep INT)
BEGIN
    DELETE FROM metrics_summary 
    WHERE last_updated < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    
    DELETE FROM metrics_calculation_log 
    WHERE calculated_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    
    DELETE FROM metrics_export_log 
    WHERE exported_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
END //

DELIMITER ;
