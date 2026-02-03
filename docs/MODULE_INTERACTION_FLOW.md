# Module Interaction Flow Diagrams

## Overview

This document illustrates how modules interact in the redesigned architecture.

---

## 1. Employee Creation Flow

### Scenario: Creating a new employee triggers actions in multiple modules

```
┌─────────────┐
│   Client    │
│  (Frontend) │
└──────┬──────┘
       │
       │ POST /api/v2/hrcore/employees
       │ { "first_name": "John", ... }
       │
       ▼
┌─────────────────────────────────────┐
│         API Gateway                 │
│  - Authenticate                     │
│  - Authorize (HR Admin)             │
│  - Rate Limit                       │
└──────┬──────────────────────────────┘
       │
       │ Route to v2
       │
       ▼
┌─────────────────────────────────────┐
│      HR Core Module                 │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ EmployeesController (v2)     │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ EmployeeService              │  │
│  │ - Validate DTO                │  │
│  │ - Create employee            │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ EmployeeRepository           │  │
│  │ - Insert into hrcore_*        │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Return EmployeeDTO   │
│              │                      │
└──────────────┼──────────────────────┘
               │
               │ Publish Event
               │
               ▼
┌─────────────────────────────────────┐
│         Event Bus                   │
│                                     │
│  Event: employee.created            │
│  {                                  │
│    "employee_id": 123,              │
│    "department_id": 5,               │
│    "hire_date": "2025-01-15"        │
│  }                                  │
└──────┬──────────────────────────────┘
       │
       ├─────────────────┬─────────────────┐
       │                 │                 │
       ▼                 ▼                 ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Payroll   │  │     HMO     │  │  Analytics  │
│   Module    │  │   Module    │  │   Module    │
│             │  │             │  │             │
│ Subscribe:  │  │ Subscribe:  │  │ Subscribe:  │
│ employee.   │  │ employee.   │  │ employee.   │
│ created     │  │ created     │  │ created     │
│             │  │             │  │             │
│ Action:     │  │ Action:     │  │ Action:     │
│ - Initialize│  │ - Check     │  │ - Update    │
│   payroll   │  │   eligibility│  │   metrics   │
│   records   │  │ - Send      │  │             │
│ - Set up    │  │   welcome   │  │             │
│   default   │  │   email     │  │             │
│   salary    │  │             │  │             │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Code Flow

```php
// 1. Client Request
POST /api/v2/hrcore/employees
{
    "first_name": "John",
    "last_name": "Doe",
    "department_id": 5
}

// 2. API Gateway
// - Validates JWT token
// - Checks RBAC permissions
// - Routes to HR Core v2

// 3. HR Core Controller
class EmployeesController {
    public function create(Request $request): Response {
        $dto = CreateEmployeeDTO::fromRequest($request);
        $employee = $this->employeeService->createEmployee($dto);
        
        // Publish event
        EventBus::publish('employee.created', [
            'employee_id' => $employee->id,
            'department_id' => $employee->departmentId,
            'hire_date' => $employee->hireDate
        ]);
        
        return Response::created($employee->toArray());
    }
}

// 4. Payroll Module Subscriber
EventBus::subscribe('employee.created', function($event) {
    $payrollService = new PayrollService();
    $payrollService->initializeEmployee($event['employee_id']);
});

// 5. HMO Module Subscriber
EventBus::subscribe('employee.created', function($event) {
    $hmoService = new HMOService();
    $hmoService->checkEligibility($event['employee_id']);
});
```

---

## 2. Payroll Processing Flow

### Scenario: Processing payroll requires employee data from HR Core

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       │ POST /api/v2/payroll/runs
       │
       ▼
┌─────────────────────────────────────┐
│         API Gateway                 │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│      Payroll Module                  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ PayrollController (v2)       │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ PayrollService               │  │
│  │                              │  │
│  │ For each employee:           │  │
│  │   1. Get employee data       │  │
│  │      (via HR Core API)       │  │
│  │   2. Calculate salary        │  │
│  │   3. Apply deductions        │  │
│  │   4. Generate payslip        │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Call HR Core API     │
│              │ (via Service         │
│              │  Interface)          │
│              │                      │
└──────────────┼──────────────────────┘
               │
               │ GET /api/v2/hrcore/employees/{id}
               │ (Internal API call with circuit breaker)
               │
               ▼
┌─────────────────────────────────────┐
│      HR Core Module                  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ EmployeesController (v2)      │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ EmployeeService               │  │
│  │ - Get employee                │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ EmployeeRepository            │  │
│  │ - Query hrcore_employees      │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Return EmployeeDTO   │
│              │                      │
└──────────────┼──────────────────────┘
               │
               │ EmployeeDTO
               │
               ▼
┌─────────────────────────────────────┐
│      Payroll Module                  │
│  (continues processing)              │
│                                     │
│  - Calculate gross pay              │
│  - Apply deductions                 │
│  - Generate payslip                 │
│  - Save to payroll_* tables         │
│                                     │
│  Publish: payroll.processed         │
└─────────────────────────────────────┘
```

