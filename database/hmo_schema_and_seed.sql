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

-- End of HMO schema and seed
