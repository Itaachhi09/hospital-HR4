<?php
/**
 * Payroll V2 Model
 * - Versioned, multi-branch payroll processing
 * - Integrates HR Core compensation and HR3 timesheets (DTR)
 */

class PayrollV2 {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /** Create a new payroll run */
    public function createRun(array $data) {
        $sql = "INSERT INTO payroll_runs_v2 (BranchID, Version, PayPeriodStart, PayPeriodEnd, PayDate, Status, Notes, CreatedBy)
                VALUES (:branch_id, 1, :pps, :ppe, :pay_date, 'Draft', :notes, :created_by)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':branch_id', (int)$data['branch_id'], PDO::PARAM_INT);
        $stmt->bindValue(':pps', $data['pay_period_start'], PDO::PARAM_STR);
        $stmt->bindValue(':ppe', $data['pay_period_end'], PDO::PARAM_STR);
        $stmt->bindValue(':pay_date', $data['pay_date'], PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':created_by', $data['created_by'] ?? null, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /** List runs with filters and pagination */
    public function listRuns(int $page, int $limit, array $filters = []) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT pr.*, b.BranchName
                FROM payroll_runs_v2 pr
                JOIN hospital_branches b ON pr.BranchID = b.BranchID
                WHERE 1=1";
        $params = [];
        if (!empty($filters['branch_id'])) { $sql .= " AND pr.BranchID = :branch"; $params[':branch'] = (int)$filters['branch_id']; }
        if (!empty($filters['status'])) { $sql .= " AND pr.Status = :status"; $params[':status'] = $filters['status']; }
        if (!empty($filters['from'])) { $sql .= " AND pr.PayPeriodStart >= :from"; $params[':from'] = $filters['from']; }
        if (!empty($filters['to'])) { $sql .= " AND pr.PayPeriodEnd <= :to"; $params[':to'] = $filters['to']; }
        $sql .= " ORDER BY pr.PayPeriodEnd DESC, pr.PayrollRunID DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countRuns(array $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM payroll_runs_v2 pr WHERE 1=1";
        $params = [];
        if (!empty($filters['branch_id'])) { $sql .= " AND pr.BranchID = :branch"; $params[':branch'] = (int)$filters['branch_id']; }
        if (!empty($filters['status'])) { $sql .= " AND pr.Status = :status"; $params[':status'] = $filters['status']; }
        if (!empty($filters['from'])) { $sql .= " AND pr.PayPeriodStart >= :from"; $params[':from'] = $filters['from']; }
        if (!empty($filters['to'])) { $sql .= " AND pr.PayPeriodEnd <= :to"; $params[':to'] = $filters['to']; }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getRun(int $runId) {
        $sql = "SELECT pr.*, b.BranchName FROM payroll_runs_v2 pr JOIN hospital_branches b ON pr.BranchID = b.BranchID WHERE pr.PayrollRunID = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $runId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listPayslipsByRun(int $runId) {
        $sql = "SELECT ps.*, e.FirstName, e.LastName, e.JobTitle
                FROM payslips_v2 ps
                JOIN employees e ON ps.EmployeeID = e.EmployeeID
                WHERE ps.PayrollRunID = :id
                ORDER BY e.LastName, e.FirstName";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $runId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayslip(int $payslipId) {
        $sql = "SELECT ps.*, e.FirstName, e.LastName, e.Email, e.JobTitle
                FROM payslips_v2 ps
                JOIN employees e ON ps.EmployeeID = e.EmployeeID
                WHERE ps.PayslipID = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $payslipId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Process a run: generate payslips based on HR3 timesheets and HR Core salaries */
    public function processRun(int $runId) {
        $run = $this->getRun($runId);
        if (!$run) { throw new Exception('Payroll run not found'); }

        $this->pdo->beginTransaction();
        try {
            // Move to Processing
            $this->updateRun($runId, [ 'Status' => 'Processing' ]);

            // Get branch config
            $config = $this->getBranchConfig((int)$run['BranchID']);

            // Resolve employees (active in period, assigned to branch)
            $employees = $this->getEligibleEmployees((int)$run['BranchID'], $run['PayPeriodStart'], $run['PayPeriodEnd']);

            $totals = [ 'gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0 ];
            $count = 0;

            foreach ($employees as $emp) {
                $employeeId = (int)$emp['EmployeeID'];
                $salaryData = $this->getCurrentBaseSalary($employeeId);
                $allowances = $this->getAllowances($employeeId, $run['PayPeriodStart'], $run['PayPeriodEnd']);
                $bonuses = $this->getBonuses($employeeId, $run['PayPeriodStart'], $run['PayPeriodEnd']);
                $dtrData = $this->getOvertimeHoursFromTimesheets($employeeId, $run['PayPeriodStart'], $run['PayPeriodEnd']);
                
                // Calculate all pay components
                $basicPay = $this->calculateBasicPay($salaryData, $run['PayPeriodStart'], $run['PayPeriodEnd']);
                $overtimePay = $this->calculateOvertimePay($salaryData, $dtrData['overtime_hours'], $config);
                $nightDiffPay = $this->calculateNightDifferential($salaryData, $dtrData['night_hours'], $config);

                $gross = round(($basicPay + $allowances + $bonuses + $overtimePay + $nightDiffPay), 2);

                // Statutory contributions (simplified; replace with bracketed tables if available)
                $sss = $this->calcPercent($gross, (float)$config['SSSRateEmployee']);
                $philhealth = $this->calcPercent($gross, (float)$config['PhilHealthRateEmployee']);
                $pagibig = $this->calcPercent($gross, (float)$config['PagibigRateEmployee']);

                // Withholding tax (placeholder progressive bands)
                $tax = $this->calculateWithholdingTax($gross - ($sss + $philhealth + $pagibig), (string)$config['TaxTableVersion']);

                $otherDeds = $this->getOtherDeductions($emp['EmployeeID'], $from, $to);
                $totalDeductions = round($sss + $philhealth + $pagibig + $tax + $otherDeds, 2);
                $net = round($gross - $totalDeductions, 2);

                // Insert payslip
                $this->insertPayslip([
                    'PayrollRunID' => $runId,
                    'EmployeeID' => $employeeId,
                    'BranchID' => (int)$run['BranchID'],
                    'PayPeriodStart' => $run['PayPeriodStart'],
                    'PayPeriodEnd' => $run['PayPeriodEnd'],
                    'PayDate' => $run['PayDate'],
                    'BasicSalary' => $basicPay,
                    'OvertimeHours' => $dtrData['overtime_hours'],
                    'OvertimePay' => $overtimePay,
                    'NightDiffPay' => $nightDiffPay,
                    'Allowances' => $allowances,
                    'Bonuses' => $bonuses,
                    'GrossIncome' => $gross,
                    'SSS' => $sss,
                    'PhilHealth' => $philhealth,
                    'Pagibig' => $pagibig,
                    'Tax' => $tax,
                    'OtherDeductions' => $otherDeds,
                    'TotalDeductions' => $totalDeductions,
                    'NetIncome' => $net,
                    'DetailsJSON' => json_encode([
                        'components' => [
                            'basic_pay' => $basicPay,
                            'overtime_hours' => $dtrData['overtime_hours'],
                            'overtime_pay' => $overtimePay,
                            'night_diff_hours' => $dtrData['night_hours'],
                            'night_diff_pay' => $nightDiffPay,
                            'allowances' => $allowances,
                            'bonuses' => $bonuses,
                            'total_hours' => $dtrData['total_hours']
                        ],
                        'contributions' => [
                            'sss' => $sss,
                            'philhealth' => $philhealth,
                            'pagibig' => $pagibig,
                            'withholding_tax' => $tax
                        ],
                        'salary_info' => $salaryData
                    ])
                ]);

                $totals['gross'] += $gross;
                $totals['deductions'] += $totalDeductions;
                $totals['net'] += $net;
                $count++;
            }

            // Update run totals
            $this->updateRun($runId, [
                'Status' => 'Completed',
                'ProcessedAt' => date('Y-m-d H:i:s'),
                'TotalEmployees' => $count,
                'TotalGrossPay' => round($totals['gross'], 2),
                'TotalDeductions' => round($totals['deductions'], 2),
                'TotalNetPay' => round($totals['net'], 2)
            ]);

            $this->audit(null, $runId, 'process_run', 'Processed payroll run');
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateRun(int $runId, array $fields) {
        if (empty($fields)) return true;
        $allowed = ['Status','Notes','ProcessedAt','ApprovedBy','ApprovedAt','LockedAt','TotalEmployees','TotalGrossPay','TotalDeductions','TotalNetPay'];
        $set = [];
        $params = [':id' => $runId];
        foreach ($fields as $k=>$v) {
            if (!in_array($k, $allowed, true)) continue;
            $set[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (!$set) return true;
        $sql = "UPDATE payroll_runs_v2 SET " . implode(', ', $set) . " WHERE PayrollRunID = :id";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        return $stmt->execute();
    }

    private function insertPayslip(array $ps) {
        $sql = "INSERT INTO payslips_v2 (
                    PayrollRunID, EmployeeID, BranchID, PayPeriodStart, PayPeriodEnd, PayDate,
                    BasicSalary, OvertimeHours, OvertimePay, NightDiffPay, Allowances, Bonuses, GrossIncome,
                    SSS_Contribution, PhilHealth_Contribution, PagIBIG_Contribution, WithholdingTax,
                    OtherDeductions, TotalDeductions, NetIncome, Status, DetailsJSON
                ) VALUES (
                    :run, :emp, :branch, :pps, :ppe, :pd,
                    :basic, :oth, :otp, :ndp, :allow, :bonus, :gross,
                    :sss, :ph, :pi, :tax,
                    :other, :total, :net, 'Generated', :details
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':run', $ps['PayrollRunID'], PDO::PARAM_INT);
        $stmt->bindValue(':emp', $ps['EmployeeID'], PDO::PARAM_INT);
        $stmt->bindValue(':branch', $ps['BranchID'], PDO::PARAM_INT);
        $stmt->bindValue(':pps', $ps['PayPeriodStart'], PDO::PARAM_STR);
        $stmt->bindValue(':ppe', $ps['PayPeriodEnd'], PDO::PARAM_STR);
        $stmt->bindValue(':pd', $ps['PayDate'], PDO::PARAM_STR);
        $stmt->bindValue(':basic', $ps['BasicSalary']);
        $stmt->bindValue(':oth', $ps['OvertimeHours']);
        $stmt->bindValue(':otp', $ps['OvertimePay']);
        $stmt->bindValue(':ndp', $ps['NightDiffPay'] ?? 0.00);
        $stmt->bindValue(':allow', $ps['Allowances']);
        $stmt->bindValue(':bonus', $ps['Bonuses']);
        $stmt->bindValue(':gross', $ps['GrossIncome']);
        $stmt->bindValue(':sss', $ps['SSS']);
        $stmt->bindValue(':ph', $ps['PhilHealth']);
        $stmt->bindValue(':pi', $ps['Pagibig']);
        $stmt->bindValue(':tax', $ps['Tax']);
        $stmt->bindValue(':other', $ps['OtherDeductions']);
        $stmt->bindValue(':total', $ps['TotalDeductions']);
        $stmt->bindValue(':net', $ps['NetIncome']);
        $stmt->bindValue(':details', $ps['DetailsJSON']);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    private function getBranchConfig(int $branchId) {
        $stmt = $this->pdo->prepare("SELECT * FROM payroll_branch_config WHERE BranchID = :b");
        $stmt->bindValue(':b', $branchId, PDO::PARAM_INT);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$config) {
            // Fallback to defaults
            $config = [
                'OvertimeRate' => 1.25,
                'SSSRateEmployee' => 0.0450,
                'PhilHealthRateEmployee' => 0.0200,
                'PagibigRateEmployee' => 0.0100,
                'TaxTableVersion' => '2024'
            ];
        }
        return $config;
    }

    private function getEligibleEmployees(int $branchId, string $from, string $to) {
        // By branch assignment OR default to all active if no assignment exists
        $sql = "SELECT e.EmployeeID, e.JobTitle
                FROM employees e
                LEFT JOIN employee_branch_assignments a ON a.EmployeeID = e.EmployeeID
                    AND a.BranchID = :b AND (a.EndDate IS NULL OR a.EndDate >= :from) AND a.EffectiveDate <= :to
                WHERE e.IsActive = 1 AND (a.AssignmentID IS NOT NULL OR NOT EXISTS (
                    SELECT 1 FROM employee_branch_assignments x WHERE x.EmployeeID = e.EmployeeID
                ))";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':b', $branchId, PDO::PARAM_INT);
        $stmt->bindValue(':from', $from, PDO::PARAM_STR);
        $stmt->bindValue(':to', $to, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCurrentBaseSalary(int $employeeId) {
        // Get current salary from HR1/HR2 integration (employeesalaries table)
        $sql = "SELECT BaseSalary, PayFrequency, PayRate FROM employeesalaries 
                WHERE EmployeeID = :eid AND (IsCurrent = 1 OR (EndDate IS NULL)) 
                ORDER BY EffectiveDate DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'base_salary' => (float)($row['BaseSalary'] ?? 0),
            'pay_frequency' => $row['PayFrequency'] ?? 'Monthly',
            'pay_rate' => (float)($row['PayRate'] ?? 0)
        ];
    }

    private function getOvertimeHoursFromTimesheets(int $employeeId, string $from, string $to) {
        // Get comprehensive DTR data from HR3
        $sql = "SELECT 
                    COALESCE(SUM(OvertimeHours), 0) as overtime_hours,
                    COALESCE(SUM(TotalHoursWorked), 0) as total_hours,
                    COALESCE(SUM(CASE WHEN ScheduleID IN (2, 4) THEN TotalHoursWorked ELSE 0 END), 0) as night_hours
                FROM timesheets 
                WHERE EmployeeID = :eid 
                AND PeriodStartDate >= :from 
                AND PeriodEndDate <= :to
                AND Status = 'Approved'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':eid', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':from', $from, PDO::PARAM_STR);
        $stmt->bindValue(':to', $to, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'overtime_hours' => (float)($row['overtime_hours'] ?? 0),
            'total_hours' => (float)($row['total_hours'] ?? 0),
            'night_hours' => (float)($row['night_hours'] ?? 0)
        ];
    }

    private function getAllowances(int $employeeId, string $from, string $to) {
        // Get allowances from HR2/HR4 Compensation Planning
        $sql = "SELECT SUM(Amount) as total_allowances 
                FROM deductions 
                WHERE EmployeeID = :emp_id 
                AND DeductionType IN ('Allowance', 'Transportation', 'Meal', 'Housing')
                -- Use DeductionAmount column and avoid non-existent status/effective columns
                AND DeductionType IN ('Allowance', 'Transportation', 'Meal', 'Housing')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':emp_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':from_date', $from, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $to, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_allowances'] ?? 0);
    }

    private function getBonuses(int $employeeId, string $from, string $to) {
        // Get bonuses from payroll_bonuses table
        $sql = "SELECT SUM(Amount) as total_bonuses 
                FROM payroll_bonuses 
                WHERE EmployeeID = :emp_id 
                AND Status IN ('Computed', 'Approved', 'Paid')
                AND BonusDate BETWEEN :from_date AND :to_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':emp_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':from_date', $from, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $to, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_bonuses'] ?? 0);
    }

    private function getOtherDeductions(int $employeeId, string $from, string $to) {
        // Get voluntary deductions from deductions table
    $sql = "SELECT SUM(DeductionAmount) as total_deductions 
        FROM deductions 
        WHERE EmployeeID = :emp_id 
        AND DeductionType NOT IN ('SSS','PhilHealth','Pag-IBIG','Tax')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':emp_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':from_date', $from, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $to, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total_deductions'] ?? 0);
    }

