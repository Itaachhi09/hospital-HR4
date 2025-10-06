<?php
/**
 * Get Leave Balances API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for leave balances
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Leave Management module is disabled for HR3 integration',
    'module' => 'leave_management',
    'endpoint' => 'GET /php/api/get_leave_balances.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original leave balances implementation has been disabled
// It is preserved here for future HR3 integration reference
*/
?>