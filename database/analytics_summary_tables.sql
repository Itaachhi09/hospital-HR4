-- Analytics Summary Tables for HR4 Hospital System
-- Pre-aggregated data storage for performance optimization

-- Create analytics_headcount_summary table
CREATE TABLE IF NOT EXISTS `analytics_headcount_summary` (
  `SummaryID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportDate` date NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `EmploymentType` enum('Full-time','Part-time','Contract','Intern') DEFAULT NULL,
  `TotalHeadcount` int(11) NOT NULL DEFAULT 0,
  `NewHires` int(11) NOT NULL DEFAULT 0,
  `Separations` int(11) NOT NULL DEFAULT 0,
  `NetChange` int(11) NOT NULL DEFAULT 0,
  `TurnoverRate` decimal(5,2) DEFAULT NULL,
  `RetentionRate` decimal(5,2) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`SummaryID`),
  KEY `idx_report_date` (`ReportDate`),
  KEY `idx_department` (`DepartmentID`),
  KEY `idx_branch` (`BranchID`),
  KEY `idx_employment_type` (`EmploymentType`),
  KEY `idx_composite` (`ReportDate`, `DepartmentID`, `BranchID`, `EmploymentType`),
  CONSTRAINT `fk_headcount_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_headcount_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create analytics_payroll_summary table
CREATE TABLE IF NOT EXISTS `analytics_payroll_summary` (
  `SummaryID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportDate` date NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `PayPeriod` varchar(20) NOT NULL,
  `TotalGrossPay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `TotalNetPay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `TotalDeductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `TotalBonuses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `TotalOvertime` decimal(15,2) NOT NULL DEFAULT 0.00,
  `AverageSalary` decimal(15,2) DEFAULT NULL,
  `MedianSalary` decimal(15,2) DEFAULT NULL,
  `EmployeeCount` int(11) NOT NULL DEFAULT 0,
  `CostPerEmployee` decimal(15,2) DEFAULT NULL,
  `BudgetVariance` decimal(15,2) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`SummaryID`),
  KEY `idx_report_date` (`ReportDate`),
  KEY `idx_department` (`DepartmentID`),
  KEY `idx_branch` (`BranchID`),
  KEY `idx_pay_period` (`PayPeriod`),
  KEY `idx_composite` (`ReportDate`, `DepartmentID`, `BranchID`, `PayPeriod`),
  CONSTRAINT `fk_payroll_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_payroll_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create analytics_benefits_costs table