### Code Flow

```php
// Payroll Service Implementation
class PayrollService {
    private $hrCoreService;
    private $circuitBreaker;
    
    public function processPayroll(int $payrollRunId) {
        $employees = $this->getActiveEmployees();
        
        foreach ($employees as $employeeId) {
            // Use circuit breaker for resilience
            $employee = $this->circuitBreaker->call(function() use ($employeeId) {
                return $this->hrCoreService->getEmployee($employeeId);
            });
            
            // Process payroll for employee
            $payslip = $this->calculatePayslip($employee);
            $this->savePayslip($payslip);
        }
        
        // Publish event
        EventBus::publish('payroll.processed', [
            'payroll_run_id' => $payrollRunId,
            'employee_count' => count($employees)
        ]);
    }
}
```

---

## 3. Error Isolation Flow

### Scenario: HR Core module fails, but Payroll continues with fallback

```
┌─────────────────────────────────────┐
│      Payroll Module                  │
│                                     │
│  Attempt: Get employee data         │
│  ┌──────────────────────────────┐  │
│  │ Circuit Breaker              │  │
│  │ State: CLOSED                │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Call HR Core API     │
│              │                      │
└──────────────┼──────────────────────┘
               │
               │ Request
               │
               ▼
┌─────────────────────────────────────┐
│      HR Core Module                  │
│                                     │
│  ❌ ERROR: Database timeout         │
│                                     │
│  Returns: 503 Service Unavailable   │
└──────────────┬──────────────────────┘
               │
               │ Error Response
               │
               ▼
┌─────────────────────────────────────┐
│      Payroll Module                  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ Circuit Breaker              │  │
│  │ - Increment failure count    │  │
│  │ - State: OPEN (if threshold) │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ Fallback Logic                │  │
│  │ - Use cached employee data    │  │
│  │ - Use default values          │  │
│  │ - Log warning                 │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Continue processing  │
│              │ (with fallback data) │
│              │                      │
│  ✅ Payroll processing continues    │
│     (graceful degradation)          │
└─────────────────────────────────────┘
```

### Code Flow

```php
class PayrollService {
    public function processPayroll(int $payrollRunId) {
        $employees = $this->getActiveEmployees();
        
        foreach ($employees as $employeeId) {
            try {
                $employee = $this->circuitBreaker->call(function() use ($employeeId) {
                    return $this->hrCoreService->getEmployee($employeeId);
                });
            } catch (CircuitBreakerOpenException $e) {
                // Circuit breaker is open, use fallback
                $employee = $this->getCachedEmployee($employeeId);
                
                if (!$employee) {
                    // Use default values
                    $employee = $this->getDefaultEmployeeData($employeeId);
                }
                
                // Log warning
                Logger::warning("Using fallback data for employee {$employeeId}");
            }
            
            // Continue processing with employee data (real or fallback)
            $payslip = $this->calculatePayslip($employee);
            $this->savePayslip($payslip);
        }
    }
}
```

---

## 4. Analytics Data Aggregation Flow

### Scenario: Analytics module aggregates data from multiple modules

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       │ GET /api/v2/analytics/hr-metrics
       │
       ▼
