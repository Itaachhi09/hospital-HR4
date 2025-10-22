-- ============================================================================
-- HR4 HOSPITAL SYSTEM - COMPLETE SAMPLE DATA IMPORT
-- This combines all sample data in one file for easy import
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- STEP 1: Check current state
-- ============================================================================

SELECT '🔍 Checking existing employees...' as Status;
SELECT EmployeeID, CONCAT(FirstName, ' ', LastName) as Name, Email, JobTitle
FROM employees
WHERE EmployeeID BETWEEN 9001 AND 9005;

SELECT '' as '';

-- ============================================================================
-- STEP 2: Ensure employees exist (skip if already there)
-- ============================================================================

INSERT IGNORE INTO `employees` (
    `EmployeeID`, `FirstName`, `MiddleName`, `LastName`, `Suffix`,
    `Email`, `PersonalEmail`, `PhoneNumber`,
    `DateOfBirth`, `Gender`, `MaritalStatus`, `Nationality`,
    `AddressLine1`, `AddressLine2`, `City`, `StateProvince`, `PostalCode`, `Country`,
    `EmergencyContactName`, `EmergencyContactRelationship`, `EmergencyContactPhone`,
    `HireDate`, `JobTitle`, `DepartmentID`, `ManagerID`, `IsActive`
) VALUES
(9001, 'Maria', 'Santos', 'Cruz', 'MD',
 'emp9001.maria.cruz@hospital.gov.ph', 'mariacruz2024@gmail.com', '+63917-555-9001',
 '1985-03-15', 'Female', 'Married', 'Filipino',
 '123 Medical Drive', 'Barangay Poblacion', 'Quezon City', 'Metro Manila', '1100', 'Philippines',
 'Roberto Cruz', 'Spouse', '+63917-555-1002',
 '2020-01-15', 'Senior Physician II - Cardiology', 10, NULL, 1),

(9002, 'Jennifer', 'Reyes', 'Bautista', 'RN',
 'emp9002.jennifer.bautista@hospital.gov.ph', 'jenbautista2024@yahoo.com', '+63917-555-9002',
 '1992-08-22', 'Female', 'Single', 'Filipino',
 '456 Healing Street', 'Barangay San Roque', 'Marikina City', 'Metro Manila', '1800', 'Philippines',
 'Rosa Bautista', 'Mother', '+63917-555-1004',
 '2021-06-01', 'Nurse III - ICU', 11, NULL, 1),

(9003, 'Carlos', 'Antonio', 'Mendoza', NULL,
 'emp9003.carlos.mendoza@hospital.gov.ph', 'carlmendoza2024@gmail.com', '+63917-555-9003',
 '1988-11-30', 'Male', 'Married', 'Filipino',
 '789 Admin Avenue', 'Barangay Capitol', 'Pasig City', 'Metro Manila', '1600', 'Philippines',
 'Anna Mendoza', 'Spouse', '+63917-555-1006',
 '2019-03-10', 'Administrative Officer III', 2, NULL, 1),

(9004, 'Patricia', 'Anne', 'Villanueva', 'RMT',
 'emp9004.patricia.villanueva@hospital.gov.ph', 'patriciavillanueva2024@gmail.com', '+63917-555-9004',
 '1995-02-14', 'Female', 'Single', 'Filipino',
 '321 Laboratory Lane', 'Barangay Health Zone', 'Mandaluyong City', 'Metro Manila', '1550', 'Philippines',
 'Teresa Villanueva', 'Mother', '+63917-555-1008',
 '2022-05-15', 'Medical Technologist II', 13, NULL, 1),

(9005, 'Michelle', 'Rose', 'Garcia', NULL,
 'emp9005.michelle.garcia@hospital.gov.ph', 'michellegarcia2024@yahoo.com', '+63917-555-9005',
 '1993-06-18', 'Female', 'Single', 'Filipino',
 '567 HR Plaza', 'Barangay Personnel', 'Taguig City', 'Metro Manila', '1630', 'Philippines',
 'Rosario Garcia', 'Mother', '+63917-555-1010',
 '2021-02-01', 'Human Resource Officer II', 2, NULL, 1);

