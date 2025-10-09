-- Compensation Planning Schema Migration
-- Date: 2025-01-07
-- Description: Creates comprehensive compensation planning tables for salary grades, pay bands, and adjustment workflows

-- Create salary_grades table
CREATE TABLE IF NOT EXISTS `salary_grades` (
  `GradeID` int(11) NOT NULL AUTO_INCREMENT,
  `GradeCode` varchar(20) NOT NULL UNIQUE,
  `GradeName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `PositionCategory` varchar(100) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive','Draft') NOT NULL DEFAULT 'Draft',
  `CreatedBy` int(11) DEFAULT NULL,
  `ApprovedBy` int(11) DEFAULT NULL,
  `ApprovedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`GradeID`),
  KEY `idx_grade_code` (`GradeCode`),
  KEY `idx_grade_dept` (`DepartmentID`),
  KEY `idx_grade_branch` (`BranchID`),
  KEY `idx_grade_status` (`Status`),
  CONSTRAINT `fk_grade_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_grade_created` FOREIGN KEY (`CreatedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL,
  CONSTRAINT `fk_grade_approved` FOREIGN KEY (`ApprovedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create salary_steps table
CREATE TABLE IF NOT EXISTS `salary_steps` (
  `StepID` int(11) NOT NULL AUTO_INCREMENT,
  `GradeID` int(11) NOT NULL,
  `StepNumber` int(11) NOT NULL,
  `StepName` varchar(50) DEFAULT NULL,
  `MinRate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `MaxRate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `BaseRate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`StepID`),
  UNIQUE KEY `unique_grade_step` (`GradeID`, `StepNumber`),
  KEY `idx_step_grade` (`GradeID`),
  KEY `idx_step_number` (`StepNumber`),
  CONSTRAINT `fk_step_grade` FOREIGN KEY (`GradeID`) REFERENCES `salary_grades` (`GradeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create pay_bands table
CREATE TABLE IF NOT EXISTS `pay_bands` (
  `BandID` int(11) NOT NULL AUTO_INCREMENT,
  `BandName` varchar(100) NOT NULL,
  `MinSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `MaxSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Description` text DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `PositionCategory` varchar(100) DEFAULT NULL,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`BandID`),
  KEY `idx_band_dept` (`DepartmentID`),
  KEY `idx_band_status` (`Status`),
  CONSTRAINT `fk_band_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create employee_grade_mapping table
CREATE TABLE IF NOT EXISTS `employee_grade_mapping` (
  `MappingID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `GradeID` int(11) NOT NULL,
  `StepID` int(11) NOT NULL,
  `CurrentSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `GradeMinRate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `GradeMaxRate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `Status` enum('Within Band','Below Band','Above Band','Pending Review') NOT NULL DEFAULT 'Pending Review',
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`MappingID`),
  UNIQUE KEY `unique_emp_grade` (`EmployeeID`, `GradeID`),
  KEY `idx_mapping_emp` (`EmployeeID`),
  KEY `idx_mapping_grade` (`GradeID`),
  KEY `idx_mapping_step` (`StepID`),
  KEY `idx_mapping_status` (`Status`),
  CONSTRAINT `fk_mapping_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_grade` FOREIGN KEY (`GradeID`) REFERENCES `salary_grades` (`GradeID`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_step` FOREIGN KEY (`StepID`) REFERENCES `salary_steps` (`StepID`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_created` FOREIGN KEY (`CreatedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create pay_adjustment_workflows table
CREATE TABLE IF NOT EXISTS `pay_adjustment_workflows` (
  `WorkflowID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkflowName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `AdjustmentType` enum('Percentage','Fixed Amount','Grade Based','Position Based') NOT NULL,
  `AdjustmentValue` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `TargetGrades` json DEFAULT NULL,
  `TargetDepartments` json DEFAULT NULL,
  `TargetPositions` json DEFAULT NULL,
  `EffectiveDate` date NOT NULL,
  `Status` enum('Draft','Review','Approved','Implemented','Cancelled') NOT NULL DEFAULT 'Draft',
  `TotalImpact` decimal(15,2) DEFAULT NULL,
  `AffectedEmployees` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `ApprovedBy` int(11) DEFAULT NULL,
  `ApprovedAt` timestamp NULL DEFAULT NULL,
  `ImplementedBy` int(11) DEFAULT NULL,
  `ImplementedAt` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`WorkflowID`),
  KEY `idx_workflow_type` (`AdjustmentType`),
  KEY `idx_workflow_status` (`Status`),
  KEY `idx_workflow_created` (`CreatedBy`),
  CONSTRAINT `fk_workflow_created` FOREIGN KEY (`CreatedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL,
  CONSTRAINT `fk_workflow_approved` FOREIGN KEY (`ApprovedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL,
  CONSTRAINT `fk_workflow_implemented` FOREIGN KEY (`ImplementedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create pay_adjustment_details table
CREATE TABLE IF NOT EXISTS `pay_adjustment_details` (
  `DetailID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkflowID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `CurrentSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `NewSalary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `AdjustmentAmount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `AdjustmentPercentage` decimal(5,2) DEFAULT NULL,
  `Status` enum('Pending','Approved','Rejected','Implemented') NOT NULL DEFAULT 'Pending',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`DetailID`),
  KEY `idx_detail_workflow` (`WorkflowID`),
  KEY `idx_detail_emp` (`EmployeeID`),
  KEY `idx_detail_status` (`Status`),
  CONSTRAINT `fk_detail_workflow` FOREIGN KEY (`WorkflowID`) REFERENCES `pay_adjustment_workflows` (`WorkflowID`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create compensation_simulations table
CREATE TABLE IF NOT EXISTS `compensation_simulations` (
  `SimulationID` int(11) NOT NULL AUTO_INCREMENT,
  `SimulationName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `SimulationType` enum('Grade Adjustment','Department Adjustment','Position Adjustment','Custom') NOT NULL,
  `Parameters` json NOT NULL,
  `Results` json DEFAULT NULL,
  `TotalImpact` decimal(15,2) DEFAULT NULL,
  `AffectedEmployees` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ExpiresAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`SimulationID`),
  KEY `idx_sim_type` (`SimulationType`),
  KEY `idx_sim_created` (`CreatedBy`),
  CONSTRAINT `fk_sim_created` FOREIGN KEY (`CreatedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create compensation_analytics table
CREATE TABLE IF NOT EXISTS `compensation_analytics` (
  `AnalyticsID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportType` enum('Salary Trends','Grade Distribution','Pay Equity','Department Comparison','Cost Analysis') NOT NULL,
  `ReportData` json NOT NULL,
  `ReportDate` date NOT NULL,
  `Period` enum('Daily','Weekly','Monthly','Quarterly','Yearly') NOT NULL DEFAULT 'Monthly',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AnalyticsID`),
  KEY `idx_analytics_type` (`ReportType`),
  KEY `idx_analytics_date` (`ReportDate`),
  KEY `idx_analytics_period` (`Period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create branches table if it doesn't exist
CREATE TABLE IF NOT EXISTS `branches` (
  `BranchID` int(11) NOT NULL AUTO_INCREMENT,
  `BranchName` varchar(100) NOT NULL,
  `BranchCode` varchar(20) NOT NULL UNIQUE,
  `Address` text DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `State` varchar(100) DEFAULT NULL,
  `Country` varchar(100) DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`BranchID`),
  KEY `idx_branch_code` (`BranchCode`),
  KEY `idx_branch_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for branches
INSERT IGNORE INTO `branches` (`BranchName`, `BranchCode`, `Address`, `City`, `State`, `Country`) VALUES
('Main Hospital', 'MAIN', '123 Medical Center St', 'Manila', 'NCR', 'Philippines'),
('Satellite Clinic', 'SAT1', '456 Health Ave', 'Quezon City', 'NCR', 'Philippines'),
('Regional Branch', 'REG1', '789 Care Blvd', 'Cebu City', 'Cebu', 'Philippines');

-- Insert sample salary grades
INSERT IGNORE INTO `salary_grades` (`GradeCode`, `GradeName`, `Description`, `DepartmentID`, `PositionCategory`, `BranchID`, `EffectiveDate`, `Status`, `CreatedBy`) VALUES
('SG-01', 'Salary Grade 1', 'Entry level positions', 1, 'Administrative', 1, '2025-01-01', 'Active', 1),
('SG-05', 'Salary Grade 5', 'Mid-level administrative positions', 1, 'Administrative', 1, '2025-01-01', 'Active', 1),
('SG-10', 'Salary Grade 10', 'Professional positions', 2, 'Professional', 1, '2025-01-01', 'Active', 1),
('SG-15', 'Salary Grade 15', 'Senior professional positions', 2, 'Professional', 1, '2025-01-01', 'Active', 1),
('SG-20', 'Salary Grade 20', 'Management positions', 1, 'Management', 1, '2025-01-01', 'Active', 1);

-- Insert sample salary steps for SG-10
INSERT IGNORE INTO `salary_steps` (`GradeID`, `StepNumber`, `StepName`, `MinRate`, `MaxRate`, `BaseRate`, `EffectiveDate`) VALUES
(3, 1, 'Step 1', 20000.00, 22000.00, 21000.00, '2025-01-01'),
(3, 2, 'Step 2', 22000.00, 24000.00, 23000.00, '2025-01-01'),
(3, 3, 'Step 3', 24000.00, 26000.00, 25000.00, '2025-01-01'),
(3, 4, 'Step 4', 26000.00, 28000.00, 27000.00, '2025-01-01'),
(3, 5, 'Step 5', 28000.00, 30000.00, 29000.00, '2025-01-01');

-- Insert sample pay bands
INSERT IGNORE INTO `pay_bands` (`BandName`, `MinSalary`, `MaxSalary`, `Description`, `DepartmentID`, `PositionCategory`, `EffectiveDate`) VALUES
('Entry Level', 15000.00, 25000.00, 'Entry level positions across all departments', NULL, 'Entry Level', '2025-01-01'),
('Mid Level', 25000.00, 40000.00, 'Mid-level professional positions', NULL, 'Professional', '2025-01-01'),
('Senior Level', 40000.00, 60000.00, 'Senior professional and management positions', NULL, 'Senior', '2025-01-01'),
('Executive Level', 60000.00, 100000.00, 'Executive and leadership positions', NULL, 'Executive', '2025-01-01');
