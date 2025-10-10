<?php
/**
 * Integration Routes for Compensation Planning
 * Handles integration with HR Core, Payroll, Finance, and Analytics
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../integrations/HRCoreIntegration.php';
require_once __DIR__ . '/../integrations/PayrollIntegration.php';
require_once __DIR__ . '/../integrations/FinanceIntegration.php';
require_once __DIR__ . '/../integrations/AnalyticsIntegration.php';
require_once __DIR__ . '/../integrations/ReportingSystem.php';
require_once __DIR__ . '/../integrations/HMOPayrollIntegration.php';
require_once __DIR__ . '/../integrations/HMOAnalyticsIntegration.php';

// Apply authentication middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->authenticate();

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Route the request
if ($pathParts[count($pathParts) - 1] === 'integrations') {
    // Base integrations endpoint
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'message' => 'Integration endpoints available',
            'endpoints' => [
                'hrcore' => '/api/integrations/hrcore',
                'payroll' => '/api/integrations/payroll',
                'finance' => '/api/integrations/finance',
                'analytics' => '/api/integrations/analytics',
                'reports' => '/api/integrations/reports',
                'hmo-payroll' => '/api/integrations/hmo-payroll',
                'hmo-analytics' => '/api/integrations/hmo-analytics'
            ]
        ]);
    }
} elseif (isset($pathParts[count($pathParts) - 2]) && $pathParts[count($pathParts) - 2] === 'integrations') {
    $integrationType = $pathParts[count($pathParts) - 1];
    
    switch ($integrationType) {
        case 'hrcore':
            handleHRCoreIntegration($method, $pathParts);
            break;
        case 'payroll':
            handlePayrollIntegration($method, $pathParts);
            break;
        case 'finance':
            handleFinanceIntegration($method, $pathParts);
            break;
        case 'analytics':
            handleAnalyticsIntegration($method, $pathParts);
            break;
        case 'reports':
            handleReportsIntegration($method, $pathParts);
            break;
        case 'hmo-payroll':
            handleHMOPayrollIntegration($method, $pathParts);
            break;
        case 'hmo-analytics':
            handleHMOAnalyticsIntegration($method, $pathParts);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Integration type not found']);
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Integration endpoint not found']);
}

/**
 * Handle HR Core Integration
 */
