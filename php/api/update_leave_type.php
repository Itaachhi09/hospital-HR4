<?php
/**
 * Update Leave Type API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

// Return placeholder response for updating leave type
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Leave Management module is disabled for HR3 integration',
    'module' => 'leave_management',
    'endpoint' => 'PUT /php/api/update_leave_type.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original update leave type implementation has been disabled
// It is preserved here for future HR3 integration reference
*/
?>