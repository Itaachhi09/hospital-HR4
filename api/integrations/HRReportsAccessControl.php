<?php
/**
 * HR Reports Access Control
 * Manages user permissions and data filtering for reports
 */

require_once __DIR__ . '/../config.php';

class HRReportsAccessControl {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Validate report access for user
     */
    public function validateReportAccess($userId, $reportType, $filters = []) {
        try {
            // Get user role and permissions
            $userPermissions = $this->getUserPermissions($userId);
            
            // Check if user has access to this report type
            if (!$this->hasReportAccess($userPermissions, $reportType)) {
                throw new Exception("Access denied for report type: $reportType");
            }
            
            // Validate filters based on user permissions
            $this->validateFilters($userPermissions, $filters);
            
            return true;
        } catch (Exception $e) {
            error_log("Report access validation error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Filter data based on user access level
     */
    public function filterDataByAccess($userId, $reportType, $data) {
        try {
            $userPermissions = $this->getUserPermissions($userId);
            
            // Apply data filtering based on user role
            switch ($userPermissions['role_name']) {
                case 'System Admin':
                case 'HR Manager':
                    // Full access to all data
                    return $data;
                    
                case 'HR Staff':
                    // Limited access - filter sensitive information
                    return $this->filterSensitiveData($data);
                    
                case 'Finance Manager':
                    // Access to financial data only
                    return $this->filterFinancialData($data);
                    
                case 'Department Head':
                    // Access to department data only
                    return $this->filterDepartmentData($data, $userPermissions['department_id']);
                    
                default:
                    // Minimal access
                    return $this->filterMinimalData($data);
            }
        } catch (Exception $e) {
            error_log("Data filtering error: " . $e->getMessage());
            return $data; // Return original data if filtering fails
        }
    }

    /**
     * Get user permissions
     */
    private function getUserPermissions($userId) {
        $sql = "SELECT u.UserID, u.Username, r.RoleName, u.DepartmentID, u.BranchID
                FROM users u
                LEFT JOIN roles r ON u.RoleID = r.RoleID
                WHERE u.UserID = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("User not found");
        }
        
        return [
            'user_id' => $result['UserID'],
            'username' => $result['Username'],
            'role_name' => $result['RoleName'],
            'department_id' => $result['DepartmentID'],
            'branch_id' => $result['BranchID']
        ];
    }

    /**
     * Check if user has access to specific report type
     */
    private function hasReportAccess($userPermissions, $reportType) {
        $roleName = $userPermissions['role_name'];
        
        // Define access matrix
        $accessMatrix = [
            'System Admin' => ['*'], // Access to all reports
            'HR Manager' => [
                'executive-summary', 'employee-demographics', 'recruitment-application',
                'payroll-compensation', 'attendance-leave', 'benefits-hmo-utilization',
                'training-development', 'employee-relations-engagement', 'turnover-retention',
                'compliance-document'
            ],
            'HR Staff' => [
                'employee-demographics', 'attendance-leave', 'training-development',
                'employee-relations-engagement', 'compliance-document'
            ],
            'Finance Manager' => [
                'executive-summary', 'payroll-compensation', 'benefits-hmo-utilization'
            ],
            'Department Head' => [
                'employee-demographics', 'attendance-leave', 'training-development'
            ]
        ];
        
        $allowedReports = $accessMatrix[$roleName] ?? [];
        
        return in_array('*', $allowedReports) || in_array($reportType, $allowedReports);
    }

    /**
     * Validate filters based on user permissions
     */
    private function validateFilters($userPermissions, $filters) {
        $roleName = $userPermissions['role_name'];
        
        // Department heads can only access their department data
        if ($roleName === 'Department Head' && !empty($filters['department_id'])) {
            if ($filters['department_id'] != $userPermissions['department_id']) {
                throw new Exception("Access denied: Cannot access other department data");
            }
        }
        
        // Branch managers can only access their branch data
        if ($roleName === 'Branch Manager' && !empty($filters['branch_id'])) {
            if ($filters['branch_id'] != $userPermissions['branch_id']) {
                throw new Exception("Access denied: Cannot access other branch data");
            }
        }
    }

    /**
     * Filter sensitive data for HR Staff
     */
    private function filterSensitiveData($data) {
        // Remove sensitive fields like salary details, personal information
        $sensitiveFields = ['salary', 'sss_number', 'tin_number', 'bank_account'];
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), $sensitiveFields)) {
                    unset($data[$key]);
                } elseif (is_array($value)) {
                    $data[$key] = $this->filterSensitiveData($value);
                }
            }
        }
        
        return $data;
    }

    /**
     * Filter financial data for Finance Manager
     */
    private function filterFinancialData($data) {
        // Keep only financial-related fields
        $financialFields = ['cost', 'salary', 'payroll', 'benefits', 'premium', 'amount'];
        
        if (is_array($data)) {
            $filtered = [];
            foreach ($data as $key => $value) {
                $keyLower = strtolower($key);
                $isFinancial = false;
                
                foreach ($financialFields as $field) {
                    if (strpos($keyLower, $field) !== false) {
                        $isFinancial = true;
                        break;
                    }
                }
                
                if ($isFinancial) {
                    $filtered[$key] = $value;
                } elseif (is_array($value)) {
                    $filtered[$key] = $this->filterFinancialData($value);
                }
            }
            return $filtered;
        }
        
        return $data;
    }

    /**
     * Filter data for specific department
     */
    private function filterDepartmentData($data, $departmentId) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) && isset($value['department_id'])) {
                    if ($value['department_id'] != $departmentId) {
                        unset($data[$key]);
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->filterDepartmentData($value, $departmentId);
                }
            }
        }
        
        return $data;
    }

    /**
     * Filter to minimal data for basic users
     */
    private function filterMinimalData($data) {
        // Return only basic counts and non-sensitive information
        $minimalFields = ['count', 'total', 'summary', 'overview'];
        
        if (is_array($data)) {
            $filtered = [];
            foreach ($data as $key => $value) {
                $keyLower = strtolower($key);
                $isMinimal = false;
                
                foreach ($minimalFields as $field) {
                    if (strpos($keyLower, $field) !== false) {
                        $isMinimal = true;
                        break;
                    }
                }
                
                if ($isMinimal) {
                    $filtered[$key] = $value;
                } elseif (is_array($value)) {
                    $filtered[$key] = $this->filterMinimalData($value);
                }
            }
            return $filtered;
        }
        
        return $data;
    }

    /**
     * Get user access summary
     */
    public function getUserAccessSummary($userId) {
        try {
            $userPermissions = $this->getUserPermissions($userId);
            
            return [
                'user_id' => $userPermissions['user_id'],
                'username' => $userPermissions['username'],
                'role_name' => $userPermissions['role_name'],
                'department_id' => $userPermissions['department_id'],
                'branch_id' => $userPermissions['branch_id'],
                'access_level' => $this->getAccessLevel($userPermissions['role_name']),
                'allowed_reports' => $this->getAllowedReports($userPermissions['role_name']),
                'restrictions' => $this->getUserRestrictions($userPermissions)
            ];
        } catch (Exception $e) {
            error_log("Get user access summary error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get access level for role
     */
    private function getAccessLevel($roleName) {
        $accessLevels = [
            'System Admin' => 'full',
            'HR Manager' => 'high',
            'HR Staff' => 'medium',
            'Finance Manager' => 'financial',
            'Department Head' => 'department',
            'Branch Manager' => 'branch',
            'Employee' => 'minimal'
        ];
        
        return $accessLevels[$roleName] ?? 'minimal';
    }

    /**
     * Get allowed reports for role
     */
    private function getAllowedReports($roleName) {
        $accessMatrix = [
            'System Admin' => ['*'],
            'HR Manager' => [
                'executive-summary', 'employee-demographics', 'recruitment-application',
                'payroll-compensation', 'attendance-leave', 'benefits-hmo-utilization',
                'training-development', 'employee-relations-engagement', 'turnover-retention',
                'compliance-document'
            ],
            'HR Staff' => [
                'employee-demographics', 'attendance-leave', 'training-development',
                'employee-relations-engagement', 'compliance-document'
            ],
            'Finance Manager' => [
                'executive-summary', 'payroll-compensation', 'benefits-hmo-utilization'
            ],
            'Department Head' => [
                'employee-demographics', 'attendance-leave', 'training-development'
            ]
        ];
        
        return $accessMatrix[$roleName] ?? [];
    }

    /**
     * Get user restrictions
     */
    private function getUserRestrictions($userPermissions) {
        $restrictions = [];
        
        if ($userPermissions['role_name'] === 'Department Head') {
            $restrictions[] = 'department_limited';
        }
        
        if ($userPermissions['role_name'] === 'Branch Manager') {
            $restrictions[] = 'branch_limited';
        }
        
        if (in_array($userPermissions['role_name'], ['HR Staff', 'Employee'])) {
            $restrictions[] = 'no_sensitive_data';
        }
        
        return $restrictions;
    }
}
?>