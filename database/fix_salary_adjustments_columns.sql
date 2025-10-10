-- Fix missing columns in salary_adjustments table
-- This script is idempotent and can be run multiple times

-- Check and add missing columns
SET @dbname = 'hr_integrated_db';

-- Add DepartmentID if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'DepartmentID');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN DepartmentID INT NULL AFTER EmployeeID',
    'SELECT "DepartmentID column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add GradeID if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'GradeID');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN GradeID INT NULL AFTER NewSalary',
    'SELECT "GradeID column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add StepID if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'StepID');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN StepID INT NULL AFTER GradeID',
    'SELECT "StepID column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ReasonID if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'ReasonID');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN ReasonID INT NULL AFTER StepID',
    'SELECT "ReasonID column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add Justification if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'Justification');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN Justification TEXT NULL AFTER ReasonID',
    'SELECT "Justification column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add AttachmentURL if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'AttachmentURL');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN AttachmentURL VARCHAR(255) NULL AFTER Justification',
    'SELECT "AttachmentURL column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ReviewedBy if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'ReviewedBy');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN ReviewedBy INT NULL',
    'SELECT "ReviewedBy column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ImplementedBy if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = @dbname 
                   AND TABLE_NAME = 'salary_adjustments' 
                   AND COLUMN_NAME = 'ImplementedBy');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE salary_adjustments ADD COLUMN ImplementedBy INT NULL',
    'SELECT "ImplementedBy column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final table structure
DESCRIBE salary_adjustments;

