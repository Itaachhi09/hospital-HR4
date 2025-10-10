<?php
/**
 * Centralized Session Configuration
 * Use this file to ensure consistent session settings across all pages
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

    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,                    // Session cookie (expires when browser closes)
        'path' => '/',                      // Available across entire domain
        'domain' => '',                     // Empty for localhost compatibility
        'secure' => $secureFlag,            // HTTPS only in production
        'httponly' => true,                 // Not accessible via JavaScript
        'samesite' => 'Lax'                // Allow cookies on same-site redirects (important for login flow)
    ]);

    // Set session name for better organization
    session_name('HOSPITAL_HR_SESSION');

    // Start the session
    session_start();

    // Session validation - prevent session fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created_at'] = time();
    }

    // Optional: Add session timeout (24 hours of inactivity)
    $session_timeout = 86400; // 24 hours in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        // Session expired due to inactivity
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['expired'] = true;
    }

    // Update last activity timestamp
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

