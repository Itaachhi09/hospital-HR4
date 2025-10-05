<?php
/**
 * Position Model
 * Aggregates positions by department and maps pay grades for roles
 */
class Position {
    private $pdo;
    public function __construct(){ global $pdo; $this->pdo = $pdo; }

    public function getDepartmentPositionSummary($departmentId = null){
        $sql = "SELECT dept.DepartmentID, dept.DepartmentName,
                       hjr.JobRoleID, hjr.RoleTitle, hjr.JobLevel, hjr.SalaryGrade,
                       hjr.HeadcountBudget,
                       COUNT(e.EmployeeID) AS FilledCount,
                       GREATEST(COALESCE(hjr.HeadcountBudget,0) - COUNT(e.EmployeeID), 0) AS VacantCount
                FROM hospital_job_roles hjr
                LEFT JOIN Employees e ON hjr.JobRoleID = e.JobRoleID AND e.IsActive = 1
                LEFT JOIN OrganizationalStructure dept ON hjr.DepartmentID = dept.DepartmentID
                WHERE hjr.IsActive = 1";
        $params = [];
        if ($departmentId) { $sql .= " AND hjr.DepartmentID = :dept"; $params[':dept'] = (int)$departmentId; }
        $sql .= " GROUP BY dept.DepartmentID, dept.DepartmentName, hjr.JobRoleID, hjr.RoleTitle, hjr.JobLevel, hjr.SalaryGrade, hjr.HeadcountBudget
                  ORDER BY dept.DepartmentName, hjr.RoleTitle";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayGradeMapping(){
        $sql = "SELECT hjr.JobRoleID, hjr.RoleTitle, hjr.JobLevel, hjr.SalaryGrade, hjr.PayGradeMin, hjr.PayGradeMax,
                       dept.DepartmentID, dept.DepartmentName
                FROM hospital_job_roles hjr
                LEFT JOIN OrganizationalStructure dept ON hjr.DepartmentID = dept.DepartmentID
                WHERE hjr.IsActive = 1
                ORDER BY hjr.SalaryGrade, hjr.RoleTitle";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
