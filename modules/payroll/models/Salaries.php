<?php
require_once __DIR__ . '/../config.php';

class Salaries {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all employee salaries with comprehensive data from HR modules
     */
    public function getAllSalaries($filters = []) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeID as EmployeeNumber,
                    e.JobTitle as PositionName,
                    'N/A' as DepartmentName,
                    es.BaseSalary,
                    es.PayFrequency,
                    es.PayRate,
                    es.EffectiveDate,
                    es.IsCurrent,
                    'N/A' as BranchName,
                    'N/A' as BranchCode,
                    -- Calculate current hourly/daily rates
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22 / 8, 2)
                        WHEN es.PayFrequency = 'Daily' THEN ROUND(es.BaseSalary / 8, 2)
                        ELSE es.PayRate
                    END as hourly_rate,
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22, 2)
                        WHEN es.PayFrequency = 'Daily' THEN es.BaseSalary
                        ELSE ROUND(es.PayRate * 8, 2)
                    END as daily_rate,
                    0 as total_adjustments,
                    0 as leave_deductions,
                    0 as undertime_deductions,
                    0 as overtime_additions
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";

        $params = [];
        
        // Apply filters
        // Branch filter disabled - column doesn't exist in employees table
        // if (!empty($filters['branch_id'])) {
        //     $sql .= " AND e.BranchID = :branch_id";
        //     $params[':branch_id'] = $filters['branch_id'];
        // }
        
        // Department filter
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        // Position filter disabled - column doesn't exist in employees table
        // if (!empty($filters['position_id'])) {
        //     $sql .= " AND e.PositionID = :position_id";
        //     $params[':position_id'] = $filters['position_id'];
        // }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search1 
                     OR e.JobTitle LIKE :search2 
                     OR e.Email LIKE :search3)";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
        }

        $sql .= " ORDER BY e.LastName, e.FirstName";

        // Add pagination
        $page = max(1, $filters['page'] ?? 1);
        $limit = max(1, min(100, $filters['limit'] ?? 50));
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM employees e
                    LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                    WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL";
        
        $countParams = [];
        // Department filter
        if (!empty($filters['department_id'])) {
            $countSql .= " AND e.DepartmentID = :department_id";
            $countParams[':department_id'] = $filters['department_id'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $countSql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search1 
                         OR e.JobTitle LIKE :search2 
                         OR e.Email LIKE :search3)";
            $countParams[':search1'] = $searchTerm;
            $countParams[':search2'] = $searchTerm;
            $countParams[':search3'] = $searchTerm;
        }

        $countStmt = $this->pdo->prepare($countSql);
        foreach ($countParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'salaries' => $salaries,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get comprehensive salary summary for a specific employee
     */
    public function getEmployeeSalarySummary($employeeId) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeID as EmployeeNumber,
                    e.JobTitle as PositionName,
                    'N/A' as DepartmentName,
                    'N/A' as BranchName,
                    es.BaseSalary,
                    es.PayFrequency,
                    es.PayRate,
                    es.EffectiveDate,
                    -- Calculate rates
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22 / 8, 2)
                        WHEN es.PayFrequency = 'Daily' THEN ROUND(es.BaseSalary / 8, 2)
                        ELSE es.PayRate
                    END as hourly_rate,
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22, 2)
                        WHEN es.PayFrequency = 'Daily' THEN es.BaseSalary
                        ELSE ROUND(es.PayRate * 8, 2)
                    END as daily_rate,
                    -- Salary history
                    (SELECT COUNT(*) FROM employeesalaries WHERE EmployeeID = e.EmployeeID) as salary_changes,
                    (SELECT MAX(EffectiveDate) FROM employeesalaries WHERE EmployeeID = e.EmployeeID) as last_salary_update
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.EmployeeID = :employee_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $salary = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$salary) {
            return null;
        }

        // Get recent adjustments
        $adjustments = $this->getEmployeeAdjustments($employeeId);
        $salary['adjustments'] = $adjustments;

        // Get salary history
        $history = $this->getEmployeeSalaryHistory($employeeId);
        $salary['history'] = $history;

        return $salary;
    }

    /**
     * Get salary comparison data for analytics
     */
    public function getSalaryComparison($filters = []) {
        $sql = "SELECT 
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName,
                    COUNT(*) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    AVG(CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN es.BaseSalary / 22 / 8
                        WHEN es.PayFrequency = 'Daily' THEN es.BaseSalary / 8
                        ELSE es.PayRate
                    END) as avg_hourly_rate
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE es.BaseSalary > 0";

        $params = [];
        
        // Branch filter disabled - column doesn't exist in employees table
        // if (!empty($filters['branch_id'])) {
        //     $sql .= " AND e.BranchID = :branch_id";
        //     $params[':branch_id'] = $filters['branch_id'];
        // }
        
        // Department filter disabled - column doesn't exist in employees table
        // if (!empty($filters['department_id'])) {
        //     $sql .= " AND e.DepartmentID = :department_id";
        //     $params[':department_id'] = $filters['department_id'];
        // }
        
        // Position filter disabled - column doesn't exist in employees table
        // if (!empty($filters['position_id'])) {
        //     $sql .= " AND e.PositionID = :position_id";
        //     $params[':position_id'] = $filters['position_id'];
        // }

        $sql .= " GROUP BY 'N/A', 'N/A', 'N/A'
                  ORDER BY avg_salary DESC";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee deductions overview
     */
    public function getEmployeeDeductions($employeeId) {
        try {
            // First check if employee has salary data
            $checkSql = "SELECT BaseSalary FROM employeesalaries WHERE EmployeeID = ? AND IsCurrent = 1";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$employeeId]);
            $salaryData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$salaryData || !$salaryData['BaseSalary']) {
                return []; // Return empty array if no salary data
            }
            
            $baseSalary = $salaryData['BaseSalary'];
            
            // Calculate deductions based on base salary
            return [
                [
                    'deduction_type' => 'SSS',
                    'rate' => '4.5%',
                    'amount' => round($baseSalary * 0.045, 2),
                    'category' => 'Statutory'
                ],
                [
                    'deduction_type' => 'PhilHealth',
                    'rate' => '2.0%',
                    'amount' => round($baseSalary * 0.02, 2),
                    'category' => 'Statutory'
                ],
                [
                    'deduction_type' => 'Pag-IBIG',
                    'rate' => '1.0%',
                    'amount' => round($baseSalary * 0.01, 2),
                    'category' => 'Statutory'
                ],
                [
                    'deduction_type' => 'Withholding Tax',
                    'rate' => 'Progressive',
                    'amount' => round($baseSalary * 0.15, 2),
                    'category' => 'Tax'
                ]
            ];
        } catch (PDOException $e) {
            error_log("Error in getEmployeeDeductions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get specific employee salary details
     */
    public function getEmployeeSalary($employeeId) {
        return $this->getEmployeeSalarySummary($employeeId);
    }

    /**
     * Get employee salary adjustments from HR3
     */
    private function getEmployeeAdjustments($employeeId) {
        // Check if table exists first
        try {
            $sql = "SELECT 
                        AdjustmentType,
                        Amount,
                        AdjustmentDate,
                        Reason,
                        Status
                    FROM salary_adjustments 
                    WHERE EmployeeID = :employee_id 
                    AND AdjustmentDate >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    ORDER BY AdjustmentDate DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Table doesn't exist, return empty array
            return [];
        }
    }

    /**
     * Get employee salary history
     */
    private function getEmployeeSalaryHistory($employeeId) {
        $sql = "SELECT 
                    BaseSalary,
                    PayFrequency,
                    PayRate,
                    EffectiveDate,
                    EndDate,
                    IsCurrent
                FROM employeesalaries 
                WHERE EmployeeID = :employee_id 
                ORDER BY EffectiveDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
