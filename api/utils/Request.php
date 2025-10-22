<?php
/**
 * Request Utility Class
 * Handles HTTP request parsing and validation
 */

class Request {
    /**
     * Get the request path
     */
    public static function getPath() {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get JSON request body
     */
    public static function getJsonBody() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * Get query parameters
     */
    public static function getQueryParams() {
        return $_GET;
    }

    /**
     * Get a specific query parameter
     */
    public static function getQueryParam($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    public static function getPostData() {
        return $_POST;
    }

    /**
     * Get a specific POST parameter
     */
    public static function getPostParam($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get request method
     */
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request headers
     */
    public static function getHeaders() {
        return getallheaders();
    }

    /**
     * Get a specific header
     */
    public static function getHeader($name) {
        $headers = getallheaders();
        return $headers[$name] ?? null;
    }

    /**
     * Validate required fields
     */
    public static function validateRequired($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize a single string value (alias for sanitize)
     */
    public static function sanitizeString($value) {
        return self::sanitize($value);
    }

    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get pagination parameters from query string
     * Returns array with page, limit, offset
     */
    public static function getPagination($defaultLimit = 20, $maxLimit = 100) {
        $page = max(1, (int)(self::getQueryParam('page', 1)));
        $limit = min($maxLimit, max(1, (int)(self::getQueryParam('limit', $defaultLimit))));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Get all request data (combines GET, POST, and JSON body)
     * Prioritizes JSON body over POST over GET
     */
    public static function getData() {
        $data = [];
        
        // Start with GET parameters
        $data = array_merge($data, $_GET);
        
        // Override with POST parameters
        $data = array_merge($data, $_POST);
        
        // Override with JSON body if present
        $jsonBody = self::getJsonBody();
        if ($jsonBody && is_array($jsonBody)) {
            $data = array_merge($data, $jsonBody);
        }
        
        return $data;
    }

    /**
     * Get a specific data parameter (checks GET, POST, and JSON body)
     */
    public static function get($key, $default = null) {
        $data = self::getData();
        return $data[$key] ?? $default;
    }
}
?>