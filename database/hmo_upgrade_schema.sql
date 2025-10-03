-- Upgraded HMO & Benefits schema for hr_integrated_db
-- Safe to run multiple times

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `HMOProviders` (
  `ProviderID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderName` varchar(255) NOT NULL,
  `CompanyName` varchar(255) DEFAULT NULL,
  `ContactPerson` varchar(255) DEFAULT NULL,
  `ContactEmail` varchar(255) DEFAULT NULL,
  `ContactPhone` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `PhoneNumber` varchar(50) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Website` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ProviderID`),
  KEY `idx_hmo_providers_active` (`IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `HMOPlans` (
  `PlanID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `PlanName` varchar(255) NOT NULL,
  `PlanCode` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `MonthlyPremium` decimal(10,2) NOT NULL DEFAULT 0.00,
  `AnnualLimit` decimal(12,2) DEFAULT NULL,
  `MaximumBenefitLimit` decimal(12,2) DEFAULT NULL,
  `EligibilityRequirements` text DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `EffectiveDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`PlanID`),
  UNIQUE KEY `plan_code_unique` (`PlanCode`),
  KEY `fk_hmo_plans_provider` (`ProviderID`),
  CONSTRAINT `fk_hmo_plans_provider` FOREIGN KEY (`ProviderID`) REFERENCES `HMOProviders` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `EmployeeHMOEnrollments` (
  `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT,
  `EmployeeID` int(11) NOT NULL,
  `PlanID` int(11) NOT NULL,
  `Status` enum('Active','Inactive','Suspended','Pending') NOT NULL DEFAULT 'Active',
  `MonthlyDeduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `EnrollmentDate` date NOT NULL,
  `EffectiveDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `DependentInfo` longtext DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`EnrollmentID`),
  KEY `fk_hmo_enrollments_employee` (`EmployeeID`),
  KEY `fk_hmo_enrollments_plan` (`PlanID`),
  CONSTRAINT `fk_hmo_enrollments_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hmo_enrollments_plan` FOREIGN KEY (`PlanID`) REFERENCES `HMOPlans` (`PlanID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `HMOClaims` (
  `ClaimID` int(11) NOT NULL AUTO_INCREMENT,
  `EnrollmentID` int(11) NOT NULL,
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
  `ReceiptPath` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ClaimID`),
  UNIQUE KEY `claim_number_unique` (`ClaimNumber`),
  KEY `fk_hmo_claims_enrollment` (`EnrollmentID`),
  KEY `fk_hmo_claims_approver` (`ApprovedBy`),
  CONSTRAINT `fk_hmo_claims_enrollment` FOREIGN KEY (`EnrollmentID`) REFERENCES `EmployeeHMOEnrollments` (`EnrollmentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hmo_claims_approver` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

SET FOREIGN_KEY_CHECKS=1;


