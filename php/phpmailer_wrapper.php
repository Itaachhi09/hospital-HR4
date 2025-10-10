<?php
/**
 * PHPMailer Wrapper
 * Provides proper autoloading and class definitions for PHPMailer
 */

// Ensure Composer autoloader is loaded
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback: require PHPMailer classes directly
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
}

// Ensure classes are available
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    throw new Exception('PHPMailer class not found. Please run: composer install');
}

if (!class_exists('PHPMailer\\PHPMailer\\Exception')) {
    throw new Exception('PHPMailer Exception class not found. Please run: composer install');
}

// Export classes for use
class_alias('PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
class_alias('PHPMailer\\PHPMailer\\Exception', 'PHPMailerException');
