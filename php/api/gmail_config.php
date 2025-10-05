<?php
/**
 * Gmail Configuration for H Vill Hospital HR System
 * This file sets the Gmail credentials for 2FA emails
 */

// Load Gmail credentials from environment. Do NOT store plaintext credentials in the repo.
// For local development you can create a .env file with GMAIL_USER and GMAIL_APP_PASSWORD and it will be loaded here.

// Try to load .env if the vlucas/phpdotenv library is available and a .env file exists one level up.
$dotenvPath = __DIR__ . '/../../.env';
if (file_exists($dotenvPath) && class_exists('Dotenv\Dotenv')) {
	try {
		$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
		$dotenv->load();
	} catch (Exception $e) {
		// ignore dotenv errors; environment variables may already be set
	}
}

// Do not set credentials here. Expect them in the environment or .env.
// Example (for local dev only):
// GMAIL_USER=your-email@gmail.com
// GMAIL_APP_PASSWORD=your-app-password

// Ensure nothing is echoed to avoid breaking JSON API responses.
return;
?>