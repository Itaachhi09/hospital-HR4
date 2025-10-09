-- HMO Integration Fixes
-- Adds foreign keys, missing tables, and integration support
-- Date: 2025-10-08

-- =====================================================
-- 1. Add Foreign Key Constraints for Data Integrity
-- =====================================================

-- Check and add foreign keys to employeehmoenrollments
SET @fk_enrollment_employee = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'employeehmoenrollments' 
    AND CONSTRAINT_NAME = 'fk_enrollment_employee'
);

SET @sql_fk_employee = IF(@fk_enrollment_employee = 0,
    'ALTER TABLE employeehmoenrollments 
     ADD CONSTRAINT fk_enrollment_employee FOREIGN KEY (EmployeeID) 
     REFERENCES employees(EmployeeID) ON DELETE CASCADE',
    'SELECT "FK fk_enrollment_employee already exists" as Status'
);
PREPARE stmt FROM @sql_fk_employee;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_enrollment_plan = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'employeehmoenrollments' 
    AND CONSTRAINT_NAME = 'fk_enrollment_plan'
);

SET @sql_fk_plan = IF(@fk_enrollment_plan = 0,
    'ALTER TABLE employeehmoenrollments 
     ADD CONSTRAINT fk_enrollment_plan FOREIGN KEY (PlanID) 
     REFERENCES hmoplans(PlanID) ON DELETE RESTRICT',
    'SELECT "FK fk_enrollment_plan already exists" as Status'
);
PREPARE stmt FROM @sql_fk_plan;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys to hmoclaims
SET @fk_claim_enrollment = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'hmoclaims' 
    AND CONSTRAINT_NAME = 'fk_claim_enrollment'
);

SET @sql_fk_claim = IF(@fk_claim_enrollment = 0,
    'ALTER TABLE hmoclaims 
     ADD CONSTRAINT fk_claim_enrollment FOREIGN KEY (EnrollmentID) 
     REFERENCES employeehmoenrollments(EnrollmentID) ON DELETE CASCADE',
    'SELECT "FK fk_claim_enrollment already exists" as Status'
);
PREPARE stmt FROM @sql_fk_claim;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys to hmoplans
SET @fk_plan_provider = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'hmoplans' 
    AND CONSTRAINT_NAME = 'fk_plan_provider'
);

SET @sql_fk_provider = IF(@fk_plan_provider = 0,
    'ALTER TABLE hmoplans 
     ADD CONSTRAINT fk_plan_provider FOREIGN KEY (ProviderID) 
     REFERENCES hmoproviders(ProviderID) ON DELETE RESTRICT',
    'SELECT "FK fk_plan_provider already exists" as Status'
);
PREPARE stmt FROM @sql_fk_provider;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys to hmo_reimbursements (if table exists)
SET @table_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'hmo_reimbursements'
);

SET @fk_reimb_claim = IF(@table_exists > 0, (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'hmo_reimbursements' 
    AND CONSTRAINT_NAME = 'fk_reimb_claim'
), 0);

SET @sql_fk_reimb_claim = IF(@table_exists > 0 AND @fk_reimb_claim = 0,
    'ALTER TABLE hmo_reimbursements
     ADD CONSTRAINT fk_reimb_claim FOREIGN KEY (ClaimID) 
     REFERENCES hmoclaims(ClaimID) ON DELETE CASCADE',
    'SELECT "FK fk_reimb_claim skipped or exists" as Status'
);
PREPARE stmt FROM @sql_fk_reimb_claim;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_reimb_employee = IF(@table_exists > 0, (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'hmo_reimbursements' 
    AND CONSTRAINT_NAME = 'fk_reimb_employee'
), 0);

SET @sql_fk_reimb_employee = IF(@table_exists > 0 AND @fk_reimb_employee = 0,
    'ALTER TABLE hmo_reimbursements
     ADD CONSTRAINT fk_reimb_employee FOREIGN KEY (EmployeeID) 
     REFERENCES employees(EmployeeID) ON DELETE CASCADE',
    'SELECT "FK fk_reimb_employee skipped or exists" as Status'
);
PREPARE stmt FROM @sql_fk_reimb_employee;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 2. Create employee_compensation_benefits Table
-- =====================================================