CREATE TABLE IF NOT EXISTS `analytics_benefits_costs` (
  `SummaryID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportDate` date NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `BenefitType` enum('HMO','SSS','PhilHealth','Pag-IBIG','13th Month','Leave Credits','Other') NOT NULL,
  `TotalCost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `EmployeeCount` int(11) NOT NULL DEFAULT 0,
  `CostPerEmployee` decimal(15,2) DEFAULT NULL,
  `UtilizationRate` decimal(5,2) DEFAULT NULL,
  `ClaimsCount` int(11) DEFAULT NULL,
  `AverageClaimAmount` decimal(15,2) DEFAULT NULL,
  `BudgetAllocated` decimal(15,2) DEFAULT NULL,
  `BudgetVariance` decimal(15,2) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`SummaryID`),
  KEY `idx_report_date` (`ReportDate`),
  KEY `idx_department` (`DepartmentID`),
  KEY `idx_branch` (`BranchID`),
  KEY `idx_benefit_type` (`BenefitType`),
  KEY `idx_composite` (`ReportDate`, `DepartmentID`, `BranchID`, `BenefitType`),
  CONSTRAINT `fk_benefits_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_benefits_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create analytics_attendance_summary table (additional useful table)
CREATE TABLE IF NOT EXISTS `analytics_attendance_summary` (
  `SummaryID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportDate` date NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `TotalWorkDays` int(11) NOT NULL DEFAULT 0,
  `TotalPresentDays` int(11) NOT NULL DEFAULT 0,
  `TotalAbsentDays` int(11) NOT NULL DEFAULT 0,
  `TotalLateDays` int(11) NOT NULL DEFAULT 0,
  `TotalOvertimeHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `AttendanceRate` decimal(5,2) DEFAULT NULL,
  `AbsenteeismRate` decimal(5,2) DEFAULT NULL,
  `PunctualityRate` decimal(5,2) DEFAULT NULL,
  `EmployeeCount` int(11) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`SummaryID`),
  KEY `idx_report_date` (`ReportDate`),
  KEY `idx_department` (`DepartmentID`),
  KEY `idx_branch` (`BranchID`),
  KEY `idx_composite` (`ReportDate`, `DepartmentID`, `BranchID`),
  CONSTRAINT `fk_attendance_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_attendance_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create analytics_training_summary table (additional useful table)
CREATE TABLE IF NOT EXISTS `analytics_training_summary` (
  `SummaryID` int(11) NOT NULL AUTO_INCREMENT,
  `ReportDate` date NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `TrainingType` varchar(100) DEFAULT NULL,
  `TotalTrainingHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `CompletedSessions` int(11) NOT NULL DEFAULT 0,
  `TotalSessions` int(11) NOT NULL DEFAULT 0,
  `CompletionRate` decimal(5,2) DEFAULT NULL,
  `ParticipantCount` int(11) NOT NULL DEFAULT 0,
  `AverageScore` decimal(5,2) DEFAULT NULL,
  `CostPerHour` decimal(10,2) DEFAULT NULL,
  `TotalCost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`SummaryID`),
  KEY `idx_report_date` (`ReportDate`),
  KEY `idx_department` (`DepartmentID`),
  KEY `idx_branch` (`BranchID`),
  KEY `idx_training_type` (`TrainingType`),
  KEY `idx_composite` (`ReportDate`, `DepartmentID`, `BranchID`, `TrainingType`),
  CONSTRAINT `fk_training_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL,
  CONSTRAINT `fk_training_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create stored procedures for refreshing summary tables

DELIMITER //

-- Procedure to refresh headcount summary
CREATE PROCEDURE sp_RefreshHeadcountSummary(IN p_report_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Delete existing data for the report date
    DELETE FROM analytics_headcount_summary WHERE ReportDate = p_report_date;
    
    -- Insert new summary data
    INSERT INTO analytics_headcount_summary (
        ReportDate, DepartmentID, BranchID, EmploymentType,
        TotalHeadcount, NewHires, Separations, NetChange, TurnoverRate, RetentionRate
    )
    SELECT 
        p_report_date as ReportDate,
        e.DepartmentID,
        e.BranchID,
        e.EmploymentType,
        COUNT(*) as TotalHeadcount,
        SUM(CASE WHEN e.DateHired >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as NewHires,
        SUM(CASE WHEN e.DateTerminated >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) AND e.DateTerminated <= p_report_date THEN 1 ELSE 0 END) as Separations,
        SUM(CASE WHEN e.DateHired >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) THEN 1 ELSE 0 END) - 
        SUM(CASE WHEN e.DateTerminated >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) AND e.DateTerminated <= p_report_date THEN 1 ELSE 0 END) as NetChange,
        CASE 
            WHEN COUNT(*) > 0 THEN 
                (SUM(CASE WHEN e.DateTerminated >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) AND e.DateTerminated <= p_report_date THEN 1 ELSE 0 END) / COUNT(*)) * 100
            ELSE 0 
        END as TurnoverRate,
        CASE 
            WHEN COUNT(*) > 0 THEN 
                100 - (SUM(CASE WHEN e.DateTerminated >= DATE_SUB(p_report_date, INTERVAL 1 MONTH) AND e.DateTerminated <= p_report_date THEN 1 ELSE 0 END) / COUNT(*)) * 100
            ELSE 100 
        END as RetentionRate
    FROM employees e
    WHERE e.IsActive = 1 AND e.DateHired <= p_report_date
    GROUP BY e.DepartmentID, e.BranchID, e.EmploymentType;
    
    COMMIT;
END //

