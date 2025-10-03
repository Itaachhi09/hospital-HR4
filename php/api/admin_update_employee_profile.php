<?php
/**
 * API Endpoint: Admin Update Employee Profile
 * Updates core employee profile fields. Expects JSON body.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required.']);
    exit;
}

require_once '../db_connect.php';
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Parse JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

// Validate inputs
$employeeId = isset($data['employee_id_to_update']) ? (int)$data['employee_id_to_update'] : 0;
if ($employeeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid employee_id_to_update is required.']);
    exit;
}

// Collect fields with basic sanitization
$firstName = isset($data['first_name']) ? trim($data['first_name']) : '';
$middleName = isset($data['middle_name']) ? trim((string)$data['middle_name']) : null;
$lastName = isset($data['last_name']) ? trim($data['last_name']) : '';
$suffix = isset($data['suffix']) ? trim((string)$data['suffix']) : null;
$email = isset($data['email']) ? trim($data['email']) : '';
$personalEmail = isset($data['personal_email']) ? trim((string)$data['personal_email']) : null;
$phoneNumber = isset($data['phone_number']) ? trim((string)$data['phone_number']) : null;
$dateOfBirth = isset($data['date_of_birth']) ? trim((string)$data['date_of_birth']) : null;
$gender = isset($data['gender']) ? trim((string)$data['gender']) : null;
$maritalStatus = isset($data['marital_status']) ? trim((string)$data['marital_status']) : null;
$nationality = isset($data['nationality']) ? trim((string)$data['nationality']) : null;
$jobTitle = isset($data['job_title']) ? trim($data['job_title']) : '';
$departmentId = isset($data['department_id']) && $data['department_id'] !== null ? (int)$data['department_id'] : null;
$managerId = isset($data['manager_id']) && $data['manager_id'] !== null ? (int)$data['manager_id'] : null;
$hireDate = isset($data['hire_date']) ? trim((string)$data['hire_date']) : '';
$isActiveEmployee = isset($data['is_active_employee']) ? (int)$data['is_active_employee'] : 1;

if ($firstName === '' || $lastName === '' || $email === '' || $jobTitle === '' || $departmentId === null || $hireDate === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

try {
    $sql = "UPDATE Employees SET 
                FirstName = :first_name,
                MiddleName = :middle_name,
                LastName = :last_name,
                Suffix = :suffix,
                Email = :email,
                PersonalEmail = :personal_email,
                PhoneNumber = :phone_number,
                DateOfBirth = :date_of_birth,
                Gender = :gender,
                MaritalStatus = :marital_status,
                Nationality = :nationality,
                JobTitle = :job_title,
                DepartmentID = :department_id,
                ManagerID = :manager_id,
                HireDate = :hire_date,
                IsActive = :is_active,
                UpdatedAt = NOW()
            WHERE EmployeeID = :employee_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':first_name', $firstName, PDO::PARAM_STR);
    $stmt->bindValue(':middle_name', $middleName, $middleName === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':last_name', $lastName, PDO::PARAM_STR);
    $stmt->bindValue(':suffix', $suffix, $suffix === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':personal_email', $personalEmail, $personalEmail === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':phone_number', $phoneNumber, $phoneNumber === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':date_of_birth', $dateOfBirth, $dateOfBirth === null || $dateOfBirth === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':gender', $gender, $gender === null || $gender === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':marital_status', $maritalStatus, $maritalStatus === null || $maritalStatus === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':nationality', $nationality, $nationality === null || $nationality === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':job_title', $jobTitle, PDO::PARAM_STR);
    $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
    $stmt->bindValue(':manager_id', $managerId, $managerId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':hire_date', $hireDate, PDO::PARAM_STR);
    $stmt->bindValue(':is_active', $isActiveEmployee, PDO::PARAM_INT);
    $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Employee information updated successfully.']);
} catch (Throwable $e) {
    error_log('Admin Update Employee Profile Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update employee information.']);
}

?>


