<?php
/**
 * Session Authentication Check
 * Validates user authentication without destroying sessions
 * Use the centralized session configuration instead
 */

// Use stable session configuration
require_once __DIR__ . '/session_config_stable.php';

// Check if user is authenticated
if (!is_user_logged_in()) {
    // Redirect to login page WITHOUT destroying session
    header("Location: index.php");
    exit();
}

// Log successful session validation (optional, for auditing)
error_log("Session validated for user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
?>
