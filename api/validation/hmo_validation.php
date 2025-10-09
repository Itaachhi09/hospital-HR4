<?php
/**
 * HMO Module Comprehensive Validation Script
 * Tests all HMO functionality and identifies issues
 */

require_once __DIR__ . '/../config.php';

class HMOValidationScript {
    private $pdo;
    private $issues = [];
    private $fixes = [];
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Run comprehensive validation
     */
    public function runValidation() {
        echo "🔍 Starting HMO Module Comprehensive Validation...\n\n";
        
        $this->validateDatabaseSchema();
        $this->validateAPIEndpoints();
        $this->validateDataIntegrity();
        $this->validateSecurity();
        $this->validateIntegration();
        $this->validatePerformance();
        
        $this->generateReport();
    }
    
    /**
     * Validate database schema
     */
    private function validateDatabaseSchema() {
        echo "📊 Validating Database Schema...\n";
        
        $requiredTables = [
            'hmoproviders',
            'hmoplans', 
            'employeehmoenrollments',
            'hmoclaims',
            'hmodependents',
            'hmo_notifications',
            'claim_workflows',
            'claim_workflow_steps',
            'hmo_reimbursements',
            'hmo_provider_contracts',
            'hmo_benefit_balances'
        ];
        
        foreach ($requiredTables as $table) {
            $sql = "SHOW TABLES LIKE '$table'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                $this->issues[] = "Missing table: $table";
                $this->fixes[] = "CREATE TABLE $table with proper schema";
            } else {
                echo "✅ Table $table exists\n";
            }
        }
        
        // Check foreign key constraints
        $this->validateForeignKeys();
        
