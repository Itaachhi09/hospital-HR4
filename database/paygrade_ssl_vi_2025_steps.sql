-- Government / Public Hospital Pay Grade Table (SSL VI 2025)
-- Sets PayGradeMin to Step 1 and PayGradeMax to Step 8 for each SG.
-- Uses UPPER(REPLACE(SalaryGrade,' ','')) to match variants like 'SG 1', 'SG1', 'SG01'.

UPDATE hospital_job_roles SET PayGradeMin=14061,  PayGradeMax=15671  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG01';
UPDATE hospital_job_roles SET PayGradeMin=15078,  PayGradeMax=16814  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG02';
UPDATE hospital_job_roles SET PayGradeMin=16082,  PayGradeMax=18004  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG03';
UPDATE hospital_job_roles SET PayGradeMin=16636,  PayGradeMax=18638  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG04';
UPDATE hospital_job_roles SET PayGradeMin=17866,  PayGradeMax=19987  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG05';
UPDATE hospital_job_roles SET PayGradeMin=18864,  PayGradeMax=21099  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG06';
UPDATE hospital_job_roles SET PayGradeMin=18993,  PayGradeMax=21235  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG07';
UPDATE hospital_job_roles SET PayGradeMin=20190,  PayGradeMax=22587  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG08';
UPDATE hospital_job_roles SET PayGradeMin=23176,  PayGradeMax=25820  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG09';
UPDATE hospital_job_roles SET PayGradeMin=25586,  PayGradeMax=28504  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG10';
UPDATE hospital_job_roles SET PayGradeMin=30024,  PayGradeMax=33476  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG11';
UPDATE hospital_job_roles SET PayGradeMin=32758,  PayGradeMax=36444  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG12';
UPDATE hospital_job_roles SET PayGradeMin=34911,  PayGradeMax=38773  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG13';
UPDATE hospital_job_roles SET PayGradeMin=35567,  PayGradeMax=39511  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG14';
UPDATE hospital_job_roles SET PayGradeMin=36619,  PayGradeMax=40698  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG15';
UPDATE hospital_job_roles SET PayGradeMin=39672,  PayGradeMax=44083  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG16';
UPDATE hospital_job_roles SET PayGradeMin=43030,  PayGradeMax=47835  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG17';
UPDATE hospital_job_roles SET PayGradeMin=47003,  PayGradeMax=52290  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG18';
UPDATE hospital_job_roles SET PayGradeMin=51357,  PayGradeMax=57135  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG19';
UPDATE hospital_job_roles SET PayGradeMin=57347,  PayGradeMax=63682  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG20';
UPDATE hospital_job_roles SET PayGradeMin=63833,  PayGradeMax=70843  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG21';
UPDATE hospital_job_roles SET PayGradeMin=71511,  PayGradeMax=79262  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG22';
UPDATE hospital_job_roles SET PayGradeMin=79135,  PayGradeMax=87707  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG23';
UPDATE hospital_job_roles SET PayGradeMin=90078,  PayGradeMax=99976  WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG24';
UPDATE hospital_job_roles SET PayGradeMin=111727, PayGradeMax=123887 WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG25';
UPDATE hospital_job_roles SET PayGradeMin=123981, PayGradeMax=137368 WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG26';
UPDATE hospital_job_roles SET PayGradeMin=139817, PayGradeMax=154931 WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG27';
UPDATE hospital_job_roles SET PayGradeMin=157707, PayGradeMax=174425 WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG28';
UPDATE hospital_job_roles SET PayGradeMin=177714, PayGradeMax=196238 WHERE UPPER(REPLACE(SalaryGrade,' ',''))='SG29';
