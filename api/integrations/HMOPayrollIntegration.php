<?php
/**
 * HMO-Payroll Integration
 * Handles HMO deductions, contributions, and reimbursements in payroll
 */

class HMOPayrollIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get HMO deductions for payroll run
     * 
     * @param int $payrollRunId
     * @return array HMO deductions by employee
     */
    public function getHMODeductionsForPayroll($payrollRunId = null, $month = null, $year = null) {
        try {
            // If payroll run ID is provided, get its period
            if ($payrollRunId) {
                $runSql = "SELECT Month, Year FROM payroll_runs WHERE PayrollRunID = :run_id";
                $runStmt = $this->pdo->prepare($runSql);
                $runStmt->execute([':run_id' => $payrollRunId]);
                $run = $runStmt->fetch(PDO::FETCH_ASSOC);
                if ($run) {
                    $month = $run['Month'];
                    $year = $run['Year'];
                }
            }

            // Default to current month if not specified
            if (!$month || !$year) {
                $month = date('n');
                $year = date('Y');
            }

            $sql = "SELECT 
                        e.EmployeeID,
                        emp.FirstName,
                        emp.LastName,
                        emp.EmployeeNumber,
                        p.PlanName,
                        pr.ProviderName,
                        COALESCE(e.MonthlyDeduction, 0) as employee_share,
                        COALESCE(e.MonthlyContribution, 0) as employer_share,
                        COALESCE(e.MonthlyDeduction, 0) as deduction_amount,
                        'HMO Premium' as description
                    FROM employeehmoenrollments e
                    JOIN employees emp ON e.EmployeeID = emp.EmployeeID
                    JOIN hmoplans p ON e.PlanID = p.PlanID
                    JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                    WHERE e.Status = 'Active'
                        AND (e.EffectiveDate <= LAST_DAY(CONCAT(:year, '-', :month, '-01'))
                        AND (e.EndDate IS NULL OR e.EndDate >= CONCAT(:year, '-', :month, '-01')))
                    ORDER BY emp.LastName, emp.FirstName";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                ':year' => $year
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get HMO deductions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Apply HMO deductions to payroll run
     * 
     * @param int $payrollRunId
     * @return array Result with count of applied deductions
     */
    public function applyHMODeductionsToPayroll($payrollRunId) {
        try {
            $this->pdo->beginTransaction();

            $deductions = $this->getHMODeductionsForPayroll($payrollRunId);
            $applied = 0;

            foreach ($deductions as $deduction) {
                if ($deduction['deduction_amount'] <= 0) continue;

                // Check if deduction already exists
                $checkSql = "SELECT COUNT(*) FROM Deductions 
                             WHERE PayrollRunID = :run_id 
                             AND EmployeeID = :employee_id 
                             AND DeductionType = 'HMO Premium'";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([
                    ':run_id' => $payrollRunId,
                    ':employee_id' => $deduction['EmployeeID']
                ]);

                if ($checkStmt->fetchColumn() > 0) {
                    continue; // Skip if already exists
                }

                // Insert deduction
                $insertSql = "INSERT INTO Deductions (
                                PayrollRunID, EmployeeID, DeductionType, 
                                Amount, Description, CreatedAt
                              ) VALUES (
                                :run_id, :employee_id, 'HMO Premium',
                                :amount, :description, NOW()
                              )";
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':run_id' => $payrollRunId,
                    ':employee_id' => $deduction['EmployeeID'],
                    ':amount' => $deduction['deduction_amount'],
                    ':description' => sprintf('HMO: %s (%s)', 
                        $deduction['PlanName'], 
                        $deduction['ProviderName']
                    )
                ]);

                $applied++;
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'applied_count' => $applied,
                'total_amount' => array_sum(array_column($deductions, 'deduction_amount'))
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Apply HMO deductions error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get pending HMO reimbursements
     * 
     * @return array Pending reimbursements
     */
    public function getPendingReimbursements() {
        try {
            $sql = "SELECT 
                        r.ReimbursementID,
                        r.ClaimID,
                        r.EmployeeID,
                        r.Amount,
                        r.Status,
                        r.ProcessedDate,
                        r.Notes,
                        emp.FirstName,
                        emp.LastName,
                        emp.EmployeeNumber,
                        c.ClaimNumber,
                        c.ClaimType,
                        c.Description as ClaimDescription,
                        c.ApprovedDate
                    FROM hmo_reimbursements r
                    JOIN employees emp ON r.EmployeeID = emp.EmployeeID
                    JOIN hmoclaims c ON r.ClaimID = c.ClaimID
                    WHERE r.Status = 'Pending'
                        AND c.Status = 'Approved'
                    ORDER BY c.ApprovedDate ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get pending reimbursements error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Apply HMO reimbursements to payroll run
     * 
     * @param int $payrollRunId
     * @param array $reimbursementIds Optional array of specific reimbursement IDs
     * @return array Result with count of applied reimbursements
     */
    public function applyReimbursementsToPayroll($payrollRunId, $reimbursementIds = null) {
        try {
            $this->pdo->beginTransaction();

            if ($reimbursementIds) {
                $placeholders = implode(',', array_fill(0, count($reimbursementIds), '?'));
                $sql = "SELECT 
                            r.*,
                            emp.FirstName,
                            emp.LastName,
                            c.ClaimNumber
                        FROM hmo_reimbursements r
                        JOIN employees emp ON r.EmployeeID = emp.EmployeeID
                        JOIN hmoclaims c ON r.ClaimID = c.ClaimID
                        WHERE r.ReimbursementID IN ($placeholders)
                            AND r.Status = 'Pending'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($reimbursementIds);
            } else {
                $stmt = $this->pdo->query("
                    SELECT 
                        r.*,
                        emp.FirstName,
                        emp.LastName,
                        c.ClaimNumber
                    FROM hmo_reimbursements r
                    JOIN employees emp ON r.EmployeeID = emp.EmployeeID
                    JOIN hmoclaims c ON r.ClaimID = c.ClaimID
                    WHERE r.Status = 'Pending'
                ");
            }

            $reimbursements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $applied = 0;

            foreach ($reimbursements as $reimb) {
                // Add as a benefit/allowance in payroll
                $insertSql = "INSERT INTO Bonuses (
                                PayrollRunID, EmployeeID, BonusType,
                                Amount, Description, CreatedAt
                              ) VALUES (
                                :run_id, :employee_id, 'HMO Reimbursement',
                                :amount, :description, NOW()
                              )";
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':run_id' => $payrollRunId,
                    ':employee_id' => $reimb['EmployeeID'],
                    ':amount' => $reimb['Amount'],
                    ':description' => sprintf('HMO Claim Reimbursement: %s', $reimb['ClaimNumber'])
                ]);

                // Update reimbursement status
                $updateSql = "UPDATE hmo_reimbursements 
                              SET Status = 'Processed',
                                  PayrollRunID = :run_id,
                                  ProcessedDate = NOW()
                              WHERE ReimbursementID = :reimb_id";
                $updateStmt = $this->pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':run_id' => $payrollRunId,
                    ':reimb_id' => $reimb['ReimbursementID']
                ]);

                $applied++;
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'applied_count' => $applied,
                'total_amount' => array_sum(array_column($reimbursements, 'Amount'))
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Apply reimbursements error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark reimbursements as paid after payroll processing
     * 
     * @param int $payrollRunId
     * @return bool Success status
     */
    public function markReimbursementsAsPaid($payrollRunId) {
        try {
            $sql = "UPDATE hmo_reimbursements 
                    SET Status = 'Paid', PaidDate = NOW()
                    WHERE PayrollRunID = :run_id AND Status = 'Processed'";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':run_id' => $payrollRunId]);

        } catch (Exception $e) {
            error_log("Mark reimbursements as paid error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get HMO cost summary for payroll period
     * 
     * @param int $month
     * @param int $year
     * @return array Cost summary
     */
    public function getHMOCostSummary($month = null, $year = null) {
        if (!$month) $month = date('n');
        if (!$year) $year = date('Y');

        try {
            $sql = "SELECT 
                        COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                        COALESCE(SUM(e.MonthlyDeduction), 0) as total_employee_share,
                        COALESCE(SUM(e.MonthlyContribution), 0) as total_employer_share,
                        COALESCE(SUM(e.MonthlyDeduction + e.MonthlyContribution), 0) as total_cost,
                        (SELECT COALESCE(SUM(Amount), 0) 
                         FROM hmo_reimbursements 
                         WHERE Status IN ('Processed', 'Paid')
                         AND MONTH(ProcessedDate) = :month
                         AND YEAR(ProcessedDate) = :year) as total_reimbursements
                    FROM employeehmoenrollments e
                    WHERE e.Status = 'Active'
                        AND (e.EffectiveDate <= LAST_DAY(CONCAT(:year2, '-', :month2, '-01'))
                        AND (e.EndDate IS NULL OR e.EndDate >= CONCAT(:year2, '-', :month2, '-01')))";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year,
                ':month2' => str_pad($month, 2, '0', STR_PAD_LEFT),
                ':year2' => $year
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get HMO cost summary error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync HMO data with compensation package
     * Updates employee total compensation to include HMO benefits value
     * 
     * @param int $employeeId
     * @return bool Success status
     */
    public function syncWithCompensation($employeeId) {
        try {
            // Get employee's active HMO enrollment value
            $sql = "SELECT 
                        COALESCE(SUM(p.MonthlyPremium), 0) as hmo_value
                    FROM employeehmoenrollments e
                    JOIN hmoplans p ON e.PlanID = p.PlanID
                    WHERE e.EmployeeID = :employee_id AND e.Status = 'Active'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':employee_id' => $employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $hmoValue = $result['hmo_value'] ?? 0;

            // Update or insert into compensation package tracking
            // This assumes there's a compensation benefits table
            $updateSql = "INSERT INTO employee_compensation_benefits (
                            EmployeeID, BenefitType, MonthlyValue, AnnualValue, UpdatedAt
                          ) VALUES (
                            :employee_id, 'HMO', :monthly_value, :annual_value, NOW()
                          ) ON DUPLICATE KEY UPDATE
                            MonthlyValue = :monthly_value2,
                            AnnualValue = :annual_value2,
                            UpdatedAt = NOW()";
            
            $updateStmt = $this->pdo->prepare($updateSql);
            return $updateStmt->execute([
                ':employee_id' => $employeeId,
                ':monthly_value' => $hmoValue,
                ':annual_value' => $hmoValue * 12,
                ':monthly_value2' => $hmoValue,
                ':annual_value2' => $hmoValue * 12
            ]);

        } catch (Exception $e) {
            error_log("Sync with compensation error: " . $e->getMessage());
            return false;
        }
    }
}

