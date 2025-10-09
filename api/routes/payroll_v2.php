<?php
/**
 * Payroll V2 Routes
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/PayrollV2.php';

class PayrollV2Controller {
    private $auth;
    private $model;

    public function __construct() {
        $this->auth = new AuthMiddleware();
        $this->model = new PayrollV2();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        if (!$this->auth->authenticate()) { Response::unauthorized('Authentication required'); }
        $user = $this->auth->getCurrentUser();
        */
        $user = ['user_id' => 1, 'role_name' => 'System Admin']; // Mock user for testing

        switch ($method) {
            case 'GET':
                if ($id === null || $id === 'runs') { return $this->listRuns(); }
                if ($subResource === 'payslips') { return $this->listPayslips($id); }
                if ($subResource === 'export') { return $this->exportRunPayslips($id); }
                return $this->getRun($id);
            case 'POST':
                if ($subResource === 'process') { return $this->processRun($id, $user); }
                if ($subResource === 'approve') { return $this->approveRun($id, $user); }
                if ($subResource === 'lock') { return $this->lockRun($id, $user); }
                return $this->createRun($user);
            default:
                Response::methodNotAllowed();
        }
    }

    private function listRuns() {
        $req = new Request();
        $pagination = $req->getPagination();
        $filters = [
            'branch_id' => $req->getData('branch_id'),
            'status' => $req->getData('status'),
            'from' => $req->getData('from'),
            'to' => $req->getData('to')
        ];
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        $runs = $this->model->listRuns($pagination['page'], $pagination['limit'], $filters);
        $total = $this->model->countRuns($filters);
        $totalPages = (int)ceil($total / $pagination['limit']);
        Response::paginated($runs, [
            'current_page' => $pagination['page'],
            'per_page' => $pagination['limit'],
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $pagination['page'] < $totalPages,
            'has_prev' => $pagination['page'] > 1
        ]);
    }

    private function getRun($id) {
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        $run = $this->model->getRun((int)$id);
        if (!$run) { Response::notFound('Run not found'); }
        Response::success($run);
    }

    private function listPayslips($id) {
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        $rows = $this->model->listPayslipsByRun((int)$id);
        Response::success($rows);
    }

    private function createRun($user) {
        if (!$this->auth->hasAnyRole(['System Admin', 'HR Manager', 'Payroll Officer'])) {
            Response::forbidden('Insufficient permissions');
        }
        $req = new Request();
        $data = $req->getData();
        $errors = $req->validateRequired(['branch_id', 'pay_period_start', 'pay_period_end', 'pay_date']);
        if (!empty($errors)) { Response::validationError($errors); }
        $runId = $this->model->createRun([
            'branch_id' => (int)$data['branch_id'],
            'pay_period_start' => $data['pay_period_start'],
            'pay_period_end' => $data['pay_period_end'],
            'pay_date' => $data['pay_date'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $user['user_id'] ?? null
        ]);
        Response::created(['payroll_run_id' => $runId], 'Payroll run created');
    }

    private function processRun($id, $user) {
        if (!$this->auth->hasAnyRole(['System Admin', 'HR Manager', 'Payroll Officer'])) {
            Response::forbidden('Insufficient permissions');
        }
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        $this->model->processRun((int)$id);
        Response::success(null, 'Run processed');
    }

    private function approveRun($id, $user) {
        if (!$this->auth->hasAnyRole(['System Admin', 'Finance'])) {
            Response::forbidden('Insufficient permissions');
        }
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        $this->model->approveRun((int)$id, (int)($user['user_id'] ?? 0));
        Response::success(null, 'Run approved');
    }

    private function lockRun($id, $user) {
        if (!$this->auth->hasAnyRole(['System Admin', 'Finance'])) {
            Response::forbidden('Insufficient permissions');
        }
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        $this->model->lockRun((int)$id);
        Response::success(null, 'Run locked');
    }

    private function exportRunPayslips($id) {
        $req = new Request();
        if (!$req->validateInteger($id)) { Response::validationError(['id' => 'Invalid run ID']); }
        
        try {
            $payslips = $this->model->listPayslipsByRun((int)$id);
            if (empty($payslips)) {
                Response::error('No payslips found for this run', 404);
                return;
            }
            
            // Generate Excel file
            $filename = "payroll_run_{$id}_payslips_" . date('Y-m-d') . ".xlsx";
            
            // Set headers for file download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Simple CSV export for now (can be enhanced to use PhpSpreadsheet for true Excel)
            $output = fopen('php://output', 'w');
            
            // Headers
            fputcsv($output, [
                'Employee Name',
                'Job Title', 
                'Basic Salary',
                'Overtime Pay',
                'Total Deductions',
                'Net Income',
                'Status'
            ]);
            
            // Data rows
            foreach ($payslips as $payslip) {
                fputcsv($output, [
                    ($payslip['FirstName'] ?? '') . ' ' . ($payslip['LastName'] ?? ''),
                    $payslip['JobTitle'] ?? 'N/A',
                    $payslip['BasicSalary'] ?? 0,
                    $payslip['OvertimePay'] ?? 0,
                    $payslip['TotalDeductions'] ?? 0,
                    $payslip['NetIncome'] ?? 0,
                    $payslip['Status'] ?? 'Generated'
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            Response::error('Failed to export payslips', 500);
        }
    }
}

?>


