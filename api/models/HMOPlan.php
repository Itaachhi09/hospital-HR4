<?php
/**
 * HMO Plan Model
 * Manages HMO plan operations including coverage tracking
 */

class HMOPlan {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all plans with optional filters
     */
    public function getPlans($filters = []) {
        $sql = "SELECT 
                    p.*,
                    pr.ProviderName,
                    pr.CompanyName as ProviderCompany,
                    COUNT(DISTINCT e.EnrollmentID) as enrolled_count
                FROM hmoplans p
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN employeehmoenrollments e ON p.PlanID = e.PlanID AND e.Status = 'Active'
                WHERE 1=1";

        $params = [];

        if (isset($filters['provider_id'])) {
            $sql .= " AND p.ProviderID = :provider_id";
            $params[':provider_id'] = $filters['provider_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND p.IsActive = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if (!empty($filters['coverage_type'])) {
            $sql .= " AND p.CoverageType LIKE :coverage_type";
            $params[':coverage_type'] = '%' . $filters['coverage_type'] . '%';
        }

        if (!empty($filters['plan_category'])) {
            $sql .= " AND p.PlanCategory = :plan_category";
            $params[':plan_category'] = $filters['plan_category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.PlanName LIKE :search OR p.Description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY p.PlanID ORDER BY pr.ProviderName, p.PlanName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normalize JSON fields
        foreach ($plans as &$plan) {
            $plan['CoverageType'] = $this->normalizeJsonField($plan['CoverageType']);
            $plan['AccreditedHospitals'] = $this->normalizeJsonField($plan['AccreditedHospitals']);
        }

        return $plans;
    }

    /**
     * Get plan by ID
     */
    public function getPlanById($planId) {
        $sql = "SELECT 
                    p.*,
                    pr.ProviderName,
                    pr.CompanyName as ProviderCompany,
                    pr.ContactEmail as ProviderEmail,
                    pr.ContactPhone as ProviderPhone
                FROM hmoplans p
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                WHERE p.PlanID = :plan_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            $plan['CoverageType'] = $this->normalizeJsonField($plan['CoverageType']);
            $plan['AccreditedHospitals'] = $this->normalizeJsonField($plan['AccreditedHospitals']);
        }

        return $plan;
    }

    /**
     * Create new plan
     */
    public function createPlan($data) {
        $sql = "INSERT INTO hmoplans (
                    ProviderID, PlanName, PlanCode, Description, CoverageType,
                    PlanCategory, MonthlyPremium, MaximumBenefitLimit,
                    AccreditedHospitals, EligibilityRequirements, IsActive, EffectiveDate
                ) VALUES (
                    :provider_id, :plan_name, :plan_code, :description, :coverage_type,
                    :plan_category, :monthly_premium, :maximum_benefit_limit,
                    :accredited_hospitals, :eligibility_requirements, :is_active, :effective_date
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':provider_id' => $data['provider_id'],
            ':plan_name' => $data['plan_name'],
            ':plan_code' => $data['plan_code'] ?? null,
            ':description' => $data['description'] ?? null,
            ':coverage_type' => $this->encodeJsonField($data['coverage_type'] ?? []),
            ':plan_category' => $data['plan_category'] ?? 'Individual',
            ':monthly_premium' => $data['monthly_premium'] ?? 0,
            ':maximum_benefit_limit' => $data['maximum_benefit_limit'] ?? 0,
            ':accredited_hospitals' => $this->encodeJsonField($data['accredited_hospitals'] ?? []),
            ':eligibility_requirements' => $data['eligibility_requirements'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':effective_date' => $data['effective_date'] ?? date('Y-m-d')
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update plan
     */
    public function updatePlan($planId, $data) {
        $sql = "UPDATE hmoplans SET
                    ProviderID = :provider_id,
                    PlanName = :plan_name,
                    PlanCode = :plan_code,
                    Description = :description,
                    CoverageType = :coverage_type,
                    PlanCategory = :plan_category,
                    MonthlyPremium = :monthly_premium,
                    MaximumBenefitLimit = :maximum_benefit_limit,
                    AccreditedHospitals = :accredited_hospitals,
                    EligibilityRequirements = :eligibility_requirements,
                    IsActive = :is_active,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE PlanID = :plan_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':provider_id' => $data['provider_id'],
            ':plan_name' => $data['plan_name'],
            ':plan_code' => $data['plan_code'] ?? null,
            ':description' => $data['description'] ?? null,
            ':coverage_type' => $this->encodeJsonField($data['coverage_type'] ?? []),
            ':plan_category' => $data['plan_category'] ?? 'Individual',
            ':monthly_premium' => $data['monthly_premium'] ?? 0,
            ':maximum_benefit_limit' => $data['maximum_benefit_limit'] ?? 0,
            ':accredited_hospitals' => $this->encodeJsonField($data['accredited_hospitals'] ?? []),
            ':eligibility_requirements' => $data['eligibility_requirements'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':plan_id' => $planId
        ]);
    }

    /**
     * Delete plan (soft delete)
     */
    public function deletePlan($planId) {
        // Check for active enrollments
        $checkSql = "SELECT COUNT(*) FROM employeehmoenrollments WHERE PlanID = :plan_id AND Status = 'Active'";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([':plan_id' => $planId]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete plan with active enrollments");
        }

        $sql = "UPDATE hmoplans SET IsActive = 0, UpdatedAt = CURRENT_TIMESTAMP WHERE PlanID = :plan_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':plan_id' => $planId]);
    }

    /**
     * Get plan utilization metrics
     */
    public function getPlanUtilization($planId = null) {
        $sql = "SELECT 
                    p.PlanID,
                    p.PlanName,
                    pr.ProviderName,
                    p.MaximumBenefitLimit,
                    COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                    COUNT(DISTINCT CASE WHEN e.Status = 'Active' THEN e.EnrollmentID END) as active_enrollments,
                    COUNT(DISTINCT c.ClaimID) as total_claims,
                    COALESCE(SUM(c.Amount), 0) as total_claim_amount,
                    COALESCE(AVG(c.Amount), 0) as avg_claim_amount,
                    COALESCE(SUM(CASE WHEN c.Status = 'Pending' THEN c.Amount ELSE 0 END), 0) as pending_claim_amount
                FROM hmoplans p
                JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID
                LEFT JOIN employeehmoenrollments e ON p.PlanID = e.PlanID
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID";

        if ($planId) {
            $sql .= " WHERE p.PlanID = :plan_id";
        } else {
            $sql .= " WHERE p.IsActive = 1";
        }

        $sql .= " GROUP BY p.PlanID, p.PlanName, pr.ProviderName, p.MaximumBenefitLimit
                  ORDER BY total_enrollments DESC";

        $stmt = $this->pdo->prepare($sql);
        if ($planId) {
            $stmt->execute([':plan_id' => $planId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Helper: Normalize JSON field from database
     */
    private function normalizeJsonField($value) {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try as CSV
        $arr = array_filter(array_map('trim', explode(',', $value)));
        return array_values($arr);
    }

    /**
     * Helper: Encode JSON field for database
     */
    private function encodeJsonField($value) {
        if (is_array($value)) {
            return json_encode(array_values(array_filter($value)));
        }

        if (is_string($value)) {
            // Try parsing as JSON first
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return json_encode($decoded);
            }
            // Treat as CSV
            $arr = array_filter(array_map('trim', explode(',', $value)));
            return json_encode(array_values($arr));
        }

        return json_encode([]);
    }
}

