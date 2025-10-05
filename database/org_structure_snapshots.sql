-- Org Structure Snapshots for versioning

CREATE TABLE IF NOT EXISTS OrgStructureSnapshots (
  SnapshotID INT AUTO_INCREMENT PRIMARY KEY,
  SnapshotName VARCHAR(120) NULL,
  TakenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TakenBy INT NULL,
  Notes TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS OrgStructureSnapshotNodes (
  SnapshotNodeID INT AUTO_INCREMENT PRIMARY KEY,
  SnapshotID INT NOT NULL,
  DepartmentID INT NOT NULL,
  ParentDepartmentID INT NULL,
  DepartmentName VARCHAR(200) NOT NULL,
  DepartmentType VARCHAR(60) NULL,
  ManagerID INT NULL,
  EmployeeCount INT NOT NULL DEFAULT 0,
  HeadcountBudget INT NULL,
  VacantPositions INT NULL,
  TotalCost DECIMAL(18,2) NULL,
  INDEX idx_snapnode_snapshot (SnapshotID),
  FOREIGN KEY (SnapshotID) REFERENCES OrgStructureSnapshots(SnapshotID)
) ENGINE=InnoDB;
