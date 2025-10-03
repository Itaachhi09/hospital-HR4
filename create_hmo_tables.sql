-- HMO & Benefits Module Database Tables
-- These tables are required for the HMO module to function properly

-- Create HMO Providers table
CREATE TABLE IF NOT EXISTS `HMOProviders` (
  `ProviderID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderName` varchar(255) NOT NULL,
  `CompanyName` varchar(255) DEFAULT NULL,
  `ContactPerson` varchar(255) DEFAULT NULL,
  `ContactEmail` varchar(255) DEFAULT NULL,
  `ContactPhone` varchar(50) DEFAULT NULL,
  `PhoneNumber` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Website` varchar(255) DEFAULT NULL,
  `Logo` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `EstablishedYear` year DEFAULT NULL,
  `AccreditationNumber` varchar(100) DEFAULT NULL,
  `ServiceAreas` text DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ProviderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create HMO Plans table
CREATE TABLE IF NOT EXISTS `HMOPlans` (
  `PlanID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `PlanName` varchar(255) NOT NULL,
  `PlanCode` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CoverageType` varchar(100) DEFAULT 'Comprehensive',
  `PlanCategory` enum('Individual','Family','Corporate','Employee') DEFAULT 'Individual',
  `MonthlyPremium` decimal(10,2) NOT NULL,
  `AnnualLimit` decimal(12,2) DEFAULT NULL,
  `MaximumBenefitLimit` decimal(12,2) DEFAULT NULL,
  `RoomAndBoardLimit` decimal(10,2) DEFAULT NULL,
  `DoctorVisitLimit` decimal(10,2) DEFAULT NULL,
  `EmergencyLimit` decimal(10,2) DEFAULT NULL,
  `OutpatientLimit` decimal(10,2) DEFAULT NULL,
  `InpatientLimit` decimal(10,2) DEFAULT NULL,
  `MaternityLimit` decimal(10,2) DEFAULT NULL,
  `DentalLimit` decimal(10,2) DEFAULT NULL,
  `PreventiveCareLimit` decimal(10,2) DEFAULT NULL,
  `CoverageInpatient` tinyint(1) DEFAULT 1,
  `CoverageOutpatient` tinyint(1) DEFAULT 1,
  `CoverageEmergency` tinyint(1) DEFAULT 1,
  `CoveragePreventive` tinyint(1) DEFAULT 1,
  `CoverageMaternity` tinyint(1) DEFAULT 0,
  `CoverageDental` tinyint(1) DEFAULT 0,
  `CoverageOptical` tinyint(1) DEFAULT 0,
  `AccreditedHospitals` longtext DEFAULT NULL,
  `ExclusionsLimitations` text DEFAULT NULL,
  `EligibilityRequirements` text DEFAULT NULL,
  `WaitingPeriod` varchar(100) DEFAULT NULL,
  `CashlessLimit` decimal(10,2) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `EffectiveDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`PlanID`),
  UNIQUE KEY `plan_code_unique` (`PlanCode`),
  KEY `fk_hmo_plans_provider` (`ProviderID`),
  CONSTRAINT `fk_hmo_plans_provider` FOREIGN KEY (`ProviderID`) REFERENCES `HMOProviders` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create Employee HMO Enrollments table