-- ============================================================================
-- STEP 3: PAYROLL - SALARY RECORDS
-- ============================================================================

INSERT IGNORE INTO `employeesalaries` (
    `EmployeeID`, `BaseSalary`, `PayFrequency`, `PayRate`, `EffectiveDate`, `IsCurrent`
) VALUES
(9001, 90078.00, 'Monthly', NULL, '2024-01-01', 1),
(9002, 51357.00, 'Monthly', NULL, '2024-01-01', 1),
(9003, 36619.00, 'Monthly', NULL, '2024-01-01', 1),
(9004, 43030.00, 'Monthly', NULL, '2024-01-01', 1),
(9005, 39672.00, 'Monthly', NULL, '2024-01-01', 1);

-- ============================================================================
-- STEP 4: PAYROLL - BONUSES
-- ============================================================================

DELETE FROM `bonuses` WHERE `EmployeeID` BETWEEN 9001 AND 9005;

INSERT INTO `bonuses` (
    `EmployeeID`, `BonusType`, `BonusAmount`, `AwardDate`, `PayrollID`, `Status`
) VALUES
-- Dr. Cruz bonuses
(9001, '13th Month Pay', 90078.00, '2024-12-15', NULL, 'Paid'),
(9001, 'Holiday Bonus', 15000.00, '2024-12-20', NULL, 'Paid'),
(9001, 'Mid-Year Bonus', 45039.00, '2024-06-15', NULL, 'Paid'),

-- Nurse Bautista bonuses
(9002, '13th Month Pay', 51357.00, '2024-12-15', NULL, 'Paid'),
(9002, 'Holiday Bonus', 8000.00, '2024-12-20', NULL, 'Paid'),
(9002, 'Hazard Pay', 5000.00, '2024-09-30', NULL, 'Paid'),

-- Admin Mendoza bonuses
(9003, '13th Month Pay', 36619.00, '2024-12-15', NULL, 'Paid'),
(9003, 'Holiday Bonus', 5000.00, '2024-12-20', NULL, 'Paid'),

-- Med Tech Villanueva bonuses
(9004, '13th Month Pay', 43030.00, '2024-12-15', NULL, 'Paid'),
(9004, 'Holiday Bonus', 6000.00, '2024-12-20', NULL, 'Paid'),
(9004, 'Mid-Year Bonus', 21515.00, '2024-06-15', NULL, 'Paid'),

-- HR Garcia bonuses
(9005, '13th Month Pay', 39672.00, '2024-12-15', NULL, 'Paid'),
(9005, 'Holiday Bonus', 6000.00, '2024-12-20', NULL, 'Paid'),
(9005, 'Project Completion', 5000.00, '2024-07-30', NULL, 'Paid');

-- ============================================================================
-- STEP 5: PAYROLL - DEDUCTIONS
-- ============================================================================

DELETE FROM `deductions` WHERE `EmployeeID` BETWEEN 9001 AND 9005;

INSERT INTO `deductions` (
    `EmployeeID`, `DeductionType`, `DeductionAmount`, `DeductionDate`, 
    `PayrollID`, `Status`
) VALUES
-- Dr. Cruz deductions (October 2024)
(9001, 'SSS', 1350.00, '2024-10-15', NULL, 'Completed'),
(9001, 'PhilHealth', 1801.56, '2024-10-15', NULL, 'Completed'),
(9001, 'Pag-IBIG', 200.00, '2024-10-15', NULL, 'Completed'),
(9001, 'Withholding Tax', 18765.00, '2024-10-15', NULL, 'Completed'),

