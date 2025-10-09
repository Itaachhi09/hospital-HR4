<?php
/**
 * Payroll Integration for Compensation Planning
 * Handles synchronization with Payroll modules
 */

require_once __DIR__ . '/../config.php';

class PayrollIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Update employee base salary in payroll system
     */
    public function updateEmployeeBaseSalary($employeeId, $newBaseSalary, $effectiveDate, $reason = 'Compensation Planning Update') {
        try {
            $this->pdo->beginTransaction();
            
            // Update current salary record
            $updateSql = "UPDATE employeesalaries 
                         SET IsCurrent = 0 
                         WHERE EmployeeID = :employee_id AND IsCurrent = 1";
            $stmt = $this->pdo->prepare($updateSql);
            $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Insert new salary record
            $insertSql = "INSERT INTO employeesalaries 
                         (EmployeeID, BaseSalary, PayFrequency, PayRate, EffectiveDate, IsCurrent, AdjustmentReason, CreatedAt) 
                         VALUES (:employee_id, :base_salary, 'Monthly', :base_salary, :effective_date, 1, :reason, NOW())";
            $stmt = $this->pdo->prepare($insertSql);
            $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
            $stmt->bindParam(':base_salary', $newBaseSalary, PDO::PARAM_STR);
            $stmt->bindParam(':effective_date', $effectiveDate);
            $stmt->bindParam(':reason', $reason);
            $stmt->execute();
            
            // Log the change
            $this->logSalaryChange($employeeId, $newBaseSalary, $effectiveDate, $reason);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Base salary updated successfully',
                'data' => [
                    'employee_id' => $employeeId,
                    'new_base_salary' => $newBaseSalary,
                    'effective_date' => $effectiveDate
                ]
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update base salary: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update salaries for multiple employees
     */
    public function bulkUpdateSalaries($salaryUpdates, $effectiveDate, $reason = 'Bulk Compensation Update') {
        try {
            $this->pdo->beginTransaction();
            
            $results = [];
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($salaryUpdates as $update) {
                $result = $this->updateEmployeeBaseSalary(
                    $update['employee_id'], 
                    $update['new_salary'], 
                    $effectiveDate, 
                    $reason
                );
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                
                $results[] = $result;
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Bulk update completed: {$successCount} successful, {$errorCount} errors",
                'data' => [
                    'total_processed' => count($salaryUpdates),
                    'successful' => $successCount,
                    'errors' => $errorCount,
                    'results' => $results
                ]
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate payroll impact for salary changes
     */
    public function calculatePayrollImpact($salaryChanges, $payPeriodStart, $payPeriodEnd) {
        $totalImpact = 0;
        $employeeImpacts = [];
        
        foreach ($salaryChanges as $change) {
            $employeeId = $change['employee_id'];
            $currentSalary = $change['current_salary'];
            $newSalary = $change['new_salary'];
            
            // Calculate monthly impact
            $monthlyImpact = $newSalary - $currentSalary;
            
            // Calculate period impact based on pay frequency
            $periodImpact = $this->calculatePeriodImpact($monthlyImpact, $payPeriodStart, $payPeriodEnd);
            
            $totalImpact += $periodImpact;
            
            $employeeImpacts[] = [
                'employee_id' => $employeeId,
                'current_salary' => $currentSalary,
                'new_salary' => $newSalary,
                'monthly_impact' => $monthlyImpact,
                'period_impact' => $periodImpact
            ];
        }
        
        return [
            'total_impact' => $totalImpact,
            'employee_impacts' => $employeeImpacts,
            'period_start' => $payPeriodStart,
            'period_end' => $payPeriodEnd
        ];
    }

    /**
     * Calculate period impact based on pay frequency
     */
    private function calculatePeriodImpact($monthlyImpact, $payPeriodStart, $payPeriodEnd) {
        // Calculate days in period
        $startDate = new DateTime($payPeriodStart);
        $endDate = new DateTime($payPeriodEnd);
        $daysInPeriod = $startDate->diff($endDate)->days + 1;
        
        // Calculate daily impact
        $dailyImpact = $monthlyImpact / 22; // Assuming 22 working days per month
        
        // Return period impact
        return $dailyImpact * $daysInPeriod;
    }

    /**
     * Get current payroll data for employees
     */
    public function getCurrentPayrollData($employeeIds = []) {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    es.BaseSalary,
                    es.PayFrequency,
                    es.EffectiveDate,
                    -- Calculate current rates
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22 / 8, 2)
                        WHEN es.PayFrequency = 'Daily' THEN ROUND(es.BaseSalary / 8, 2)
                        ELSE es.PayRate
                    END as hourly_rate,
                    -- Get recent payroll data
                    COALESCE(pr.total_gross, 0) as last_payroll_gross,
                    COALESCE(pr.total_deductions, 0) as last_payroll_deductions,
                    COALESCE(pr.net_pay, 0) as last_payroll_net
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN (
                    SELECT 
                        EmployeeID,
                        SUM(GrossIncome) as total_gross,
                        SUM(TotalDeductions) as total_deductions,
                        SUM(NetIncome) as net_pay
                    FROM payslips 
                    WHERE PayPeriodEndDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    GROUP BY EmployeeID
                ) pr ON e.EmployeeID = pr.EmployeeID
                WHERE e.IsActive = 1";
        
        if (!empty($employeeIds)) {
            $placeholders = str_repeat('?,', count($employeeIds) - 1) . '?';
            $sql .= " AND e.EmployeeID IN ($placeholders)";
        }
        
        $sql .= " ORDER BY e.LastName, e.FirstName";
        
        $stmt = $this->pdo->prepare($sql);
        if (!empty($employeeIds)) {
            foreach ($employeeIds as $index => $employeeId) {
                $stmt->bindValue($index + 1, $employeeId, PDO::PARAM_INT);
            }
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Notify payroll system of upcoming changes
     */
    public function notifyPayrollOfChanges($changes, $effectiveDate) {
        // This would integrate with your payroll notification system
        // For now, we'll log the notification
        $notification = [
            'type' => 'salary_changes',
            'effective_date' => $effectiveDate,
            'changes_count' => count($changes),
            'total_impact' => array_sum(array_column($changes, 'impact')),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Log to database or send to notification system
        $this->logPayrollNotification($notification);
        
        return [
            'success' => true,
            'message' => 'Payroll system notified of upcoming changes',
            'data' => $notification
        ];
    }

    /**
     * Log salary change
     */
    private function logSalaryChange($employeeId, $newSalary, $effectiveDate, $reason) {
        $sql = "INSERT INTO salary_change_log 
                (employee_id, new_salary, effective_date, reason, created_at) 
                VALUES (:employee_id, :new_salary, :effective_date, :reason, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':new_salary', $newSalary, PDO::PARAM_STR);
        $stmt->bindParam(':effective_date', $effectiveDate);
        $stmt->bindParam(':reason', $reason);
        $stmt->execute();
    }

    /**
     * Log payroll notification
     */
    private function logPayrollNotification($notification) {
        $sql = "INSERT INTO payroll_notifications 
                (type, data, created_at) 
                VALUES (:type, :data, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':type', $notification['type']);
        $stmt->bindParam(':data', json_encode($notification));
        $stmt->execute();
    }

    /**
     * Get payroll impact summary for reporting
     */
    public function getPayrollImpactSummary($dateFrom, $dateTo) {
        $sql = "SELECT 
                    DATE(created_at) as change_date,
                    COUNT(*) as total_changes,
                    SUM(new_salary - LAG(new_salary) OVER (PARTITION BY employee_id ORDER BY created_at)) as total_impact
                FROM salary_change_log 
                WHERE created_at BETWEEN :date_from AND :date_to
                GROUP BY DATE(created_at)
                ORDER BY change_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':date_from', $dateFrom);
        $stmt->bindParam(':date_to', $dateTo);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
