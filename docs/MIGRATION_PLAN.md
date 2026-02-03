# API Migration Plan: v1 to v2

## Overview

This document outlines the step-by-step migration plan from the current API (v1) to the new modular architecture (v2).

## Migration Strategy

### Principles

1. **Zero Downtime**: v1 and v2 APIs run in parallel
2. **Gradual Migration**: Move clients one by one
3. **Backward Compatibility**: v1 APIs remain functional
4. **Rollback Capability**: Can revert if issues occur
5. **Feature Flags**: Control rollout per feature

---

## Phase 1: Foundation Setup (Weeks 1-2)

### Tasks

- [x] Create API architecture documentation
- [x] Implement Event Bus
- [x] Implement Circuit Breaker
- [x] Implement Retry Service
- [x] Create DTO system
- [x] Set up API Gateway v2 structure
- [ ] Set up test database
- [ ] Configure CI/CD pipeline

### Deliverables

- Event Bus service
- Circuit Breaker service
- Base DTO classes
- API Gateway v2 entry point

---

## Phase 2: HR Core Module Migration (Weeks 3-4)

### Current State (v1)

```php
// modules/employees/api/get_employees.php
// Direct database access
$stmt = $pdo->query("SELECT * FROM Employees");
```

### Target State (v2)

```php
// modules/hrcore/api/v2/EmployeesController.php
// Uses service interface and DTOs
$employees = $this->hrCoreService->getEmployees();
```

### Migration Steps

1. **Create v2 API Structure**
   - [ ] Create `modules/hrcore/api/v2/` directory
   - [ ] Create `EmployeesController` (v2)
   - [ ] Create `EmployeeService` implementation
   - [ ] Create `EmployeeRepository` (data access layer)

2. **Implement Service Interface**
   - [ ] Create `HRCoreServiceInterface`
   - [ ] Implement `HRCoreService`
   - [ ] Move business logic from controllers to service

3. **Create DTOs**
   - [ ] Create `EmployeeDTO`
   - [ ] Create `CreateEmployeeDTO`
   - [ ] Update controllers to use DTOs

4. **Add Event Publishing**
   - [ ] Publish `employee.created` event
   - [ ] Publish `employee.updated` event
   - [ ] Publish `employee.deleted` event

5. **Update Router**
   - [ ] Add v2 routes to API Gateway
   - [ ] Test v2 endpoints

### Testing

- [ ] Unit tests for EmployeeService
- [ ] Integration tests for API endpoints
- [ ] Contract tests for DTOs
- [ ] E2E test for employee creation flow

### Rollout

- [ ] Deploy v2 API alongside v1
- [ ] Test v2 endpoints in staging
- [ ] Gradually move internal clients to v2
- [ ] Monitor for errors

---

## Phase 3: Payroll Module Migration (Weeks 5-6)

### Current State (v1)

```php
// modules/payroll/api/get_salaries.php
// Direct database access, may access Employees table
$stmt = $pdo->query("
    SELECT e.*, s.Salary 
    FROM Employees e 
    JOIN Salaries s ON e.EmployeeID = s.EmployeeID
");
```

### Target State (v2)

```php
// modules/payroll/services/PayrollService.php
// Uses HR Core service interface
$employee = $this->hrCoreService->getEmployee($id);
$salary = $this->getSalary($id);
```

### Migration Steps

1. **Refactor to Use HR Core API**
   - [ ] Remove direct database access to Employees table
   - [ ] Inject `HRCoreServiceInterface` into PayrollService
   - [ ] Update all methods to use HR Core API

2. **Add Circuit Breaker**
   - [ ] Wrap HR Core API calls with circuit breaker
   - [ ] Implement fallback logic
   - [ ] Add retry mechanism

3. **Add Event Subscriptions**
   - [ ] Subscribe to `employee.created` event
   - [ ] Initialize payroll records on employee creation
   - [ ] Subscribe to `employee.updated` event (if needed)

4. **Create v2 API Endpoints**
   - [ ] Create `PayrollController` (v2)
   - [ ] Create Payroll DTOs
   - [ ] Update routes

### Testing

- [ ] Test circuit breaker behavior
- [ ] Test fallback logic
- [ ] Test event subscriptions
- [ ] Integration test with HR Core

---

## Phase 4: HMO Module Migration (Weeks 7-8)

### Migration Steps

