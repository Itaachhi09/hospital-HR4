-- Map SalaryGrade codes to hospital_job_roles by RoleTitle
-- Run this after hospital_hr_structure_update.sql created roles

UPDATE hospital_job_roles SET SalaryGrade='SG13' WHERE RoleTitle='Benefits Specialist';
UPDATE hospital_job_roles SET SalaryGrade='SG22' WHERE RoleTitle='Compensation & Benefits Manager';
UPDATE hospital_job_roles SET SalaryGrade='SG10' WHERE RoleTitle='Payroll Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG21' WHERE RoleTitle='Employee Relations Manager';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='Engagement Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG11' WHERE RoleTitle='HR Coordinator - Facilities';
UPDATE hospital_job_roles SET SalaryGrade='SG11' WHERE RoleTitle='HR Coordinator - Finance';
UPDATE hospital_job_roles SET SalaryGrade='SG29' WHERE RoleTitle='Hospital Director';
UPDATE hospital_job_roles SET SalaryGrade='SG20' WHERE RoleTitle='HR Administration Manager';
UPDATE hospital_job_roles SET SalaryGrade='SG13' WHERE RoleTitle='HR Officer - Compliance';
UPDATE hospital_job_roles SET SalaryGrade='SG14' WHERE RoleTitle='HRIS Administrator';
UPDATE hospital_job_roles SET SalaryGrade='SG14' WHERE RoleTitle='Labor Relations Specialist';
UPDATE hospital_job_roles SET SalaryGrade='SG26' WHERE RoleTitle='Chief Human Resources Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG24' WHERE RoleTitle='HR Director';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='HR Coordinator - IT';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='HR Coordinator - Laboratory';
UPDATE hospital_job_roles SET SalaryGrade='SG27' WHERE RoleTitle='Chief Medical Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='HR Coordinator - Nursing';
UPDATE hospital_job_roles SET SalaryGrade='SG15' WHERE RoleTitle='Company Nurse';
UPDATE hospital_job_roles SET SalaryGrade='SG23' WHERE RoleTitle='Occupational Health Physician';
UPDATE hospital_job_roles SET SalaryGrade='SG13' WHERE RoleTitle='Safety Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='HR Coordinator - Pharmacy';
UPDATE hospital_job_roles SET SalaryGrade='SG12' WHERE RoleTitle='HR Coordinator - Radiology';
UPDATE hospital_job_roles SET SalaryGrade='SG11' WHERE RoleTitle='Onboarding Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG21' WHERE RoleTitle='Recruitment Manager';
UPDATE hospital_job_roles SET SalaryGrade='SG13' WHERE RoleTitle='Recruitment Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG13' WHERE RoleTitle='Learning & Development Officer';
UPDATE hospital_job_roles SET SalaryGrade='SG21' WHERE RoleTitle='Training & Development Manager';
UPDATE hospital_job_roles SET SalaryGrade='SG11' WHERE RoleTitle='Training Coordinator';

-- Optional: Ensure PayGradeMin/Max reflect SSL VI 2025 step ranges
-- Execute database/paygrade_ssl_vi_2025_steps.sql after this mapping to populate ranges


