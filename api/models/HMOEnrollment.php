<?php
/**
 * HMO Enrollment Model
 * Manages employee HMO enrollments and dependents
 */

class HMOEnrollment {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all enrollments with filters
     */
    public function getEnrollments($filters = []) {
        $sql = "SELECT 
                    e.*,
                    emp.FirstName,
                    emp.LastName,
                    emp.EmployeeID as EmployeeNumber,
                    emp.Email as EmployeeEmail,
                    emp.DepartmentID,
                    d.DepartmentName,
                    p.PlanName,
                    p.PlanCode,
                    p.MonthlyPremium,
                    p.MaximumBenefitLimit,
                    pr.ProviderName,
                    pr.ProviderID
                FROM employeehmoenrollments e
                JOIN employees emp ON e.EmployeeID = emp.EmployeeID
                JOIN hmoplans p ON e.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN Departments d ON emp.DepartmentID = d.DepartmentID
                WHERE 1=1";

        $params = [];

        if (isset($filters['employee_id'])) {
            $sql .= " AND e.EmployeeID = :employee_id";
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['plan_id'])) {
            $sql .= " AND e.PlanID = :plan_id";
            $params[':plan_id'] = $filters['plan_id'];
        }

        if (isset($filters['provider_id'])) {
            $sql .= " AND pr.ProviderID = :provider_id";
            $params[':provider_id'] = $filters['provider_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['department_id'])) {
            $sql .= " AND emp.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        $sql .= " ORDER BY e.CreatedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get enrollment by ID
     */
    public function getEnrollmentById($enrollmentId) {
        $sql = "SELECT 
                    e.*,
                    emp.FirstName,
                    emp.LastName,
                    emp.EmployeeID as EmployeeNumber,
                    emp.Email as EmployeeEmail,
                    p.PlanName,
                    p.PlanCode,
                    p.CoverageType,
                    p.MonthlyPremium,
                    p.MaximumBenefitLimit,
                    pr.ProviderName,
                    pr.ProviderID,
                    pr.ContactEmail as ProviderEmail,
                    pr.ContactPhone as ProviderPhone
                FROM employeehmoenrollments e
                JOIN employees emp ON e.EmployeeID = emp.EmployeeID
                JOIN hmoplans p ON e.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                WHERE e.EnrollmentID = :enrollment_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':enrollment_id' => $enrollmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new enrollment
     */
    public function createEnrollment($data) {
        // Check for existing active enrollment for the same employee
        $checkSql = "SELECT COUNT(*) FROM employeehmoenrollments 
                     WHERE EmployeeID = :employee_id AND Status = 'Active'";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([':employee_id' => $data['employee_id']]);
        
        if ($checkStmt->fetchColumn() > 0 && ($data['allow_multiple'] ?? false) === false) {
            throw new Exception("Employee already has an active HMO enrollment");
        }

        $sql = "INSERT INTO employeehmoenrollments (
                    EmployeeID, PlanID, EnrollmentDate, EffectiveDate, EndDate,
                    Status, MonthlyDeduction, MonthlyContribution, 
                    DependentsCount, Notes
                ) VALUES (
                    :employee_id, :plan_id, :enrollment_date, :effective_date, :end_date,
                    :status, :monthly_deduction, :monthly_contribution,
                    :dependents_count, :notes
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':plan_id' => $data['plan_id'],
            ':enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
            ':effective_date' => $data['effective_date'] ?? date('Y-m-d'),
            ':end_date' => $data['end_date'] ?? null,
            ':status' => $data['status'] ?? 'Active',
            ':monthly_deduction' => $data['monthly_deduction'] ?? 0,
            ':monthly_contribution' => $data['monthly_contribution'] ?? 0,
            ':dependents_count' => $data['dependents_count'] ?? 0,
            ':notes' => $data['notes'] ?? null
        ]);

        $enrollmentId = $this->pdo->lastInsertId();

        // Create notification
        $this->createEnrollmentNotification($enrollmentId, 'created');

        return $enrollmentId;
    }

    /**
     * Update enrollment
     */
    public function updateEnrollment($enrollmentId, $data) {
        $sql = "UPDATE employeehmoenrollments SET
                    PlanID = :plan_id,
                    EffectiveDate = :effective_date,
                    EndDate = :end_date,
                    Status = :status,
                    MonthlyDeduction = :monthly_deduction,
                    MonthlyContribution = :monthly_contribution,
                    DependentsCount = :dependents_count,
                    Notes = :notes,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE EnrollmentID = :enrollment_id";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':plan_id' => $data['plan_id'],
            ':effective_date' => $data['effective_date'],
            ':end_date' => $data['end_date'] ?? null,
            ':status' => $data['status'],
            ':monthly_deduction' => $data['monthly_deduction'] ?? 0,
            ':monthly_contribution' => $data['monthly_contribution'] ?? 0,
            ':dependents_count' => $data['dependents_count'] ?? 0,
            ':notes' => $data['notes'] ?? null,
            ':enrollment_id' => $enrollmentId
        ]);

