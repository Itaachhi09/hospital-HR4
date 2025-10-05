<?php
/**
 * API Endpoint: Get Employees
 * Retrieves a list of employees with detailed information.
 * v2.1 - Added UserID to the selection.
 * v2.0 - Fetches more comprehensive employee details including personal, contact, address, and employment info.
 */

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep 0 for production
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/../../php-error.log'); // Ensure this path is writable

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production

// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php'; // Path relative to this api script
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('Database connection object ($pdo) not properly created by db_connect.php.');
    }
} catch (Throwable $e) {
    error_log("PHP Error in get_employees.php (db_connect include): " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Server configuration error: Could not connect to the database.']);
    exit;
}

try {
    // Build SQL with optional filters
    $sql = "SELECT
                e.EmployeeID,
                e.FirstName,
                e.MiddleName,
                e.LastName,
                e.Suffix,
                e.Email,
                e.PersonalEmail,
                e.PhoneNumber,
                e.DateOfBirth,
                e.Gender,
                e.MaritalStatus,
                e.Nationality,
                e.AddressLine1,
                e.AddressLine2,
                e.City,
                e.StateProvince,
                e.PostalCode,
                e.Country,
                e.EmergencyContactName,
                e.EmergencyContactRelationship,
                e.EmergencyContactPhone,
                e.HireDate,
                e.JobTitle,
                e.EmploymentType,
                e.EmploymentStatus,
                e.DepartmentID,
                d.DepartmentName,
                e.ManagerID,
                CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                e.IsActive,
                e.TerminationDate,
                e.TerminationReason,
                e.EmployeePhotoPath,
                u.UserID
            FROM
                Employees e
            LEFT JOIN
                OrganizationalStructure d ON e.DepartmentID = d.DepartmentID
            LEFT JOIN
                Employees m ON e.ManagerID = m.EmployeeID
            LEFT JOIN 
                Users u ON e.EmployeeID = u.EmployeeID
            WHERE 1=1";

    $params = [];

    // Optional filters from query string
    $departmentId = isset($_GET['department_id']) ? filter_var($_GET['department_id'], FILTER_VALIDATE_INT) : null;
    if (!empty($departmentId)) {
        $sql .= " AND e.DepartmentID = :department_id";
        $params[':department_id'] = $departmentId;
    }

    // is_active filter (0/1)
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $isActive = ($_GET['is_active'] === '1' || $_GET['is_active'] === 1) ? 1 : 0;
        $sql .= " AND e.IsActive = :is_active";
        $params[':is_active'] = $isActive;
    }

    // employment_status exact match
    if (!empty($_GET['employment_status'])) {
        $sql .= " AND e.EmploymentStatus = :employment_status";
        $params[':employment_status'] = $_GET['employment_status'];
    }

    // employment_type exact match
    if (!empty($_GET['employment_type'])) {
        $sql .= " AND e.EmploymentType = :employment_type";
        $params[':employment_type'] = $_GET['employment_type'];
    }

    // job_title partial match
    if (!empty($_GET['job_title'])) {
        $sql .= " AND e.JobTitle LIKE :job_title";
        $params[':job_title'] = '%' . $_GET['job_title'] . '%';
    }

    // smart text search across name/email/job title
    if (!empty($_GET['search'])) {
        $sql .= " AND (e.FirstName LIKE :search OR e.LastName LIKE :search OR e.Email LIKE :search OR e.JobTitle LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sql .= " ORDER BY e.LastName, e.FirstName";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates and other fields as needed
    foreach ($employees as &$employee) {
        if (!empty($employee['HireDate'])) {
            $employee['HireDateFormatted'] = date('M d, Y', strtotime($employee['HireDate']));
        }
        if (!empty($employee['DateOfBirth'])) {
            $employee['DateOfBirthFormatted'] = date('M d, Y', strtotime($employee['DateOfBirth']));
        }
        if (!empty($employee['TerminationDate'])) {
            $employee['TerminationDateFormatted'] = date('M d, Y', strtotime($employee['TerminationDate']));
        }
        $employee['Status'] = ($employee['IsActive'] == 1) ? 'Active' : 'Inactive';
    }
    unset($employee); // Unset the reference

    // Output the results as JSON
    if (headers_sent()) { exit; }
    http_response_code(200);
    echo json_encode($employees);

} catch (\PDOException $e) {
    error_log("API Error (get_employees): " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Failed to retrieve employee data.']);
} catch (Throwable $e) {
    error_log("PHP Throwable in get_employees.php: " . $e->getMessage());
    if (!headers_sent()) { http_response_code(500); }
    echo json_encode(['error' => 'Unexpected server error retrieving employee data.']);
}
exit;
?>
