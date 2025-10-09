<?php
/**
 * Reporting System for Compensation Planning
 * Handles PDF/Excel export and comprehensive reporting
 */

require_once __DIR__ . '/../config.php';

class ReportingSystem {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Generate comprehensive compensation report
     */
    public function generateCompensationReport($reportType, $filters = [], $format = 'json') {
        $report = [
            'report_metadata' => [
                'type' => $reportType,
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $_SESSION['user_id'] ?? 'System',
                'filters' => $filters,
                'format' => $format
            ],
            'data' => []
        ];

        switch ($reportType) {
            case 'salary_grade_table':
                $report['data'] = $this->getSalaryGradeTableData($filters);
                break;
            case 'employee_grade_mapping':
                $report['data'] = $this->getEmployeeGradeMappingData($filters);
                break;
            case 'adjustment_simulation':
                $report['data'] = $this->getAdjustmentSimulationData($filters);
                break;
            case 'payroll_impact':
                $report['data'] = $this->getPayrollImpactData($filters);
                break;
            case 'equity_distribution':
                $report['data'] = $this->getEquityDistributionData($filters);
                break;
            case 'comprehensive':
                $report['data'] = $this->getComprehensiveData($filters);
                break;
            default:
                throw new Exception("Unknown report type: {$reportType}");
        }

        // Add summary statistics
        $report['summary'] = $this->generateSummaryStatistics($report['data'], $reportType);

        if ($format === 'pdf') {
            return $this->generatePDFReport($report);
        } elseif ($format === 'excel') {
            return $this->generateExcelReport($report);
        }

        return $report;
    }

    /**
     * Get salary grade table data
     */
    private function getSalaryGradeTableData($filters = []) {
        $sql = "SELECT 
                    sg.grade_id,
                    sg.grade_code,
                    sg.grade_name,
                    sg.department_id,
                    d.DepartmentName,
                    sg.effective_date,
                    sg.status,
                    COUNT(sgs.step_id) as total_steps,
                    MIN(sgs.step_rate) as min_rate,
                    MAX(sgs.step_rate) as max_rate,
                    AVG(sgs.step_rate) as avg_rate,
                    COUNT(egm.employee_id) as mapped_employees
                FROM salary_grades sg
                LEFT JOIN organizationalstructure d ON sg.department_id = d.DepartmentID
                LEFT JOIN salary_grade_steps sgs ON sg.grade_id = sgs.grade_id
                LEFT JOIN employee_grade_mapping egm ON sg.grade_id = egm.grade_id AND egm.is_active = 1
                WHERE sg.is_active = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND sg.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND sg.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $sql .= " GROUP BY sg.grade_id
                  ORDER BY sg.grade_code";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee grade mapping data
     */
    private function getEmployeeGradeMappingData($filters = []) {
        $sql = "SELECT 
                    e.EmployeeID,
                    e.EmployeeNumber,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.JobTitle,
                    d.DepartmentName,
                    sg.grade_code,
                    sgs.step_number,
                    sgs.step_rate as grade_rate,
                    sgs.min_rate,
                    sgs.max_rate,
                    es.BaseSalary as current_salary,
                    CASE 
                        WHEN es.BaseSalary < sgs.min_rate THEN 'Below Band'
                        WHEN es.BaseSalary > sgs.max_rate THEN 'Above Band'
                        ELSE 'Within Band'
                    END as band_status,
                    egm.mapping_date,
                    egm.status as mapping_status
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.employee_id AND egm.is_active = 1
                LEFT JOIN salary_grades sg ON egm.grade_id = sg.grade_id
                LEFT JOIN salary_grade_steps sgs ON egm.step_id = sgs.step_id
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['grade_id'])) {
            $sql .= " AND egm.grade_id = :grade_id";
            $params[':grade_id'] = $filters['grade_id'];
        }
        
        if (!empty($filters['band_status'])) {
            $sql .= " AND CASE 
                        WHEN es.BaseSalary < sgs.min_rate THEN 'Below Band'
                        WHEN es.BaseSalary > sgs.max_rate THEN 'Above Band'
                        ELSE 'Within Band'
                      END = :band_status";
            $params[':band_status'] = $filters['band_status'];
        }
        
        $sql .= " ORDER BY d.DepartmentName, sg.grade_code, e.LastName";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get adjustment simulation data
     */
    private function getAdjustmentSimulationData($filters = []) {
        $simulationId = $filters['simulation_id'] ?? null;
        
        if (!$simulationId) {
            return [];
        }
        
        $sql = "SELECT 
                    cs.simulation_id,
                    cs.simulation_name,
                    cs.adjustment_type,
                    cs.adjustment_value,
                    cs.affected_employees,
                    cs.total_impact,
                    cs.created_at,
                    cs.status
                FROM compensation_simulations cs
                WHERE cs.simulation_id = :simulation_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':simulation_id', $simulationId, PDO::PARAM_INT);
        $stmt->execute();
        
