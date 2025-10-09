<?php
/**
 * Analytics Integration for Compensation Planning
 * Handles data visualization and reporting for compensation insights
 */

require_once __DIR__ . '/../config.php';

class AnalyticsIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get compensation analytics dashboard data
     */
    public function getCompensationAnalytics($filters = []) {
        $analytics = [
            'salary_distribution' => $this->getSalaryDistribution($filters),
            'department_analysis' => $this->getDepartmentAnalysis($filters),
            'grade_distribution' => $this->getGradeDistribution($filters),
            'pay_equity_analysis' => $this->getPayEquityAnalysis($filters),
            'trend_analysis' => $this->getTrendAnalysis($filters),
            'summary_metrics' => $this->getSummaryMetrics($filters)
        ];
        
        return $analytics;
    }

    /**
     * Get salary distribution data
     */
    public function getSalaryDistribution($filters = []) {
        $sql = "SELECT 
                    CASE 
                        WHEN es.BaseSalary < 20000 THEN 'Under 20k'
                        WHEN es.BaseSalary < 30000 THEN '20k-30k'
                        WHEN es.BaseSalary < 40000 THEN '30k-40k'
                        WHEN es.BaseSalary < 50000 THEN '40k-50k'
                        WHEN es.BaseSalary < 60000 THEN '50k-60k'
                        WHEN es.BaseSalary < 80000 THEN '60k-80k'
                        WHEN es.BaseSalary < 100000 THEN '80k-100k'
                        ELSE 'Over 100k'
                    END as salary_range,
                    COUNT(*) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY salary_range
                  ORDER BY MIN(es.BaseSalary)";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get department analysis
     */
    public function getDepartmentAnalysis($filters = []) {
        $sql = "SELECT 
                    d.DepartmentName,
                    COUNT(e.EmployeeID) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    STDDEV(es.BaseSalary) as salary_stddev,
                    SUM(es.BaseSalary) as total_salary_cost,
                    -- Calculate salary range
                    MAX(es.BaseSalary) - MIN(es.BaseSalary) as salary_range,
                    -- Calculate coefficient of variation
                    CASE 
                        WHEN AVG(es.BaseSalary) > 0 THEN ROUND((STDDEV(es.BaseSalary) / AVG(es.BaseSalary)) * 100, 2)
                        ELSE 0
                    END as coefficient_of_variation
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY d.DepartmentID, d.DepartmentName
                  ORDER BY avg_salary DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get grade distribution
     */
    public function getGradeDistribution($filters = []) {
        $sql = "SELECT 
                    sg.grade_code,
                    sg.grade_name,
                    COUNT(egm.employee_id) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    AVG(sgs.step_rate) as avg_step_rate
                FROM salary_grades sg
                LEFT JOIN salary_grade_steps sgs ON sg.grade_id = sgs.grade_id
                LEFT JOIN employee_grade_mapping egm ON sg.grade_id = egm.grade_id AND egm.is_active = 1
                LEFT JOIN employees e ON egm.employee_id = e.EmployeeID AND e.IsActive = 1
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE sg.is_active = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY sg.grade_id, sg.grade_code, sg.grade_name
                  ORDER BY sg.grade_code";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pay equity analysis
     */
    public function getPayEquityAnalysis($filters = []) {
        $sql = "SELECT 
                    e.Gender,
                    d.DepartmentName,
                    COUNT(e.EmployeeID) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    -- Calculate pay gap
                    CASE 
                        WHEN e.Gender = 'Male' THEN 
                            AVG(es.BaseSalary) - (SELECT AVG(es2.BaseSalary) 
                                                 FROM employees e2 
                                                 LEFT JOIN employeesalaries es2 ON e2.EmployeeID = es2.EmployeeID AND es2.IsCurrent = 1
                                                 WHERE e2.DepartmentID = e.DepartmentID AND e2.Gender = 'Female' AND e2.IsActive = 1)
                        WHEN e.Gender = 'Female' THEN 
                            AVG(es.BaseSalary) - (SELECT AVG(es2.BaseSalary) 
                                                 FROM employees e2 
                                                 LEFT JOIN employeesalaries es2 ON e2.EmployeeID = es2.EmployeeID AND es2.IsCurrent = 1
                                                 WHERE e2.DepartmentID = e.DepartmentID AND e2.Gender = 'Male' AND e2.IsActive = 1)
                        ELSE 0
                    END as pay_gap
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL AND e.Gender IS NOT NULL";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY e.Gender, d.DepartmentID, d.DepartmentName
                  ORDER BY d.DepartmentName, e.Gender";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis($filters = []) {
        $sql = "SELECT 
                    DATE_FORMAT(scl.created_at, '%Y-%m') as month,
                    COUNT(*) as salary_changes,
                    AVG(scl.new_salary - LAG(scl.new_salary) OVER (PARTITION BY scl.employee_id ORDER BY scl.created_at)) as avg_increase,
                    SUM(scl.new_salary - LAG(scl.new_salary) OVER (PARTITION BY scl.employee_id ORDER BY scl.created_at)) as total_increase
                FROM salary_change_log scl
                WHERE scl.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND scl.employee_id IN (
                        SELECT EmployeeID FROM employees WHERE DepartmentID = :department_id
                      )";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY DATE_FORMAT(scl.created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary metrics
     */
    public function getSummaryMetrics($filters = []) {
        $sql = "SELECT 
                    COUNT(DISTINCT e.EmployeeID) as total_employees,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    SUM(es.BaseSalary) as total_salary_cost,
                    STDDEV(es.BaseSalary) as salary_stddev,
                    -- Calculate median salary
                    (SELECT es2.BaseSalary 
                     FROM employeesalaries es2 
                     LEFT JOIN employees e2 ON es2.EmployeeID = e2.EmployeeID
                     WHERE e2.IsActive = 1 AND es2.IsCurrent = 1
                     ORDER BY es2.BaseSalary 
                     LIMIT 1 OFFSET (SELECT COUNT(*) FROM employeesalaries es3 LEFT JOIN employees e3 ON es3.EmployeeID = e3.EmployeeID WHERE e3.IsActive = 1 AND es3.IsCurrent = 1) / 2
                    ) as median_salary
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate compensation report
     */
    public function generateCompensationReport($reportType, $filters = []) {
        $report = [
            'report_type' => $reportType,
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'data' => []
        ];
        
        switch ($reportType) {
            case 'salary_grade_table':
                $report['data'] = $this->getSalaryGradeTable($filters);
                break;
            case 'employee_grade_mapping':
                $report['data'] = $this->getEmployeeGradeMapping($filters);
                break;
            case 'adjustment_simulation':
                $report['data'] = $this->getAdjustmentSimulation($filters);
                break;
            case 'payroll_impact':
                $report['data'] = $this->getPayrollImpactReport($filters);
                break;
            case 'equity_distribution':
                $report['data'] = $this->getEquityDistributionReport($filters);
                break;
            default:
                $report['data'] = $this->getCompensationAnalytics($filters);
        }
        
        return $report;
    }

    /**
     * Get salary grade table for reporting
     */
    private function getSalaryGradeTable($filters = []) {
        $sql = "SELECT 
                    sg.grade_code,
                    sg.grade_name,
                    sgs.step_number,
                    sgs.step_rate,
                    sgs.min_rate,
                    sgs.max_rate,
                    COUNT(egm.employee_id) as employee_count
                FROM salary_grades sg
                LEFT JOIN salary_grade_steps sgs ON sg.grade_id = sgs.grade_id
                LEFT JOIN employee_grade_mapping egm ON sg.grade_id = egm.grade_id AND egm.is_active = 1
                WHERE sg.is_active = 1
                GROUP BY sg.grade_id, sgs.step_id
                ORDER BY sg.grade_code, sgs.step_number";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee grade mapping for reporting
     */
    private function getEmployeeGradeMapping($filters = []) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.JobTitle,
                    d.DepartmentName,
                    sg.grade_code,
                    sgs.step_number,
                    es.BaseSalary as current_salary,
                    sgs.step_rate as grade_rate,
                    CASE 
                        WHEN es.BaseSalary < sgs.min_rate THEN 'Below Band'
                        WHEN es.BaseSalary > sgs.max_rate THEN 'Above Band'
                        ELSE 'Within Band'
                    END as band_status
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.employee_id AND egm.is_active = 1
                LEFT JOIN salary_grades sg ON egm.grade_id = sg.grade_id
                LEFT JOIN salary_grade_steps sgs ON egm.step_id = sgs.step_id
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " ORDER BY d.DepartmentName, e.LastName";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get adjustment simulation data
     */
    private function getAdjustmentSimulation($filters = []) {
        // This would return simulation results
        return [
            'simulation_id' => $filters['simulation_id'] ?? null,
            'total_impact' => 0,
            'affected_employees' => 0,
            'department_breakdown' => []
        ];
    }

    /**
     * Get payroll impact report
     */
    private function getPayrollImpactReport($filters = []) {
        // This would return payroll impact data
        return [
            'total_payroll_impact' => 0,
            'monthly_impact' => 0,
            'annual_impact' => 0,
            'department_impacts' => []
        ];
    }

    /**
     * Get equity distribution report
     */
    private function getEquityDistributionReport($filters = []) {
        return $this->getPayEquityAnalysis($filters);
    }
}
?>
