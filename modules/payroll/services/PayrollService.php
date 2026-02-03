<?php
/**
 * Payroll Service
 * 
 * Example of service using HR Core service interface with circuit breaker.
 * This demonstrates how modules should interact with each other.
 */

namespace Modules\Payroll\Services;

use Modules\HRCore\Services\Interfaces\HRCoreServiceInterface;
use Common\Services\CircuitBreaker;
use Common\Services\RetryService;
use Common\Services\EventBus;

class PayrollService {
    private $hrCoreService;
    private $circuitBreaker;
    private $retryService;
    
    public function __construct(HRCoreServiceInterface $hrCoreService) {
        $this->hrCoreService = $hrCoreService;
        
        // Initialize circuit breaker for HR Core API calls
        $this->circuitBreaker = new CircuitBreaker(
            'hrcore-api',
            failureThreshold: 5,
            timeout: 60
        );
        
        // Initialize retry service
        $this->retryService = new RetryService(
            maxAttempts: 3,
            initialDelayMs: 100,
            maxDelayMs: 5000
        );
    }
    
    /**
     * Process payroll for an employee
     * 
     * @param int $employeeId
     * @return array
     */
    public function processEmployeePayroll(int $employeeId): array {
        // Get employee data with circuit breaker protection
        $employee = $this->getEmployeeWithFallback($employeeId);
        
        // Calculate payroll
        $grossPay = $this->calculateGrossPay($employee);
        $deductions = $this->calculateDeductions($employee);
        $netPay = $grossPay - $deductions;
        
        return [
            'employee_id' => $employeeId,
            'gross_pay' => $grossPay,
            'deductions' => $deductions,
            'net_pay' => $netPay
        ];
    }
    
    /**
     * Get employee with fallback logic
     * 
     * @param int $employeeId
     * @return object Employee data (DTO or fallback object)
     */
    private function getEmployeeWithFallback(int $employeeId): object {
        try {
            // Use circuit breaker and retry service
            $employee = $this->circuitBreaker->call(
                function() use ($employeeId) {
                    return $this->retryService->execute(
                        function() use ($employeeId) {
                            return $this->hrCoreService->getEmployee($employeeId);
                        },
                        [RetryService::class, 'isRetryableError']
                    );
                },
                // Fallback if circuit is open
                function() use ($employeeId) {
                    return $this->getCachedEmployee($employeeId);
                }
            );
            
            if ($employee === null) {
                throw new \Exception("Employee not found: {$employeeId}");
            }
            
            return $employee;
        } catch (\Exception $e) {
            error_log("Error getting employee {$employeeId}: " . $e->getMessage());
            
            // Try to get cached data
            $cached = $this->getCachedEmployee($employeeId);
            if ($cached !== null) {
                return $cached;
            }
            
            // Last resort: use default values
            return $this->getDefaultEmployeeData($employeeId);
        }
    }
    
    /**
     * Get cached employee data
     * 
     * @param int $employeeId
     * @return object|null
     */
    private function getCachedEmployee(int $employeeId): ?object {
        // Implementation would use cache (Redis, Memcached, etc.)
        // For now, return null
        return null;
    }
    
    /**
     * Get default employee data (fallback)
     * 
     * @param int $employeeId
     * @return object
     */
    private function getDefaultEmployeeData(int $employeeId): object {
        return (object)[
            'id' => $employeeId,
            'firstName' => 'Unknown',
            'lastName' => 'Employee',
            'departmentId' => 0,
            'status' => 'active'
        ];
    }
    
    /**
     * Calculate gross pay
     * 
     * @param object $employee
     * @return float
     */
    private function calculateGrossPay(object $employee): float {
        // Implementation would calculate based on salary, hours, etc.
        return 0.0;
    }
    
    /**
     * Calculate deductions
     * 
     * @param object $employee
     * @return float
     */
    private function calculateDeductions(object $employee): float {
        // Implementation would calculate deductions
        return 0.0;
    }
    
    /**
     * Initialize employee in payroll system
     * Called via event subscription
     * 
     * @param array $eventData
     * @return void
     */
    public function initializeEmployee(array $eventData): void {
        $employeeId = $eventData['employee_id'] ?? null;
        
        if ($employeeId === null) {
            error_log("Employee ID missing in employee.created event");
            return;
        }
        
        try {
            // Initialize payroll records for new employee
            // This would create default salary, deductions, etc.
            error_log("Initializing payroll for employee {$employeeId}");
            
            // Publish event
            EventBus::publish('payroll.employee.initialized', [
                'employee_id' => $employeeId
            ]);
        } catch (\Exception $e) {
            error_log("Error initializing payroll for employee {$employeeId}: " . $e->getMessage());
        }
    }
}
