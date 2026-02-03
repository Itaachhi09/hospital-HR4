<?php
/**
 * HMO Claim Model
 * Manages claims submission, approval workflows, and reimbursements
 */

class HMOClaim {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all claims with filters
     */
    public function getClaims($filters = []) {
        $sql = "SELECT 
                    c.*,
                    e.EmployeeID,
                    emp.FirstName,
                    emp.LastName,
                    emp.EmployeeID as EmployeeNumber,
                    p.PlanName,
                    pr.ProviderName,
                    CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName,
                    CONCAT(approver.FirstName, ' ', approver.LastName) as ApproverName
                FROM hmoclaims c
                JOIN employeehmoenrollments enr ON c.EnrollmentID = enr.EnrollmentID
                JOIN employees emp ON enr.EmployeeID = emp.EmployeeID
                LEFT JOIN employees e ON c.EmployeeID = e.EmployeeID
                JOIN hmoplans p ON enr.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN Users u ON c.ApprovedBy = u.UserID
                LEFT JOIN employees approver ON u.EmployeeID = approver.EmployeeID
                WHERE 1=1";

        $params = [];

        if (isset($filters['employee_id'])) {
            $sql .= " AND enr.EmployeeID = :employee_id";
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['enrollment_id'])) {
            $sql .= " AND c.EnrollmentID = :enrollment_id";
            $params[':enrollment_id'] = $filters['enrollment_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND c.Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['claim_type'])) {
            $sql .= " AND c.ClaimType = :claim_type";
            $params[':claim_type'] = $filters['claim_type'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND c.ClaimDate >= :from_date";
            $params[':from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND c.ClaimDate <= :to_date";
            $params[':to_date'] = $filters['to_date'];
        }

        if (!empty($filters['min_amount'])) {
            $sql .= " AND c.Amount >= :min_amount";
            $params[':min_amount'] = $filters['min_amount'];
        }

        if (!empty($filters['max_amount'])) {
            $sql .= " AND c.Amount <= :max_amount";
            $params[':max_amount'] = $filters['max_amount'];
        }

        $sql .= " ORDER BY c.SubmittedDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normalize attachments
        foreach ($claims as &$claim) {
            $claim['Attachments'] = $this->normalizeAttachments($claim['Attachments'] ?? null);
        }

        return $claims;
    }

    /**
     * Get claim by ID
     */
    public function getClaimById($claimId) {
        $sql = "SELECT 
                    c.*,
                    e.EmployeeID,
                    emp.FirstName,
                    emp.LastName,
                    emp.EmployeeID as EmployeeNumber,
                    emp.Email as EmployeeEmail,
                    p.PlanName,
                    p.MaximumBenefitLimit,
                    pr.ProviderName,
                    pr.ContactEmail as ProviderEmail,
                    CONCAT(approver.FirstName, ' ', approver.LastName) as ApproverName
                FROM hmoclaims c
                JOIN employeehmoenrollments e ON c.EnrollmentID = e.EnrollmentID
                JOIN employees emp ON e.EmployeeID = emp.EmployeeID
                JOIN hmoplans p ON e.PlanID = p.PlanID
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN Users u ON c.ApprovedBy = u.UserID
                LEFT JOIN employees approver ON u.EmployeeID = approver.EmployeeID
                WHERE c.ClaimID = :claim_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':claim_id' => $claimId]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($claim) {
            $claim['Attachments'] = $this->normalizeAttachments($claim['Attachments'] ?? null);
        }

        return $claim;
    }

    /**
     * Create new claim
     */
    public function createClaim($data) {
        // Validate enrollment is active
        $enrollmentSql = "SELECT e.*, p.MaximumBenefitLimit 
                          FROM employeehmoenrollments e
                          JOIN hmoplans p ON e.PlanID = p.PlanID
                          WHERE e.EnrollmentID = :enrollment_id AND e.Status = 'Active'";
        $enrollmentStmt = $this->pdo->prepare($enrollmentSql);
        $enrollmentStmt->execute([':enrollment_id' => $data['enrollment_id']]);
        $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            throw new Exception("Invalid or inactive enrollment");
        }

        // Check benefit balance
        $balanceSql = "SELECT COALESCE(SUM(Amount), 0) as used_amount 
                       FROM hmoclaims 
                       WHERE EnrollmentID = :enrollment_id AND Status = 'Approved'";
        $balanceStmt = $this->pdo->prepare($balanceSql);
        $balanceStmt->execute([':enrollment_id' => $data['enrollment_id']]);
        $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);

