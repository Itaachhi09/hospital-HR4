<?php
/**
 * API Endpoint: Delete Employee (Soft Delete)
 * Sets Employees.IsActive = 0. Expects JSON body: { employee_id: number }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST method required.']); exit; }

require_once '../db_connect.php';
if (!isset($pdo)) { http_response_code(500); echo json_encode(['error' => 'Database connection failed.']); exit; }

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['employee_id'])) { http_response_code(400); echo json_encode(['error' => 'employee_id is required.']); exit; }
    $employeeId = (int)$data['employee_id'];
    if ($employeeId <= 0) { http_response_code(400); echo json_encode(['error' => 'employee_id must be a positive integer.']); exit; }

    $stmt = $pdo->prepare('UPDATE Employees SET IsActive = 0, TerminationDate = COALESCE(TerminationDate, CURDATE()) WHERE EmployeeID = :id');
    $stmt->bindValue(':id', $employeeId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Employee deleted (soft) successfully.']);
} catch (Throwable $e) {
    error_log('Delete Employee Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete employee.']);
}

?>


