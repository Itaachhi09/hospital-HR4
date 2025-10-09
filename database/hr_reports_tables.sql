-- Create table for HR Reports audit trail
CREATE TABLE IF NOT EXISTS report_generation_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    format VARCHAR(20) NOT NULL,
    filters JSON,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_at (generated_at),
    INDEX idx_generated_by (generated_by)
);

-- Create table for scheduled reports
CREATE TABLE IF NOT EXISTS scheduled_reports (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    email_recipients JSON,
    filters JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_generated_at TIMESTAMP NULL,
    next_generation_at TIMESTAMP NULL,
    INDEX idx_schedule_type (schedule_type),
    INDEX idx_is_active (is_active),
    INDEX idx_next_generation (next_generation_at)
);
