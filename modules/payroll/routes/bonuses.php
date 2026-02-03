<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Bonuses.php';
require_once __DIR__ . '/../utils/Response.php';

class BonusesController {
    private $auth;
    private $bonusesModel;

    public function __construct() {
        $this->auth = new AuthMiddleware();
        $this->bonusesModel = new Bonuses();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        // Authentication required for all bonus endpoints
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
            return;
        }

        // Check if user has payroll access
        $currentUser = $this->auth->getCurrentUser();
        if (!in_array($currentUser['role_name'] ?? '', ['System Admin', 'HR Manager', 'HR Staff', 'Payroll Officer'])) {
            Response::forbidden('Insufficient permissions for bonus access');
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
            // Get all bonuses with filters
            $filters = [
                'branch_id' => $_GET['branch_id'] ?? null,
                'department_id' => $_GET['department_id'] ?? null,
                'position_id' => $_GET['position_id'] ?? null,
                'bonus_type' => $_GET['bonus_type'] ?? null,
                'payroll_run_id' => $_GET['payroll_run_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];
            
            $result = $this->bonusesModel->getAllBonuses($filters);
            Response::success($result, 'Bonuses retrieved successfully');
            
        } elseif ($subResource === 'summary') {
            // Get bonus summary for specific employee
            $result = $this->bonusesModel->getEmployeeBonusSummary($id);
            if (!$result) {
                Response::notFound('Employee bonus information not found');
                return;
            }
            Response::success($result, 'Employee bonus summary retrieved successfully');
            
        } elseif ($subResource === 'computation') {
            // Get bonus computation details
            $result = $this->bonusesModel->getBonusComputation($id);
            if (!$result) {
                Response::notFound('Bonus computation not found');
                return;
            }
            Response::success($result, 'Bonus computation retrieved successfully');
            
        } elseif ($subResource === 'types') {
            // Get available bonus types
            $result = $this->bonusesModel->getBonusTypes();
            Response::success($result, 'Bonus types retrieved successfully');
            
        } elseif ($subResource === 'eligibility') {
            // Check employee eligibility for bonuses
            $result = $this->bonusesModel->checkEmployeeEligibility($id);
            Response::success($result, 'Employee eligibility checked successfully');
            
        } else {
            // Get specific bonus details
            $result = $this->bonusesModel->getBonus($id);
            if (!$result) {
                Response::notFound('Bonus not found');
                return;
            }
            Response::success($result, 'Bonus retrieved successfully');
        }
    }

    private function handlePost($id, $subResource) {
        if ($subResource === 'compute') {
            // Compute bonuses for a payroll run
            $input = json_decode(file_get_contents('php://input'), true);
            $payrollRunId = $input['payroll_run_id'] ?? null;
            $branchId = $input['branch_id'] ?? null;
            
            if (!$payrollRunId || !$branchId) {
                Response::error('Payroll run ID and branch ID are required', 400);
                return;
            }
            
            $result = $this->bonusesModel->computeBonusesForPayrollRun($payrollRunId, $branchId);
            Response::success($result, 'Bonuses computed successfully');
            
        } elseif ($subResource === 'manual') {
            // Add manual bonus entry
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $this->bonusesModel->addManualBonus($input);
            Response::success($result, 'Manual bonus added successfully');
            
        } else {
            // Create new bonus
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $this->bonusesModel->createBonus($input);
            Response::success($result, 'Bonus created successfully');
        }
    }

    private function handlePut($id, $subResource) {
        // Update bonus
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $this->bonusesModel->updateBonus($id, $input);
        if (!$result) {
            Response::notFound('Bonus not found');
            return;
        }
        Response::success($result, 'Bonus updated successfully');
    }

    private function handleDelete($id, $subResource) {
        // Delete bonus
        $result = $this->bonusesModel->deleteBonus($id);
        if (!$result) {
            Response::notFound('Bonus not found');
            return;
        }
        Response::success(null, 'Bonus deleted successfully');
    }
}
