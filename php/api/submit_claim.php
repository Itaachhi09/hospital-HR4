<?php
/**
 * Submit Claim API - DISABLED FOR HR3 INTEGRATION
 * 
 * NOTE: This module has been disabled for HR3 integration.
 * Returns placeholder response indicating integration readiness.
 * Frontend components are preserved for future integration.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return placeholder response for claim submission
echo json_encode([
    'status' => 'integration_pending',
    'message' => 'Claims and Reimbursement module is disabled for HR3 integration',
    'module' => 'claims_reimbursement',
    'endpoint' => 'POST /php/api/submit_claim.php',
    'ready_for_integration' => true,
    'data' => []
]);

// ========================================
// ORIGINAL IMPLEMENTATION COMMENTED OUT
// ========================================

/*
// Original submit claim implementation has been disabled
// It is preserved here for future HR3 integration reference
*/
?>