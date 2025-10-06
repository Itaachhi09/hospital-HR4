<?php
/**
 * Add Attendance API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for adding attendance
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Time & Attendance module is disabled for HR3 integration',
    'module' => 'time_attendance',
    'endpoint' => 'POST /php/api/add_attendance.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original add attendance implementation has been disabled
// It is preserved here for future HR3 integration reference
*/
?>