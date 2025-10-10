<?php
/**
 * Error Handler Middleware
 * Handles and formats application errors
 */

class ErrorHandler {
    private $debugMode;

    public function __construct($debugMode = false) {
        $this->debugMode = $debugMode;
    }

    /**
     * Handle application errors
     */
    public function handle($error) {
        $this->logError($error);
        
        if ($this->debugMode) {
            $this->sendDebugError($error);
        } else {
            $this->sendUserFriendlyError();
        }
    }

    /**
     * Log error to file
     */
    private function logError($error) {
        $logMessage = date('Y-m-d H:i:s') . ' - ';
        
        if ($error instanceof Exception) {
            $logMessage .= 'Exception: ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine();
        } else {
            $logMessage .= 'Error: ' . $error;
        }
        
        $logMessage .= PHP_EOL;
        
        error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
    }

    /**
     * Send debug error response
     */
    private function sendDebugError($error) {
        http_response_code(500);
        
        $response = [
            'success' => false,
            'message' => 'Internal Server Error',
            'error' => [
                'type' => get_class($error),
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString()
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send user-friendly error response
     */
    private function sendUserFriendlyError() {
        http_response_code(500);
        
        $response = [
            'success' => false,
            'message' => 'An internal server error occurred. Please try again later.',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        exit;
    }

    /**
     * Handle fatal errors
     */
    public function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handle(new Exception($error['message'] . ' in ' . $error['file'] . ':' . $error['line']));
        }
    }

    /**
     * Set debug mode
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode;
    }
}

// Set up error handlers
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($exception) {
    $errorHandler = new ErrorHandler();
    $errorHandler->handle($exception);
});

register_shutdown_function(function() {
    $errorHandler = new ErrorHandler();
    $errorHandler->handleFatalError();
});
?>