-- Nurse Bautista deductions (October 2024)
(9002, 'SSS', 1350.00, '2024-10-15', NULL, 'Completed'),
(9002, 'PhilHealth', 1027.14, '2024-10-15', NULL, 'Completed'),
(9002, 'Pag-IBIG', 200.00, '2024-10-15', NULL, 'Completed'),
(9002, 'Withholding Tax', 7856.00, '2024-10-15', NULL, 'Completed'),

-- Admin Mendoza deductions (October 2024)
(9003, 'SSS', 1350.00, '2024-10-15', NULL, 'Completed'),
(9003, 'PhilHealth', 732.38, '2024-10-15', NULL, 'Completed'),
(9003, 'Pag-IBIG', 200.00, '2024-10-15', NULL, 'Completed'),
(9003, 'Withholding Tax', 4235.00, '2024-10-15', NULL, 'Completed'),

-- Med Tech Villanueva deductions (October 2024)
(9004, 'SSS', 1350.00, '2024-10-15', NULL, 'Completed'),
(9004, 'PhilHealth', 860.60, '2024-10-15', NULL, 'Completed'),
(9004, 'Pag-IBIG', 200.00, '2024-10-15', NULL, 'Completed'),
(9004, 'Withholding Tax', 5678.00, '2024-10-15', NULL, 'Completed'),

-- HR Garcia deductions (October 2024)
(9005, 'SSS', 1350.00, '2024-10-15', NULL, 'Completed'),
(9005, 'PhilHealth', 793.44, '2024-10-15', NULL, 'Completed'),
(9005, 'Pag-IBIG', 200.00, '2024-10-15', NULL, 'Completed'),
(9005, 'Withholding Tax', 5234.00, '2024-10-15', NULL, 'Completed');

-- ============================================================================
-- STEP 6: PAYROLL - PAYSLIPS
-- ============================================================================

DELETE FROM `payslips` WHERE `EmployeeID` BETWEEN 9001 AND 9005;

INSERT INTO `payslips` (
    `EmployeeID`, `PayPeriodStart`, `PayPeriodEnd`, 
    `GrossPay`, `NetPay`, `TotalDeductions`, `TotalBonuses`,
    `Status`, `GeneratedDate`
) VALUES
-- October 2024 payslips
(9001, '2024-10-01', '2024-10-15', 90078.00, 66761.44, 23316.56, 0.00, 'Released', '2024-10-15'),
(9002, '2024-10-01', '2024-10-15', 51357.00, 40423.86, 10933.14, 0.00, 'Released', '2024-10-15'),
(9003, '2024-10-01', '2024-10-15', 36619.00, 29301.62, 7317.38, 0.00, 'Released', '2024-10-15'),
(9004, '2024-10-01', '2024-10-15', 43030.00, 33491.40, 9538.60, 0.00, 'Released', '2024-10-15'),
(9005, '2024-10-01', '2024-10-15', 39672.00, 31594.56, 8077.44, 0.00, 'Released', '2024-10-15'),

-- September 2024 payslips
(9001, '2024-09-01', '2024-09-15', 90078.00, 66761.44, 23316.56, 0.00, 'Released', '2024-09-15'),
(9002, '2024-09-01', '2024-09-15', 51357.00, 40423.86, 10933.14, 0.00, 'Released', '2024-09-15'),
(9003, '2024-09-01', '2024-09-15', 36619.00, 29301.62, 7317.38, 0.00, 'Released', '2024-09-15'),
(9004, '2024-09-01', '2024-09-15', 43030.00, 33491.40, 9538.60, 0.00, 'Released', '2024-09-15'),
(9005, '2024-09-01', '2024-09-15', 39672.00, 31594.56, 8077.44, 0.00, 'Released', '2024-09-15');

-- ============================================================================
-- STEP 7: HMO ENROLLMENTS (if not already there)
-- ============================================================================

