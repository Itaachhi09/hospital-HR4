<?php
/**
 * API Endpoint: Manage HR Structure
 * Handles CRUD operations for HR divisions, job roles, and department coordinators
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../db_connect.php';
require_once '../auth_check.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Check authentication and authorization
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Check if user has HR management permissions
$allowedRoles = ['System Admin', 'HR Admin', 'Hospital Director', 'HR Director'];
if (!in_array($user['RoleName'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$entity = $_GET['entity'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($entity, $pdo);
            break;
            
        case 'POST':
            handlePostRequest($entity, $pdo, $user);
            break;
            
        case 'PUT':
            handlePutRequest($entity, $pdo, $user);
            break;
            
        case 'DELETE':
            handleDeleteRequest($entity, $pdo, $user);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in manage_hr_structure.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred']);
}

function handleGetRequest($entity, $pdo) {
    // Already handled by get_hospital_org_structure.php
    echo json_encode(['message' => 'Use get_hospital_org_structure.php for GET requests']);
}

function handlePostRequest($entity, $pdo, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($entity) {
        case 'division':
            createHRDivision($data, $pdo, $user);
            break;
            
        case 'role':
            createJobRole($data, $pdo, $user);
            break;
            
        case 'coordinator':
            assignDepartmentCoordinator($data, $pdo, $user);
            break;
            
        case 'department':
            createDepartment($data, $pdo, $user);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity type']);
            break;
    }
}

function handlePutRequest($entity, $pdo, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID required for update']);
        return;
    }
    
    switch ($entity) {
        case 'division':
            updateHRDivision($id, $data, $pdo, $user);
            break;
            
        case 'role':
            updateJobRole($id, $data, $pdo, $user);
            break;
            
        case 'coordinator':
            updateDepartmentCoordinator($id, $data, $pdo, $user);
            break;
            
        case 'department':
            updateDepartment($id, $data, $pdo, $user);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity type']);
            break;
    }
}

function handleDeleteRequest($entity, $pdo, $user) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID required for deletion']);
        return;
    }
    
    switch ($entity) {
        case 'division':
            deleteHRDivision($id, $pdo, $user);
            break;
            
        case 'role':
            deleteJobRole($id, $pdo, $user);
            break;
            
        case 'coordinator':
            removeDepartmentCoordinator($id, $pdo, $user);
            break;
            
        case 'department':
            deleteDepartment($id, $pdo, $user);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity type']);
            break;
    }
}

// HR Division CRUD functions
function createHRDivision($data, $pdo, $user) {
    $requiredFields = ['DivisionName', 'DivisionCode'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $sql = "INSERT INTO hr_divisions (DivisionName, DivisionCode, Description, DivisionHead, ParentDivisionID) 
            VALUES (:division_name, :division_code, :description, :division_head, :parent_division_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':division_name', $data['DivisionName']);
    $stmt->bindParam(':division_code', $data['DivisionCode']);
    $stmt->bindParam(':description', $data['Description'] ?? null);
    $stmt->bindParam(':division_head', $data['DivisionHead'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':parent_division_id', $data['ParentDivisionID'] ?? null, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $divisionId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'HR Division created successfully',
            'division_id' => $divisionId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create HR Division']);
    }
}

function updateHRDivision($id, $data, $pdo, $user) {
    $sql = "UPDATE hr_divisions SET 
                DivisionName = :division_name,
                DivisionCode = :division_code,
                Description = :description,
                DivisionHead = :division_head,
                ParentDivisionID = :parent_division_id,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE DivisionID = :division_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':division_id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':division_name', $data['DivisionName']);
    $stmt->bindParam(':division_code', $data['DivisionCode']);
    $stmt->bindParam(':description', $data['Description'] ?? null);
    $stmt->bindParam(':division_head', $data['DivisionHead'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':parent_division_id', $data['ParentDivisionID'] ?? null, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'HR Division updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update HR Division']);
    }
}

function deleteHRDivision($id, $pdo, $user) {
    // Soft delete
    $sql = "UPDATE hr_divisions SET IsActive = 0, UpdatedAt = CURRENT_TIMESTAMP WHERE DivisionID = :division_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':division_id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'HR Division deactivated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to deactivate HR Division']);
    }
}

// Job Role CRUD functions
function createJobRole($data, $pdo, $user) {
    $requiredFields = ['RoleTitle', 'RoleCode', 'JobLevel', 'JobFamily'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $sql = "INSERT INTO hospital_job_roles 
            (RoleTitle, RoleCode, DivisionID, DepartmentID, JobLevel, JobFamily, 
             MinimumQualification, JobDescription, ReportsTo) 
            VALUES (:role_title, :role_code, :division_id, :department_id, :job_level, 
                    :job_family, :min_qualification, :job_description, :reports_to)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':role_title', $data['RoleTitle']);
    $stmt->bindParam(':role_code', $data['RoleCode']);
    $stmt->bindParam(':division_id', $data['DivisionID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':department_id', $data['DepartmentID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':job_level', $data['JobLevel']);
    $stmt->bindParam(':job_family', $data['JobFamily']);
    $stmt->bindParam(':min_qualification', $data['MinimumQualification'] ?? null);
    $stmt->bindParam(':job_description', $data['JobDescription'] ?? null);
    $stmt->bindParam(':reports_to', $data['ReportsTo'] ?? null, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $roleId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Job Role created successfully',
            'role_id' => $roleId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create Job Role']);
    }
}

function updateJobRole($id, $data, $pdo, $user) {
    $sql = "UPDATE hospital_job_roles SET 
                RoleTitle = :role_title,
                RoleCode = :role_code,
                DivisionID = :division_id,
                DepartmentID = :department_id,
                JobLevel = :job_level,
                JobFamily = :job_family,
                MinimumQualification = :min_qualification,
                JobDescription = :job_description,
                ReportsTo = :reports_to,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE JobRoleID = :role_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':role_id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':role_title', $data['RoleTitle']);
    $stmt->bindParam(':role_code', $data['RoleCode']);
    $stmt->bindParam(':division_id', $data['DivisionID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':department_id', $data['DepartmentID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':job_level', $data['JobLevel']);
    $stmt->bindParam(':job_family', $data['JobFamily']);
    $stmt->bindParam(':min_qualification', $data['MinimumQualification'] ?? null);
    $stmt->bindParam(':job_description', $data['JobDescription'] ?? null);
    $stmt->bindParam(':reports_to', $data['ReportsTo'] ?? null, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Job Role updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update Job Role']);
    }
}

function deleteJobRole($id, $pdo, $user) {
    // Soft delete
    $sql = "UPDATE hospital_job_roles SET IsActive = 0, UpdatedAt = CURRENT_TIMESTAMP WHERE JobRoleID = :role_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':role_id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Job Role deactivated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to deactivate Job Role']);
    }
}

// Department Coordinator functions
function assignDepartmentCoordinator($data, $pdo, $user) {
    $requiredFields = ['DepartmentID', 'EmployeeID'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Deactivate existing primary coordinator if assigning a new primary
    if (($data['CoordinatorType'] ?? 'Primary') === 'Primary') {
        $sql = "UPDATE department_hr_coordinators 
                SET IsActive = 0, EndDate = CURDATE() 
                WHERE DepartmentID = :department_id AND CoordinatorType = 'Primary' AND IsActive = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':department_id', $data['DepartmentID'], PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $sql = "INSERT INTO department_hr_coordinators 
            (DepartmentID, EmployeeID, CoordinatorType, EffectiveDate, AssignedBy) 
            VALUES (:department_id, :employee_id, :coordinator_type, :effective_date, :assigned_by)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':department_id', $data['DepartmentID'], PDO::PARAM_INT);
    $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
    $stmt->bindParam(':coordinator_type', $data['CoordinatorType'] ?? 'Primary');
    $stmt->bindParam(':effective_date', $data['EffectiveDate'] ?? date('Y-m-d'));
    $stmt->bindParam(':assigned_by', $user['EmployeeID'], PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $coordinatorId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Department Coordinator assigned successfully',
            'coordinator_id' => $coordinatorId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to assign Department Coordinator']);
    }
}

function updateDepartmentCoordinator($id, $data, $pdo, $user) {
    $sql = "UPDATE department_hr_coordinators SET 
                DepartmentID = :department_id,
                EmployeeID = :employee_id,
                CoordinatorType = :coordinator_type,
                EffectiveDate = :effective_date,
                EndDate = :end_date,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE CoordinatorID = :coordinator_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':coordinator_id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':department_id', $data['DepartmentID'], PDO::PARAM_INT);
    $stmt->bindParam(':employee_id', $data['EmployeeID'], PDO::PARAM_INT);
    $stmt->bindParam(':coordinator_type', $data['CoordinatorType'] ?? 'Primary');
    $stmt->bindParam(':effective_date', $data['EffectiveDate']);
    $stmt->bindParam(':end_date', $data['EndDate'] ?? null);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Department Coordinator updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update Department Coordinator']);
    }
}

function removeDepartmentCoordinator($id, $pdo, $user) {
    $sql = "UPDATE department_hr_coordinators 
            SET IsActive = 0, EndDate = CURDATE(), UpdatedAt = CURRENT_TIMESTAMP 
            WHERE CoordinatorID = :coordinator_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':coordinator_id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Department Coordinator removed successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove Department Coordinator']);
    }
}

// Department CRUD functions
function createDepartment($data, $pdo, $user) {
    $requiredFields = ['DepartmentName', 'DepartmentCode', 'DepartmentType'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $sql = "INSERT INTO organizationalstructure 
            (DepartmentName, DepartmentCode, DepartmentType, Description, ManagerID, ParentDepartmentID, Budget, Location) 
            VALUES (:dept_name, :dept_code, :dept_type, :description, :manager_id, :parent_id, :budget, :location)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dept_name', $data['DepartmentName']);
    $stmt->bindParam(':dept_code', $data['DepartmentCode']);
    $stmt->bindParam(':dept_type', $data['DepartmentType']);
    $stmt->bindParam(':description', $data['Description'] ?? null);
    $stmt->bindParam(':manager_id', $data['ManagerID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':parent_id', $data['ParentDepartmentID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':budget', $data['Budget'] ?? null);
    $stmt->bindParam(':location', $data['Location'] ?? null);
    
    if ($stmt->execute()) {
        $deptId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Department created successfully',
            'department_id' => $deptId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create Department']);
    }
}

function updateDepartment($id, $data, $pdo, $user) {
    $sql = "UPDATE organizationalstructure SET 
                DepartmentName = :dept_name,
                DepartmentCode = :dept_code,
                DepartmentType = :dept_type,
                Description = :description,
                ManagerID = :manager_id,
                ParentDepartmentID = :parent_id,
                Budget = :budget,
                Location = :location,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE DepartmentID = :dept_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dept_id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':dept_name', $data['DepartmentName']);
    $stmt->bindParam(':dept_code', $data['DepartmentCode']);
    $stmt->bindParam(':dept_type', $data['DepartmentType']);
    $stmt->bindParam(':description', $data['Description'] ?? null);
    $stmt->bindParam(':manager_id', $data['ManagerID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':parent_id', $data['ParentDepartmentID'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':budget', $data['Budget'] ?? null);
    $stmt->bindParam(':location', $data['Location'] ?? null);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Department updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update Department']);
    }
}

function deleteDepartment($id, $pdo, $user) {
    // Check if department has employees
    $sql = "SELECT COUNT(*) as employee_count FROM employees WHERE DepartmentID = :dept_id AND IsActive = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dept_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['employee_count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Cannot delete department with active employees',
            'employee_count' => $result['employee_count']
        ]);
        return;
    }
    
    // Soft delete
    $sql = "UPDATE organizationalstructure SET IsActive = 0, UpdatedAt = CURRENT_TIMESTAMP WHERE DepartmentID = :dept_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dept_id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Department deactivated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to deactivate Department']);
    }
}
?>