    private function calculateBasicPay(array $salaryData, string $from, string $to) {
        $baseSalary = $salaryData['base_salary'];
        $payFrequency = $salaryData['pay_frequency'];
        
        // Calculate basic pay based on frequency and period
        if ($payFrequency === 'Monthly') {
            // For semi-monthly periods, divide monthly by 2
            $daysInPeriod = $this->getWorkingDaysInPeriod($from, $to);
            $daysInMonth = 22; // Approximate working days per month
            return round(($baseSalary / $daysInMonth) * $daysInPeriod, 2);
        } elseif ($payFrequency === 'Daily') {
            $daysInPeriod = $this->getWorkingDaysInPeriod($from, $to);
            return round($baseSalary * $daysInPeriod, 2);
        } elseif ($payFrequency === 'Hourly') {
            $hoursInPeriod = $this->getWorkingHoursInPeriod($from, $to);
            return round($baseSalary * $hoursInPeriod, 2);
        }
        
        return $baseSalary; // Default to full amount
    }

    private function calculateOvertimePay(array $salaryData, float $otHours, array $config) {
        if ($otHours <= 0) return 0.00;
        
        $baseSalary = $salaryData['base_salary'];
        $payFrequency = $salaryData['pay_frequency'];
        
        // Calculate hourly rate based on pay frequency
        if ($payFrequency === 'Monthly') {
            $hourly = $baseSalary / 22.0 / 8.0; // Monthly / 22 days / 8 hours
        } elseif ($payFrequency === 'Daily') {
            $hourly = $baseSalary / 8.0; // Daily / 8 hours
        } else {
            $hourly = $baseSalary; // Already hourly
        }
        
        $rate = (float)($config['OvertimeRate'] ?? 1.25);
        return round($hourly * $rate * $otHours, 2);
    }