-- Procedure to refresh payroll summary
CREATE PROCEDURE sp_RefreshPayrollSummary(IN p_report_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Delete existing data for the report date
    DELETE FROM analytics_payroll_summary WHERE ReportDate = p_report_date;
    
    -- Insert new summary data
    INSERT INTO analytics_payroll_summary (
        ReportDate, DepartmentID, BranchID, PayPeriod,
        TotalGrossPay, TotalNetPay, TotalDeductions, TotalBonuses, TotalOvertime,
        AverageSalary, MedianSalary, EmployeeCount, CostPerEmployee
    )
    SELECT 
        p_report_date as ReportDate,
        e.DepartmentID,
        e.BranchID,
        pr.PayPeriod as PayPeriod,
        SUM(ps.GrossPay) as TotalGrossPay,
        SUM(ps.NetPay) as TotalNetPay,
        SUM(ps.TotalDeductions) as TotalDeductions,
        SUM(ps.Bonuses) as TotalBonuses,
        SUM(ps.OvertimePay) as TotalOvertime,
        AVG(ps.GrossPay) as AverageSalary,
        (SELECT ps2.GrossPay FROM payslips ps2 
         WHERE ps2.PayrollRunID = pr.PayrollRunID 
         ORDER BY ps2.GrossPay LIMIT 1 OFFSET FLOOR(COUNT(*)/2)) as MedianSalary,
        COUNT(*) as EmployeeCount,
        SUM(ps.GrossPay) / COUNT(*) as CostPerEmployee
    FROM payrollruns pr
    JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID
    JOIN employees e ON ps.EmployeeID = e.EmployeeID
    WHERE pr.PayPeriodStart >= DATE_SUB(p_report_date, INTERVAL 1 MONTH)
      AND pr.PayPeriodEnd <= p_report_date
    GROUP BY e.DepartmentID, e.BranchID, pr.PayPeriod;
    
    COMMIT;
END //

-- Procedure to refresh benefits costs summary
CREATE PROCEDURE sp_RefreshBenefitsCostsSummary(IN p_report_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Delete existing data for the report date
    DELETE FROM analytics_benefits_costs WHERE ReportDate = p_report_date;
    
    -- Insert HMO costs summary
    INSERT INTO analytics_benefits_costs (
        ReportDate, DepartmentID, BranchID, BenefitType,
        TotalCost, EmployeeCount, CostPerEmployee, UtilizationRate, ClaimsCount, AverageClaimAmount
    )
    SELECT 
        p_report_date as ReportDate,
        e.DepartmentID,
        e.BranchID,
        'HMO' as BenefitType,
        SUM(he.MonthlyPremium) as TotalCost,
        COUNT(DISTINCT he.EmployeeID) as EmployeeCount,
        SUM(he.MonthlyPremium) / COUNT(DISTINCT he.EmployeeID) as CostPerEmployee,
        (COUNT(DISTINCT hc.EmployeeID) / COUNT(DISTINCT he.EmployeeID)) * 100 as UtilizationRate,
        COUNT(hc.ClaimID) as ClaimsCount,
        AVG(hc.ClaimAmount) as AverageClaimAmount
    FROM hmoenrollments he
    JOIN employees e ON he.EmployeeID = e.EmployeeID
    LEFT JOIN hmoclaims hc ON he.EmployeeID = hc.EmployeeID 
        AND hc.ClaimDate >= DATE_SUB(p_report_date, INTERVAL 1 MONTH)
        AND hc.ClaimDate <= p_report_date
    WHERE he.IsActive = 1
    GROUP BY e.DepartmentID, e.BranchID;
    
    COMMIT;
END //

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_headcount_summary_performance ON analytics_headcount_summary (ReportDate, DepartmentID, BranchID, EmploymentType);
CREATE INDEX idx_payroll_summary_performance ON analytics_payroll_summary (ReportDate, DepartmentID, BranchID, PayPeriod);
CREATE INDEX idx_benefits_costs_performance ON analytics_benefits_costs (ReportDate, DepartmentID, BranchID, BenefitType);
CREATE INDEX idx_attendance_summary_performance ON analytics_attendance_summary (ReportDate, DepartmentID, BranchID);
CREATE INDEX idx_training_summary_performance ON analytics_training_summary (ReportDate, DepartmentID, BranchID, TrainingType);

-- Create views for common analytics queries
CREATE VIEW v_headcount_trends AS
SELECT 
    ReportDate,
    DepartmentID,
    BranchID,
    EmploymentType,
    TotalHeadcount,
    NewHires,
    Separations,
    NetChange,
    TurnoverRate,
    RetentionRate,
    LAG(TotalHeadcount) OVER (PARTITION BY DepartmentID, BranchID, EmploymentType ORDER BY ReportDate) as PreviousHeadcount,
    TotalHeadcount - LAG(TotalHeadcount) OVER (PARTITION BY DepartmentID, BranchID, EmploymentType ORDER BY ReportDate) as HeadcountChange
FROM analytics_headcount_summary
ORDER BY ReportDate DESC;

CREATE VIEW v_payroll_trends AS
SELECT 
    ReportDate,
    DepartmentID,
    BranchID,
    PayPeriod,
    TotalGrossPay,
    TotalNetPay,
    TotalDeductions,
    TotalBonuses,
    TotalOvertime,
    AverageSalary,
    MedianSalary,
    EmployeeCount,
    CostPerEmployee,
    LAG(TotalGrossPay) OVER (PARTITION BY DepartmentID, BranchID ORDER BY ReportDate) as PreviousGrossPay,
    TotalGrossPay - LAG(TotalGrossPay) OVER (PARTITION BY DepartmentID, BranchID ORDER BY ReportDate) as PayrollChange
FROM analytics_payroll_summary
ORDER BY ReportDate DESC;

CREATE VIEW v_benefits_trends AS
SELECT 
    ReportDate,
    DepartmentID,
    BranchID,
    BenefitType,
    TotalCost,
    EmployeeCount,
    CostPerEmployee,
    UtilizationRate,
    ClaimsCount,
    AverageClaimAmount,
    LAG(TotalCost) OVER (PARTITION BY DepartmentID, BranchID, BenefitType ORDER BY ReportDate) as PreviousCost,
    TotalCost - LAG(TotalCost) OVER (PARTITION BY DepartmentID, BranchID, BenefitType ORDER BY ReportDate) as CostChange
FROM analytics_benefits_costs
ORDER BY ReportDate DESC;