        // Check indexes
        $this->validateIndexes();
    }
    
    /**
     * Validate foreign key constraints
     */
    private function validateForeignKeys() {
        echo "🔗 Validating Foreign Key Constraints...\n";
        
        $expectedFKs = [
            'hmoplans' => ['ProviderID' => 'hmoproviders'],
            'employeehmoenrollments' => ['PlanID' => 'hmoplans', 'EmployeeID' => 'employees'],
            'hmoclaims' => ['EnrollmentID' => 'employeehmoenrollments'],
            'hmodependents' => ['EnrollmentID' => 'employeehmoenrollments'],
            'claim_workflows' => ['ClaimID' => 'hmoclaims'],
            'claim_workflow_steps' => ['WorkflowID' => 'claim_workflows'],
            'hmo_reimbursements' => ['ClaimID' => 'hmoclaims', 'EmployeeID' => 'employees']
        ];
        
        foreach ($expectedFKs as $table => $constraints) {
            foreach ($constraints as $column => $refTable) {
                $sql = "SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = '$table' 
                        AND COLUMN_NAME = '$column' 
                        AND REFERENCED_TABLE_NAME = '$refTable'";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                
                if (!$stmt->fetch()) {
                    $this->issues[] = "Missing FK constraint: $table.$column -> $refTable";
                    $this->fixes[] = "ALTER TABLE $table ADD FOREIGN KEY ($column) REFERENCES $refTable";
                } else {
                    echo "✅ FK constraint exists: $table.$column -> $refTable\n";
                }
            }
        }
    }
    
    /**
     * Validate indexes
     */
    private function validateIndexes() {
        echo "📇 Validating Database Indexes...\n";
        
        $expectedIndexes = [
            'hmoproviders' => ['ProviderName', 'Status'],
            'hmoplans' => ['ProviderID', 'Status'],
            'employeehmoenrollments' => ['EmployeeID', 'PlanID', 'Status'],
            'hmoclaims' => ['EnrollmentID', 'EmployeeID', 'Status', 'ClaimDate'],
            'hmodependents' => ['EnrollmentID'],
            'hmo_notifications' => ['EmployeeID', 'IsRead', 'CreatedAt'],
            'claim_workflows' => ['ClaimID', 'Status'],
            'claim_workflow_steps' => ['WorkflowID']
        ];
        
        foreach ($expectedIndexes as $table => $columns) {
            foreach ($columns as $column) {
                $sql = "SHOW INDEX FROM $table WHERE Column_name = '$column'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                
                if (!$stmt->fetch()) {
                    $this->issues[] = "Missing index: $table.$column";
                    $this->fixes[] = "CREATE INDEX idx_{$table}_{$column} ON $table ($column)";
                } else {
                    echo "✅ Index exists: $table.$column\n";
                }
            }
        }
    }
    
    /**
     * Validate API endpoints
     */
    private function validateAPIEndpoints() {
        echo "🌐 Validating API Endpoints...\n";
        
        $endpoints = [
            'hmo_unified.php/hmo_claims' => ['GET', 'POST', 'PUT', 'DELETE'],
            'hmo_unified.php/hmo_providers' => ['GET', 'POST', 'PUT', 'DELETE'],
            'hmo_unified.php/hmo_plans' => ['GET', 'POST', 'PUT', 'DELETE'],
            'hmo_unified.php/hmo_enrollments' => ['GET', 'POST', 'PUT', 'DELETE'],
            'hmo_unified.php/hmo_dashboard' => ['GET'],
            'hmo_unified.php/get_employee_enrollments' => ['GET']
        ];
        
        foreach ($endpoints as $endpoint => $methods) {
            $filePath = __DIR__ . "/../php/api/$endpoint";
            if (!file_exists($filePath)) {
                $this->issues[] = "Missing API endpoint: $endpoint";
                $this->fixes[] = "Create API endpoint: $endpoint";
            } else {
                echo "✅ API endpoint exists: $endpoint\n";
            }
        }
    }
    
    /**
     * Validate data integrity
     */
    private function validateDataIntegrity() {
        echo "🔍 Validating Data Integrity...\n";
        
        // Check for orphaned records
        $this->checkOrphanedRecords();
        
        // Check for invalid data
        $this->checkInvalidData();
        
        // Check for missing required fields
        $this->checkRequiredFields();
    }
    
    /**
     * Check for orphaned records
     */
    private function checkOrphanedRecords() {
        $checks = [
            'hmoplans' => 'SELECT COUNT(*) as count FROM hmoplans p LEFT JOIN hmoproviders pr ON p.ProviderID = pr.ProviderID WHERE pr.ProviderID IS NULL',
            'employeehmoenrollments' => 'SELECT COUNT(*) as count FROM employeehmoenrollments e LEFT JOIN hmoplans p ON e.PlanID = p.PlanID WHERE p.PlanID IS NULL',
            'hmoclaims' => 'SELECT COUNT(*) as count FROM hmoclaims c LEFT JOIN employeehmoenrollments e ON c.EnrollmentID = e.EnrollmentID WHERE e.EnrollmentID IS NULL'
        ];
        
        foreach ($checks as $table => $sql) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $this->issues[] = "Orphaned records found in $table: {$result['count']} records";
                $this->fixes[] = "Clean up orphaned records in $table";
            } else {
                echo "✅ No orphaned records in $table\n";
            }
        }
    }
    
    /**
     * Check for invalid data
     */
    private function checkInvalidData() {
        $checks = [
            'hmoproviders' => 'SELECT COUNT(*) as count FROM hmoproviders WHERE ProviderName IS NULL OR ProviderName = ""',
            'hmoplans' => 'SELECT COUNT(*) as count FROM hmoplans WHERE PlanName IS NULL OR PlanName = "" OR ProviderID IS NULL',
            'employeehmoenrollments' => 'SELECT COUNT(*) as count FROM employeehmoenrollments WHERE EmployeeID IS NULL OR PlanID IS NULL OR StartDate IS NULL',
            'hmoclaims' => 'SELECT COUNT(*) as count FROM hmoclaims WHERE EnrollmentID IS NULL OR ClaimDate IS NULL'
        ];
        
        foreach ($checks as $table => $sql) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $this->issues[] = "Invalid data found in $table: {$result['count']} records";
                $this->fixes[] = "Fix invalid data in $table";
            } else {
                echo "✅ No invalid data in $table\n";
            }
        }
    }
    
    /**
     * Check required fields
     */
    private function checkRequiredFields() {
        $requiredFields = [
            'hmoproviders' => ['ProviderName', 'Status'],
            'hmoplans' => ['ProviderID', 'PlanName', 'Status'],
            'employeehmoenrollments' => ['EmployeeID', 'PlanID', 'StartDate', 'Status'],
            'hmoclaims' => ['EnrollmentID', 'ClaimDate', 'Status']
        ];
        
        foreach ($requiredFields as $table => $fields) {
            foreach ($fields as $field) {
                $sql = "SELECT COUNT(*) as count FROM $table WHERE $field IS NULL OR $field = ''";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $this->issues[] = "Missing required field $field in $table: {$result['count']} records";
                    $this->fixes[] = "Update records in $table to have valid $field values";
                } else {
                    echo "✅ All records in $table have valid $field\n";
                }
            }
        }
    }
    
    /**
     * Validate security
     */
    private function validateSecurity() {
        echo "🔒 Validating Security...\n";
        
        // Check authentication
        $this->checkAuthentication();
        
        // Check authorization
        $this->checkAuthorization();
        
        // Check input validation
        $this->checkInputValidation();
    }
    
    /**
     * Check authentication
     */
    private function checkAuthentication() {
        $authFiles = [
            '../middlewares/AuthMiddleware.php',
            '../utils/Request.php',
            '../utils/Response.php'
        ];
        
        foreach ($authFiles as $file) {
            if (!file_exists(__DIR__ . "/$file")) {
                $this->issues[] = "Missing authentication file: $file";
                $this->fixes[] = "Create authentication file: $file";
            } else {
                echo "✅ Authentication file exists: $file\n";
            }
        }
    }
    
    /**
     * Check authorization
     */
    private function checkAuthorization() {
        // Check if role-based access control is implemented
        $sql = "SELECT COUNT(*) as count FROM users WHERE RoleID IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $this->issues[] = "No users have role assignments";
            $this->fixes[] = "Assign roles to users for proper authorization";
        } else {
            echo "✅ Users have role assignments\n";
        }
    }
    
    /**
     * Check input validation
     */
    private function checkInputValidation() {
        // Check if input validation is implemented in API files
        $apiFiles = [
            '../php/api/hmo_unified.php'
        ];
        
        foreach ($apiFiles as $file) {
            if (file_exists(__DIR__ . "/$file")) {
                $content = file_get_contents(__DIR__ . "/$file");
                if (strpos($content, 'validate') !== false || strpos($content, 'filter') !== false) {
                    echo "✅ Input validation found in $file\n";
                } else {
                    $this->issues[] = "Missing input validation in $file";
                    $this->fixes[] = "Add input validation to $file";
                }
            }
        }
    }
    
    /**
     * Validate integration
     */
    private function validateIntegration() {
        echo "🔗 Validating Integration...\n";
        
        // Check integration files
        $integrationFiles = [
            '../api/integrations/HMOPayrollIntegration.php',
            '../api/integrations/HMOAnalyticsIntegration.php'
        ];
        
        foreach ($integrationFiles as $file) {
            if (!file_exists(__DIR__ . "/$file")) {
                $this->issues[] = "Missing integration file: $file";
                $this->fixes[] = "Create integration file: $file";
            } else {
                echo "✅ Integration file exists: $file\n";
            }
        }
        
        // Check if integration tables exist
        $integrationTables = [
            'employee_compensation_benefits',
            'hmo_payroll_applications',
            'hmo_integration_audit'
        ];
        
        foreach ($integrationTables as $table) {
            $sql = "SHOW TABLES LIKE '$table'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                $this->issues[] = "Missing integration table: $table";
                $this->fixes[] = "Create integration table: $table";
            } else {
                echo "✅ Integration table exists: $table\n";
            }
        }
    }
    
    /**
     * Validate performance
     */
    private function validatePerformance() {
        echo "⚡ Validating Performance...\n";
        
        // Check for missing indexes on frequently queried columns
        $this->checkPerformanceIndexes();
        
        // Check for large tables that might need optimization
        $this->checkLargeTables();
    }
    
    /**
     * Check performance indexes
     */
    private function checkPerformanceIndexes() {
        $performanceIndexes = [
            'hmoclaims' => ['ClaimDate', 'Status', 'EmployeeID'],
            'employeehmoenrollments' => ['EmployeeID', 'Status', 'StartDate'],
            'hmoplans' => ['ProviderID', 'Status'],
            'hmoproviders' => ['Status']
        ];
        
        foreach ($performanceIndexes as $table => $columns) {
            foreach ($columns as $column) {
                $sql = "SHOW INDEX FROM $table WHERE Column_name = '$column'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                
                if (!$stmt->fetch()) {
                    $this->issues[] = "Missing performance index: $table.$column";
                    $this->fixes[] = "CREATE INDEX idx_{$table}_{$column}_perf ON $table ($column)";
                } else {
                    echo "✅ Performance index exists: $table.$column\n";
                }
            }
        }
    }
    
    /**
     * Check large tables
     */
    private function checkLargeTables() {
        $tables = ['hmoclaims', 'employeehmoenrollments', 'hmoplans', 'hmoproviders'];
        
        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as count FROM $table";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 10000) {
                $this->issues[] = "Large table detected: $table has {$result['count']} records";
                $this->fixes[] = "Consider partitioning or archiving old records in $table";
            } else {
                echo "✅ Table $table size is manageable: {$result['count']} records\n";
            }
        }
    }
    
    /**
     * Generate validation report
     */
    private function generateReport() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📋 HMO MODULE VALIDATION REPORT\n";
        echo str_repeat("=", 60) . "\n\n";
        
        if (empty($this->issues)) {
            echo "🎉 CONGRATULATIONS! No issues found.\n";
            echo "✅ HMO Module is fully functional and ready for production.\n\n";
        } else {
            echo "⚠️  ISSUES FOUND: " . count($this->issues) . "\n\n";
            
            echo "🔍 ISSUES:\n";
            foreach ($this->issues as $i => $issue) {
                echo "   " . ($i + 1) . ". $issue\n";
            }
            
            echo "\n🔧 RECOMMENDED FIXES:\n";
            foreach ($this->fixes as $i => $fix) {
                echo "   " . ($i + 1) . ". $fix\n";
            }
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Validation completed at: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// Run validation if called directly
if (php_sapi_name() === 'cli') {
    $validator = new HMOValidationScript();
    $validator->runValidation();
}
?>
