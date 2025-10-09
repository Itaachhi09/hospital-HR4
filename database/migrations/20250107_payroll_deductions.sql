-- Migration: Add deductions table for Payroll V2 system
-- Date: 2025-01-07
-- Description: Creates deductions table to support statutory and voluntary deductions

-- Create deductions table
CREATE TABLE IF NOT EXISTS `deductions` (
  `DeductionID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `DeductionType` varchar(50) NOT NULL,
  `DeductionName` varchar(100) NOT NULL,
  `Amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Percentage` decimal(5,2) DEFAULT NULL,
  `BaseAmount` decimal(12,2) DEFAULT NULL,
  `ComputationMethod` enum('Fixed','Percentage','Formula') NOT NULL DEFAULT 'Fixed',
  `IsStatutory` tinyint(1) NOT NULL DEFAULT 0,
  `IsVoluntary` tinyint(1) NOT NULL DEFAULT 0,
  `PayrollRunID` int(11) DEFAULT NULL,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive','Paid','Cancelled') NOT NULL DEFAULT 'Active',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`DeductionID`),
  KEY `idx_ded_emp` (`EmployeeID`),
  KEY `idx_ded_type` (`DeductionType`),
  KEY `idx_ded_payroll` (`PayrollRunID`),
  KEY `idx_ded_statutory` (`IsStatutory`),
  KEY `idx_ded_voluntary` (`IsVoluntary`),
  CONSTRAINT `fk_ded_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `fk_ded_payroll` FOREIGN KEY (`PayrollRunID`) REFERENCES `payroll_v2_runs` (`PayrollRunID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create payroll_bonuses table (if not exists)
CREATE TABLE IF NOT EXISTS `payroll_bonuses` (
  `BonusID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollRunID` int(11) DEFAULT NULL,
  `EmployeeID` int(11) NOT NULL,
  `BonusType` varchar(50) NOT NULL,
  `Amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `BonusDate` date NOT NULL,
  `Status` enum('Manual','Computed','Approved','Paid') NOT NULL DEFAULT 'Manual',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`BonusID`),
  KEY `idx_bonus_emp` (`EmployeeID`),
  KEY `idx_bonus_payroll` (`PayrollRunID`),
  KEY `idx_bonus_type` (`BonusType`),
  CONSTRAINT `fk_bonus_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `fk_bonus_payroll` FOREIGN KEY (`PayrollRunID`) REFERENCES `payroll_v2_runs` (`PayrollRunID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample statutory deduction rates for existing branches
INSERT IGNORE INTO `payroll_v2_branch_configs` (`BranchID`, `SSSRateEmployee`, `PhilHealthRateEmployee`, `PagibigRateEmployee`, `TaxRateEmployee`, `CreatedAt`, `UpdatedAt`)
SELECT 
    hb.BranchID,
    0.045,  -- 4.5% SSS
    0.020,  -- 2.0% PhilHealth  
    0.010,  -- 1.0% Pag-IBIG
    0.000,  -- Variable tax rate
    NOW(),
    NOW()
FROM `hospital_branches` hb
WHERE NOT EXISTS (
    SELECT 1 FROM `payroll_v2_branch_configs` pbc 
    WHERE pbc.BranchID = hb.BranchID
);

-- Insert sample voluntary deductions for testing
INSERT IGNORE INTO `deductions` (`EmployeeID`, `DeductionType`, `DeductionName`, `Amount`, `ComputationMethod`, `IsStatutory`, `IsVoluntary`, `EffectiveDate`, `Status`, `Notes`)
SELECT 
    e.EmployeeID,
    'HMO',
    'HMO Premium',
    500.00,
    'Fixed',
    0,
    1,
    CURDATE(),
    'Active',
    'Monthly HMO premium deduction'
FROM `employees` e
WHERE e.EmploymentStatus = 'Active'
AND NOT EXISTS (
    SELECT 1 FROM `deductions` d 
    WHERE d.EmployeeID = e.EmployeeID 
    AND d.DeductionType = 'HMO' 
    AND d.Status = 'Active'
)
LIMIT 5;

-- Insert sample loan deductions
INSERT IGNORE INTO `deductions` (`EmployeeID`, `DeductionType`, `DeductionName`, `Amount`, `ComputationMethod`, `IsStatutory`, `IsVoluntary`, `EffectiveDate`, `Status`, `Notes`)
SELECT 
    e.EmployeeID,
    'Loan',
    'Salary Loan Payment',
    1000.00,
    'Fixed',
    0,
    1,
    CURDATE(),
    'Active',
    'Monthly salary loan payment'
FROM `employees` e
WHERE e.EmploymentStatus = 'Active'
AND NOT EXISTS (
    SELECT 1 FROM `deductions` d 
    WHERE d.EmployeeID = e.EmployeeID 
    AND d.DeductionType = 'Loan' 
    AND d.Status = 'Active'
)
LIMIT 3;
