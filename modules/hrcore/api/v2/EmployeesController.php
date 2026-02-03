<?php
/**
 * Employees API Controller - Version 2
 * 
 * Example of refactored API endpoint with:
 * - Versioning
 * - DTOs
 * - Service interfaces
 * - Event publishing
 * - Error handling
 */

// Note: Using global Response/Request classes for compatibility
// In production, consider using namespaced versions

class EmployeesController {
    private $hrCoreService;
    
    public function __construct(HRCoreServiceInterface $hrCoreService) {
        $this->hrCoreService = $hrCoreService;
    }
    
    /**
     * List employees
     * GET /api/v2/hrcore/employees
     */
    public function list($request) {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $filters = [
                'department_id' => $_GET['department_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Remove null filters
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });
            
            $result = $this->hrCoreService->getEmployees($page, $limit, $filters);
            
            Response::success([
                'data' => array_map(function($dto) {
                    return $dto->toArray();
                }, $result['data']),
                'pagination' => [
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $result['limit'])
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error in EmployeesController::list: " . $e->getMessage());
            Response::error('Failed to retrieve employees', 500);
        }
    }
    
    /**
     * Get employee by ID
     * GET /api/v2/hrcore/employees/{id}
     */
    public function get(int $id) {
        try {
            $employee = $this->hrCoreService->getEmployee($id);
            
            if ($employee === null) {
                Response::notFound('Employee not found');
                return;
            }
            
            Response::success($employee->toArray());
        } catch (\Exception $e) {
            error_log("Error in EmployeesController::get: " . $e->getMessage());
            Response::error('Failed to retrieve employee', 500);
        }
    }
    
    /**
     * Create employee
     * POST /api/v2/hrcore/employees
     */
    public function create() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                Response::error('Invalid request body', 400);
                return;
            }
            
            // Create DTO from request
            $dto = CreateEmployeeDTO::fromRequest($data);
            
            // Validate DTO
            $errors = $dto->validate();
            if (!empty($errors)) {
                Response::error('Validation failed', 400, ['errors' => $errors]);
                return;
            }
            
            // Create employee
            $employee = $this->hrCoreService->createEmployee($dto);
            
            // Publish event
            require_once __DIR__ . '/../../../../common/services/EventBus.php';
            \Common\Services\EventBus::publish('employee.created', [
                'employee_id' => $employee->id,
                'department_id' => $employee->departmentId,
                'hire_date' => $employee->hireDate
            ]);
            
            Response::success($employee->toArray(), 'Employee created successfully', 201);
        } catch (\Exception $e) {
            error_log("Error in EmployeesController::create: " . $e->getMessage());
            Response::error('Failed to create employee', 500);
        }
    }
    
    /**
     * Update employee
     * PUT /api/v2/hrcore/employees/{id}
     */
    public function update(int $id) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                Response::error('Invalid request body', 400);
                return;
            }
            
            $employee = $this->hrCoreService->updateEmployee($id, $data);
            
            // Publish event
            require_once __DIR__ . '/../../../../common/services/EventBus.php';
            \Common\Services\EventBus::publish('employee.updated', [
                'employee_id' => $id,
                'changes' => $data
            ]);
            
            Response::success($employee->toArray());
        } catch (\Exception $e) {
            error_log("Error in EmployeesController::update: " . $e->getMessage());
            Response::error('Failed to update employee', 500);
        }
    }
    
    /**
     * Delete employee
     * DELETE /api/v2/hrcore/employees/{id}
     */
    public function delete(int $id) {
        try {
            $success = $this->hrCoreService->deleteEmployee($id);
            
            if (!$success) {
                Response::notFound('Employee not found');
                return;
            }
            
            // Publish event
            require_once __DIR__ . '/../../../../common/services/EventBus.php';
            \Common\Services\EventBus::publish('employee.deleted', [
                'employee_id' => $id
            ]);
            
            Response::success(['message' => 'Employee deleted successfully']);
        } catch (\Exception $e) {
            error_log("Error in EmployeesController::delete: " . $e->getMessage());
            Response::error('Failed to delete employee', 500);
        }
    }
}