        $simulation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$simulation) {
            return [];
        }
        
        // Get detailed results
        $sql = "SELECT 
                    e.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.JobTitle,
                    d.DepartmentName,
                    csr.current_salary,
                    csr.proposed_salary,
                    csr.salary_difference,
                    csr.percentage_change
                FROM compensation_simulation_results csr
                LEFT JOIN employees e ON csr.employee_id = e.EmployeeID
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                WHERE csr.simulation_id = :simulation_id
                ORDER BY d.DepartmentName, e.LastName";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':simulation_id', $simulationId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'simulation' => $simulation,
            'results' => $results
        ];
    }

    /**
     * Get payroll impact data
     */
    private function getPayrollImpactData($filters = []) {
        $sql = "SELECT 
                    pir.impact_id,
                    pir.workflow_id,
                    pw.workflow_name,
                    pir.total_impact,
                    pir.monthly_impact,
                    pir.annual_impact,
                    pir.affected_employees,
                    pir.created_at,
                    pir.status
                FROM payroll_impact_reports pir
                LEFT JOIN pay_adjustment_workflows pw ON pir.workflow_id = pw.workflow_id";
        
        $params = [];
        if (!empty($filters['workflow_id'])) {
            $sql .= " WHERE pir.workflow_id = :workflow_id";
            $params[':workflow_id'] = $filters['workflow_id'];
        }
        
        $sql .= " ORDER BY pir.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get equity distribution data
     */
    private function getEquityDistributionData($filters = []) {
        $sql = "SELECT 
                    e.Gender,
                    d.DepartmentName,
                    COUNT(e.EmployeeID) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    STDDEV(es.BaseSalary) as salary_stddev,
                    -- Calculate pay gap
                    CASE 
                        WHEN e.Gender = 'Male' THEN 
                            AVG(es.BaseSalary) - (SELECT AVG(es2.BaseSalary) 
                                                 FROM employees e2 
                                                 LEFT JOIN employeesalaries es2 ON e2.EmployeeID = es2.EmployeeID AND es2.IsCurrent = 1
                                                 WHERE e2.DepartmentID = e.DepartmentID AND e2.Gender = 'Female' AND e2.IsActive = 1)
                        WHEN e.Gender = 'Female' THEN 
                            AVG(es.BaseSalary) - (SELECT AVG(es2.BaseSalary) 
                                                 FROM employees e2 
                                                 LEFT JOIN employeesalaries es2 ON e2.EmployeeID = es2.EmployeeID AND es2.IsCurrent = 1
                                                 WHERE e2.DepartmentID = e.DepartmentID AND e2.Gender = 'Male' AND e2.IsActive = 1)
                        ELSE 0
                    END as pay_gap
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL AND e.Gender IS NOT NULL";
        
        $params = [];
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        $sql .= " GROUP BY e.Gender, d.DepartmentID, d.DepartmentName
                  ORDER BY d.DepartmentName, e.Gender";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get comprehensive data
     */
    private function getComprehensiveData($filters = []) {
        return [
            'salary_grades' => $this->getSalaryGradeTableData($filters),
            'employee_mapping' => $this->getEmployeeGradeMappingData($filters),
            'equity_analysis' => $this->getEquityDistributionData($filters),
            'payroll_impact' => $this->getPayrollImpactData($filters)
        ];
    }

    /**
     * Generate summary statistics
     */
    private function generateSummaryStatistics($data, $reportType) {
        $summary = [
            'total_records' => 0,
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        switch ($reportType) {
            case 'salary_grade_table':
                $summary['total_records'] = count($data);
                $summary['total_employees'] = array_sum(array_column($data, 'mapped_employees'));
                $summary['avg_employees_per_grade'] = $summary['total_records'] > 0 ? 
                    round($summary['total_employees'] / $summary['total_records'], 2) : 0;
                break;
                
            case 'employee_grade_mapping':
                $summary['total_records'] = count($data);
                $bandStatusCounts = array_count_values(array_column($data, 'band_status'));
                $summary['band_status_breakdown'] = $bandStatusCounts;
                break;
                
            case 'adjustment_simulation':
                if (isset($data['simulation'])) {
                    $summary['simulation_name'] = $data['simulation']['simulation_name'];
                    $summary['total_impact'] = $data['simulation']['total_impact'];
                    $summary['affected_employees'] = $data['simulation']['affected_employees'];
                }
                $summary['total_records'] = count($data['results'] ?? []);
                break;
                
            case 'payroll_impact':
                $summary['total_records'] = count($data);
                $summary['total_impact'] = array_sum(array_column($data, 'total_impact'));
                $summary['total_affected_employees'] = array_sum(array_column($data, 'affected_employees'));
                break;
                
            case 'equity_distribution':
                $summary['total_records'] = count($data);
                $summary['departments_analyzed'] = count(array_unique(array_column($data, 'DepartmentName')));
                break;
        }
        
        return $summary;
    }

    /**
     * Generate PDF report (placeholder)
     */
    private function generatePDFReport($report) {
        // This would integrate with a PDF library like TCPDF or FPDF
        // For now, return a placeholder
        return [
            'success' => true,
            'message' => 'PDF report generation not yet implemented',
            'data' => $report
        ];
    }

    /**
     * Generate Excel report (placeholder)
     */
    private function generateExcelReport($report) {
        // This would integrate with a library like PhpSpreadsheet
        // For now, return a placeholder
        return [
            'success' => true,
            'message' => 'Excel report generation not yet implemented',
            'data' => $report
        ];
    }

    /**
     * Export report data
     */
    public function exportReport($reportType, $filters = [], $format = 'json') {
        $report = $this->generateCompensationReport($reportType, $filters, $format);
        
        // Save report to database
        $this->saveReportToDatabase($report);
        
        return $report;
    }

    /**
     * Save report to database
     */
    private function saveReportToDatabase($report) {
        $sql = "INSERT INTO compensation_reports 
                (report_type, report_data, generated_by, generated_at) 
                VALUES (:report_type, :report_data, :generated_by, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':report_type', $report['report_metadata']['type']);
        $stmt->bindParam(':report_data', json_encode($report));
        $stmt->bindParam(':generated_by', $report['report_metadata']['generated_by']);
        $stmt->execute();
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Get report history
     */
    public function getReportHistory($limit = 50) {
        $sql = "SELECT 
                    report_id,
                    report_type,
                    generated_by,
                    generated_at,
                    LENGTH(report_data) as data_size
                FROM compensation_reports 
                ORDER BY generated_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
