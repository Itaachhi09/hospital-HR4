<?php
/**
 * Role-Based Access Control Middleware
 * 
 * Checks if user has permission to access a resource.
 */

namespace Common\Middlewares;

use Common\Utils\Response;

class RBACMiddleware {
    /**
     * Check if user has permission
     * 
     * @param string $module Module name
     * @param string $action Action (create, read, update, delete)
     * @return bool
     */
    public function hasPermission(string $module, string $action): bool {
        // Get current user from session
        $user = $this->getCurrentUser();
        
        if ($user === null) {
            return false;
        }
        
        // Get user role
        $role = $user['RoleName'] ?? null;
        
        if ($role === null) {
            return false;
        }
        
        // Check permissions based on role
        return $this->checkRolePermission($role, $module, $action);
    }
    
    /**
     * Get current user from session
     * 
     * @return array|null
     */
    private function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Check role permission
     * 
     * @param string $role Role name
     * @param string $module Module name
     * @param string $action Action
     * @return bool
     */
    private function checkRolePermission(string $role, string $module, string $action): bool {
        // Permission matrix
        $permissions = [
            'System Admin' => [
                '*' => ['*'] // All permissions
            ],
            'HR Admin' => [
                'hrcore' => ['create', 'read', 'update', 'delete'],
                'payroll' => ['read', 'update'],
                'hmo' => ['read', 'update'],
                'analytics' => ['read']
            ],
            'HR Director' => [
                'hrcore' => ['read', 'update'],
                'payroll' => ['read'],
                'analytics' => ['read']
            ],
            'Employee' => [
                'hrcore' => ['read'], // Own data only
                'payroll' => ['read'], // Own payslips only
                'hmo' => ['read'] // Own enrollments only
            ]
        ];
        
        // Check if role has wildcard permission
        if (isset($permissions[$role]['*']) && 
            in_array('*', $permissions[$role]['*'])) {
            return true;
        }
        
        // Check module permission
        if (!isset($permissions[$role][$module])) {
            return false;
        }
        
        // Check action permission
        return in_array($action, $permissions[$role][$module]) ||
               in_array('*', $permissions[$role][$module]);
    }
    
    /**
     * Require permission or throw exception
     * 
     * @param string $module
     * @param string $action
     * @return void
     * @throws \Exception
     */
    public function requirePermission(string $module, string $action): void {
        if (!$this->hasPermission($module, $action)) {
            throw new \Exception("Permission denied: {$module}.{$action}");
        }
    }
}
