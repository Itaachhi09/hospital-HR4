<?php
/**
 * HR Core Document Model
 * Handles document data for HR Core integration
 */

class HRCoreDocument {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * List all HR Core documents with filters
     */
    public function listAll($filters = []) {
        $sql = "SELECT 
                    d.doc_id,
                    d.emp_id,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    d.title,
                    d.category,
                    d.module_origin,
                    d.uploaded_by,
                    d.upload_date,
                    d.status,
                    d.file_type,
                    d.file_size
                FROM hrcore_documents d
                LEFT JOIN employees e ON d.emp_id = e.EmployeeID
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['module_origin'])) {
            $sql .= " AND d.module_origin = ?";
            $params[] = $filters['module_origin'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND d.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.title LIKE ? OR CONCAT(e.FirstName, ' ', e.LastName) LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY d.upload_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get document by ID
     */
    public function getById($docId) {
        $sql = "SELECT 
                    d.doc_id,
                    d.emp_id,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    d.title,
                    d.category,
                    d.module_origin,
                    d.uploaded_by,
                    d.upload_date,
                    d.status,
                    d.file_type,
                    d.file_size,
                    d.file_path
                FROM hrcore_documents d
                LEFT JOIN employees e ON d.emp_id = e.EmployeeID
                WHERE d.doc_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$docId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get document count by module origin
     */
    public function getCountByModule($module) {
        $sql = "SELECT COUNT(*) as count FROM hrcore_documents WHERE module_origin = ? AND status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$module]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Get document count by category
     */
    public function getCountByCategory($category) {
        $sql = "SELECT COUNT(*) as count FROM hrcore_documents WHERE category = ? AND status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$category]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Get total document count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM hrcore_documents WHERE status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Add document from HR1/HR2 integration
     */
    public function addFromIntegration($data) {
        $sql = "INSERT INTO hrcore_documents 
                (emp_id, module_origin, category, title, file_path, uploaded_by, upload_date, status, file_type, file_size)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'active', ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['emp_id'],
            $data['module_origin'],
            $data['category'],
            $data['title'],
            $data['file_path'],
            $data['uploaded_by'],
            $data['file_type'],
            $data['file_size']
        ]);
    }

    /**
     * Update document status
     */
    public function updateStatus($docId, $status) {
        $sql = "UPDATE hrcore_documents SET status = ? WHERE doc_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $docId]);
    }

    /**
     * Get document categories with descriptions
     */
    public function getCategories() {
        return [
            'A' => [
                'name' => 'Initial Application Requirements',
                'description' => 'Documents required for initial application and screening',
                'items' => [
                    'Application Letter / Cover Letter',
                    'Updated Resume or Personal Data Sheet (PDS)',
                    'Diploma and Transcript of Records (TOR)',
                    'PRC License / Certificate of Board Rating',
                    'Training / Seminar Certificates (BLS, ACLS, IVT, etc.)',
                    'Certificate of Employment (if experienced)',
                    'NBI Clearance',
                    'Recent 2×2 ID Picture(s)',
                    'Valid Government ID (PhilID, Passport, Driver\'s License, etc.)'
                ]
            ],
            'B' => [
                'name' => 'Pre-Employment / Hiring Requirements',
                'description' => 'Documents submitted after passing interview or receiving job offer',
                'items' => [
                    'SSS E-1 or SSS Number Slip',
                    'PhilHealth Member Data Record (MDR)',
                    'Pag-IBIG (HDMF) Member\'s Data Form / MID Number',
                    'TIN (Tax Identification Number)',
                    'Medical Certificate / Pre-employment Medical Exam Results',
                    'Barangay Clearance / Police Clearance',
                    'Birth Certificate (PSA)',
                    'Marriage Certificate (if applicable)',
                    'Certificate of Good Moral Character',
                    '2×2 or Passport-size Photos (additional copies)',
                    'Updated Resume (signed hard copy)'
                ]
            ],
            'C' => [
                'name' => 'Optional / Position-Specific Requirements',
                'description' => 'For doctors, nurses, and other licensed or specialized staff',
                'items' => [
                    'Board Certificate / PRC ID',
                    'BLS / ACLS / PALS Certificates',
                    'Certificate of Immunization / Hepatitis B Vaccination Record',
                    'Specialized Training Certificates (OR Nursing, Dialysis, Radiologic Safety, etc.)',
                    'Performance Evaluation (if transferring from another DOH hospital)'
                ]
            ]
        ];
    }
}
?>
