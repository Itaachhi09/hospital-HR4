-- HR Core Documents Table
-- Stores all employee documents from HR1 and HR2 systems

CREATE TABLE IF NOT EXISTS hrcore_documents (
    doc_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    module_origin ENUM('HR1', 'HR2') NOT NULL COMMENT 'Source module: HR1 (Recruitment) or HR2 (Training & Performance)',
    category ENUM('A', 'B', 'C') NOT NULL COMMENT 'Document category: A (Initial Application), B (Pre-Employment), C (Position-Specific)',
    title VARCHAR(255) NOT NULL COMMENT 'Document title/name',
    file_path VARCHAR(500) NOT NULL COMMENT 'Server file path',
    file_type VARCHAR(50) NOT NULL COMMENT 'File MIME type (application/pdf, image/jpeg, etc.)',
    file_size INT NOT NULL COMMENT 'File size in bytes',
    uploaded_by VARCHAR(100) NOT NULL COMMENT 'User who uploaded the document',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When document was uploaded',
    status ENUM('active', 'archived', 'expired') DEFAULT 'active' COMMENT 'Document status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_emp_id (emp_id),
    INDEX idx_module_origin (module_origin),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_upload_date (upload_date),
    INDEX idx_emp_module (emp_id, module_origin),
    INDEX idx_category_status (category, status),
    
    -- Foreign key constraint
    FOREIGN KEY (emp_id) REFERENCES employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='HR Core document storage for integrated HR1 and HR2 documents';

-- Insert sample data for testing
INSERT INTO hrcore_documents (emp_id, module_origin, category, title, file_path, file_type, file_size, uploaded_by) VALUES
(1, 'HR1', 'A', 'Application Letter - John Doe', '/uploads/hr1/applications/john_doe_application.pdf', 'application/pdf', 245760, 'HR1 System'),
(1, 'HR1', 'A', 'Resume - John Doe', '/uploads/hr1/resumes/john_doe_resume.pdf', 'application/pdf', 189440, 'HR1 System'),
(1, 'HR1', 'A', 'Diploma - Nursing', '/uploads/hr1/education/john_doe_diploma.pdf', 'application/pdf', 512000, 'HR1 System'),
(1, 'HR1', 'A', 'PRC License', '/uploads/hr1/licenses/john_doe_prc.pdf', 'application/pdf', 156720, 'HR1 System'),
(1, 'HR1', 'A', 'NBI Clearance', '/uploads/hr1/clearances/john_doe_nbi.pdf', 'application/pdf', 98765, 'HR1 System'),
(1, 'HR1', 'B', 'SSS E-1 Form', '/uploads/hr1/employment/john_doe_sss.pdf', 'application/pdf', 123456, 'HR1 System'),
(1, 'HR1', 'B', 'PhilHealth MDR', '/uploads/hr1/employment/john_doe_philhealth.pdf', 'application/pdf', 98765, 'HR1 System'),
(1, 'HR1', 'B', 'Medical Certificate', '/uploads/hr1/medical/john_doe_medical.pdf', 'application/pdf', 234567, 'HR1 System'),
(1, 'HR2', 'C', 'BLS Certificate', '/uploads/hr2/training/john_doe_bls.pdf', 'application/pdf', 178432, 'HR2 System'),
(1, 'HR2', 'C', 'ACLS Certificate', '/uploads/hr2/training/john_doe_acls.pdf', 'application/pdf', 198765, 'HR2 System'),
(1, 'HR2', 'C', 'Performance Evaluation Q1', '/uploads/hr2/performance/john_doe_perf_q1.pdf', 'application/pdf', 312456, 'HR2 System'),
(2, 'HR1', 'A', 'Application Letter - Jane Smith', '/uploads/hr1/applications/jane_smith_application.pdf', 'application/pdf', 267890, 'HR1 System'),
(2, 'HR1', 'A', 'Resume - Jane Smith', '/uploads/hr1/resumes/jane_smith_resume.pdf', 'application/pdf', 201234, 'HR1 System'),
(2, 'HR1', 'B', 'SSS E-1 Form', '/uploads/hr1/employment/jane_smith_sss.pdf', 'application/pdf', 134567, 'HR1 System'),
(2, 'HR2', 'C', 'Specialized Training - Dialysis', '/uploads/hr2/training/jane_smith_dialysis.pdf', 'application/pdf', 456789, 'HR2 System');
