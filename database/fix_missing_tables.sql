-- Fix missing tables and schema issues
-- Date: 2025-10-10

-- Create incentive_types table
CREATE TABLE IF NOT EXISTS `incentive_types` (
  `IncentiveTypeID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(150) NOT NULL,
  `Category` VARCHAR(100) NOT NULL COMMENT 'e.g., Performance, Project, Attendance, etc.',
  `Description` TEXT NULL,
  `EligibilityJSON` JSON NULL COMMENT 'JSON criteria for eligibility',
  `ValueType` ENUM('Fixed', 'Percentage') NOT NULL DEFAULT 'Fixed',
  `ValueAmount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `Frequency` ENUM('One-time', 'Monthly', 'Quarterly', 'Annual', 'Project') NOT NULL DEFAULT 'One-time',
  `DepartmentID` INT NULL COMMENT 'NULL = all departments',
  `PositionCategory` VARCHAR(100) NULL COMMENT 'NULL = all positions',
  `Status` ENUM('Active', 'Inactive', 'Archived') NOT NULL DEFAULT 'Active',
  `Taxable` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (Status),
  INDEX idx_category (Category),
  INDEX idx_department (DepartmentID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Incentive Type Definitions';

-- Seed some default incentive types
INSERT IGNORE INTO `incentive_types` 
  (Name, Category, Description, ValueType, ValueAmount, Frequency, Status, Taxable) 
VALUES
  ('Performance Bonus', 'Performance', 'Annual performance-based bonus', 'Fixed', 5000.00, 'Annual', 'Active', 1),
  ('Project Completion', 'Project', 'Bonus for completing major projects', 'Percentage', 10.00, 'Project', 'Active', 1),
  ('Attendance Incentive', 'Attendance', 'Perfect attendance bonus', 'Fixed', 2000.00, 'Monthly', 'Active', 1),
  ('Sales Commission', 'Sales', 'Sales performance commission', 'Percentage', 5.00, 'Monthly', 'Active', 1);

-- Note: payroll_v2_runs should be payroll_runs_v2 (table already exists)
-- No need to create it, just need to update the model reference

