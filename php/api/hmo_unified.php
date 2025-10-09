<?php
/**
 * Unified HMO API Bridge
 * Provides backward compatibility while using the new unified HMO routes
 */

// Suppress error output to prevent HTML in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once __DIR__ . '/../../api/config.php';
    require_once __DIR__ . '/../../api/utils/Response.php';
    require_once __DIR__ . '/../../api/utils/Request.php';
    require_once __DIR__ . '/../../api/middlewares/AuthMiddleware.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load required files: ' . $e->getMessage()
    ]);
    exit;
}

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the requested endpoint from the URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract the HMO endpoint
$hmoEndpoint = end($pathParts);
$hmoEndpoint = str_replace('.php', '', $hmoEndpoint);

try {
// Initialize authentication
$authMiddleware = new AuthMiddleware();
    $authenticated = $authMiddleware->authenticate();

    if (!$authenticated) {
        ob_end_clean();
    Response::unauthorized('Authentication required');
}

    // Get current user data
    $user = $authMiddleware->getCurrentUser();

    // Clear any buffered output before routing
    ob_end_clean();

// Route to appropriate handler based on endpoint
switch ($hmoEndpoint) {
    case 'hmo_claims':
        handleClaimsEndpoint();
        break;
    case 'hmo_providers':
        handleProvidersEndpoint();
        break;
    case 'hmo_plans':
        handlePlansEndpoint();
        break;
    case 'hmo_enrollments':
        handleEnrollmentsEndpoint();
        break;
    case 'hmo_dashboard':
        handleDashboardEndpoint();
        break;
    case 'get_employee_enrollments':
        handleEmployeeEnrollmentsEndpoint();
        break;
    default:
        Response::notFound('HMO endpoint not found');
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("HMO Unified API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}

/**
 * Handle claims endpoint
 */
function handleClaimsEndpoint() {
    global $pdo, $user;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $request = new Request();
    
    try {
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    getClaimById($_GET['id'], $user);
                } elseif (isset($_GET['mode']) && $_GET['mode'] === 'history') {
                    getClaimHistory($user);
                } else {
                    getAllClaims($user);
                }
                break;
            case 'POST':
                createClaim($request, $user);
                break;
            case 'PUT':
            case 'PATCH':
                updateClaim($request, $user);
                break;
            case 'DELETE':
                deleteClaim($_GET['id'] ?? null, $user);
                break;
            default:
                Response::methodNotAllowed();
        }
    } catch (Exception $e) {
        Response::error('Claims operation failed: ' . $e->getMessage());
    }
}

/**
 * Get all claims
 */
function getAllClaims($user) {
    global $pdo;
    
    $sql = "SELECT 
                c.*,
                e.EmployeeID,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM hmoclaims c
            JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
            JOIN employees emp ON enr.EmployeeID = emp.EmployeeID
            LEFT JOIN employees e ON c.EmployeeID = e.EmployeeID
            JOIN hmoplans p ON enr.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE 1=1";
    
    $params = [];
    
    // Role-based filtering
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        $sql .= " AND enr.EmployeeID = :employee_id";
        $params[':employee_id'] = $user['employee_id'];
    }
    
    $sql .= " ORDER BY c.ClaimDate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalize attachments
    foreach ($claims as &$claim) {
        $attachments = $claim['Attachments'] ?? null;
        if ($attachments === null || $attachments === '') {
            $claim['Attachments'] = [];
        } else {
            $decoded = json_decode($attachments, true);
            $claim['Attachments'] = is_array($decoded) ? $decoded : [];
        }
    }
    
    Response::success(['claims' => $claims]);
}

/**
 * Get claim by ID
 */
function getClaimById($id, $user) {
    global $pdo;
    
    $sql = "SELECT 
                c.*,
                e.EmployeeID,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM hmoclaims c
            JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
            JOIN employees emp ON enr.EmployeeID = emp.EmployeeID
            LEFT JOIN employees e ON c.EmployeeID = e.EmployeeID
            JOIN hmoplans p ON enr.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE c.ClaimID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$claim) {
        Response::notFound('Claim not found');
    }
    
    // Role-based access control
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        if ($claim['EmployeeID'] != $user['employee_id']) {
            Response::forbidden('Access denied');
        }
    }
    
    // Normalize attachments
    $attachments = $claim['Attachments'] ?? null;
    if ($attachments === null || $attachments === '') {
        $claim['Attachments'] = [];
    } else {
        $decoded = json_decode($attachments, true);
        $claim['Attachments'] = is_array($decoded) ? $decoded : [];
    }
    
    Response::success(['claim' => $claim]);
}

