<?php
/**
 * Rate Limiting Middleware
 * 
 * Implements rate limiting to prevent API abuse.
 * Uses simple in-memory storage (for production, use Redis).
 */

namespace Common\Middlewares;

class RateLimitMiddleware {
    private static $requests = [];
    private $limit;
    private $window; // in seconds
    
    /**
     * Constructor
     * 
     * @param int $limit Maximum requests per window
     * @param int $window Time window in seconds
     */
    public function __construct(int $limit = 100, int $window = 60) {
        $this->limit = $limit;
        $this->window = $window;
    }
    
    /**
     * Check if request is within rate limit
     * 
     * @return bool
     */
    public function check(): bool {
        $key = $this->getClientKey();
        $now = time();
        
        // Initialize if not exists
        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = [];
        }
        
        // Remove old requests outside window
        self::$requests[$key] = array_filter(
            self::$requests[$key],
            function($timestamp) use ($now) {
                return ($now - $timestamp) < $this->window;
            }
        );
        
        // Check limit
        if (count(self::$requests[$key]) >= $this->limit) {
            return false;
        }
        
        // Add current request
        self::$requests[$key][] = $now;
        
        return true;
    }
    
    /**
     * Get client identifier for rate limiting
     * 
     * @return string
     */
    private function getClientKey(): string {
        // In production, use user ID from session/token
        // For now, use IP address
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get remaining requests
     * 
     * @return int
     */
    public function getRemaining(): int {
        $key = $this->getClientKey();
        
        if (!isset(self::$requests[$key])) {
            return $this->limit;
        }
        
        $now = time();
        self::$requests[$key] = array_filter(
            self::$requests[$key],
            function($timestamp) use ($now) {
                return ($now - $timestamp) < $this->window;
            }
        );
        
        return max(0, $this->limit - count(self::$requests[$key]));
    }
}
