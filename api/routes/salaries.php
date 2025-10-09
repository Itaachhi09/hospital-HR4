<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Salaries.php';
require_once __DIR__ . '/../utils/Response.php';

class SalariesController {
    private $auth;
    private $salariesModel;

    public function __construct() {
        $this->auth = new AuthMiddleware();
        $this->salariesModel = new Salaries();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed
        /*
        // Authentication required for all salary endpoints
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
            return;
        }

        // Check if user has payroll access
        $currentUser = $this->auth->getCurrentUser();
        if (!in_array($currentUser['role_name'] ?? '', ['System Admin', 'HR Manager', 'HR Staff', 'Payroll Officer'])) {
            Response::forbidden('Insufficient permissions for salary access');
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
                Response::success('OK', ['methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']]);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function handleGet($id, $subResource) {
        if ($id === null) {
            // Get all salaries with filters
            $filters = [
                'branch_id' => $_GET['branch_id'] ?? null,
                'department_id' => $_GET['department_id'] ?? null,
                'position_id' => $_GET['position_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];
            
            $result = $this->salariesModel->getAllSalaries($filters);
            Response::success('Salaries retrieved successfully', $result);
            
        } elseif ($subResource === 'summary') {
            // Get salary summary for specific employee
            $result = $this->salariesModel->getEmployeeSalarySummary($id);
            if (!$result) {
                Response::notFound('Employee salary information not found');
                return;
            }
            Response::success('Employee salary summary retrieved successfully', $result);
            
        } elseif ($subResource === 'comparison') {
            // Get salary comparison data
            $filters = [
                'branch_id' => $_GET['branch_id'] ?? null,
                'department_id' => $_GET['department_id'] ?? null,
                'position_id' => $_GET['position_id'] ?? null
            ];
            $result = $this->salariesModel->getSalaryComparison($filters);
            Response::success('Salary comparison data retrieved successfully', $result);
            
        } elseif ($subResource === 'deductions') {
            // Get deductions overview for employee
            $result = $this->salariesModel->getEmployeeDeductions($id);
            if (!$result) {
                Response::notFound('Employee deductions not found');
                return;
            }
            Response::success('Employee deductions retrieved successfully', $result);
            
        } else {
            // Get specific employee salary details
            $result = $this->salariesModel->getEmployeeSalary($id);
            if (!$result) {
                Response::notFound('Employee salary not found');
                return;
            }
            Response::success('Employee salary retrieved successfully', $result);
        }
    }

    private function handlePost($id, $subResource) {
        // Salaries are read-only from HR modules, no manual creation
        Response::forbidden('Salary creation not allowed - data comes from HR1/HR2 modules');
    }

    private function handlePut($id, $subResource) {
        // Salaries are read-only from HR modules, no manual updates
        Response::forbidden('Salary updates not allowed - data comes from HR1/HR2 modules');
    }

    private function handleDelete($id, $subResource) {
        // Salaries are read-only from HR modules, no manual deletion
        Response::forbidden('Salary deletion not allowed - data comes from HR1/HR2 modules');
    }
}
