-- HRCore Documents Upgrade (run in DB)

ALTER TABLE EmployeeDocuments
  ADD COLUMN IF NOT EXISTS Category VARCHAR(100) NULL AFTER DocumentType,
  ADD COLUMN IF NOT EXISTS Version INT DEFAULT 1 AFTER DocumentName,
  ADD COLUMN IF NOT EXISTS UploadedBy INT NULL AFTER EmployeeID,
  ADD COLUMN IF NOT EXISTS Checksum CHAR(64) NULL AFTER FilePath,
  ADD COLUMN IF NOT EXISTS ExpiresOn DATE NULL AFTER UploadedAt,
  ADD COLUMN IF NOT EXISTS Tags JSON NULL AFTER ExpiresOn,
  ADD COLUMN IF NOT EXISTS CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS UpdatedAt DATETIME NULL,
  ADD INDEX idx_empdoc_empid (EmployeeID),
  ADD INDEX idx_empdoc_expires (ExpiresOn),
  ADD INDEX idx_empdoc_category (Category);

CREATE TABLE IF NOT EXISTS DocumentAccessTokens (
  TokenID INT AUTO_INCREMENT PRIMARY KEY,
  DocumentID INT NOT NULL,
  Token VARCHAR(128) NOT NULL UNIQUE,
  ExpiresAt DATETIME NOT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CreatedBy INT NULL,
  INDEX idx_doc_token_expires (ExpiresAt)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS DocumentAuditLogs (
  AuditID INT AUTO_INCREMENT PRIMARY KEY,
  DocumentID INT NOT NULL,
  ActorUserID INT NULL,
  Action ENUM('Upload','View','Download','Delete','Update','NewVersion') NOT NULL,
  Details TEXT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doc_audit_doc (DocumentID)
) ENGINE=InnoDB;
