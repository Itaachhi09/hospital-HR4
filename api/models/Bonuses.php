<?php
require_once __DIR__ . '/../config.php';

class Bonuses {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all bonuses with comprehensive data from HR2/HR4 modules
     */
    public function getAllBonuses($filters = []) {
        $sql = "SELECT 
                    b.BonusID,
                    b.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName,
                    b.BonusType,
                    b.BonusType as BonusName,
                    b.BonusAmount as Amount,
                    0 as Percentage,
                    b.BonusAmount as BaseAmount,
                    'Fixed' as ComputationMethod,
                    'N/A' as EligibilityCriteria,
                    b.PayrollID as PayrollRunID,
                    b.AwardDate as EffectiveDate,
                    'Active' as Status,
                    b.AwardDate as CreatedAt,
                    'N/A' as Notes,
                    b.BonusAmount as computed_amount
                FROM bonuses b
                LEFT JOIN employees e ON b.EmployeeID = e.EmployeeID
                WHERE 1=1";

        $params = [];
        
        // Apply filters
        // Branch, department and position filters disabled - columns don't exist in employees table
        // if (!empty($filters['branch_id'])) {
        //     $sql .= " AND e.BranchID = :branch_id";
        //     $params[':branch_id'] = $filters['branch_id'];
        // }
        
        // if (!empty($filters['department_id'])) {
        //     $sql .= " AND e.DepartmentID = :department_id";
        //     $params[':department_id'] = $filters['department_id'];
        // }
        
        // if (!empty($filters['position_id'])) {
        //     $sql .= " AND e.PositionID = :position_id";
        //     $params[':position_id'] = $filters['position_id'];
        // }
        
        if (!empty($filters['bonus_type'])) {
            $sql .= " AND b.BonusType = :bonus_type";
            $params[':bonus_type'] = $filters['bonus_type'];
        }
        
        if (!empty($filters['payroll_run_id'])) {
            $sql .= " AND b.PayrollRunID = :payroll_run_id";
            $params[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                     OR e.EmployeeNumber LIKE :search 
                     OR b.BonusType LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY b.BonusID DESC, e.LastName, e.FirstName";

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

        $bonuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM bonuses b
                    LEFT JOIN employees e ON b.EmployeeID = e.EmployeeID
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
        if (!empty($filters['bonus_type'])) {
            $countSql .= " AND b.BonusType = :bonus_type";
            $countParams[':bonus_type'] = $filters['bonus_type'];
        }
        if (!empty($filters['payroll_run_id'])) {
            $countSql .= " AND b.PayrollRunID = :payroll_run_id";
            $countParams[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        if (!empty($filters['search'])) {
            $countSql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                         OR e.EmployeeNumber LIKE :search 
                         OR b.BonusName LIKE :search 
                         OR b.BonusType LIKE :search)";
            $countParams[':search'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->pdo->prepare($countSql);
        foreach ($countParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'bonuses' => $bonuses,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get comprehensive bonus summary for a specific employee
     */
    public function getEmployeeBonusSummary($employeeId) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName,
                    -- Current year bonus summary
                    COALESCE(SUM(CASE WHEN YEAR(b.EffectiveDate) = YEAR(CURDATE()) THEN b.Amount ELSE 0 END), 0) as year_to_date_bonuses,
                    COALESCE(COUNT(CASE WHEN YEAR(b.EffectiveDate) = YEAR(CURDATE()) THEN 1 END), 0) as bonus_count_ytd,
                    -- Last 12 months summary
                    COALESCE(SUM(CASE WHEN b.EffectiveDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN b.Amount ELSE 0 END), 0) as last_12_months_bonuses,
                    -- Average bonus amount
                    COALESCE(AVG(b.Amount), 0) as average_bonus_amount
                FROM employees e
                LEFT JOIN bonuses b ON e.EmployeeID = b.EmployeeID
                WHERE e.EmployeeID = :employee_id
                GROUP BY e.EmployeeID";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$summary) {
            return null;
        }

        // Get recent bonuses
        $recentBonuses = $this->getEmployeeRecentBonuses($employeeId);
        $summary['recent_bonuses'] = $recentBonuses;

        // Get bonus types breakdown
        $bonusTypes = $this->getEmployeeBonusTypes($employeeId);
        $summary['bonus_types_breakdown'] = $bonusTypes;

        return $summary;
    }

    /**
     * Get bonus computation details
     */
    public function getBonusComputation($bonusId) {
        $sql = "SELECT 
                    b.*,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    es.BaseSalary,
                    es.PayFrequency,
                    -- Computation details
                    CASE 
                        WHEN b.ComputationMethod = 'Fixed' THEN b.Amount
                        WHEN b.ComputationMethod = 'Percentage' THEN ROUND(b.BaseAmount * (b.Percentage / 100), 2)
                        WHEN b.ComputationMethod = 'Formula' THEN b.Amount
                        ELSE b.Amount
                    END as computed_amount,
                    -- Eligibility check
                    CASE 
                        WHEN b.EligibilityCriteria IS NULL THEN 'No criteria'
                        ELSE b.EligibilityCriteria
                    END as eligibility_status
                FROM bonuses b
                LEFT JOIN employees e ON b.EmployeeID = e.EmployeeID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE b.BonusID = :bonus_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':bonus_id', $bonusId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get available bonus types
     */
    public function getBonusTypes() {
        $sql = "SELECT DISTINCT 
                    BonusType as type,
                    COUNT(*) as count,
                    AVG(Amount) as average_amount,
                    MIN(Amount) as min_amount,
                    MAX(Amount) as max_amount
                FROM bonuses 
                WHERE Status = 'Active'
                GROUP BY BonusType
                ORDER BY BonusType";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check employee eligibility for bonuses
     */
    public function checkEmployeeEligibility($employeeId) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    d.DepartmentName,
                    p.PositionName,
                    es.BaseSalary,
                    es.PayFrequency,
                    -- Check various eligibility criteria
                    CASE 
                        WHEN e.EmploymentStatus = 'Active' THEN 'Eligible'
                        ELSE 'Not Eligible - Inactive Status'
                    END as employment_status,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), e.HireDate) >= 90 THEN 'Eligible'
                        ELSE 'Not Eligible - Less than 90 days'
                    END as tenure_eligibility,
                    CASE 
                        WHEN es.BaseSalary > 0 THEN 'Eligible'
                        ELSE 'Not Eligible - No salary data'
                    END as salary_eligibility
                FROM employees e
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN positions p ON e.PositionID = p.PositionID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.EmployeeID = :employee_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Compute bonuses for a payroll run
     */
    public function computeBonusesForPayrollRun($payrollRunId, $branchId) {
        // Get payroll run details
        $payrollSql = "SELECT * FROM payroll_v2_runs WHERE PayrollRunID = :run_id AND BranchID = :branch_id";
        $payrollStmt = $this->pdo->prepare($payrollSql);
        $payrollStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $payrollStmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $payrollStmt->execute();
        $payrollRun = $payrollStmt->fetch(PDO::FETCH_ASSOC);

        if (!$payrollRun) {
            throw new Exception('Payroll run not found');
        }

        // Get eligible employees
        $employees = $this->getEligibleEmployees($branchId, $payrollRun['PayPeriodStart'], $payrollRun['PayPeriodEnd']);
        
        $computedBonuses = [];
        $totalAmount = 0;

        foreach ($employees as $employee) {
            $employeeBonuses = $this->computeEmployeeBonuses($employee['EmployeeID'], $payrollRun);
            $computedBonuses = array_merge($computedBonuses, $employeeBonuses);
            
            foreach ($employeeBonuses as $bonus) {
                $totalAmount += $bonus['Amount'];
            }
        }

        return [
            'payroll_run_id' => $payrollRunId,
            'branch_id' => $branchId,
            'computed_bonuses' => $computedBonuses,
            'total_amount' => $totalAmount,
            'employee_count' => count($employees),
            'bonus_count' => count($computedBonuses)
        ];
    }

    /**
     * Add manual bonus entry
     */
    public function addManualBonus($data) {
        $sql = "INSERT INTO bonuses (
                    EmployeeID, BonusType, BonusName, Amount, ComputationMethod, 
                    EligibilityCriteria, PayrollRunID, EffectiveDate, Status, Notes
                ) VALUES (
                    :employee_id, :bonus_type, :bonus_name, :amount, :computation_method,
                    :eligibility_criteria, :payroll_run_id, :effective_date, :status, :notes
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':bonus_type', $data['bonus_type']);
        $stmt->bindValue(':bonus_name', $data['bonus_name']);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':computation_method', $data['computation_method'] ?? 'Fixed');
        $stmt->bindValue(':eligibility_criteria', $data['eligibility_criteria'] ?? null);
        $stmt->bindValue(':payroll_run_id', $data['payroll_run_id'] ?? null);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'Active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Create new bonus
     */
    public function createBonus($data) {
        return $this->addManualBonus($data);
    }

    /**
     * Update bonus
     */
    public function updateBonus($bonusId, $data) {
        $sql = "UPDATE bonuses SET 
                    BonusType = :bonus_type,
                    BonusName = :bonus_name,
                    Amount = :amount,
                    ComputationMethod = :computation_method,
                    EligibilityCriteria = :eligibility_criteria,
                    EffectiveDate = :effective_date,
                    Status = :status,
                    Notes = :notes
                WHERE BonusID = :bonus_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':bonus_id', $bonusId, PDO::PARAM_INT);
        $stmt->bindValue(':bonus_type', $data['bonus_type']);
        $stmt->bindValue(':bonus_name', $data['bonus_name']);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':computation_method', $data['computation_method']);
        $stmt->bindValue(':eligibility_criteria', $data['eligibility_criteria']);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':notes', $data['notes']);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete bonus
     */
    public function deleteBonus($bonusId) {
        $sql = "DELETE FROM bonuses WHERE BonusID = :bonus_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':bonus_id', $bonusId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get specific bonus details
     */
    public function getBonus($bonusId) {
        $sql = "SELECT 
                    b.*,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    d.DepartmentName,
                    p.PositionName,
                    hb.BranchName
                FROM bonuses b
                LEFT JOIN employees e ON b.EmployeeID = e.EmployeeID
                WHERE b.BonusID = :bonus_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':bonus_id', $bonusId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get eligible employees for bonus computation
     */
    private function getEligibleEmployees($branchId, $periodStart, $periodEnd) {
        $sql = "SELECT 
                    e.EmployeeID,
                    e.FirstName,
                    e.LastName,
                    e.EmployeeNumber,
                    es.BaseSalary,
                    es.PayFrequency,
                    d.DepartmentName,
                    p.PositionName
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN positions p ON e.PositionID = p.PositionID
                WHERE e.BranchID = :branch_id 
                AND e.EmploymentStatus = 'Active'
                AND es.BaseSalary > 0";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compute bonuses for a specific employee
     */
    private function computeEmployeeBonuses($employeeId, $payrollRun) {
        $bonuses = [];
        
        // Get employee salary data
        $salaryData = $this->getEmployeeSalaryData($employeeId);
        if (!$salaryData) {
            return $bonuses;
        }

        // Define bonus types and their computation logic
        $bonusTypes = [
            'Mid-Year Bonus' => [
                'computation' => 'percentage',
                'percentage' => 25, // 25% of base salary
                'base_amount' => $salaryData['BaseSalary'],
                'eligibility' => 'active_employee'
            ],
            'Year-End Bonus' => [
                'computation' => 'percentage',
                'percentage' => 50, // 50% of base salary
                'base_amount' => $salaryData['BaseSalary'],
                'eligibility' => 'active_employee'
            ],
            'Hazard Pay' => [
                'computation' => 'fixed',
                'amount' => 2000, // Fixed amount
                'eligibility' => 'hazard_department'
            ],
            'Night Differential' => [
                'computation' => 'percentage',
                'percentage' => 10, // 10% of base salary
                'base_amount' => $salaryData['BaseSalary'],
                'eligibility' => 'night_shift'
            ],
            'Overtime Allowance' => [
                'computation' => 'fixed',
                'amount' => 500, // Fixed amount
                'eligibility' => 'overtime_eligible'
            ],
            'Performance Incentive' => [
                'computation' => 'percentage',
                'percentage' => 15, // 15% of base salary
                'base_amount' => $salaryData['BaseSalary'],
                'eligibility' => 'performance_based'
            ]
        ];

        foreach ($bonusTypes as $bonusType => $config) {
            if ($this->checkEligibility($employeeId, $config['eligibility'])) {
                $amount = $this->computeBonusAmount($config);
                
                $bonuses[] = [
                    'EmployeeID' => $employeeId,
                    'BonusType' => $bonusType,
                    'BonusName' => $bonusType,
                    'Amount' => $amount,
                    'ComputationMethod' => $config['computation'],
                    'EligibilityCriteria' => $config['eligibility'],
                    'PayrollRunID' => $payrollRun['PayrollRunID'],
                    'EffectiveDate' => $payrollRun['PayDate'],
                    'Status' => 'Active',
                    'Notes' => 'Auto-computed bonus'
                ];
            }
        }

        return $bonuses;
    }

    /**
     * Get employee salary data
     */
    private function getEmployeeSalaryData($employeeId) {
        $sql = "SELECT BaseSalary, PayFrequency FROM employeesalaries 
                WHERE EmployeeID = :employee_id AND IsCurrent = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check employee eligibility for bonus
     */
    private function checkEligibility($employeeId, $eligibilityType) {
        // Simplified eligibility check - in production, this would be more complex
        switch ($eligibilityType) {
            case 'active_employee':
                return true; // All active employees are eligible
            case 'hazard_department':
                // Check if employee is in a hazardous department
                return true; // Simplified for demo
            case 'night_shift':
                // Check if employee works night shifts
                return true; // Simplified for demo
            case 'overtime_eligible':
                // Check if employee is eligible for overtime
                return true; // Simplified for demo
            case 'performance_based':
                // Check performance metrics
                return true; // Simplified for demo
            default:
                return false;
        }
    }

    /**
     * Compute bonus amount based on configuration
     */
    private function computeBonusAmount($config) {
        if ($config['computation'] === 'fixed') {
            return $config['amount'];
        } elseif ($config['computation'] === 'percentage') {
            return round($config['base_amount'] * ($config['percentage'] / 100), 2);
        }
        return 0;
    }

    /**
     * Get employee recent bonuses
     */
    private function getEmployeeRecentBonuses($employeeId) {
        $sql = "SELECT 
                    BonusType,
                    BonusName,
                    Amount,
                    EffectiveDate,
                    Status
                FROM bonuses 
                WHERE EmployeeID = :employee_id 
                ORDER BY EffectiveDate DESC 
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee bonus types breakdown
     */
    private function getEmployeeBonusTypes($employeeId) {
        $sql = "SELECT 
                    BonusType,
                    COUNT(*) as count,
                    SUM(Amount) as total_amount,
                    AVG(Amount) as average_amount
                FROM bonuses 
                WHERE EmployeeID = :employee_id 
                GROUP BY BonusType
                ORDER BY total_amount DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
