<?php
/**
 * Department Model
 * Handles department-related database operations
 */

class Department {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get department by ID
     */
    public function getDepartmentById($departmentId) {
        $sql = "SELECT
                    d.DepartmentID, d.DepartmentName, d.Description, d.ManagerID,
                    d.Budget, d.Location, d.IsActive, d.CreatedDate,
                    CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                    COUNT(e.EmployeeID) AS EmployeeCount
                FROM OrganizationalStructure d
                LEFT JOIN Employees m ON d.ManagerID = m.EmployeeID
                LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.IsActive = 1
                WHERE d.DepartmentID = :department_id
                GROUP BY d.DepartmentID";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all HR divisions
     */
    public function getHRDivisions($includeInactive = false) {
        $sql = "SELECT 
                    d.DivisionID, d.DivisionName, d.DivisionCode, d.Description,
                    d.DivisionHead, d.ParentDivisionID, d.IsActive,
                    CONCAT(e.FirstName, ' ', e.LastName) AS DivisionHeadName,
                    pd.DivisionName AS ParentDivisionName,
                    COUNT(hjr.JobRoleID) AS RoleCount
                FROM hr_divisions d
                LEFT JOIN employees e ON d.DivisionHead = e.EmployeeID
                LEFT JOIN hr_divisions pd ON d.ParentDivisionID = pd.DivisionID
                LEFT JOIN hospital_job_roles hjr ON d.DivisionID = hjr.DivisionID";
        
        if (!$includeInactive) {
            $sql .= " WHERE d.IsActive = 1";
        }
        
        $sql .= " GROUP BY d.DivisionID ORDER BY d.ParentDivisionID, d.DivisionName";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hospital job roles
     */
    public function getHospitalJobRoles($divisionId = null, $departmentId = null) {
        $sql = "SELECT 
                    hjr.JobRoleID, hjr.RoleTitle, hjr.RoleCode, hjr.DivisionID, 
                    hjr.DepartmentID, hjr.JobLevel, hjr.JobFamily, hjr.MinimumQualification,
                    hjr.JobDescription, hjr.ReportsTo, hjr.IsActive,
                    d.DivisionName, dept.DepartmentName,
                    CONCAT(e.FirstName, ' ', e.LastName) AS ReportsToName,
                    COUNT(emp.EmployeeID) AS EmployeeCount
                FROM hospital_job_roles hjr
                LEFT JOIN hr_divisions d ON hjr.DivisionID = d.DivisionID
                LEFT JOIN organizationalstructure dept ON hjr.DepartmentID = dept.DepartmentID
                LEFT JOIN hospital_job_roles reports_role ON hjr.ReportsTo = reports_role.JobRoleID
                LEFT JOIN employees e ON reports_role.JobRoleID = e.JobRoleID
                LEFT JOIN employees emp ON hjr.JobRoleID = emp.JobRoleID
                WHERE hjr.IsActive = 1";
        
        $params = [];
        
        if ($divisionId) {
            $sql .= " AND hjr.DivisionID = :division_id";
            $params[':division_id'] = $divisionId;
        }
        
        if ($departmentId) {
            $sql .= " AND hjr.DepartmentID = :department_id";
            $params[':department_id'] = $departmentId;
        }
        
        $sql .= " GROUP BY hjr.JobRoleID ORDER BY hjr.JobLevel, hjr.RoleTitle";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get department HR coordinators
     */
    public function getDepartmentHRCoordinators($departmentId = null) {
        $sql = "SELECT 
                    dhc.CoordinatorID, dhc.DepartmentID, dhc.EmployeeID,
                    dhc.CoordinatorType, dhc.EffectiveDate, dhc.EndDate, dhc.IsActive,
                    CONCAT(e.FirstName, ' ', e.LastName) AS CoordinatorName,
                    e.Email, e.PhoneNumber, e.JobTitle,
                    dept.DepartmentName, dept.DepartmentCode,
                    CONCAT(assigned.FirstName, ' ', assigned.LastName) AS AssignedByName
                FROM department_hr_coordinators dhc
                LEFT JOIN employees e ON dhc.EmployeeID = e.EmployeeID
                LEFT JOIN organizationalstructure dept ON dhc.DepartmentID = dept.DepartmentID
                LEFT JOIN employees assigned ON dhc.AssignedBy = assigned.EmployeeID
                WHERE dhc.IsActive = 1";
        
        if ($departmentId) {
            $sql .= " AND dhc.DepartmentID = :department_id";
        }
        
        $sql .= " ORDER BY dept.DepartmentName, dhc.CoordinatorType, dhc.EffectiveDate DESC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($departmentId) {
            $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hospital organizational hierarchy
     */
    public function getHospitalOrgHierarchy() {
        $sql = "SELECT 
                    os.DepartmentID, os.DepartmentName, os.DepartmentCode, 
                    os.DepartmentType, os.Description, os.ManagerID, os.ParentDepartmentID,
                    CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                    m.JobTitle AS ManagerTitle,
                    parent_os.DepartmentName AS ParentDepartmentName,
                    COUNT(e.EmployeeID) AS EmployeeCount,
                    COUNT(dhc.CoordinatorID) AS CoordinatorCount
                FROM organizationalstructure os
                LEFT JOIN employees m ON os.ManagerID = m.EmployeeID
                LEFT JOIN organizationalstructure parent_os ON os.ParentDepartmentID = parent_os.DepartmentID
                LEFT JOIN employees e ON os.DepartmentID = e.DepartmentID AND e.IsActive = 1
                LEFT JOIN department_hr_coordinators dhc ON os.DepartmentID = dhc.DepartmentID AND dhc.IsActive = 1
                WHERE os.IsActive = 1
                GROUP BY os.DepartmentID
                ORDER BY os.ParentDepartmentID, os.DepartmentName";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all departments
     */
    public function getDepartments($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT
                    d.DepartmentID, d.DepartmentName, d.Description, d.ManagerID,
                    d.Budget, d.Location, d.IsActive, d.CreatedDate,
                    CONCAT(m.FirstName, ' ', m.LastName) AS ManagerName,
                    COUNT(e.EmployeeID) AS EmployeeCount
                FROM OrganizationalStructure d
                LEFT JOIN Employees m ON d.ManagerID = m.EmployeeID
                LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.IsActive = 1
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['is_active'])) {
            $sql .= " AND d.IsActive = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.DepartmentName LIKE :search OR d.Description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY d.DepartmentID ORDER BY d.DepartmentName LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total departments
     */
    public function countDepartments($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM OrganizationalStructure d WHERE 1=1";
        $params = [];

        // Apply same filters as getDepartments
        if (!empty($filters['is_active'])) {
            $sql .= " AND d.IsActive = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.DepartmentName LIKE :search OR d.Description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    }

    /**
     * Create new department
     */
    public function createDepartment($data) {
        $sql = "INSERT INTO OrganizationalStructure (
                    DepartmentName, Description, ManagerID, Budget, Location, IsActive
                ) VALUES (
                    :department_name, :description, :manager_id, :budget, :location, :is_active
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':manager_id', $data['manager_id'], PDO::PARAM_INT);
        $stmt->bindParam(':budget', $data['budget'], PDO::PARAM_STR);
        $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
        
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    /**
     * Update department
     */
    public function updateDepartment($departmentId, $data) {
        $sql = "UPDATE OrganizationalStructure SET 
                    DepartmentName = :department_name,
                    Description = :description,
                    ManagerID = :manager_id,
                    Budget = :budget,
                    Location = :location,
                    IsActive = :is_active
                WHERE DepartmentID = :department_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':manager_id', $data['manager_id'], PDO::PARAM_INT);
        $stmt->bindParam(':budget', $data['budget'], PDO::PARAM_STR);
        $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
        $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Delete department (soft delete)
     */
    public function deleteDepartment($departmentId) {
        $sql = "UPDATE OrganizationalStructure SET IsActive = 0 WHERE DepartmentID = :department_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Check if department name exists
     */
    public function departmentNameExists($departmentName, $excludeDepartmentId = null) {
        $sql = "SELECT DepartmentID FROM OrganizationalStructure WHERE DepartmentName = :department_name";
        
        if ($excludeDepartmentId) {
            $sql .= " AND DepartmentID != :exclude_department_id";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
        
        if ($excludeDepartmentId) {
            $stmt->bindParam(':exclude_department_id', $excludeDepartmentId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    /**
     * Get department employees
     */
    public function getDepartmentEmployees($departmentId) {
        $sql = "SELECT
                    e.EmployeeID, e.FirstName, e.LastName, e.Email, e.JobTitle,
                    e.HireDate, e.IsActive,
                    u.Username, r.RoleName
                FROM Employees e
                LEFT JOIN Users u ON e.EmployeeID = u.EmployeeID
                LEFT JOIN Roles r ON u.RoleID = r.RoleID
                WHERE e.DepartmentID = :department_id
                ORDER BY e.LastName, e.FirstName";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

