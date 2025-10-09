-- HMO module schema and seed data
-- Run this on your MySQL database to create tables and sample data for HMO providers, plans, enrollments, and claims.

SET FOREIGN_KEY_CHECKS=0;

-- Providers
CREATE TABLE IF NOT EXISTS HMOProviders (
  ProviderID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderName VARCHAR(255) NOT NULL,
  Description TEXT,
  ContactPerson VARCHAR(255),
  ContactNumber VARCHAR(50),
  Email VARCHAR(255),
  Status ENUM('Active','Inactive') DEFAULT 'Active',
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Plans
CREATE TABLE IF NOT EXISTS HMOPlans (
  PlanID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID INT NOT NULL,
  PlanName VARCHAR(255) NOT NULL,
  Coverage JSON DEFAULT NULL,
  AccreditedHospitals TEXT DEFAULT NULL,
  Eligibility VARCHAR(64) DEFAULT 'Individual',
  MaximumBenefitLimit DECIMAL(12,2) DEFAULT NULL,
  PremiumCost DECIMAL(12,2) DEFAULT NULL,
  Status ENUM('Active','Inactive') DEFAULT 'Active',
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ProviderID) REFERENCES HMOProviders(ProviderID) ON DELETE CASCADE
);

-- Enrollments
CREATE TABLE IF NOT EXISTS EmployeeHMOEnrollments (
  EnrollmentID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  PlanID INT NOT NULL,
  StartDate DATE NOT NULL,
  EndDate DATE DEFAULT NULL,
  Status ENUM('Active','Terminated','Pending') DEFAULT 'Active',
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (PlanID) REFERENCES HMOPlans(PlanID) ON DELETE CASCADE,
  INDEX (EmployeeID)
);

-- Claims
CREATE TABLE IF NOT EXISTS HMOClaims (
  ClaimID INT AUTO_INCREMENT PRIMARY KEY,
  EnrollmentID INT NOT NULL,
  ClaimDate DATE NOT NULL,
  HospitalClinic VARCHAR(255),
  Diagnosis TEXT,
  ClaimAmount DECIMAL(12,2) DEFAULT 0.00,
  ClaimStatus ENUM('Pending','Approved','Denied') DEFAULT 'Pending',
  Remarks TEXT,
  SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (EnrollmentID) REFERENCES EmployeeHMOEnrollments(EnrollmentID) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=1;

-- Seed data
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
VALUES
('HealthCare Plus','Large network provider','Ana Santos','09171234567','contact@hcp.example','Active'),
('MediAssist','Affordable plans for employees','Jose Delgado','09179876543','support@mediassist.example','Active');

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, MaximumBenefitLimit, PremiumCost, Status)
VALUES
(1, 'HCP Basic', JSON_ARRAY('inpatient','outpatient','emergency'), 50000.00, 250.00, 'Active'),
(1, 'HCP Premium', JSON_ARRAY('inpatient','outpatient','emergency','preventive','dental'), 200000.00, 750.00, 'Active'),
(2, 'Medi Standard', JSON_ARRAY('inpatient','outpatient'), 100000.00, 350.00, 'Active');

-- Example employee and enrollment rows require existing Employees table; add only if you have an Employees table
-- The following are sample inserts that assume Employees.EmployeeID 1 and 2 exist. Remove or adjust as needed.
-- INSERT INTO EmployeeHMOEnrollments (EmployeeID, PlanID, StartDate, EndDate, Status) VALUES (1,1, '2024-01-01', NULL, 'Active');
-- INSERT INTO EmployeeHMOEnrollments (EmployeeID, PlanID, StartDate, EndDate, Status) VALUES (2,2, '2024-02-01', NULL, 'Active');

-- Example claims (requires enrollments)
-- INSERT INTO HMOClaims (EnrollmentID, ClaimDate, HospitalClinic, Diagnosis, ClaimAmount, ClaimStatus, Remarks) VALUES (1, '2024-06-15', 'City Hospital', 'Flu', 1200.00, 'Approved', 'Routine claim');

-- Additional tables for enhanced HMO module

