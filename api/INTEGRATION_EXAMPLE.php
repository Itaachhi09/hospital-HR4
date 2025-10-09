<?php
/**
 * HMO Module Integration Example
 * 
 * This file demonstrates how to integrate the HMO module with
 * existing Payroll, Compensation, and Analytics systems.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/integrations/HMOPayrollIntegration.php';
require_once __DIR__ . '/integrations/HMOAnalyticsIntegration.php';

/**
 * Example 1: Monthly Payroll Processing with HMO Deductions
 */
function processMonthlyPayrollWithHMO($payrollRunId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $hmoPayroll = new HMOPayrollIntegration();
        
        // Step 1: Apply HMO deductions
        $deductionResult = $hmoPayroll->applyHMODeductionsToPayroll($payrollRunId);
        
        if (!$deductionResult['success']) {
            throw new Exception('Failed to apply HMO deductions: ' . $deductionResult['error']);
        }
        
        echo "✅ Applied HMO deductions:\n";
        echo "   - Count: {$deductionResult['applied_count']}\n";
        echo "   - Total: ₱" . number_format($deductionResult['total_amount'], 2) . "\n\n";
        
        // Step 2: Apply pending reimbursements
        $reimbursementResult = $hmoPayroll->applyReimbursementsToPayroll($payrollRunId);
        
        if (!$reimbursementResult['success']) {
            throw new Exception('Failed to apply reimbursements: ' . $reimbursementResult['error']);
        }
        
        echo "✅ Applied reimbursements:\n";
        echo "   - Count: {$reimbursementResult['applied_count']}\n";
        echo "   - Total: ₱" . number_format($reimbursementResult['total_amount'], 2) . "\n\n";
        
        // Step 3: Get cost summary
        $summary = $hmoPayroll->getHMOCostSummary(date('n'), date('Y'));
        
        echo "📊 HMO Cost Summary:\n";
        echo "   - Enrollments: {$summary['total_enrollments']}\n";
        echo "   - Employee Share: ₱" . number_format($summary['total_employee_share'], 2) . "\n";
        echo "   - Employer Share: ₱" . number_format($summary['total_employer_share'], 2) . "\n";
        echo "   - Total Cost: ₱" . number_format($summary['total_cost'], 2) . "\n";
        echo "   - Reimbursements: ₱" . number_format($summary['total_reimbursements'], 2) . "\n\n";
        
        $pdo->commit();
        
        return [
            'success' => true,
            'deductions' => $deductionResult,
            'reimbursements' => $reimbursementResult,
            'summary' => $summary
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Payroll processing error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Example 2: Generate Monthly Healthcare Analytics Report
 */
function generateMonthlyHealthcareReport($yearMonth) {
    $analytics = new HMOAnalyticsIntegration();
    
    echo "🏥 Healthcare Analytics Report - $yearMonth\n";
    echo "==========================================\n\n";
    
    // Get dashboard data
    $dashboard = $analytics->getHealthcareDashboard();
    
    // Overview
    $overview = $dashboard['overview'];
    echo "📊 Overview:\n";
    echo "   - Active Enrollments: {$overview['active_enrollments']}\n";
    echo "   - Covered Employees: {$overview['covered_employees']}\n";
    echo "   - Pending Claims: {$overview['pending_claims']} (₱" . number_format($overview['pending_amount'], 2) . ")\n";
    echo "   - MTD Claims: ₱" . number_format($overview['mtd_claims'], 2) . "\n";
    echo "   - YTD Claims: ₱" . number_format($overview['ytd_claims'], 2) . "\n";
    echo "   - Monthly Premium Cost: ₱" . number_format($overview['monthly_premium_cost'], 2) . "\n";
    echo "   - Avg Processing Days: " . round($overview['avg_processing_days'], 1) . " days\n\n";
    
    // Department costs
    echo "🏢 Top Departments by Cost:\n";
    $topDepts = array_slice($dashboard['department_costs'], 0, 5);
    foreach ($topDepts as $dept) {
        echo sprintf("   - %-30s ₱%s (%d claims)\n",
            $dept['DepartmentName'],
            number_format($dept['total_claims'], 2),
            $dept['claim_count']
        );
    }
    echo "\n";
    
    // Provider performance
    echo "🏥 Provider Performance:\n";
    foreach ($dashboard['provider_performance'] as $provider) {
        echo sprintf("   - %-30s %d enrollments | %d claims | %.1f days avg | %.1f%% approval\n",
            $provider['ProviderName'],
            $provider['enrollment_count'],
            $provider['total_claims'],
            $provider['avg_processing_days'],
            $provider['approval_rate']
        );
    }
    echo "\n";
    
    // Cost projections
    $projections = $dashboard['cost_projections'];
    echo "📈 Cost Projections:\n";
    echo "   - Monthly Premium: ₱" . number_format($projections['monthly_premium'], 2) . "\n";
    echo "   - Avg Monthly Claims: ₱" . number_format($projections['avg_monthly_claims'], 2) . "\n";
    echo "   - Projected Monthly Cost: ₱" . number_format($projections['projected_monthly_cost'], 2) . "\n";
    echo "   - Projected Annual Cost: ₱" . number_format($projections['projected_annual_cost'], 2) . "\n";
    echo "   - Cost per Employee: ₱" . number_format($projections['cost_per_employee'], 2) . "\n\n";
    
    return $dashboard;
}

/**
 * Example 3: Export Healthcare Data to Finance System
 */
function exportHealthcareDataToFinance($period) {
    $analytics = new HMOAnalyticsIntegration();
    
    echo "📤 Exporting healthcare data for period: $period\n\n";
    
    // Generate export data
    $exportData = $analytics->exportToFinanceAnalytics($period);
    
    // Save to file
    $filename = "exports/healthcare_export_{$period}.json";
    $dir = dirname(__DIR__) . '/exports';
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents(
        $dir . '/' . basename($filename),
        json_encode($exportData, JSON_PRETTY_PRINT)
    );
    
    echo "✅ Export completed:\n";
    echo "   - File: $filename\n";
    echo "   - Period: {$exportData['period']}\n";
    echo "   - Export Date: {$exportData['export_date']}\n";
    echo "   - Total Claims: {$exportData['summary']['total_claims']}\n";
    echo "   - Total Amount: ₱" . number_format($exportData['summary']['total_claim_amount'], 2) . "\n\n";
    
    // In a real system, you would send this data to the Finance API
    // Example:
    // $financeAPI = new FinanceIntegration();
    // $financeAPI->sendHealthcareData($exportData);
    
    return $exportData;
}

/**
 * Example 4: Sync Employee HMO Benefits with Compensation Package
 */
function syncEmployeeCompensation($employeeId) {
    $hmoPayroll = new HMOPayrollIntegration();
    
    echo "🔄 Syncing HMO benefits for Employee #$employeeId\n";
    
    $result = $hmoPayroll->syncWithCompensation($employeeId);
    
    if ($result) {
        echo "✅ Compensation package updated with HMO benefits\n\n";
    } else {
        echo "❌ Failed to sync compensation package\n\n";
    }
    
    return $result;
}

/**
 * Example 5: Automated Workflow - New Employee Enrollment
 */
function enrollNewEmployeeInHMO($employeeId, $planId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        require_once __DIR__ . '/models/HMOEnrollment.php';
        $enrollmentModel = new HMOEnrollment();
        
        // Get plan details
        $planSql = "SELECT p.*, pr.ProviderName 
                    FROM hmoplans p 
                    JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID 
                    WHERE p.PlanID = ?";
        $planStmt = $pdo->prepare($planSql);
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            throw new Exception("Plan not found");
        }
        
        // Create enrollment
        $enrollmentData = [
            'employee_id' => $employeeId,
            'plan_id' => $planId,
            'enrollment_date' => date('Y-m-d'),
            'effective_date' => date('Y-m-d', strtotime('first day of next month')),
            'monthly_deduction' => $plan['MonthlyPremium'] * 0.5, // 50% employee share
            'monthly_contribution' => $plan['MonthlyPremium'] * 0.5, // 50% employer share
            'status' => 'Active'
        ];
        
        $enrollmentId = $enrollmentModel->createEnrollment($enrollmentData);
        
        echo "✅ Employee enrolled in HMO:\n";
        echo "   - Enrollment ID: $enrollmentId\n";
        echo "   - Plan: {$plan['PlanName']}\n";
        echo "   - Provider: {$plan['ProviderName']}\n";
        echo "   - Effective Date: {$enrollmentData['effective_date']}\n";
        echo "   - Monthly Deduction: ₱" . number_format($enrollmentData['monthly_deduction'], 2) . "\n\n";
        
        // Sync with compensation
        $hmoPayroll = new HMOPayrollIntegration();
        $hmoPayroll->syncWithCompensation($employeeId);
        
        $pdo->commit();
        
        return $enrollmentId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Enrollment error: ' . $e->getMessage());
        echo "❌ Error: {$e->getMessage()}\n\n";
        return false;
    }
}

// ===================================================================
// USAGE EXAMPLES - Uncomment to test
// ===================================================================

// Example 1: Process October 2025 payroll with HMO
// $payrollRunId = 42;
// $result = processMonthlyPayrollWithHMO($payrollRunId);

// Example 2: Generate monthly healthcare report
// generateMonthlyHealthcareReport('2025-10');

// Example 3: Export data to finance for October 2025
// exportHealthcareDataToFinance('2025-10');

// Example 4: Sync compensation for employee
// syncEmployeeCompensation(123);

// Example 5: Enroll new employee
// enrollNewEmployeeInHMO(456, 3);

echo "✅ Integration examples ready. Uncomment code blocks to test.\n";
?>

