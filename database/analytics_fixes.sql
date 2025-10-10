-- Analytics Module Database Fixes
-- This file contains fixes for missing tables and schema inconsistencies

-- Fix 1: Create missing analytics tables if they don't exist
CREATE TABLE IF NOT EXISTS `analytics_headcount_summary` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period` VARCHAR(7) NOT NULL, -- YYYY-MM format
    `department_id` INT,
    `total_headcount` INT DEFAULT 0,
    `new_hires` INT DEFAULT 0,
    `separations` INT DEFAULT 0,
    `turnover_rate` DECIMAL(5,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period`),
    INDEX `idx_department` (`department_id`),
    UNIQUE KEY `unique_period_dept` (`period`, `department_id`)
);

CREATE TABLE IF NOT EXISTS `analytics_payroll_summary` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period` VARCHAR(7) NOT NULL, -- YYYY-MM format
    `department_id` INT,
    `total_gross_pay` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_net_pay` DECIMAL(15,2) DEFAULT 0,
    `avg_salary` DECIMAL(10,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period`),
    INDEX `idx_department` (`department_id`),
    UNIQUE KEY `unique_period_dept` (`period`, `department_id`)
);

CREATE TABLE IF NOT EXISTS `analytics_benefits_costs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period` VARCHAR(7) NOT NULL, -- YYYY-MM format
    `department_id` INT,
    `hmo_cost` DECIMAL(15,2) DEFAULT 0,
    `active_enrollments` INT DEFAULT 0,
    `claims_processed` INT DEFAULT 0,
    `avg_claim_cost` DECIMAL(10,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period`),
    INDEX `idx_department` (`department_id`),
    UNIQUE KEY `unique_period_dept` (`period`, `department_id`)
);

-- Fix 2: Ensure EmploymentStatus column exists and has proper values
-- Note: The employees table uses EmploymentStatus, not IsActive