CREATE TABLE IF NOT EXISTS employee_compensation_benefits (
    BenefitID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    BenefitType VARCHAR(50) NOT NULL COMMENT 'HMO, Allowance, Insurance, etc.',
    MonthlyValue DECIMAL(10,2) DEFAULT 0 COMMENT 'Monthly benefit value',
    AnnualValue DECIMAL(10,2) DEFAULT 0 COMMENT 'Annual benefit value',
    Description TEXT COMMENT 'Benefit details',
    IsActive TINYINT(1) DEFAULT 1,
    EffectiveDate DATE DEFAULT (CURRENT_DATE),
    EndDate DATE NULL,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_employee_benefit (EmployeeID, BenefitType, IsActive),
    KEY idx_employee (EmployeeID),
    KEY idx_benefit_type (BenefitType),
    KEY idx_active (IsActive),
    
    CONSTRAINT fk_comp_benefit_employee FOREIGN KEY (EmployeeID) 
        REFERENCES employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks employee non-salary compensation (HMO, allowances, etc.)';

-- =====================================================
-- 3. Create HMO-Payroll Integration Tracking
-- =====================================================

CREATE TABLE IF NOT EXISTS hmo_payroll_applications (
    ApplicationID INT AUTO_INCREMENT PRIMARY KEY,
    PayrollRunID INT NOT NULL COMMENT 'Reference to payroll run',
    ApplicationType ENUM('Deduction', 'Reimbursement') NOT NULL,
    EmployeeCount INT DEFAULT 0,
    TotalAmount DECIMAL(12,2) DEFAULT 0,
    AppliedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    AppliedBy INT NULL COMMENT 'UserID who triggered the application',
    Status ENUM('Pending', 'Applied', 'Verified', 'Failed') DEFAULT 'Applied',
    Notes TEXT,
    
    KEY idx_payroll_run (PayrollRunID),
    KEY idx_type (ApplicationType),
    KEY idx_status (Status),
    KEY idx_applied_at (AppliedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks when HMO deductions/reimbursements are applied to payroll';

-- =====================================================
-- 4. Add Integration Columns to Existing Tables
-- =====================================================

-- Add integration tracking to employeehmoenrollments
ALTER TABLE employeehmoenrollments 
    ADD COLUMN IF NOT EXISTS LastSyncedToPayroll TIMESTAMP NULL COMMENT 'Last sync to payroll system',
    ADD COLUMN IF NOT EXISTS LastSyncedToCompensation TIMESTAMP NULL COMMENT 'Last sync to compensation module',
    ADD COLUMN IF NOT EXISTS IntegrationStatus ENUM('Synced', 'Pending', 'Failed') DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS SyncNotes TEXT COMMENT 'Integration sync notes/errors';

-- Add integration tracking to hmoclaims
ALTER TABLE hmoclaims 
    ADD COLUMN IF NOT EXISTS ProcessedToPayroll TINYINT(1) DEFAULT 0 COMMENT 'If reimbursement added to payroll',
    ADD COLUMN IF NOT EXISTS PayrollRunID INT NULL COMMENT 'Payroll run where reimbursement was added',
    ADD COLUMN IF NOT EXISTS ProcessedToAnalytics TINYINT(1) DEFAULT 0 COMMENT 'If pushed to analytics',
    ADD COLUMN IF NOT EXISTS AnalyticsSyncDate TIMESTAMP NULL;

-- Add HMO tracking to Deductions table
ALTER TABLE Deductions 
    ADD COLUMN IF NOT EXISTS HMOEnrollmentID INT NULL COMMENT 'Link to employeehmoenrollments',
    ADD COLUMN IF NOT EXISTS SourceType ENUM('Manual', 'HMO', 'Statutory', 'System') DEFAULT 'Manual',
    ADD KEY IF NOT EXISTS idx_hmo_enrollment (HMOEnrollmentID),
    ADD KEY IF NOT EXISTS idx_source_type (SourceType);

-- Add HMO tracking to Bonuses table (for reimbursements)
ALTER TABLE Bonuses 
    ADD COLUMN IF NOT EXISTS HMOClaimID INT NULL COMMENT 'Link to hmoclaims for reimbursements',
    ADD COLUMN IF NOT EXISTS SourceType ENUM('Manual', 'HMO_Reimbursement', 'Performance', 'System') DEFAULT 'Manual',
    ADD KEY IF NOT EXISTS idx_hmo_claim (HMOClaimID),
    ADD KEY IF NOT EXISTS idx_bonus_source (SourceType);

-- =====================================================
-- 5. Create Integration Audit Log
-- =====================================================

CREATE TABLE IF NOT EXISTS hmo_integration_audit (
    AuditID BIGINT AUTO_INCREMENT PRIMARY KEY,
    IntegrationType ENUM('Payroll', 'Compensation', 'Analytics', 'Finance') NOT NULL,
    ActionType ENUM('Sync', 'Push', 'Pull', 'Update') NOT NULL,
    EntityType ENUM('Enrollment', 'Claim', 'Reimbursement', 'Deduction') NOT NULL,
    EntityID INT NOT NULL,
    TargetSystem VARCHAR(50) NOT NULL COMMENT 'System being integrated with',
    Status ENUM('Success', 'Failed', 'Partial') NOT NULL,
    RecordsAffected INT DEFAULT 0,
    ErrorMessage TEXT NULL,
    RequestData JSON NULL COMMENT 'Integration request payload',
    ResponseData JSON NULL COMMENT 'Integration response',
    ExecutedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ExecutedBy INT NULL COMMENT 'UserID who triggered',
    ExecutionTimeMs INT NULL COMMENT 'Execution time in milliseconds',
    
    KEY idx_integration_type (IntegrationType),
    KEY idx_entity (EntityType, EntityID),
    KEY idx_status (Status),
    KEY idx_executed_at (ExecutedAt),
    KEY idx_target_system (TargetSystem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for all HMO integration activities';

-- =====================================================
-- 6. Create Views for Integration Reporting
-- =====================================================

-- View: Active HMO Deductions for Current Period
CREATE OR REPLACE VIEW v_hmo_active_deductions AS
SELECT 
    e.EnrollmentID,
    e.EmployeeID,
    emp.EmployeeNumber,
    emp.FirstName,
    emp.LastName,
    emp.DepartmentID,
    p.PlanID,
    p.PlanName,
    pr.ProviderName,
    e.MonthlyDeduction as EmployeeShare,
    e.MonthlyContribution as EmployerShare,
    (e.MonthlyDeduction + e.MonthlyContribution) as TotalMonthlyCost,
    e.EffectiveDate,
    e.EndDate,
    e.Status,
    e.LastSyncedToPayroll,
    e.IntegrationStatus
FROM employeehmoenrollments e
JOIN employees emp ON e.EmployeeID = emp.EmployeeID
JOIN hmoplans p ON e.PlanID = p.PlanID
JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
WHERE e.Status = 'Active';

-- View: Pending HMO Reimbursements
CREATE OR REPLACE VIEW v_hmo_pending_reimbursements AS
SELECT 
    r.ReimbursementID,
    r.ClaimID,
    r.EmployeeID,
    emp.EmployeeNumber,
    emp.FirstName,
    emp.LastName,
    c.ClaimNumber,
    c.ClaimType,
    r.Amount,
    r.Status,
    c.ApprovedDate,
    r.ProcessedDate,
    r.PayrollRunID,
    c.ProcessedToPayroll,
    DATEDIFF(CURRENT_DATE, c.ApprovedDate) as DaysSinceApproval
FROM hmo_reimbursements r
JOIN employees emp ON r.EmployeeID = emp.EmployeeID
JOIN hmoclaims c ON r.ClaimID = c.ClaimID
WHERE r.Status = 'Pending' AND c.Status = 'Approved'
ORDER BY c.ApprovedDate ASC;

-- View: Employee Total Compensation (Salary + Benefits)
CREATE OR REPLACE VIEW v_employee_total_compensation AS
SELECT 
    e.EmployeeID,
    e.EmployeeNumber,
    e.FirstName,
    e.LastName,
    e.DepartmentID,
    COALESCE(es.BaseSalary, 0) as BaseSalary,
    COALESCE(es.PayFrequency, 'Monthly') as PayFrequency,
    COALESCE(SUM(CASE WHEN cb.BenefitType = 'HMO' AND cb.IsActive = 1 THEN cb.MonthlyValue ELSE 0 END), 0) as HMOValue,
    COALESCE(SUM(CASE WHEN cb.BenefitType != 'HMO' AND cb.IsActive = 1 THEN cb.MonthlyValue ELSE 0 END), 0) as OtherBenefitsValue,
    COALESCE(SUM(CASE WHEN cb.IsActive = 1 THEN cb.MonthlyValue ELSE 0 END), 0) as TotalBenefitsValue,
    (COALESCE(es.BaseSalary, 0) + COALESCE(SUM(CASE WHEN cb.IsActive = 1 THEN cb.MonthlyValue ELSE 0 END), 0)) as TotalMonthlyCompensation,
    (COALESCE(es.BaseSalary, 0) + COALESCE(SUM(CASE WHEN cb.IsActive = 1 THEN cb.MonthlyValue ELSE 0 END), 0)) * 12 as TotalAnnualCompensation
FROM employees e
LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
LEFT JOIN employee_compensation_benefits cb ON e.EmployeeID = cb.EmployeeID
GROUP BY e.EmployeeID, e.EmployeeNumber, e.FirstName, e.LastName, e.DepartmentID, es.BaseSalary, es.PayFrequency;

-- =====================================================
-- 7. Create Stored Procedures for Integration
-- =====================================================

DELIMITER $$

-- Procedure: Sync HMO to Compensation
DROP PROCEDURE IF EXISTS sp_sync_hmo_to_compensation$$
CREATE PROCEDURE sp_sync_hmo_to_compensation(
    IN p_employee_id INT
)
BEGIN
    DECLARE v_hmo_value DECIMAL(10,2);
    DECLARE v_annual_value DECIMAL(10,2);
    
    -- Calculate total HMO value for employee
    SELECT COALESCE(SUM(p.MonthlyPremium), 0)
    INTO v_hmo_value
    FROM employeehmoenrollments e
    JOIN hmoplans p ON e.PlanID = p.PlanID
    WHERE e.EmployeeID = p_employee_id AND e.Status = 'Active';
    
    SET v_annual_value = v_hmo_value * 12;
    
    -- Insert or update compensation benefits
    INSERT INTO employee_compensation_benefits (
        EmployeeID, BenefitType, MonthlyValue, AnnualValue, IsActive, UpdatedAt
    ) VALUES (
        p_employee_id, 'HMO', v_hmo_value, v_annual_value, 1, NOW()
    ) ON DUPLICATE KEY UPDATE
        MonthlyValue = v_hmo_value,
        AnnualValue = v_annual_value,
        IsActive = 1,
        UpdatedAt = NOW();
    
    -- Update enrollment sync status
    UPDATE employeehmoenrollments
    SET LastSyncedToCompensation = NOW(),
        IntegrationStatus = 'Synced'
    WHERE EmployeeID = p_employee_id AND Status = 'Active';
    
    -- Log audit
    INSERT INTO hmo_integration_audit (
        IntegrationType, ActionType, EntityType, EntityID, 
        TargetSystem, Status, RecordsAffected
    ) VALUES (
        'Compensation', 'Sync', 'Enrollment', p_employee_id,
        'employee_compensation_benefits', 'Success', 1
    );
END$$

-- Procedure: Get HMO Deductions for Payroll
DROP PROCEDURE IF EXISTS sp_get_hmo_deductions_for_payroll$$
CREATE PROCEDURE sp_get_hmo_deductions_for_payroll(
    IN p_month INT,
    IN p_year INT
)
BEGIN
    SELECT 
        e.EnrollmentID,
        e.EmployeeID,
        emp.EmployeeNumber,
        emp.FirstName,
        emp.LastName,
        p.PlanName,
        pr.ProviderName,
        COALESCE(e.MonthlyDeduction, 0) as employee_share,
        COALESCE(e.MonthlyContribution, 0) as employer_share,
        COALESCE(e.MonthlyDeduction, 0) as deduction_amount,
        CONCAT('HMO Premium: ', p.PlanName, ' (', pr.ProviderName, ')') as description
    FROM employeehmoenrollments e
    JOIN employees emp ON e.EmployeeID = emp.EmployeeID
    JOIN hmoplans p ON e.PlanID = p.PlanID
    JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
    WHERE e.Status = 'Active'
        AND e.EffectiveDate <= LAST_DAY(CONCAT(p_year, '-', LPAD(p_month, 2, '0'), '-01'))
        AND (e.EndDate IS NULL OR e.EndDate >= CONCAT(p_year, '-', LPAD(p_month, 2, '0'), '-01'))
    ORDER BY emp.LastName, emp.FirstName;
END$$

DELIMITER ;

-- =====================================================
-- 8. Insert Initial Sample Data
-- =====================================================

-- Sample: Sync existing enrollments to compensation
INSERT IGNORE INTO employee_compensation_benefits (EmployeeID, BenefitType, MonthlyValue, AnnualValue, IsActive)
SELECT 
    e.EmployeeID,
    'HMO' as BenefitType,
    COALESCE(p.MonthlyPremium, 0) as MonthlyValue,
    COALESCE(p.MonthlyPremium, 0) * 12 as AnnualValue,
    1 as IsActive
FROM employeehmoenrollments e
JOIN hmoplans p ON e.PlanID = p.PlanID
WHERE e.Status = 'Active';

-- =====================================================
-- 9. Create Indexes for Performance
-- =====================================================

-- Indexes for employee_compensation_benefits
CREATE INDEX IF NOT EXISTS idx_comp_benefit_active ON employee_compensation_benefits(IsActive, EmployeeID);
CREATE INDEX IF NOT EXISTS idx_comp_benefit_dates ON employee_compensation_benefits(EffectiveDate, EndDate);

-- Indexes for integration tracking
CREATE INDEX IF NOT EXISTS idx_enrollment_sync_status ON employeehmoenrollments(IntegrationStatus, LastSyncedToPayroll);
CREATE INDEX IF NOT EXISTS idx_claim_payroll ON hmoclaims(ProcessedToPayroll, PayrollRunID);

-- =====================================================
-- END OF INTEGRATION FIXES
-- =====================================================

SELECT 'HMO Integration Fixes Applied Successfully!' as Status;
SELECT COUNT(*) as 'Active Enrollments' FROM employeehmoenrollments WHERE Status = 'Active';
SELECT COUNT(*) as 'Compensation Benefits Synced' FROM employee_compensation_benefits WHERE BenefitType = 'HMO';

