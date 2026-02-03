# Example Refactored Endpoints

This document shows examples of refactored endpoints, comparing v1 (old) and v2 (new) implementations.

---

## Example 1: Get Employees

### v1 Implementation (Old - Direct Database Access)

```php
// modules/employees/api/get_employees.php
<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$sql = "SELECT 
            e.EmployeeID, 
            e.FirstName, 
            e.LastName,
            e.Email,
            d.DepartmentName
        FROM Employees e
        LEFT JOIN Departments d ON e.DepartmentID = d.DepartmentID
        ORDER BY e.LastName";

$stmt = $pdo->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($employees);
```

**Problems:**
- Direct database access
- No validation
- No error handling
- No versioning
- No DTOs
- Exposes database structure

### v2 Implementation (New - Modular Architecture)

```php
// modules/hrcore/api/v2/EmployeesController.php
<?php
namespace Modules\HRCore\API\V2;

use Common\Utils\Response;
use Common\Utils\Request;
use Modules\HRCore\Services\Interfaces\HRCoreServiceInterface;

class EmployeesController {
    private $hrCoreService;
    
    public function __construct(HRCoreServiceInterface $hrCoreService) {
        $this->hrCoreService = $hrCoreService;
    }
    
    public function list(Request $request): Response {
        try {
            $page = (int)($request->getQuery('page', 1));
            $limit = (int)($request->getQuery('limit', 20));
            $filters = [
                'department_id' => $request->getQuery('department_id'),
                'status' => $request->getQuery('status')
            ];
            
            $filters = array_filter($filters, fn($v) => $v !== null);
            
            $result = $this->hrCoreService->getEmployees($page, $limit, $filters);
            
            return Response::success([
                'data' => array_map(fn($dto) => $dto->toArray(), $result['data']),
                'pagination' => [
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total' => $result['total']
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error: " . $e->getMessage());
            return Response::error('Failed to retrieve employees', 500);
        }
    }
}
```

**Improvements:**
- ✅ Uses service interface (loose coupling)
- ✅ Uses DTOs (type safety)
- ✅ Proper error handling
- ✅ Versioned API (`/api/v2/`)
- ✅ Pagination support
- ✅ Filtering support
- ✅ Standardized response format

---

## Example 2: Create Employee

### v1 Implementation (Old)

```php
// modules/employees/api/add_employee.php
<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$sql = "INSERT INTO Employees (FirstName, LastName, Email, DepartmentID) 
        VALUES (:first_name, :last_name, :email, :department_id)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':first_name' => $data['first_name'],
    ':last_name' => $data['last_name'],
    ':email' => $data['email'],
    ':department_id' => $data['department_id']
]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
```

**Problems:**
- No validation
- No error handling
- No event publishing
- Direct database access
- No DTOs

### v2 Implementation (New)

```php
// modules/hrcore/api/v2/EmployeesController.php
public function create(Request $request): Response {
    try {
        // Create DTO from request
        $dto = CreateEmployeeDTO::fromRequest($request->getBody());
        
        // Validate
        $errors = $dto->validate();
        if (!empty($errors)) {
            return Response::error('Validation failed', 400, ['errors' => $errors]);
        }
        
        // Create employee via service
        $employee = $this->hrCoreService->createEmployee($dto);
        
        // Publish event
        EventBus::publish('employee.created', [
            'employee_id' => $employee->id,
            'department_id' => $employee->departmentId,
            'hire_date' => $employee->hireDate
        ]);
        
        return Response::created($employee->toArray());
    } catch (\Exception $e) {
        error_log("Error: " . $e->getMessage());
        return Response::error('Failed to create employee', 500);
    }
}
```

**Improvements:**
- ✅ DTO validation
- ✅ Service layer (business logic separation)
- ✅ Event publishing (triggers other modules)
- ✅ Proper error handling
- ✅ Standardized response

---

## Example 3: Process Payroll (Cross-Module)

### v1 Implementation (Old - Direct Database Join)

```php
// modules/payroll/api/process_payroll.php
<?php
require_once '../db_connect.php';

// Direct cross-module database access
$sql = "SELECT 
            e.EmployeeID,
            e.FirstName,
            e.LastName,
            s.Salary,
            s.EffectiveDate
        FROM Employees e
        JOIN Salaries s ON e.EmployeeID = s.EmployeeID
        WHERE e.Status = 'active'";

$stmt = $pdo->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as $emp) {
    // Process payroll...
}
```

**Problems:**
- Direct cross-module database access
- Tight coupling
- No error isolation
- If Employees table changes, Payroll breaks

### v2 Implementation (New - Service Interface)

