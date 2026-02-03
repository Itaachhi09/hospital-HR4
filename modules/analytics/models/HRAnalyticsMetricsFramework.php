<?php
/**
 * HR Analytics Metrics Framework
 * Dynamic, data-driven metrics calculation and management system
 * Consolidates workforce, payroll, benefits, and engagement data into measurable KPIs
 */

require_once __DIR__ . '/../config.php';

class HRAnalyticsMetricsFramework {
    private $pdo;
    private $cache = [];
    private $metricDefinitions = [];

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->initializeMetricDefinitions();
    }

    /**
     * Initialize metric definitions with formulas and configurations
     */
    private function initializeMetricDefinitions() {
        $this->metricDefinitions = [
            'employee_demographics' => [
                'total_headcount' => [
                    'formula' => 'COUNT(Active Employees)',
                    'sql' => 'SELECT COUNT(*) as value FROM employees WHERE IsActive = 1',
                    'display_type' => 'KPI_CARD',
                    'category' => 'workforce',
                    'description' => 'Total active workforce size'
                ],
                'headcount_by_department' => [
                    'formula' => 'COUNT(EmployeeID) GROUP BY Department',
                    'sql' => 'SELECT d.DepartmentName, COUNT(e.EmployeeID) as value FROM employees e LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID WHERE e.IsActive = 1 GROUP BY d.DepartmentID, d.DepartmentName',
                    'display_type' => 'BAR_CHART',
                    'category' => 'workforce',
                    'description' => 'Staffing distribution across departments'
                ],
                'average_age' => [
                    'formula' => 'AVG(CurrentDate - Birthdate)',
                    'sql' => 'SELECT AVG(TIMESTAMPDIFF(YEAR, DateOfBirth, CURDATE())) as value FROM employees WHERE IsActive = 1',
                    'display_type' => 'LINE_CHART',
                    'category' => 'workforce',
                    'description' => 'Average workforce age'
                ],
                'gender_ratio' => [
                    'formula' => '(Male / Total Employees) × 100',
                    'sql' => 'SELECT Gender, COUNT(*) as value FROM employees WHERE IsActive = 1 GROUP BY Gender',
                    'display_type' => 'DONUT_CHART',
                    'category' => 'workforce',
                    'description' => 'Gender diversity distribution'
                ],
                'employment_type_ratio' => [
                    'formula' => 'COUNT by Employment Type',
                    'sql' => 'SELECT EmploymentType, COUNT(*) as value FROM employees WHERE IsActive = 1 GROUP BY EmploymentType',
                    'display_type' => 'PIE_CHART',
                    'category' => 'workforce',
                    'description' => 'Employment classification distribution'
                ],
                'average_tenure' => [
                    'formula' => 'AVG(CurrentDate - DateHired)',
                    'sql' => 'SELECT AVG(TIMESTAMPDIFF(YEAR, DateHired, CURDATE())) as value FROM employees WHERE IsActive = 1',
                    'display_type' => 'GAUGE',
                    'category' => 'workforce',
                    'description' => 'Average employee tenure in years'
                ],
                'education_level_ratio' => [
                    'formula' => 'COUNT by Education Level',
                    'sql' => 'SELECT EducationLevel, COUNT(*) as value FROM employees WHERE IsActive = 1 GROUP BY EducationLevel',
                    'display_type' => 'BAR_CHART',
                    'category' => 'workforce',
                    'description' => 'Educational background distribution'
                ]
            ],
            'recruitment' => [
                'applications_received' => [
                    'formula' => 'COUNT(Applications)',
                    'sql' => 'SELECT DATE_FORMAT(ApplicationDate, "%Y-%m") as period, COUNT(*) as value FROM jobapplications WHERE ApplicationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(ApplicationDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'hiring',
                    'description' => 'Monthly application volume trend'
                ],
                'time_to_hire' => [
                    'formula' => 'AVG(DateHired - DatePosted)',
                    'sql' => 'SELECT AVG(DATEDIFF(DateHired, ApplicationDate)) as value FROM jobapplications WHERE Status = "Hired" AND DateHired IS NOT NULL',
                    'display_type' => 'KPI_CARD',
                    'category' => 'hiring',
                    'description' => 'Average days from application to hire'
                ],
                'offer_acceptance_rate' => [
                    'formula' => '(Accepted / Offers Sent) × 100',
                    'sql' => 'SELECT (COUNT(CASE WHEN Status = "Hired" THEN 1 END) / COUNT(CASE WHEN Status IN ("Hired", "Rejected") THEN 1 END)) * 100 as value FROM jobapplications WHERE Status IN ("Hired", "Rejected")',
                    'display_type' => 'GAUGE',
                    'category' => 'hiring',
                    'description' => 'Percentage of offers accepted'
                ],
                'new_hire_retention_30_days' => [
                    'formula' => '(Employed after 30 days / Hired) × 100',
                    'sql' => 'SELECT (COUNT(CASE WHEN DateSeparated IS NULL OR DateSeparated > DATE_ADD(DateHired, INTERVAL 30 DAY) THEN 1 END) / COUNT(*)) * 100 as value FROM employees WHERE DateHired >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)',
                    'display_type' => 'BAR_CHART',
                    'category' => 'hiring',
                    'description' => '30-day new hire retention rate'
                ],
                'vacancy_rate' => [
                    'formula' => '(Open Positions / Total Positions) × 100',
                    'sql' => 'SELECT (COUNT(CASE WHEN Status = "Open" THEN 1 END) / COUNT(*)) * 100 as value FROM positions WHERE IsActive = 1',
                    'display_type' => 'KPI_CARD',
                    'category' => 'hiring',
                    'description' => 'Percentage of open positions'
                ],
                'source_of_hire_ratio' => [
                    'formula' => 'COUNT(Hires by Source)',
                    'sql' => 'SELECT ja.ApplicationSource, COUNT(*) as value FROM jobapplications ja WHERE ja.Status = "Hired" GROUP BY ja.ApplicationSource',
                    'display_type' => 'PIE_CHART',
                    'category' => 'hiring',
                    'description' => 'Hiring source effectiveness'
                ]
            ],
            'payroll_compensation' => [
                'total_payroll_cost' => [
                    'formula' => 'SUM(Gross Pay)',
                    'sql' => 'SELECT DATE_FORMAT(pr.PayPeriodStart, "%Y-%m") as period, SUM(ps.GrossPay) as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(pr.PayPeriodStart, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'financial',
                    'description' => 'Monthly total payroll expenditure'
                ],
                'avg_salary_per_grade' => [
                    'formula' => 'AVG(Salary) GROUP BY Grade',
                    'sql' => 'SELECT sg.grade_name, AVG(es.BaseSalary) as value FROM salary_grades sg LEFT JOIN employee_grade_mapping egm ON sg.grade_id = egm.grade_id LEFT JOIN employeesalaries es ON egm.employee_id = es.EmployeeID WHERE es.IsCurrent = 1 GROUP BY sg.grade_id, sg.grade_name',
                    'display_type' => 'BAR_CHART',
                    'category' => 'financial',
                    'description' => 'Average salary by grade level'
                ],
                'payroll_cost_per_department' => [
                    'formula' => 'SUM(Salary + Benefits) GROUP BY Department',
                    'sql' => 'SELECT d.DepartmentName, SUM(ps.GrossPay) as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID LEFT JOIN employees e ON ps.EmployeeID = e.EmployeeID LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY d.DepartmentID, d.DepartmentName',
                    'display_type' => 'STACKED_BAR',
                    'category' => 'financial',
                    'description' => 'Department-wise payroll costs'
                ],
                'overtime_cost_ratio' => [
                    'formula' => '(OT Pay / Total Pay) × 100',
                    'sql' => 'SELECT (SUM(ps.OvertimePay) / SUM(ps.GrossPay)) * 100 as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'display_type' => 'GAUGE',
                    'category' => 'financial',
                    'description' => 'Overtime cost as percentage of total payroll'
                ],
                'tax_deduction_rate' => [
                    'formula' => '(Deductions / Gross Pay) × 100',
                    'sql' => 'SELECT (SUM(ps.DeductionAmount) / SUM(ps.GrossPay)) * 100 as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'financial',
                    'description' => 'Tax and deduction rate'
                ],
                'net_pay_distribution' => [
                    'formula' => 'SUM(NetPay) GROUP BY Department',
                    'sql' => 'SELECT d.DepartmentName, SUM(ps.NetPay) as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID LEFT JOIN employees e ON ps.EmployeeID = e.EmployeeID LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY d.DepartmentID, d.DepartmentName',
                    'display_type' => 'TABLE',
                    'category' => 'financial',
                    'description' => 'Net pay distribution by department'
                ]
            ],
            'attendance_leave' => [
                'attendance_rate' => [
                    'formula' => '(Days Present / Work Days) × 100',
                    'sql' => 'SELECT DATE_FORMAT(AttendanceDate, "%Y-%m") as period, (SUM(CASE WHEN Status = "Present" THEN 1 ELSE 0 END) / COUNT(*)) * 100 as value FROM attendance WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(AttendanceDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'productivity',
                    'description' => 'Monthly attendance rate trend'
                ],
                'absenteeism_rate' => [
                    'formula' => '(Days Absent / Work Days) × 100',
                    'sql' => 'SELECT (SUM(CASE WHEN Status = "Absent" THEN 1 ELSE 0 END) / COUNT(*)) * 100 as value FROM attendance WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'productivity',
                    'description' => 'Current month absenteeism rate'
                ],
                'late_arrival_rate' => [
                    'formula' => '(Late Entries / Employees) × 100',
                    'sql' => 'SELECT (SUM(CASE WHEN Status = "Late" THEN 1 ELSE 0 END) / COUNT(DISTINCT EmployeeID)) * 100 as value FROM attendance WHERE AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'display_type' => 'GAUGE',
                    'category' => 'productivity',
                    'description' => 'Late arrival rate'
                ],
                'overtime_hours' => [
                    'formula' => 'SUM(OT Hours) GROUP BY Department',
                    'sql' => 'SELECT d.DepartmentName, SUM(a.OvertimeHours) as value FROM attendance a LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID WHERE a.AttendanceDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY d.DepartmentID, d.DepartmentName',
                    'display_type' => 'BAR_CHART',
                    'category' => 'productivity',
                    'description' => 'Overtime hours by department'
                ],
                'leave_utilization_rate' => [
                    'formula' => '(Leaves Taken / Entitlement) × 100',
                    'sql' => 'SELECT lt.LeaveTypeName, (SUM(lr.DaysApproved) / COUNT(DISTINCT lr.EmployeeID)) as value FROM leaverequests lr LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID WHERE lr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY lt.LeaveTypeID, lt.LeaveTypeName',
                    'display_type' => 'DONUT_CHART',
                    'category' => 'productivity',
                    'description' => 'Leave utilization by type'
                ],
                'top_leave_types' => [
                    'formula' => 'COUNT BY Leave Type',
                    'sql' => 'SELECT lt.LeaveTypeName, COUNT(*) as value FROM leaverequests lr LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID WHERE lr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY lt.LeaveTypeID, lt.LeaveTypeName ORDER BY value DESC LIMIT 5',
                    'display_type' => 'PIE_CHART',
                    'category' => 'productivity',
                    'description' => 'Most used leave types'
                ]
            ],
            'benefits_hmo' => [
                'total_benefits_cost' => [
                    'formula' => 'SUM(Benefit Amount)',
                    'sql' => 'SELECT DATE_FORMAT(he.EnrollmentDate, "%Y-%m") as period, SUM(hmo.MonthlyPremium) as value FROM hmoenrollments he LEFT JOIN hmoplans hmo ON he.PlanID = hmo.PlanID WHERE he.IsActive = 1 AND he.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(he.EnrollmentDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'benefits',
                    'description' => 'Monthly total benefits cost'
                ],
                'hmo_utilization_rate' => [
                    'formula' => '(Claims / Enrolled Employees) × 100',
                    'sql' => 'SELECT (COUNT(DISTINCT hc.EmployeeID) / COUNT(DISTINCT he.EmployeeID)) * 100 as value FROM hmoenrollments he LEFT JOIN hmoclaims hc ON he.EmployeeID = hc.EmployeeID WHERE he.IsActive = 1 AND hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'GAUGE',
                    'category' => 'benefits',
                    'description' => 'HMO utilization rate'
                ],
                'average_claim_cost' => [
                    'formula' => 'SUM(Claim Amount) / COUNT(Claims)',
                    'sql' => 'SELECT AVG(hc.ClaimAmount) as value FROM hmoclaims hc WHERE hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'benefits',
                    'description' => 'Average claim cost'
                ],
                'claim_processing_time' => [
                    'formula' => 'AVG(Approved - Filed)',
                    'sql' => 'SELECT AVG(DATEDIFF(hc.ProcessedDate, hc.ClaimDate)) as value FROM hmoclaims hc WHERE hc.ProcessedDate IS NOT NULL AND hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'LINE_CHART',
                    'category' => 'benefits',
                    'description' => 'Average claim processing time in days'
                ],
                'benefits_roi' => [
                    'formula' => '(Satisfaction / Cost)',
                    'sql' => 'SELECT (AVG(esr.Score) / AVG(hmo.MonthlyPremium)) as value FROM hmoenrollments he LEFT JOIN hmoplans hmo ON he.PlanID = hmo.PlanID LEFT JOIN engagementsurveyresponses esr ON he.EmployeeID = esr.EmployeeID WHERE he.IsActive = 1',
                    'display_type' => 'INDICATOR_GAUGE',
                    'category' => 'benefits',
                    'description' => 'Benefits return on investment'
                ]
            ],
            'training_development' => [
                'training_participation_rate' => [
                    'formula' => '(Attendees / Invited) × 100',
                    'sql' => 'SELECT (COUNT(CASE WHEN te.CompletionStatus = "Completed" THEN 1 END) / COUNT(*)) * 100 as value FROM trainingenrollments te WHERE te.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'DONUT_CHART',
                    'category' => 'development',
                    'description' => 'Training participation rate'
                ],
                'training_cost_per_employee' => [
                    'formula' => 'Total Cost / Employees',
                    'sql' => 'SELECT (SUM(t.Cost) / COUNT(DISTINCT te.EmployeeID)) as value FROM trainings t LEFT JOIN trainingenrollments te ON t.TrainingID = te.TrainingID WHERE t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'development',
                    'description' => 'Average training cost per employee'
                ],
                'competency_improvement_score' => [
                    'formula' => 'Post-test - Pre-test',
                    'sql' => 'SELECT AVG(tsa.ScoreAfter - tsa.ScoreBefore) as value FROM trainingskillassessments tsa WHERE tsa.AssessmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'BAR_CHART',
                    'category' => 'development',
                    'description' => 'Average competency improvement'
                ],
                'certifications_earned' => [
                    'formula' => 'COUNT(Certificates)',
                    'sql' => 'SELECT DATE_FORMAT(CertificationDate, "%Y-%m") as period, COUNT(*) as value FROM employeecertifications WHERE CertificationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(CertificationDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'TABLE',
                    'category' => 'development',
                    'description' => 'Certifications earned by month'
                ],
                'skill_gap_index' => [
                    'formula' => '(Required - Current Skills) / Required',
                    'sql' => 'SELECT AVG((ts.RequiredLevel - COALESCE(tsa.ScoreAfter, 0)) / ts.RequiredLevel) * 100 as value FROM trainingskills ts LEFT JOIN trainingskillassessments tsa ON ts.SkillID = tsa.SkillID',
                    'display_type' => 'GAUGE',
                    'category' => 'development',
                    'description' => 'Skill gap percentage'
                ]
            ],
            'employee_relations_engagement' => [
                'engagement_index' => [
                    'formula' => 'AVG(Survey Scores)',
                    'sql' => 'SELECT AVG(esr.Score) as value FROM engagementsurveyresponses esr WHERE esr.ResponseDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'GAUGE',
                    'category' => 'engagement',
                    'description' => 'Overall engagement score'
                ],
                'participation_rate' => [
                    'formula' => '(Joined Activities / Employees) × 100',
                    'sql' => 'SELECT (COUNT(DISTINCT er.EmployeeID) / COUNT(DISTINCT e.EmployeeID)) * 100 as value FROM employeerecognitions er LEFT JOIN employees e ON 1=1 WHERE er.RecognitionDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'engagement',
                    'description' => 'Employee participation in activities'
                ],
                'disciplinary_case_rate' => [
                    'formula' => '(# Cases / Employees) × 100',
                    'sql' => 'SELECT (COUNT(dc.CaseID) / COUNT(DISTINCT e.EmployeeID)) * 100 as value FROM disciplinarycases dc LEFT JOIN employees e ON 1=1 WHERE dc.CaseDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'BAR_CHART',
                    'category' => 'engagement',
                    'description' => 'Disciplinary cases per 100 employees'
                ],
                'case_resolution_time' => [
                    'formula' => 'AVG(Close - Open Date)',
                    'sql' => 'SELECT AVG(DATEDIFF(dc.ResolutionDate, dc.CaseDate)) as value FROM disciplinarycases dc WHERE dc.ResolutionDate IS NOT NULL AND dc.CaseDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'engagement',
                    'description' => 'Average case resolution time in days'
                ],
                'recognition_events_per_month' => [
                    'formula' => 'COUNT(Events)',
                    'sql' => 'SELECT DATE_FORMAT(er.RecognitionDate, "%Y-%m") as period, COUNT(*) as value FROM employeerecognitions er WHERE er.RecognitionDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(er.RecognitionDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'engagement',
                    'description' => 'Recognition events by month'
                ]
            ],
            'turnover_retention' => [
                'turnover_rate' => [
                    'formula' => '(Exits / Avg. Headcount) × 100',
                    'sql' => 'SELECT DATE_FORMAT(e.DateSeparated, "%Y-%m") as period, (COUNT(e.EmployeeID) / AVG((SELECT COUNT(*) FROM employees WHERE IsActive = 1))) * 100 as value FROM employees e WHERE e.DateSeparated IS NOT NULL AND e.DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(e.DateSeparated, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'retention',
                    'description' => 'Monthly turnover rate trend'
                ],
                'voluntary_vs_involuntary_exit' => [
                    'formula' => '(Resigned / Terminated)',
                    'sql' => 'SELECT SeparationType, COUNT(*) as value FROM employees WHERE DateSeparated IS NOT NULL AND DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY SeparationType',
                    'display_type' => 'PIE_CHART',
                    'category' => 'retention',
                    'description' => 'Voluntary vs involuntary exit ratio'
                ],
                'avg_tenure_of_exiting_employees' => [
                    'formula' => 'AVG(ExitDate - HireDate)',
                    'sql' => 'SELECT AVG(TIMESTAMPDIFF(YEAR, DateHired, DateSeparated)) as value FROM employees WHERE DateSeparated IS NOT NULL AND DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'retention',
                    'description' => 'Average tenure of exiting employees'
                ],
                'retention_rate' => [
                    'formula' => '100 - Turnover Rate',
                    'sql' => 'SELECT 100 - ((COUNT(CASE WHEN DateSeparated IS NOT NULL AND DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) / COUNT(*)) * 100) as value FROM employees WHERE IsActive = 1',
                    'display_type' => 'KPI_CARD',
                    'category' => 'retention',
                    'description' => 'Employee retention rate'
                ],
                'top_reasons_for_exit' => [
                    'formula' => 'GROUPED COUNT(Reason)',
                    'sql' => 'SELECT ExitReason, COUNT(*) as value FROM employees WHERE DateSeparated IS NOT NULL AND DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND ExitReason IS NOT NULL GROUP BY ExitReason ORDER BY value DESC LIMIT 5',
                    'display_type' => 'BAR_CHART',
                    'category' => 'retention',
                    'description' => 'Top reasons for employee exit'
                ]
            ],
            'compliance_audit' => [
                'license_compliance_rate' => [
                    'formula' => '(Valid Licenses / Required) × 100',
                    'sql' => 'SELECT (COUNT(CASE WHEN ed.ExpiryDate > CURDATE() THEN 1 END) / COUNT(*)) * 100 as value FROM employeedocuments ed WHERE ed.DocumentType = "License" AND ed.IsActive = 1',
                    'display_type' => 'GAUGE',
                    'category' => 'compliance',
                    'description' => 'License compliance rate'
                ],
                'document_completion_rate' => [
                    'formula' => '(Complete Files / Employees) × 100',
                    'sql' => 'SELECT (COUNT(DISTINCT ed.EmployeeID) / COUNT(DISTINCT e.EmployeeID)) * 100 as value FROM employeedocuments ed LEFT JOIN employees e ON 1=1 WHERE ed.IsActive = 1',
                    'display_type' => 'KPI_CARD',
                    'category' => 'compliance',
                    'description' => 'Document completion rate'
                ],
                'expiring_documents_count' => [
                    'formula' => 'COUNT(Expiring ≤ 30 days)',
                    'sql' => 'SELECT ed.DocumentType, COUNT(*) as value FROM employeedocuments ed WHERE ed.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND ed.ExpiryDate > CURDATE() AND ed.IsActive = 1 GROUP BY ed.DocumentType',
                    'display_type' => 'TABLE',
                    'category' => 'compliance',
                    'description' => 'Documents expiring within 30 days'
                ],
                'audit_findings_rate' => [
                    'formula' => '(Non-Compliances / Audited Records) × 100',
                    'sql' => 'SELECT DATE_FORMAT(audit_date, "%Y-%m") as period, (COUNT(CASE WHEN status = "Non-Compliant" THEN 1 END) / COUNT(*)) * 100 as value FROM audit_records WHERE audit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(audit_date, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'compliance',
                    'description' => 'Audit findings rate trend'
                ]
            ],
            'executive_kpi' => [
                'headcount_trend' => [
                    'formula' => 'Monthly headcount',
                    'sql' => 'SELECT DATE_FORMAT(DateHired, "%Y-%m") as period, COUNT(*) as value FROM employees WHERE DateHired >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(DateHired, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'executive',
                    'description' => 'Monthly headcount growth trend'
                ],
                'turnover_trend_ytd' => [
                    'formula' => 'Monthly turnover',
                    'sql' => 'SELECT DATE_FORMAT(DateSeparated, "%Y-%m") as period, COUNT(*) as value FROM employees WHERE DateSeparated >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(DateSeparated, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'executive',
                    'description' => 'Year-to-date turnover trend'
                ],
                'total_payroll_vs_budget' => [
                    'formula' => '(Actual / Budget) × 100',
                    'sql' => 'SELECT (SUM(ps.GrossPay) / 1000000) * 100 as value FROM payrollruns pr LEFT JOIN payslips ps ON pr.PayrollRunID = ps.PayrollRunID WHERE pr.PayPeriodStart >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'display_type' => 'BAR_CHART',
                    'category' => 'executive',
                    'description' => 'Payroll vs budget percentage'
                ],
                'engagement_score' => [
                    'formula' => 'Weighted average',
                    'sql' => 'SELECT AVG(esr.Score) as value FROM engagementsurveyresponses esr WHERE esr.ResponseDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)',
                    'display_type' => 'GAUGE',
                    'category' => 'executive',
                    'description' => 'Overall organizational engagement score'
                ],
                'avg_training_hours_per_employee' => [
                    'formula' => 'Total Hours / Employees',
                    'sql' => 'SELECT (SUM(t.DurationHours) / COUNT(DISTINCT te.EmployeeID)) as value FROM trainings t LEFT JOIN trainingenrollments te ON t.TrainingID = te.TrainingID WHERE t.TrainingDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)',
                    'display_type' => 'KPI_CARD',
                    'category' => 'executive',
                    'description' => 'Average training hours per employee'
                ],
                'benefit_utilization_trend' => [
                    'formula' => 'Monthly benefits usage',
                    'sql' => 'SELECT DATE_FORMAT(hc.ClaimDate, "%Y-%m") as period, COUNT(*) as value FROM hmoclaims hc WHERE hc.ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(hc.ClaimDate, "%Y-%m") ORDER BY period',
                    'display_type' => 'LINE_CHART',
                    'category' => 'executive',
                    'description' => 'Monthly benefit utilization trend'
                ],
                'compliance_index' => [
                    'formula' => 'AVG(All Compliance Rates)',
                    'sql' => 'SELECT AVG(compliance_rate) as value FROM (SELECT (COUNT(CASE WHEN ed.ExpiryDate > CURDATE() THEN 1 END) / COUNT(*)) * 100 as compliance_rate FROM employeedocuments ed WHERE ed.IsActive = 1 UNION ALL SELECT (COUNT(DISTINCT ed.EmployeeID) / COUNT(DISTINCT e.EmployeeID)) * 100 as compliance_rate FROM employeedocuments ed LEFT JOIN employees e ON 1=1 WHERE ed.IsActive = 1) as rates',
                    'display_type' => 'KPI_CARD',
                    'category' => 'executive',
                    'description' => 'Overall compliance index'
                ]
            ]
        ];
    }

    /**
     * Calculate a specific metric
     */
    public function calculateMetric($category, $metricName, $filters = []) {
        $cacheKey = $this->getCacheKey($category, $metricName, $filters);
        
        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        if (!isset($this->metricDefinitions[$category][$metricName])) {
            throw new Exception("Metric $category.$metricName not found");
        }
        
        $metric = $this->metricDefinitions[$category][$metricName];
        $sql = $metric['sql'];
        
        // Apply filters to SQL
        $sql = $this->applyFilters($sql, $filters);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format result based on display type
            $formattedResult = $this->formatMetricResult($result, $metric['display_type']);
            
            // Cache the result
            $this->cache[$cacheKey] = $formattedResult;
            
            return $formattedResult;
            
        } catch (Exception $e) {
            throw new Exception("Error calculating metric $category.$metricName: " . $e->getMessage());
        }
    }

    /**
     * Calculate multiple metrics
     */
    public function calculateMetrics($category, $filters = []) {
        $results = [];
        
        if (!isset($this->metricDefinitions[$category])) {
            throw new Exception("Category $category not found");
        }
        
        foreach ($this->metricDefinitions[$category] as $metricName => $metric) {
            try {
                $results[$metricName] = $this->calculateMetric($category, $metricName, $filters);
            } catch (Exception $e) {
                $results[$metricName] = [
                    'error' => $e->getMessage(),
                    'value' => null
                ];
            }
        }
        
        return $results;
    }

    /**
     * Calculate all metrics across all categories
     */
    public function calculateAllMetrics($filters = []) {
        $allResults = [];
        
        foreach ($this->metricDefinitions as $category => $metrics) {
            $allResults[$category] = $this->calculateMetrics($category, $filters);
        }
        
        return $allResults;
    }

    /**
     * Get metric definition
     */
    public function getMetricDefinition($category, $metricName) {
        if (!isset($this->metricDefinitions[$category][$metricName])) {
            return null;
        }
        
        return $this->metricDefinitions[$category][$metricName];
    }

    /**
     * Get all metric definitions
     */
    public function getAllMetricDefinitions() {
        return $this->metricDefinitions;
    }

    /**
     * Apply filters to SQL query
     */
    private function applyFilters($sql, $filters) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = 'e.DepartmentID = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['branch_id'])) {
            $whereConditions[] = 'e.BranchID = :branch_id';
            $params[':branch_id'] = $filters['branch_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'date_field >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'date_field <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($whereConditions)) {
            if (strpos($sql, 'WHERE') !== false) {
                $sql .= ' AND ' . implode(' AND ', $whereConditions);
            } else {
                $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
            }
        }
        
        return $sql;
    }

    /**
     * Format metric result based on display type
     */
    private function formatMetricResult($result, $displayType) {
        switch ($displayType) {
            case 'KPI_CARD':
                return [
                    'value' => $result[0]['value'] ?? 0,
                    'display_type' => 'KPI_CARD',
                    'formatted_value' => $this->formatValue($result[0]['value'] ?? 0)
                ];
                
            case 'GAUGE':
                return [
                    'value' => $result[0]['value'] ?? 0,
                    'display_type' => 'GAUGE',
                    'formatted_value' => $this->formatValue($result[0]['value'] ?? 0),
                    'max_value' => 100,
                    'min_value' => 0
                ];
                
            case 'LINE_CHART':
            case 'BAR_CHART':
            case 'STACKED_BAR':
                return [
                    'data' => $result,
                    'display_type' => $displayType,
                    'labels' => array_column($result, 'period'),
                    'values' => array_column($result, 'value')
                ];
                
            case 'PIE_CHART':
            case 'DONUT_CHART':
                return [
                    'data' => $result,
                    'display_type' => $displayType,
                    'labels' => array_column($result, array_keys($result[0])[0]),
                    'values' => array_column($result, 'value')
                ];
                
            case 'TABLE':
                return [
                    'data' => $result,
                    'display_type' => 'TABLE',
                    'columns' => array_keys($result[0] ?? []),
                    'rows' => $result
                ];
                
            case 'INDICATOR_GAUGE':
                return [
                    'value' => $result[0]['value'] ?? 0,
                    'display_type' => 'INDICATOR_GAUGE',
                    'formatted_value' => $this->formatValue($result[0]['value'] ?? 0),
                    'status' => $this->getIndicatorStatus($result[0]['value'] ?? 0)
                ];
                
            default:
                return [
                    'data' => $result,
                    'display_type' => $displayType
                ];
        }
    }

    /**
     * Format value for display
     */
    private function formatValue($value, $type = 'number') {
        if (is_numeric($value)) {
            if ($value >= 1000000) {
                return number_format($value / 1000000, 1) . 'M';
            } elseif ($value >= 1000) {
                return number_format($value / 1000, 1) . 'K';
            } else {
                return number_format($value, 2);
            }
        }
        
        return $value;
    }

    /**
     * Get indicator status
     */
    private function getIndicatorStatus($value) {
        if ($value >= 80) return 'excellent';
        if ($value >= 60) return 'good';
        if ($value >= 40) return 'fair';
        return 'poor';
    }

    /**
     * Generate cache key
     */
    private function getCacheKey($category, $metricName, $filters) {
        return md5($category . '.' . $metricName . '.' . serialize($filters));
    }

    /**
     * Clear cache
     */
    public function clearCache() {
        $this->cache = [];
    }

    /**
     * Get metric categories
     */
    public function getMetricCategories() {
        return array_keys($this->metricDefinitions);
    }

    /**
     * Get metrics for a category
     */
    public function getMetricsForCategory($category) {
        if (!isset($this->metricDefinitions[$category])) {
            return [];
        }
        
        return array_keys($this->metricDefinitions[$category]);
    }

    /**
     * Validate filters
     */
    public function validateFilters($filters) {
        $validFilters = ['department_id', 'branch_id', 'date_from', 'date_to', 'employment_type'];
        $validatedFilters = [];
        
        foreach ($filters as $key => $value) {
            if (in_array($key, $validFilters) && !empty($value)) {
                $validatedFilters[$key] = $value;
            }
        }
        
        return $validatedFilters;
    }
}
?>
