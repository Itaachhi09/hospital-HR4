<?php
// Compatibility shim: forward requests to get_hr_analytics_summary.php
// This file was added to maintain backward compatibility with front-end code
// that expects get_enhanced_hr_analytics.php. It simply includes the
// existing summary endpoint which returns a compatible JSON structure.

// Resolve the path relative to this file
$summaryPath = __DIR__ . '/get_hr_analytics_summary.php';

if (file_exists($summaryPath)) {
    // Include the summary script. It should echo JSON and handle inputs.
    include $summaryPath;
    return;
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "get_hr_analytics_summary.php not found on server"]);
    return;
}
