<?php
/**
 * Response Utility Class
 * Handles HTTP response formatting and status codes
 */

class Response {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $data = null) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Not Found') {
        self::error($message, 404);
    }

    /**
     * Send method not allowed response
     */
    public static function methodNotAllowed($message = 'Method Not Allowed') {
        self::error($message, 405);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation Error') {
        self::error($message, 422, ['errors' => $errors]);
    }

    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal Server Error') {
        self::error($message, 500);
    }

    /**
     * Send created response
     */
    public static function created($data = null, $message = 'Created') {
        self::success($data, $message, 201);
    }

    /**
     * Send no content response
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Success') {
        self::success([
            'items' => $data,
            'pagination' => $pagination
        ], $message);
    }

    /**
     * Send file download response
     */
    public static function download($filePath, $filename = null) {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
        }

        $filename = $filename ?: basename($filePath);
        $mimeType = mime_content_type($filePath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }

    /**
     * Send JSON response with custom headers
     */
    public static function json($data, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);
        
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>