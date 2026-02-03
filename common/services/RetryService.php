<?php
/**
 * Retry Service
 * 
 * Implements retry logic with exponential backoff for resilient API calls.
 */

namespace Common\Services;

class RetryService {
    private $maxAttempts;
    private $initialDelay; // in microseconds
    private $maxDelay; // in microseconds
    private $multiplier;
    
    /**
     * Constructor
     * 
     * @param int $maxAttempts Maximum number of retry attempts
     * @param int $initialDelayMs Initial delay in milliseconds
     * @param int $maxDelayMs Maximum delay in milliseconds
     * @param float $multiplier Exponential backoff multiplier
     */
    public function __construct(
        int $maxAttempts = 3,
        int $initialDelayMs = 100,
        int $maxDelayMs = 5000,
        float $multiplier = 2.0
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->initialDelay = $initialDelayMs * 1000; // Convert to microseconds
        $this->maxDelay = $maxDelayMs * 1000;
        $this->multiplier = $multiplier;
    }
    
    /**
     * Execute operation with retry logic
     * 
     * @param callable $operation Operation to execute
     * @param callable|null $shouldRetry Function to determine if error is retryable
     * @return mixed
     * @throws \Throwable
     */
    public function execute(callable $operation, ?callable $shouldRetry = null) {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $this->maxAttempts) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;
                
                // Check if we should retry
                if ($attempt >= $this->maxAttempts) {
                    break;
                }
                
                if ($shouldRetry !== null && !$shouldRetry($e)) {
                    break;
                }
                
                // Calculate delay with exponential backoff
                $delay = min(
                    $this->initialDelay * pow($this->multiplier, $attempt - 1),
                    $this->maxDelay
                );
                
                // Wait before retrying
                usleep($delay);
                
                error_log(sprintf(
                    "Retry attempt %d/%d for operation after %dms delay",
                    $attempt,
                    $this->maxAttempts,
                    $delay / 1000
                ));
            }
        }
        
        // All attempts failed
        throw $lastException;
    }
    
    /**
     * Default retry condition: retry on network/timeout errors
     * 
     * @param \Throwable $exception
     * @return bool
     */
    public static function isRetryableError(\Throwable $exception): bool {
        $message = $exception->getMessage();
        
        // Retry on network errors
        if (strpos($message, 'timeout') !== false ||
            strpos($message, 'connection') !== false ||
            strpos($message, 'network') !== false) {
            return true;
        }
        
        // Retry on 5xx errors (if it's an HTTP exception)
        if ($exception instanceof \GuzzleHttp\Exception\ServerException) {
            return true;
        }
        
        // Don't retry on 4xx errors (client errors)
        if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
            return false;
        }
        
        // Default: don't retry
        return false;
    }
}
