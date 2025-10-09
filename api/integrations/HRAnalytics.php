<?php
/**
 * Comprehensive HR Analytics Integration
 * Transforms raw HR and payroll data into actionable insights
 * Integrates with HR Core, Payroll, Compensation, HMO, and Finance
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/HMOAnalyticsIntegration.php';

class HRAnalytics {
    private $pdo;
    private $hmoAnalytics;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->hmoAnalytics = new HMOAnalyticsIntegration();
    }

    /**
     * Get comprehensive HR analytics dashboard
     * Consolidates all key metrics and visualizations
     */
    public function getHRAnalyticsDashboard($filters = []) {
        return [
            'overview' => $this->getOverviewMetrics(),
            'workforce' => $this->getWorkforceAnalytics($filters),
            'payroll' => $this->getPayrollCostAnalytics($filters),
            'benefits' => $this->getBenefitsUtilization($filters),
            'training' => $this->getTrainingAnalytics($filters),
            'attendance' => $this->getAttendanceAnalytics($filters),
            'turnover' => $this->getTurnoverAnalytics($filters),
            'demographics' => $this->getDemographicsAnalytics($filters),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Dashboard Overview Metrics (KPIs)
     */
    public function getOverviewMetrics() {
        $sql = "SELECT 
                    -- Workforce Metrics
                    (SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active') as total_active_employees,
                    (SELECT COUNT(*) FROM employees WHERE MONTH(HireDate) = MONTH(CURDATE()) AND YEAR(HireDate) = YEAR(CURDATE())) as monthly_new_hires,
                    (SELECT COUNT(*) FROM employees WHERE TerminationDate IS NOT NULL AND MONTH(TerminationDate) = MONTH(CURDATE()) AND YEAR(TerminationDate) = YEAR(CURDATE())) as monthly_separations,
                    
                    -- Turnover Rate Calculation (Annualized)
                    ROUND((SELECT COUNT(*) FROM employees WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)) / 
                    NULLIF((SELECT AVG(employee_count) FROM (
                        SELECT COUNT(*) as employee_count FROM employees WHERE EmploymentStatus = 'Active' GROUP BY MONTH(CURDATE())
                    ) as avg_count), 0) * 100, 2) as annual_turnover_rate,
                    
                    -- Payroll Metrics
                    (SELECT COALESCE(SUM(BaseSalary), 0) FROM employeesalaries WHERE IsCurrent = 1) as total_monthly_payroll_cost,
                    (SELECT COALESCE(AVG(BaseSalary), 0) FROM employeesalaries WHERE IsCurrent = 1) as avg_salary,
                    
                    -- Benefits Metrics
                    (SELECT COUNT(*) FROM employeehmoenrollments WHERE Status = 'Active') as active_hmo_enrollments,
                    (SELECT COALESCE(SUM(MonthlyDeduction + MonthlyContribution), 0) FROM employeehmoenrollments WHERE Status = 'Active') as total_monthly_hmo_cost,
                    
                    -- Attendance & Leave
                    (SELECT COALESCE(AVG(attendance_rate), 0) FROM (
                        SELECT COUNT(*) / NULLIF(DATEDIFF(CURDATE(), DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) * 100 as attendance_rate
                        FROM attendancerecords WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND Status = 'Present'
                        GROUP BY EmployeeID
                    ) as avg_monthly_attendance) as absenteeism_rate,
                    
                    (SELECT COUNT(*) FROM leaverequests WHERE Status = 'Pending') as pending_leave_requests,
                    
                    -- Training & Development
                    (SELECT 0) as trainings_this_year,
                    (SELECT 0) as avg_training_completion_rate,
                    
                    -- Tenure Metrics
                    (SELECT COALESCE(AVG(TIMESTAMPDIFF(YEAR, HireDate, CURDATE())), 0) FROM employees WHERE EmploymentStatus = 'Active') as avg_employee_tenure_years,
                    
                    -- Cost per Employee
                    ROUND((SELECT COALESCE(SUM(BaseSalary), 0) FROM employeesalaries WHERE IsCurrent = 1) / 
                    NULLIF((SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active'), 0), 2) as cost_per_employee";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Workforce Analytics
     * Headcount, distribution, turnover, demographics
     */
    public function getWorkforceAnalytics($filters = []) {
        return [
            'headcount_by_department' => $this->getHeadcountByDepartment($filters),
            'headcount_by_employment_type' => $this->getHeadcountByEmploymentType($filters),
            'headcount_trend' => $this->getHeadcountTrend($filters),
            'new_hires_trend' => $this->getNewHiresTrend($filters),
            'separations_trend' => $this->getSeparationsTrend($filters),
            'turnover_by_department' => $this->getTurnoverByDepartment($filters),
            'gender_distribution' => $this->getGenderDistribution($filters),
            'age_distribution' => $this->getAgeDistribution($filters),
            'tenure_distribution' => $this->getTenureDistribution($filters)
        ];
    }

    private function getHeadcountByDepartment($filters = []) {
        $sql = "SELECT 
                    COALESCE(d.DepartmentName, 'Unassigned') as department_name,
                    COUNT(e.EmployeeID) as headcount,
                    COUNT(CASE WHEN e.Gender = 'Male' THEN 1 END) as male_count,
                    COUNT(CASE WHEN e.Gender = 'Female' THEN 1 END) as female_count,
                    COUNT(CASE WHEN e.EmploymentType = 'Regular' THEN 1 END) as regular_count,
                    COUNT(CASE WHEN e.EmploymentType = 'Contractual' THEN 1 END) as contractual_count,
                    COALESCE(AVG(es.BaseSalary), 0) as avg_salary
                FROM employees e
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.EmploymentStatus = 'Active'";

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND e.BranchID = :branch_id";
        }

        $sql .= " GROUP BY d.DepartmentID, d.DepartmentName ORDER BY headcount DESC";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        if (!empty($filters['branch_id'])) {
            $stmt->bindValue(':branch_id', $filters['branch_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getHeadcountByEmploymentType($filters = []) {
        $sql = "SELECT 
                    COALESCE(e.EmploymentType, 'Unknown') as employment_type,
                    COUNT(e.EmployeeID) as headcount,
                    ROUND(COUNT(e.EmployeeID) * 100.0 / NULLIF((SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active'), 0), 2) as percentage
                FROM employees e
                WHERE e.EmploymentStatus = 'Active'";

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
        }

        $sql .= " GROUP BY e.EmploymentType ORDER BY headcount DESC";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getHeadcountTrend($filters = []) {
        $months = $filters['months'] ?? 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(date_point, '%Y-%m') as month,
                    DATE_FORMAT(date_point, '%b %Y') as month_name,
                    (SELECT COUNT(*) FROM employees 
                     WHERE HireDate <= LAST_DAY(date_point) 
                     AND (TerminationDate IS NULL OR TerminationDate > LAST_DAY(date_point))
                    ) as headcount
                FROM (
                    SELECT DATE_SUB(LAST_DAY(CURDATE()), INTERVAL n MONTH) as date_point
                    FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                          UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) numbers
                ) dates
                WHERE date_point >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNewHiresTrend($filters = []) {
        $months = $filters['months'] ?? 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(HireDate, '%Y-%m') as month,
                    DATE_FORMAT(HireDate, '%b %Y') as month_name,
                    COUNT(*) as new_hires,
                    COUNT(CASE WHEN Gender = 'Male' THEN 1 END) as male_hires,
                    COUNT(CASE WHEN Gender = 'Female' THEN 1 END) as female_hires
                FROM employees
                WHERE HireDate >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)";

        if (!empty($filters['department_id'])) {
            $sql .= " AND DepartmentID = :department_id";
        }

        $sql .= " GROUP BY DATE_FORMAT(HireDate, '%Y-%m'), DATE_FORMAT(HireDate, '%b %Y')
                  ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSeparationsTrend($filters = []) {
        $months = $filters['months'] ?? 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(TerminationDate, '%Y-%m') as month,
                    DATE_FORMAT(TerminationDate, '%b %Y') as month_name,
                    COUNT(*) as separations,
                    COUNT(CASE WHEN TerminationReason = 'Resignation' THEN 1 END) as resignations,
                    COUNT(CASE WHEN TerminationReason = 'Termination' THEN 1 END) as terminations,
                    COUNT(CASE WHEN TerminationReason = 'Retirement' THEN 1 END) as retirements
                FROM employees
                WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    AND TerminationDate IS NOT NULL";

        if (!empty($filters['department_id'])) {
            $sql .= " AND DepartmentID = :department_id";
        }

        $sql .= " GROUP BY DATE_FORMAT(TerminationDate, '%Y-%m'), DATE_FORMAT(TerminationDate, '%b %Y')
                  ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTurnoverByDepartment($filters = []) {
        $sql = "SELECT 
                    COALESCE(d.DepartmentName, 'Unassigned') as department_name,
                    COUNT(DISTINCT e.EmployeeID) as total_employees,
                    COUNT(CASE WHEN e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) as separations_12mo,
                    ROUND(COUNT(CASE WHEN e.TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) * 100.0 / 
                        NULLIF(COUNT(DISTINCT e.EmployeeID), 0), 2) as turnover_rate
                FROM employees e
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                WHERE e.HireDate <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY d.DepartmentID, d.DepartmentName
                HAVING total_employees > 0
                ORDER BY turnover_rate DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getGenderDistribution($filters = []) {
        $sql = "SELECT 
                    COALESCE(e.Gender, 'Unknown') as gender,
                    COUNT(e.EmployeeID) as count,
                    ROUND(COUNT(e.EmployeeID) * 100.0 / NULLIF((SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active'), 0), 2) as percentage
                FROM employees e
                WHERE e.EmploymentStatus = 'Active'";

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
        }

        $sql .= " GROUP BY e.Gender ORDER BY count DESC";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAgeDistribution($filters = []) {
        $sql = "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) < 25 THEN 'Under 25'
                        WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) < 35 THEN '25-34'
                        WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) < 45 THEN '35-44'
                        WHEN TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()) < 55 THEN '45-54'
                        ELSE '55+'
                    END as age_range,
                    COUNT(e.EmployeeID) as count,
                    ROUND(AVG(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE())), 1) as avg_age
                FROM employees e
                WHERE e.EmploymentStatus = 'Active' AND e.DateOfBirth IS NOT NULL";

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
        }

        $sql .= " GROUP BY age_range
                  ORDER BY MIN(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE()))";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTenureDistribution($filters = []) {
        $sql = "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) < 1 THEN 'Less than 1 year'
                        WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) < 3 THEN '1-2 years'
                        WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) < 5 THEN '3-4 years'
                        WHEN TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()) < 10 THEN '5-9 years'
                        ELSE '10+ years'
                    END as tenure_range,
                    COUNT(e.EmployeeID) as count,
                    ROUND(AVG(TIMESTAMPDIFF(MONTH, e.HireDate, CURDATE())) / 12, 1) as avg_tenure_years
                FROM employees e
                WHERE e.EmploymentStatus = 'Active'";

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
        }

        $sql .= " GROUP BY tenure_range
                  ORDER BY MIN(TIMESTAMPDIFF(YEAR, e.HireDate, CURDATE()))";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['department_id'])) {
            $stmt->bindValue(':department_id', $filters['department_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Payroll Cost Analytics
     * Track payroll costs, trends, and composition
     */
    public function getPayrollCostAnalytics($filters = []) {
        return [
            'cost_by_department' => $this->getPayrollCostByDepartment($filters),
            'cost_trend' => $this->getPayrollCostTrend($filters),
            'cost_composition' => $this->getPayrollCostComposition($filters),
            'deduction_breakdown' => $this->getDeductionBreakdown($filters),
            'bonus_analysis' => $this->getBonusAnalysis($filters),
            'budget_vs_actual' => $this->getBudgetVsActual($filters)
        ];
    }

    private function getPayrollCostByDepartment($filters = []) {
        $sql = "SELECT 
                    COALESCE(d.DepartmentName, 'Unassigned') as department_name,
                    COUNT(DISTINCT e.EmployeeID) as employee_count,
                    COALESCE(SUM(es.BaseSalary), 0) as total_base_salary,
                    COALESCE(AVG(es.BaseSalary), 0) as avg_base_salary,
                    COALESCE((SELECT SUM(BonusAmount) FROM bonuses b WHERE b.EmployeeID = e.EmployeeID), 0) as total_bonuses,
                    COALESCE((SELECT SUM(DeductionAmount) FROM deductions ded WHERE ded.EmployeeID = e.EmployeeID), 0) as total_deductions
                FROM employees e
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.EmploymentStatus = 'Active'";

        if (!empty($filters['branch_id'])) {
            $sql .= " AND e.BranchID = :branch_id";
        }

        $sql .= " GROUP BY d.DepartmentID, d.DepartmentName ORDER BY total_base_salary DESC";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filters['branch_id'])) {
            $stmt->bindValue(':branch_id', $filters['branch_id'], PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPayrollCostTrend($filters = []) {
        $months = $filters['months'] ?? 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(pr.PayPeriodStart, '%Y-%m') as month,
                    DATE_FORMAT(pr.PayPeriodStart, '%b %Y') as month_name,
                    COUNT(DISTINCT pr.PayrollRunID) as payroll_runs,
                    COALESCE(SUM(pr.TotalGrossPay), 0) as total_gross,
                    COALESCE(SUM(pr.TotalDeductions), 0) as total_deductions,
                    COALESCE(SUM(pr.TotalNetPay), 0) as total_net,
                    COUNT(DISTINCT ps.EmployeeID) as employees_paid
                FROM payroll_runs pr
                LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollID
                WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    AND pr.Status IN ('Completed', 'Paid')
                GROUP BY DATE_FORMAT(pr.PayPeriodStart, '%Y-%m'), DATE_FORMAT(pr.PayPeriodStart, '%b %Y')
                ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPayrollCostComposition($filters = []) {
        $sql = "SELECT 
                    'Base Salary' as component,
                    COALESCE(SUM(es.BaseSalary), 0) as amount,
                    ROUND(SUM(es.BaseSalary) * 100.0 / NULLIF(SUM(es.BaseSalary + COALESCE(b.total_bonuses, 0)), 0), 2) as percentage
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN (SELECT EmployeeID, SUM(BonusAmount) as total_bonuses FROM bonuses GROUP BY EmployeeID) b ON e.EmployeeID = b.EmployeeID
                WHERE e.EmploymentStatus = 'Active'
                UNION ALL
                SELECT 
                    'Bonuses & Allowances' as component,
                    COALESCE(SUM(BonusAmount), 0) as amount,
                    ROUND(SUM(BonusAmount) * 100.0 / NULLIF((SELECT SUM(BaseSalary) + SUM(BonusAmount) FROM employees e2 LEFT JOIN employeesalaries es2 ON e2.EmployeeID = es2.EmployeeID AND es2.IsCurrent = 1 LEFT JOIN bonuses b2 ON e2.EmployeeID = b2.EmployeeID WHERE e2.EmploymentStatus = 'Active'), 0), 2) as percentage
                FROM bonuses";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDeductionBreakdown($filters = []) {
        $sql = "SELECT 
                    d.DeductionType,
                    COUNT(*) as transaction_count,
                    COALESCE(SUM(d.DeductionAmount), 0) as total_amount,
                    COALESCE(AVG(d.DeductionAmount), 0) as avg_amount
                FROM deductions d
                GROUP BY d.DeductionType
                ORDER BY total_amount DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBonusAnalysis($filters = []) {
        $sql = "SELECT 
                    b.BonusType,
                    COUNT(*) as count,
                    COALESCE(SUM(b.BonusAmount), 0) as total_amount,
                    COALESCE(AVG(b.BonusAmount), 0) as avg_amount,
                    COUNT(DISTINCT b.EmployeeID) as unique_recipients
                FROM bonuses b
                GROUP BY b.BonusType
                ORDER BY total_amount DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBudgetVsActual($filters = []) {
        // This would integrate with finance module for budget data
        // For now, return placeholder structure
        return [
            'current_month' => [
                'budgeted' => 0,
                'actual' => 0,
                'variance' => 0,
                'variance_percentage' => 0
            ],
            'ytd' => [
                'budgeted' => 0,
                'actual' => 0,
                'variance' => 0,
                'variance_percentage' => 0
            ]
        ];
    }

    /**
     * Benefits Utilization Analytics
     * Integrates with HMO module
     */
    public function getBenefitsUtilization($filters = []) {
        // Leverage HMO Analytics Integration
        return [
            'hmo_overview' => $this->hmoAnalytics->getHealthcareDashboard()['overview'] ?? [],
            'hmo_by_department' => $this->hmoAnalytics->getHealthcareDashboard()['department_costs'] ?? [],
            'claims_trend' => $this->hmoAnalytics->getHealthcareDashboard()['monthly_trends'] ?? [],
            'provider_utilization' => $this->hmoAnalytics->getHealthcareDashboard()['plan_utilization'] ?? [],
            'total_benefit_value' => $this->getTotalBenefitValue($filters)
        ];
    }

    private function getTotalBenefitValue($filters = []) {
        $sql = "SELECT 
                    COUNT(DISTINCT e.EmployeeID) as employees_with_benefits,
                    0 as total_monthly_benefit_value,
                    0 as total_annual_benefit_value,
                    0 as avg_monthly_benefit_per_employee
                FROM employees e
                -- LEFT JOIN employee_compensation_benefits cb ON e.EmployeeID = cb.EmployeeID AND cb.IsActive = 1
                WHERE e.EmploymentStatus = 'Active'";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Training & Competency Analytics
     */
    public function getTrainingAnalytics($filters = []) {
        return [
            'training_completion' => $this->getTrainingCompletion($filters),
            'training_hours_by_department' => $this->getTrainingHoursByDepartment($filters),
            'competency_scores' => $this->getCompetencyScores($filters),
            'skill_gaps' => $this->getSkillGaps($filters)
        ];
    }

    private function getTrainingCompletion($filters = []) {
        // Training tables don't exist yet - return empty data
        return [
            'total_trainings' => 0,
            'employees_trained' => 0,
            'completed_trainings' => 0,
            'completion_rate' => 0,
            'total_training_hours' => 0
        ];
    }

    private function getTrainingHoursByDepartment($filters = []) {
        // Training tables don't exist yet - return empty data
        return [];
    }

    private function getCompetencyScores($filters = []) {
        // Placeholder - would integrate with competency assessment system
        return [];
    }

    private function getSkillGaps($filters = []) {
        // Placeholder - would analyze required vs. actual skills
        return [];
    }

    /**
     * Attendance & Leave Analytics
     */
    public function getAttendanceAnalytics($filters = []) {
        return [
            'attendance_rate' => $this->getAttendanceRate($filters),
            'absenteeism_trend' => $this->getAbsenteeismTrend($filters),
            'leave_utilization' => $this->getLeaveUtilization($filters),
            'attendance_by_department' => $this->getAttendanceByDepartment($filters)
        ];
    }

    private function getAttendanceRate($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN Status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN Status = 'Absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN Status = 'Late' THEN 1 END) as late_count,
                    ROUND(COUNT(CASE WHEN Status = 'Present' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as attendance_rate,
                    ROUND(COUNT(CASE WHEN Status = 'Absent' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as absenteeism_rate
                FROM attendancerecords
                WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getAbsenteeismTrend($filters = []) {
        $months = $filters['months'] ?? 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(AttendanceDate, '%Y-%m') as month,
                    DATE_FORMAT(AttendanceDate, '%b %Y') as month_name,
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN Status = 'Absent' THEN 1 END) as absent_days,
                    ROUND(COUNT(CASE WHEN Status = 'Absent' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as absenteeism_rate
                FROM attendancerecords
                WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(AttendanceDate, '%Y-%m'), DATE_FORMAT(AttendanceDate, '%b %Y')
                ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLeaveUtilization($filters = []) {
        $sql = "SELECT 
                    lt.TypeName as LeaveTypeName,
                    COUNT(lr.RequestID) as total_requests,
                    COUNT(CASE WHEN lr.Status = 'Approved' THEN 1 END) as approved_requests,
                    COALESCE(SUM(CASE WHEN lr.Status = 'Approved' THEN DATEDIFF(lr.EndDate, lr.StartDate) + 1 ELSE 0 END), 0) as total_days_used,
                    ROUND(COUNT(CASE WHEN lr.Status = 'Approved' THEN 1 END) * 100.0 / NULLIF(COUNT(lr.RequestID), 0), 2) as approval_rate
                FROM leavetypes lt
                LEFT JOIN leaverequests lr ON lt.LeaveTypeID = lr.LeaveTypeID
                WHERE lr.StartDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY lt.LeaveTypeID, lt.TypeName
                ORDER BY total_days_used DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAttendanceByDepartment($filters = []) {
        $sql = "SELECT 
                    COALESCE(d.DepartmentName, 'Unassigned') as department_name,
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN a.Status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN a.Status = 'Absent' THEN 1 END) as absent_count,
                    ROUND(COUNT(CASE WHEN a.Status = 'Present' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as attendance_rate
                FROM attendancerecords a
                LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                WHERE a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY d.DepartmentID, d.DepartmentName
                ORDER BY attendance_rate DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Turnover Analytics
     */
    public function getTurnoverAnalytics($filters = []) {
        return [
            'overall_turnover' => $this->getOverallTurnover($filters),
            'turnover_by_reason' => $this->getTurnoverByReason($filters),
            'turnover_by_tenure' => $this->getTurnoverByTenure($filters),
            'retention_rate' => $this->getRetentionRate($filters)
        ];
    }

    private function getOverallTurnover($filters = []) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM employees WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)) as separations_12mo,
                    (SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active') as current_headcount,
                    (SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active') as avg_headcount_12mo,
                    ROUND((SELECT COUNT(*) FROM employees WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)) * 100.0 / 
                        NULLIF((SELECT COUNT(*) FROM employees WHERE EmploymentStatus = 'Active'), 0), 2) as turnover_rate_annual";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTurnoverByReason($filters = []) {
        $sql = "SELECT 
                    COALESCE(TerminationReason, 'Unknown') as reason,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / NULLIF((SELECT COUNT(*) FROM employees WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)), 0), 2) as percentage
                FROM employees
                WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY TerminationReason
                ORDER BY count DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTurnoverByTenure($filters = []) {
        $sql = "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(MONTH, HireDate, TerminationDate) < 6 THEN 'Less than 6 months'
                        WHEN TIMESTAMPDIFF(MONTH, HireDate, TerminationDate) < 12 THEN '6-12 months'
                        WHEN TIMESTAMPDIFF(YEAR, HireDate, TerminationDate) < 2 THEN '1-2 years'
                        WHEN TIMESTAMPDIFF(YEAR, HireDate, TerminationDate) < 5 THEN '2-5 years'
                        ELSE '5+ years'
                    END as tenure_at_separation,
                    COUNT(*) as count,
                    ROUND(AVG(TIMESTAMPDIFF(MONTH, HireDate, TerminationDate)) / 12, 1) as avg_tenure_years
                FROM employees
                WHERE TerminationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    AND TerminationDate IS NOT NULL
                GROUP BY tenure_at_separation
                ORDER BY MIN(TIMESTAMPDIFF(MONTH, HireDate, TerminationDate))";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRetentionRate($filters = []) {
        $sql = "SELECT 
                    COUNT(CASE WHEN HireDate <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND (TerminationDate IS NULL OR TerminationDate > CURDATE()) THEN 1 END) as retained_employees,
                    COUNT(CASE WHEN HireDate <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) as total_eligible,
                    ROUND(COUNT(CASE WHEN HireDate <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND (TerminationDate IS NULL OR TerminationDate > CURDATE()) THEN 1 END) * 100.0 / 
                        NULLIF(COUNT(CASE WHEN HireDate <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END), 0), 2) as retention_rate
                FROM employees";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Demographics Analytics
     */
    public function getDemographicsAnalytics($filters = []) {
        return [
            'gender_by_level' => $this->getGenderByLevel($filters),
            'age_by_department' => $this->getAgeByDepartment($filters),
            'diversity_metrics' => $this->getDiversityMetrics($filters)
        ];
    }

    private function getGenderByLevel($filters = []) {
        $sql = "SELECT 
                    COALESCE(jr.JobRoleName, 'Unknown') as level,
                    COUNT(CASE WHEN e.Gender = 'Male' THEN 1 END) as male_count,
                    COUNT(CASE WHEN e.Gender = 'Female' THEN 1 END) as female_count,
                    COUNT(*) as total_count,
                    ROUND(COUNT(CASE WHEN e.Gender = 'Female' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as female_percentage
                FROM employees e
                LEFT JOIN job_roles jr ON e.JobRoleID = jr.JobRoleID
                WHERE e.EmploymentStatus = 'Active'
                GROUP BY e.JobRoleID, jr.JobRoleName
                ORDER BY level";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAgeByDepartment($filters = []) {
        $sql = "SELECT 
                    COALESCE(d.DepartmentName, 'Unassigned') as department_name,
                    COUNT(*) as employee_count,
                    ROUND(AVG(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE())), 1) as avg_age,
                    MIN(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE())) as min_age,
                    MAX(TIMESTAMPDIFF(YEAR, e.DateOfBirth, CURDATE())) as max_age
                FROM employees e
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                WHERE e.EmploymentStatus = 'Active' AND e.DateOfBirth IS NOT NULL
                GROUP BY d.DepartmentID, d.DepartmentName
                ORDER BY avg_age DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDiversityMetrics($filters = []) {
        return [
            'gender_distribution' => $this->getGenderDistribution($filters),
            'age_distribution' => $this->getAgeDistribution($filters)
        ];
    }

    /**
     * Export analytics data for Finance/HADS integration
     */
    public function exportToFinanceAnalytics($period = null) {
        $period = $period ?? date('Y-m');
        list($year, $month) = explode('-', $period);

        return [
            'period' => $period,
            'workforce_summary' => $this->getOverviewMetrics(),
            'payroll_costs' => $this->getPayrollCostByDepartment([]),
            'benefit_costs' => $this->getBenefitsUtilization([]),
            'headcount_trend' => $this->getHeadcountTrend(['months' => 12]),
            'export_date' => date('Y-m-d H:i:s')
        ];
    }
}
?>