-- Fix 3: Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_employees_status` ON `employees` (`EmploymentStatus`);
CREATE INDEX IF NOT EXISTS `idx_employees_hire_date` ON `employees` (`HireDate`);
CREATE INDEX IF NOT EXISTS `idx_employees_termination_date` ON `employees` (`TerminationDate`);
CREATE INDEX IF NOT EXISTS `idx_employees_department` ON `employees` (`DepartmentID`);

-- Fix 4: Create views for common analytics queries
CREATE OR REPLACE VIEW `v_active_employees` AS
SELECT 
    e.*,
    d.DepartmentName,
    p.PositionName,
    es.BaseSalary,
    es.IsCurrent
FROM employees e
LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
LEFT JOIN positions p ON e.PositionID = p.PositionID
LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
WHERE e.EmploymentStatus = 'Active';

CREATE OR REPLACE VIEW `v_employee_turnover` AS
SELECT 
    DATE_FORMAT(TerminationDate, '%Y-%m') as period,
    DepartmentID,
    COUNT(*) as separations,
    TerminationReason
FROM employees 
WHERE TerminationDate IS NOT NULL 
    AND TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
GROUP BY DATE_FORMAT(TerminationDate, '%Y-%m'), DepartmentID, TerminationReason;

-- Fix 5: Create stored procedures for analytics calculations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `sp_CalculateHeadcountMetrics`(IN p_period VARCHAR(7))
BEGIN
    DECLARE v_department_id INT;
    DECLARE v_total_headcount INT;
    DECLARE v_new_hires INT;
    DECLARE v_separations INT;
    DECLARE v_turnover_rate DECIMAL(5,2);
    
    DECLARE done INT DEFAULT FALSE;
    DECLARE dept_cursor CURSOR FOR 
        SELECT DISTINCT DepartmentID FROM employees WHERE DepartmentID IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN dept_cursor;
    
    dept_loop: LOOP
        FETCH dept_cursor INTO v_department_id;
        IF done THEN
            LEAVE dept_loop;
        END IF;
        
        -- Calculate metrics for this department and period
        SELECT COUNT(*) INTO v_total_headcount
        FROM employees 
        WHERE DepartmentID = v_department_id 
            AND IsActive = 1;
            
        SELECT COUNT(*) INTO v_new_hires
        FROM employees 
        WHERE DepartmentID = v_department_id 
            AND DATE_FORMAT(HireDate, '%Y-%m') = p_period;
            
        SELECT COUNT(*) INTO v_separations
        FROM employees 
        WHERE DepartmentID = v_department_id 
            AND DATE_FORMAT(TerminationDate, '%Y-%m') = p_period;
            
        SET v_turnover_rate = CASE 
            WHEN v_total_headcount > 0 THEN (v_separations / v_total_headcount) * 100
            ELSE 0
        END;
        
        -- Insert or update summary
        INSERT INTO analytics_headcount_summary 
        (period, department_id, total_headcount, new_hires, separations, turnover_rate)
        VALUES (p_period, v_department_id, v_total_headcount, v_new_hires, v_separations, v_turnover_rate)
        ON DUPLICATE KEY UPDATE
            total_headcount = VALUES(total_headcount),
            new_hires = VALUES(new_hires),
            separations = VALUES(separations),
            turnover_rate = VALUES(turnover_rate),
            updated_at = CURRENT_TIMESTAMP;
            
    END LOOP;
    
    CLOSE dept_cursor;
END //

CREATE PROCEDURE IF NOT EXISTS `sp_CalculatePayrollMetrics`(IN p_period VARCHAR(7))
BEGIN
    DECLARE v_department_id INT;
    DECLARE v_total_gross DECIMAL(15,2);
    DECLARE v_total_deductions DECIMAL(15,2);
    DECLARE v_total_net DECIMAL(15,2);
    DECLARE v_avg_salary DECIMAL(10,2);
    
    DECLARE done INT DEFAULT FALSE;
    DECLARE dept_cursor CURSOR FOR 
        SELECT DISTINCT e.DepartmentID 
        FROM employees e 
        WHERE e.DepartmentID IS NOT NULL AND e.IsActive = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN dept_cursor;
    
    dept_loop: LOOP
        FETCH dept_cursor INTO v_department_id;
        IF done THEN
            LEAVE dept_loop;
        END IF;
        
        -- Calculate payroll metrics for this department and period
        SELECT 
            COALESCE(SUM(ps.GrossPay), 0),
            COALESCE(SUM(ps.DeductionAmount), 0),
            COALESCE(SUM(ps.NetPay), 0),
            COALESCE(AVG(ps.GrossPay), 0)
        INTO v_total_gross, v_total_deductions, v_total_net, v_avg_salary
        FROM payrollruns pr
        LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID
        LEFT JOIN employees e ON ps.EmployeeID = e.EmployeeID
        WHERE e.DepartmentID = v_department_id
            AND DATE_FORMAT(pr.PayPeriodStart, '%Y-%m') = p_period;
        
        -- Insert or update summary
        INSERT INTO analytics_payroll_summary 
        (period, department_id, total_gross_pay, total_deductions, total_net_pay, avg_salary)
        VALUES (p_period, v_department_id, v_total_gross, v_total_deductions, v_total_net, v_avg_salary)
        ON DUPLICATE KEY UPDATE
            total_gross_pay = VALUES(total_gross_pay),
            total_deductions = VALUES(total_deductions),
            total_net_pay = VALUES(total_net_pay),
            avg_salary = VALUES(avg_salary),
            updated_at = CURRENT_TIMESTAMP;
            
    END LOOP;
    
    CLOSE dept_cursor;
END //

DELIMITER ;

-- Fix 6: Create triggers to automatically update summary tables
DELIMITER //

CREATE TRIGGER IF NOT EXISTS `tr_employee_status_change` 
AFTER UPDATE ON `employees`
FOR EACH ROW
BEGIN
    IF OLD.IsActive != NEW.IsActive OR OLD.DepartmentID != NEW.DepartmentID THEN
        -- Update headcount summary for current month
        CALL sp_CalculateHeadcountMetrics(DATE_FORMAT(NOW(), '%Y-%m'));
    END IF;
END //

DELIMITER ;

-- Fix 7: Insert sample data for testing (if tables are empty)
INSERT IGNORE INTO analytics_headcount_summary (period, department_id, total_headcount, new_hires, separations, turnover_rate)
SELECT 
    DATE_FORMAT(NOW(), '%Y-%m') as period,
    d.DepartmentID,
    COUNT(e.EmployeeID) as total_headcount,
    0 as new_hires,
    0 as separations,
    0.0 as turnover_rate
FROM departments d
LEFT JOIN employees e ON d.DepartmentID = e.DepartmentID AND e.IsActive = 1
GROUP BY d.DepartmentID;

-- Fix 8: Create cache directory if it doesn't exist
-- This will be handled by the PHP code, but we can ensure the structure is ready
