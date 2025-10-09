<?php
/**
 * Comprehensive HR Reports Integration
 * Consolidates HR, payroll, benefits, and employee data into interactive dashboards and downloadable reports
 * Integrates with all HR modules for executive and operational reporting
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/HRAnalytics.php';
require_once __DIR__ . '/HMOAnalyticsIntegration.php';

class HRReportsIntegration {
    private $pdo;
    private $hrAnalytics;
    private $hmoAnalytics;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->hrAnalytics = new HRAnalytics();
        $this->hmoAnalytics = new HMOAnalyticsIntegration();
    }

    /**
     * Check whether a given column exists on a table in the current database
     */
    private function columnExists($table, $column) {
        $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':table', $table);
        $stmt->bindValue(':column', $column);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return (!empty($res) && intval($res['cnt']) > 0);
    }

    /**
     * Check whether a given table exists in the current database
     */
    private function tableExists($table) {
        $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':table', $table);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return (!empty($res) && intval($res['cnt']) > 0);
    }

    /**
     * Get comprehensive HR Reports dashboard data
     */
    public function getHRReportsDashboard($filters = []) {
        return [
            'employee_demographics' => $this->getEmployeeDemographicsReport($filters),
            'recruitment_application' => $this->getRecruitmentApplicationReport($filters),
            'payroll_compensation' => $this->getPayrollCompensationReport($filters),
            'attendance_leave' => $this->getAttendanceLeaveReport($filters),
            'benefits_hmo_utilization' => $this->getBenefitsHMOUtilizationReport($filters),
            'training_development' => $this->getTrainingDevelopmentReport($filters),
            'employee_relations_engagement' => $this->getEmployeeRelationsEngagementReport($filters),
            'turnover_retention' => $this->getTurnoverRetentionReport($filters),
            'compliance_document' => $this->getComplianceDocumentReport($filters),
            'executive_summary' => $this->getExecutiveSummaryReport($filters),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 1. Employee Demographics Report
     * Analyze workforce composition and trends
     */
    public function getEmployeeDemographicsReport($filters = []) {
        // Defensive: only reference columns that exist in the employees table
        $hasEmploymentType = $this->columnExists('employees', 'EmploymentType');
        $hasEducationLevel = $this->columnExists('employees', 'EducationLevel');
        $hasHasProfessionalLicense = $this->columnExists('employees', 'HasProfessionalLicense');

        $selectParts = [
            "COUNT(DISTINCT e.EmployeeID) as total_headcount",
            "COUNT(DISTINCT CASE WHEN e.IsActive = 1 THEN e.EmployeeID END) as active_headcount",
            "SUM(CASE WHEN e.Gender = 'Male' THEN 1 ELSE 0 END) as male_count",
            "SUM(CASE WHEN e.Gender = 'Female' THEN 1 ELSE 0 END) as female_count",
        ];

        if ($hasEmploymentType) {
            $selectParts[] = "SUM(CASE WHEN e.EmploymentType = 'Regular' THEN 1 ELSE 0 END) as regular_count";
            $selectParts[] = "SUM(CASE WHEN e.EmploymentType = 'Contractual' THEN 1 ELSE 0 END) as contractual_count";
            $selectParts[] = "SUM(CASE WHEN e.EmploymentType = 'Part-time' THEN 1 ELSE 0 END) as parttime_count";
        } else {
            // Emit zeroed aliases so downstream code can rely on the keys
            $selectParts[] = "0 as regular_count";
            $selectParts[] = "0 as contractual_count";
            $selectParts[] = "0 as parttime_count";
        }

        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) < 25 THEN 1 ELSE 0 END) as age_under_25";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as age_25_34";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as age_35_44";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as age_45_54";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) >= 55 THEN 1 ELSE 0 END) as age_55_plus";

        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) < 1 THEN 1 ELSE 0 END) as tenure_under_1_year";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) BETWEEN 1 AND 3 THEN 1 ELSE 0 END) as tenure_1_3_years";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as tenure_4_7_years";
        $selectParts[] = "SUM(CASE WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) >= 8 THEN 1 ELSE 0 END) as tenure_8_plus_years";

        if ($hasEducationLevel) {
            $selectParts[] = "SUM(CASE WHEN e.EducationLevel = 'High School' THEN 1 ELSE 0 END) as education_high_school";
            $selectParts[] = "SUM(CASE WHEN e.EducationLevel = 'Bachelor' THEN 1 ELSE 0 END) as education_bachelor";
            $selectParts[] = "SUM(CASE WHEN e.EducationLevel = 'Master' THEN 1 ELSE 0 END) as education_master";
            $selectParts[] = "SUM(CASE WHEN e.EducationLevel = 'Doctorate' THEN 1 ELSE 0 END) as education_doctorate";
        } else {
            $selectParts[] = "0 as education_high_school";
            $selectParts[] = "0 as education_bachelor";
            $selectParts[] = "0 as education_master";
            $selectParts[] = "0 as education_doctorate";
        }

        if ($hasHasProfessionalLicense) {
            $selectParts[] = "SUM(CASE WHEN e.HasProfessionalLicense = 1 THEN 1 ELSE 0 END) as licensed_employees";
        } else {
            $selectParts[] = "0 as licensed_employees";
        }

        $selectParts[] = "d.DepartmentName";
        $selectParts[] = "COUNT(e.EmployeeID) as dept_headcount";

        $sql = "SELECT " . implode(",\n                    ", $selectParts) . "\n                FROM employees e\n                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID\n                WHERE e.IsActive = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        // Only apply branch filter if BranchID exists on employees table
        if (!empty($filters['branch_id']) && $this->columnExists('employees', 'BranchID')) {
            $sql .= " AND e.BranchID = :branch_id";
            $params[':branch_id'] = $filters['branch_id'];
        }
        
        $sql .= " GROUP BY d.DepartmentID, d.DepartmentName";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $departmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get overall demographics
        $overallSql = "SELECT 
                        COUNT(DISTINCT e.EmployeeID) as total_headcount,
                        AVG(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE())) as avg_age,
                        AVG(TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE())) as avg_tenure_years
                      FROM employees e
                      WHERE e.IsActive = 1";
        
        $overallStmt = $this->pdo->prepare($overallSql);
        $overallStmt->execute();
        $overallData = $overallStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'overview' => $overallData,
            'department_distribution' => $departmentData,
            'gender_distribution' => [
                ['gender' => 'Male', 'count' => $departmentData[0]['male_count'] ?? 0],
                ['gender' => 'Female', 'count' => $departmentData[0]['female_count'] ?? 0]
            ],
            'employment_type_distribution' => [
                ['type' => 'Regular', 'count' => $departmentData[0]['regular_count'] ?? 0],
                ['type' => 'Contractual', 'count' => $departmentData[0]['contractual_count'] ?? 0],
                ['type' => 'Part-time', 'count' => $departmentData[0]['parttime_count'] ?? 0]
            ],
            'age_distribution' => [
                ['range' => 'Under 25', 'count' => $departmentData[0]['age_under_25'] ?? 0],
                ['range' => '25-34', 'count' => $departmentData[0]['age_25_34'] ?? 0],
                ['range' => '35-44', 'count' => $departmentData[0]['age_35_44'] ?? 0],
                ['range' => '45-54', 'count' => $departmentData[0]['age_45_54'] ?? 0],
                ['range' => '55+', 'count' => $departmentData[0]['age_55_plus'] ?? 0]
            ],
            'tenure_distribution' => [
                ['range' => 'Under 1 year', 'count' => $departmentData[0]['tenure_under_1_year'] ?? 0],
                ['range' => '1-3 years', 'count' => $departmentData[0]['tenure_1_3_years'] ?? 0],
                ['range' => '4-7 years', 'count' => $departmentData[0]['tenure_4_7_years'] ?? 0],
                ['range' => '8+ years', 'count' => $departmentData[0]['tenure_8_plus_years'] ?? 0]
            ],
            'education_distribution' => [
                ['level' => 'High School', 'count' => $departmentData[0]['education_high_school'] ?? 0],
                ['level' => 'Bachelor', 'count' => $departmentData[0]['education_bachelor'] ?? 0],
                ['level' => 'Master', 'count' => $departmentData[0]['education_master'] ?? 0],
                ['level' => 'Doctorate', 'count' => $departmentData[0]['education_doctorate'] ?? 0]
            ]
        ];
    }

    /**
     * 2. Recruitment & Application Report
     * Measure hiring efficiency and pipeline performance
     */
    public function getRecruitmentApplicationReport($filters = []) {
        // Defensive: skip recruitment queries if the jobapplications or positions tables don't exist
        $sourceData = [];
        $vacancyData = ['total_vacancies' => 0, 'open_vacancies' => 0, 'filled_vacancies' => 0, 'avg_fill_time_days' => 0];
        $trendData = [];

        if ($this->tableExists('jobapplications')) {
            // Get applications data
            $sql = "SELECT 
                        COUNT(*) as total_applications,
                        COUNT(CASE WHEN ja.Status = 'Hired' THEN 1 END) as hired_count,
                        COUNT(CASE WHEN ja.Status = 'Rejected' THEN 1 END) as rejected_count,
                        COUNT(CASE WHEN ja.Status = 'Pending' THEN 1 END) as pending_count,
                        AVG(CASE WHEN ja.Status = 'Hired' THEN DATEDIFF(ja.DateHired, ja.ApplicationDate) END) as avg_time_to_hire_days,
                        ja.ApplicationSource,
                        COUNT(*) as applications_by_source
                    FROM jobapplications ja
                    WHERE ja.ApplicationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

            $params = [];
            if (!empty($filters['department_id']) && $this->tableExists('positions')) {
                $sql .= " AND ja.PositionID IN (SELECT PositionID FROM positions WHERE DepartmentID = :department_id)";
                $params[':department_id'] = $filters['department_id'];
            }

            $sql .= " GROUP BY ja.ApplicationSource";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $sourceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get monthly recruitment trend
            $trendSql = "SELECT 
                            DATE_FORMAT(ja.ApplicationDate, '%Y-%m') as month,
                            COUNT(*) as applications,
                            COUNT(CASE WHEN ja.Status = 'Hired' THEN 1 END) as hires
                        FROM jobapplications ja
                        WHERE ja.ApplicationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(ja.ApplicationDate, '%Y-%m')
                        ORDER BY month";

            $trendStmt = $this->pdo->prepare($trendSql);
            $trendStmt->execute();
            $trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tableExists('positions')) {
            // Defensive vacancy data: positions table in some schemas uses different column names.
            $hasPosIsActive = $this->columnExists('positions', 'IsActive');
            $hasPosStatus = $this->columnExists('positions', 'Status');
            $hasPosDatePosted = $this->columnExists('positions', 'DatePosted');
            $hasPosDateFilled = $this->columnExists('positions', 'DateFilled');
            $hasPosCreatedAt = $this->columnExists('positions', 'CreatedAt');

            // Choose a sensible field to use for "posted date"
            $postedField = null;
            if ($hasPosDatePosted) {
                $postedField = 'p.DatePosted';
            } elseif ($hasPosCreatedAt) {
                $postedField = 'p.CreatedAt';
            }

            $selectParts = [
                "COUNT(*) as total_vacancies"
            ];

            // Open vacancies: prefer explicit IsActive + Status where available, otherwise fall back to Status checks or zero
            if ($hasPosIsActive && $hasPosStatus) {
                $selectParts[] = "COUNT(CASE WHEN p.IsActive = 1 AND (p.Status IN ('Open','Active')) THEN 1 END) as open_vacancies";
            } elseif ($hasPosIsActive) {
                $selectParts[] = "COUNT(CASE WHEN p.IsActive = 1 THEN 1 END) as open_vacancies";
            } elseif ($hasPosStatus) {
                $selectParts[] = "COUNT(CASE WHEN p.Status IN ('Open','Active') THEN 1 END) as open_vacancies";
            } else {
                $selectParts[] = "0 as open_vacancies";
            }

            // Filled vacancies: rely on Status if present, otherwise return zero (cannot determine)
            if ($hasPosStatus && $hasPosIsActive) {
                $selectParts[] = "COUNT(CASE WHEN p.IsActive = 1 AND (p.Status IN ('Filled','Closed','Inactive')) THEN 1 END) as filled_vacancies";
            } elseif ($hasPosStatus) {
                $selectParts[] = "COUNT(CASE WHEN p.Status IN ('Filled','Closed','Inactive') THEN 1 END) as filled_vacancies";
            } else {
                $selectParts[] = "0 as filled_vacancies";
            }

            // Average fill time: only if both date fields are present (or we can use CreatedAt as posted date)
            if ($hasPosDateFilled && $postedField) {
                $selectParts[] = "AVG(DATEDIFF(p.DateFilled, $postedField)) as avg_fill_time_days";
            } else {
                $selectParts[] = "0 as avg_fill_time_days";
            }

            $vacancySql = "SELECT " . implode(",\n                            ", $selectParts) . "\n                          FROM positions p";

            if ($postedField) {
                $vacancySql .= "\n                          WHERE $postedField >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
            }

            $vacancyStmt = $this->pdo->prepare($vacancySql);
            $vacancyStmt->execute();
            $vacancyData = $vacancyStmt->fetch(PDO::FETCH_ASSOC);
        }

        return [
            'overview' => [
                'total_applications' => array_sum(array_column($sourceData, 'applications_by_source')),
                'hired_count' => array_sum(array_column($sourceData, 'hired_count')),
                'rejected_count' => array_sum(array_column($sourceData, 'rejected_count')),
                'pending_count' => array_sum(array_column($sourceData, 'pending_count')),
                'acceptance_rate' => 0, // Will calculate
                'avg_time_to_hire' => !empty($sourceData) ? (array_sum(array_column($sourceData, 'avg_time_to_hire_days')) / count($sourceData)) : 0
            ],
            'applications_by_source' => $sourceData,
            'vacancy_metrics' => $vacancyData,
            'recruitment_trend' => $trendData,
            'funnel_data' => [
                ['stage' => 'Applications', 'count' => array_sum(array_column($sourceData, 'applications_by_source'))],
                ['stage' => 'Screened', 'count' => array_sum(array_column($sourceData, 'applications_by_source')) * 0.7], // Estimated
                ['stage' => 'Interviewed', 'count' => array_sum(array_column($sourceData, 'applications_by_source')) * 0.3], // Estimated
                ['stage' => 'Hired', 'count' => ar                    DATE_FORMAT(pr.PayPeriodStart, '%Y-%m') as month,
                    SUM(ps.GrossPay) as total_gross_pay,
                    SUM(ps.NetPay) as total_net_pay,
                    SUM(ps.BaseSalary) as total_base_salary,
                    SUM(ps.OvertimePay) as total_overtime_pay,
                    SUM(ps.BonusAmount) as total_bonus_amount,
                    SUM(ps.DeductionAmount) as total_deduction_amount,
                    AVG(ps.GrossPay) as avg_gross_pay,
                    COUNT(DISTINCT ps.EmployeeID) as employees_paid
                FROM payrollruns pr
                LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID
                WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND ps.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY DATE_FORMAT(pr.PayPeriodStart, '%Y-%m')
                  ORDER BY month";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $payrollTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get salary grade distribution
        $gradeSql = "SELECT 
                        sg.grade_code,
                        sg.grade_name,
                        COUNT(egm.employee_id) as employee_count,
                        AVG(es.BaseSalary) as avg_salary,
                        MIN(es.BaseSalary) as min_salary,
                        MAX(es.BaseSalary) as max_salary
                    FROM salary_grades sg
                    LEFT JOIN employee_grade_mapping egm ON sg.grade_id = egm.grade_id AND egm.is_active = 1
                    LEFT JOIN employees e ON egm.employee_id = e.EmployeeID AND e.IsActive = 1
                    LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                    WHERE sg.is_active = 1";
        
        if (!empty($filters['department_id'])) {
            $gradeSql .= " AND e.DepartmentID = :department_id";
        }
        
        $gradeSql .= " GROUP BY sg.grade_id, sg.grade_code, sg.grade_name
                       ORDER BY sg.grade_code";
        
        $gradeStmt = $this->pdo->prepare($gradeSql);
        if (!empty($filters['department_id'])) {
            $gradeStmt->bindValue(':department_id', $filters['department_id']);
        }
        $gradeStmt->execute();
        
        $gradeDistribution = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get deduction breakdown
        $deductionSql = "SELECT 
                            pd.DeductionType,
                            SUM(pd.Amount) as total_amount,
                            COUNT(DISTINCT pd.EmployeeID) as affected_employees
                        FROM payrolldeductions pd
                        LEFT JOIN payrollruns pr ON pd.PayrollRunID = pr.PayrollRunID
                        WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $deductionSql .= " AND pd.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $deductionSql .= " GROUP BY pd.DeductionType
                           ORDER BY total_amount DESC";
        
        $deductionStmt = $this->pdo->prepare($deductionSql);
        if (!empty($filters['department_id'])) {
            $deductionStmt->bindValue(':department_id', $filters['department_id']);
        }
        $deductionStmt->execute();
        
        $deductionBreakdown = $deductionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'payroll_trend' => $payrollTrend,
            'salary_grade_distribution' => $gradeDistribution,
            'deduction_breakdown' => $deductionBreakdown,
            'payroll_composition' => [
                ['component' => 'Base Salary', 'amount' => $payrollTrend[0]['total_base_salary'] ?? 0],
                ['component' => 'Overtime Pay', 'amount' => $payrollTrend[0]['total_overtime_pay'] ?? 0],
                ['component' => 'Bonus Amount', 'amount' => $payrollTrend[0]['total_bonus_amount'] ?? 0],
                ['component' => 'Deductions', 'amount' => $payrollTrend[0]['total_deduction_amount'] ?? 0]
            ],
            'summary_metrics' => [
                'total_monthly_payroll' => $payrollTrend[0]['total_gross_pay'] ?? 0,
                'avg_employee_pay' => $payrollTrend[0]['avg_gross_pay'] ?? 0,
                'total_employees_paid' => $payrollTrend[0]['employees_paid'] ?? 0
            ]
        ];
    }

    /**
     * 4. Attendance & Leave Report
     * Monitor attendance patterns and leave usage
     */
    public function getAttendanceLeaveReport($filters = []) {
        // Get attendance data
        $sql = "SELECT 
                    DATE_FORMAT(a.AttendanceDate, '%Y-%m') as month,
                    COUNT(DISTINCT a.EmployeeID) as total_employees,
                    SUM(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.Status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN a.Status = 'Late' THEN 1 ELSE 0 END) as late_count,
                    AVG(CASE WHEN a.Status = 'Present' THEN 1 ELSE 0 END) * 100 as attendance_rate
                FROM attendance a
                WHERE a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND a.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY DATE_FORMAT(a.AttendanceDate, '%Y-%m')
                  ORDER BY month";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $attendanceTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get leave utilization
        $leaveSql = "SELECT 
                        lt.LeaveTypeName,
                        COUNT(lr.LeaveRequestID) as total_requests,
                        SUM(lr.DaysRequested) as total_days_requested,
                        SUM(lr.DaysApproved) as total_days_approved,
                        AVG(lr.DaysRequested) as avg_days_per_request
                    FROM leaverequests lr
                    LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
                    WHERE lr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $leaveSql .= " AND lr.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $leaveSql .= " GROUP BY lt.LeaveTypeID, lt.LeaveTypeName
                       ORDER BY total_days_requested DESC";
        
        $leaveStmt = $this->pdo->prepare($leaveSql);
        if (!empty($filters['department_id'])) {
            $leaveStmt->bindValue(':department_id', $filters['department_id']);
        }
        $leaveStmt->execute();
        
        $leaveUtilization = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get overtime data
        $overtimeSql = "SELECT 
                            DATE_FORMAT(a.AttendanceDate, '%Y-%m') as month,
                            SUM(a.OvertimeHours) as total_overtime_hours,
                            COUNT(DISTINCT CASE WHEN a.OvertimeHours > 0 THEN a.EmployeeID END) as employees_with_overtime,
                            AVG(a.OvertimeHours) as avg_overtime_per_employee
                        FROM attendance a
                        WHERE a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        AND a.OvertimeHours > 0";
        
        if (!empty($filters['department_id'])) {
            $overtimeSql .= " AND a.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $overtimeSql .= " GROUP BY DATE_FORMAT(a.AttendanceDate, '%Y-%m')
                          ORDER BY month";
        
        $overtimeStmt = $this->pdo->prepare($overtimeSql);
        if (!empty($filters['department_id'])) {
            $overtimeStmt->bindValue(':department_id', $filters['department_id']);
        }
        $overtimeStmt->execute();
        
        $overtimeTrend = $overtimeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'attendance_trend' => $attendanceTrend,
            'leave_utilization' => $leaveUtilization,
            'overtime_trend' => $overtimeTrend,
            'attendance_summary' => [
                'current_attendance_rate' => $attendanceTrend[0]['attendance_rate'] ?? 0,
                'total_present_days' => array_sum(array_column($attendanceTrend, 'present_count')),
                'total_absent_days' => array_sum(array_column($attendanceTrend, 'absent_count')),
                'total_late_days' => array_sum(array_column($attendanceTrend, 'late_count'))
            ]
        ];
    }

    /**
     * 5. Benefits & HMO Utilization Report
     * Track benefit costs, claims, and provider usage
     */
    public function getBenefitsHMOUtilizationReport($filters = []) {
        // Get HMO enrollment data
        $sql = "SELECT 
                    hmo.PlanName,
                    COUNT(he.EmployeeID) as enrollment_count,
                    SUM(hmo.MonthlyPremium) as total_monthly_cost,
                    AVG(hmo.MonthlyPremium) as avg_premium_per_employee
                FROM hmoenrollments he
                LEFT JOIN hmoplans hmo ON he.PlanID = hmo.PlanID
                WHERE he.IsActive = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND he.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY hmo.PlanID, hmo.PlanName
                  ORDER BY enrollment_count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $hmoUtilization = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get claims data
        $claimsSql = "SELECT 
                        DATE_FORMAT(hc.ClaimDate, '%Y-%m') as month,
                        COUNT(hc.ClaimID) as total_claims,
                        SUM(hc.ClaimAmount) as total_claim_amount,
                        AVG(hc.ClaimAmount) as avg_claim_amount,
                        AVG(DATEDIFF(hc.ProcessedDate, hc.ClaimDate)) as avg_processing_days
                    FROM hmoclaims hc
                    WHERE hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $claimsSql .= " AND hc.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $claimsSql .= " GROUP BY DATE_FORMAT(hc.ClaimDate, '%Y-%m')
                        ORDER BY month";
        
        $claimsStmt = $this->pdo->prepare($claimsSql);
        if (!empty($filters['department_id'])) {
            $claimsStmt->bindValue(':department_id', $filters['department_id']);
        }
        $claimsStmt->execute();
        
        $claimsTrend = $claimsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get provider utilization
        $providerSql = "SELECT 
                            hp.ProviderName,
                            COUNT(hc.ClaimID) as claim_count,
                            SUM(hc.ClaimAmount) as total_claim_amount,
                            AVG(hc.ClaimAmount) as avg_claim_amount
                        FROM hmoclaims hc
                        LEFT JOIN hmoplans hmo ON hc.PlanID = hmo.PlanID
                        LEFT JOIN hmoproviders hp ON hmo.ProviderID = hp.ProviderID
                        WHERE hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $providerSql .= " AND hc.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $providerSql .= " GROUP BY hp.ProviderID, hp.ProviderName
                          ORDER BY claim_count DESC";
        
        $providerStmt = $this->pdo->prepare($providerSql);
        if (!empty($filters['department_id'])) {
            $providerStmt->bindValue(':department_id', $filters['department_id']);
        }
        $providerStmt->execute();
        
        $providerUtilization = $providerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'hmo_utilization' => $hmoUtilization,
            'claims_trend' => $claimsTrend,
            'provider_utilization' => $providerUtilization,
            'benefits_summary' => [
                'total_active_enrollments' => array_sum(array_column($hmoUtilization, 'enrollment_count')),
                'total_monthly_premium_cost' => array_sum(array_column($hmoUtilization, 'total_monthly_cost')),
                'total_annual_premium_cost' => array_sum(array_column($hmoUtilization, 'total_monthly_cost')) * 12,
                'avg_processing_days' => $claimsTrend[0]['avg_processing_days'] ?? 0
            ]
        ];
    }

    /**
     * 6. Training & Development Report
     * Evaluate training effectiveness and cost impact
     */
    public function getTrainingDevelopmentReport($filters = []) {
        // Get training completion data
        $sql = "SELECT 
                    t.TrainingName,
                    t.TrainingType,
                    COUNT(te.EmployeeID) as total_participants,
                    SUM(CASE WHEN te.CompletionStatus = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                    AVG(CASE WHEN te.CompletionStatus = 'Completed' THEN 1 ELSE 0 END) * 100 as completion_rate,
                    AVG(te.Score) as avg_score,
                    SUM(t.DurationHours) as total_hours,
                    SUM(t.Cost) as total_cost
                FROM trainings t
                LEFT JOIN trainingenrollments te ON t.TrainingID = te.TrainingID
                WHERE t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND te.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY t.TrainingID, t.TrainingName, t.TrainingType
                  ORDER BY completion_rate DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $trainingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get training by department
        $deptSql = "SELECT 
                        d.DepartmentName,
                        COUNT(DISTINCT te.EmployeeID) as employees_trained,
                        SUM(t.DurationHours) as total_training_hours,
                        SUM(t.Cost) as total_training_cost,
                        AVG(te.Score) as avg_training_score
                    FROM trainings t
                    LEFT JOIN trainingenrollments te ON t.TrainingID = te.TrainingID
                    LEFT JOIN employees e ON te.EmployeeID = e.EmployeeID
                    LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                    WHERE t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $deptSql .= " AND e.DepartmentID = :department_id";
        }
        
        $deptSql .= " GROUP BY d.DepartmentID, d.DepartmentName
                      ORDER BY total_training_hours DESC";
        
        $deptStmt = $this->pdo->prepare($deptSql);
        if (!empty($filters['department_id'])) {
            $deptStmt->bindValue(':department_id', $filters['department_id']);
        }
        $deptStmt->execute();
        
        $departmentTraining = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get skill improvement data
        $skillSql = "SELECT 
                        ts.SkillName,
                        AVG(tsa.ScoreBefore) as avg_score_before,
                        AVG(tsa.ScoreAfter) as avg_score_after,
                        AVG(tsa.ScoreAfter - tsa.ScoreBefore) as avg_improvement,
                        COUNT(tsa.EmployeeID) as employees_assessed
                    FROM trainingskills ts
                    LEFT JOIN trainingskillassessments tsa ON ts.SkillID = tsa.SkillID
                    WHERE tsa.AssessmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $skillSql .= " AND tsa.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $skillSql .= " GROUP BY ts.SkillID, ts.SkillName
                       ORDER BY avg_improvement DESC";
        
        $skillStmt = $this->pdo->prepare($skillSql);
        if (!empty($filters['department_id'])) {
            $skillStmt->bindValue(':department_id', $filters['department_id']);
        }
        $skillStmt->execute();
        
        $skillImprovement = $skillStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'training_completion' => $trainingData,
            'training_by_department' => $departmentTraining,
            'skill_improvement' => $skillImprovement,
            'training_summary' => [
                'total_trainings' => count($trainingData),
                'total_participants' => array_sum(array_column($trainingData, 'total_participants')),
                'overall_completion_rate' => array_sum(array_column($trainingData, 'completed_count')) / array_sum(array_column($trainingData, 'total_participants')) * 100,
                'total_training_hours' => array_sum(array_column($trainingData, 'total_hours')),
                'total_training_cost' => array_sum(array_column($trainingData, 'total_cost'))
            ]
        ];
    }

    /**
     * 7. Employee Relations & Engagement Report
     * Assess workplace engagement and environment
     */
    public function getEmployeeRelationsEngagementReport($filters = []) {
        // Get engagement survey data
        $sql = "SELECT 
                    es.SurveyName,
                    AVG(esr.Score) as avg_engagement_score,
                    COUNT(esr.EmployeeID) as participants,
                    AVG(CASE WHEN esr.Score >= 4 THEN 1 ELSE 0 END) * 100 as high_engagement_rate
                FROM engagementsurveys es
                LEFT JOIN engagementsurveyresponses esr ON es.SurveyID = esr.SurveyID
                WHERE es.SurveyDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND esr.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY es.SurveyID, es.SurveyName
                  ORDER BY avg_engagement_score DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $engagementData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get disciplinary cases
        $disciplinarySql = "SELECT 
                                DATE_FORMAT(dc.CaseDate, '%Y-%m') as month,
                                COUNT(dc.CaseID) as total_cases,
                                COUNT(CASE WHEN dc.CaseType = 'Warning' THEN 1 END) as warning_cases,
                                COUNT(CASE WHEN dc.CaseType = 'Suspension' THEN 1 END) as suspension_cases,
                                COUNT(CASE WHEN dc.CaseType = 'Termination' THEN 1 END) as termination_cases
                            FROM disciplinarycases dc
                            WHERE dc.CaseDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $disciplinarySql .= " AND dc.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $disciplinarySql .= " GROUP BY DATE_FORMAT(dc.CaseDate, '%Y-%m')
                              ORDER BY month";
        
        $disciplinaryStmt = $this->pdo->prepare($disciplinarySql);
        if (!empty($filters['department_id'])) {
            $disciplinaryStmt->bindValue(':department_id', $filters['department_id']);
        }
        $disciplinaryStmt->execute();
        
        $disciplinaryTrend = $disciplinaryStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recognition data
        $recognitionSql = "SELECT 
                            DATE_FORMAT(er.RecognitionDate, '%Y-%m') as month,
                            COUNT(er.RecognitionID) as total_recognitions,
                            COUNT(CASE WHEN er.RecognitionType = 'Employee of the Month' THEN 1 END) as employee_of_month,
                            COUNT(CASE WHEN er.RecognitionType = 'Performance Award' THEN 1 END) as performance_awards,
                            COUNT(CASE WHEN er.RecognitionType = 'Team Achievement' THEN 1 END) as team_achievements
                        FROM employeerecognitions er
                        WHERE er.RecognitionDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $recognitionSql .= " AND er.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
        }
        
        $recognitionSql .= " GROUP BY DATE_FORMAT(er.RecognitionDate, '%Y-%m')
                            ORDER BY month";
        
        $recognitionStmt = $this->pdo->prepare($recognitionSql);
        if (!empty($filters['department_id'])) {
            $recognitionStmt->bindValue(':department_id', $filters['department_id']);
        }
        $recognitionStmt->execute();
        
        $recognitionTrend = $recognitionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'engagement_surveys' => $engagementData,
            'disciplinary_trend' => $disciplinaryTrend,
            'recognition_trend' => $recognitionTrend,
            'engagement_summary' => [
                'overall_engagement_score' => $engagementData[0]['avg_engagement_score'] ?? 0,
                'high_engagement_rate' => $engagementData[0]['high_engagement_rate'] ?? 0,
                'total_survey_participants' => array_sum(array_column($engagementData, 'participants')),
                'total_disciplinary_cases' => array_sum(array_column($disciplinaryTrend, 'total_cases')),
                'total_recognitions' => array_sum(array_column($recognitionTrend, 'total_recognitions'))
            ]
        ];
    }

    /**
     * 8. Turnover & Retention Report
     * Track attrition, retention, and exit reasons
     */
    public function getTurnoverRetentionReport($filters = []) {
        // Get turnover data
        $hasSeparationType = $this->columnExists('employees', 'SeparationType');

        $separationSelectParts = [
            "DATE_FORMAT(e.TerminationDate, '%Y-%m') as month",
            "COUNT(e.EmployeeID) as separations",
        ];
        if ($hasSeparationType) {
            $separationSelectParts[] = "COUNT(CASE WHEN e.SeparationType = 'Resignation' THEN 1 END) as resignations";
            $separationSelectParts[] = "COUNT(CASE WHEN e.SeparationType = 'Termination' THEN 1 END) as terminations";
            $separationSelectParts[] = "COUNT(CASE WHEN e.SeparationType = 'Retirement' THEN 1 END) as retirements";
        } else {
            $separationSelectParts[] = "0 as resignations";
            $separationSelectParts[] = "0 as terminations";
            $separationSelectParts[] = "0 as retirements";
        }
        $separationSelectParts[] = "AVG(TIMESTAMPDIFF(YEAR, e.HireDate, e.TerminationDate)) as avg_tenure_at_separation";

        $sql = "SELECT " . implode(",\n                    ", $separationSelectParts) . "\n                FROM employees e\n                WHERE e.TerminationDate IS NOT NULL \n                AND e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        // Branch filter only if present
        if (!empty($filters['branch_id']) && $this->columnExists('employees', 'BranchID')) {
            $sql .= " AND e.BranchID = :branch_id";
            $params[':branch_id'] = $filters['branch_id'];
        }
        
    $sql .= " GROUP BY DATE_FORMAT(e.TerminationDate, '%Y-%m')
                  ORDER BY month";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $turnoverTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get exit reasons
        // Exit reasons: try ExitReason, fallback to TerminationReason if available
        $exitReasons = [];
        $exitCol = null;
        if ($this->columnExists('employees', 'ExitReason')) {
            $exitCol = 'ExitReason';
        } elseif ($this->columnExists('employees', 'TerminationReason')) {
            $exitCol = 'TerminationReason';
        }

        if ($exitCol) {
            $exitReasonSql = "SELECT 
                                e." . $exitCol . " as reason,
                                COUNT(e.EmployeeID) as count,
                                AVG(TIMESTAMPDIFF(YEAR, e.HireDate, e.TerminationDate)) as avg_tenure
                            FROM employees e
                            WHERE e.TerminationDate IS NOT NULL 
                            AND e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                            AND e." . $exitCol . " IS NOT NULL";

            if (!empty($filters['department_id'])) {
                $exitReasonSql .= " AND e.DepartmentID = :department_id";
            }

            $exitReasonSql .= " GROUP BY e." . $exitCol . " ORDER BY count DESC";

            $exitReasonStmt = $this->pdo->prepare($exitReasonSql);
            if (!empty($filters['department_id'])) {
                $exitReasonStmt->bindValue(':department_id', $filters['department_id']);
            }
            $exitReasonStmt->execute();
            $exitReasons = $exitReasonStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get retention by department
        $retentionSql = "SELECT 
                            d.DepartmentName,
                            COUNT(DISTINCT e.EmployeeID) as total_employees,
                            COUNT(DISTINCT CASE WHEN e.TerminationDate IS NOT NULL AND e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN e.EmployeeID END) as separations_12_months,
                            COUNT(DISTINCT CASE WHEN e.HireDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN e.EmployeeID END) as new_hires_12_months,
                            CASE 
                                WHEN COUNT(DISTINCT e.EmployeeID) > 0 THEN 
                                    (COUNT(DISTINCT CASE WHEN e.TerminationDate IS NOT NULL AND e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN e.EmployeeID END) / COUNT(DISTINCT e.EmployeeID)) * 100
                                ELSE 0 
                            END as turnover_rate
                        FROM employees e
                        LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                        WHERE e.IsActive = 1 OR e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        if (!empty($filters['department_id'])) {
            $retentionSql .= " AND e.DepartmentID = :department_id";
        }
        
        $retentionSql .= " GROUP BY d.DepartmentID, d.DepartmentName
                          ORDER BY turnover_rate DESC";
        
        $retentionStmt = $this->pdo->prepare($retentionSql);
        if (!empty($filters['department_id'])) {
            $retentionStmt->bindValue(':department_id', $filters['department_id']);
        }
        $retentionStmt->execute();
        
        $retentionByDept = $retentionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'turnover_trend' => $turnoverTrend,
            'exit_reasons' => $exitReasons,
            'retention_by_department' => $retentionByDept,
            'turnover_summary' => [
                'total_separations_12_months' => array_sum(array_column($turnoverTrend, 'separations')),
                'total_resignations' => array_sum(array_column($turnoverTrend, 'resignations')),
                'total_terminations' => array_sum(array_column($turnoverTrend, 'terminations')),
                'total_retirements' => array_sum(array_column($turnoverTrend, 'retirements')),
                'avg_tenure_at_separation' => array_sum(array_column($turnoverTrend, 'avg_tenure_at_separation')) / count($turnoverTrend)
            ]
        ];
    }

    /**
     * 9. Compliance & Document Report
     * Monitor document completeness and expiring credentials
     */
    public function getComplianceDocumentReport($filters = []) {
        // Get expiring documents
        $sql = "SELECT 
                    ed.DocumentType,
                    COUNT(ed.DocumentID) as total_documents,
                    COUNT(CASE WHEN ed.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_30_days,
                    COUNT(CASE WHEN ed.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 END) as expiring_60_days,
                    COUNT(CASE WHEN ed.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_90_days,
                    COUNT(CASE WHEN ed.ExpiryDate < CURDATE() THEN 1 END) as expired_documents
                FROM employeedocuments ed
                WHERE ed.IsActive = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND ed.EmployeeID IN (SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY ed.DocumentType
                  ORDER BY expiring_30_days DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $documentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get compliance rate by employee
        $complianceSql = "SELECT 
                            e.EmployeeID,
                            CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                            d.DepartmentName,
                            COUNT(ed.DocumentID) as total_required_docs,
                            COUNT(CASE WHEN ed.ExpiryDate > CURDATE() THEN 1 END) as valid_documents,
                            COUNT(CASE WHEN ed.ExpiryDate <= CURDATE() THEN 1 END) as expired_documents,
                            CASE 
                                WHEN COUNT(ed.DocumentID) > 0 THEN 
                                    (COUNT(CASE WHEN ed.ExpiryDate > CURDATE() THEN 1 END) / COUNT(ed.DocumentID)) * 100
                                ELSE 0 
                            END as compliance_rate
                        FROM employees e
                        LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                        LEFT JOIN employeedocuments ed ON e.EmployeeID = ed.EmployeeID AND ed.IsActive = 1
                        WHERE e.IsActive = 1";
        
        if (!empty($filters['department_id'])) {
            $complianceSql .= " AND e.DepartmentID = :department_id";
        }
        
        $complianceSql .= " GROUP BY e.EmployeeID, e.FirstName, e.LastName, d.DepartmentName
                            HAVING compliance_rate < 100
                            ORDER BY compliance_rate ASC";
        
        $complianceStmt = $this->pdo->prepare($complianceSql);
        if (!empty($filters['department_id'])) {
            $complianceStmt->bindValue(':department_id', $filters['department_id']);
        }
        $complianceStmt->execute();
        
        $complianceIssues = $complianceStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get contract renewals if ContractEndDate exists
        $contractRenewals = [];
        if ($this->columnExists('employees', 'ContractEndDate')) {
            $contractSql = "SELECT 
                                e.EmployeeID,
                                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                                d.DepartmentName,
                                e.ContractEndDate,
                                DATEDIFF(e.ContractEndDate, CURDATE()) as days_until_expiry,
                                CASE 
                                    WHEN e.ContractEndDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                                    WHEN e.ContractEndDate <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Due for Review'
                                    ELSE 'Current'
                                END as renewal_status
                            FROM employees e
                            LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                            WHERE e.IsActive = 1 
                            AND e.ContractEndDate IS NOT NULL
                            AND e.ContractEndDate <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)";

            if (!empty($filters['department_id'])) {
                $contractSql .= " AND e.DepartmentID = :department_id";
            }

            $contractSql .= " ORDER BY e.ContractEndDate ASC";

            $contractStmt = $this->pdo->prepare($contractSql);
            if (!empty($filters['department_id'])) {
                $contractStmt->bindValue(':department_id', $filters['department_id']);
            }
            $contractStmt->execute();
            $contractRenewals = $contractStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [
            'document_status' => $documentStatus,
            'compliance_issues' => $complianceIssues,
            'contract_renewals' => $contractRenewals,
            'compliance_summary' => [
                'total_documents' => array_sum(array_column($documentStatus, 'total_documents')),
                'expiring_30_days' => array_sum(array_column($documentStatus, 'expiring_30_days')),
                'expiring_60_days' => array_sum(array_column($documentStatus, 'expiring_60_days')),
                'expired_documents' => array_sum(array_column($documentStatus, 'expired_documents')),
                'employees_with_compliance_issues' => count($complianceIssues),
                'contracts_due_for_renewal' => count($contractRenewals)
            ]
        ];
    }

    /**
     * 10. Executive / Management Summary Report
     * Provide top-level HR performance snapshot
     */
    public function getExecutiveSummaryReport($filters = []) {
        // Get key metrics
        $sql = "SELECT 
                    -- Headcount metrics
                    COUNT(DISTINCT CASE WHEN e.IsActive = 1 THEN e.EmployeeID END) as total_active_employees,
                    COUNT(DISTINCT CASE WHEN e.HireDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN e.EmployeeID END) as new_hires_this_month,
                    COUNT(DISTINCT CASE WHEN e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN e.EmployeeID END) as separations_this_month,
                    
                    -- Payroll metrics
                    SUM(CASE WHEN pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN ps.GrossPay ELSE 0 END) as monthly_payroll_cost,
                    AVG(CASE WHEN pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN ps.GrossPay ELSE NULL END) as avg_monthly_salary,
                    
                    -- Attendance metrics
                    AVG(CASE WHEN a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND a.Status = 'Present' THEN 1 ELSE 0 END) * 100 as monthly_attendance_rate,
                    
                    -- Training metrics
                    COUNT(DISTINCT CASE WHEN t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN t.TrainingID END) as trainings_this_month,
                    
                    -- Benefits metrics
                    SUM(CASE WHEN he.IsActive = 1 THEN hmo.MonthlyPremium ELSE 0 END) as monthly_benefits_cost
                FROM employees e
                LEFT JOIN payrollruns pr ON pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID AND ps.EmployeeID = e.EmployeeID
RVAL 1 MONTH) THEN ps.GrossIncome ELSE 0 END) as monthly_payroll_cost,
                    AVG(CASE WHEN pr.PayPeriodStartDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN ps.GrossIncome ELSE NULL END) as avg_monthly_salary,
                    
                    -- Attendance metrics
                    AVG(CASE WHEN a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND a.Status = 'Present' THEN 1 ELSE 0 END) * 100 as monthly_attendance_rate,
                    
                    -- Training metrics
                    COUNT(DISTINCT CASE WHEN t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN t.TrainingID END) as trainings_this_month,
                    
                    -- Benefits metrics
                    SUM(CASE WHEN he.IsActive = 1 THEN hmo.MonthlyPremium ELSE 0 END) as monthly_benefits_cost
                FROM employees e
                LEFT JOIN payrollruns pr ON pr.PayPeriodStartDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                LEFT JOIN payslips ps ON pr.PayrollID = ps.PayrollID AND ps.EmployeeID = e.EmployeeID
                LEFT JOIN attendance a ON a.EmployeeID = e.EmployeeID AND a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                LEFT JOIN trainings t ON t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                LEFT JOIN hmoenrollments he ON he.EmployeeID = e.EmployeeID AND he.IsActive = 1
                LEFT JOIN hmoplans hmo ON he.PlanID = hmo.PlanID";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " WHERE e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $summaryData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate derived metrics
        $turnoverRate = 0;
        if ($summaryData['total_active_employees'] > 0) {
            $turnoverRate = ($summaryData['separations_this_month'] / $summaryData['total_active_employees']) * 100 * 12; // Annualized
        }
        
        return [
            'kpi_metrics' => [
                'total_active_employees' => $summaryData['total_active_employees'] ?? 0,
                'monthly_new_hires' => $summaryData['new_hires_this_month'] ?? 0,
                'monthly_separations' => $summaryData['separations_this_month'] ?? 0,
                'annual_turnover_rate' => round($turnoverRate, 2),
                'monthly_payroll_cost' => $summaryData['monthly_payroll_cost'] ?? 0,
                'avg_monthly_salary' => $summaryData['avg_monthly_salary'] ?? 0,
                'monthly_attendance_rate' => round($summaryData['monthly_attendance_rate'] ?? 0, 2),
                'trainings_this_month' => $summaryData['trainings_this_month'] ?? 0,
                'monthly_benefits_cost' => $summaryData['monthly_benefits_cost'] ?? 0
            ],
            'trend_indicators' => [
                'headcount_change' => ($summaryData['new_hires_this_month'] ?? 0) - ($summaryData['separations_this_month'] ?? 0),
                'payroll_change_percent' => 0, // Would need previous month data
                'attendance_trend' => 'stable', // Would need trend analysis
                'training_completion_rate' => 0 // Would need completion data
            ],
            'alerts' => [
                'high_turnover_departments' => [],
                'compliance_issues' => [],
                'budget_variance' => [],
                'attendance_concerns' => []
            ]
        ];
    }

    /**
     * Export data to various formats
     */
    public function exportReportData($reportType, $format, $filters = []) {
        $data = [];
        
        switch ($reportType) {
            case 'employee_demographics':
                $data = $this->getEmployeeDemographicsReport($filters);
                break;
            case 'recruitment_application':
                $data = $this->getRecruitmentApplicationReport($filters);
                break;
            case 'payroll_compensation':
                $data = $this->getPayrollCompensationReport($filters);
                break;
            case 'attendance_leave':
                $data = $this->getAttendanceLeaveReport($filters);
                break;
            case 'benefits_hmo_utilization':
                $data = $this->getBenefitsHMOUtilizationReport($filters);
                break;
            case 'training_development':
                $data = $this->getTrainingDevelopmentReport($filters);
                break;
            case 'employee_relations_engagement':
                $data = $this->getEmployeeRelationsEngagementReport($filters);
                break;
            case 'turnover_retention':
                $data = $this->getTurnoverRetentionReport($filters);
                break;
            case 'compliance_document':
                $data = $this->getComplianceDocumentReport($filters);
                break;
            case 'executive_summary':
                $data = $this->getExecutiveSummaryReport($filters);
                break;
            default:
                $data = $this->getHRReportsDashboard($filters);
        }
        
        return $this->formatExportData($data, $format, $reportType);
    }

    /**
     * Format data for export
     */
    private function formatExportData($data, $format, $reportType) {
        switch ($format) {
            case 'CSV':
                return $this->formatAsCSV($data, $reportType);
            case 'Excel':
                return $this->formatAsExcel($data, $reportType);
            case 'PDF':
                return $this->formatAsPDF($data, $reportType);
            default:
                return $data;
        }
    }

    /**
     * Format data as CSV
     */
    private function formatAsCSV($data, $reportType) {
        $csv = "Report Type,Generated At\n";
        $csv .= "$reportType," . date('Y-m-d H:i:s') . "\n\n";
        
        // Flatten the data structure for CSV
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= "\n$key\n";
                if (!empty($value) && is_array($value[0])) {
                    // Array of objects
                    $headers = array_keys($value[0]);
                    $csv .= implode(',', $headers) . "\n";
                    foreach ($value as $row) {
                        $csv .= implode(',', array_values($row)) . "\n";
                    }
                } else {
                    // Simple array
                    $csv .= implode(',', array_values($value)) . "\n";
                }
            } else {
                $csv .= "$key,$value\n";
            }
        }
        
        return $csv;
    }

    /**
     * Format data as Excel (placeholder)
     */
    private function formatAsExcel($data, $reportType) {
        // TODO: Implement Excel formatting using PhpSpreadsheet
        return [
            'message' => 'Excel export not yet implemented',
            'data' => $data
        ];
    }

    /**
     * Format data as PDF (placeholder)
     */
    private function formatAsPDF($data, $reportType) {
        // TODO: Implement PDF formatting using TCPDF or similar
        return [
            'message' => 'PDF export not yet implemented',
            'data' => $data
        ];
    }

    /**
     * Generate scheduled reports
     */
    public function generateScheduledReports($scheduleType = 'weekly') {
        $reports = [];
        $filters = [];
        
        switch ($scheduleType) {
            case 'daily':
                $reports = ['executive_summary'];
                break;
            case 'weekly':
                $reports = ['employee_demographics', 'attendance_leave', 'compliance_document'];
                break;
            case 'monthly':
                $reports = ['payroll_compensation', 'benefits_hmo_utilization', 'training_development', 'turnover_retention'];
                break;
            case 'quarterly':
                $reports = ['recruitment_application', 'employee_relations_engagement'];
                break;
        }
        
        $generatedReports = [];
        foreach ($reports as $reportType) {
            $generatedReports[$reportType] = $this->exportReportData($reportType, 'CSV', $filters);
        }
        
        return $generatedReports;
    }

    /**
     * Log report generation for audit trail
     */
    public function logReportGeneration($reportType, $format, $filters, $userId) {
        $sql = "INSERT INTO report_generation_log (report_type, format, filters, generated_by, generated_at) 
                VALUES (:report_type, :format, :filters, :generated_by, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':report_type', $reportType);
        $stmt->bindValue(':format', $format);
        $stmt->bindValue(':filters', json_encode($filters));
        $stmt->bindValue(':generated_by', $userId);
        
        return $stmt->execute();
    }
}
?>