/**
 * Get claim history with filters
 */
function getClaimHistory($user) {
    global $pdo;
    
    $employeeId = $_GET['employee_id'] ?? 0;
    $status = $_GET['status'] ?? '';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    
    $sql = "SELECT 
                c.*,
                e.EmployeeID,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM hmoclaims c
            JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
            JOIN employees emp ON enr.EmployeeID = emp.EmployeeID
            LEFT JOIN employees e ON c.EmployeeID = e.EmployeeID
            JOIN hmoplans p ON enr.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE 1=1";
    
    $params = [];
    
    if ($employeeId > 0) {
        $sql .= " AND enr.EmployeeID = :employee_id";
        $params[':employee_id'] = $employeeId;
    }
    
    if ($status !== '') {
        $sql .= " AND c.Status = :status";
        $params[':status'] = $status;
    }
    
    if ($from !== '') {
        $sql .= " AND c.ClaimDate >= :from";
        $params[':from'] = $from;
    }
    
    if ($to !== '') {
        $sql .= " AND c.ClaimDate <= :to";
        $params[':to'] = $to;
    }
    
    // Role-based filtering
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        $sql .= " AND enr.EmployeeID = :user_employee_id";
        $params[':user_employee_id'] = $user['employee_id'];
    }
    
    $sql .= " ORDER BY c.ClaimDate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['claims' => $claims]);
}

/**
 * Create new claim
 */
function createClaim($request, $user) {
    global $pdo;
    
    $data = $request->getData();
    
    // Validate required fields
    $requiredFields = ['enrollment_id', 'claim_date', 'claim_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Response::error("Missing required field: $field");
        }
    }
    
    // Generate claim number
    $claimNumber = 'CLM-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Build description with hospital/clinic if provided
    $description = $data['description'] ?? '';
    if (!empty($data['hospital_clinic'])) {
        $description = "Hospital/Clinic: " . $data['hospital_clinic'] . "\n" . $description;
    }
    if (!empty($data['diagnosis'])) {
        $description .= "\nDiagnosis: " . $data['diagnosis'];
    }
    
    $sql = "INSERT INTO hmoclaims (
                EnrollmentID, EmployeeID, ClaimNumber, ClaimType, 
                ProviderName, Description, Amount, ClaimDate, 
                SubmittedDate, Status, Comments
            ) VALUES (
                :enrollment_id, :employee_id, :claim_number, :claim_type,
                :provider_name, :description, :amount, :claim_date,
                NOW(), :status, :comments
            )";
    
    $params = [
        ':enrollment_id' => $data['enrollment_id'],
        ':employee_id' => $user['employee_id'],
        ':claim_number' => $claimNumber,
        ':claim_type' => $data['claim_type'] ?? 'Medical',
        ':provider_name' => $data['provider_name'] ?? $data['hospital_clinic'] ?? '',
        ':description' => trim($description),
        ':amount' => $data['claim_amount'],
        ':claim_date' => $data['claim_date'],
        ':status' => 'Submitted',
        ':comments' => $data['remarks'] ?? ''
    ];
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        $claimId = $pdo->lastInsertId();
        Response::success(['message' => 'Claim created successfully', 'claim_id' => $claimId]);
    } else {
        Response::error('Failed to create claim');
    }
}

/**
 * Update claim
 */
