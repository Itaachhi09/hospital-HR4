-- Compatibility-only INSERT seed for Top-7 HMOs and sample plans
-- Safe to import on most MySQL/MariaDB versions. Idempotent checks are not included, so run on a fresh or test DB.

-- INSERT providers
INSERT INTO `hmoproviders` (`ProviderID`,`ProviderName`,`CompanyName`,`ContactPerson`,`ContactEmail`,`ContactPhone`,`Address`,`Website`,`Description`,`EstablishedYear`,`AccreditationNumber`,`ServiceAreas`,`IsActive`,`CreatedAt`,`UpdatedAt`) VALUES
(1,'Maxicare','Maxicare Healthcare Corporation','Customer Service Manager','customercare@maxicare.com.ph','+63-2-8711-9000','7th Floor, The Enterprise Center, Makati City','https://www.maxicare.com.ph','Leading HMO provider in the Philippines',1987,'DOH-LTO-HMO-001','Metro Manila, Cebu, Davao',1, NOW(), NOW()),
(2,'Medicard','Medicard Philippines, Inc.','Customer Relations Head','customercare@medicard.com.ph','+63-2-8985-9999','2nd Floor, Prestige Tower, Ortigas Center, Pasig City','https://www.medicard.com.ph','Premier healthcare provider',1982,'DOH-LTO-HMO-002','Metro Manila, Laguna, Cavite',1, NOW(), NOW()),
(3,'Intellicare','Asalus Corporation','Client Services Director','customerservice@intellicare.com.ph','+63-2-8894-7777','Tower One & Exchange Plaza, Makati City','https://www.intellicare.com.ph','Flexible healthcare solutions',1997,'DOH-LTO-HMO-003','Metro Manila, Cebu, Davao',1, NOW(), NOW()),
(4,'PhilCare','PhilHealthCare, Inc.','Operations Manager','customercare@philcare.com.ph','+63-2-8638-9999','Ayala Life-FGU Center, Makati City','https://www.philcare.com.ph','Comprehensive healthcare management',1994,'DOH-LTO-HMO-004','Metro Manila, Cebu, Davao',1, NOW(), NOW()),
(5,'Kaiser','Kaiser International Health Group, Inc.','Account Manager','info@kaiser.com.ph','+63-2-8892-2222','Petron Megaplaza, Makati City','https://www.kaiser.com.ph','International standard healthcare coverage',1993,'DOH-LTO-HMO-005','Metro Manila, Cebu, Davao',1, NOW(), NOW()),
(6,'Insular Health Care','Insular Health Care, Inc.','Client Relations Manager','customercare@insularhealthcare.com.ph','+63-2-8818-9999','Insular Life Building, Makati City','https://www.insularhealthcare.com.ph','Comprehensive health maintenance organization',1990,'DOH-LTO-HMO-006','Metro Manila, Laguna, Cavite',1, NOW(), NOW()),
(7,'ValuCare','Value Care Health Systems, Inc.','Customer Support Head','customerservice@valucare.com.ph','+63-2-8756-8888','Orient Square Building, Ortigas Center, Pasig City','https://www.valucare.com.ph','Affordable healthcare solutions',1996,'DOH-LTO-HMO-007','Metro Manila, Central Luzon, Cebu',1, NOW(), NOW());

-- INSERT sample plans (one per provider)
INSERT INTO `hmoplans` (`PlanID`,`ProviderID`,`PlanName`,`PlanCode`,`Description`,`CoverageType`,`PlanCategory`,`MonthlyPremium`,`MaximumBenefitLimit`,`AccreditedHospitals`,`EligibilityRequirements`,`IsActive`,`EffectiveDate`,`CreatedAt`,`UpdatedAt`) VALUES
(1,1,'Maxicare Individual','MXI-IND','Individual healthcare coverage', 'Comprehensive','Individual',3500.00,1000000.00,'["St. Lukes Medical Center","Makati Medical Center","The Medical City"]','Age 0-65',1,'2024-01-01',NOW(),NOW()),
(2,2,'Medicard Classic','MDC-CLS','Classic healthcare plan', 'Standard','Individual',2800.00,600000.00,'["Manila Doctors Hospital","UST Hospital"]','Age 0-65',1,'2024-01-01',NOW(),NOW()),
(3,3,'Intellicare Flexicare','INT-FLEX','Flexible healthcare plan','Flexible','Individual',2200.00,500000.00,'["Veterans Memorial Medical Center","Philippine Heart Center"]','Age 0-60',1,'2024-01-01',NOW(),NOW()),
(4,4,'PhilCare Health PRO','PHC-PRO','Professional health plan','Comprehensive','Individual',3800.00,1200000.00,'["Cebu Doctors Hospital","Chong Hua Hospital"]','Age 0-65',1,'2024-01-01',NOW(),NOW()),
(5,5,'Kaiser Ultimate Health Builder','KAI-UHB','Ultimate health plan','Premium','Individual',6500.00,2000000.00,'["Kaiser Medical Center","Metropolitan Medical Center"]','Age 0-70',1,'2024-01-01',NOW(),NOW()),
(6,6,'Insular iCare','IHC-ICR','Individual care plan','Comprehensive','Individual',2900.00,750000.00,'["Makati Medical Center","Asian Hospital"]','Age 0-65',1,'2024-01-01',NOW(),NOW()),
(7,7,'ValuCare Individual','VAL-IND','Affordable individual healthcare plan','Basic','Individual',1500.00,300000.00,'["Jose Fabella Hospital","Pasig City General Hospital"]','Age 0-60',1,'2024-01-01',NOW(),NOW());
