# Testing Framework

## Overview

This directory contains the testing framework for the Hospital HR System API.

## Test Structure

```
tests/
├── unit/              # Unit tests (70% of tests)
│   ├── hrcore/
│   ├── payroll/
│   ├── hmo/
│   └── ...
├── integration/       # Integration tests (20% of tests)
│   ├── api/
│   ├── events/
│   └── services/
├── contract/          # Contract tests (API contracts between modules)
│   └── api/
└── e2e/               # End-to-end tests (10% of tests)
    └── scenarios/
```

## Test Types

### 1. Unit Tests

Test individual functions/classes in isolation.

**Example:**
```php
// tests/unit/hrcore/EmployeeServiceTest.php
class EmployeeServiceTest extends PHPUnit\Framework\TestCase {
    public function testGetEmployee() {
        // Mock repository
        $repo = $this->createMock(EmployeeRepository::class);
        $repo->expects($this->once())
             ->method('findById')
             ->willReturn(new EmployeeDTO());
        
        $service = new EmployeeService($repo);
        $employee = $service->getEmployee(1);
        
        $this->assertInstanceOf(EmployeeDTO::class, $employee);
    }
}
```

### 2. Integration Tests

Test module interactions and API contracts.

**Example:**
```php
// tests/integration/api/HRCorePayrollIntegrationTest.php
class HRCorePayrollIntegrationTest extends PHPUnit\Framework\TestCase {
    public function testPayrollUsesHRCoreAPI() {
        // Test that payroll module uses HR Core API correctly
        $payrollService = new PayrollService($hrCoreService);
        $result = $payrollService->processEmployeePayroll(1);
        
        $this->assertArrayHasKey('gross_pay', $result);
    }
}
```

### 3. Contract Tests

Verify API contracts between modules.

**Example:**
```php
// tests/contract/api/HRCoreServiceContractTest.php
class HRCoreServiceContractTest extends PHPUnit\Framework\TestCase {
    public function testEmployeeDTOContract() {
        // Verify DTO structure matches contract
        $dto = new EmployeeDTO();
        $array = $dto->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('first_name', $array);
        $this->assertArrayHasKey('last_name', $array);
    }
}
```

### 4. E2E Tests

Test complete user workflows.

**Example:**
```php
// tests/e2e/scenarios/EmployeeCreationFlowTest.php
class EmployeeCreationFlowTest extends PHPUnit\Framework\TestCase {
    public function testCreateEmployeeTriggersPayrollInit() {
        // Create employee via API
        $response = $this->post('/api/v2/hrcore/employees', [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        // Verify payroll was initialized (via event)
        $this->assertEventPublished('employee.created');
    }
}
```

## Running Tests

```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test suite
php vendor/bin/phpunit tests/unit
php vendor/bin/phpunit tests/integration
php vendor/bin/phpunit tests/contract
php vendor/bin/phpunit tests/e2e

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage/
```

## Test Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Integration Tests**: All API endpoints
- **Contract Tests**: All inter-module contracts
- **E2E Tests**: Critical user workflows

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Before deployment
