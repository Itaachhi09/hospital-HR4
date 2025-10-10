<?php
/**
 * HR Reports Access Control
 * Manages role-based access to different report levels and features
 */

require_once __DIR__ . '/../config.php';

class HRReportsAccessControl {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Define report access levels
     */
    private function getReportAccessLevels() {
        return [
            'executive-summary' => [
                'roles' => ['System Admin', 'HR Manager', 'Executive', 'Finance Manager'],
                'level' => 'executive',
                'description' => 'Top-level KPIs and strategic insights'
            ],
            'employee-demographics' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Executive'],
                'level' => 'manager',
                'description' => 'Workforce composition and demographics'
            ],
            'recruitment-application' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff'],
                'level' => 'hr',
                'description' => 'Hiring efficiency and pipeline performance'
            ],
            'payroll-compensation' => [
                'roles' => ['System Admin', 'HR Manager', 'Finance Manager', 'Executive'],
                'level' => 'finance',
                'description' => 'Payroll expenses and compensation analysis'
            ],
            'attendance-leave' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Department Manager'],
                'level' => 'manager',
                'description' => 'Attendance patterns and leave utilization'
            ],
            'benefits-hmo-utilization' => [
                'roles' => ['System Admin', 'HR Manager', 'Finance Manager', 'Executive'],
                'level' => 'finance',
                'description' => 'Benefits costs and HMO utilization'
            ],
            'training-development' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Department Manager'],
                'level' => 'manager',
                'description' => 'Training effectiveness and development'
            ],
            'employee-relations-engagement' => [
                'roles' => ['System Admin', 'HR Manager', 'Executive'],
                'level' => 'executive',
                'description' => 'Employee engagement and relations'
            ],
            'turnover-retention' => [
                'roles' => ['System Admin', 'HR Manager', 'Executive'],
                'level' => 'executive',
                'description' => 'Turnover analysis and retention strategies'
            ],
            'compliance-document' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Compliance Officer'],
                'level' => 'compliance',
                'description' => 'Document compliance and expiring credentials'
            ]
        ];
    }

    /**
     * Define feature access levels
     */
    private function getFeatureAccessLevels() {
        return [
            'export_pdf' => [
                'roles' => ['System Admin', 'HR Manager', 'Executive', 'Finance Manager'],
                'level' => 'manager',
                'description' => 'Export reports to PDF format'
            ],
            'export_excel' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Finance Manager'],
                'level' => 'staff',
                'description' => 'Export reports to Excel format'
            ],
            'export_csv' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff', 'Department Manager'],
                'level' => 'staff',
                'description' => 'Export reports to CSV format'
            ],
            'schedule_reports' => [
                'roles' => ['System Admin', 'HR Manager'],
                'level' => 'admin',
                'description' => 'Schedule automatic report generation'
            ],
            'view_audit_trail' => [
                'roles' => ['System Admin', 'HR Manager'],
                'level' => 'admin',
                'description' => 'View report generation audit trail'
            ],
            'custom_filters' => [
                'roles' => ['System Admin', 'HR Manager', 'HR Staff'],
                'level' => 'staff',
                'description' => 'Apply custom filters to reports'
            ],
            'department_data' => [
                'roles' => ['System Admin', 'HR Manager', 'Department Manager'],
                'level' => 'manager',
                'description' => 'View department-specific data'
            ],
            'financial_data' => [
                'roles' => ['System Admin', 'HR Manager', 'Finance Manager', 'Executive'],
                'level' => 'finance',
                'description' => 'View financial and cost data'
            ]
        ];
    }

    /**
     * Check if user has access to a specific report
     */
    public function hasReportAccess($userId, $reportType) {
        $userRole = $this->getUserRole($userId);
        $accessLevels = $this->getReportAccessLevels();
        
        if (!isset($accessLevels[$reportType])) {
            return false; // Unknown report type
        }
        
        return in_array($userRole, $accessLevels[$reportType]['roles']);
    }

    /**
     * Check if user has access to a specific feature
     */
    public function hasFeatureAccess($userId, $feature) {
        $userRole = $this->getUserRole($userId);
        $accessLevels = $this->getFeatureAccessLevels();
        
        if (!isset($accessLevels[$feature])) {
            return false; // Unknown feature
        }
        
        return in_array($userRole, $accessLevels[$feature]['roles']);
    }

    /**
     * Get user's role
     */
    private function getUserRole($userId) {
        $sql = "SELECT r.RoleName 
                FROM users u
                LEFT JOIN roles r ON u.RoleID = r.RoleID
                WHERE u.UserID = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['RoleName'] ?? 'Guest';
    }

    /**
     * Get accessible reports for user
     */
    public function getAccessibleReports($userId) {
        $userRole = $this->getUserRole($userId);
        $accessLevels = $this->getReportAccessLevels();
        $accessibleReports = [];
        
        foreach ($accessLevels as $reportType => $config) {
            if (in_array($userRole, $config['roles'])) {
                $accessibleReports[$reportType] = [
                    'name' => ucwords(str_replace('-', ' ', $reportType)),
                    'level' => $config['level'],
                    'description' => $config['description']
                ];
            }
        }
        
        return $accessibleReports;
    }

    /**
     * Get accessible features for user
     */
    public function getAccessibleFeatures($userId) {
        $userRole = $this->getUserRole($userId);
        $accessLevels = $this->getFeatureAccessLevels();
        $accessibleFeatures = [];
        
        foreach ($accessLevels as $feature => $config) {
            if (in_array($userRole, $config['roles'])) {
                $accessibleFeatures[$feature] = [
                    'name' => ucwords(str_replace('_', ' ', $feature)),
                    'level' => $config['level'],
                    'description' => $config['description']
                ];
            }
        }
        
        return $accessibleFeatures;
    }

    /**
     * Filter data based on user access level
     */
    public function filterDataByAccess($userId, $reportType, $data) {
        $userRole = $this->getUserRole($userId);
        
        // Apply role-based data filtering
        switch ($userRole) {
            case 'Department Manager':
                return $this->filterDepartmentData($userId, $data);
            case 'HR Staff':
                return $this->filterHRStaffData($data);
            case 'Finance Manager':
                return $this->filterFinanceData($data);
            case 'Executive':
                return $this->filterExecutiveData($data);
            default:
                return $data; // System Admin and HR Manager see all data
        }
    }

    /**
     * Filter data for Department Manager
     */
    private function filterDepartmentData($userId, $data) {
        // Get user's department
        $userDepartment = $this->getUserDepartment($userId);
        
        if (!$userDepartment) {
            return []; // No department access
        }
        
        // Filter data to show only user's department
        $filteredData = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    // Array of objects - filter by department
                    $filteredData[$key] = array_filter($value, function($item) use ($userDepartment) {
                        return isset($item['DepartmentName']) && $item['DepartmentName'] === $userDepartment;
                    });
                } else {
                    $filteredData[$key] = $value;
                }
            } else {
                $filteredData[$key] = $value;
            }
        }
        
        return $filteredData;
    }

    /**
     * Filter data for HR Staff
     */
    private function filterHRStaffData($data) {
        // HR Staff can see most data but not sensitive financial information
        $sensitiveKeys = ['monthly_payroll_cost', 'total_monthly_payroll', 'avg_monthly_salary'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        
        return $data;
    }

    /**
     * Filter data for Finance Manager
     */
    private function filterFinanceData($data) {
        // Finance Manager sees financial data but not personal details
        $personalKeys = ['employee_name', 'personal_details'];
        
        foreach ($personalKeys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        
        return $data;
    }

    /**
     * Filter data for Executive
     */
    private function filterExecutiveData($data) {
        // Executive sees high-level summaries and KPIs
        $executiveKeys = ['kpi_metrics', 'summary', 'overview', 'trend_indicators'];
        $filteredData = [];
        
        foreach ($executiveKeys as $key) {
            if (isset($data[$key])) {
                $filteredData[$key] = $data[$key];
            }
        }
        
        return $filteredData;
    }

    /**
     * Get user's department
     */
    private function getUserDepartment($userId) {
        $sql = "SELECT d.DepartmentName 
                FROM users u
                LEFT JOIN employees e ON u.EmployeeID = e.EmployeeID
                LEFT JOIN organizationalstructure d ON e.DepartmentID = d.DepartmentID
                WHERE u.UserID = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['DepartmentName'] ?? null;
    }

    /**
     * Check if user can access department-specific data
     */
    public function canAccessDepartmentData($userId, $departmentId) {
        $userRole = $this->getUserRole($userId);
        
        // System Admin and HR Manager can access all departments
        if (in_array($userRole, ['System Admin', 'HR Manager'])) {
            return true;
        }
        
        // Department Manager can only access their own department
        if ($userRole === 'Department Manager') {
            $userDepartment = $this->getUserDepartment($userId);
            $targetDepartment = $this->getDepartmentName($departmentId);
            
            return $userDepartment === $targetDepartment;
        }
        
        return false;
    }

    /**
     * Get department name by ID
     */
    private function getDepartmentName($departmentId) {
        $sql = "SELECT DepartmentName FROM organizationalstructure WHERE DepartmentID = :department_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':department_id', $departmentId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['DepartmentName'] ?? null;
    }

    /**
     * Get user's access summary
     */
    public function getUserAccessSummary($userId) {
        $userRole = $this->getUserRole($userId);
        $accessibleReports = $this->getAccessibleReports($userId);
        $accessibleFeatures = $this->getAccessibleFeatures($userId);
        
        return [
            'user_id' => $userId,
            'role' => $userRole,
            'accessible_reports' => $accessibleReports,
            'accessible_features' => $accessibleFeatures,
            'access_level' => $this->getAccessLevel($userRole),
            'department_access' => $this->getUserDepartment($userId)
        ];
    }

    /**
     * Get access level for role
     */
    private function getAccessLevel($role) {
        $accessLevels = [
            'System Admin' => 'admin',
            'HR Manager' => 'manager',
            'Executive' => 'executive',
            'Finance Manager' => 'finance',
            'HR Staff' => 'staff',
            'Department Manager' => 'department',
            'Compliance Officer' => 'compliance',
            'Guest' => 'guest'
        ];
        
        return $accessLevels[$role] ?? 'guest';
    }

    /**
     * Validate report access before processing
     */
    public function validateReportAccess($userId, $reportType, $filters = []) {
        // Check basic report access
        if (!$this->hasReportAccess($userId, $reportType)) {
            throw new Exception("Access denied: You don't have permission to access this report");
        }
        
        // Check department access if department filter is applied
        if (isset($filters['department_id']) && !empty($filters['department_id'])) {
            if (!$this->canAccessDepartmentData($userId, $filters['department_id'])) {
                throw new Exception("Access denied: You don't have permission to access this department's data");
            }
        }
        
        return true;
    }

    /**
     * Get role hierarchy
     */
    public function getRoleHierarchy() {
        return [
            'System Admin' => [
                'level' => 1,
                'description' => 'Full system access',
                'can_access' => 'all'
            ],
            'HR Manager' => [
                'level' => 2,
                'description' => 'HR management access',
                'can_access' => 'hr_all'
            ],
            'Executive' => [
                'level' => 3,
                'description' => 'Executive level access',
                'can_access' => 'executive_reports'
            ],
            'Finance Manager' => [
                'level' => 3,
                'description' => 'Financial data access',
                'can_access' => 'financial_reports'
            ],
            'HR Staff' => [
                'level' => 4,
                'description' => 'HR operational access',
                'can_access' => 'hr_operational'
            ],
            'Department Manager' => [
                'level' => 4,
                'description' => 'Department-specific access',
                'can_access' => 'department_data'
            ],
            'Compliance Officer' => [
                'level' => 4,
                'description' => 'Compliance monitoring access',
                'can_access' => 'compliance_data'
            ],
            'Guest' => [
                'level' => 5,
                'description' => 'Limited access',
                'can_access' => 'none'
            ]
        ];
    }
}
?>
