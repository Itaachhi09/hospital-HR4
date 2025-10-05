<?php
/**
 * HR Core Aggregator (Read-Only)
 * - GET /api/hr-core/employees        => consolidated view of employees (HR1 base + HR2 career + HR3 status)
 * - GET /api/hr-core/documents        => consolidated documents index across HR1–HR3 (simulated via local tables now)
 * - GET /api/hr-core/sync-status      => last sync timestamps and health
 * - GET /api/hr-core/config           => current read-only flag and capabilities
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Attendance.php';

class HRCoreController {
    private $pdo;
    private $auth;
    private $employeeModel;
    private $documentModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->employeeModel = new Employee();
        $this->documentModel = new Document();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        if ($method !== 'GET') {
            return Response::methodNotAllowed();
        }
        $resource = $id ?: '';
        switch ($resource) {
            case 'employees':
                return $this->employees();
            case 'documents':
                return $this->documents();
            case 'sync-status':
                return $this->syncStatus();
            case 'config':
                return $this->config();
            default:
                return Response::notFound();
        }
    }

    private function employees() {
        $request = new Request();
        $pagination = $request->getPagination();
        // Accept same filters as EmployeesController; acts as viewer-only proxy
        $filters = [
            'department_id' => $request->getData('department_id'),
            'is_active' => $request->getData('is_active'),
            'manager_id' => $request->getData('manager_id'),
            'search' => $request->getData('search'),
            'employment_status' => $request->getData('employment_status'),
            'employment_type' => $request->getData('employment_type'),
            'job_title' => $request->getData('job_title')
        ];
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        $rows = $this->employeeModel->getEmployees($pagination['page'], $pagination['limit'], $filters);
        $total = $this->employeeModel->countEmployees($filters);
        $totalPages = (int)ceil($total / $pagination['limit']);
        Response::paginated($rows, [
            'current_page' => $pagination['page'],
            'per_page' => $pagination['limit'],
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $pagination['page'] < $totalPages,
            'has_prev' => $pagination['page'] > 1
        ]);
    }

    private function documents() {
        $request = new Request();
        // Viewer-only: index of documents; support filters
        $filters = [
            'employee_id' => $request->getData('employee_id'),
            'document_type' => $request->getData('document_type'),
            'category' => $request->getData('category'),
            'search' => $request->getData('search'),
            'expiring_within_days' => $request->getData('expiring_within_days')
        ];
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        $rows = $this->documentModel->listAll($filters);
        Response::success($rows);
    }

    private function syncStatus() {
        // Basic stub: show last processed timestamps from Outbox/attendance etc. if available
        try {
            $hr1 = $this->pdo->query("SELECT MAX(CreatedAt) AS last_hr1 FROM OutboxEvents WHERE EventType LIKE 'HR1.%'")->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { $hr1 = ['last_hr1' => null]; }
        try {
            $hr2 = $this->pdo->query("SELECT MAX(CreatedAt) AS last_hr2 FROM EmploymentHistory")->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { $hr2 = ['last_hr2' => null]; }
        try {
            $hr3 = $this->pdo->query("SELECT MAX(CreatedDate) AS last_hr3 FROM AttendanceRecords")->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { $hr3 = ['last_hr3' => null]; }
        Response::success([
            'hr1_employees_last' => $hr1['last_hr1'] ?? null,
            'hr2_career_last' => $hr2['last_hr2'] ?? null,
            'hr3_attendance_last' => $hr3['last_hr3'] ?? null
        ]);
    }

    private function config() {
        $cfg = $GLOBALS['api_config'] ?? [];
        Response::success([
            'read_only' => (bool)($cfg['app']['read_only'] ?? false),
        ]);
    }
}
