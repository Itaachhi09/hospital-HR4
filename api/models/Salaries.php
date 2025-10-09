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
                    e.EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
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
                    -- Get recent adjustments from HR3
                    COALESCE(adj.total_adjustments, 0) as total_adjustments,
                    COALESCE(adj.leave_deductions, 0) as leave_deductions,
                    COALESCE(adj.undertime_deductions, 0) as undertime_deductions,
                    COALESCE(adj.overtime_additions, 0) as overtime_additions
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN (
                    SELECT 
                        EmployeeID,
                        SUM(CASE WHEN AdjustmentType = 'Leave' THEN Amount ELSE 0 END) as leave_deductions,
                        SUM(CASE WHEN AdjustmentType = 'Undertime' THEN Amount ELSE 0 END) as undertime_deductions,
                        SUM(CASE WHEN AdjustmentType = 'Overtime' THEN Amount ELSE 0 END) as overtime_additions,
                        SUM(Amount) as total_adjustments
                    FROM salary_adjustments 
                    WHERE AdjustmentDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    GROUP BY EmployeeID
                ) adj ON e.EmployeeID = adj.EmployeeID
                WHERE 1=1";

        $params = [];
        
        // Apply filters
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
        
        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                     OR e.EmployeeNumber LIKE :search 
)";
            $params[':search'] = '%' . $filters['search'] . '%';
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
                    WHERE 1=1";
        
        $countParams = [];
        // Branch, department and position filters disabled - columns don't exist in employees table
        // if (!empty($filters['branch_id'])) {
        //     $countSql .= " AND e.BranchID = :branch_id";
        //     $countParams[':branch_id'] = $filters['branch_id'];
        // }
        // if (!empty($filters['department_id'])) {
        //     $countSql .= " AND e.DepartmentID = :department_id";
        //     $countParams[':department_id'] = $filters['department_id'];
        // }
        // if (!empty($filters['position_id'])) {
        //     $countSql .= " AND e.PositionID = :position_id";
        //     $countParams[':position_id'] = $filters['position_id'];
        // }
        if (!empty($filters['search'])) {
            $countSql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                         OR e.EmployeeNumber LIKE :search)";
            $countParams[':search'] = '%' . $filters['search'] . '%';
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
                    e.EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
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
        $sql = "SELECT 
                    'SSS' as deduction_type,
                    '4.5%' as rate,
                    ROUND(es.BaseSalary * 0.045, 2) as amount,
                    'Statutory' as category
                FROM employeesalaries es 
                WHERE es.EmployeeID = :employee_id AND es.IsCurrent = 1
                
                UNION ALL
                
                SELECT 
                    'PhilHealth' as deduction_type,
                    '2.0%' as rate,
                    ROUND(es.BaseSalary * 0.02, 2) as amount,
                    'Statutory' as category
                FROM employeesalaries es 
                WHERE es.EmployeeID = :employee_id AND es.IsCurrent = 1
                
                UNION ALL
                
                SELECT 
                    'Pag-IBIG' as deduction_type,
                    '1.0%' as rate,
                    ROUND(es.BaseSalary * 0.01, 2) as amount,
                    'Statutory' as category
                FROM employeesalaries es 
                WHERE es.EmployeeID = :employee_id AND es.IsCurrent = 1
                
                UNION ALL
                
                SELECT 
                    'Withholding Tax' as deduction_type,
                    'Progressive' as rate,
                    ROUND(es.BaseSalary * 0.15, 2) as amount,
                    'Tax' as category
                FROM employeesalaries es 
                WHERE es.EmployeeID = :employee_id AND es.IsCurrent = 1
                
                ORDER BY category, deduction_type";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
