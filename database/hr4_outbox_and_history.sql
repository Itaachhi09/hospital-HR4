-- Outbox and Employment History tables (apply via your DB tool)

CREATE TABLE IF NOT EXISTS OutboxEvents (
  OutboxID INT AUTO_INCREMENT PRIMARY KEY,
  EventType VARCHAR(100) NOT NULL,
  PayloadJSON LONGTEXT NOT NULL,
  CreatedAt DATETIME NOT NULL,
  ProcessedAt DATETIME NULL,
  Status ENUM('Pending','Processed','Failed') DEFAULT 'Pending',
  ErrorMessage TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS EmploymentHistory (
  HistoryID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  EventType ENUM('Promotion','Transfer','StatusChange') NOT NULL,
  OldDepartmentID INT NULL,
  NewDepartmentID INT NULL,
  OldJobTitle VARCHAR(150) NULL,
  NewJobTitle VARCHAR(150) NULL,
  OldManagerID INT NULL,
  NewManagerID INT NULL,
  OldEmploymentStatus VARCHAR(50) NULL,
  NewEmploymentStatus VARCHAR(50) NULL,
  EffectiveDate DATE NULL,
  Notes TEXT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
