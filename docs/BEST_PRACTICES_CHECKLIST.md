# API Development Best Practices Checklist

## API Development

### Versioning
- [ ] All endpoints use versioning (`/api/v1/`, `/api/v2/`)
- [ ] Never modify existing API versions
- [ ] Create new versions for breaking changes
- [ ] Document deprecation timeline (minimum 6 months)

### Endpoints
- [ ] Use RESTful conventions
- [ ] Use appropriate HTTP methods (GET, POST, PUT, DELETE)
- [ ] Use proper HTTP status codes
- [ ] Return consistent response format

### DTOs (Data Transfer Objects)
- [ ] All API requests/responses use DTOs
- [ ] DTOs extend `BaseDTO`
- [ ] DTOs have validation methods
- [ ] DTOs convert to/from arrays correctly

### Validation
- [ ] Validate all inputs at API boundary
- [ ] Return clear validation error messages
- [ ] Use DTO validation methods
- [ ] Sanitize inputs to prevent injection

### Error Handling
- [ ] Use standardized error responses
- [ ] Log errors with context
- [ ] Don't expose internal errors to clients
- [ ] Return appropriate HTTP status codes

---

## Module Development

### Database Access
- [ ] No direct cross-module database access
- [ ] Each module owns its database schema
- [ ] Use repositories for data access
- [ ] Use prepared statements (no SQL injection)

### Service Interfaces
- [ ] Modules expose service interfaces
- [ ] Other modules use interfaces, not implementations
- [ ] Interfaces define clear contracts
- [ ] Document interface contracts

### Inter-Module Communication
- [ ] Use service interfaces for synchronous calls
- [ ] Use events for asynchronous operations
- [ ] Never call other modules' internal functions
- [ ] Use circuit breakers for resilience

### Events
- [ ] Publish events for important actions
- [ ] Subscribe to events from other modules
- [ ] Event names follow convention: `{entity}.{action}`
- [ ] Event data is minimal and focused

### Business Logic
- [ ] Business logic in service layer, not controllers
- [ ] Controllers are thin (routing only)
- [ ] Services are testable (dependency injection)
- [ ] No business logic in repositories

---

## Security

### Authentication
- [ ] All endpoints require authentication (except public)
- [ ] Use JWT tokens or session-based auth
- [ ] Validate tokens on every request
- [ ] Tokens have expiration

### Authorization
- [ ] Implement RBAC (Role-Based Access Control)
- [ ] Check permissions before operations
- [ ] Use middleware for authorization
- [ ] Document required permissions

### Input Security
- [ ] Validate all inputs
- [ ] Sanitize inputs
- [ ] Use parameterized queries
- [ ] Escape output to prevent XSS

### Rate Limiting
- [ ] Implement rate limiting
- [ ] Different limits per user role
- [ ] Return 429 status on limit exceeded
- [ ] Include rate limit headers in response

### Logging
- [ ] Log all sensitive operations
- [ ] Don't log sensitive data (passwords, tokens)
- [ ] Use structured logging
- [ ] Monitor logs for security issues

---

## Testing

### Unit Tests
- [ ] Test all business logic
- [ ] Mock external dependencies
- [ ] Achieve 80%+ code coverage
- [ ] Tests are fast and isolated

### Integration Tests
- [ ] Test API endpoints
- [ ] Test module interactions
- [ ] Test event publishing/subscribing
- [ ] Use test database

### Contract Tests
- [ ] Test DTO contracts
- [ ] Test service interface contracts
- [ ] Verify backward compatibility
- [ ] Test API versioning

### E2E Tests
- [ ] Test critical user workflows
- [ ] Test complete scenarios
- [ ] Test error scenarios
- [ ] Tests run in CI/CD

---

## Performance

### Caching
- [ ] Cache frequently accessed data
- [ ] Invalidate cache on updates
- [ ] Use appropriate cache TTL
- [ ] Monitor cache hit rates

### Database
- [ ] Use indexes on frequently queried columns
- [ ] Avoid N+1 queries
- [ ] Use pagination for large datasets
- [ ] Optimize slow queries

### API Calls
- [ ] Use parallel calls when possible
- [ ] Implement circuit breakers
- [ ] Use retry with exponential backoff
- [ ] Set appropriate timeouts

---

## Documentation

### API Documentation
- [ ] Document all endpoints (OpenAPI/Swagger)
- [ ] Document request/response formats
- [ ] Document error responses
- [ ] Document required permissions

### Code Documentation
- [ ] Document all public methods
- [ ] Document complex logic
- [ ] Use PHPDoc comments
- [ ] Keep documentation up to date

### Migration Guides
- [ ] Document API changes
- [ ] Provide migration examples
- [ ] Document breaking changes
- [ ] Provide rollback procedures

---

## Monitoring & Observability

### Logging
- [ ] Log all API requests
- [ ] Log errors with stack traces
- [ ] Use log levels appropriately
- [ ] Centralized logging

### Metrics
- [ ] Track API response times
- [ ] Track error rates
- [ ] Track circuit breaker states
- [ ] Track event publishing/subscribing

### Alerts
- [ ] Alert on high error rates
- [ ] Alert on circuit breaker openings
- [ ] Alert on performance degradation
- [ ] Alert on security issues

---

## Deployment

### Feature Flags
- [ ] Use feature flags for new features
- [ ] Can enable/disable features without deploy
- [ ] Gradual rollout capability
- [ ] Easy rollback

### Staging Environment
- [ ] Test in staging before production
- [ ] Staging mirrors production
- [ ] Test migration in staging
- [ ] Verify all tests pass

### Rollback Plan
- [ ] Can rollback quickly
- [ ] No data loss on rollback
- [ ] Documented rollback procedure
- [ ] Tested rollback procedure

---

## Code Quality

### Code Style
- [ ] Follow PSR-12 coding standards
- [ ] Use consistent naming conventions
- [ ] Keep functions small and focused
- [ ] Avoid code duplication

### Code Review
- [ ] All code reviewed before merge
- [ ] Review for security issues
- [ ] Review for performance issues
- [ ] Review for best practices

### Refactoring
- [ ] Refactor when needed
- [ ] Don't accumulate technical debt
- [ ] Keep code maintainable
- [ ] Update tests when refactoring

---

## Checklist Usage

Use this checklist:

1. **Before Starting Development**
   - Review relevant sections
   - Understand requirements

2. **During Development**
   - Check items as you implement
   - Don't skip items

3. **Before Code Review**
   - Review all items
   - Fix any issues

4. **Before Deployment**
   - Verify all items checked
   - Run all tests
   - Review documentation

---

## Quick Reference

### API Endpoint Structure
```
GET    /api/v2/{module}/{resource}        # List
GET    /api/v2/{module}/{resource}/{id}   # Get one
POST   /api/v2/{module}/{resource}        # Create
PUT    /api/v2/{module}/{resource}/{id}   # Update
DELETE /api/v2/{module}/{resource}/{id}   # Delete
```

### Event Naming Convention
```
{entity}.{action}
Examples:
- employee.created
- employee.updated
- employee.deleted
- payroll.processed
```

### DTO Naming Convention
```
{Entity}DTO           # For responses
Create{Entity}DTO     # For creation
Update{Entity}DTO     # For updates
```

### Service Interface Naming
```
{Module}ServiceInterface
Examples:
- HRCoreServiceInterface
- PayrollServiceInterface
```

---

## Questions?

If unsure about any practice:

1. Check the architecture documentation
2. Review existing code examples
3. Ask the team
4. Follow the principle: "If in doubt, make it modular and testable"