```php
// modules/payroll/services/PayrollService.php
<?php
namespace Modules\Payroll\Services;

use Modules\HRCore\Services\Interfaces\HRCoreServiceInterface;
use Common\Services\CircuitBreaker;
use Common\Services\RetryService;

class PayrollService {
    private $hrCoreService;
    private $circuitBreaker;
    private $retryService;
    
    public function __construct(HRCoreServiceInterface $hrCoreService) {
        $this->hrCoreService = $hrCoreService;
        $this->circuitBreaker = new CircuitBreaker('hrcore-api');
        $this->retryService = new RetryService();
    }
    
    public function processPayroll(int $payrollRunId): void {
        // Get active employees via HR Core API
        $employees = $this->hrCoreService->getEmployees(
            page: 1,
            limit: 1000,
            filters: ['status' => 'active']
        );
        
        foreach ($employees['data'] as $employeeDTO) {
            // Process with circuit breaker protection
            try {
                $payslip = $this->calculatePayslip($employeeDTO);
                $this->savePayslip($payslip);
            } catch (\Exception $e) {
                // Log but continue with other employees
                error_log("Error processing payroll for employee {$employeeDTO->id}: " . $e->getMessage());
            }
        }
        
        // Publish event
        EventBus::publish('payroll.processed', [
            'payroll_run_id' => $payrollRunId,
            'employee_count' => count($employees['data'])
        ]);
    }
    
    private function calculatePayslip(EmployeeDTO $employee): array {
        // Get employee data with fallback
        $emp = $this->circuitBreaker->call(
            fn() => $this->retryService->execute(
                fn() => $this->hrCoreService->getEmployee($employee->id)
            ),
            fn() => $this->getCachedEmployee($employee->id) // Fallback
        );
        
        // Calculate payslip...
        return [];
    }
}
```

**Improvements:**
- ✅ Uses HR Core service interface (loose coupling)
- ✅ Circuit breaker prevents cascade failures
- ✅ Retry mechanism for resilience
- ✅ Fallback logic
- ✅ Error isolation (one employee failure doesn't stop others)
- ✅ Event publishing

---

## Example 4: Event Subscription

### v1 Implementation (Old - No Events)

```php
// When employee is created, payroll must be initialized manually
// No automatic triggering
```

### v2 Implementation (New - Event-Driven)

```php
// modules/payroll/services/PayrollService.php

// Subscribe to employee.created event
EventBus::subscribe('employee.created', function($eventData) {
    $payrollService = new PayrollService($hrCoreService);
    $payrollService->initializeEmployee($eventData);
});

// In PayrollService:
public function initializeEmployee(array $eventData): void {
    $employeeId = $eventData['employee_id'];
    
    // Initialize payroll records
    $this->createDefaultSalary($employeeId);
    $this->createDefaultDeductions($employeeId);
    
    // Publish event
    EventBus::publish('payroll.employee.initialized', [
        'employee_id' => $employeeId
    ]);
}
```

**Improvements:**
- ✅ Automatic initialization
- ✅ Loose coupling (Payroll doesn't know about HR Core internals)
- ✅ Scalable (can add more subscribers)
- ✅ Asynchronous (doesn't block employee creation)

---

## Example 5: API Response Format

### v1 Implementation (Old - Inconsistent)

```php
// Sometimes returns array directly
echo json_encode($employees);

// Sometimes returns object
echo json_encode(['data' => $employees]);

// Sometimes includes success flag
echo json_encode(['success' => true, 'data' => $employees]);
```

### v2 Implementation (New - Standardized)

```php
// Always uses Response class
return Response::success([
    'data' => $employees,
    'pagination' => [...]
]);

// Error responses
return Response::error('Validation failed', 400, ['errors' => $errors]);

// Created responses
return Response::created($employee->toArray());
```

**Response Format:**
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 100
    }
}
```

**Improvements:**
- ✅ Consistent response format
- ✅ Proper HTTP status codes
- ✅ Error messages included
- ✅ Easy to parse on frontend

---

## Migration Example

### Migrating from v1 to v2

**Step 1: Update API URL**
```javascript
// Old
fetch('/api/employees/get_employees.php')

// New
fetch('/api/v2/hrcore/employees')
```

**Step 2: Update Request Format**
```javascript
// Old
fetch('/api/employees/add_employee.php', {
    method: 'POST',
    body: JSON.stringify({
        first_name: 'John',
        last_name: 'Doe'
    })
})

// New
fetch('/api/v2/hrcore/employees', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        first_name: 'John',
        last_name: 'Doe',
        email: 'john.doe@example.com',
        department_id: 5
    })
})
```

**Step 3: Update Response Handling**
```javascript
// Old
const employees = await response.json();

// New
const result = await response.json();
if (result.success) {
    const employees = result.data;
    const pagination = result.pagination;
} else {
    // Handle error
    console.error(result.message);
}
```

---

## Summary

### Key Improvements

1. **Modularity**: Modules are independent
2. **Loose Coupling**: Use interfaces, not implementations
3. **Resilience**: Circuit breakers and retries
4. **Event-Driven**: Asynchronous communication
5. **Type Safety**: DTOs instead of arrays
6. **Versioning**: Clear API versions
7. **Standardization**: Consistent responses
8. **Error Isolation**: Failures don't cascade

### Migration Benefits

- ✅ Easier to maintain
- ✅ Easier to test
- ✅ Easier to scale
- ✅ More resilient
- ✅ Better performance (with caching)
- ✅ Future-proof (can evolve to microservices)
