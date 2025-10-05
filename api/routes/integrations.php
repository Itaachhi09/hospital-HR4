<?php
/**
 * Integrations Routes (Inbound and Outbound)
 * - POST /api/integrations/hr1/applicant-hired
 * - POST /api/integrations/hr2/career-event
 * - POST /api/integrations/hr3/attendance-batch
 * - GET  /api/integrations/payroll/employees?updated_since=
 * - GET  /api/integrations/hmo/employees?updated_since=
 * - GET  /api/integrations/analytics/employees?updated_since=
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Employee.php';

class IntegrationsController {
    private $pdo;
    private $auth;
    private $employeeModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->employeeModel = new Employee();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        $segments = $this->getSegments();
        if (empty($segments)) return Response::notFound();

        if ($segments[0] === 'integrations') {
            if ($method === 'POST' && isset($segments[1], $segments[2])) {
                if ($segments[1] === 'hr1' && $segments[2] === 'applicant-hired') return $this->hr1ApplicantHired();
                if ($segments[1] === 'hr2' && $segments[2] === 'career-event') return $this->hr2CareerEvent();
                if ($segments[1] === 'hr3' && $segments[2] === 'attendance-batch') return $this->hr3AttendanceBatch();
            }
            if ($method === 'GET' && isset($segments[1], $segments[2])) {
                if ($segments[1] === 'payroll' && $segments[2] === 'employees') return $this->deltaEmployees();
                if ($segments[1] === 'hmo' && $segments[2] === 'employees') return $this->deltaEmployees();
                if ($segments[1] === 'analytics' && $segments[2] === 'employees') return $this->deltaEmployees();
            }
        }
        return Response::notFound();
    }

    private function hr1ApplicantHired() {
        if (!$this->auth->authenticate() || !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::unauthorized('Authentication required');
        }
        $request = new Request();
        $data = $request->getData();
        $required = ['first_name','last_name','email','job_title','department_id','hire_date'];
        $errors = [];
        foreach ($required as $f) { if (empty($data[$f])) $errors[$f] = ucfirst(str_replace('_',' ',$f)).' is required'; }
        if (!empty($errors)) Response::validationError($errors);
        // Create employee using existing model
        $payload = [
            'first_name' => $request->sanitizeString($data['first_name']),
            'middle_name' => isset($data['middle_name']) ? $request->sanitizeString($data['middle_name']) : null,
            'last_name' => $request->sanitizeString($data['last_name']),
            'suffix' => isset($data['suffix']) ? $request->sanitizeString($data['suffix']) : null,
            'email' => $data['email'],
            'personal_email' => $data['personal_email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state_province' => $data['state_province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'hire_date' => $data['hire_date'],
            'job_title' => $request->sanitizeString($data['job_title']),
            'department_id' => (int)$data['department_id'],
            'manager_id' => isset($data['manager_id']) ? (int)$data['manager_id'] : null,
            'is_active' => 1
        ];
        $id = $this->employeeModel->createEmployee($payload);
        // TODO: optionally create user
        Response::created(['employee_id' => $id], 'Applicant converted to employee');
    }

    private function hr2CareerEvent() {
        if (!$this->auth->authenticate() || !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::unauthorized('Authentication required');
        }
        $request = new Request();
        $data = $request->getData();
        $required = ['employee_id','event_type']; // promotion|transfer|status_change
        $errors = [];
        foreach ($required as $f) { if (empty($data[$f])) $errors[$f] = ucfirst(str_replace('_',' ',$f)).' is required'; }
        if (!empty($errors)) Response::validationError($errors);

        $employeeId = (int)$data['employee_id'];
        $update = [];
        $old = $this->employeeModel->getEmployeeById($employeeId);
        if (!empty($data['new_job_title'])) { $update['job_title'] = $request->sanitizeString($data['new_job_title']); }
        if (!empty($data['new_department_id'])) { $update['department_id'] = (int)$data['new_department_id']; }
        if (!empty($data['new_manager_id'])) { $update['manager_id'] = (int)$data['new_manager_id']; }
        if (!empty($data['new_employment_status'])) { $update['is_active'] = strtoupper($data['new_employment_status']) === 'ACTIVE' ? 1 : 0; }
        // Persist
        if (!empty($update)) { $this->employeeModel->updateEmployee($employeeId, $update); }
        // Write employment history row (best effort)
        try {
            $stmt = $this->pdo->prepare("INSERT INTO EmploymentHistory (EmployeeID, EventType, OldDepartmentID, NewDepartmentID, OldJobTitle, NewJobTitle, OldManagerID, NewManagerID, OldEmploymentStatus, NewEmploymentStatus, EffectiveDate, Notes) VALUES (:eid, :etype, :oldDept, :newDept, :oldTitle, :newTitle, :oldMgr, :newMgr, :oldStatus, :newStatus, :eff, :notes)");
            $etype = isset($data['event_type']) ? $data['event_type'] : 'StatusChange';
            $oldDept = $old['DepartmentID'] ?? null;
            $newDept = $update['department_id'] ?? $oldDept;
            $oldTitle = $old['JobTitle'] ?? null;
            $newTitle = $update['job_title'] ?? $oldTitle;
            $oldMgr = $old['ManagerID'] ?? null;
            $newMgr = $update['manager_id'] ?? $oldMgr;
            $oldStatus = ($old['IsActive'] ?? 1) == 1 ? 'Active' : 'Inactive';
            $newStatus = isset($update['is_active']) ? ($update['is_active'] == 1 ? 'Active' : 'Inactive') : $oldStatus;
            $eff = $data['effective_date'] ?? date('Y-m-d');
            $notes = $data['notes'] ?? null;
            $stmt->bindParam(':eid', $employeeId, PDO::PARAM_INT);
            $stmt->bindParam(':etype', $etype, PDO::PARAM_STR);
            $stmt->bindParam(':oldDept', $oldDept, PDO::PARAM_INT);
            $stmt->bindParam(':newDept', $newDept, PDO::PARAM_INT);
            $stmt->bindParam(':oldTitle', $oldTitle, PDO::PARAM_STR);
            $stmt->bindParam(':newTitle', $newTitle, PDO::PARAM_STR);
            $stmt->bindParam(':oldMgr', $oldMgr, PDO::PARAM_INT);
            $stmt->bindParam(':newMgr', $newMgr, PDO::PARAM_INT);
            $stmt->bindParam(':oldStatus', $oldStatus, PDO::PARAM_STR);
            $stmt->bindParam(':newStatus', $newStatus, PDO::PARAM_STR);
            $stmt->bindParam(':eff', $eff, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Throwable $e) { error_log('EmploymentHistory insert failed: ' . $e->getMessage()); }
        // Write to outbox for downstream systems
        $this->writeOutboxEvent('hr2.career_event', $data);
        Response::success(null, 'Career event processed');
    }

    private function hr3AttendanceBatch() {
        if (!$this->auth->authenticate() || !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::unauthorized('Authentication required');
        }
        $request = new Request();
        $data = $request->getData();
        // Accept and store raw batch (out of scope to expand here)
        $this->writeOutboxEvent('hr3.attendance_batch', $data);
        Response::success(null, 'Attendance batch received');
    }

    private function deltaEmployees() {
        if (!$this->auth->authenticate() || !$this->auth->hasAnyRole(['System Admin','HR Manager','HR Admin'])) {
            Response::unauthorized('Authentication required');
        }
        $request = new Request();
        $updatedSince = $request->getData('updated_since');
        // Simple implementation: reuse employees list; in a real impl, join by UpdatedAt
        $filters = [];
        $page = 1; $limit = 1000;
        $items = $this->employeeModel->getEmployees($page, $limit, $filters);
        Response::success(['items' => $items, 'updated_since' => $updatedSince]);
    }

    private function writeOutboxEvent($eventType, $payload) {
        // Minimal outbox writer (table must exist). Fails silently if table missing.
        try {
            $stmt = $this->pdo->prepare("INSERT INTO OutboxEvents (EventType, PayloadJSON, CreatedAt) VALUES (:type, :payload, NOW())");
            $json = json_encode($payload);
            $stmt->bindParam(':type', $eventType, PDO::PARAM_STR);
            $stmt->bindParam(':payload', $json, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Outbox write failed: ' . $e->getMessage());
        }
    }

    private function getSegments() {
        $path = ltrim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/');
        $segs = explode('/', $path);
        if (isset($segs[0]) && $segs[0] === 'api') array_shift($segs);
        return $segs;
    }
}
