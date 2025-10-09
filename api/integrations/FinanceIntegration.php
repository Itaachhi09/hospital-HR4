<?php
/**
 * Finance Integration for Compensation Planning
 * Handles budget impact reporting and financial analysis
 */

require_once __DIR__ . '/../config.php';

class FinanceIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Calculate budget impact of salary changes
     */
    public function calculateBudgetImpact($salaryChanges, $fiscalYear = null) {
        if (!$fiscalYear) {
            $fiscalYear = date('Y');
        }
        
        $totalAnnualImpact = 0;
        $departmentImpacts = [];
        $monthlyImpacts = [];
        
        foreach ($salaryChanges as $change) {
            $employeeId = $change['employee_id'];
            $currentSalary = $change['current_salary'];
            $newSalary = $change['new_salary'];
            $departmentId = $change['department_id'] ?? null;
            
            // Calculate annual impact
            $annualImpact = ($newSalary - $currentSalary) * 12;
            $totalAnnualImpact += $annualImpact;
            
            // Group by department
            if ($departmentId) {
                if (!isset($departmentImpacts[$departmentId])) {
                    $departmentImpacts[$departmentId] = 0;
                }
                $departmentImpacts[$departmentId] += $annualImpact;
            }
            
            // Calculate monthly impacts
            $monthlyImpact = $newSalary - $currentSalary;
            for ($month = 1; $month <= 12; $month++) {
                if (!isset($monthlyImpacts[$month])) {
                    $monthlyImpacts[$month] = 0;
                }
                $monthlyImpacts[$month] += $monthlyImpact;
            }
        }
        
        return [
            'fiscal_year' => $fiscalYear,
            'total_annual_impact' => $totalAnnualImpact,
            'department_impacts' => $departmentImpacts,
            'monthly_impacts' => $monthlyImpacts,
            'change_count' => count($salaryChanges)
        ];
    }

    /**
     * Send budget impact report to Finance
     */
    public function sendBudgetImpactReport($impactData, $reportType = 'salary_adjustment') {
        try {
            // Create budget impact record
            $sql = "INSERT INTO budget_impact_reports 
                    (report_type, fiscal_year, total_impact, impact_data, status, created_at) 
                    VALUES (:report_type, :fiscal_year, :total_impact, :impact_data, 'pending', NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':report_type', $reportType);
            $stmt->bindParam(':fiscal_year', $impactData['fiscal_year']);
            $stmt->bindParam(':total_impact', $impactData['total_annual_impact']);
            $stmt->bindParam(':impact_data', json_encode($impactData));
            $stmt->execute();
            
            $reportId = $this->pdo->lastInsertId();
            
            // Log the report
            $this->logFinanceReport($reportId, $impactData);
            
            return [
                'success' => true,
                'message' => 'Budget impact report sent to Finance',
                'data' => [
                    'report_id' => $reportId,
                    'fiscal_year' => $impactData['fiscal_year'],
                    'total_impact' => $impactData['total_annual_impact']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send budget impact report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get budget allocation for compensation
     */
    public function getCompensationBudget($fiscalYear = null) {
        if (!$fiscalYear) {
            $fiscalYear = date('Y');
        }
        
        $sql = "SELECT 
                    b.budget_category,
                    b.allocated_amount,
                    b.used_amount,
                    b.remaining_amount,
                    b.percentage_used,
                    b.last_updated
                FROM budget_allocations b
                WHERE b.fiscal_year = :fiscal_year 
                AND b.budget_category IN ('Compensation', 'Salaries', 'Personnel Costs')
                ORDER BY b.allocated_amount DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':fiscal_year', $fiscalYear);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if salary changes fit within budget
     */
    public function checkBudgetAvailability($totalImpact, $fiscalYear = null) {
        if (!$fiscalYear) {
            $fiscalYear = date('Y');
        }
        
        $budget = $this->getCompensationBudget($fiscalYear);
        $availableBudget = 0;
        
        foreach ($budget as $category) {
            $availableBudget += $category['remaining_amount'];
        }
        
        $fitsInBudget = $totalImpact <= $availableBudget;
        $shortfall = max(0, $totalImpact - $availableBudget);
        
        return [
            'fits_in_budget' => $fitsInBudget,
            'available_budget' => $availableBudget,
            'required_amount' => $totalImpact,
            'shortfall' => $shortfall,
            'utilization_percentage' => $availableBudget > 0 ? ($totalImpact / $availableBudget) * 100 : 0
        ];
    }

    /**
     * Generate financial forecast for compensation changes
     */
    public function generateFinancialForecast($salaryChanges, $forecastMonths = 12) {
        $forecast = [];
        $cumulativeImpact = 0;
        
        for ($month = 1; $month <= $forecastMonths; $month++) {
            $monthlyImpact = 0;
            foreach ($salaryChanges as $change) {
                $monthlyImpact += $change['new_salary'] - $change['current_salary'];
            }
            
            $cumulativeImpact += $monthlyImpact;
            
            $forecast[] = [
                'month' => $month,
                'monthly_impact' => $monthlyImpact,
                'cumulative_impact' => $cumulativeImpact,
                'date' => date('Y-m-01', strtotime("+{$month} months"))
            ];
        }
        
        return $forecast;
    }

    /**
     * Get cost center analysis for compensation
     */
    public function getCostCenterAnalysis($departmentId = null) {
        $sql = "SELECT 
                    d.DepartmentID,
                    d.DepartmentName,
                    COUNT(e.EmployeeID) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    SUM(es.BaseSalary) as total_salary_cost,
                    -- Calculate cost per employee
                    ROUND(SUM(es.BaseSalary) / COUNT(e.EmployeeID), 2) as cost_per_employee,
                    -- Calculate salary variance
                    STDDEV(es.BaseSalary) as salary_variance
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";
        
        if ($departmentId) {
            $sql .= " AND d.DepartmentID = :department_id";
        }
        
        $sql .= " GROUP BY d.DepartmentID, d.DepartmentName
                  ORDER BY total_salary_cost DESC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($departmentId) {
            $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate ROI analysis for compensation changes
     */
    public function generateROIAnalysis($salaryChanges, $expectedProductivityGains = []) {
        $totalInvestment = 0;
        $totalExpectedReturn = 0;
        
        foreach ($salaryChanges as $change) {
            $annualInvestment = ($change['new_salary'] - $change['current_salary']) * 12;
            $totalInvestment += $annualInvestment;
            
            // Calculate expected return based on productivity gains
            $employeeId = $change['employee_id'];
            $productivityGain = $expectedProductivityGains[$employeeId] ?? 0;
            $expectedReturn = $change['current_salary'] * ($productivityGain / 100);
            $totalExpectedReturn += $expectedReturn;
        }
        
        $roi = $totalInvestment > 0 ? (($totalExpectedReturn - $totalInvestment) / $totalInvestment) * 100 : 0;
        
        return [
            'total_investment' => $totalInvestment,
            'total_expected_return' => $totalExpectedReturn,
            'net_return' => $totalExpectedReturn - $totalInvestment,
            'roi_percentage' => $roi,
            'payback_period_months' => $totalInvestment > 0 ? ($totalInvestment / ($totalExpectedReturn / 12)) : 0
        ];
    }

    /**
     * Log finance report
     */
    private function logFinanceReport($reportId, $impactData) {
        $sql = "INSERT INTO finance_report_log 
                (report_id, report_type, impact_data, created_at) 
                VALUES (:report_id, 'budget_impact', :impact_data, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->bindParam(':impact_data', json_encode($impactData));
        $stmt->execute();
    }

    /**
     * Get financial summary for compensation planning
     */
    public function getFinancialSummary($fiscalYear = null) {
        if (!$fiscalYear) {
            $fiscalYear = date('Y');
        }
        
        $sql = "SELECT 
                    'Total Compensation Budget' as metric,
                    SUM(allocated_amount) as value
                FROM budget_allocations 
                WHERE fiscal_year = :fiscal_year 
                AND budget_category IN ('Compensation', 'Salaries', 'Personnel Costs')
                
                UNION ALL
                
                SELECT 
                    'Current Compensation Spend' as metric,
                    SUM(used_amount) as value
                FROM budget_allocations 
                WHERE fiscal_year = :fiscal_year 
                AND budget_category IN ('Compensation', 'Salaries', 'Personnel Costs')
                
                UNION ALL
                
                SELECT 
                    'Remaining Budget' as metric,
                    SUM(remaining_amount) as value
                FROM budget_allocations 
                WHERE fiscal_year = :fiscal_year 
                AND budget_category IN ('Compensation', 'Salaries', 'Personnel Costs')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':fiscal_year', $fiscalYear);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
