# Hospital HR System - API Architecture Redesign

**Version:** 2.0  
**Date:** January 2025  
**Status:** Architecture Specification

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Principles](#architecture-principles)
3. [System Architecture Overview](#system-architecture-overview)
4. [Module Architecture](#module-architecture)
5. [API Contract & Versioning](#api-contract--versioning)
6. [Module Communication](#module-communication)
7. [Data Governance](#data-governance)
8. [Error Isolation & Resilience](#error-isolation--resilience)
9. [Security Architecture](#security-architecture)
10. [Testing Strategy](#testing-strategy)
11. [Migration Plan](#migration-plan)

---

## Executive Summary

This document outlines the complete redesign of the Hospital HR System API architecture to achieve:

- **Modularity**: Complete separation of concerns between modules
- **Stability**: Changes in one module do not impact others
- **Scalability**: Independent scaling of modules
- **Maintainability**: Clear contracts and interfaces
- **Future-proof**: Versioned APIs with backward compatibility

### Key Changes

1. **API Versioning**: All APIs use `/api/v1/`, `/api/v2/` structure
2. **Event-Driven Communication**: Modules communicate via events, not direct calls
3. **Service Interfaces**: DTOs and interfaces replace direct database access
4. **Circuit Breakers**: Error isolation prevents cascade failures
5. **API Gateway**: Centralized routing, authentication, and rate limiting

---

## Architecture Principles

### 1. Modular Monolith → Microservices-Ready

The system is designed as a **Modular Monolith** that can evolve into microservices:

- Each module is self-contained
- Clear API boundaries
- Independent deployment capability
- Shared infrastructure (database, messaging) but isolated logic

### 2. API-First Design

- All module interactions go through APIs
- No direct database cross-access
- Versioned contracts
- OpenAPI/Swagger documentation

### 3. Loose Coupling

- **Event-Driven**: Modules publish/subscribe to events
- **Service Interfaces**: Abstract contracts, not implementations
- **DTOs**: Data transfer objects for all API communication
- **No Shared State**: Each module owns its data

### 4. Failure Isolation

- Circuit breakers prevent cascade failures
- Retry mechanisms with exponential backoff
- Fallback responses
- Graceful degradation

### 5. Backward Compatibility

- Never modify existing API versions
- Deprecation timeline (minimum 6 months)
- Feature flags for gradual rollout
- Staging environment for testing

---

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      API Gateway                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Auth & RBAC  │  │ Rate Limiting│  │   Logging    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
┌───────▼──────┐  ┌───────▼──────┐  ┌───────▼──────┐
│   HR Core    │  │   Payroll     │  │     HMO      │
│   Module     │  │   Module      │  │   Module     │
│              │  │               │  │              │
│ ┌──────────┐ │  │ ┌──────────┐ │  │ ┌──────────┐ │
│ │ API v1/v2│ │  │ │ API v1/v2│ │  │ │ API v1/v2│ │
│ │ Service  │ │  │ │ Service  │ │  │ │ Service  │ │
│ │ Layer    │ │  │ │ Layer    │ │  │ │ Layer    │ │
│ └──────────┘ │  │ └──────────┘ │  │ └──────────┘ │
│ ┌──────────┐ │  │ ┌──────────┐ │  │ ┌──────────┐ │
│ │ Business │ │  │ │ Business │ │  │ │ Business │ │
│ │ Logic    │ │  │ │ Logic    │ │  │ │ Logic    │ │
│ └──────────┘ │  │ └──────────┘ │  │ └──────────┘ │
│ ┌──────────┐ │  │ ┌──────────┐ │  │ ┌──────────┐ │
│ │   Data   │ │  │ │   Data   │ │  │ │   Data   │ │
│ │  Access  │ │  │ │  Access  │ │  │ │  Access  │ │
│ └──────────┘ │  │ └──────────┘ │  │ └──────────┘ │
└───────┬──────┘  └───────┬──────┘  └───────┬──────┘
        │                 │                 │
        └─────────────────┼─────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
┌───────▼──────┐  ┌───────▼──────┐  ┌───────▼──────┐
│   Event      │  │   Shared     │  │   Analytics  │
│   Bus        │  │   Services   │  │   Module     │
│              │  │              │  │              │
│ - Publish    │  │ - Logging    │  │              │
│ - Subscribe  │  │ - Monitoring │  │              │
│ - Queue      │  │ - Config     │  │              │
└──────────────┘  └──────────────┘  └──────────────┘
```

### Module Structure

Each module follows this structure:

```
modules/{module_name}/
├── api/
│   └── v1/              # Version 1 endpoints
│   └── v2/              # Version 2 endpoints
├── services/            # Business logic layer
│   ├── interfaces/      # Service interfaces
│   └── implementations/ # Service implementations
├── models/              # Data models
├── dto/                 # Data Transfer Objects
├── events/              # Event publishers/subscribers
├── repositories/        # Data access layer
└── tests/               # Module tests
```

---

## Module Architecture

### Core Modules

1. **HR Core Module** (`hrcore`)
   - Employee management
   - Organizational structure
   - Documents
   - **Database Schema**: `hrcore_*` tables

2. **Payroll Module** (`payroll`)
   - Salary management
   - Payroll runs
   - Payslips
   - Deductions & bonuses
   - **Database Schema**: `payroll_*` tables

3. **Compensation Module** (`compensation`)
   - Compensation plans
   - Salary grades
   - Adjustments
   - **Database Schema**: `compensation_*` tables

4. **HMO Module** (`hmo`)
   - HMO enrollments
   - Claims processing
   - Providers & plans
   - **Database Schema**: `hmo_*` tables

5. **Analytics Module** (`analytics`)
   - HR metrics
   - Reports generation
   - Data aggregation
   - **Database Schema**: `analytics_*` tables (read-only from other modules)

### Module Independence Rules

1. **No Direct Database Access**
   ```php
   // ❌ BAD: Direct cross-module database access
   $stmt = $pdo->query("SELECT * FROM Employees WHERE DepartmentID = 1");
   
   // ✅ GOOD: Use HR Core API
   $employees = $hrCoreService->getEmployeesByDepartment(1);
   ```

2. **No Direct Function Calls**
   ```php
   // ❌ BAD: Direct function call
   $employee = getEmployeeById($id);
   
   // ✅ GOOD: Use service interface
   $employee = $hrCoreServiceInterface->getEmployee($id);
   ```

3. **Use Events for Async Operations**
   ```php
   // ✅ GOOD: Publish event
   EventBus::publish('employee.created', [
       'employee_id' => $id,
       'department_id' => $deptId
   ]);
   ```

---

## API Contract & Versioning

### Versioning Strategy

**URL Pattern:**
```
/api/v1/{module}/{resource}
/api/v2/{module}/{resource}
```

**Examples:**
```
GET  /api/v1/hrcore/employees
POST /api/v1/hrcore/employees
GET  /api/v2/hrcore/employees/{id}
PUT  /api/v2/hrcore/employees/{id}
```

### Version Lifecycle

1. **Active**: Current version, fully supported
2. **Deprecated**: Still supported, but marked for removal
3. **Sunset**: Removed after deprecation period (6+ months)

### API Contract Rules

1. **Never Modify Existing Endpoints**
   - Add new versions instead
   - Maintain backward compatibility

2. **Strict Request/Response Contracts**
   - Use DTOs for all requests/responses
   - Validate all inputs
   - Document all fields

3. **OpenAPI Documentation**
   - All endpoints documented
   - Auto-generated from code
   - Versioned documentation

### Example API Structure

```php
// modules/hrcore/api/v1/EmployeesController.php
class EmployeesController {
    public function list(Request $request): Response {
        // v1 implementation
    }
}

// modules/hrcore/api/v2/EmployeesController.php
class EmployeesController {
    public function list(Request $request): Response {
        // v2 implementation with improvements
    }
}
```

---

## Module Communication

### Event-Driven Architecture

Modules communicate via an **Event Bus**:

#### Event Types

1. **Domain Events**: Business events (employee.created, payroll.processed)
2. **Integration Events**: Cross-module events
3. **System Events**: Technical events (module.started, error.occurred)

#### Event Flow

```
Module A                    Event Bus                    Module B
   │                            │                            │
   │─── publish(event) ────────>│                            │
   │                            │─── subscribe(event) ───────>│
   │                            │                            │
   │                            │<─── handle(event) ─────────│
```

#### Implementation

```php
// Event Publisher
EventBus::publish('employee.created', [
    'employee_id' => 123,
    'department_id' => 5,
    'timestamp' => time()
]);

// Event Subscriber (in Payroll module)
EventBus::subscribe('employee.created', function($event) {
    // Initialize payroll records for new employee
    $payrollService->initializeEmployee($event['employee_id']);
});
```

### Service Interfaces

Modules expose **service interfaces** instead of direct implementations:

```php
// modules/hrcore/services/interfaces/HRCoreServiceInterface.php
interface HRCoreServiceInterface {
    public function getEmployee(int $id): EmployeeDTO;
    public function getEmployeesByDepartment(int $deptId): array;
    public function createEmployee(CreateEmployeeDTO $dto): EmployeeDTO;
}

// modules/payroll/services/PayrollService.php
class PayrollService {
    private $hrCoreService;
    
    public function __construct(HRCoreServiceInterface $hrCoreService) {
        $this->hrCoreService = $hrCoreService;
    }
    
    public function processPayroll(int $employeeId) {
        // Use interface, not direct database access
        $employee = $this->hrCoreService->getEmployee($employeeId);
        // Process payroll...
    }
}
```

### DTOs (Data Transfer Objects)

All API communication uses DTOs:

```php
// modules/hrcore/dto/EmployeeDTO.php
class EmployeeDTO {
    public int $id;
    public string $firstName;
    public string $lastName;
    public int $departmentId;
    public string $departmentName;
    public DateTime $hireDate;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'department_id' => $this->departmentId,
            'department_name' => $this->departmentName,
            'hire_date' => $this->hireDate->format('Y-m-d')
        ];
    }
}
```

---

## Data Governance

### Database Schema Isolation

Each module owns its database schema:

```
hrcore_*          → HR Core Module
payroll_*         → Payroll Module
compensation_*    → Compensation Module
hmo_*             → HMO Module
analytics_*       → Analytics Module (read-only aggregations)
```

### Data Access Rules

1. **No Cross-Schema Queries**
   ```php
   // ❌ BAD
   SELECT e.*, p.Salary 
   FROM hrcore_employees e 
   JOIN payroll_salaries p ON e.EmployeeID = p.EmployeeID
   
   // ✅ GOOD: Use APIs
   $employee = $hrCoreService->getEmployee($id);
   $salary = $payrollService->getSalary($id);
   ```

2. **Read-Only Access for Analytics**
   - Analytics module can read from all schemas
   - But only for aggregation/reporting
   - No write operations

3. **Data Validation**
   - All inputs validated at API boundary
   - Schema validation for DTOs
   - Database constraints as last line of defense

### Integration Layer

For complex queries requiring data from multiple modules:

```php
// modules/integrations/services/EmployeePayrollIntegrationService.php
class EmployeePayrollIntegrationService {
    private $hrCoreService;
    private $payrollService;
    
    public function getEmployeeWithPayroll(int $employeeId): array {
        $employee = $this->hrCoreService->getEmployee($employeeId);
        $payroll = $this->payrollService->getEmployeePayroll($employeeId);
        
        return [
            'employee' => $employee->toArray(),
            'payroll' => $payroll->toArray()
        ];
    }
}
```

---

## Error Isolation & Resilience

### Circuit Breaker Pattern

Prevents cascade failures:

```php
// common/services/CircuitBreaker.php
class CircuitBreaker {
    private $failureCount = 0;
    private $lastFailureTime = null;
    private $state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
    
    public function call(callable $operation) {
        if ($this->state === 'OPEN') {
            if ($this->shouldAttemptReset()) {
                $this->state = 'HALF_OPEN';
            } else {
                throw new CircuitBreakerOpenException();
            }
        }
        
        try {
            $result = $operation();
            $this->onSuccess();
            return $result;
        } catch (Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }
}
```

### Retry Mechanism

```php
// common/services/RetryService.php
class RetryService {
    public function execute(callable $operation, int $maxAttempts = 3) {
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            try {
                return $operation();
            } catch (Exception $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                usleep(pow(2, $attempt) * 100000); // Exponential backoff
            }
        }
    }
}
```

### Fallback Logic

```php
// modules/payroll/services/PayrollService.php
class PayrollService {
    public function getEmployeeSalary(int $employeeId): SalaryDTO {
        try {
            return $this->hrCoreService->getEmployee($employeeId)
                ->then(fn($emp) => $this->calculateSalary($emp));
        } catch (ServiceUnavailableException $e) {
            // Fallback to cached data
            return $this->getCachedSalary($employeeId);
        }
    }
}
```

### Error Handling Strategy

1. **Module-Level Errors**: Handled within module, not propagated
2. **API Errors**: Standardized error responses
3. **System Errors**: Logged and monitored, graceful degradation

---

## Security Architecture

### API Gateway Security

1. **Authentication**
   - JWT tokens
   - OAuth2 support
   - Token validation at gateway

2. **Authorization**
   - Role-Based Access Control (RBAC)
   - Module-level permissions
   - Resource-level permissions

3. **Rate Limiting**
   - Per-user limits
   - Per-IP limits
   - Per-module limits

### Module Security

```php
// common/middlewares/RBACMiddleware.php
class RBACMiddleware {
    public function checkPermission(string $module, string $action): bool {
        $user = $this->getCurrentUser();
        return $this->permissionService->hasPermission(
            $user->getId(),
            $module,
            $action
        );
    }
}
```

### Security Rules

1. **Input Validation**: All inputs validated
2. **SQL Injection Prevention**: Prepared statements only
3. **XSS Prevention**: Output encoding
4. **CSRF Protection**: Token validation
5. **Audit Logging**: All sensitive operations logged

---

## Testing Strategy

### Test Pyramid

```
        ┌─────────────┐
        │   E2E Tests │  (10%)
        └─────────────┘
       ┌───────────────┐
       │ Integration   │  (20%)
       │   Tests       │
       └───────────────┘
      ┌─────────────────┐
      │   Unit Tests    │  (70%)
      └─────────────────┘
```

### Test Types

1. **Unit Tests**
   - Test individual functions/classes
   - Mock dependencies
   - Fast execution

2. **Integration Tests**
   - Test module interactions
   - Test API contracts
   - Test event handling

3. **Contract Tests**
   - Verify API contracts between modules
   - Ensure backward compatibility

4. **E2E Tests**
   - Test complete workflows
   - Test user scenarios

### Testing Framework Structure

```
tests/
├── unit/
│   ├── hrcore/
│   ├── payroll/
│   └── ...
├── integration/
│   ├── api/
│   ├── events/
│   └── services/
├── contract/
│   └── api/
└── e2e/
    └── scenarios/
```

---

## Migration Plan

### Phase 1: Foundation (Weeks 1-2)
- [ ] Set up API versioning structure
- [ ] Create event bus foundation
- [ ] Implement DTO system
- [ ] Set up API Gateway

### Phase 2: Core Modules (Weeks 3-6)
- [ ] Refactor HR Core to v2 API
- [ ] Refactor Payroll to v2 API
- [ ] Implement service interfaces
- [ ] Add event publishers/subscribers

### Phase 3: Integration (Weeks 7-10)
- [ ] Implement circuit breakers
- [ ] Add retry mechanisms
- [ ] Set up monitoring
- [ ] Create integration tests

### Phase 4: Security & Testing (Weeks 11-12)
- [ ] Implement RBAC
- [ ] Add rate limiting
- [ ] Complete test suite
- [ ] Performance testing

### Phase 5: Documentation & Rollout (Weeks 13-14)
- [ ] Complete API documentation
- [ ] Create migration guides
- [ ] Gradual rollout with feature flags
- [ ] Monitor and adjust

### Migration Strategy

1. **Parallel Run**: v1 and v2 APIs run simultaneously
2. **Gradual Migration**: Move clients to v2 one by one
3. **Feature Flags**: Control rollout per feature
4. **Rollback Plan**: Ability to revert if issues occur

---

## Best Practices Checklist

### API Development
- [ ] All endpoints versioned (`/api/v1/`, `/api/v2/`)
- [ ] Never modify existing endpoints
- [ ] Use DTOs for all requests/responses
- [ ] Validate all inputs
- [ ] Document with OpenAPI/Swagger

### Module Development
- [ ] No direct database cross-access
- [ ] Use service interfaces for inter-module communication
- [ ] Publish events for async operations
- [ ] Implement circuit breakers
- [ ] Add retry logic with exponential backoff

### Security
- [ ] Authenticate all requests
- [ ] Authorize based on RBAC
- [ ] Rate limit APIs
- [ ] Log all sensitive operations
- [ ] Validate and sanitize inputs

### Testing
- [ ] Unit tests for all business logic
- [ ] Integration tests for API contracts
- [ ] Contract tests between modules
- [ ] E2E tests for critical workflows

### Documentation
- [ ] API documentation (OpenAPI)
- [ ] Module dependency map
- [ ] Event catalog
- [ ] Migration guides

---

## Conclusion

This architecture redesign provides:

✅ **Modularity**: Complete separation of concerns  
✅ **Stability**: Changes isolated to modules  
✅ **Scalability**: Independent scaling  
✅ **Maintainability**: Clear contracts and interfaces  
✅ **Future-proof**: Versioned APIs with backward compatibility  

The system is designed to evolve from a modular monolith to microservices when needed, without requiring a complete rewrite.

---

**Next Steps:**
1. Review and approve architecture
2. Begin Phase 1 implementation
3. Set up development environment
4. Create initial API v2 endpoints
