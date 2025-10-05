-- Positions & Pay Grades additions

ALTER TABLE hospital_job_roles
  ADD COLUMN IF NOT EXISTS HeadcountBudget INT NULL AFTER JobFamily,
  ADD COLUMN IF NOT EXISTS SalaryGrade VARCHAR(10) NULL AFTER JobLevel,
  ADD COLUMN IF NOT EXISTS PayGradeMin DECIMAL(12,2) NULL AFTER SalaryGrade,
  ADD COLUMN IF NOT EXISTS PayGradeMax DECIMAL(12,2) NULL AFTER PayGradeMin;

-- Seed common examples for public hospitals (adjust to your policies)
-- Example SG mapping for illustrative purposes
UPDATE hospital_job_roles SET SalaryGrade='SG15' WHERE RoleTitle LIKE 'Nurse I%';
UPDATE hospital_job_roles SET SalaryGrade='SG17' WHERE RoleTitle LIKE 'Nurse II%';
UPDATE hospital_job_roles SET SalaryGrade='SG19' WHERE RoleTitle LIKE 'Nurse III%';
UPDATE hospital_job_roles SET SalaryGrade='SG04' WHERE RoleTitle LIKE 'Nursing Attendant I%';
UPDATE hospital_job_roles SET SalaryGrade='SG06' WHERE RoleTitle LIKE 'Nursing Attendant II%';
UPDATE hospital_job_roles SET SalaryGrade='SG07' WHERE RoleTitle LIKE 'Ward Assistant%';
UPDATE hospital_job_roles SET SalaryGrade='SG24' WHERE RoleTitle LIKE 'Rural Health Physician%';
-- Minimum SG floor for doctors in government hospitals
UPDATE hospital_job_roles SET SalaryGrade='SG22' WHERE RoleTitle LIKE 'Physician%' AND (SalaryGrade IS NULL OR SalaryGrade < 'SG22');