    private function calculateNightDifferential(array $salaryData, float $nightHours, array $config) {
        if ($nightHours <= 0) return 0.00;
        
        $baseSalary = $salaryData['base_salary'];
        $payFrequency = $salaryData['pay_frequency'];
        
        // Calculate hourly rate
        if ($payFrequency === 'Monthly') {
            $hourly = $baseSalary / 22.0 / 8.0;
        } elseif ($payFrequency === 'Daily') {
            $hourly = $baseSalary / 8.0;
        } else {
            $hourly = $baseSalary;
        }
        
        $nightDiffRate = 0.10; // 10% night differential
        return round($hourly * $nightDiffRate * $nightHours, 2);
    }

    private function getWorkingDaysInPeriod(string $from, string $to) {
        $start = new DateTime($from);
        $end = new DateTime($to);
        $days = 0;
        
        while ($start <= $end) {
            // Count weekdays only (Monday = 1, Sunday = 7)
            if ($start->format('N') < 6) {
                $days++;
            }
            $start->add(new DateInterval('P1D'));
        }
        
        return $days;
    }

    private function getWorkingHoursInPeriod(string $from, string $to) {
        return $this->getWorkingDaysInPeriod($from, $to) * 8; // 8 hours per working day
    }

    private function calcPercent(float $base, float $rate) {
        return round(max($base, 0) * max($rate, 0), 2);
    }

