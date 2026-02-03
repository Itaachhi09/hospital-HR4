<?php
/**
 * HMO-Analytics Integration
 * Provides healthcare cost analytics and reporting
 */

class HMOAnalyticsIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get healthcare cost dashboard data
     * 
     * @return array Dashboard metrics
     */
    public function getHealthcareDashboard() {
        try {
            $currentYear = date('Y');
            $currentMonth = date('n');

            $data = [
                'overview' => $this->getOverviewMetrics(),
                'monthly_trends' => $this->getMonthlyTrends($currentYear),
                'department_costs' => $this->getDepartmentCosts(),
                'provider_performance' => $this->getProviderPerformance(),
                'plan_utilization' => $this->getPlanUtilization(),
                'top_claim_types' => $this->getTopClaimTypes(),
                'cost_projections' => $this->getCostProjections()
            ];

            return $data;

        } catch (Exception $e) {
            error_log("Get healthcare dashboard error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM employeehmoenrollments WHERE Status = 'Active') as active_enrollments,
                    (SELECT COUNT(DISTINCT EmployeeID) FROM employeehmoenrollments WHERE Status = 'Active') as covered_employees,
                    (SELECT COUNT(*) FROM hmoclaims WHERE Status = 'Pending') as pending_claims,
                    (SELECT COALESCE(SUM(Amount), 0) FROM hmoclaims WHERE Status = 'Pending') as pending_amount,
                    (SELECT COALESCE(SUM(Amount), 0) FROM hmoclaims WHERE Status = 'Approved' AND YEAR(ApprovedDate) = YEAR(CURDATE())) as ytd_claims,
                    (SELECT COALESCE(SUM(Amount), 0) FROM hmoclaims WHERE Status = 'Approved' AND MONTH(ApprovedDate) = MONTH(CURDATE()) AND YEAR(ApprovedDate) = YEAR(CURDATE())) as mtd_claims,
                    (SELECT COALESCE(SUM(MonthlyDeduction + MonthlyContribution), 0) FROM employeehmoenrollments WHERE Status = 'Active') as monthly_premium_cost,
                    (SELECT COALESCE(AVG(DATEDIFF(ApprovedDate, SubmittedDate)), 0) FROM hmoclaims WHERE Status = 'Approved' AND ApprovedDate >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) as avg_processing_days";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly trend data
     */
    private function getMonthlyTrends($year) {
        $sql = "SELECT 
                    DATE_FORMAT(ClaimDate, '%Y-%m') as month,
                    DATE_FORMAT(ClaimDate, '%b %Y') as month_name,
                    COUNT(*) as claim_count,
                    COUNT(DISTINCT e.EmployeeID) as unique_claimants,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as approved_amount,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount
                FROM hmoclaims c
                JOIN employeehmoenrollments e ON c.EnrollmentID = e.EnrollmentID
                WHERE YEAR(c.ClaimDate) = :year
                GROUP BY month, month_name
                ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cost breakdown by department
     */
    private function getDepartmentCosts() {
        $sql = "SELECT 
                    d.DepartmentID,
                    d.DepartmentName,
                    COUNT(DISTINCT e.EnrollmentID) as enrollment_count,
                    COUNT(DISTINCT emp.EmployeeID) as employee_count,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_claims,
                    COALESCE(SUM(e.MonthlyDeduction + e.MonthlyContribution), 0) as monthly_premium_cost,
                    COALESCE(SUM(e.MonthlyDeduction + e.MonthlyContribution) * 12, 0) as annual_premium_cost,
                    COUNT(c.ClaimID) as claim_count,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount
                FROM Departments d
                JOIN employees emp ON d.DepartmentID = emp.DepartmentID
                LEFT JOIN employeehmoenrollments e ON emp.EmployeeID = e.EmployeeID AND e.Status = 'Active'
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID AND c.Status = 'Approved'
                GROUP BY d.DepartmentID, d.DepartmentName
                HAVING enrollment_count > 0
                ORDER BY total_claims DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get provider performance metrics
     */
    private function getProviderPerformance() {
        $sql = "SELECT 
                    p.ProviderID,
                    p.ProviderName,
                    COUNT(DISTINCT pl.PlanID) as plan_count,
                    COUNT(DISTINCT e.EnrollmentID) as enrollment_count,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Approved' THEN c.ClaimID END) as approved_claims,
                    COUNT(CASE WHEN c.Status = 'Denied' THEN c.ClaimID END) as denied_claims,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_approved_amount,
                    COALESCE(AVG(DATEDIFF(c.ApprovedDate, c.SubmittedDate)), 0) as avg_processing_days,
                    ROUND(COUNT(CASE WHEN c.Status = 'Approved' THEN c.ClaimID END) * 100.0 / NULLIF(COUNT(c.ClaimID), 0), 2) as approval_rate
                FROM hmoproviders p
                LEFT JOIN hmoplans pl ON p.ProviderID = pl.ProviderID
                LEFT JOIN employeehmoenrollments e ON pl.PlanID = e.PlanID AND e.Status = 'Active'
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE p.IsActive = 1
                GROUP BY p.ProviderID, p.ProviderName
                ORDER BY enrollment_count DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get plan utilization data
     */
    private function getPlanUtilization() {
        $sql = "SELECT 
                    pl.PlanID,
                    pl.PlanName,
                    pr.ProviderName,
                    pl.MonthlyPremium,
                    pl.MaximumBenefitLimit,
                    COUNT(DISTINCT e.EnrollmentID) as enrollment_count,
                    COUNT(c.ClaimID) as claim_count,
                    COALESCE(SUM(c.Amount), 0) as total_claimed,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount,
                    ROUND(COALESCE(SUM(c.Amount), 0) * 100.0 / NULLIF(SUM(pl.MaximumBenefitLimit), 0), 2) as utilization_rate,
                    (COUNT(DISTINCT e.EnrollmentID) * pl.MonthlyPremium) as monthly_revenue
                FROM hmoplans pl
                JOIN hmoproviders pr ON pl.ProviderID = pr.ProviderID
                LEFT JOIN employeehmoenrollments e ON pl.PlanID = e.PlanID AND e.Status = 'Active'
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID AND c.Status = 'Approved'
                WHERE pl.IsActive = 1
                GROUP BY pl.PlanID, pl.PlanName, pr.ProviderName, pl.MonthlyPremium, pl.MaximumBenefitLimit
                ORDER BY enrollment_count DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top claim types
     */
    private function getTopClaimTypes($limit = 10) {
        $sql = "SELECT 
                    ClaimType,
                    COUNT(*) as claim_count,
                    COALESCE(SUM(Amount), 0) as total_amount,
                    COALESCE(AVG(Amount), 0) as avg_amount,
                    COUNT(CASE WHEN Status = 'Approved' THEN 1 END) as approved_count,
                    ROUND(COUNT(CASE WHEN Status = 'Approved' THEN 1 END) * 100.0 / COUNT(*), 2) as approval_rate
                FROM hmoclaims
                WHERE ClaimType IS NOT NULL
                GROUP BY ClaimType
                ORDER BY total_amount DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cost projections based on historical data
     */
    private function getCostProjections() {
        $sql = "SELECT 
                    (SELECT COALESCE(SUM(MonthlyDeduction + MonthlyContribution), 0) FROM employeehmoenrollments WHERE Status = 'Active') as monthly_premium,
                    (SELECT COALESCE(AVG(monthly_claims), 0) FROM (
                        SELECT COALESCE(SUM(Amount), 0) as monthly_claims
                        FROM hmoclaims
                        WHERE Status = 'Approved'
                            AND ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY YEAR(ClaimDate), MONTH(ClaimDate)
                    ) as mc) as avg_monthly_claims,
                    (SELECT COUNT(*) FROM employeehmoenrollments WHERE Status = 'Active') as active_enrollments";

        $stmt = $this->pdo->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $data['projected_monthly_cost'] = $data['monthly_premium'] + $data['avg_monthly_claims'];
            $data['projected_annual_cost'] = $data['projected_monthly_cost'] * 12;
            $data['cost_per_employee'] = $data['active_enrollments'] > 0 
                ? $data['projected_monthly_cost'] / $data['active_enrollments'] 
                : 0;
        }

        return $data;
    }

    /**
     * Generate comprehensive HMO report
     * 
     * @param string $reportType Type of report (monthly, quarterly, annual)
     * @param string $period Period for the report
     * @return array Report data
     */
    public function generateReport($reportType, $period) {
        try {
            switch ($reportType) {
                case 'monthly':
                    return $this->generateMonthlyReport($period);
                case 'quarterly':
                    return $this->generateQuarterlyReport($period);
                case 'annual':
                    return $this->generateAnnualReport($period);
                case 'employee':
                    return $this->generateEmployeeReport($period);
                default:
                    throw new Exception("Invalid report type");
            }
        } catch (Exception $e) {
            error_log("Generate report error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate monthly report
     */
    private function generateMonthlyReport($yearMonth) {
        list($year, $month) = explode('-', $yearMonth);

        $sql = "SELECT 
                    COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                    COUNT(DISTINCT e.EmployeeID) as unique_employees,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Approved' THEN 1 END) as approved_claims,
                    COUNT(CASE WHEN c.Status = 'Denied' THEN 1 END) as denied_claims,
                    COUNT(CASE WHEN c.Status = 'Pending' THEN 1 END) as pending_claims,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_claim_amount,
                    COALESCE(SUM(e.MonthlyDeduction + e.MonthlyContribution), 0) as total_premium,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount
                FROM employeehmoenrollments e
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID 
                    AND YEAR(c.ClaimDate) = :year 
                    AND MONTH(c.ClaimDate) = :month
                WHERE e.Status = 'Active'
                    AND (e.EffectiveDate <= LAST_DAY(CONCAT(:year2, '-', :month2, '-01'))
                    AND (e.EndDate IS NULL OR e.EndDate >= CONCAT(:year2, '-', :month2, '-01')))";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':year' => $year,
            ':month' => $month,
            ':year2' => $year,
            ':month2' => str_pad($month, 2, '0', STR_PAD_LEFT)
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate quarterly report
     */
    private function generateQuarterlyReport($yearQuarter) {
        list($year, $quarter) = explode('-Q', $yearQuarter);
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        $sql = "SELECT 
                    COUNT(DISTINCT e.EnrollmentID) as avg_enrollments,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Approved' THEN 1 END) as approved_claims,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_claim_amount,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount
                FROM employeehmoenrollments e
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE YEAR(c.ClaimDate) = :year
                    AND MONTH(c.ClaimDate) BETWEEN :start_month AND :end_month
                    AND e.Status = 'Active'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':year' => $year,
            ':start_month' => $startMonth,
            ':end_month' => $endMonth
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate annual report
     */
    private function generateAnnualReport($year) {
        $sql = "SELECT 
                    COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Approved' THEN 1 END) as approved_claims,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_claim_amount,
                    COALESCE(SUM(e.MonthlyDeduction + e.MonthlyContribution) * 12, 0) as total_premium_cost,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount,
                    COALESCE(AVG(DATEDIFF(c.ApprovedDate, c.SubmittedDate)), 0) as avg_processing_days
                FROM employeehmoenrollments e
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID AND YEAR(c.ClaimDate) = :year
                WHERE e.Status = 'Active' OR YEAR(e.CreatedAt) = :year2";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':year' => $year, ':year2' => $year]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate employee-specific report
     */
    private function generateEmployeeReport($employeeId) {
        $sql = "SELECT 
                    e.EmployeeID,
                    emp.FirstName,
                    emp.LastName,
                    emp.EmployeeNumber,
                    p.PlanName,
                    pr.ProviderName,
                    en.EffectiveDate,
                    en.EndDate,
                    p.MaximumBenefitLimit,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_used,
                    (p.MaximumBenefitLimit - COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0)) as remaining_balance,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Pending' THEN 1 END) as pending_claims
                FROM employees e
                JOIN employeehmoenrollments en ON e.EmployeeID = en.EmployeeID
                JOIN hmoplans p ON en.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN employees emp ON e.EmployeeID = emp.EmployeeID
                LEFT JOIN hmoclaims c ON en.EnrollmentID = c.EnrollmentID
                WHERE e.EmployeeID = :employee_id AND en.Status = 'Active'
                GROUP BY e.EmployeeID, emp.FirstName, emp.LastName, emp.EmployeeNumber,
                         p.PlanName, pr.ProviderName, en.EffectiveDate, en.EndDate, p.MaximumBenefitLimit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Export data for Finance integration (HADS)
     * 
     * @param string $period Period in format YYYY-MM
     * @return array Healthcare analytics data
     */
    public function exportToFinanceAnalytics($period) {
        list($year, $month) = explode('-', $period);

        return [
            'period' => $period,
            'summary' => $this->generateMonthlyReport($period),
            'department_breakdown' => $this->getDepartmentCosts(),
            'provider_costs' => $this->getProviderPerformance(),
            'claim_types' => $this->getTopClaimTypes(),
            'projections' => $this->getCostProjections(),
            'export_date' => date('Y-m-d H:i:s')
        ];
    }
}

