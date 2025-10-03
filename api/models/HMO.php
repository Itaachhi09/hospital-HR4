<?php
/**
 * Cleaned HMO model
 * - Consolidated methods
 * - Uses prepared statements and clear parameter binding
 * - Returns consistent types (array for fetchAll, assoc for fetch, boolean for status)
 */

class HMO {
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new \RuntimeException('Database connection ($pdo) is not available');
        }
        $this->pdo = $pdo;
    }

    // ------------------- Providers -------------------
    public function listProviders(bool $activeOnly = true): array
    {
        $sql = 'SELECT ProviderID, ProviderName, ContactPerson, ContactEmail, ContactPhone, PhoneNumber, Email, Address, Website, IsActive FROM HMOProviders';
        if ($activeOnly) {
            $sql .= ' WHERE IsActive = 1';
        }
        $sql .= ' ORDER BY ProviderName ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getProvider(int $providerId): ?array
    {
        $sql = 'SELECT * FROM HMOProviders WHERE ProviderID = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $providerId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function createProvider(array $data): int
    {
        $sql = 'INSERT INTO HMOProviders (ProviderName, ContactPerson, ContactEmail, ContactPhone, PhoneNumber, Email, Address, Website, IsActive, CreatedAt) VALUES (:name, :contact_person, :contact_email, :contact_phone, :phone, :email, :address, :website, :is_active, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $data['provider_name'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':contact_person', $data['contact_person'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_email', $data['contact_email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_phone', $data['contact_phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone_number'] ?? $data['contact_phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? $data['contact_email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':website', $data['website'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (int)$data['is_active'] : 1, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateProvider(int $providerId, array $data): bool
    {
        $sql = 'UPDATE HMOProviders SET ProviderName = :name, ContactPerson = :contact_person, ContactEmail = :contact_email, ContactPhone = :contact_phone, PhoneNumber = :phone, Email = :email, Address = :address, Website = :website, IsActive = :is_active, UpdatedAt = NOW() WHERE ProviderID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $data['provider_name'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':contact_person', $data['contact_person'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_email', $data['contact_email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':contact_phone', $data['contact_phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone_number'] ?? $data['contact_phone'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? $data['contact_email'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':website', $data['website'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (int)$data['is_active'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $providerId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function softDeleteProvider(int $providerId): bool
    {
        $sql = 'UPDATE HMOProviders SET IsActive = 0, UpdatedAt = NOW() WHERE ProviderID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $providerId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ------------------- Plans -------------------
    public function listPlans(int $page = 1, int $limit = 0, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * max(1, $limit));
        $sql = 'SELECT hp.PlanID, hp.PlanName, hp.Description, hp.MonthlyPremium, hp.AnnualLimit, hp.CoverageLimit, hp.IsActive, hp.EffectiveDate, hp.EndDate, hpr.ProviderID, hpr.ProviderName FROM HMOPlans hp LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID WHERE 1=1';
        $params = [];
        if (!empty($filters['provider_id'])) {
            $sql .= ' AND hp.ProviderID = :provider_id';
            $params[':provider_id'] = $filters['provider_id'];
        }
        if (isset($filters['is_active'])) {
            $sql .= ' AND hp.IsActive = :is_active';
            $params[':is_active'] = $filters['is_active'];
        }
        $sql .= ' ORDER BY hpr.ProviderName ASC, hp.PlanName ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPlan(int $planId): ?array
    {
        $sql = 'SELECT hp.*, hpr.ProviderName, hpr.ContactPerson, hpr.ContactEmail, hpr.ContactPhone, hpr.Address FROM HMOPlans hp LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID WHERE hp.PlanID = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $planId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function createPlan(array $data): int
    {
        $sql = 'INSERT INTO HMOPlans (ProviderID, PlanName, Description, CoverageType, MonthlyPremium, AnnualLimit, RoomAndBoardLimit, DoctorVisitLimit, EmergencyLimit, CoverageLimit, IsActive, EffectiveDate, EndDate, CreatedAt) VALUES (:provider_id, :plan_name, :description, :coverage_type, :monthly_premium, :annual_limit, :room_board_limit, :doctor_visit_limit, :emergency_limit, :coverage_limit, :is_active, :effective_date, :end_date, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':provider_id', $data['provider_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':plan_name', $data['plan_name'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':coverage_type', $data['coverage_type'] ?? 'Comprehensive', PDO::PARAM_STR);
        $stmt->bindValue(':monthly_premium', $data['monthly_premium'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':annual_limit', $data['annual_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':room_board_limit', $data['room_board_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':doctor_visit_limit', $data['doctor_visit_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':emergency_limit', $data['emergency_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':coverage_limit', $data['coverage_limit'] ?? $data['annual_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (int)$data['is_active'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':effective_date', $data['effective_date'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePlan(int $planId, array $data): bool
    {
        $sql = 'UPDATE HMOPlans SET ProviderID = :provider_id, PlanName = :plan_name, Description = :description, CoverageType = :coverage_type, MonthlyPremium = :monthly_premium, AnnualLimit = :annual_limit, RoomAndBoardLimit = :room_board_limit, DoctorVisitLimit = :doctor_visit_limit, EmergencyLimit = :emergency_limit, CoverageLimit = :coverage_limit, IsActive = :is_active, EffectiveDate = :effective_date, EndDate = :end_date, UpdatedAt = NOW() WHERE PlanID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':provider_id', $data['provider_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':plan_name', $data['plan_name'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':coverage_type', $data['coverage_type'] ?? 'Comprehensive', PDO::PARAM_STR);
        $stmt->bindValue(':monthly_premium', $data['monthly_premium'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':annual_limit', $data['annual_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':room_board_limit', $data['room_board_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':doctor_visit_limit', $data['doctor_visit_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':emergency_limit', $data['emergency_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':coverage_limit', $data['coverage_limit'] ?? $data['annual_limit'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':is_active', isset($data['is_active']) ? (int)$data['is_active'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':effective_date', $data['effective_date'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':id', $planId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function softDeletePlan(int $planId): bool
    {
        $sql = 'UPDATE HMOPlans SET IsActive = 0, UpdatedAt = NOW() WHERE PlanID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $planId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ------------------- Enrollments -------------------
    public function listEnrollments(int $page = 1, int $limit = 0, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * max(1, $limit));
        $sql = 'SELECT eh.EnrollmentID, eh.EmployeeID, eh.PlanID, eh.Status, eh.MonthlyDeduction, eh.EnrollmentDate, eh.EffectiveDate, eh.EndDate, e.FirstName, e.LastName, e.Email, hp.PlanName, hpr.ProviderName FROM EmployeeHMOEnrollments eh LEFT JOIN Employees e ON eh.EmployeeID = e.EmployeeID LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID WHERE 1=1';
        $params = [];
        if (!empty($filters['employee_id'])) {
            $sql .= ' AND eh.EmployeeID = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND eh.Status = :status';
            $params[':status'] = $filters['status'];
        }
        $sql .= ' ORDER BY eh.EnrollmentDate DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEmployeeEnrollment(int $employeeId, bool $activeOnly = true): array
    {
        $sql = 'SELECT eh.EnrollmentID, eh.EmployeeID, eh.PlanID, eh.Status, eh.MonthlyDeduction, eh.EnrollmentDate, eh.EffectiveDate, eh.EndDate, hp.PlanName, hp.Description, hp.CoverageType, hp.MonthlyPremium, hp.AnnualLimit, hpr.ProviderName, hpr.ContactPerson, hpr.ContactEmail, hpr.ContactPhone, hpr.Address FROM EmployeeHMOEnrollments eh LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID WHERE eh.EmployeeID = :employee_id';
        if ($activeOnly) {
            $sql .= " AND eh.Status = 'Active'";
        }
        $sql .= ' ORDER BY eh.EffectiveDate DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createEnrollment(array $data): int
    {
        // Deactivate previous active enrollments
        if (!empty($data['employee_id'])) {
            $deactSql = "UPDATE EmployeeHMOEnrollments SET Status = 'Inactive', EndDate = CURDATE(), UpdatedAt = NOW() WHERE EmployeeID = :employee_id AND Status = 'Active'";
            $dstmt = $this->pdo->prepare($deactSql);
            $dstmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
            $dstmt->execute();
        }

        $sql = 'INSERT INTO EmployeeHMOEnrollments (EmployeeID, PlanID, Status, MonthlyDeduction, EnrollmentDate, EffectiveDate, EndDate, CreatedAt) VALUES (:employee_id, :plan_id, :status, :monthly_deduction, :enrollment_date, :effective_date, :end_date, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':plan_id', $data['plan_id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'Active', PDO::PARAM_STR);
        $stmt->bindValue(':monthly_deduction', $data['monthly_deduction'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':enrollment_date', $data['enrollment_date'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':effective_date', $data['effective_date'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateEnrollment(int $enrollmentId, array $data): bool
    {
        $sql = 'UPDATE EmployeeHMOEnrollments SET PlanID = :plan_id, Status = :status, MonthlyDeduction = :monthly_deduction, EffectiveDate = :effective_date, EndDate = :end_date, UpdatedAt = NOW() WHERE EnrollmentID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':plan_id', $data['plan_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'Active', PDO::PARAM_STR);
        $stmt->bindValue(':monthly_deduction', $data['monthly_deduction'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':effective_date', $data['effective_date'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':id', $enrollmentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function terminateEnrollment(int $enrollmentId): bool
    {
        $sql = "UPDATE EmployeeHMOEnrollments SET Status = 'Inactive', EndDate = CURDATE(), UpdatedAt = NOW() WHERE EnrollmentID = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $enrollmentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ------------------- Claims -------------------
    private function generateClaimNumber(): string
    {
        return 'HMO' . date('Ym') . sprintf('%04d', mt_rand(1, 9999));
    }

    public function createClaim(array $data): array
    {
        $claimNumber = $this->generateClaimNumber();
        $attempts = 0;
        while ($attempts < 10) {
            $check = $this->pdo->prepare('SELECT COUNT(*) FROM HMOClaims WHERE ClaimNumber = :cn');
            $check->bindValue(':cn', $claimNumber, PDO::PARAM_STR);
            $check->execute();
            if ($check->fetchColumn() == 0) break;
            $claimNumber = $this->generateClaimNumber();
            $attempts++;
        }

        $sql = 'INSERT INTO HMOClaims (EnrollmentID, EmployeeID, ClaimNumber, ClaimType, ProviderName, Description, Amount, ClaimDate, Status, SubmittedDate) VALUES (:enrollment_id, :employee_id, :claim_number, :claim_type, :provider_name, :description, :amount, :claim_date, :status, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':enrollment_id', $data['enrollment_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $data['employee_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':claim_number', $claimNumber, PDO::PARAM_STR);
        $stmt->bindValue(':claim_type', $data['claim_type'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':provider_name', $data['provider_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':amount', $data['amount'] ?? 0, PDO::PARAM_STR);
        $stmt->bindValue(':claim_date', $data['claim_date'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Submitted', PDO::PARAM_STR);
        $stmt->execute();
        return ['claim_id' => (int)$this->pdo->lastInsertId(), 'claim_number' => $claimNumber];
    }

    public function listClaims(int $page = 1, int $limit = 0, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * max(1, $limit));
        $sql = 'SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.ClaimNumber, hc.ClaimType, hc.ProviderName, hc.Description, hc.Amount, hc.ClaimDate, hc.SubmittedDate, hc.ApprovedDate, hc.Status, hc.Comments, CONCAT(e.FirstName, " ", e.LastName) AS EmployeeName, hp.PlanName, hpr.ProviderName as HMOProviderName FROM HMOClaims hc LEFT JOIN employees e ON hc.EmployeeID = e.EmployeeID LEFT JOIN EmployeeHMOEnrollments eh ON hc.EnrollmentID = eh.EnrollmentID LEFT JOIN HMOPlans hp ON eh.PlanID = hp.PlanID LEFT JOIN HMOProviders hpr ON hp.ProviderID = hpr.ProviderID WHERE 1=1';
        $params = [];
        if (!empty($filters['employee_id'])) { $sql .= ' AND hc.EmployeeID = :employee_id'; $params[':employee_id'] = $filters['employee_id']; }
        if (!empty($filters['status'])) { $sql .= ' AND hc.Status = :status'; $params[':status'] = $filters['status']; }
        if (!empty($filters['claim_id'])) { $sql .= ' AND hc.ClaimID = :claim_id'; $params[':claim_id'] = $filters['claim_id']; }
        $sql .= ' ORDER BY hc.SubmittedDate DESC';
        if ($limit > 0) { $sql .= ' LIMIT :limit OFFSET :offset'; }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        if ($limit > 0) { $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT); $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateClaimStatus(int $claimId, string $status, ?string $comments = null, ?int $approvedBy = null): bool
    {
        $sql = 'UPDATE HMOClaims SET Status = :status, Comments = :comments, UpdatedAt = NOW()';
        if (in_array($status, ['Approved', 'Rejected'], true)) {
            $sql .= ', ApprovedDate = NOW(), ApprovedBy = :approved_by';
        }
        $sql .= ' WHERE ClaimID = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':comments', $comments ?? null, PDO::PARAM_STR);
        if (in_array($status, ['Approved', 'Rejected'], true)) {
            $stmt->bindValue(':approved_by', $approvedBy ?? null, PDO::PARAM_INT);
        }
        $stmt->bindValue(':id', $claimId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ------------------- Dashboard / Stats -------------------
    public function getDashboardStats(): array
    {
        $stats = [];
        $stats['totalProviders'] = (int)$this->pdo->query('SELECT COUNT(*) FROM HMOProviders WHERE IsActive = 1')->fetchColumn();
        $stats['activePlans'] = (int)$this->pdo->query('SELECT COUNT(*) FROM HMOPlans WHERE IsActive = 1')->fetchColumn();
        $stats['enrolledEmployees'] = (int)$this->pdo->query("SELECT COUNT(DISTINCT EmployeeID) FROM EmployeeHMOEnrollments WHERE Status = 'Active'")->fetchColumn();
        $stats['pendingClaims'] = (int)$this->pdo->query("SELECT COUNT(*) FROM HMOClaims WHERE Status = 'Submitted'")->fetchColumn();
        $stats['thisMonthClaims'] = (int)$this->pdo->query("SELECT COUNT(*) FROM HMOClaims WHERE MONTH(SubmittedDate) = MONTH(CURDATE()) AND YEAR(SubmittedDate) = YEAR(CURDATE())")->fetchColumn();
        $stats['thisMonthClaimAmount'] = (float)$this->pdo->query("SELECT COALESCE(SUM(Amount),0) FROM HMOClaims WHERE MONTH(SubmittedDate) = MONTH(CURDATE()) AND YEAR(SubmittedDate) = YEAR(CURDATE())")->fetchColumn();
        return $stats;
    }

    // ------------------- Notifications -------------------
    public function createNotification(int $employeeId, string $type, string $title, string $message): bool
    {
        $sql = 'INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message, CreatedAt) VALUES (:employee_id, :type, :title, :message, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        return $stmt->execute();
    }
}