1. **Refactor to Use HR Core API**
   - [ ] Remove direct database access
   - [ ] Use HR Core service interface

2. **Add Event Subscriptions**
   - [ ] Subscribe to `employee.created`
   - [ ] Check HMO eligibility on employee creation

3. **Create v2 API Endpoints**
   - [ ] Create HMO DTOs
   - [ ] Create v2 controllers
   - [ ] Update routes

---

## Phase 5: Analytics Module Migration (Weeks 9-10)

### Migration Steps

1. **Refactor Data Aggregation**
   - [ ] Use APIs instead of direct database queries
   - [ ] Implement parallel API calls
   - [ ] Add caching

2. **Create v2 API Endpoints**
   - [ ] Create Analytics DTOs
   - [ ] Create v2 controllers

---

## Phase 6: Security & Testing (Weeks 11-12)

### Tasks

1. **Implement RBAC**
   - [ ] Add permission checks to all endpoints
   - [ ] Test role-based access

2. **Add Rate Limiting**
   - [ ] Configure rate limits per module
   - [ ] Test rate limiting

3. **Complete Test Suite**
   - [ ] Achieve 80%+ code coverage
   - [ ] All integration tests passing
   - [ ] All contract tests passing

4. **Performance Testing**
   - [ ] Load testing
   - [ ] Stress testing
   - [ ] Optimize bottlenecks

---

## Phase 7: Documentation & Rollout (Weeks 13-14)

### Tasks

1. **Complete Documentation**
   - [ ] OpenAPI/Swagger documentation
   - [ ] API migration guide
   - [ ] Developer guidelines

2. **Gradual Rollout**
   - [ ] Move internal tools to v2
   - [ ] Move frontend to v2
   - [ ] Move external integrations to v2

3. **Monitor & Adjust**
   - [ ] Monitor error rates
   - [ ] Monitor performance
   - [ ] Adjust as needed

---

## Migration Checklist

### For Each Module

- [ ] Create v2 API structure
- [ ] Implement service interfaces
- [ ] Create DTOs
- [ ] Add event publishing/subscriptions
- [ ] Add circuit breakers
- [ ] Write tests
- [ ] Update documentation
- [ ] Deploy to staging
- [ ] Test in staging
- [ ] Deploy to production
- [ ] Monitor and adjust

---

## Rollback Plan

If issues occur during migration:

1. **Immediate Rollback**
   - Revert to v1 API
   - Disable v2 endpoints (via feature flag)

2. **Partial Rollback**
   - Keep v2 for working modules
   - Revert problematic modules to v1

3. **Data Rollback**
   - No data migration needed (v1 and v2 use same database)
   - Only API layer changes

---

## Success Criteria

- [ ] All modules migrated to v2
- [ ] All clients using v2 API
- [ ] Zero downtime during migration
- [ ] No increase in error rates
- [ ] Performance maintained or improved
- [ ] 80%+ test coverage
- [ ] Complete documentation

---

## Timeline Summary

| Phase | Duration | Status |
|-------|----------|--------|
| Phase 1: Foundation | 2 weeks | ✅ Complete |
| Phase 2: HR Core | 2 weeks | 🔄 In Progress |
| Phase 3: Payroll | 2 weeks | ⏳ Pending |
| Phase 4: HMO | 2 weeks | ⏳ Pending |
| Phase 5: Analytics | 2 weeks | ⏳ Pending |
| Phase 6: Security & Testing | 2 weeks | ⏳ Pending |
| Phase 7: Documentation & Rollout | 2 weeks | ⏳ Pending |

**Total Duration**: 14 weeks (3.5 months)

---

## Risk Mitigation

### Risks

1. **Breaking Changes**
   - Mitigation: Maintain v1 API in parallel
   - Mitigation: Comprehensive testing

2. **Performance Degradation**
   - Mitigation: Performance testing before rollout
   - Mitigation: Caching and optimization

3. **Integration Issues**
   - Mitigation: Contract tests
   - Mitigation: Staging environment testing

4. **Data Inconsistency**
   - Mitigation: No direct database changes
   - Mitigation: API layer only changes

---

## Communication Plan

1. **Weekly Status Updates**
   - Progress report
   - Blockers and issues
   - Next week's plan

2. **Stakeholder Notifications**
   - Before each phase
   - After each phase completion
   - On issues or delays

3. **Developer Documentation**
   - API changes
   - Migration guides
   - Best practices
