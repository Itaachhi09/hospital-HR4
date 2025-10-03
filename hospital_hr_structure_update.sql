-- Hospital HR Core Organizational Structure Update
-- This script updates the existing HR system to support hospital-specific organizational structure
-- Compatible with DOLE, DOH, PhilHealth, SSS, and Pag-IBIG standards

-- =====================================================
-- PHASE 1: CREATE NEW HOSPITAL-SPECIFIC TABLES
-- =====================================================

-- Create HR Divisions table for major HR functional areas
CREATE TABLE IF NOT EXISTS `hr_divisions` (
  `DivisionID` int(11) NOT NULL AUTO_INCREMENT,
  `DivisionName` varchar(150) NOT NULL,
  `DivisionCode` varchar(20) NOT NULL UNIQUE,
  `Description` text,
  `DivisionHead` int(11) DEFAULT NULL,
  `ParentDivisionID` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`DivisionID`),
  KEY `fk_division_head` (`DivisionHead`),
  KEY `fk_parent_division` (`ParentDivisionID`),
  CONSTRAINT `fk_division_head` FOREIGN KEY (`DivisionHead`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL,
  CONSTRAINT `fk_parent_division` FOREIGN KEY (`ParentDivisionID`) REFERENCES `hr_divisions` (`DivisionID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create Hospital Job Roles table for detailed role definitions
CREATE TABLE IF NOT EXISTS `hospital_job_roles` (
  `JobRoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleTitle` varchar(150) NOT NULL,
  `RoleCode` varchar(20) NOT NULL UNIQUE,
  `DivisionID` int(11) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `JobLevel` enum('Executive','Senior Management','Middle Management','Supervisory','Officer','Staff','Entry Level') NOT NULL DEFAULT 'Staff',
  `JobFamily` enum('Clinical','Administrative','Support','Technical','Management') NOT NULL DEFAULT 'Administrative',
  `MinimumQualification` text,
  `JobDescription` text,
  `ReportsTo` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`JobRoleID`),
  KEY `fk_role_division` (`DivisionID`),
  KEY `fk_role_department` (`DepartmentID`),
  KEY `fk_reports_to` (`ReportsTo`),
  CONSTRAINT `fk_role_division` FOREIGN KEY (`DivisionID`) REFERENCES `hr_divisions` (`DivisionID`) ON DELETE SET NULL,
  CONSTRAINT `fk_role_department` FOREIGN KEY (`DepartmentID`) REFERENCES `organizationalstructure` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_reports_to` FOREIGN KEY (`ReportsTo`) REFERENCES `hospital_job_roles` (`JobRoleID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create Department Coordinators table for hospital department HR coordinators
CREATE TABLE IF NOT EXISTS `department_hr_coordinators` (
  `CoordinatorID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `CoordinatorType` enum('Primary','Backup','Interim') NOT NULL DEFAULT 'Primary',
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `AssignedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CoordinatorID`),
  KEY `fk_coord_department` (`DepartmentID`),
  KEY `fk_coord_employee` (`EmployeeID`),
  KEY `fk_coord_assigned_by` (`AssignedBy`),
  CONSTRAINT `fk_coord_department` FOREIGN KEY (`DepartmentID`) REFERENCES `organizationalstructure` (`DepartmentID`) ON DELETE CASCADE,
  CONSTRAINT `fk_coord_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `fk_coord_assigned_by` FOREIGN KEY (`AssignedBy`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PHASE 2: ENHANCE EXISTING TABLES
-- =====================================================

-- Add additional columns to OrganizationalStructure table for hospital-specific data
ALTER TABLE `organizationalstructure` 
ADD COLUMN IF NOT EXISTS `DepartmentCode` varchar(20) UNIQUE AFTER `DepartmentName`,
ADD COLUMN IF NOT EXISTS `DepartmentType` enum('Clinical','Administrative','Support','Ancillary','Executive') DEFAULT 'Administrative' AFTER `DepartmentCode`,
ADD COLUMN IF NOT EXISTS `Description` text AFTER `DepartmentType`,
ADD COLUMN IF NOT EXISTS `ManagerID` int(11) DEFAULT NULL AFTER `Description`,
ADD COLUMN IF NOT EXISTS `Budget` decimal(15,2) DEFAULT NULL AFTER `ManagerID`,
ADD COLUMN IF NOT EXISTS `Location` varchar(255) DEFAULT NULL AFTER `Budget`,
ADD COLUMN IF NOT EXISTS `IsActive` tinyint(1) NOT NULL DEFAULT 1 AFTER `Location`,
ADD COLUMN IF NOT EXISTS `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `IsActive`,
ADD COLUMN IF NOT EXISTS `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `CreatedAt`;

-- Add foreign key constraint for ManagerID if not exists
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'organizationalstructure' 
                  AND CONSTRAINT_NAME = 'fk_dept_manager');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `organizationalstructure` ADD CONSTRAINT `fk_dept_manager` FOREIGN KEY (`ManagerID`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL',
    'SELECT "Foreign key fk_dept_manager already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add additional columns to Employees table for hospital-specific data
ALTER TABLE `employees`
ADD COLUMN IF NOT EXISTS `JobRoleID` int(11) DEFAULT NULL AFTER `JobTitle`,
ADD COLUMN IF NOT EXISTS `EmployeeNumber` varchar(20) UNIQUE AFTER `JobRoleID`,
ADD COLUMN IF NOT EXISTS `LicenseNumber` varchar(50) DEFAULT NULL AFTER `EmployeeNumber`,
ADD COLUMN IF NOT EXISTS `LicenseExpiryDate` date DEFAULT NULL AFTER `LicenseNumber`,
ADD COLUMN IF NOT EXISTS `Specialization` varchar(150) DEFAULT NULL AFTER `LicenseExpiryDate`,
ADD COLUMN IF NOT EXISTS `EmploymentType` enum('Regular','Contractual','Probationary','Consultant','Part-time') DEFAULT 'Regular' AFTER `Specialization`,
ADD COLUMN IF NOT EXISTS `EmploymentStatus` enum('Active','On Leave','Suspended','Terminated','Retired') DEFAULT 'Active' AFTER `EmploymentType`;

-- Add foreign key for JobRoleID if not exists
SET @fk_exists2 = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'employees' 
                   AND CONSTRAINT_NAME = 'fk_employee_job_role');

SET @sql2 = IF(@fk_exists2 = 0, 
    'ALTER TABLE `employees` ADD CONSTRAINT `fk_employee_job_role` FOREIGN KEY (`JobRoleID`) REFERENCES `hospital_job_roles` (`JobRoleID`) ON DELETE SET NULL',
    'SELECT "Foreign key fk_employee_job_role already exists"');

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- =====================================================
-- PHASE 3: INSERT HOSPITAL HR STRUCTURE DATA
-- =====================================================

-- Insert HR Divisions (Main HR functional areas)
INSERT INTO `hr_divisions` (`DivisionName`, `DivisionCode`, `Description`) VALUES
-- Top Level
('Hospital Administration', 'ADMIN', 'Hospital Director and Chief Administrative Officers'),
('Human Resources', 'HR', 'Chief Human Resources Officer and HR Management'),

-- HR Sub-Divisions
('HR Administration & Compliance', 'HR-ADM', 'HR policy management, compliance, and HRIS administration'),
('Recruitment & Staffing', 'HR-REC', 'Talent acquisition, recruitment, and onboarding processes'),
('Compensation & Benefits', 'HR-CNB', 'Salary administration, payroll, and employee benefits management'),
('Employee Relations & Engagement', 'HR-ENG', 'Employee relations, engagement programs, and conflict resolution'),
('Training & Development', 'HR-TRN', 'Learning and development, training programs, and career development'),
('Occupational Health & Safety', 'HR-OHS', 'Workplace safety, occupational health, and compliance');

-- Update parent relationships for HR sub-divisions
UPDATE `hr_divisions` SET `ParentDivisionID` = (SELECT `DivisionID` FROM (SELECT * FROM `hr_divisions`) AS t WHERE `DivisionCode` = 'HR') 
WHERE `DivisionCode` IN ('HR-ADM', 'HR-REC', 'HR-CNB', 'HR-ENG', 'HR-TRN', 'HR-OHS');

-- Clear existing organizational structure and rebuild with hospital-specific structure
DELETE FROM `organizationalstructure` WHERE `DepartmentID` > 0;

-- Insert Hospital Departments with proper hierarchy
INSERT INTO `organizationalstructure` (`DepartmentID`, `DepartmentName`, `DepartmentCode`, `DepartmentType`, `Description`, `ParentDepartmentID`) VALUES
-- Executive Level
(1, 'Hospital Administration', 'ADMIN', 'Executive', 'Hospital Director and executive management', NULL),
(2, 'Human Resources', 'HR', 'Administrative', 'Human Resources Department', 1),

-- Clinical Departments
(10, 'Medical Services', 'MED', 'Clinical', 'Clinical operations and medical services', 1),
(11, 'Nursing Services', 'NURS', 'Clinical', 'Nursing operations and patient care', 10),
(12, 'Radiology Department', 'RAD', 'Clinical', 'Diagnostic imaging and radiology services', 10),
(13, 'Laboratory Services', 'LAB', 'Clinical', 'Clinical laboratory and pathology services', 10),
(14, 'Pharmacy Department', 'PHARM', 'Clinical', 'Pharmaceutical services and medication management', 10),
(15, 'Emergency Department', 'ER', 'Clinical', 'Emergency and trauma care services', 10),
(16, 'Surgery Department', 'SURG', 'Clinical', 'Surgical services and operating room management', 10),

-- Administrative Departments
(20, 'Finance Department', 'FIN', 'Administrative', 'Financial management and accounting', 1),
(21, 'Information Technology', 'IT', 'Administrative', 'IT services and system management', 1),
(22, 'Legal Affairs', 'LEGAL', 'Administrative', 'Legal compliance and affairs', 1),

-- Support Departments
(30, 'Facilities Management', 'FAC', 'Support', 'Building maintenance and facilities', 1),
(31, 'Security Department', 'SEC', 'Support', 'Hospital security and safety', 30),
(32, 'Housekeeping Services', 'HOUSE', 'Support', 'Cleaning and sanitation services', 30),
(33, 'Food Services', 'FOOD', 'Support', 'Dietary and food service operations', 30),

-- HR Sub-Departments
(50, 'HR Administration', 'HR-ADM', 'Administrative', 'HR policy, compliance, and administration', 2),
(51, 'Recruitment & Staffing', 'HR-REC', 'Administrative', 'Talent acquisition and staffing', 2),
(52, 'Compensation & Benefits', 'HR-CNB', 'Administrative', 'Payroll, compensation, and benefits', 2),
(53, 'Employee Relations', 'HR-ENG', 'Administrative', 'Employee relations and engagement', 2),
(54, 'Training & Development', 'HR-TRN', 'Administrative', 'Learning and development programs', 2),
(55, 'Occupational Health & Safety', 'HR-OHS', 'Administrative', 'Workplace health and safety', 2);

-- Insert Hospital Job Roles with proper hierarchy
INSERT INTO `hospital_job_roles` (`RoleTitle`, `RoleCode`, `DivisionID`, `DepartmentID`, `JobLevel`, `JobFamily`, `MinimumQualification`, `JobDescription`) VALUES
-- Executive Level
('Hospital Director', 'DIR-001', 1, 1, 'Executive', 'Management', 'MD degree, MBA preferred, 10+ years healthcare management experience', 'Chief executive officer of the hospital responsible for overall operations'),
('Chief Medical Officer', 'CMO-001', 1, 10, 'Executive', 'Clinical', 'MD degree, board certification, 15+ years clinical and management experience', 'Chief medical officer overseeing all clinical operations'),
('Chief Human Resources Officer', 'CHRO-001', 2, 2, 'Executive', 'Management', 'Masters in HR/MBA, 10+ years HR leadership experience in healthcare', 'Chief HR officer responsible for all human resources functions'),

-- HR Management Level
('HR Director', 'HR-DIR-001', 2, 2, 'Senior Management', 'Management', 'Bachelor\'s in HR/Psychology, 8+ years HR management experience', 'Director of Human Resources reporting to CHRO'),
('HR Administration Manager', 'HR-ADM-001', 2, 50, 'Middle Management', 'Administrative', 'Bachelor\'s in HR, 5+ years HR experience', 'Manager of HR administration and compliance'),
('Recruitment Manager', 'HR-REC-001', 2, 51, 'Middle Management', 'Administrative', 'Bachelor\'s in HR/Psychology, 5+ years recruitment experience', 'Manager of recruitment and staffing operations'),
('Compensation & Benefits Manager', 'HR-CNB-001', 2, 52, 'Middle Management', 'Administrative', 'Bachelor\'s in HR/Accounting, 5+ years compensation experience', 'Manager of compensation, benefits, and payroll'),
('Employee Relations Manager', 'HR-ENG-001', 2, 53, 'Middle Management', 'Administrative', 'Bachelor\'s in HR/Psychology, 5+ years employee relations experience', 'Manager of employee relations and engagement'),
('Training & Development Manager', 'HR-TRN-001', 2, 54, 'Middle Management', 'Administrative', 'Bachelor\'s in Education/HR, 5+ years training experience', 'Manager of learning and development programs'),

-- HR Officer Level
('HR Officer - Compliance', 'HR-OFF-001', 2, 50, 'Officer', 'Administrative', 'Bachelor\'s in HR/Law, 3+ years HR experience', 'HR officer specializing in compliance and policy'),
('HRIS Administrator', 'HR-SYS-001', 2, 50, 'Officer', 'Technical', 'Bachelor\'s in IT/HR, 3+ years HRIS experience', 'Administrator of HR information systems'),
('Labor Relations Specialist', 'HR-LAB-001', 2, 50, 'Officer', 'Administrative', 'Bachelor\'s in HR/Law, 3+ years labor relations experience', 'Specialist in labor relations and legal compliance'),
('Recruitment Officer', 'HR-REC-002', 2, 51, 'Officer', 'Administrative', 'Bachelor\'s in HR/Psychology, 2+ years recruitment experience', 'Officer responsible for recruitment activities'),
('Onboarding Officer', 'HR-ONB-001', 2, 51, 'Officer', 'Administrative', 'Bachelor\'s in HR, 2+ years HR experience', 'Officer responsible for employee onboarding'),
('Payroll Officer', 'HR-PAY-001', 2, 52, 'Officer', 'Administrative', 'Bachelor\'s in Accounting/HR, 2+ years payroll experience', 'Officer responsible for payroll processing'),
('Benefits Specialist', 'HR-BEN-001', 2, 52, 'Officer', 'Administrative', 'Bachelor\'s in HR, 2+ years benefits administration experience', 'Specialist in employee benefits and HMO coordination'),
('Engagement Officer', 'HR-ENG-002', 2, 53, 'Officer', 'Administrative', 'Bachelor\'s in HR/Psychology, 2+ years experience', 'Officer responsible for employee engagement programs'),
('Training Coordinator', 'HR-TRN-002', 2, 54, 'Officer', 'Administrative', 'Bachelor\'s in Education/HR, 2+ years training experience', 'Coordinator of training programs and events'),
('Learning & Development Officer', 'HR-LND-001', 2, 54, 'Officer', 'Administrative', 'Bachelor\'s in Education/HR, 2+ years L&D experience', 'Officer responsible for learning and development initiatives'),

-- Occupational Health & Safety
('Occupational Health Physician', 'HR-OHP-001', 2, 55, 'Officer', 'Clinical', 'MD degree, occupational medicine specialization', 'Physician responsible for occupational health programs'),
('Company Nurse', 'HR-NUR-001', 2, 55, 'Officer', 'Clinical', 'BSN degree, RN license, 2+ years clinical experience', 'Nurse responsible for employee health and wellness'),
('Safety Officer', 'HR-SAF-001', 2, 55, 'Officer', 'Technical', 'Bachelor\'s in Engineering/Safety, safety certification', 'Officer responsible for workplace safety and compliance'),

-- Department HR Coordinators
('HR Coordinator - Nursing', 'HR-CRD-001', 2, 11, 'Officer', 'Administrative', 'Bachelor\'s in HR/Nursing, 2+ years experience', 'HR coordinator for nursing department'),
('HR Coordinator - Radiology', 'HR-CRD-002', 2, 12, 'Officer', 'Administrative', 'Bachelor\'s in HR, healthcare experience preferred', 'HR coordinator for radiology department'),
('HR Coordinator - Laboratory', 'HR-CRD-003', 2, 13, 'Officer', 'Administrative', 'Bachelor\'s in HR, laboratory experience preferred', 'HR coordinator for laboratory services'),
('HR Coordinator - Pharmacy', 'HR-CRD-004', 2, 14, 'Officer', 'Administrative', 'Bachelor\'s in HR, pharmacy experience preferred', 'HR coordinator for pharmacy department'),
('HR Coordinator - IT', 'HR-CRD-005', 2, 21, 'Officer', 'Administrative', 'Bachelor\'s in HR/IT, technology experience preferred', 'HR coordinator for IT department'),
('HR Coordinator - Finance', 'HR-CRD-006', 2, 20, 'Officer', 'Administrative', 'Bachelor\'s in HR/Accounting, finance experience preferred', 'HR coordinator for finance department'),
('HR Coordinator - Facilities', 'HR-CRD-007', 2, 30, 'Officer', 'Administrative', 'Bachelor\'s in HR, facilities management experience preferred', 'HR coordinator for facilities management');

-- Update roles hierarchy (ReportsTo relationships)
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'CHRO-001') WHERE `RoleCode` = 'HR-DIR-001';
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-DIR-001') WHERE `RoleCode` IN ('HR-ADM-001', 'HR-REC-001', 'HR-CNB-001', 'HR-ENG-001', 'HR-TRN-001');
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-ADM-001') WHERE `RoleCode` IN ('HR-OFF-001', 'HR-SYS-001', 'HR-LAB-001');
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-REC-001') WHERE `RoleCode` IN ('HR-REC-002', 'HR-ONB-001');
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-CNB-001') WHERE `RoleCode` IN ('HR-PAY-001', 'HR-BEN-001');
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-ENG-001') WHERE `RoleCode` = 'HR-ENG-002';
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-TRN-001') WHERE `RoleCode` IN ('HR-TRN-002', 'HR-LND-001');
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-DIR-001') WHERE `RoleCode` IN ('HR-OHP-001', 'HR-NUR-001', 'HR-SAF-001');

-- Update HR coordinators to report to HR Administration Manager
UPDATE `hospital_job_roles` SET `ReportsTo` = (SELECT `JobRoleID` FROM (SELECT * FROM `hospital_job_roles`) AS t WHERE `RoleCode` = 'HR-ADM-001') WHERE `RoleCode` LIKE 'HR-CRD-%';

-- =====================================================
-- PHASE 4: UPDATE ROLES TABLE FOR HOSPITAL-SPECIFIC ACCESS
-- =====================================================

-- Add new roles for hospital-specific access control
INSERT INTO `roles` (`RoleName`) VALUES
('Hospital Director'),
('HR Director'),
('HR Manager'),
('HR Officer'),
('HR Coordinator'),
('Department Manager'),
('Medical Staff'),
('Nursing Staff'),
('Support Staff')
ON DUPLICATE KEY UPDATE `RoleName` = VALUES(`RoleName`);

-- =====================================================
-- PHASE 5: CREATE SAMPLE DATA FOR TESTING
-- =====================================================

-- Note: Employee data will be populated through the application interface
-- This provides the structure for hospital-specific HR management

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_hospital_job_roles_division ON hospital_job_roles(DivisionID);
CREATE INDEX IF NOT EXISTS idx_hospital_job_roles_department ON hospital_job_roles(DepartmentID);
CREATE INDEX IF NOT EXISTS idx_hospital_job_roles_level ON hospital_job_roles(JobLevel);
CREATE INDEX IF NOT EXISTS idx_employees_job_role ON employees(JobRoleID);
CREATE INDEX IF NOT EXISTS idx_employees_employment_type ON employees(EmploymentType);
CREATE INDEX IF NOT EXISTS idx_department_coordinators_dept ON department_hr_coordinators(DepartmentID);
CREATE INDEX IF NOT EXISTS idx_org_structure_type ON organizationalstructure(DepartmentType);
CREATE INDEX IF NOT EXISTS idx_org_structure_code ON organizationalstructure(DepartmentCode);

-- =====================================================
-- PHASE 6: VERIFICATION QUERIES
-- =====================================================

-- Verify structure creation
SELECT 'HR Divisions Created' as Status, COUNT(*) as Count FROM hr_divisions;
SELECT 'Hospital Job Roles Created' as Status, COUNT(*) as Count FROM hospital_job_roles;
SELECT 'Departments Updated' as Status, COUNT(*) as Count FROM organizationalstructure;
SELECT 'Roles Updated' as Status, COUNT(*) as Count FROM roles;

-- Show HR hierarchy
SELECT 
    d.DivisionName,
    d.DivisionCode,
    pd.DivisionName as ParentDivision,
    COUNT(hjr.JobRoleID) as RoleCount
FROM hr_divisions d
LEFT JOIN hr_divisions pd ON d.ParentDivisionID = pd.DivisionID
LEFT JOIN hospital_job_roles hjr ON d.DivisionID = hjr.DivisionID
GROUP BY d.DivisionID
ORDER BY d.ParentDivisionID, d.DivisionName;