        if ($result) {
            $this->createEnrollmentNotification($enrollmentId, 'updated');
        }

        return $result;
    }

    /**
     * Terminate enrollment
     */
    public function terminateEnrollment($enrollmentId, $endDate = null) {
        $sql = "UPDATE employeehmoenrollments SET
                    Status = 'Terminated',
                    EndDate = :end_date,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE EnrollmentID = :enrollment_id";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':end_date' => $endDate ?? date('Y-m-d'),
            ':enrollment_id' => $enrollmentId
        ]);

        if ($result) {
            $this->createEnrollmentNotification($enrollmentId, 'terminated');
        }

        return $result;
    }

    /**
     * Get enrollment benefit balance
     */
    public function getEnrollmentBalance($enrollmentId) {
        $sql = "SELECT 
                    e.EnrollmentID,
                    p.MaximumBenefitLimit,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as used_amount,
                    (p.MaximumBenefitLimit - COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0)) as remaining_balance,
                    COUNT(c.ClaimID) as total_claims,
                    COUNT(CASE WHEN c.Status = 'Pending' THEN c.ClaimID END) as pending_claims
                FROM employeehmoenrollments e
                JOIN hmoplans p ON e.PlanID = p.PlanID
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE e.EnrollmentID = :enrollment_id
                GROUP BY e.EnrollmentID, p.MaximumBenefitLimit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':enrollment_id' => $enrollmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get enrollment history
     */
    public function getEnrollmentHistory($employeeId) {
        $sql = "SELECT 
                    e.*,
                    p.PlanName,
                    pr.ProviderName,
                    COUNT(DISTINCT c.ClaimID) as total_claims,
                    COALESCE(SUM(c.Amount), 0) as total_claim_amount
                FROM employeehmoenrollments e
                JOIN hmoplans p ON e.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE e.EmployeeID = :employee_id
                GROUP BY e.EnrollmentID
                ORDER BY e.EffectiveDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create enrollment notification
     */
    private function createEnrollmentNotification($enrollmentId, $action) {
        try {
            $enrollment = $this->getEnrollmentById($enrollmentId);
            if (!$enrollment) return;

            $messages = [
                'created' => 'You have been enrolled in HMO plan: ' . ($enrollment['PlanName'] ?? 'N/A'),
                'updated' => 'Your HMO enrollment has been updated',
                'terminated' => 'Your HMO enrollment has been terminated'
            ];

            $message = $messages[$action] ?? 'HMO enrollment notification';

            // Create HMO notification
            $sql = "INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message)
                    VALUES (:employee_id, :type, :title, :message)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':employee_id' => $enrollment['EmployeeID'],
                ':type' => strtoupper($action),
                ':title' => 'HMO Enrollment ' . ucfirst($action),
                ':message' => $message
            ]);

            // Create global notification for user
            $userSql = "SELECT UserID FROM Users WHERE EmployeeID = :employee_id LIMIT 1";
            $userStmt = $this->pdo->prepare($userSql);
            $userStmt->execute([':employee_id' => $enrollment['EmployeeID']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $globalSql = "INSERT INTO Notifications (UserID, SenderUserID, NotificationType, Message, Link, IsRead)
                              VALUES (:user_id, :sender_id, :type, :message, :link, 0)";
                $globalStmt = $this->pdo->prepare($globalSql);
                $globalStmt->execute([
                    ':user_id' => $user['UserID'],
                    ':sender_id' => $_SESSION['user_id'] ?? $user['UserID'],
                    ':type' => 'HMO_' . strtoupper($action),
                    ':message' => $message,
                    ':link' => '#hmo-benefits'
                ]);
            }
        } catch (Exception $e) {
            error_log('Enrollment notification error: ' . $e->getMessage());
        }
    }
}