        $remainingBalance = $enrollment['MaximumBenefitLimit'] - $balance['used_amount'];
        if ($data['amount'] > $remainingBalance) {
            throw new Exception("Claim amount exceeds remaining benefit balance (₱" . number_format($remainingBalance, 2) . ")");
        }

        // Generate claim number
        $claimNumber = $this->generateClaimNumber();

        $sql = "INSERT INTO hmoclaims (
                    EnrollmentID, EmployeeID, ClaimNumber, ClaimType, ClaimDate,
                    ProviderName, Description, Amount, SubmittedDate, Status,
                    Comments, Attachments
                ) VALUES (
                    :enrollment_id, :employee_id, :claim_number, :claim_type, :claim_date,
                    :provider_name, :description, :amount, :submitted_date, :status,
                    :comments, :attachments
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':enrollment_id' => $data['enrollment_id'],
            ':employee_id' => $data['employee_id'] ?? $enrollment['EmployeeID'],
            ':claim_number' => $claimNumber,
            ':claim_type' => $data['claim_type'] ?? 'Medical',
            ':claim_date' => $data['claim_date'] ?? date('Y-m-d'),
            ':provider_name' => $data['provider_name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':amount' => $data['amount'],
            ':submitted_date' => date('Y-m-d H:i:s'),
            ':status' => 'Pending',
            ':comments' => $data['comments'] ?? null,
            ':attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null
        ]);

        $claimId = $this->pdo->lastInsertId();

        // Create notification
        $this->createClaimNotification($claimId, 'submitted');

        // Create workflow instance
        $this->createClaimWorkflow($claimId, $data['amount']);

        return $claimId;
    }

    /**
     * Update claim
     */
    public function updateClaim($claimId, $data) {
        $currentClaim = $this->getClaimById($claimId);
        if (!$currentClaim) {
            throw new Exception("Claim not found");
        }

        $sql = "UPDATE hmoclaims SET
                    ClaimType = :claim_type,
                    ClaimDate = :claim_date,
                    ProviderName = :provider_name,
                    Description = :description,
                    Amount = :amount,
                    Comments = :comments,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE ClaimID = :claim_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':claim_type' => $data['claim_type'] ?? $currentClaim['ClaimType'],
            ':claim_date' => $data['claim_date'] ?? $currentClaim['ClaimDate'],
            ':provider_name' => $data['provider_name'] ?? $currentClaim['ProviderName'],
            ':description' => $data['description'] ?? $currentClaim['Description'],
            ':amount' => $data['amount'] ?? $currentClaim['Amount'],
            ':comments' => $data['comments'] ?? $currentClaim['Comments'],
            ':claim_id' => $claimId
        ]);
    }

    /**
     * Approve claim
     */
    public function approveClaim($claimId, $approverId, $comments = null) {
        $claim = $this->getClaimById($claimId);
        if (!$claim) {
            throw new Exception("Claim not found");
        }

        if ($claim['Status'] !== 'Pending' && $claim['Status'] !== 'Under Review') {
            throw new Exception("Only pending or under review claims can be approved");
        }

        $sql = "UPDATE hmoclaims SET
                    Status = 'Approved',
                    ApprovedBy = :approved_by,
                    ApprovedDate = CURRENT_TIMESTAMP,
                    Comments = :comments,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE ClaimID = :claim_id";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':approved_by' => $approverId,
            ':comments' => $comments ?? $claim['Comments'],
            ':claim_id' => $claimId
        ]);

        if ($result) {
            $this->createClaimNotification($claimId, 'approved');
            $this->completeClaimWorkflow($claimId, 'approved');
            
            // Create payroll integration entry for reimbursement
            $this->createPayrollReimbursement($claimId);
        }

        return $result;
    }

    /**
     * Deny claim
     */
    public function denyClaim($claimId, $deniedBy, $reason) {
        $claim = $this->getClaimById($claimId);
        if (!$claim) {
            throw new Exception("Claim not found");
        }

        $sql = "UPDATE hmoclaims SET
                    Status = 'Denied',
                    ApprovedBy = :denied_by,
                    Comments = :reason,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE ClaimID = :claim_id";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':denied_by' => $deniedBy,
            ':reason' => $reason,
            ':claim_id' => $claimId
        ]);

        if ($result) {
            $this->createClaimNotification($claimId, 'denied');
            $this->completeClaimWorkflow($claimId, 'denied');
        }

        return $result;
    }

    /**
     * Request claim revision
     */
    public function requestRevision($claimId, $requestedBy, $comments) {
        $sql = "UPDATE hmoclaims SET
                    Status = 'Pending',
                    Comments = :comments,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE ClaimID = :claim_id";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':comments' => $comments,
            ':claim_id' => $claimId
        ]);

        if ($result) {
            $this->createClaimNotification($claimId, 'revision_requested');
        }

        return $result;
    }

    /**
     * Get claim statistics
     */
    public function getClaimStatistics($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_claims,
                    COUNT(CASE WHEN Status = 'Pending' THEN 1 END) as pending_claims,
                    COUNT(CASE WHEN Status = 'Under Review' THEN 1 END) as under_review_claims,
                    COUNT(CASE WHEN Status = 'Approved' THEN 1 END) as approved_claims,
                    COUNT(CASE WHEN Status = 'Denied' THEN 1 END) as denied_claims,
                    COALESCE(SUM(CASE WHEN Status = 'Approved' THEN Amount ELSE 0 END), 0) as total_approved_amount,
                    COALESCE(AVG(CASE WHEN Status = 'Approved' THEN Amount END), 0) as avg_approved_amount,
                    COALESCE(AVG(DATEDIFF(ApprovedDate, SubmittedDate)), 0) as avg_processing_days
                FROM hmoclaims
                WHERE 1=1";

        $params = [];

        if (!empty($filters['from_date'])) {
            $sql .= " AND SubmittedDate >= :from_date";
            $params[':from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND SubmittedDate <= :to_date";
            $params[':to_date'] = $filters['to_date'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get top claim hospitals/providers
     */
    public function getTopProviders($limit = 10) {
        $sql = "SELECT 
                    ProviderName,
                    COUNT(*) as claim_count,
                    SUM(Amount) as total_amount,
                    AVG(Amount) as avg_amount
                FROM hmoclaims
                WHERE ProviderName IS NOT NULL
                GROUP BY ProviderName
                ORDER BY claim_count DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate unique claim number
     */
    private function generateClaimNumber() {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM hmoclaims 
                WHERE YEAR(SubmittedDate) = :year AND MONTH(SubmittedDate) = :month";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':year' => $year, ':month' => $month]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return sprintf('HMO-%s%s-%04d', $year, $month, $count + 1);
    }

    /**
     * Create claim notification
     */
    private function createClaimNotification($claimId, $action) {
        try {
            $claim = $this->getClaimById($claimId);
            if (!$claim) return;

            $messages = [
                'submitted' => 'Your HMO claim (' . $claim['ClaimNumber'] . ') has been submitted',
                'approved' => 'Your HMO claim (' . $claim['ClaimNumber'] . ') has been approved',
                'denied' => 'Your HMO claim (' . $claim['ClaimNumber'] . ') has been denied',
                'revision_requested' => 'Revision requested for your HMO claim (' . $claim['ClaimNumber'] . ')'
            ];

            $message = $messages[$action] ?? 'HMO claim notification';

            // HMO notification
            $sql = "INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message)
                    VALUES (:employee_id, :type, :title, :message)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':employee_id' => $claim['EmployeeID'],
                ':type' => 'CLAIM_' . strtoupper($action),
                ':title' => 'HMO Claim ' . ucwords(str_replace('_', ' ', $action)),
                ':message' => $message
            ]);

            // Global notification
            $userSql = "SELECT UserID FROM Users WHERE EmployeeID = :employee_id LIMIT 1";
            $userStmt = $this->pdo->prepare($userSql);
            $userStmt->execute([':employee_id' => $claim['EmployeeID']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $globalSql = "INSERT INTO Notifications (UserID, SenderUserID, NotificationType, Message, Link, IsRead)
                              VALUES (:user_id, :sender_id, :type, :message, :link, 0)";
                $globalStmt = $this->pdo->prepare($globalSql);
                $globalStmt->execute([
                    ':user_id' => $user['UserID'],
                    ':sender_id' => $_SESSION['user_id'] ?? $user['UserID'],
                    ':type' => 'HMO_CLAIM_' . strtoupper($action),
                    ':message' => $message,
                    ':link' => '#hmo-claims'
                ]);
            }
        } catch (Exception $e) {
            error_log('Claim notification error: ' . $e->getMessage());
        }
    }

    /**
     * Create claim workflow instance
     */
    private function createClaimWorkflow($claimId, $amount) {
        try {
            // Determine workflow based on amount
            $workflowType = $amount > 10000 ? 'high_value_claim' : 'standard_claim';
            
            $sql = "INSERT INTO claim_workflows (ClaimID, WorkflowType, CurrentStep, Status, CreatedAt)
                    VALUES (:claim_id, :workflow_type, 1, 'Pending', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':claim_id' => $claimId,
                ':workflow_type' => $workflowType
            ]);
        } catch (Exception $e) {
            error_log('Workflow creation error: ' . $e->getMessage());
        }
    }

    /**
     * Complete claim workflow
     */
    private function completeClaimWorkflow($claimId, $status) {
        try {
            $sql = "UPDATE claim_workflows SET 
                    Status = :status, 
                    CompletedAt = NOW(),
                    UpdatedAt = NOW()
                    WHERE ClaimID = :claim_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':claim_id' => $claimId
            ]);
        } catch (Exception $e) {
            error_log('Workflow completion error: ' . $e->getMessage());
        }
    }

    /**
     * Create payroll reimbursement entry
     */
    private function createPayrollReimbursement($claimId) {
        try {
            $claim = $this->getClaimById($claimId);
            if (!$claim) return;

            // Check if reimbursement already exists
            $checkSql = "SELECT COUNT(*) FROM hmo_reimbursements WHERE ClaimID = :claim_id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([':claim_id' => $claimId]);
            if ($checkStmt->fetchColumn() > 0) return;

            // Create reimbursement entry
            $sql = "INSERT INTO hmo_reimbursements (
                        ClaimID, EmployeeID, Amount, Status, 
                        ProcessedDate, CreatedAt
                    ) VALUES (
                        :claim_id, :employee_id, :amount, 'Pending',
                        NOW(), NOW()
                    )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':claim_id' => $claimId,
                ':employee_id' => $claim['EmployeeID'],
                ':amount' => $claim['Amount']
            ]);
        } catch (Exception $e) {
            error_log('Payroll reimbursement error: ' . $e->getMessage());
        }
    }

    /**
     * Normalize attachments from JSON
     */
    private function normalizeAttachments($value) {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Add attachment to claim
     */
    public function addAttachment($claimId, $filePath) {
        $claim = $this->getClaimById($claimId);
        if (!$claim) {
            throw new Exception("Claim not found");
        }

        $attachments = $claim['Attachments'];
        $attachments[] = $filePath;

        $sql = "UPDATE hmoclaims SET Attachments = :attachments WHERE ClaimID = :claim_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':attachments' => json_encode($attachments),
            ':claim_id' => $claimId
        ]);
    }
}

