<?php
/**
 * API Endpoint: Get Hospital Organizational Structure
 * Retrieves the comprehensive hospital organizational structure including HR divisions, 
 * job roles, and department coordinators
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db_connect.php';
require_once '../../api/models/Department.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

try {
    $departmentModel = new Department();
    
    $view = $_GET['view'] ?? 'full';
    
    switch ($view) {
        case 'divisions':
            // Get HR divisions only
            $data = $departmentModel->getHRDivisions();
            break;
            
        case 'roles':
            // Get hospital job roles
            $divisionId = $_GET['division_id'] ?? null;
            $departmentId = $_GET['department_id'] ?? null;
            $data = $departmentModel->getHospitalJobRoles($divisionId, $departmentId);
            break;
            
        case 'coordinators':
            // Get department HR coordinators
            $departmentId = $_GET['department_id'] ?? null;
            $data = $departmentModel->getDepartmentHRCoordinators($departmentId);
            break;
            
        case 'hierarchy':
            // Get full organizational hierarchy
            $data = $departmentModel->getHospitalOrgHierarchy();
            break;

        case 'functional':
            // Functional summary per department/role
            $data = $departmentModel->getFunctionalSummary();
            break;

        case 'paygrade':
            // Pay grade mapping per role/department
            $data = $departmentModel->getPayGrades();
            break;
            
        default:
            // Get comprehensive hospital structure
            $data = [
                'divisions' => $departmentModel->getHRDivisions(),
                'departments' => $departmentModel->getHospitalOrgHierarchy(),
                'roles' => $departmentModel->getHospitalJobRoles(),
                'coordinators' => $departmentModel->getDepartmentHRCoordinators()
            ];
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'view' => $view,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_hospital_org_structure.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => 'Failed to retrieve hospital organizational structure'
    ]);
} catch (Exception $e) {
    error_log("General Error in get_hospital_org_structure.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred',
        'message' => 'Failed to process request'
    ]);
}
?>
