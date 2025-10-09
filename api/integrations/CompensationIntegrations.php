<?php

namespace App\Integrations;

use PDO;
use Exception;

class CompensationIntegrations
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Sync salary adjustments to Payroll module
     */
    public function syncToPayroll($workflowId, $adjustmentDetails)
    {
        try {
            $this->pdo->beginTransaction();

            // Update employee base salaries in payroll
            foreach ($adjustmentDetails as $detail) {
                $sql = "UPDATE employees SET 
                        BaseSalary = :new_salary,
                        UpdatedAt = CURRENT_TIMESTAMP
                        WHERE EmployeeID = :employee_id";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'new_salary' => $detail['new_salary'],
                    'employee_id' => $detail['employee_id']
                ]);

                // Create payroll adjustment record
                $adjustmentSql = "INSERT INTO salary_adjustments 
                                 (EmployeeID, AdjustmentType, Amount, AdjustmentDate, Reason, Status, CreatedBy) 
                                 VALUES 
                                 (:employee_id, :adjustment_type, :amount, :adjustment_date, :reason, 'Approved', :created_by)";

                $adjustmentStmt = $this->pdo->prepare($adjustmentSql);
                $adjustmentStmt->execute([
                    'employee_id' => $detail['employee_id'],
                    'adjustment_type' => 'Compensation Planning',
                    'amount' => $detail['adjustment_amount'],
                    'adjustment_date' => date('Y-m-d'),
                    'reason' => "Workflow ID: {$workflowId} - Compensation Planning Adjustment",
                    'created_by' => 1 // TODO: Get from session
                ]);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Sync compensation data to HR Core
     */
    public function syncToHRCore($employeeId, $gradeData)
    {
        try {
            // Update employee position and grade information
            $sql = "UPDATE employees SET 
                    Position = :position,
                    Department = :department,
                    BaseSalary = :base_salary,
                    UpdatedAt = CURRENT_TIMESTAMP
                    WHERE EmployeeID = :employee_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'position' => $gradeData['position'],
                'department' => $gradeData['department'],
                'base_salary' => $gradeData['base_salary'],
                'employee_id' => $employeeId
            ]);

            return true;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Sync compensation data to Finance module
     */
    public function syncToFinance($workflowId, $totalImpact, $affectedEmployees)
    {
        try {
            // Create budget impact record
            $sql = "INSERT INTO budget_impacts 
                    (Module, ReferenceID, ImpactType, Amount, Description, EffectiveDate, CreatedAt) 
                    VALUES 
                    ('Compensation Planning', :workflow_id, 'Salary Adjustment', :amount, :description, CURDATE(), CURRENT_TIMESTAMP)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'workflow_id' => $workflowId,
                'amount' => $totalImpact,
                'description' => "Compensation Planning Workflow - {$affectedEmployees} employees affected"
            ]);

            return true;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate compensation analytics data
     */
    public function generateAnalyticsData()
    {
        try {
            // Salary trends analysis
            $salaryTrends = $this->getSalaryTrends();
            
            // Grade distribution analysis
            $gradeDistribution = $this->getGradeDistribution();
            
            // Pay equity analysis
            $payEquity = $this->getPayEquityAnalysis();
            
            // Department comparison
            $departmentComparison = $this->getDepartmentComparison();
            
            // Cost analysis
            $costAnalysis = $this->getCostAnalysis();

            $analyticsData = [
                'salary_trends' => $salaryTrends,
                'grade_distribution' => $gradeDistribution,
                'pay_equity' => $payEquity,
                'department_comparison' => $departmentComparison,
                'cost_analysis' => $costAnalysis,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Store analytics data
            $this->storeAnalyticsData('Compensation Overview', $analyticsData);

            return $analyticsData;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get salary trends data
     */
    private function getSalaryTrends()
    {
        $sql = "SELECT 
                    DATE_FORMAT(sa.AdjustmentDate, '%Y-%m') as month,
                    COUNT(*) as adjustments,
                    AVG(sa.Amount) as average_adjustment,
                    SUM(sa.Amount) as total_adjustment
                FROM salary_adjustments sa
                WHERE sa.AdjustmentDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                AND sa.Status = 'Approved'
                GROUP BY DATE_FORMAT(sa.AdjustmentDate, '%Y-%m')
                ORDER BY month";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get grade distribution data
     */
    private function getGradeDistribution()
    {
        $sql = "SELECT 
                    sg.GradeCode,
                    sg.GradeName,
                    COUNT(egm.EmployeeID) as employee_count,
                    AVG(egm.CurrentSalary) as average_salary,
                    MIN(egm.CurrentSalary) as min_salary,
                    MAX(egm.CurrentSalary) as max_salary
                FROM salary_grades sg
                LEFT JOIN employee_grade_mapping egm ON sg.GradeID = egm.GradeID 
                    AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                WHERE sg.Status = 'Active'
                GROUP BY sg.GradeID, sg.GradeCode, sg.GradeName
                ORDER BY sg.GradeCode";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pay equity analysis
     */
    private function getPayEquityAnalysis()
    {
        $sql = "SELECT 
                    e.Gender,
                    e.Department,
                    AVG(e.BaseSalary) as average_salary,
                    COUNT(*) as employee_count
                FROM employees e
                WHERE e.Status = 'Active'
                AND e.Gender IS NOT NULL
                GROUP BY e.Gender, e.Department
                ORDER BY e.Department, e.Gender";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get department comparison data
     */
    private function getDepartmentComparison()
    {
        $sql = "SELECT 
                    e.Department,
                    COUNT(*) as employee_count,
                    AVG(e.BaseSalary) as average_salary,
                    MIN(e.BaseSalary) as min_salary,
                    MAX(e.BaseSalary) as max_salary,
                    SUM(e.BaseSalary) as total_payroll
                FROM employees e
                WHERE e.Status = 'Active'
                GROUP BY e.Department
                ORDER BY average_salary DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cost analysis data
     */
    private function getCostAnalysis()
    {
        $sql = "SELECT 
                    COUNT(*) as total_employees,
                    SUM(e.BaseSalary) as total_monthly_payroll,
                    AVG(e.BaseSalary) as average_salary,
                    MIN(e.BaseSalary) as lowest_salary,
                    MAX(e.BaseSalary) as highest_salary
                FROM employees e
                WHERE e.Status = 'Active'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate annual costs
        $result['total_annual_payroll'] = $result['total_monthly_payroll'] * 12;
        $result['average_annual_salary'] = $result['average_salary'] * 12;

        return $result;
    }

    /**
     * Store analytics data
     */
    private function storeAnalyticsData($reportType, $data)
    {
        $sql = "INSERT INTO compensation_analytics 
                (ReportType, ReportData, ReportDate, Period) 
                VALUES 
                (:report_type, :report_data, CURDATE(), 'Monthly')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'report_type' => $reportType,
            'report_data' => json_encode($data)
        ]);
    }

    /**
     * Get compensation dashboard data
     */
    public function getDashboardData()
    {
        try {
            $data = [
                'total_employees' => $this->getTotalEmployees(),
                'total_payroll' => $this->getTotalPayroll(),
                'average_salary' => $this->getAverageSalary(),
                'grade_distribution' => $this->getGradeDistribution(),
                'recent_adjustments' => $this->getRecentAdjustments(),
                'pending_workflows' => $this->getPendingWorkflows(),
                'salary_trends' => $this->getSalaryTrends()
            ];

            return $data;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get total employees count
     */
    private function getTotalEmployees()
    {
        $sql = "SELECT COUNT(*) as total FROM employees WHERE Status = 'Active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Get total payroll amount
     */
    private function getTotalPayroll()
    {
        $sql = "SELECT SUM(BaseSalary) as total FROM employees WHERE Status = 'Active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Get average salary
     */
    private function getAverageSalary()
    {
        $sql = "SELECT AVG(BaseSalary) as average FROM employees WHERE Status = 'Active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['average'];
    }

    /**
     * Get recent adjustments
     */
    private function getRecentAdjustments()
    {
        $sql = "SELECT 
                    sa.AdjustmentID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    sa.Amount,
                    sa.AdjustmentDate,
                    sa.Reason
                FROM salary_adjustments sa
                LEFT JOIN employees e ON sa.EmployeeID = e.EmployeeID
                WHERE sa.Status = 'Approved'
                ORDER BY sa.AdjustmentDate DESC
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pending workflows
     */
    private function getPendingWorkflows()
    {
        $sql = "SELECT 
                    WorkflowID,
                    WorkflowName,
                    Status,
                    CreatedAt
                FROM pay_adjustment_workflows
                WHERE Status IN ('Draft', 'Review')
                ORDER BY CreatedAt DESC
                LIMIT 5";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Export compensation data for external systems
     */
    public function exportCompensationData($format = 'json')
    {
        try {
            $data = [
                'employees' => $this->getEmployeeCompensationData(),
                'grades' => $this->getGradeData(),
                'workflows' => $this->getWorkflowData(),
                'exported_at' => date('Y-m-d H:i:s')
            ];

            switch ($format) {
                case 'json':
                    return json_encode($data, JSON_PRETTY_PRINT);
                case 'csv':
                    return $this->convertToCSV($data);
                default:
                    return $data;
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get employee compensation data for export
     */
    private function getEmployeeCompensationData()
    {
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.Position,
                    e.Department,
                    e.BaseSalary,
                    sg.GradeCode,
                    sg.GradeName,
                    ss.StepNumber,
                    egm.Status as mapping_status,
                    egm.EffectiveDate
                FROM employees e
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.EmployeeID 
                    AND (egm.EndDate IS NULL OR egm.EndDate > CURDATE())
                LEFT JOIN salary_grades sg ON egm.GradeID = sg.GradeID
                LEFT JOIN salary_steps ss ON egm.StepID = ss.StepID
                WHERE e.Status = 'Active'
                ORDER BY e.LastName, e.FirstName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get grade data for export
     */
    private function getGradeData()
    {
        $sql = "SELECT 
                    sg.GradeID,
                    sg.GradeCode,
                    sg.GradeName,
                    sg.Description,
                    sg.DepartmentID,
                    d.DepartmentName,
                    sg.PositionCategory,
                    sg.Status,
                    sg.EffectiveDate
                FROM salary_grades sg
                LEFT JOIN departments d ON sg.DepartmentID = d.DepartmentID
                ORDER BY sg.GradeCode";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get workflow data for export
     */
    private function getWorkflowData()
    {
        $sql = "SELECT 
                    WorkflowID,
                    WorkflowName,
                    Description,
                    AdjustmentType,
                    AdjustmentValue,
                    Status,
                    TotalImpact,
                    AffectedEmployees,
                    CreatedAt,
                    EffectiveDate
                FROM pay_adjustment_workflows
                ORDER BY CreatedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Convert data to CSV format
     */
    private function convertToCSV($data)
    {
        $csv = '';
        
        // Add employees data
        if (!empty($data['employees'])) {
            $csv .= "Employee Data\n";
            $csv .= "Employee ID,Name,Position,Department,Base Salary,Grade Code,Grade Name,Step,Status,Effective Date\n";
            foreach ($data['employees'] as $employee) {
                $csv .= implode(',', [
                    $employee['EmployeeID'],
                    '"' . $employee['employee_name'] . '"',
                    '"' . $employee['Position'] . '"',
                    '"' . $employee['Department'] . '"',
                    $employee['BaseSalary'],
                    $employee['GradeCode'] ?? '',
                    '"' . ($employee['GradeName'] ?? '') . '"',
                    $employee['StepNumber'] ?? '',
                    $employee['mapping_status'] ?? '',
                    $employee['EffectiveDate'] ?? ''
                ]) . "\n";
            }
            $csv .= "\n";
        }

        return $csv;
    }
}
