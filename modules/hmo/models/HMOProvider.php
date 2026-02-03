<?php
/**
 * HMO Provider Model
 * Manages HMO provider operations including performance metrics
 */

class HMOProvider {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all providers with optional filters
     */
    public function getProviders($filters = []) {
        $sql = "SELECT 
                    ProviderID,
                    ProviderName,
                    CompanyName,
                    ContactPerson,
                    ContactEmail,
                    ContactPhone,
                    Address,
                    Website,
                    Description,
                    EstablishedYear,
                    AccreditationNumber,
                    ServiceAreas,
                    IsActive,
                    CreatedAt,
                    UpdatedAt
                FROM hmoproviders
                WHERE 1=1";

        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND IsActive = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (ProviderName LIKE :search OR CompanyName LIKE :search OR Description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY ProviderName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get provider by ID
     */
    public function getProviderById($providerId) {
        $sql = "SELECT * FROM hmoproviders WHERE ProviderID = :provider_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':provider_id' => $providerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new provider
     */
    public function createProvider($data) {
        $sql = "INSERT INTO hmoproviders (
                    ProviderName, CompanyName, ContactPerson, ContactEmail, 
                    ContactPhone, Address, Website, Description, 
                    EstablishedYear, AccreditationNumber, ServiceAreas, IsActive
                ) VALUES (
                    :provider_name, :company_name, :contact_person, :contact_email,
                    :contact_phone, :address, :website, :description,
                    :established_year, :accreditation_number, :service_areas, :is_active
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':provider_name' => $data['provider_name'],
            ':company_name' => $data['company_name'] ?? $data['provider_name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':website' => $data['website'] ?? null,
            ':description' => $data['description'] ?? null,
            ':established_year' => $data['established_year'] ?? null,
            ':accreditation_number' => $data['accreditation_number'] ?? null,
            ':service_areas' => $data['service_areas'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update provider
     */
    public function updateProvider($providerId, $data) {
        $sql = "UPDATE hmoproviders SET
                    ProviderName = :provider_name,
                    CompanyName = :company_name,
                    ContactPerson = :contact_person,
                    ContactEmail = :contact_email,
                    ContactPhone = :contact_phone,
                    Address = :address,
                    Website = :website,
                    Description = :description,
                    EstablishedYear = :established_year,
                    AccreditationNumber = :accreditation_number,
                    ServiceAreas = :service_areas,
                    IsActive = :is_active,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE ProviderID = :provider_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':provider_name' => $data['provider_name'],
            ':company_name' => $data['company_name'] ?? $data['provider_name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':website' => $data['website'] ?? null,
            ':description' => $data['description'] ?? null,
            ':established_year' => $data['established_year'] ?? null,
            ':accreditation_number' => $data['accreditation_number'] ?? null,
            ':service_areas' => $data['service_areas'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':provider_id' => $providerId
        ]);
    }

    /**
     * Delete provider (soft delete)
     */
    public function deleteProvider($providerId) {
        // Check if provider has active plans
        $checkSql = "SELECT COUNT(*) FROM hmoplans WHERE ProviderID = :provider_id AND IsActive = 1";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([':provider_id' => $providerId]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete provider with active plans");
        }

        $sql = "UPDATE hmoproviders SET IsActive = 0, UpdatedAt = CURRENT_TIMESTAMP WHERE ProviderID = :provider_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':provider_id' => $providerId]);
    }

    /**
     * Get provider performance metrics
     */
    public function getProviderMetrics($providerId) {
        $sql = "SELECT 
                    p.ProviderID,
                    p.ProviderName,
                    COUNT(DISTINCT pl.PlanID) as total_plans,
                    COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                    COUNT(DISTINCT c.ClaimID) as total_claims,
                    COUNT(DISTINCT CASE WHEN c.Status = 'Approved' THEN c.ClaimID END) as approved_claims,
                    COUNT(DISTINCT CASE WHEN c.Status = 'Pending' THEN c.ClaimID END) as pending_claims,
                    COALESCE(SUM(CASE WHEN c.Status = 'Approved' THEN c.Amount ELSE 0 END), 0) as total_claim_amount,
                    COALESCE(AVG(DATEDIFF(c.ApprovedDate, c.SubmittedDate)), 0) as avg_processing_days
                FROM hmoproviders p
                LEFT JOIN hmoplans pl ON p.ProviderID = pl.ProviderID
                LEFT JOIN employeehmoenrollments e ON pl.PlanID = e.PlanID AND e.Status = 'Active'
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE p.ProviderID = :provider_id
                GROUP BY p.ProviderID, p.ProviderName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':provider_id' => $providerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all providers with their metrics
     */
    public function getProvidersWithMetrics() {
        $sql = "SELECT 
                    p.ProviderID,
                    p.ProviderName,
                    p.CompanyName,
                    p.ContactPerson,
                    p.ContactEmail,
                    p.ContactPhone,
                    p.IsActive,
                    COUNT(DISTINCT pl.PlanID) as total_plans,
                    COUNT(DISTINCT e.EnrollmentID) as total_enrollments,
                    COUNT(DISTINCT CASE WHEN c.Status = 'Pending' THEN c.ClaimID END) as pending_claims,
                    COALESCE(AVG(DATEDIFF(c.ApprovedDate, c.SubmittedDate)), 0) as avg_processing_days
                FROM hmoproviders p
                LEFT JOIN hmoplans pl ON p.ProviderID = pl.ProviderID
                LEFT JOIN employeehmoenrollments e ON pl.PlanID = e.PlanID AND e.Status = 'Active'
                LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                WHERE p.IsActive = 1
                GROUP BY p.ProviderID, p.ProviderName, p.CompanyName, p.ContactPerson, p.ContactEmail, p.ContactPhone, p.IsActive
                ORDER BY p.ProviderName";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

