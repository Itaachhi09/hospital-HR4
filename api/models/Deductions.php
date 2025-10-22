<?php
require_once __DIR__ . '/../config.php';

class Deductions {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all deductions with comprehensive data from HR1/HR2 modules
     */
    public function getAllDeductions($filters = []) {
        $sql = "SELECT 
                    d.DeductionID,
                    d.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeID as EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName,
                    d.DeductionType,
                    d.DeductionType as DeductionName,
                    d.DeductionAmount as Amount,
                    0 as Percentage,
                    d.DeductionAmount as BaseAmount,
                    'Fixed' as ComputationMethod,
                    CASE 
                        WHEN d.DeductionType IN ('SSS', 'PhilHealth', 'Pag-IBIG', 'Tax') THEN 1
                        ELSE 0
                    END as IsStatutory,
                    CASE 
                        WHEN d.DeductionType IN ('SSS', 'PhilHealth', 'Pag-IBIG', 'Tax') THEN 0
                        ELSE 1
                    END as IsVoluntary,
                    d.PayrollID as PayrollRunID,
                    CURDATE() as EffectiveDate,
                    'Active' as Status,
                    CURDATE() as CreatedAt,
                    d.Provider as Notes,
                    d.DeductionAmount as computed_amount
                FROM deductions d
                LEFT JOIN employees e ON d.EmployeeID = e.EmployeeID
                WHERE 1=1";

        $params = [];
        
        // Apply filters
        if (!empty($filters['branch_id'])) {
            // Branch filtering not available in current schema
            // $sql .= " AND e.BranchID = :branch_id";
            // $params[':branch_id'] = $filters['branch_id'];
        }
        
        if (!empty($filters['department_id'])) {
            // Department filtering not available in current schema
            // $sql .= " AND e.DepartmentID = :department_id";
            // $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['deduction_type'])) {
            $sql .= " AND d.DeductionType = :deduction_type";
            $params[':deduction_type'] = $filters['deduction_type'];
        }
        
        if (!empty($filters['payroll_run_id'])) {
            // table uses PayrollID column name
            $sql .= " AND d.PayrollID = :payroll_run_id";
            $params[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search1 
                     OR e.Email LIKE :search2 
                     OR e.JobTitle LIKE :search3 
                     OR d.DeductionType LIKE :search4)";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        $sql .= " ORDER BY d.DeductionID DESC, e.LastName, e.FirstName";

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

        $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM deductions d
                    LEFT JOIN employees e ON d.EmployeeID = e.EmployeeID
                    WHERE 1=1";
        
        $countParams = [];
        if (!empty($filters['branch_id'])) {
            // Branch filtering not available in current schema
            // $countSql .= " AND e.BranchID = :branch_id";
            // $countParams[':branch_id'] = $filters['branch_id'];
        }
        if (!empty($filters['department_id'])) {
            // Department filtering not available in current schema
            // $countSql .= " AND e.DepartmentID = :department_id";
            // $countParams[':department_id'] = $filters['department_id'];
        }
        if (!empty($filters['deduction_type'])) {
            $countSql .= " AND d.DeductionType = :deduction_type";
            $countParams[':deduction_type'] = $filters['deduction_type'];
        }
        if (!empty($filters['payroll_run_id'])) {
            $countSql .= " AND d.PayrollRunID = :payroll_run_id";
            $countParams[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $countSql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search1 
                         OR e.Email LIKE :search2 
                         OR e.JobTitle LIKE :search3 
                         OR d.DeductionType LIKE :search4)";
            $countParams[':search1'] = $searchTerm;
            $countParams[':search2'] = $searchTerm;
            $countParams[':search3'] = $searchTerm;
            $countParams[':search4'] = $searchTerm;
        }

        $countStmt = $this->pdo->prepare($countSql);
        foreach ($countParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'deductions' => $deductions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get comprehensive deduction summary for a specific employee
     */
    public function getEmployeeDeductionSummary($employeeId) {
    $sql = "SELECT 
            e.EmployeeID,
            CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
            e.EmployeeNumber,
            dept.DepartmentName,
            e.JobTitle as PositionName,
            'N/A' as BranchName,
            es.BaseSalary,
                    -- Statutory deductions summary
                    COALESCE(SUM(CASE WHEN d.DeductionType IN ('SSS','PhilHealth','Pag-IBIG','Tax') THEN d.DeductionAmount ELSE 0 END), 0) as total_statutory,
                    COALESCE(SUM(CASE WHEN d.DeductionType NOT IN ('SSS','PhilHealth','Pag-IBIG','Tax') THEN d.DeductionAmount ELSE 0 END), 0) as total_voluntary,
                    COALESCE(SUM(d.DeductionAmount), 0) as total_deductions,
                    -- Current month deductions not available (no date fields in deductions table)
                    0 as current_month_deductions
                FROM employees e
                LEFT JOIN deductions d ON e.EmployeeID = d.EmployeeID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN departments dept ON e.DepartmentID = dept.DepartmentID
                -- Position/Branch fields are not present in this schema; use JobTitle and placeholder for Branch
                WHERE e.EmployeeID = :employee_id
                GROUP BY e.EmployeeID";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$summary) {
            return null;
        }

        // Get statutory deductions breakdown
        $statutoryDeductions = $this->getEmployeeStatutoryDeductions($employeeId);
        $summary['statutory_breakdown'] = $statutoryDeductions;

        // Get voluntary deductions breakdown
        $voluntaryDeductions = $this->getEmployeeVoluntaryDeductions($employeeId);
        $summary['voluntary_breakdown'] = $voluntaryDeductions;

        return $summary;
    }

    /**
     * Get statutory deductions for employee
     */
    public function getEmployeeStatutoryDeductions($employeeId) {
        $sql = "SELECT 
                    d.DeductionType,
                    d.DeductionType as DeductionName,
                    d.DeductionAmount as Amount,
                    NULL as Percentage,
                    d.DeductionAmount as BaseAmount,
                    'Fixed' as ComputationMethod,
                    NULL as EffectiveDate,
                    NULL as Status
                FROM deductions d
                WHERE d.EmployeeID = :employee_id 
                AND d.DeductionType IN ('SSS','PhilHealth','Pag-IBIG','Tax')
                ORDER BY d.DeductionType";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get voluntary deductions for employee
     */
    public function getEmployeeVoluntaryDeductions($employeeId) {
        $sql = "SELECT 
                    d.DeductionType,
                    d.DeductionType as DeductionName,
                    d.DeductionAmount as Amount,
                    NULL as Percentage,
                    d.DeductionAmount as BaseAmount,
                    'Fixed' as ComputationMethod,
                    NULL as EffectiveDate,
                    NULL as Status,
                    d.Provider as Notes
                FROM deductions d
                WHERE d.EmployeeID = :employee_id 
                AND d.DeductionType NOT IN ('SSS','PhilHealth','Pag-IBIG','Tax')
                ORDER BY d.DeductionType";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get deduction configuration from HR1/HR2
     */
    public function getDeductionConfiguration() {
        // Get statutory deduction rates from payroll configuration
        $configSql = "SELECT 
                        BranchID,
                        SSSRateEmployee,
                        PhilHealthRateEmployee,
                        PagibigRateEmployee,
                        TaxRateEmployee,
                        CreatedAt,
                        UpdatedAt
                      FROM payroll_v2_branch_configs
                      ORDER BY BranchID";

        $configStmt = $this->pdo->prepare($configSql);
        $configStmt->execute();
        $configs = $configStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get voluntary deduction types
        $voluntarySql = "SELECT DISTINCT 
                            DeductionType,
                            COUNT(*) as count,
                            AVG(Amount) as average_amount
                         FROM deductions 
                         WHERE IsVoluntary = 1 AND Status = 'Active'
                         GROUP BY DeductionType
                         ORDER BY DeductionType";

        $voluntaryStmt = $this->pdo->prepare($voluntarySql);
        $voluntaryStmt->execute();
        $voluntaryTypes = $voluntaryStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'statutory_rates' => $configs,
            'voluntary_types' => $voluntaryTypes,
            'computation_formula' => 'Total Deductions = SSS + PhilHealth + Pag-IBIG + Tax + Voluntary'
        ];
    }

    /**
     * Get available deduction types
     */
    public function getDeductionTypes() {
        // Fetch existing deduction types from the database
        $sql = "SELECT DISTINCT 
                    DeductionType as type,
                    CASE 
                        WHEN DeductionType IN ('SSS', 'PhilHealth', 'Pag-IBIG', 'Tax') THEN 1
                        ELSE 0
                    END as IsStatutory,
                    CASE 
                        WHEN DeductionType IN ('SSS', 'PhilHealth', 'Pag-IBIG', 'Tax') THEN 0
                        ELSE 1
                    END as IsVoluntary,
                    COUNT(*) as count,
                    AVG(DeductionAmount) as average_amount,
                    MIN(DeductionAmount) as min_amount,
                    MAX(DeductionAmount) as max_amount
                FROM deductions 
                GROUP BY DeductionType";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ensure statutory types are always present in the returned list
        $statutory = ['SSS', 'PhilHealth', 'Pag-IBIG', 'Tax'];
        $existing = array_column($types, 'type');
        foreach ($statutory as $s) {
            if (!in_array($s, $existing, true)) {
                $types[] = [
                    'type' => $s,
                    'IsStatutory' => 1,
                    'IsVoluntary' => 0,
                    'count' => 0,
                    'average_amount' => null,
                    'min_amount' => null,
                    'max_amount' => null
                ];
            }
        }

        // Sort so statutory types appear first, then alphabetically
        usort($types, function($a, $b) {
            if (($a['IsStatutory'] ?? 0) !== ($b['IsStatutory'] ?? 0)) {
                return ($b['IsStatutory'] ?? 0) - ($a['IsStatutory'] ?? 0);
            }
            return strcmp($a['type'] ?? '', $b['type'] ?? '');
        });

        return $types;
    }

    /**
     * Compute deductions for a payroll run
     */
    public function computeDeductionsForPayrollRun($payrollRunId, $branchId) {
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
        
        $computedDeductions = [];
        $totalStatutory = 0;
        $totalVoluntary = 0;

        foreach ($employees as $employee) {
            $employeeDeductions = $this->computeEmployeeDeductions($employee['EmployeeID'], $payrollRun, $branchId);
            $computedDeductions = array_merge($computedDeductions, $employeeDeductions);
            
            foreach ($employeeDeductions as $deduction) {
                if ($deduction['IsStatutory']) {
                    $totalStatutory += $deduction['Amount'];
                } else {
                    $totalVoluntary += $deduction['Amount'];
                }
            }
        }

        return [
            'payroll_run_id' => $payrollRunId,
            'branch_id' => $branchId,
            'computed_deductions' => $computedDeductions,
            'total_statutory' => $totalStatutory,
            'total_voluntary' => $totalVoluntary,
            'total_deductions' => $totalStatutory + $totalVoluntary,
            'employee_count' => count($employees),
            'deduction_count' => count($computedDeductions)
        ];
    }

    /**
     * Add voluntary deduction entry
     */
    public function addVoluntaryDeduction($data) {
        $sql = "INSERT INTO deductions (
                    EmployeeID, DeductionType, DeductionName, Amount, ComputationMethod, 
                    IsStatutory, IsVoluntary, PayrollRunID, EffectiveDate, Status, Notes
                ) VALUES (
                    :employee_id, :deduction_type, :deduction_name, :amount, :computation_method,
                    0, 1, :payroll_run_id, :effective_date, :status, :notes
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':deduction_type', $data['deduction_type']);
        $stmt->bindValue(':deduction_name', $data['deduction_name']);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':computation_method', $data['computation_method'] ?? 'Fixed');
        $stmt->bindValue(':payroll_run_id', $data['payroll_run_id'] ?? null);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status'] ?? 'Active');
        $stmt->bindValue(':notes', $data['notes'] ?? null);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Create new deduction
     */
    public function createDeduction($data) {
        return $this->addVoluntaryDeduction($data);
    }

    /**
     * Update deduction
     */
    public function updateDeduction($deductionId, $data) {
        $sql = "UPDATE deductions SET 
                    DeductionType = :deduction_type,
                    DeductionName = :deduction_name,
                    Amount = :amount,
                    ComputationMethod = :computation_method,
                    EffectiveDate = :effective_date,
                    Status = :status,
                    Notes = :notes
                WHERE DeductionID = :deduction_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':deduction_id', $deductionId, PDO::PARAM_INT);
        $stmt->bindValue(':deduction_type', $data['deduction_type']);
        $stmt->bindValue(':deduction_name', $data['deduction_name']);
        $stmt->bindValue(':amount', $data['amount']);
        $stmt->bindValue(':computation_method', $data['computation_method']);
        $stmt->bindValue(':effective_date', $data['effective_date']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':notes', $data['notes']);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete deduction
     */
    public function deleteDeduction($deductionId) {
        $sql = "DELETE FROM deductions WHERE DeductionID = :deduction_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':deduction_id', $deductionId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get specific deduction details
     */
    public function getDeduction($deductionId) {
        $sql = "SELECT 
                    d.*,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeID as EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName
                FROM deductions d
                LEFT JOIN employees e ON d.EmployeeID = e.EmployeeID
                WHERE d.DeductionID = :deduction_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':deduction_id', $deductionId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get eligible employees for deduction computation
     */
    private function getEligibleEmployees($branchId, $periodStart, $periodEnd) {
        $sql = "SELECT 
                    e.EmployeeID,
                    e.FirstName,
                    e.LastName,
                    e.EmployeeID as EmployeeNumber,
                    es.BaseSalary,
                    es.PayFrequency,
                    dept.DepartmentName,
                    p.PositionName
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN departments dept ON e.DepartmentID = dept.DepartmentID
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
     * Compute deductions for a specific employee
     */
    private function computeEmployeeDeductions($employeeId, $payrollRun, $branchId) {
        $deductions = [];
        
        // Get employee salary data
        $salaryData = $this->getEmployeeSalaryData($employeeId);
        if (!$salaryData) {
            return $deductions;
        }

        // Get branch-specific rates
        $rates = $this->getBranchDeductionRates($branchId);

        // Compute statutory deductions
        $statutoryDeductions = $this->computeStatutoryDeductions($employeeId, $salaryData, $rates, $payrollRun);
        $deductions = array_merge($deductions, $statutoryDeductions);

        // Compute voluntary deductions
        $voluntaryDeductions = $this->computeVoluntaryDeductions($employeeId, $salaryData, $payrollRun);
        $deductions = array_merge($deductions, $voluntaryDeductions);

        // **NEW: Compute HMO deductions**
        $hmoDeductions = $this->computeHMODeductions($employeeId, $payrollRun);
        $deductions = array_merge($deductions, $hmoDeductions);

        return $deductions;
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
     * Get branch-specific deduction rates
     */
    private function getBranchDeductionRates($branchId) {
        $sql = "SELECT SSSRateEmployee, PhilHealthRateEmployee, PagibigRateEmployee, TaxRateEmployee 
                FROM payroll_v2_branch_configs 
                WHERE BranchID = :branch_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'sss_rate' => (float)($result['SSSRateEmployee'] ?? 0.045), // 4.5%
            'philhealth_rate' => (float)($result['PhilHealthRateEmployee'] ?? 0.02), // 2.0%
            'pagibig_rate' => (float)($result['PagibigRateEmployee'] ?? 0.01), // 1.0%
            'tax_rate' => (float)($result['TaxRateEmployee'] ?? 0.0) // Variable
        ];
    }

    /**
     * Compute statutory deductions
     */
    private function computeStatutoryDeductions($employeeId, $salaryData, $rates, $payrollRun) {
        $deductions = [];
        $baseSalary = $salaryData['BaseSalary'];

        // SSS Contribution (4.5% of base salary)
        $sssAmount = round($baseSalary * $rates['sss_rate'], 2);
        $deductions[] = [
            'EmployeeID' => $employeeId,
            'DeductionType' => 'SSS',
            'DeductionName' => 'SSS Contribution',
            'Amount' => $sssAmount,
            'ComputationMethod' => 'Percentage',
            'Percentage' => $rates['sss_rate'] * 100,
            'BaseAmount' => $baseSalary,
            'IsStatutory' => 1,
            'IsVoluntary' => 0,
            'PayrollRunID' => $payrollRun['PayrollRunID'],
            'EffectiveDate' => $payrollRun['PayDate'],
            'Status' => 'Active',
            'Notes' => 'Auto-computed statutory deduction'
        ];

        // PhilHealth Contribution (2.0% of base salary)
        $philhealthAmount = round($baseSalary * $rates['philhealth_rate'], 2);
        $deductions[] = [
            'EmployeeID' => $employeeId,
            'DeductionType' => 'PhilHealth',
            'DeductionName' => 'PhilHealth Contribution',
            'Amount' => $philhealthAmount,
            'ComputationMethod' => 'Percentage',
            'Percentage' => $rates['philhealth_rate'] * 100,
            'BaseAmount' => $baseSalary,
            'IsStatutory' => 1,
            'IsVoluntary' => 0,
            'PayrollRunID' => $payrollRun['PayrollRunID'],
            'EffectiveDate' => $payrollRun['PayDate'],
            'Status' => 'Active',
            'Notes' => 'Auto-computed statutory deduction'
        ];

        // Pag-IBIG Contribution (1.0% of base salary)
        $pagibigAmount = round($baseSalary * $rates['pagibig_rate'], 2);
        $deductions[] = [
            'EmployeeID' => $employeeId,
            'DeductionType' => 'Pag-IBIG',
            'DeductionName' => 'Pag-IBIG Contribution',
            'Amount' => $pagibigAmount,
            'ComputationMethod' => 'Percentage',
            'Percentage' => $rates['pagibig_rate'] * 100,
            'BaseAmount' => $baseSalary,
            'IsStatutory' => 1,
            'IsVoluntary' => 0,
            'PayrollRunID' => $payrollRun['PayrollRunID'],
            'EffectiveDate' => $payrollRun['PayDate'],
            'Status' => 'Active',
            'Notes' => 'Auto-computed statutory deduction'
        ];

        // Withholding Tax (progressive calculation)
        $taxAmount = $this->calculateWithholdingTax($baseSalary);
        $deductions[] = [
            'EmployeeID' => $employeeId,
            'DeductionType' => 'Tax',
            'DeductionName' => 'Withholding Tax',
            'Amount' => $taxAmount,
            'ComputationMethod' => 'Formula',
            'Percentage' => 0,
            'BaseAmount' => $baseSalary,
            'IsStatutory' => 1,
            'IsVoluntary' => 0,
            'PayrollRunID' => $payrollRun['PayrollRunID'],
            'EffectiveDate' => $payrollRun['PayDate'],
            'Status' => 'Active',
            'Notes' => 'Auto-computed progressive tax'
        ];

        return $deductions;
    }

    /**
     * Compute voluntary deductions
     */
    private function computeVoluntaryDeductions($employeeId, $salaryData, $payrollRun) {
        $deductions = [];

        // Get existing voluntary deductions for this employee
        $sql = "SELECT * FROM deductions 
                WHERE EmployeeID = :employee_id 
                AND IsVoluntary = 1 
                AND Status = 'Active'
                AND (EffectiveDate <= :pay_date OR EffectiveDate IS NULL)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':pay_date', $payrollRun['PayDate']);
        $stmt->execute();
        
        $existingDeductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existingDeductions as $deduction) {
            $amount = $deduction['Amount'];
            if ($deduction['ComputationMethod'] === 'Percentage') {
                $amount = round($salaryData['BaseSalary'] * ($deduction['Percentage'] / 100), 2);
            }

            $deductions[] = [
                'EmployeeID' => $employeeId,
                'DeductionType' => $deduction['DeductionType'],
                'DeductionName' => $deduction['DeductionName'],
                'Amount' => $amount,
                'ComputationMethod' => $deduction['ComputationMethod'],
                'Percentage' => $deduction['Percentage'],
                'BaseAmount' => $salaryData['BaseSalary'],
                'IsStatutory' => 0,
                'IsVoluntary' => 1,
                'PayrollRunID' => $payrollRun['PayrollRunID'],
                'EffectiveDate' => $payrollRun['PayDate'],
                'Status' => 'Active',
                'Notes' => $deduction['Notes']
            ];
        }

        return $deductions;
    }

    /**
     * Calculate withholding tax (simplified progressive calculation)
     */
    private function calculateWithholdingTax($baseSalary) {
        // Simplified progressive tax calculation for Philippines
        // This is a basic implementation - in production, use official BIR tax tables
        
        if ($baseSalary <= 20833) {
            return 0; // No tax for basic salary up to 20833
        } elseif ($baseSalary <= 33333) {
            return round(($baseSalary - 20833) * 0.20, 2);
        } elseif ($baseSalary <= 66667) {
            return round(2500 + (($baseSalary - 33333) * 0.25), 2);
        } elseif ($baseSalary <= 166667) {
            return round(10833.33 + (($baseSalary - 66667) * 0.30), 2);
        } elseif ($baseSalary <= 666667) {
            return round(40833.33 + (($baseSalary - 166667) * 0.32), 2);
        } else {
            return round(200833.33 + (($baseSalary - 666667) * 0.35), 2);
        }
    }

    /**
     * Compute HMO deductions for employee
     * Integrates with HMO module to get active enrollments
     */
    private function computeHMODeductions($employeeId, $payrollRun) {
        $hmoDeductions = [];
        
        try {
            // Load HMO Payroll Integration
            require_once __DIR__ . '/../integrations/HMOPayrollIntegration.php';
            $hmoIntegration = new HMOPayrollIntegration();
            
            // Get payroll period month and year
            $periodStart = $payrollRun['PayPeriodStart'];
            $month = date('n', strtotime($periodStart));
            $year = date('Y', strtotime($periodStart));
            
            // Get HMO deductions for this period
            $allHMODeductions = $hmoIntegration->getHMODeductionsForPayroll(null, $month, $year);
            
            // Filter for current employee
            foreach ($allHMODeductions as $hmo) {
                if ($hmo['EmployeeID'] == $employeeId && $hmo['deduction_amount'] > 0) {
                    $hmoDeductions[] = [
                        'EmployeeID' => $employeeId,
                        'DeductionType' => 'HMO Premium',
                        'Amount' => $hmo['deduction_amount'],
                        'Description' => $hmo['description'],
                        'IsStatutory' => false,
                        'Category' => 'Voluntary',
                        'SourceType' => 'HMO',
                        'HMOEnrollmentID' => null, // Can be added if needed
                        'PayrollRunID' => $payrollRun['PayrollRunID'],
                        'IsRecurring' => true,
                        'Status' => 'Active'
                    ];
                }
            }
        } catch (Exception $e) {
            // Log error but don't break payroll processing
            error_log('HMO deduction computation error for Employee ' . $employeeId . ': ' . $e->getMessage());
        }
        
        return $hmoDeductions;
    }
}