-- HMO Dependents (for family coverage)
CREATE TABLE IF NOT EXISTS HMODependents (
  DependentID INT AUTO_INCREMENT PRIMARY KEY,
  EnrollmentID INT NOT NULL,
  DependentName VARCHAR(255) NOT NULL,
  Relationship ENUM('Spouse','Child','Parent','Other') NOT NULL,
  DateOfBirth DATE,
  Gender ENUM('Male','Female','Other') DEFAULT NULL,
  IsActive TINYINT(1) DEFAULT 1,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (EnrollmentID) REFERENCES EmployeeHMOEnrollments(EnrollmentID) ON DELETE CASCADE,
  INDEX idx_enrollment (EnrollmentID)
);

-- HMO Notifications
CREATE TABLE IF NOT EXISTS hmo_notifications (
  NotificationID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  Type VARCHAR(50) NOT NULL,
  Title VARCHAR(255) NOT NULL,
  Message TEXT,
  IsRead TINYINT(1) DEFAULT 0,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ReadAt TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_employee (EmployeeID),
  INDEX idx_read (IsRead),
  INDEX idx_created (CreatedAt)
);

-- Claim Workflows
CREATE TABLE IF NOT EXISTS claim_workflows (
  WorkflowID INT AUTO_INCREMENT PRIMARY KEY,
  ClaimID INT NOT NULL,
  WorkflowType VARCHAR(50) NOT NULL,
  CurrentStep INT DEFAULT 1,
  Status ENUM('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CompletedAt TIMESTAMP NULL DEFAULT NULL,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ClaimID) REFERENCES HMOClaims(ClaimID) ON DELETE CASCADE,
  INDEX idx_claim (ClaimID),
  INDEX idx_status (Status)
);

-- Workflow Steps (Audit Trail)
CREATE TABLE IF NOT EXISTS claim_workflow_steps (
  StepID INT AUTO_INCREMENT PRIMARY KEY,
  WorkflowID INT NOT NULL,
  StepNumber INT NOT NULL,
  StepName VARCHAR(100) NOT NULL,
  AssignedTo INT NULL,
  Status ENUM('Pending','Approved','Rejected','Skipped') DEFAULT 'Pending',
  Comments TEXT,
  ProcessedAt TIMESTAMP NULL DEFAULT NULL,
  ProcessedBy INT NULL,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (WorkflowID) REFERENCES claim_workflows(WorkflowID) ON DELETE CASCADE,
  INDEX idx_workflow (WorkflowID),
  INDEX idx_assigned (AssignedTo)
);

-- HMO Reimbursements (for payroll integration)
CREATE TABLE IF NOT EXISTS hmo_reimbursements (
  ReimbursementID INT AUTO_INCREMENT PRIMARY KEY,
  ClaimID INT NOT NULL,
  EmployeeID INT NOT NULL,
  Amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  Status ENUM('Pending','Processed','Paid','Cancelled') DEFAULT 'Pending',
  PayrollRunID INT NULL,
  ProcessedDate TIMESTAMP NULL DEFAULT NULL,
  PaidDate TIMESTAMP NULL DEFAULT NULL,
  Notes TEXT,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ClaimID) REFERENCES HMOClaims(ClaimID) ON DELETE CASCADE,
  INDEX idx_employee (EmployeeID),
  INDEX idx_status (Status),
  INDEX idx_payroll (PayrollRunID)
);

-- HMO Provider Contracts (for tracking contract validity)
CREATE TABLE IF NOT EXISTS hmo_provider_contracts (
  ContractID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID INT NOT NULL,
  ContractNumber VARCHAR(100),
  StartDate DATE NOT NULL,
  EndDate DATE NOT NULL,
  AnnualCost DECIMAL(15,2) DEFAULT 0.00,
  EmployeeCoverage INT DEFAULT 0,
  RenewalDate DATE,
  Status ENUM('Active','Expired','Terminated','Pending Renewal') DEFAULT 'Active',
  Terms TEXT,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ProviderID) REFERENCES HMOProviders(ProviderID) ON DELETE CASCADE,
  INDEX idx_provider (ProviderID),
  INDEX idx_status (Status),
  INDEX idx_dates (StartDate, EndDate)
);