function handleHRCoreIntegration($method, $pathParts) {
    $hrCore = new HRCoreIntegration();
    
    if ($method === 'POST' && end($pathParts) === 'sync') {
        // Sync with HR Core
        try {
            $result = $hrCore->syncEmployeeData($_POST['employee_id'] ?? null);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET') {
        // Get HR Core data
        $filters = $_GET;
        $data = $hrCore->getEmployeesForCompensationPlanning($filters);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle Payroll Integration
 */
function handlePayrollIntegration($method, $pathParts) {
    $payroll = new PayrollIntegration();
    
    if ($method === 'POST' && end($pathParts) === 'update') {
        // Update payroll with changes
        $input = json_decode(file_get_contents('php://input'), true);
        $changes = $input['changes'] ?? [];
        $effectiveDate = $input['effective_date'] ?? date('Y-m-d');
        $reason = $input['reason'] ?? 'Compensation Planning Update';
        
        try {
            $result = $payroll->bulkUpdateSalaries($changes, $effectiveDate, $reason);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'POST' && end($pathParts) === 'impact') {
        // Calculate payroll impact
        $input = json_decode(file_get_contents('php://input'), true);
        $salaryChanges = $input['salary_changes'] ?? [];
        $payPeriodStart = $input['pay_period_start'] ?? date('Y-m-01');
        $payPeriodEnd = $input['pay_period_end'] ?? date('Y-m-t');
        
        try {
            $result = $payroll->calculatePayrollImpact($salaryChanges, $payPeriodStart, $payPeriodEnd);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET') {
        // Get payroll data
        $filters = $_GET;
        $data = $payroll->getCurrentPayrollData($filters['employee_ids'] ?? []);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle Finance Integration
 */
function handleFinanceIntegration($method, $pathParts) {
    $finance = new FinanceIntegration();
    
    if ($method === 'POST' && end($pathParts) === 'budget-impact') {
        // Send budget impact report
        $input = json_decode(file_get_contents('php://input'), true);
        $impactData = $input['impact_data'] ?? [];
        $reportType = $input['report_type'] ?? 'salary_adjustment';
        
        try {
            $result = $finance->sendBudgetImpactReport($impactData, $reportType);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'POST' && end($pathParts) === 'analyze-impact') {
        // Analyze budget impact
        $input = json_decode(file_get_contents('php://input'), true);
        $salaryChanges = $input['salary_changes'] ?? [];
        $fiscalYear = $input['fiscal_year'] ?? date('Y');
        
        try {
            $result = $finance->calculateBudgetImpact($salaryChanges, $fiscalYear);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET' && end($pathParts) === 'budget') {
        // Get budget information
        $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
        $data = $finance->getCompensationBudget($fiscalYear);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'GET' && end($pathParts) === 'summary') {
        // Get financial summary
        $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
        $data = $finance->getFinancialSummary($fiscalYear);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle Analytics Integration
 */
function handleAnalyticsIntegration($method, $pathParts) {
    $analytics = new AnalyticsIntegration();
    
    if ($method === 'GET' && end($pathParts) === 'compensation') {
        // Get compensation analytics
        $filters = $_GET;
        $data = $analytics->getCompensationAnalytics($filters);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'GET' && isset($_GET['type']) && $_GET['type'] === 'incentives') {
        // Get incentives analytics
        $filters = $_GET;
        $data = $analytics->getIncentivesAnalytics($filters);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'GET' && end($pathParts) === 'pay-equity') {
        // Get pay equity analysis
        $filters = $_GET;
        $data = $analytics->getPayEquityAnalysis($filters);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'GET' && end($pathParts) === 'report') {
        // Generate analytics report
        $reportType = $_GET['report_type'] ?? 'comprehensive';
        $filters = $_GET;
        unset($filters['report_type']);
        
        $data = $analytics->generateCompensationReport($reportType, $filters);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle Reports Integration
 */
function handleReportsIntegration($method, $pathParts) {
    $reports = new ReportingSystem();
    
    if ($method === 'POST' && end($pathParts) === 'generate') {
        // Generate comprehensive report
        $input = json_decode(file_get_contents('php://input'), true);
        $reportType = $input['report_type'] ?? 'comprehensive';
        $filters = $input['filters'] ?? [];
        $format = $input['format'] ?? 'json';
        
        try {
            $result = $reports->generateCompensationReport($reportType, $filters, $format);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET' && end($pathParts) === 'history') {
        // Get report history
        $limit = $_GET['limit'] ?? 50;
        $data = $reports->getReportHistory($limit);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'GET' && end($pathParts) === 'export') {
        // Export report
        $reportType = $_GET['report_type'] ?? 'comprehensive';
        $filters = $_GET;
        unset($filters['report_type']);
        $format = $_GET['format'] ?? 'json';
        
        try {
            $result = $reports->exportReport($reportType, $filters, $format);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle HMO-Payroll Integration
 */
function handleHMOPayrollIntegration($method, $pathParts) {
    $hmoPayroll = new HMOPayrollIntegration();
    
    if ($method === 'GET' && end($pathParts) === 'deductions') {
        // Get HMO deductions for payroll period
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        $payrollRunId = $_GET['payroll_run_id'] ?? null;
        
        $data = $hmoPayroll->getHMODeductionsForPayroll($payrollRunId, $month, $year);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    } elseif ($method === 'POST' && end($pathParts) === 'apply-deductions') {
        // Apply HMO deductions to payroll run
        $input = json_decode(file_get_contents('php://input'), true);
        $payrollRunId = $input['payroll_run_id'] ?? null;
        
        if (!$payrollRunId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payroll run ID required']);
            return;
        }
        
        try {
            $result = $hmoPayroll->applyHMODeductionsToPayroll($payrollRunId);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET' && end($pathParts) === 'pending-reimbursements') {
        // Get pending reimbursements
        $data = $hmoPayroll->getPendingReimbursements();
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    } elseif ($method === 'POST' && end($pathParts) === 'apply-reimbursements') {
        // Apply reimbursements to payroll
        $input = json_decode(file_get_contents('php://input'), true);
        $payrollRunId = $input['payroll_run_id'] ?? null;
        $reimbursementIds = $input['reimbursement_ids'] ?? null;
        
        if (!$payrollRunId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payroll run ID required']);
            return;
        }
        
        try {
            $result = $hmoPayroll->applyReimbursementsToPayroll($payrollRunId, $reimbursementIds);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET' && end($pathParts) === 'cost-summary') {
        // Get HMO cost summary
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;
        
        $data = $hmoPayroll->getHMOCostSummary($month, $year);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } elseif ($method === 'POST' && end($pathParts) === 'sync-compensation') {
        // Sync HMO with compensation
        $input = json_decode(file_get_contents('php://input'), true);
        $employeeId = $input['employee_id'] ?? null;
        
        if (!$employeeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            return;
        }
        
        try {
            $result = $hmoPayroll->syncWithCompensation($employeeId);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Compensation synced successfully' : 'Failed to sync compensation'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

/**
 * Handle HMO-Analytics Integration
 */
function handleHMOAnalyticsIntegration($method, $pathParts) {
    $hmoAnalytics = new HMOAnalyticsIntegration();
    
    if ($method === 'GET' && end($pathParts) === 'dashboard') {
        // Get healthcare dashboard data
        try {
            $data = $hmoAnalytics->getHealthcareDashboard();
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'GET' && end($pathParts) === 'report') {
        // Generate report
        $reportType = $_GET['report_type'] ?? 'monthly';
        $period = $_GET['period'] ?? date('Y-m');
        
        try {
            $data = $hmoAnalytics->generateReport($reportType, $period);
            echo json_encode([
                'success' => true,
                'data' => $data,
                'report_type' => $reportType,
                'period' => $period
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($method === 'POST' && end($pathParts) === 'export-finance') {
        // Export to Finance Analytics (HADS)
        $input = json_decode(file_get_contents('php://input'), true);
        $period = $input['period'] ?? date('Y-m');
        
        try {
            $data = $hmoAnalytics->exportToFinanceAnalytics($period);
            echo json_encode([
                'success' => true,
                'data' => $data,
                'message' => 'Data exported to Finance Analytics'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}
?>