CREATE TABLE IF NOT EXISTS `EmployeeHMOEnrollments` (
  `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `PlanID` int(11) NOT NULL,
  `Status` enum('Active','Inactive','Suspended','Pending') NOT NULL DEFAULT 'Active',
  `MonthlyDeduction` decimal(10,2) NOT NULL,
  `EnrollmentDate` date NOT NULL,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`EnrollmentID`),
  KEY `fk_hmo_enrollments_employee` (`EmployeeID`),
  KEY `fk_hmo_enrollments_plan` (`PlanID`),
  CONSTRAINT `fk_hmo_enrollments_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hmo_enrollments_plan` FOREIGN KEY (`PlanID`) REFERENCES `HMOPlans` (`PlanID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create HMO Claims table
CREATE TABLE IF NOT EXISTS `HMOClaims` (
  `ClaimID` int(11) NOT NULL AUTO_INCREMENT,
  `EnrollmentID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `ClaimNumber` varchar(50) UNIQUE NOT NULL,
  `ClaimType` varchar(100) NOT NULL,
  `ProviderName` varchar(255) DEFAULT NULL,
  `Description` text NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `ClaimDate` date NOT NULL,
  `SubmittedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `ApprovedDate` timestamp NULL DEFAULT NULL,
  `Status` enum('Submitted','Under Review','Approved','Rejected','Paid') NOT NULL DEFAULT 'Submitted',
  `Comments` text DEFAULT NULL,
  `ApprovedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ClaimID`),
  UNIQUE KEY `claim_number_unique` (`ClaimNumber`),
  KEY `fk_hmo_claims_enrollment` (`EnrollmentID`),
  KEY `fk_hmo_claims_employee` (`EmployeeID`),
  KEY `fk_hmo_claims_approver` (`ApprovedBy`),
  CONSTRAINT `fk_hmo_claims_enrollment` FOREIGN KEY (`EnrollmentID`) REFERENCES `EmployeeHMOEnrollments` (`EnrollmentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hmo_claims_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hmo_claims_approver` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Top 7 Philippine HMO Providers
INSERT INTO `HMOProviders` (`ProviderName`, `CompanyName`, `ContactPerson`, `ContactEmail`, `ContactPhone`, `PhoneNumber`, `Email`, `Address`, `Website`, `Description`, `EstablishedYear`, `AccreditationNumber`, `ServiceAreas`, `IsActive`) VALUES

-- Maxicare Healthcare Corporation
('Maxicare', 'Maxicare Healthcare Corporation', 'Customer Service Manager', 'customercare@maxicare.com.ph', '+63-2-8711-9000', '+63-2-8711-9000', 'info@maxicare.com.ph', '7th Floor, The Enterprise Center, Tower 1, 6766 Ayala Avenue corner Paseo de Roxas, Makati City', 'https://www.maxicare.com.ph', 'Leading HMO provider in the Philippines with comprehensive healthcare coverage', 1987, 'DOH-LTO-HMO-001', 'Metro Manila, Cebu, Davao, Bacolod, Iloilo, Cagayan de Oro, Baguio', 1),

-- Medicard Philippines  
('Medicard', 'Medicard Philippines, Inc.', 'Customer Relations Head', 'customercare@medicard.com.ph', '+63-2-8985-9999', '+63-2-8985-9999', 'info@medicard.com.ph', '2nd Floor, Prestige Tower, F. Ortigas Jr. Road, Ortigas Center, Pasig City', 'https://www.medicard.com.ph', 'Premier healthcare provider offering innovative medical services and HMO plans', 1982, 'DOH-LTO-HMO-002', 'Metro Manila, Laguna, Cavite, Rizal, Bulacan, Pampanga, Bataan', 1),

-- Intellicare (Asalus Corp.)
('Intellicare', 'Asalus Corporation', 'Client Services Director', 'customerservice@intellicare.com.ph', '+63-2-8894-7777', '+63-2-8894-7777', 'info@intellicare.com.ph', '6th Floor, Tower One & Exchange Plaza, Ayala Triangle, Ayala Avenue, Makati City', 'https://www.intellicare.com.ph', 'Flexible healthcare solutions with personalized medical care programs', 1997, 'DOH-LTO-HMO-003', 'Metro Manila, Cebu, Davao, Baguio, Clark, Subic', 1),

-- PhilCare (PhilHealthCare, Inc.)
('PhilCare', 'PhilHealthCare, Inc.', 'Operations Manager', 'customercare@philcare.com.ph', '+63-2-8638-9999', '+63-2-8638-9999', 'info@philcare.com.ph', '15th Floor, Ayala Life-FGU Center, 6811 Ayala Avenue, Makati City', 'https://www.philcare.com.ph', 'Comprehensive healthcare management with focus on preventive care and wellness', 1994, 'DOH-LTO-HMO-004', 'Metro Manila, Cebu, Davao, Iloilo, Bacolod, Dumaguete', 1),

-- Kaiser International Health Group
('Kaiser', 'Kaiser International Health Group, Inc.', 'Account Manager', 'info@kaiser.com.ph', '+63-2-8892-2222', '+63-2-8892-2222', 'customerservice@kaiser.com.ph', '26th Floor, Petron Megaplaza, 358 Senator Gil Puyat Avenue, Makati City', 'https://www.kaiser.com.ph', 'International standard healthcare coverage with global network partnerships', 1993, 'DOH-LTO-HMO-005', 'Metro Manila, Cebu, Davao, Clark, Baguio', 1),

-- Insular Health Care
('Insular Health Care', 'Insular Health Care, Inc.', 'Client Relations Manager', 'customercare@insularhealthcare.com.ph', '+63-2-8818-9999', '+63-2-8818-9999', 'info@insularhealthcare.com.ph', '21st Floor, Insular Life Building, Ayala Avenue corner Paseo de Roxas, Makati City', 'https://www.insularhealthcare.com.ph', 'Comprehensive health maintenance organization with extensive provider network', 1990, 'DOH-LTO-HMO-006', 'Metro Manila, Laguna, Cavite, Batangas, Rizal, Bulacan', 1),

-- Value Care (ValuCare)
('ValuCare', 'Value Care Health Systems, Inc.', 'Customer Support Head', 'customerservice@valucare.com.ph', '+63-2-8756-8888', '+63-2-8756-8888', 'info@valucare.com.ph', '12th Floor, Orient Square Building, F. Ortigas Jr. Road, Ortigas Center, Pasig City', 'https://www.valucare.com.ph', 'Affordable healthcare solutions with quality medical services for all', 1996, 'DOH-LTO-HMO-007', 'Metro Manila, Central Luzon, CALABARZON, Cebu, Davao', 1);

-- Insert Comprehensive HMO Plans for Top 7 Providers
INSERT INTO `HMOPlans` (`ProviderID`, `PlanName`, `PlanCode`, `Description`, `CoverageType`, `PlanCategory`, `MonthlyPremium`, `MaximumBenefitLimit`, `AnnualLimit`, `RoomAndBoardLimit`, `OutpatientLimit`, `InpatientLimit`, `EmergencyLimit`, `MaternityLimit`, `DentalLimit`, `PreventiveCareLimit`, `CoverageInpatient`, `CoverageOutpatient`, `CoverageEmergency`, `CoveragePreventive`, `CoverageMaternity`, `CoverageDental`, `AccreditedHospitals`, `EligibilityRequirements`, `WaitingPeriod`, `CashlessLimit`, `IsActive`, `EffectiveDate`) VALUES

-- MAXICARE PLANS
(1, 'Maxicare Individual', 'MXI-IND', 'Individual healthcare coverage with comprehensive benefits', 'Comprehensive', 'Individual', 3500.00, 1000000.00, 1000000.00, 5000.00, 50000.00, 300000.00, 50000.00, 100000.00, 25000.00, 15000.00, 1, 1, 1, 1, 1, 1, 
'["Asian Hospital and Medical Center", "St. Lukes Medical Center", "Makati Medical Center", "The Medical City", "Cardinal Santos Medical Center", "Manila Doctors Hospital", "Chinese General Hospital", "Fatima University Medical Center"]', 
'Age 0-65, Pre-existing conditions covered after 12 months', '30 days for accidents, 120 days for illnesses', 100000.00, 1, '2024-01-01'),

(1, 'Maxicare Family', 'MXF-FAM', 'Family healthcare plan covering up to 6 family members', 'Comprehensive', 'Family', 8500.00, 2000000.00, 2000000.00, 5000.00, 75000.00, 500000.00, 75000.00, 150000.00, 40000.00, 25000.00, 1, 1, 1, 1, 1, 1,
'["Asian Hospital and Medical Center", "St. Lukes Medical Center", "Makati Medical Center", "The Medical City", "Cardinal Santos Medical Center", "Manila Doctors Hospital", "Chinese General Hospital", "Fatima University Medical Center"]',
'Principal member age 21-60, dependents 0-65', '30 days for accidents, 120 days for illnesses', 150000.00, 1, '2024-01-01'),

(1, 'Maxicare Corporate', 'MXC-CORP', 'Corporate healthcare plan for employees and dependents', 'Comprehensive', 'Corporate', 2800.00, 800000.00, 800000.00, 4000.00, 40000.00, 250000.00, 40000.00, 80000.00, 20000.00, 12000.00, 1, 1, 1, 1, 1, 1,
'["Asian Hospital and Medical Center", "St. Lukes Medical Center", "Makati Medical Center", "The Medical City", "Cardinal Santos Medical Center", "Manila Doctors Hospital", "Chinese General Hospital", "Fatima University Medical Center"]',
'Minimum 5 employees, renewable annually', '30 days for accidents, 120 days for illnesses', 80000.00, 1, '2024-01-01'),

-- MEDICARD PLANS  
(2, 'Medicard Classic', 'MDC-CLS', 'Classic healthcare plan with essential medical coverage', 'Standard', 'Individual', 2800.00, 600000.00, 600000.00, 3500.00, 35000.00, 200000.00, 35000.00, 60000.00, 15000.00, 10000.00, 1, 1, 1, 1, 1, 0,
'["Manila Doctors Hospital", "University of Santo Tomas Hospital", "Ospital ng Makati", "Medical Center Manila", "Quirino Memorial Medical Center", "East Avenue Medical Center", "Lung Center of the Philippines"]',
'Age 0-65, medical examination required above 50', '60 days general waiting period', 75000.00, 1, '2024-01-01'),

(2, 'Medicard VIP', 'MDV-VIP', 'VIP healthcare plan with premium medical services', 'Premium', 'Individual', 5200.00, 1500000.00, 1500000.00, 7500.00, 100000.00, 500000.00, 100000.00, 200000.00, 50000.00, 30000.00, 1, 1, 1, 1, 1, 1,
'["Manila Doctors Hospital", "University of Santo Tomas Hospital", "Ospital ng Makati", "Medical Center Manila", "Quirino Memorial Medical Center", "East Avenue Medical Center", "Lung Center of the Philippines"]',
'Age 0-65, comprehensive medical examination', '60 days general, 12 months pre-existing', 200000.00, 1, '2024-01-01'),

(2, 'Medicard Corporate', 'MDC-CORP', 'Corporate healthcare solution for business organizations', 'Comprehensive', 'Corporate', 3200.00, 800000.00, 800000.00, 4000.00, 50000.00, 300000.00, 50000.00, 100000.00, 25000.00, 15000.00, 1, 1, 1, 1, 1, 1,
'["Manila Doctors Hospital", "University of Santo Tomas Hospital", "Ospital ng Makati", "Medical Center Manila", "Quirino Memorial Medical Center", "East Avenue Medical Center", "Lung Center of the Philippines"]',
'Minimum 10 employees, group application', '60 days general waiting period', 100000.00, 1, '2024-01-01'),

-- INTELLICARE PLANS
(3, 'Intellicare Flexicare', 'INT-FLEX', 'Flexible healthcare plan with customizable benefits', 'Flexible', 'Individual', 2200.00, 500000.00, 500000.00, 3000.00, 30000.00, 150000.00, 30000.00, 50000.00, 12000.00, 8000.00, 1, 1, 1, 1, 1, 0,
'["Veterans Memorial Medical Center", "Philippine Heart Center", "National Kidney and Transplant Institute", "Research Institute for Tropical Medicine", "Philippine Orthopedic Center", "Lung Center of the Philippines"]',
'Age 0-60, flexible payment terms', '45 days general waiting period', 60000.00, 1, '2024-01-01'),

(3, 'Intellicare Corporate Health', 'INT-CORP', 'Corporate health plan with comprehensive medical coverage', 'Comprehensive', 'Corporate', 2600.00, 700000.00, 700000.00, 3500.00, 40000.00, 200000.00, 40000.00, 70000.00, 18000.00, 12000.00, 1, 1, 1, 1, 1, 1,
'["Veterans Memorial Medical Center", "Philippine Heart Center", "National Kidney and Transplant Institute", "Research Institute for Tropical Medicine", "Philippine Orthopedic Center", "Lung Center of the Philippines"]',
'Minimum 8 employees, annual contract', '45 days general waiting period', 80000.00, 1, '2024-01-01'),

-- PHILCARE PLANS
(4, 'PhilCare Health PRO', 'PHC-PRO', 'Professional health plan with preventive care focus', 'Comprehensive', 'Individual', 3800.00, 1200000.00, 1200000.00, 6000.00, 60000.00, 400000.00, 60000.00, 120000.00, 30000.00, 20000.00, 1, 1, 1, 1, 1, 1,
'["Cebu Doctors University Hospital", "Chong Hua Hospital", "Vicente Sotto Memorial Medical Center", "Perpetual Succour Hospital", "Miller Sanitarium & Hospital", "Southwestern University Medical Center"]',
'Age 0-65, wellness program included', '90 days general, 12 months pre-existing', 120000.00, 1, '2024-01-01'),

(4, 'PhilCare ER Vantage', 'PHC-ERV', 'Emergency-focused plan with 24/7 coverage', 'Emergency', 'Individual', 1800.00, 300000.00, 300000.00, 2500.00, 15000.00, 100000.00, 50000.00, 30000.00, 8000.00, 5000.00, 1, 1, 1, 1, 0, 0,
'["Cebu Doctors University Hospital", "Chong Hua Hospital", "Vicente Sotto Memorial Medical Center", "Perpetual Succour Hospital", "Miller Sanitarium & Hospital", "Southwestern University Medical Center"]',
'Age 18-60, emergency response priority', '30 days general waiting period', 50000.00, 1, '2024-01-01'),

(4, 'PhilCare Corporate', 'PHC-CORP', 'Corporate wellness program with comprehensive benefits', 'Comprehensive', 'Corporate', 3200.00, 900000.00, 900000.00, 4500.00, 45000.00, 300000.00, 45000.00, 90000.00, 22000.00, 15000.00, 1, 1, 1, 1, 1, 1,
'["Cebu Doctors University Hospital", "Chong Hua Hospital", "Vicente Sotto Memorial Medical Center", "Perpetual Succour Hospital", "Miller Sanitarium & Hospital", "Southwestern University Medical Center"]',
'Minimum 15 employees, wellness programs', '90 days general waiting period', 100000.00, 1, '2024-01-01'),

-- KAISER PLANS
(5, 'Kaiser Ultimate Health Builder', 'KAI-UHB', 'Ultimate health plan with international coverage options', 'Premium', 'Individual', 6500.00, 2000000.00, 2000000.00, 10000.00, 150000.00, 800000.00, 150000.00, 300000.00, 75000.00, 50000.00, 1, 1, 1, 1, 1, 1,
'["Kaiser Medical Center", "Metropolitan Medical Center", "De Los Santos Medical Center", "World Citi Medical Center", "New World Diagnostics", "Capitol Medical Center"]',
'Age 0-70, international network access', '120 days general, 24 months pre-existing', 300000.00, 1, '2024-01-01'),

(5, 'Kaiser Corporate', 'KAI-CORP', 'Corporate health plan with global network partnerships', 'Comprehensive', 'Corporate', 4200.00, 1200000.00, 1200000.00, 6000.00, 80000.00, 400000.00, 80000.00, 150000.00, 40000.00, 25000.00, 1, 1, 1, 1, 1, 1,
'["Kaiser Medical Center", "Metropolitan Medical Center", "De Los Santos Medical Center", "World Citi Medical Center", "New World Diagnostics", "Capitol Medical Center"]',
'Minimum 20 employees, international coverage', '120 days general waiting period', 150000.00, 1, '2024-01-01'),

-- INSULAR HEALTH CARE PLANS
(6, 'Insular iCare', 'IHC-ICR', 'Individual care plan with comprehensive medical benefits', 'Comprehensive', 'Individual', 2900.00, 750000.00, 750000.00, 4000.00, 45000.00, 250000.00, 45000.00, 80000.00, 20000.00, 12000.00, 1, 1, 1, 1, 1, 1,
'["Makati Medical Center", "Asian Hospital and Medical Center", "Capitol Medical Center", "Medical Center Manila", "Dela Salle University Medical Center", "San Juan de Dios Hospital"]',
'Age 0-65, family-friendly benefits', '75 days general waiting period', 90000.00, 1, '2024-01-01'),

(6, 'Insular Corporate Care', 'IHC-CORP', 'Corporate care solution for medium to large enterprises', 'Comprehensive', 'Corporate', 2400.00, 600000.00, 600000.00, 3500.00, 35000.00, 200000.00, 35000.00, 60000.00, 15000.00, 10000.00, 1, 1, 1, 1, 1, 1,
'["Makati Medical Center", "Asian Hospital and Medical Center", "Capitol Medical Center", "Medical Center Manila", "Dela Salle University Medical Center", "San Juan de Dios Hospital"]',
'Minimum 12 employees, flexible terms', '75 days general waiting period', 70000.00, 1, '2024-01-01'),

-- VALUCARE PLANS
(7, 'ValuCare Individual', 'VAL-IND', 'Affordable individual healthcare plan with essential benefits', 'Basic', 'Individual', 1500.00, 300000.00, 300000.00, 2000.00, 20000.00, 100000.00, 20000.00, 30000.00, 8000.00, 5000.00, 1, 1, 1, 1, 1, 0,
'["Dr. Jose Fabella Memorial Hospital", "Jose Reyes Memorial Medical Center", "Tondo Medical Center", "Pasig City General Hospital", "Marikina Valley Medical Center", "Rizal Medical Center"]',
'Age 0-60, affordable premium payments', '60 days general waiting period', 40000.00, 1, '2024-01-01'),

(7, 'ValuCare Family', 'VAL-FAM', 'Family healthcare plan with value-for-money benefits', 'Standard', 'Family', 4200.00, 800000.00, 800000.00, 2500.00, 35000.00, 200000.00, 35000.00, 60000.00, 15000.00, 10000.00, 1, 1, 1, 1, 1, 1,
'["Dr. Jose Fabella Memorial Hospital", "Jose Reyes Memorial Medical Center", "Tondo Medical Center", "Pasig City General Hospital", "Marikina Valley Medical Center", "Rizal Medical Center"]',
'Principal member 21-55, up to 5 dependents', '60 days general waiting period', 60000.00, 1, '2024-01-01'),

(7, 'ValuCare Corporate', 'VAL-CORP', 'Corporate healthcare solution for small to medium enterprises', 'Standard', 'Corporate', 1800.00, 400000.00, 400000.00, 2000.00, 25000.00, 120000.00, 25000.00, 40000.00, 10000.00, 6000.00, 1, 1, 1, 1, 1, 0,
'["Dr. Jose Fabella Memorial Hospital", "Jose Reyes Memorial Medical Center", "Tondo Medical Center", "Pasig City General Hospital", "Marikina Valley Medical Center", "Rizal Medical Center"]',
'Minimum 5 employees, cost-effective', '60 days general waiting period', 50000.00, 1, '2024-01-01');

-- Create notifications table if it doesn't exist (for HMO notifications)
CREATE TABLE IF NOT EXISTS `hmo_notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `fk_hmo_notifications_employee` (`EmployeeID`),
  CONSTRAINT `fk_hmo_notifications_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