function updateClaim($request, $user) {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        Response::error('Claim ID required');
    }
    
    $data = $request->getData();
    
    // Check if user can update this claim
    $sql = "SELECT c.*, enr.EmployeeID 
            FROM hmoclaims c
            JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
            WHERE c.ClaimID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$claim) {
        Response::notFound('Claim not found');
    }
    
    // Role-based access control
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        if ($claim['EmployeeID'] != $user['employee_id']) {
            Response::forbidden('Access denied');
        }
    }
    
    // Build update query
    $updateFields = [];
    $params = [':id' => $id];
    
    // Map request field names to database column names
    $fieldMapping = [
        'enrollment_id' => 'EnrollmentID',
        'claim_type' => 'ClaimType',
        'provider_name' => 'ProviderName',
        'amount' => 'Amount',
        'claim_date' => 'ClaimDate',
        'claim_amount' => 'Amount'  // Map claim_amount to Amount
    ];
    
    foreach ($fieldMapping as $requestField => $dbColumn) {
        if (isset($data[$requestField])) {
            $updateFields[] = "$dbColumn = :$requestField";
            $params[":$requestField"] = $data[$requestField];
        }
    }
    
    // Handle description updates (combine with hospital/diagnosis if provided)
    if (isset($data['description']) || isset($data['hospital_clinic']) || isset($data['diagnosis'])) {
        $descriptionText = $data['description'] ?? '';
        if (!empty($data['hospital_clinic'])) {
            $descriptionText = "Hospital/Clinic: " . $data['hospital_clinic'] . "\n" . $descriptionText;
        }
        if (!empty($data['diagnosis'])) {
            $descriptionText .= "\nDiagnosis: " . $data['diagnosis'];
        }
        $updateFields[] = "Description = :description";
        $params[":description"] = trim($descriptionText);
    }
    
    // Handle status update (map claim_status to Status)
    if (isset($data['claim_status'])) {
        $updateFields[] = "Status = :status";
        $params[":status"] = $data['claim_status'];
    }
    
    // Handle remarks/comments
    if (isset($data['remarks'])) {
        $updateFields[] = "Comments = :comments";
        $params[":comments"] = $data['remarks'];
    }
    
    if (empty($updateFields)) {
        Response::error('No fields to update');
    }
    
    $sql = "UPDATE hmoclaims SET " . implode(', ', $updateFields) . ", UpdatedAt = NOW() WHERE ClaimID = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        Response::success(['message' => 'Claim updated successfully']);
    } else {
        Response::error('Failed to update claim');
    }
}

/**
 * Delete claim
 */
function deleteClaim($id, $user) {
    global $pdo;
    
    if (!$id) {
        Response::error('Claim ID required');
    }
    
    // Check if user can delete this claim
    $sql = "SELECT c.*, enr.EmployeeID 
            FROM hmoclaims c
            JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
            WHERE c.ClaimID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $claim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$claim) {
        Response::notFound('Claim not found');
    }
    
    // Only admins can delete claims
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        Response::forbidden('Access denied');
    }
    
    $sql = "DELETE FROM hmoclaims WHERE ClaimID = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        Response::success(['message' => 'Claim deleted successfully']);
    } else {
        Response::error('Failed to delete claim');
    }
}

/**
 * Handle providers endpoint
 */
function handleProvidersEndpoint() {
    global $pdo, $user;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $request = new Request();
    
    try {
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    getProviderById($_GET['id']);
                } else {
                    getAllProviders();
                }
                break;
            case 'POST':
                createProvider($request);
                break;
            case 'PUT':
            case 'PATCH':
                updateProvider($request);
                break;
            case 'DELETE':
                deleteProvider($_GET['id'] ?? null);
                break;
            default:
                Response::methodNotAllowed();
        }
    } catch (Exception $e) {
        Response::error('Providers operation failed: ' . $e->getMessage());
    }
}

/**
 * Get all providers
 */
function getAllProviders() {
    global $pdo;
    
    $sql = "SELECT * FROM hmoproviders ORDER BY ProviderName";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['providers' => $providers]);
}

/**
 * Get provider by ID
 */
function getProviderById($id) {
    global $pdo;
    
    $sql = "SELECT * FROM hmoproviders WHERE ProviderID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$provider) {
        Response::notFound('Provider not found');
    }
    
    Response::success(['provider' => $provider]);
}

/**
 * Create new provider
 */
function createProvider($request) {
    global $pdo;
    
    $data = $request->getData();
    
    // Validate required fields
    if (!isset($data['provider_name']) || empty($data['provider_name'])) {
        Response::error('Provider name is required');
    }
    
    $sql = "INSERT INTO hmoproviders (
                ProviderName, Description, ContactPerson, Email, 
                IsActive, CreatedAt
            ) VALUES (
                :provider_name, :description, :contact_person,
                :email, :is_active, NOW()
            )";
    
    // Convert status to IsActive boolean (1 or 0)
    $isActive = (!isset($data['status']) || $data['status'] === 'Active' || $data['status'] === '1' || $data['status'] === 1) ? 1 : 0;
    
    $params = [
        ':provider_name' => $data['provider_name'],
        ':description' => $data['description'] ?? '',
        ':contact_person' => $data['contact_person'] ?? '',
        ':email' => $data['email'] ?? '',
        ':is_active' => $isActive
    ];
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        $providerId = $pdo->lastInsertId();
        Response::success(['message' => 'Provider created successfully', 'provider_id' => $providerId]);
    } else {
        Response::error('Failed to create provider');
    }
}

