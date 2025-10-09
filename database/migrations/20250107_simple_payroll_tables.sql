-- Simple Payroll Tables Migration
-- Create minimal tables needed for the payroll modules

-- Create payroll_bonuses table
CREATE TABLE IF NOT EXISTS `payroll_bonuses` (
  `BonusID` int(11) NOT NULL AUTO_INCREMENT,
  `PayrollRunID` int(11) DEFAULT NULL,
  `EmployeeID` int(11) NOT NULL,
  `BonusType` varchar(50) NOT NULL,
  `Amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `BonusDate` date NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Manual',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`BonusID`),
  KEY `idx_bonus_emp` (`EmployeeID`),
  KEY `idx_bonus_payroll` (`PayrollRunID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create salary_adjustments table
CREATE TABLE IF NOT EXISTS `salary_adjustments` (
  `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `AdjustmentType` varchar(50) NOT NULL,
  `Amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `AdjustmentDate` date NOT NULL,
  `Reason` text DEFAULT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AdjustmentID`),
  KEY `idx_adj_emp` (`EmployeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT IGNORE INTO `payroll_bonuses` (`EmployeeID`, `BonusType`, `Amount`, `BonusDate`, `Status`, `Notes`) VALUES
(1, 'Performance Bonus', 2000.00, CURDATE(), 'Manual', 'Q4 Performance bonus'),
(1, 'Holiday Bonus', 5000.00, CURDATE(), 'Manual', 'Christmas bonus'),
(2, 'Performance Bonus', 1500.00, CURDATE(), 'Manual', 'Q4 Performance bonus'),
(2, 'Holiday Bonus', 5000.00, CURDATE(), 'Manual', 'Christmas bonus');

INSERT IGNORE INTO `salary_adjustments` (`EmployeeID`, `AdjustmentType`, `Amount`, `AdjustmentDate`, `Reason`, `Status`) VALUES
(1, 'Overtime', 1500.00, CURDATE(), 'Overtime hours worked', 'Approved'),
(1, 'Leave Deduction', -500.00, CURDATE(), 'Unpaid leave taken', 'Approved'),
(2, 'Overtime', 1200.00, CURDATE(), 'Overtime hours worked', 'Approved'),
(2, 'Leave Deduction', -300.00, CURDATE(), 'Unpaid leave taken', 'Approved');
