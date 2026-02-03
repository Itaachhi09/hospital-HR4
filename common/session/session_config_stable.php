<?php
/**
 * Stable Session Configuration
 * Reliable session management without aggressive timeouts
 */

// Prevent multiple session starts
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

// Only configure session settings if we're in a web context (not CLI)
if (php_sapi_name() !== 'cli') {
    // Configure session settings BEFORE session_start()
    $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // Set session cookie parameters - stable configuration
    session_set_cookie_params([
        'lifetime' => 0,                    // Session cookie (expires when browser closes)
        'path' => '/',                       // Available across entire domain
        'domain' => '',                      // Empty for localhost compatibility
        'secure' => false,                   // Allow HTTP for localhost development
        'httponly' => true,                  // Not accessible via JavaScript
        'samesite' => 'Lax'                 // Allow cookies on same-site redirects
    ]);

    // Set session name
    session_name('HOSPITAL_HR_SESSION');

    // Start the session
    session_start();

    // Only regenerate session ID if this is a completely new session
    if (!isset($_SESSION['session_started'])) {
        session_regenerate_id(true);
        $_SESSION['session_started'] = true;
        $_SESSION['created_at'] = time();
    }

    // Update last activity timestamp (but don't destroy session on timeout)
    $_SESSION['last_activity'] = time();
}

// Function to check if user is logged in
function is_user_logged_in() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

// Function to require authentication
function require_auth($redirect_to = 'index.php') {
    if (!is_user_logged_in()) {
        // Store the current URL to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect_to");
        exit();
    }
}

// Function to get current user data
function get_current_user_data() {
    if (!is_user_logged_in()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'employee_id' => $_SESSION['employee_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role_id' => $_SESSION['role_id'] ?? null,
        'role_name' => $_SESSION['role_name'] ?? null,
        'hmo_enrollment' => $_SESSION['hmo_enrollment'] ?? null
    ];
}

// Function to check session age (for debugging)
function get_session_age() {
    if (!isset($_SESSION['created_at'])) {
        return 'Unknown';
    }
    return time() - $_SESSION['created_at'];
}

// Function to check last activity (for debugging)
function get_last_activity() {
    if (!isset($_SESSION['last_activity'])) {
        return 'Unknown';
    }
    return time() - $_SESSION['last_activity'];
}