/**
 * Update provider
 */
function updateProvider($request) {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        Response::error('Provider ID required');
    }
    
    $data = $request->getData();
    
    // Build update query
    $updateFields = [];
    $params = [':id' => $id];
    
    // Map request field names to database column names
    $fieldMapping = [
        'provider_name' => 'ProviderName',
        'description' => 'Description',
        'contact_person' => 'ContactPerson',
        'contact_number' => 'ContactNumber',
        'email' => 'Email'
    ];
    
    foreach ($fieldMapping as $requestField => $dbColumn) {
        if (isset($data[$requestField])) {
            $updateFields[] = "$dbColumn = :$requestField";
            $params[":$requestField"] = $data[$requestField];
        }
    }
    
    // Handle status -> IsActive conversion
    if (isset($data['status'])) {
        $updateFields[] = "IsActive = :is_active";
        $params[":is_active"] = ($data['status'] === 'Active' || $data['status'] === '1' || $data['status'] === 1) ? 1 : 0;
    }
    
    if (empty($updateFields)) {
        Response::error('No fields to update');
    }
    
    $sql = "UPDATE hmoproviders SET " . implode(', ', $updateFields) . ", UpdatedAt = NOW() WHERE ProviderID = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        Response::success(['message' => 'Provider updated successfully']);
    } else {
        Response::error('Failed to update provider');
    }
}

/**
 * Delete provider
 */
function deleteProvider($id) {
    global $pdo;
    
    if (!$id) {
        Response::error('Provider ID required');
    }
    
    // Check if provider has associated plans
    $sql = "SELECT COUNT(*) as plan_count FROM hmoplans WHERE ProviderID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['plan_count'] > 0) {
        Response::error('Cannot delete provider with associated plans');
    }
    
    $sql = "DELETE FROM hmoproviders WHERE ProviderID = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        Response::success(['message' => 'Provider deleted successfully']);
    } else {
        Response::error('Failed to delete provider');
    }
}

/**
 * Handle plans endpoint
 */
function handlePlansEndpoint() {
    global $pdo, $user;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $request = new Request();
    
    try {
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    getPlanById($_GET['id']);
                } else {
                    getAllPlans();
                }
                break;
            case 'POST':
                createPlan($request);
                break;
            case 'PUT':
            case 'PATCH':
                updatePlan($request);
                break;
            case 'DELETE':
                deletePlan($_GET['id'] ?? null);
                break;
            default:
                Response::methodNotAllowed();
        }
    } catch (Exception $e) {
        Response::error('Plans operation failed: ' . $e->getMessage());
    }
}

/**
 * Get all plans
 */
function getAllPlans() {
    global $pdo;
    
    $sql = "SELECT p.*, pr.ProviderName 
            FROM hmoplans p
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            ORDER BY p.PlanName";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['plans' => $plans]);
}

/**
 * Get plan by ID
 */
function getPlanById($id) {
    global $pdo;
    
    $sql = "SELECT p.*, pr.ProviderName 
            FROM hmoplans p
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE p.PlanID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        Response::notFound('Plan not found');
    }
    
    Response::success(['plan' => $plan]);
}

/**
 * Create new plan
 */
