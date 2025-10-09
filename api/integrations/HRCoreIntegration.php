<?php
/**
 * HR Core Integration for Compensation Planning
 * Handles synchronization with HR Core modules (HR1-HR3)
 */

require_once __DIR__ . '/../config.php';

class HRCoreIntegration {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all employees with current salary data for compensation planning
     */
    public function getEmployeesForCompensationPlanning($filters = []) {
        $sql = "SELECT 
                    e.EmployeeID,
                    e.EmployeeNumber,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.JobTitle,
                    e.DepartmentID,
                    d.DepartmentName,
                    e.HireDate,
                    e.IsActive,
                    es.BaseSalary,
                    es.PayFrequency,
                    es.EffectiveDate as salary_effective_date,
                    -- Calculate current rates
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22 / 8, 2)
                        WHEN es.PayFrequency = 'Daily' THEN ROUND(es.BaseSalary / 8, 2)
                        ELSE es.PayRate
                    END as hourly_rate,
                    CASE 
                        WHEN es.PayFrequency = 'Monthly' THEN ROUND(es.BaseSalary / 22, 2)
                        WHEN es.PayFrequency = 'Daily' THEN es.BaseSalary
                        ELSE ROUND(es.PayRate * 8, 2)
                    END as daily_rate,
                    -- Get position category for grade mapping
                    CASE 
                        WHEN e.JobTitle LIKE '%Manager%' OR e.JobTitle LIKE '%Director%' THEN 'Management'
                        WHEN e.JobTitle LIKE '%Nurse%' OR e.JobTitle LIKE '%Doctor%' THEN 'Clinical'
                        WHEN e.JobTitle LIKE '%Admin%' OR e.JobTitle LIKE '%Clerk%' THEN 'Administrative'
                        WHEN e.JobTitle LIKE '%Technician%' OR e.JobTitle LIKE '%Specialist%' THEN 'Technical'
                        ELSE 'General'
                    END as position_category,
                    -- Get current grade mapping if exists
                    egm.grade_id,
                    egm.step_id,
                    egm.mapping_date,
                    egm.status as mapping_status
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                LEFT JOIN employee_grade_mapping egm ON e.EmployeeID = egm.employee_id AND egm.is_active = 1
                WHERE e.IsActive = 1";

        $params = [];
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND e.DepartmentID = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['position_category'])) {
            $sql .= " AND CASE 
                        WHEN e.JobTitle LIKE '%Manager%' OR e.JobTitle LIKE '%Director%' THEN 'Management'
                        WHEN e.JobTitle LIKE '%Nurse%' OR e.JobTitle LIKE '%Doctor%' THEN 'Clinical'
                        WHEN e.JobTitle LIKE '%Admin%' OR e.JobTitle LIKE '%Clerk%' THEN 'Administrative'
                        WHEN e.JobTitle LIKE '%Technician%' OR e.JobTitle LIKE '%Specialist%' THEN 'Technical'
                        ELSE 'General'
                      END = :position_category";
            $params[':position_category'] = $filters['position_category'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                     OR e.EmployeeNumber LIKE :search 
                     OR e.JobTitle LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY d.DepartmentName, e.JobTitle, e.LastName";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get departments for compensation planning
     */
    public function getDepartmentsForCompensation() {
        $sql = "SELECT 
                    DepartmentID,
                    DepartmentName,
                    DepartmentCode,
                    ParentDepartmentID,
                    IsActive,
                    -- Count employees in each department
                    (SELECT COUNT(*) FROM employees WHERE DepartmentID = d.DepartmentID AND IsActive = 1) as employee_count
                FROM organizationalstructure d
                WHERE IsActive = 1
                ORDER BY DepartmentName";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get position categories for grade mapping
     */
    public function getPositionCategories() {
        $sql = "SELECT DISTINCT
                    CASE 
                        WHEN JobTitle LIKE '%Manager%' OR JobTitle LIKE '%Director%' THEN 'Management'
                        WHEN JobTitle LIKE '%Nurse%' OR JobTitle LIKE '%Doctor%' THEN 'Clinical'
                        WHEN JobTitle LIKE '%Admin%' OR JobTitle LIKE '%Clerk%' THEN 'Administrative'
                        WHEN JobTitle LIKE '%Technician%' OR JobTitle LIKE '%Specialist%' THEN 'Technical'
                        ELSE 'General'
                    END as position_category,
                    COUNT(*) as employee_count
                FROM employees 
                WHERE IsActive = 1
                GROUP BY position_category
                ORDER BY employee_count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate employee data for compensation planning
     */
    public function validateEmployeeData($employeeId) {
        $sql = "SELECT 
                    e.EmployeeID,
                    e.IsActive,
                    es.BaseSalary,
                    es.EffectiveDate,
                    CASE WHEN es.BaseSalary IS NULL THEN 'Missing Salary Data' ELSE 'OK' END as salary_status,
                    CASE WHEN e.DepartmentID IS NULL THEN 'Missing Department' ELSE 'OK' END as department_status,
                    CASE WHEN e.JobTitle IS NULL OR e.JobTitle = '' THEN 'Missing Job Title' ELSE 'OK' END as position_status
                FROM employees e
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.EmployeeID = :employee_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get salary history for an employee
     */
    public function getEmployeeSalaryHistory($employeeId, $limit = 12) {
        $sql = "SELECT 
                    BaseSalary,
                    PayFrequency,
                    PayRate,
                    EffectiveDate,
                    IsCurrent,
                    AdjustmentReason
                FROM employeesalaries 
                WHERE EmployeeID = :employee_id
                ORDER BY EffectiveDate DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sync employee data with compensation planning
     */
    public function syncEmployeeData($employeeId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get current employee data
            $employee = $this->getEmployeesForCompensationPlanning(['employee_id' => $employeeId]);
            
            if (empty($employee)) {
                throw new Exception("Employee not found");
            }
            
            $emp = $employee[0];
            
            // Update employee grade mapping if needed
            $this->updateEmployeeGradeMapping($emp);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Employee data synchronized successfully',
                'data' => $emp
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to sync employee data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update employee grade mapping based on current data
     */
    private function updateEmployeeGradeMapping($employee) {
        // This would contain logic to automatically map employees to appropriate grades
        // based on their position, department, and current salary
        // Implementation would depend on your specific business rules
    }

    /**
     * Get compensation analytics data
     */
    public function getCompensationAnalytics() {
        $sql = "SELECT 
                    d.DepartmentName,
                    COUNT(e.EmployeeID) as employee_count,
                    AVG(es.BaseSalary) as avg_salary,
                    MIN(es.BaseSalary) as min_salary,
                    MAX(es.BaseSalary) as max_salary,
                    STDDEV(es.BaseSalary) as salary_stddev
                FROM employees e
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                LEFT JOIN employeesalaries es ON e.EmployeeID = es.EmployeeID AND es.IsCurrent = 1
                WHERE e.IsActive = 1 AND es.BaseSalary IS NOT NULL
                GROUP BY d.DepartmentID, d.DepartmentName
                ORDER BY avg_salary DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
