-- Complete Payroll V2 Schema Migration
-- This migration creates all necessary tables for the complete payroll system

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
  `Status` enum('Active','Inactive','Paid') NOT NULL DEFAULT 'Active',
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

-- Create payroll_bonuses table
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

-- Create salary_adjustments table (for HR3 integration)
CREATE TABLE IF NOT EXISTS `salary_adjustments` (
  `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `AdjustmentType` varchar(50) NOT NULL,
  `Amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `AdjustmentDate` date NOT NULL,
  `Reason` text DEFAULT NULL,
  `Status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`AdjustmentID`),
  KEY `idx_adj_emp` (`EmployeeID`),
  KEY `idx_adj_type` (`AdjustmentType`),
  KEY `idx_adj_date` (`AdjustmentDate`),
  CONSTRAINT `fk_adj_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create departments table if it doesn't exist
CREATE TABLE IF NOT EXISTS `departments` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `ManagerID` int(11) DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`DepartmentID`),
  KEY `idx_dept_manager` (`ManagerID`),
  CONSTRAINT `fk_dept_manager` FOREIGN KEY (`ManagerID`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create positions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `positions` (
  `PositionID` int(11) NOT NULL AUTO_INCREMENT,
  `PositionName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `Level` int(11) DEFAULT 1,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`PositionID`),
  KEY `idx_pos_dept` (`DepartmentID`),
  CONSTRAINT `fk_pos_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add missing columns to employees table if they don't exist
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `DepartmentID` int(11) DEFAULT NULL AFTER `Department`,
ADD COLUMN IF NOT EXISTS `PositionID` int(11) DEFAULT NULL AFTER `Position`,
ADD COLUMN IF NOT EXISTS `BranchID` int(11) DEFAULT NULL AFTER `Branch`,
ADD COLUMN IF NOT EXISTS `EmploymentStatus` enum('Active','Inactive','Terminated','On Leave') NOT NULL DEFAULT 'Active' AFTER `Status`;

-- Add foreign key constraints to employees table
ALTER TABLE `employees` 
ADD CONSTRAINT `fk_emp_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_emp_pos` FOREIGN KEY (`PositionID`) REFERENCES `positions` (`PositionID`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_emp_branch` FOREIGN KEY (`BranchID`) REFERENCES `hospital_branches` (`BranchID`) ON DELETE SET NULL;

-- Insert sample departments if they don't exist
INSERT IGNORE INTO `departments` (`DepartmentName`, `Description`) VALUES
('Human Resources', 'Human Resources Department'),
('Finance', 'Finance and Accounting Department'),
('Nursing', 'Nursing Department'),
('Medical', 'Medical Department'),
('Administration', 'Administration Department'),
('IT', 'Information Technology Department'),
('Maintenance', 'Maintenance Department'),
('Security', 'Security Department');

-- Insert sample positions if they don't exist
INSERT IGNORE INTO `positions` (`PositionName`, `Description`, `DepartmentID`, `Level`) VALUES
('HR Manager', 'Human Resources Manager', 1, 5),
('HR Staff', 'Human Resources Staff', 1, 3),
('Finance Manager', 'Finance Manager', 2, 5),
('Accountant', 'Accountant', 2, 4),
('Nurse', 'Registered Nurse', 3, 3),
('Head Nurse', 'Head Nurse', 3, 4),
('Doctor', 'Medical Doctor', 4, 5),
('Administrator', 'Hospital Administrator', 5, 6),
('IT Manager', 'IT Manager', 6, 5),
('IT Staff', 'IT Staff', 6, 3);

-- Update employees table to link with departments and positions
UPDATE `employees` e 
LEFT JOIN `departments` d ON e.Department = d.DepartmentName 
SET e.DepartmentID = d.DepartmentID 
WHERE e.DepartmentID IS NULL;

UPDATE `employees` e 
LEFT JOIN `positions` p ON e.Position = p.PositionName 
SET e.PositionID = p.PositionID 
WHERE e.PositionID IS NULL;

-- Insert sample deductions for testing
INSERT IGNORE INTO `deductions` (`EmployeeID`, `DeductionType`, `DeductionName`, `Amount`, `ComputationMethod`, `IsStatutory`, `IsVoluntary`, `EffectiveDate`, `Status`, `Notes`) VALUES
(1, 'HMO', 'HMO Premium', 500.00, 'Fixed', 0, 1, CURDATE(), 'Active', 'Monthly HMO premium'),
(1, 'Loan', 'Salary Loan', 1000.00, 'Fixed', 0, 1, CURDATE(), 'Active', 'Monthly salary loan payment'),
(2, 'HMO', 'HMO Premium', 500.00, 'Fixed', 0, 1, CURDATE(), 'Active', 'Monthly HMO premium'),
(2, 'Cash Advance', 'Emergency Loan', 2000.00, 'Fixed', 0, 1, CURDATE(), 'Active', 'Emergency cash advance');

-- Insert sample bonuses for testing
INSERT IGNORE INTO `payroll_bonuses` (`EmployeeID`, `BonusType`, `Amount`, `BonusDate`, `Status`, `Notes`) VALUES
(1, 'Performance Bonus', 2000.00, CURDATE(), 'Manual', 'Q4 Performance bonus'),
(1, 'Holiday Bonus', 5000.00, CURDATE(), 'Manual', 'Christmas bonus'),
(2, 'Performance Bonus', 1500.00, CURDATE(), 'Manual', 'Q4 Performance bonus'),
(2, 'Holiday Bonus', 5000.00, CURDATE(), 'Manual', 'Christmas bonus');

-- Insert sample salary adjustments for testing
INSERT IGNORE INTO `salary_adjustments` (`EmployeeID`, `AdjustmentType`, `Amount`, `AdjustmentDate`, `Reason`, `Status`) VALUES
(1, 'Overtime', 1500.00, CURDATE(), 'Overtime hours worked', 'Approved'),
(1, 'Leave Deduction', -500.00, CURDATE(), 'Unpaid leave taken', 'Approved'),
(2, 'Overtime', 1200.00, CURDATE(), 'Overtime hours worked', 'Approved'),
(2, 'Leave Deduction', -300.00, CURDATE(), 'Unpaid leave taken', 'Approved');
