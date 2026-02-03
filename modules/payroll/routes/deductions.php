<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Deductions.php';
require_once __DIR__ . '/../utils/Response.php';

class DeductionsController {
    private $auth;
    private $deductionsModel;

    public function __construct() {
        $this->auth = new AuthMiddleware();
        $this->deductionsModel = new Deductions();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        // Authentication required for all deduction endpoints
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
            return;
        }

        // Check if user has payroll access
        $currentUser = $this->auth->getCurrentUser();
        if (!in_array($currentUser['role_name'] ?? '', ['System Admin', 'HR Manager', 'HR Staff', 'Payroll Officer'])) {
            Response::forbidden('Insufficient permissions for deduction access');
            return;
        }
        */

        switch ($method) {
            case 'GET':
                $this->handleGet($id, $subResource);
                break;
            case 'POST':
                $this->handlePost($id, $subResource);
                break;
            case 'PUT':
            case 'PATCH':
                $this->handlePut($id, $subResource);
                break;
            case 'DELETE':
                $this->handleDelete($id, $subResource);
                break;
            case 'OPTIONS':
                Response::success(['methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']], 'OK');
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function handleGet($id, $subResource) {
        if ($id === null) {
            // Get all deductions with filters
            $filters = [
                'branch_id' => $_GET['branch_id'] ?? null,
                'department_id' => $_GET['department_id'] ?? null,
                'deduction_type' => $_GET['deduction_type'] ?? null,
                'payroll_run_id' => $_GET['payroll_run_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];
            
            $result = $this->deductionsModel->getAllDeductions($filters);
            Response::success($result, 'Deductions retrieved successfully');
            
        } elseif ($subResource === 'summary') {
            // Get deduction summary for specific employee
            $result = $this->deductionsModel->getEmployeeDeductionSummary($id);
            if (!$result) {
                Response::notFound('Employee deduction information not found');
                return;
            }
            Response::success($result, 'Employee deduction summary retrieved successfully');
            
        } elseif ($subResource === 'statutory') {
            // Get statutory deductions for employee
            $result = $this->deductionsModel->getEmployeeStatutoryDeductions($id);
            Response::success($result, 'Employee statutory deductions retrieved successfully');
            
        } elseif ($subResource === 'voluntary') {
            // Get voluntary deductions for employee
            $result = $this->deductionsModel->getEmployeeVoluntaryDeductions($id);
            Response::success($result, 'Employee voluntary deductions retrieved successfully');
            
        } elseif ($subResource === 'config') {
            // Get deduction configuration
            $result = $this->deductionsModel->getDeductionConfiguration();
            Response::success($result, 'Deduction configuration retrieved successfully');
            
        } elseif ($id === 'types') {
            // Get available deduction types
            $result = $this->deductionsModel->getDeductionTypes();
            Response::success($result, 'Deduction types retrieved successfully');
            
        } elseif ($subResource === 'types') {
            // Get available deduction types
            $result = $this->deductionsModel->getDeductionTypes();
            Response::success($result, 'Deduction types retrieved successfully');
            
        } else {
            // Get specific deduction details
            $result = $this->deductionsModel->getDeduction($id);
            if (!$result) {
                Response::notFound('Deduction not found');
                return;
            }
            Response::success($result, 'Deduction retrieved successfully');
        }
    }

    private function handlePost($id, $subResource) {
        if ($subResource === 'compute') {
            // Compute deductions for a payroll run
            $input = json_decode(file_get_contents('php://input'), true);
            $payrollRunId = $input['payroll_run_id'] ?? null;
            $branchId = $input['branch_id'] ?? null;
            
            if (!$payrollRunId || !$branchId) {
                Response::error('Payroll run ID and branch ID are required', 400);
                return;
            }
            
            $result = $this->deductionsModel->computeDeductionsForPayrollRun($payrollRunId, $branchId);
            Response::success($result, 'Deductions computed successfully');
            
        } elseif ($subResource === 'voluntary') {
            // Add voluntary deduction entry
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $this->deductionsModel->addVoluntaryDeduction($input);
            Response::success($result, 'Voluntary deduction added successfully');
            
        } else {
            // Create new deduction
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $this->deductionsModel->createDeduction($input);
            Response::success($result, 'Deduction created successfully');
        }
    }

    private function handlePut($id, $subResource) {
        // Update deduction
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $this->deductionsModel->updateDeduction($id, $input);
        if (!$result) {
            Response::notFound('Deduction not found');
            return;
        }
        Response::success($result, 'Deduction updated successfully');
    }

    private function handleDelete($id, $subResource) {
        // Delete deduction
        $result = $this->deductionsModel->deleteDeduction($id);
        if (!$result) {
            Response::notFound('Deduction not found');
            return;
        }
        Response::success(null, 'Deduction deleted successfully');
    }
}