INSERT IGNORE INTO `employeehmoenrollments` (
    `EmployeeID`, `PlanID`, `Status`, `MonthlyDeduction`, 
    `EnrollmentDate`, `EffectiveDate`
) VALUES
(9001, 2, 'Active', 1200.00, '2020-02-01', '2020-02-01'),
(9002, 4, 'Active', 500.00, '2021-07-01', '2021-07-01'),
(9003, 3, 'Active', 800.00, '2019-04-01', '2019-04-01'),
(9004, 7, 'Active', 450.00, '2022-06-01', '2022-06-01'),
(9005, 5, 'Active', 500.00, '2021-03-01', '2021-03-01');

-- ============================================================================
-- STEP 8: HR CORE DOCUMENTS (if not already there)
-- ============================================================================

DELETE FROM `hrcore_documents` WHERE `emp_id` BETWEEN 9001 AND 9005;

INSERT INTO `hrcore_documents` (
    `emp_id`, `module_origin`, `category`, `title`, `file_path`, 
    `file_type`, `file_size`, `uploaded_by`, `status`
) VALUES
-- Dr. Cruz documents
(9001, 'HR1', 'A', 'PRC License - Physician', '/uploads/hr1/licenses/cruz_prc_md_2020.pdf', 'application/pdf', 245680, 'HR Admin', 'active'),
(9001, 'HR1', 'A', 'Diploma - Doctor of Medicine', '/uploads/hr1/education/cruz_md_diploma.pdf', 'application/pdf', 512000, 'HR Admin', 'active'),
(9001, 'HR2', 'C', 'ACLS Certificate', '/uploads/hr2/training/cruz_acls_2020.pdf', 'application/pdf', 234567, 'Training Dept', 'active'),
(9001, 'HR2', 'C', 'Performance Evaluation 2024', '/uploads/hr2/performance/cruz_perf_2024.pdf', 'application/pdf', 312456, 'HR Manager', 'active'),

-- Nurse Bautista documents
(9002, 'HR1', 'A', 'PRC License - Registered Nurse', '/uploads/hr1/licenses/bautista_prc_rn_2021.pdf', 'application/pdf', 198765, 'HR Admin', 'active'),
(9002, 'HR1', 'A', 'Diploma - BSN', '/uploads/hr1/education/bautista_bsn_diploma.pdf', 'application/pdf', 456789, 'HR Admin', 'active'),
(9002, 'HR2', 'C', 'BLS Certification', '/uploads/hr2/training/bautista_bls_2021.pdf', 'application/pdf', 178432, 'Training Dept', 'active'),
(9002, 'HR2', 'C', 'ICU Nursing Certificate', '/uploads/hr2/training/bautista_icu_nursing.pdf', 'application/pdf', 289456, 'Training Dept', 'active'),

-- Admin Mendoza documents
(9003, 'HR1', 'A', 'Diploma - BPA', '/uploads/hr1/education/mendoza_bpa_diploma.pdf', 'application/pdf', 398765, 'HR Admin', 'active'),
(9003, 'HR2', 'C', 'Records Management Training', '/uploads/hr2/training/mendoza_records_mgmt.pdf', 'application/pdf', 245678, 'Training Dept', 'active'),

-- Med Tech Villanueva documents
(9004, 'HR1', 'A', 'PRC License - RMT', '/uploads/hr1/licenses/villanueva_prc_rmt_2022.pdf', 'application/pdf', 212345, 'HR Admin', 'active'),
(9004, 'HR1', 'A', 'Diploma - BSMT', '/uploads/hr1/education/villanueva_bsmt_diploma.pdf', 'application/pdf', 478901, 'HR Admin', 'active'),
(9004, 'HR2', 'C', 'Laboratory Safety Certification', '/uploads/hr2/training/villanueva_lab_safety.pdf', 'application/pdf', 198765, 'Training Dept', 'active'),