┌─────────────────────────────────────┐
│      Analytics Module                │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ AnalyticsController (v2)      │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│  ┌───────────▼──────────────────┐  │
│  │ AnalyticsService              │  │
│  │                               │  │
│  │ 1. Get employee count         │  │
│  │    (HR Core API)              │  │
│  │                               │  │
│  │ 2. Get payroll totals         │  │
│  │    (Payroll API)              │  │
│  │                               │  │
│  │ 3. Get HMO enrollments        │  │
│  │    (HMO API)                  │  │
│  │                               │  │
│  │ 4. Aggregate data             │  │
│  │                               │  │
│  │ 5. Cache result               │  │
│  └───────────┬──────────────────┘  │
│              │                      │
│              │ Parallel API calls   │
│              │                      │
└──────────────┼──────────────────────┘
               │
       ┌───────┼───────┐
       │       │       │
       ▼       ▼       ▼
┌─────────┐ ┌─────────┐ ┌─────────┐
│HR Core  │ │Payroll  │ │  HMO    │
│  API    │ │  API    │ │  API    │
└────┬────┘ └────┬────┘ └────┬────┘
     │           │           │
     │           │           │
     └───────────┼───────────┘
                 │
                 │ Aggregated Data
                 │
                 ▼
┌─────────────────────────────────────┐
│      Analytics Module                │
│                                     │
│  - Combine data                     │
│  - Calculate metrics                │
│  - Return AnalyticsDTO              │
└─────────────────────────────────────┘
```

### Code Flow

```php
class AnalyticsService {
    public function getHRMetrics(): AnalyticsDTO {
        // Parallel API calls for performance
        [$employees, $payroll, $hmo] = Promise::all([
            $this->hrCoreService->getEmployeeCount(),
            $this->payrollService->getTotalPayroll(),
            $this->hmoService->getEnrollmentCount()
        ]);
        
        return new AnalyticsDTO([
            'total_employees' => $employees,
            'total_payroll' => $payroll,
            'hmo_enrollments' => $hmo,
            'avg_salary' => $payroll / $employees
        ]);
    }
}
```

---

## 5. Event-Driven Compensation Update Flow

### Scenario: Salary adjustment triggers multiple module updates

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       │ PUT /api/v2/compensation/salaries/{id}
       │ { "salary": 50000 }
       │
       ▼
┌─────────────────────────────────────┐
│   Compensation Module                │
│                                     │
│  - Update salary                    │
│  - Validate amount                  │
│  - Save to compensation_*           │
│                                     │
│  Publish: salary.updated            │
│  {                                  │
│    "employee_id": 123,               │
│    "old_salary": 45000,              │
│    "new_salary": 50000,              │
│    "effective_date": "2025-02-01"   │
│  }                                  │
└──────────────┬───────────────────────┘
               │
               │ Event
               │
               ▼
┌─────────────────────────────────────┐
│         Event Bus                   │
└──────┬──────────────────────────────┘
       │
       ├─────────────────┬─────────────────┐
       │                 │                 │
       ▼                 ▼                 ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Payroll   │  │  Analytics   │  │Notifications│
│   Module    │  │   Module     │  │   Module    │
│             │  │              │  │             │
│ Action:     │  │ Action:      │  │ Action:     │
│ - Update    │  │ - Recalculate│  │ - Send      │
│   next      │  │   metrics     │  │   email     │
│   payroll   │  │ - Update     │  │   to        │
│   run       │  │   cache      │  │   employee  │
│             │  │              │  │             │
└─────────────┘  └─────────────┘  └─────────────┘
```

---

## Key Patterns

### 1. Synchronous API Calls
- Used when immediate response needed
- Protected with circuit breakers
- Has fallback logic

### 2. Asynchronous Events
- Used for side effects
- Fire-and-forget operations
- No blocking of main flow

### 3. Parallel Calls
- Used for independent data fetching
- Improves performance
- All calls must succeed or handle failures

### 4. Error Isolation
- Circuit breakers prevent cascade failures
- Fallback data ensures continuity
- Logging for monitoring

---

## Benefits

✅ **Loose Coupling**: Modules don't know about each other's internals  
✅ **Resilience**: Failures in one module don't crash others  
✅ **Scalability**: Modules can scale independently  
✅ **Maintainability**: Clear boundaries and contracts  
✅ **Testability**: Easy to mock and test modules independently  