    private function calculateWithholdingTax(float $taxable, string $version) {
        if ($taxable <= 0) return 0.00;
        // Simplified band (example only); replace with BIR table per version
        $tax = 0.0;
        if ($taxable <= 20833) $tax = 0;
        else if ($taxable <= 33333) $tax = 0.20 * ($taxable - 20833);
        else if ($taxable <= 66667) $tax = 2500 + 0.25 * ($taxable - 33333);
        else if ($taxable <= 166667) $tax = 10833 + 0.30 * ($taxable - 66667);
        else if ($taxable <= 666667) $tax = 40833 + 0.32 * ($taxable - 166667);
        else $tax = 200833 + 0.35 * ($taxable - 666667);
        return round($tax, 2);
    }

    public function approveRun(int $runId, int $userId) {
        return $this->updateRun($runId, [ 'Status' => 'Approved', 'ApprovedBy' => $userId, 'ApprovedAt' => date('Y-m-d H:i:s') ]);
    }

    public function lockRun(int $runId) {
        return $this->updateRun($runId, [ 'Status' => 'Locked', 'LockedAt' => date('Y-m-d H:i:s') ]);
    }

    public function audit(?int $payslipId, ?int $runId, string $action, string $details = null, ?int $actorUserId = null, ?string $actorRole = null) {
        $sql = "INSERT INTO payroll_audit_logs (PayrollRunID, PayslipID, Action, ActorUserID, ActorRole, Details) VALUES (:run, :ps, :action, :uid, :role, :details)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':run', $runId, $runId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ps', $payslipId, $payslipId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $actorUserId, $actorUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':role', $actorRole, PDO::PARAM_STR);
        $stmt->bindValue(':details', $details, PDO::PARAM_STR);
        return $stmt->execute();
    }
}

?>


