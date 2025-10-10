<?php
/**
 * API Endpoint: Check Session
 * Validates current session and returns user information
 */

// Use simplified session configuration
require_once __DIR__ . '/../session_config_simple.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'GET method required.']);
    exit;
}

try {
    // Check if user is authenticated
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        // User has an active session
        http_response_code(200);
        echo json_encode([
            'logged_in' => true,
            'user' => [
                'user_id' => $_SESSION['user_id'],
                'employee_id' => $_SESSION['employee_id'] ?? null,
                'username' => $_SESSION['username'] ?? '',
                'full_name' => $_SESSION['full_name'] ?? '',
                'role_id' => $_SESSION['role_id'] ?? null,
                'role_name' => $_SESSION['role_name'] ?? '',
                'hmo_enrollment' => $_SESSION['hmo_enrollment'] ?? null
            ]
        ]);
    } else {
        // No active session
        http_response_code(200);
        echo json_encode([
            'logged_in' => false,
            'message' => 'No active session found.'
        ]);
    }
} catch (Exception $e) {
    error_log("Check Session API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while checking session.',
        'logged_in' => false
    ]);
}
?>

