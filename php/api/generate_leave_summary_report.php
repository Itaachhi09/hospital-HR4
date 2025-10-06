<?php
/**
 * Generate Leave Summary Report API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for leave summary report
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Leave Management module is disabled for HR3 integration',
    'module' => 'leave_management',
    'endpoint' => 'GET /php/api/generate_leave_summary_report.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original leave summary report implementation has been disabled
// It is preserved here for future HR3 integration reference
*/
?>