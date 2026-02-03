<?php
/**
 * Circuit Breaker Pattern Implementation
 * 
 * Prevents cascade failures by stopping requests to a failing service
 * after a threshold of failures is reached.
 * 
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Service is failing, requests are blocked
 * - HALF_OPEN: Testing if service has recovered
 */

namespace Common\Services;

class CircuitBreaker {
    private $name;
    private $failureThreshold;
    private $timeout;
    private $failureCount = 0;
    private $lastFailureTime = null;
    private $state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
    private $successCount = 0;
    private $halfOpenSuccessThreshold;
    
    /**
     * Constructor
     * 
     * @param string $name Circuit breaker name (e.g., 'hrcore-api')
     * @param int $failureThreshold Number of failures before opening
     * @param int $timeout Seconds to wait before attempting recovery
     * @param int $halfOpenSuccessThreshold Successes needed to close from HALF_OPEN
     */
    public function __construct(
        string $name,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $halfOpenSuccessThreshold = 2
    ) {
        $this->name = $name;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->halfOpenSuccessThreshold = $halfOpenSuccessThreshold;
    }
    
    /**
     * Execute a callable with circuit breaker protection
     * 
     * @param callable $operation Operation to execute
     * @param callable|null $fallback Fallback operation if circuit is open
     * @return mixed
     * @throws CircuitBreakerOpenException
     */
    public function call(callable $operation, ?callable $fallback = null) {
        // Check if circuit is open
        if ($this->state === 'OPEN') {
            if ($this->shouldAttemptReset()) {
                $this->state = 'HALF_OPEN';
                $this->successCount = 0;
            } else {
                if ($fallback !== null) {
                    return $fallback();
                }
                throw new CircuitBreakerOpenException(
                    "Circuit breaker '{$this->name}' is OPEN"
                );
            }
        }
        
        // Execute operation
        try {
            $result = $operation();
            $this->onSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }
    
    /**
     * Handle successful operation
     * 
     * @return void
     */
    private function onSuccess(): void {
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        
        if ($this->state === 'HALF_OPEN') {
            $this->successCount++;
            
            // If we've had enough successes, close the circuit
            if ($this->successCount >= $this->halfOpenSuccessThreshold) {
                $this->state = 'CLOSED';
                $this->successCount = 0;
                error_log("Circuit breaker '{$this->name}' closed after recovery");
            }
        } else if ($this->state === 'OPEN') {
            // This shouldn't happen, but handle it
            $this->state = 'HALF_OPEN';
        }
    }
    
    /**
     * Handle failed operation
     * 
     * @return void
     */
    private function onFailure(): void {
        $this->failureCount++;
        $this->lastFailureTime = time();
        
        if ($this->state === 'HALF_OPEN') {
            // Failed during recovery, open again
            $this->state = 'OPEN';
            $this->successCount = 0;
            error_log("Circuit breaker '{$this->name}' reopened after failure");
        } else if ($this->failureCount >= $this->failureThreshold) {
            // Threshold reached, open the circuit
            $this->state = 'OPEN';
            error_log("Circuit breaker '{$this->name}' opened after {$this->failureCount} failures");
        }
    }
    
    /**
     * Check if we should attempt to reset (move from OPEN to HALF_OPEN)
     * 
     * @return bool
     */
    private function shouldAttemptReset(): bool {
        if ($this->state !== 'OPEN') {
            return false;
        }
        
        if ($this->lastFailureTime === null) {
            return false;
        }
        
        $timeSinceLastFailure = time() - $this->lastFailureTime;
        return $timeSinceLastFailure >= $this->timeout;
    }
    
    /**
     * Get current state
     * 
     * @return string
     */
    public function getState(): string {
        return $this->state;
    }
    
    /**
     * Get failure count
     * 
     * @return int
     */
    public function getFailureCount(): int {
        return $this->failureCount;
    }
    
    /**
     * Manually reset circuit breaker (for testing/admin)
     * 
     * @return void
     */
    public function reset(): void {
        $this->state = 'CLOSED';
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        $this->successCount = 0;
    }
    
    /**
     * Get circuit breaker status
     * 
     * @return array
     */
    public function getStatus(): array {
        return [
            'name' => $this->name,
            'state' => $this->state,
            'failure_count' => $this->failureCount,
            'last_failure_time' => $this->lastFailureTime,
            'failure_threshold' => $this->failureThreshold,
            'timeout' => $this->timeout
        ];
    }
}

/**
 * Exception thrown when circuit breaker is open
 */
class CircuitBreakerOpenException extends \Exception {
}
