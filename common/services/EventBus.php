<?php
/**
 * Event Bus Service
 * 
 * Implements publish-subscribe pattern for module communication.
 * Modules can publish events and subscribe to events from other modules.
 * 
 * Features:
 * - Event publishing
 * - Event subscription
 * - Event queuing (for async processing)
 * - Event history (for debugging)
 */

namespace Common\Services;

// Ensure EventBus class is available globally if needed
if (!class_exists('EventBus')) {
    class_alias(\Common\Services\EventBus::class, 'EventBus');
}

class EventBus {
    private static $subscribers = [];
    private static $eventQueue = [];
    private static $eventHistory = [];
    private static $isProcessing = false;
    private static $maxHistorySize = 1000;
    
    /**
     * Publish an event
     * 
     * @param string $eventName Event name (e.g., 'employee.created')
     * @param array $eventData Event payload
     * @param bool $async Whether to process asynchronously
     * @return void
     */
    public static function publish(string $eventName, array $eventData = [], bool $async = true): void {
        $event = [
            'name' => $eventName,
            'data' => $eventData,
            'timestamp' => time(),
            'id' => uniqid('evt_', true)
        ];
        
        // Add to history
        self::addToHistory($event);
        
        if ($async) {
            // Add to queue for async processing
            self::$eventQueue[] = $event;
            
            // Process queue if not already processing
            if (!self::$isProcessing) {
                self::processQueue();
            }
        } else {
            // Process synchronously
            self::dispatchEvent($event);
        }
    }
    
    /**
     * Subscribe to an event
     * 
     * @param string $eventName Event name to subscribe to
     * @param callable $callback Callback function
     * @param string|null $moduleName Module name (for logging)
     * @return void
     */
    public static function subscribe(string $eventName, callable $callback, ?string $moduleName = null): void {
        if (!isset(self::$subscribers[$eventName])) {
            self::$subscribers[$eventName] = [];
        }
        
        self::$subscribers[$eventName][] = [
            'callback' => $callback,
            'module' => $moduleName ?? 'unknown'
        ];
    }
    
    /**
     * Unsubscribe from an event
     * 
     * @param string $eventName Event name
     * @param callable|null $callback Specific callback to remove, or null to remove all
     * @return void
     */
    public static function unsubscribe(string $eventName, ?callable $callback = null): void {
        if (!isset(self::$subscribers[$eventName])) {
            return;
        }
        
        if ($callback === null) {
            unset(self::$subscribers[$eventName]);
        } else {
            self::$subscribers[$eventName] = array_filter(
                self::$subscribers[$eventName],
                function($subscriber) use ($callback) {
                    return $subscriber['callback'] !== $callback;
                }
            );
        }
    }
    
    /**
     * Dispatch event to subscribers
     * 
     * @param array $event Event data
     * @return void
     */
    private static function dispatchEvent(array $event): void {
        $eventName = $event['name'];
        
        if (!isset(self::$subscribers[$eventName])) {
            return;
        }
        
        foreach (self::$subscribers[$eventName] as $subscriber) {
            try {
                call_user_func($subscriber['callback'], $event['data'], $event);
            } catch (\Throwable $e) {
                // Log error but don't stop other subscribers
                error_log(sprintf(
                    "Event subscriber error [%s] in module [%s]: %s",
                    $eventName,
                    $subscriber['module'],
                    $e->getMessage()
                ));
            }
        }
    }
    
    /**
     * Process event queue
     * 
     * @return void
     */
    private static function processQueue(): void {
        if (self::$isProcessing || empty(self::$eventQueue)) {
            return;
        }
        
        self::$isProcessing = true;
        
        while (!empty(self::$eventQueue)) {
            $event = array_shift(self::$eventQueue);
            self::dispatchEvent($event);
        }
        
        self::$isProcessing = false;
    }
    
    /**
     * Add event to history
     * 
     * @param array $event Event data
     * @return void
     */
    private static function addToHistory(array $event): void {
        self::$eventHistory[] = $event;
        
        // Limit history size
        if (count(self::$eventHistory) > self::$maxHistorySize) {
            array_shift(self::$eventHistory);
        }
    }
    
    /**
     * Get event history
     * 
     * @param string|null $eventName Filter by event name
     * @param int $limit Limit number of results
     * @return array
     */
    public static function getHistory(?string $eventName = null, int $limit = 100): array {
        $history = self::$eventHistory;
        
        if ($eventName !== null) {
            $history = array_filter($history, function($event) use ($eventName) {
                return $event['name'] === $eventName;
            });
        }
        
        return array_slice(array_reverse($history), 0, $limit);
    }
    
    /**
     * Get list of subscribed events
     * 
     * @return array
     */
    public static function getSubscriptions(): array {
        $result = [];
        
        foreach (self::$subscribers as $eventName => $subscribers) {
            $result[$eventName] = array_map(function($sub) {
                return $sub['module'];
            }, $subscribers);
        }
        
        return $result;
    }
    
    /**
     * Clear event queue (useful for testing)
     * 
     * @return void
     */
    public static function clearQueue(): void {
        self::$eventQueue = [];
    }
    
    /**
     * Clear event history (useful for testing)
     * 
     * @return void
     */
    public static function clearHistory(): void {
        self::$eventHistory = [];
    }
}