function createPlan($request) {
    global $pdo;
    
    $data = $request->getData();
    
    // Validate required fields
    if (!isset($data['provider_id']) || empty($data['provider_id'])) {
        Response::error('Provider ID is required');
    }
    if (!isset($data['plan_name']) || empty($data['plan_name'])) {
        Response::error('Plan name is required');
    }
    
    $sql = "INSERT INTO hmoplans (
                ProviderID, PlanName, MonthlyPremium, Coverage, AccreditedHospitals,
                MaximumBenefitLimit, IsActive, CreatedAt
            ) VALUES (
                :provider_id, :plan_name, :monthly_premium, :coverage, :accredited_hospitals,
                :maximum_benefit_limit, :is_active, NOW()
            )";
    
    // Convert status to IsActive boolean (1 or 0)
    $isActive = (!isset($data['status']) || $data['status'] === 'Active' || $data['status'] === '1' || $data['status'] === 1) ? 1 : 0;
    
    $params = [
        ':provider_id' => $data['provider_id'],
        ':plan_name' => $data['plan_name'],
        ':monthly_premium' => $data['premium_cost'] ?? $data['monthly_premium'] ?? 0,
        ':coverage' => isset($data['coverage']) ? json_encode($data['coverage']) : null,
        ':accredited_hospitals' => isset($data['accredited_hospitals']) ? json_encode($data['accredited_hospitals']) : null,
        ':maximum_benefit_limit' => $data['maximum_benefit_limit'] ?? null,
        ':is_active' => $isActive
    ];
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        $planId = $pdo->lastInsertId();
        Response::success(['message' => 'Plan created successfully', 'plan_id' => $planId]);
    } else {
        Response::error('Failed to create plan');
    }
}

/**
 * Update plan
 */
function updatePlan($request) {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        Response::error('Plan ID required');
    }
    
    $data = $request->getData();
    
    // Build update query
    $updateFields = [];
    $params = [':id' => $id];
    
    // Map request field names to database column names
    $fieldMapping = [
        'provider_id' => 'ProviderID',
        'plan_name' => 'PlanName',
        'coverage' => 'Coverage',
        'accredited_hospitals' => 'AccreditedHospitals',
        'maximum_benefit_limit' => 'MaximumBenefitLimit',
        'premium_cost' => 'MonthlyPremium',
        'monthly_premium' => 'MonthlyPremium'
    ];
    
    foreach ($fieldMapping as $requestField => $dbColumn) {
        if (isset($data[$requestField])) {
            if ($requestField === 'coverage' || $requestField === 'accredited_hospitals') {
                $updateFields[] = "$dbColumn = :$requestField";
                $params[":$requestField"] = json_encode($data[$requestField]);
            } else {
                $updateFields[] = "$dbColumn = :$requestField";
                $params[":$requestField"] = $data[$requestField];
            }
        }
    }
    
    // Handle status -> IsActive conversion
    if (isset($data['status'])) {
        $updateFields[] = "IsActive = :is_active";
        $params[":is_active"] = ($data['status'] === 'Active' || $data['status'] === '1' || $data['status'] === 1) ? 1 : 0;
    }
    
    if (empty($updateFields)) {
        Response::error('No fields to update');
    }
    
    $sql = "UPDATE hmoplans SET " . implode(', ', $updateFields) . ", UpdatedAt = NOW() WHERE PlanID = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        Response::success(['message' => 'Plan updated successfully']);
    } else {
        Response::error('Failed to update plan');
    }
}

/**
 * Delete plan
 */
function deletePlan($id) {
    global $pdo;
    
    if (!$id) {
        Response::error('Plan ID required');
    }
    
    // Check if plan has associated enrollments
    $sql = "SELECT COUNT(*) as enrollment_count FROM employeehmoenrollments WHERE PlanID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['enrollment_count'] > 0) {
        Response::error('Cannot delete plan with associated enrollments');
    }
    
    $sql = "DELETE FROM hmoplans WHERE PlanID = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        Response::success(['message' => 'Plan deleted successfully']);
    } else {
        Response::error('Failed to delete plan');
    }
}

/**
 * Handle enrollments endpoint
 */
function handleEnrollmentsEndpoint() {
    global $pdo, $user;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $request = new Request();
    
    try {
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    getEnrollmentById($_GET['id'], $user);
                } else {
                    getAllEnrollments($user);
                }
                break;
            case 'POST':
                createEnrollment($request, $user);
                break;
            case 'PUT':
            case 'PATCH':
                updateEnrollment($request, $user);
                break;
            case 'DELETE':
                deleteEnrollment($_GET['id'] ?? null, $user);
                break;
            default:
                Response::methodNotAllowed();
        }
    } catch (Exception $e) {
        Response::error('Enrollments operation failed: ' . $e->getMessage());
    }
}

/**
 * Get all enrollments
 */