-- HR Garcia documents
(9005, 'HR1', 'A', 'Diploma - BS Psychology', '/uploads/hr1/education/garcia_bspsych_diploma.pdf', 'application/pdf', 412345, 'HR Admin', 'active'),
(9005, 'HR2', 'C', 'Labor Law Training', '/uploads/hr2/training/garcia_labor_law.pdf', 'application/pdf', 345678, 'Training Dept', 'active'),
(9005, 'HR2', 'C', 'Payroll Administration Certification', '/uploads/hr2/training/garcia_payroll_admin.pdf', 'application/pdf', 267890, 'Training Dept', 'active');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '✅ ============================================================' as '';
SELECT '✅ ALL SAMPLE DATA IMPORTED!' as Status;
SELECT '✅ ============================================================' as '';

SELECT '' as '';
SELECT '👥 EMPLOYEES:' as Info;
SELECT 
    EmployeeID,
    CONCAT(FirstName, ' ', LastName) as Name,
    JobTitle,
    Email
FROM employees
WHERE EmployeeID BETWEEN 9001 AND 9005
ORDER BY EmployeeID;

SELECT '' as '';
SELECT '💰 SALARIES:' as Info;
SELECT 
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) as Name,
    CONCAT('₱', FORMAT(s.BaseSalary, 2)) as Salary
FROM employees e
JOIN employeesalaries s ON e.EmployeeID = s.EmployeeID
WHERE e.EmployeeID BETWEEN 9001 AND 9005;

SELECT '' as '';
SELECT '💵 BONUSES:' as Info;
SELECT 
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) as Name,
    COUNT(b.BonusID) as 'Total Bonuses',
    CONCAT('₱', FORMAT(SUM(b.BonusAmount), 2)) as 'Total Amount'
FROM employees e
LEFT JOIN bonuses b ON e.EmployeeID = b.EmployeeID
WHERE e.EmployeeID BETWEEN 9001 AND 9005
GROUP BY e.EmployeeID;

SELECT '' as '';
SELECT '➖ DEDUCTIONS:' as Info;
SELECT 
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) as Name,
    COUNT(d.DeductionID) as 'Total Deductions',
    CONCAT('₱', FORMAT(SUM(d.DeductionAmount), 2)) as 'Total Amount'
FROM employees e
LEFT JOIN deductions d ON e.EmployeeID = d.EmployeeID
WHERE e.EmployeeID BETWEEN 9001 AND 9005
GROUP BY e.EmployeeID;

SELECT '' as '';
SELECT '📄 PAYSLIPS:' as Info;
SELECT 
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) as Name,
    COUNT(p.PayslipID) as 'Total Payslips'
FROM employees e
LEFT JOIN payslips p ON e.EmployeeID = p.EmployeeID
WHERE e.EmployeeID BETWEEN 9001 AND 9005
GROUP BY e.EmployeeID;

SELECT '' as '';
SELECT '📊 SUMMARY:' as Info;
SELECT 
    'Employees' as Item, COUNT(DISTINCT e.EmployeeID) as Count
FROM employees e WHERE e.EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'Salaries', COUNT(*) FROM employeesalaries WHERE EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'Bonuses', COUNT(*) FROM bonuses WHERE EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'Deductions', COUNT(*) FROM deductions WHERE EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'Payslips', COUNT(*) FROM payslips WHERE EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'HMO Enrollments', COUNT(*) FROM employeehmoenrollments WHERE EmployeeID BETWEEN 9001 AND 9005
UNION ALL
SELECT 'Documents', COUNT(*) FROM hrcore_documents WHERE emp_id BETWEEN 9001 AND 9005;

SELECT '' as '';
SELECT '✅ ============================================================' as '';
SELECT '✅ You can now refresh your Payroll pages!' as Message;
SELECT '✅ All data should appear in Salaries, Bonuses, Deductions, Payslips' as Modules;
SELECT '✅ ============================================================' as '';

-- ============================================================================