-- Benefit Balance Tracking
CREATE TABLE IF NOT EXISTS hmo_benefit_balances (
  BalanceID INT AUTO_INCREMENT PRIMARY KEY,
  EnrollmentID INT NOT NULL,
  BenefitYear INT NOT NULL,
  MaximumLimit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  UsedAmount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  RemainingAmount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  LastUpdated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (EnrollmentID) REFERENCES EmployeeHMOEnrollments(EnrollmentID) ON DELETE CASCADE,
  UNIQUE KEY unique_enrollment_year (EnrollmentID, BenefitYear),
  INDEX idx_enrollment (EnrollmentID),
  INDEX idx_year (BenefitYear)
);

-- Alter existing tables to add missing columns (if they don't exist)
ALTER TABLE hmoproviders 
  ADD COLUMN IF NOT EXISTS CompanyName VARCHAR(255) NULL AFTER ProviderName,
  ADD COLUMN IF NOT EXISTS Address TEXT NULL AFTER ContactPhone,
  ADD COLUMN IF NOT EXISTS Website VARCHAR(255) NULL AFTER Address,
  ADD COLUMN IF NOT EXISTS EstablishedYear INT NULL AFTER Website,
  ADD COLUMN IF NOT EXISTS AccreditationNumber VARCHAR(100) NULL AFTER EstablishedYear,
  ADD COLUMN IF NOT EXISTS ServiceAreas VARCHAR(255) NULL AFTER AccreditationNumber,
  ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) DEFAULT 1 AFTER ServiceAreas;

ALTER TABLE hmoplans
  ADD COLUMN IF NOT EXISTS PlanCode VARCHAR(50) NULL AFTER PlanName,
  ADD COLUMN IF NOT EXISTS Description TEXT NULL AFTER PlanCode,
  ADD COLUMN IF NOT EXISTS PlanCategory VARCHAR(50) DEFAULT 'Individual' AFTER MaximumBenefitLimit,
  ADD COLUMN IF NOT EXISTS EffectiveDate DATE NULL AFTER IsActive,
  ADD COLUMN IF NOT EXISTS IsActive TINYINT(1) DEFAULT 1 AFTER AccreditedHospitals;

ALTER TABLE employeehmoenrollments
  ADD COLUMN IF NOT EXISTS EnrollmentDate DATE NULL AFTER PlanID,
  ADD COLUMN IF NOT EXISTS EffectiveDate DATE NULL AFTER EnrollmentDate,
  ADD COLUMN IF NOT EXISTS MonthlyContribution DECIMAL(10,2) DEFAULT 0.00 AFTER Status,
  ADD COLUMN IF NOT EXISTS MonthlyDeduction DECIMAL(10,2) DEFAULT 0.00 AFTER MonthlyContribution,
  ADD COLUMN IF NOT EXISTS DependentsCount INT DEFAULT 0 AFTER MonthlyDeduction,
  ADD COLUMN IF NOT EXISTS Notes TEXT NULL AFTER DependentsCount;

ALTER TABLE hmoclaims
  ADD COLUMN IF NOT EXISTS EmployeeID INT NULL AFTER EnrollmentID,
  ADD COLUMN IF NOT EXISTS ClaimNumber VARCHAR(50) NULL AFTER EmployeeID,
  ADD COLUMN IF NOT EXISTS ClaimType VARCHAR(50) DEFAULT 'Medical' AFTER ClaimNumber,
  ADD COLUMN IF NOT EXISTS ProviderName VARCHAR(255) NULL AFTER ClaimType,
  ADD COLUMN IF NOT EXISTS Description TEXT NULL AFTER ProviderName,
  ADD COLUMN IF NOT EXISTS Amount DECIMAL(12,2) DEFAULT 0.00 AFTER Description,
  ADD COLUMN IF NOT EXISTS SubmittedDate TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER Amount,
  ADD COLUMN IF NOT EXISTS ApprovedDate TIMESTAMP NULL DEFAULT NULL AFTER SubmittedDate,
  ADD COLUMN IF NOT EXISTS Status VARCHAR(50) DEFAULT 'Pending' AFTER ApprovedDate,
  ADD COLUMN IF NOT EXISTS Comments TEXT NULL AFTER Status,
  ADD COLUMN IF NOT EXISTS ApprovedBy INT NULL AFTER Comments,
  ADD INDEX IF NOT EXISTS idx_employee (EmployeeID),
  ADD INDEX IF NOT EXISTS idx_claim_number (ClaimNumber),
  ADD INDEX IF NOT EXISTS idx_status (Status);

-- End of HMO schema and seed