function getAllEnrollments($user) {
    global $pdo;
    
    $sql = "SELECT 
                e.*,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM employeehmoenrollments e
            JOIN employees emp ON e.EmployeeID = emp.EmployeeID
            JOIN hmoplans p ON e.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE 1=1";
    
    $params = [];
    
    // Role-based filtering
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        $sql .= " AND e.EmployeeID = :employee_id";
        $params[':employee_id'] = $user['employee_id'];
    }
    
    $sql .= " ORDER BY e.StartDate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['enrollments' => $enrollments]);
}

/**
 * Get enrollment by ID
 */
function getEnrollmentById($id, $user) {
    global $pdo;
    
    $sql = "SELECT 
                e.*,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM employeehmoenrollments e
            JOIN employees emp ON e.EmployeeID = emp.EmployeeID
            JOIN hmoplans p ON e.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE e.EnrollmentID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        Response::notFound('Enrollment not found');
    }
    
    // Role-based access control
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        if ($enrollment['EmployeeID'] != $user['employee_id']) {
            Response::forbidden('Access denied');
        }
    }
    
    Response::success(['enrollment' => $enrollment]);
}

/**
 * Create new enrollment
 */
function createEnrollment($request, $user) {
    global $pdo;
    
    $data = $request->getData();
    
    // Validate required fields
    $requiredFields = ['employee_id', 'plan_id', 'start_date'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Response::error("Missing required field: $field");
        }
    }
    
    $sql = "INSERT INTO employeehmoenrollments (
                EmployeeID, PlanID, StartDate, EndDate, Status, CreatedAt
            ) VALUES (
                :employee_id, :plan_id, :start_date, :end_date, :status, NOW()
            )";
    
    $params = [
        ':employee_id' => $data['employee_id'],
        ':plan_id' => $data['plan_id'],
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'] ?? null,
        ':status' => $data['status'] ?? 'Active'
    ];
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        $enrollmentId = $pdo->lastInsertId();
        Response::success(['message' => 'Enrollment created successfully', 'enrollment_id' => $enrollmentId]);
    } else {
        Response::error('Failed to create enrollment');
    }
}

/**
 * Update enrollment
 */
function updateEnrollment($request, $user) {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        Response::error('Enrollment ID required');
    }
    
    $data = $request->getData();
    
    // Check if user can update this enrollment
    $sql = "SELECT EmployeeID FROM employeehmoenrollments WHERE EnrollmentID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        Response::notFound('Enrollment not found');
    }
    
    // Role-based access control
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        if ($enrollment['EmployeeID'] != $user['employee_id']) {
            Response::forbidden('Access denied');
        }
    }
    
    // Build update query
    $updateFields = [];
    $params = [':id' => $id];
    
    // Map request field names to database column names
    $fieldMapping = [
        'employee_id' => 'EmployeeID',
        'plan_id' => 'PlanID',
        'start_date' => 'StartDate',
        'end_date' => 'EndDate',
        'status' => 'Status'
    ];
    
    foreach ($fieldMapping as $requestField => $dbColumn) {
        if (isset($data[$requestField])) {
            $updateFields[] = "$dbColumn = :$requestField";
            $params[":$requestField"] = $data[$requestField];
        }
    }
    
    if (empty($updateFields)) {
        Response::error('No fields to update');
    }
    
    $sql = "UPDATE employeehmoenrollments SET " . implode(', ', $updateFields) . ", UpdatedAt = NOW() WHERE EnrollmentID = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        Response::success(['message' => 'Enrollment updated successfully']);
    } else {
        Response::error('Failed to update enrollment');
    }
}

/**
 * Delete enrollment
 */
function deleteEnrollment($id, $user) {
    global $pdo;
    
    if (!$id) {
        Response::error('Enrollment ID required');
    }
    
    // Check if user can delete this enrollment
    $sql = "SELECT EmployeeID FROM employeehmoenrollments WHERE EnrollmentID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        Response::notFound('Enrollment not found');
    }
    
    // Only admins can delete enrollments
    if ($user['role_name'] !== 'System Admin' && $user['role_name'] !== 'HR Admin') {
        Response::forbidden('Access denied');
    }
    
    // Check if enrollment has associated claims
    $sql = "SELECT COUNT(*) as claim_count FROM hmoclaims WHERE EnrollmentID = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['claim_count'] > 0) {
        Response::error('Cannot delete enrollment with associated claims');
    }
    
    $sql = "DELETE FROM employeehmoenrollments WHERE EnrollmentID = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        Response::success(['message' => 'Enrollment deleted successfully']);
    } else {
        Response::error('Failed to delete enrollment');
    }
}

