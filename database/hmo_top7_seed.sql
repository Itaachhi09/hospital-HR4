-- Seed file: Top 7 Philippine HMO providers and sample plans
-- Run: mysql -u root -p your_db < hmo_top7_seed.sql

-- Ensure tables exist (add columns if needed)
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS ProviderName VARCHAR(255) NOT NULL;
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS Description TEXT NULL;
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS ContactPerson VARCHAR(255) NULL;
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS ContactNumber VARCHAR(50) NULL;
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS Email VARCHAR(255) NULL;
ALTER TABLE IF EXISTS HMOProviders ADD COLUMN IF NOT EXISTS Status ENUM('Active','Inactive') DEFAULT 'Active';

ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS Coverage JSON NULL;
ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS AccreditedHospitals TEXT NULL;
ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS Eligibility VARCHAR(64) DEFAULT 'Individual';
ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS MaximumBenefitLimit DECIMAL(12,2) NULL;
ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS PremiumCost DECIMAL(12,2) NULL;
ALTER TABLE IF EXISTS HMOPlans ADD COLUMN IF NOT EXISTS Status ENUM('Active','Inactive') DEFAULT 'Active';

-- Insert providers (if not exists) and sample plans
-- Maxicare
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Maxicare', 'Maxicare Healthcare Corporation', 'Support', '09171234567', 'support@maxicare.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Maxicare') LIMIT 1;

-- Medicard
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Medicard', 'Medicard Philippines, Inc.', 'Support', '09171234568', 'support@medicard.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Medicard') LIMIT 1;

-- Intellicare (Asalus Corp.)
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Intellicare', 'Intellicare (Asalus Corp.)', 'Support', '09171234569', 'support@intellicare.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Intellicare') LIMIT 1;

-- PhilCare
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'PhilCare', 'PhilCare (PhilHealthCare, Inc.)', 'Support', '09171234570', 'support@philcare.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='PhilCare') LIMIT 1;

-- Kaiser
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Kaiser', 'Kaiser Philippines', 'Support', '09171234571', 'support@kaiser.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Kaiser') LIMIT 1;

-- Insular Health Care
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Insular Health Care', 'Insular Health Care (iCare)', 'Support', '09171234572', 'support@insularicare.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Insular Health Care') LIMIT 1;

-- Value Care
INSERT INTO HMOProviders (ProviderName, Description, ContactPerson, ContactNumber, Email, Status)
SELECT 'Value Care', 'Value Care (ValuCare)', 'Support', '09171234573', 'support@valuecare.com.ph', 'Active'
WHERE NOT EXISTS (SELECT 1 FROM HMOProviders WHERE ProviderName='Value Care') LIMIT 1;

-- Insert sample plans for each provider
-- Maxicare plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Individual', JSON_ARRAY('inpatient','outpatient','emergency'), JSON_ARRAY('St. Luke\'s Medical Center','The Medical City','Makati Medical Center'), 'Individual', 500000.00, 1500.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Maxicare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Individual' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Family', JSON_ARRAY('inpatient','outpatient','emergency','dental','preventive'), JSON_ARRAY('St. Luke\'s Medical Center','The Medical City','Makati Medical Center'), 'Family', 1000000.00, 4000.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Maxicare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Family' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('St. Luke\'s Medical Center','The Medical City','Makati Medical Center'), 'Corporate', 2000000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Maxicare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- Medicard plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Classic', JSON_ARRAY('inpatient','outpatient','emergency'), JSON_ARRAY('MediMed Hospital','Universal Medical Center'), 'Individual', 400000.00, 1200.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Medicard' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Classic' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'VIP', JSON_ARRAY('inpatient','outpatient','emergency','dental','preventive'), JSON_ARRAY('St. Luke\'s Medical Center','Makati Medical Center'), 'Individual', 800000.00, 3000.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Medicard' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='VIP' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('Universal Medical Center','MediMed Hospital'), 'Corporate', 1500000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Medicard' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- Intellicare plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Flexicare', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('The Medical City','Asian Hospital'), 'Individual', 600000.00, 1800.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Intellicare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Flexicare' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate Health Plans', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('The Medical City','St. Luke\'s Medical Center'), 'Corporate', 1500000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Intellicare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate Health Plans' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- PhilCare plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Health PRO', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('Makati Medical Center','St. Luke\'s Medical Center'), 'Individual', 700000.00, 2000.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='PhilCare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Health PRO' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'ER Vantage', JSON_ARRAY('emergency'), JSON_ARRAY('The Medical City','St. Luke\'s Medical Center'), 'Individual', 200000.00, 500.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='PhilCare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='ER Vantage' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate', JSON_ARRAY('inpatient','outpatient','emergency'), JSON_ARRAY('Makati Medical Center','The Medical City'), 'Corporate', 1500000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='PhilCare' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- Kaiser plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Kaiser Ultimate Health Builder', JSON_ARRAY('inpatient','outpatient','emergency','preventive','dental'), JSON_ARRAY('Kaiser Clinic','Makati Medical Center'), 'Individual', 900000.00, 3200.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Kaiser' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Kaiser Ultimate Health Builder' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('Kaiser Clinic','St. Luke\'s Medical Center'), 'Corporate', 1800000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Kaiser' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- Insular Health Care plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'iCare', JSON_ARRAY('inpatient','outpatient','emergency'), JSON_ARRAY('Insular Hospital','MediMed Hospital'), 'Individual', 350000.00, 1000.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Insular Health Care' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='iCare' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate Care', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('Insular Hospital','Universal Medical Center'), 'Corporate', 1200000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Insular Health Care' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate Care' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- Value Care plans
INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Individual', JSON_ARRAY('inpatient','outpatient','emergency'), JSON_ARRAY('ValueCare Clinic','MediMed Hospital'), 'Individual', 300000.00, 900.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Value Care' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Individual' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Family', JSON_ARRAY('inpatient','outpatient','emergency','dental'), JSON_ARRAY('ValueCare Clinic','St. Luke\'s Medical Center'), 'Family', 700000.00, 2500.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Value Care' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Family' AND pl.ProviderID=p.ProviderID) LIMIT 1;

INSERT INTO HMOPlans (ProviderID, PlanName, Coverage, AccreditedHospitals, Eligibility, MaximumBenefitLimit, PremiumCost, Status)
SELECT p.ProviderID, 'Corporate', JSON_ARRAY('inpatient','outpatient','emergency','preventive'), JSON_ARRAY('ValueCare Clinic','MediMed Hospital'), 'Corporate', 1000000.00, 0.00, 'Active'
FROM HMOProviders p WHERE p.ProviderName='Value Care' AND NOT EXISTS (SELECT 1 FROM HMOPlans pl WHERE pl.PlanName='Corporate' AND pl.ProviderID=p.ProviderID) LIMIT 1;

-- End of seed