/**
 * Handle dashboard endpoint
 */
function handleDashboardEndpoint() {
    global $pdo;
    
    // Verify PDO connection is available
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Dashboard Error: PDO connection not available");
        Response::error('Database connection not available', 500);
        return;
    }
    
    $mode = $_GET['mode'] ?? 'summary';
    
    try {
        switch ($mode) {
            case 'summary':
                getDashboardSummary();
                break;
            case 'monthly_claims':
                getMonthlyClaimsData();
                break;
            case 'top_hospitals':
                getTopHospitalsData();
                break;
            case 'plan_utilization':
                getPlanUtilizationData();
                break;
            default:
                Response::error('Invalid dashboard mode');
        }
    } catch (PDOException $e) {
        error_log("Dashboard PDO Error: " . $e->getMessage());
        Response::error('Database query failed: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        error_log("Dashboard Error: " . $e->getMessage());
        Response::error('Dashboard operation failed: ' . $e->getMessage(), 500);
    }
}

/**
 * Get dashboard summary
 */
function getDashboardSummary() {
    global $pdo;
    
    // Get total active providers (using IsActive column)
    $sql = "SELECT COUNT(*) as total FROM hmoproviders WHERE IsActive = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $providers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total active plans (using IsActive column)
    $sql = "SELECT COUNT(*) as total FROM hmoplans WHERE IsActive = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $plans = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total enrolled employees
    $sql = "SELECT COUNT(*) as total FROM employeehmoenrollments WHERE Status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get claims summary
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 'Submitted' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as denied
            FROM hmoclaims";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $claims = $stmt->fetch(PDO::FETCH_ASSOC);
    
    Response::success([
        'summary' => [
            'total_active_providers' => $providers,
            'total_active_plans' => $plans,
            'total_enrolled_employees' => $enrollments,
            'claims' => $claims
        ]
    ]);
}

/**
 * Get monthly claims data
 */
function getMonthlyClaimsData() {
    global $pdo;
    
    $sql = "SELECT 
                DATE_FORMAT(ClaimDate, '%Y-%m') as ym,
                COUNT(*) as cnt
            FROM hmoclaims
            WHERE ClaimDate >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(ClaimDate, '%Y-%m')
            ORDER BY ym";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $monthlyClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['monthly_claims' => $monthlyClaims]);
}

/**
 * Get top hospitals data
 */
function getTopHospitalsData() {
    global $pdo;
    
    // Use ProviderName as proxy for hospital/clinic since HospitalClinic column doesn't exist
    $sql = "SELECT 
                ProviderName as hospital,
                COUNT(*) as cnt
            FROM hmoclaims
            WHERE ProviderName IS NOT NULL AND ProviderName != ''
            GROUP BY ProviderName
            ORDER BY cnt DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $topHospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['top_hospitals' => $topHospitals]);
}

/**
 * Get plan utilization data
 */
function getPlanUtilizationData() {
    global $pdo;
    
    $sql = "SELECT 
                p.PlanName,
                COUNT(e.EnrollmentID) as enrolled
            FROM hmoplans p
            LEFT JOIN employeehmoenrollments e ON p.PlanID = e.PlanID AND e.Status = 'Active'
            WHERE p.IsActive = 1
            GROUP BY p.PlanID, p.PlanName
            ORDER BY enrolled DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $planUtilization = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['plan_utilization' => $planUtilization]);
}

/**
 * Handle employee enrollments endpoint
 */
function handleEmployeeEnrollmentsEndpoint() {
    global $pdo, $user;
    
    $sql = "SELECT 
                e.*,
                emp.FirstName,
                emp.LastName,
                emp.EmployeeNumber,
                p.PlanName,
                pr.ProviderName,
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName
            FROM employeehmoenrollments e
            JOIN employees emp ON e.EmployeeID = emp.EmployeeID
            JOIN hmoplans p ON e.PlanID = p.PlanID
            JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
            WHERE e.EmployeeID = :employee_id
            ORDER BY e.StartDate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':employee_id' => $user['employee_id']]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success(['enrollments' => $enrollments]);
}
?>
