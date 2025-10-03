<?php
/**
 * Hospital HR Structure Setup Script for XAMPP
 * Executes the hospital-specific organizational structure setup
 */

header('Content-Type: text/html; charset=UTF-8');

// Database configuration for XAMPP
$db_host = 'localhost';
$db_name = 'hr_integrated_db'; // Update to match your database name
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>      
    <title>Hospital HR Structure Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .sql-output { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; font-family: monospace; font-size: 12px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏥 Hospital HR Structure Setup</h1>
        <p>This script will create the comprehensive hospital organizational structure with HR divisions, job roles, and department coordinators.</p>

<?php

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    echo "<div class='success'>✅ Database connection successful!</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>Common fixes:</strong><br>";
    echo "1. Make sure XAMPP MySQL is running<br>";
    echo "2. Update the database name in this script to match your actual database<br>";
    echo "3. Check MySQL credentials (default is user: 'root', password: '')<br>";
    echo "</div>";
    exit;
}

try {
    echo "<h2>📋 Executing Hospital HR Structure Setup</h2>";
    
    $executed = 0;
    $errors = 0;
    $results = [];
    
    // Step 1: Create HR Divisions table
    echo "<h3>Creating HR Divisions table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `hr_divisions` (
        `DivisionID` int(11) NOT NULL AUTO_INCREMENT,
        `DivisionName` varchar(150) NOT NULL,
        `DivisionCode` varchar(20) NOT NULL UNIQUE,
        `Description` text,
        `DivisionHead` int(11) DEFAULT NULL,
        `ParentDivisionID` int(11) DEFAULT NULL,
        `IsActive` tinyint(1) NOT NULL DEFAULT 1,
        `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`DivisionID`),
        KEY `fk_division_head` (`DivisionHead`),
        KEY `fk_parent_division` (`ParentDivisionID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $pdo->exec($sql);
    echo "<div class='success'>✓ HR Divisions table created</div>";
    $executed++;
    
    // Step 2: Create Hospital Job Roles table
    echo "<h3>Creating Hospital Job Roles table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `hospital_job_roles` (
        `JobRoleID` int(11) NOT NULL AUTO_INCREMENT,
        `RoleTitle` varchar(150) NOT NULL,
        `RoleCode` varchar(20) NOT NULL UNIQUE,
        `DivisionID` int(11) DEFAULT NULL,
        `DepartmentID` int(11) DEFAULT NULL,
        `JobLevel` enum('Executive','Senior Management','Middle Management','Supervisory','Officer','Staff','Entry Level') NOT NULL DEFAULT 'Staff',
        `JobFamily` enum('Clinical','Administrative','Support','Technical','Management') NOT NULL DEFAULT 'Administrative',
        `MinimumQualification` text,
        `JobDescription` text,
        `ReportsTo` int(11) DEFAULT NULL,
        `IsActive` tinyint(1) NOT NULL DEFAULT 1,
        `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`JobRoleID`),
        KEY `fk_role_division` (`DivisionID`),
        KEY `fk_role_department` (`DepartmentID`),
        KEY `fk_reports_to` (`ReportsTo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $pdo->exec($sql);
    echo "<div class='success'>✓ Hospital Job Roles table created</div>";
    $executed++;
    
    // Step 3: Create Department HR Coordinators table
    echo "<h3>Creating Department HR Coordinators table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `department_hr_coordinators` (
        `CoordinatorID` int(11) NOT NULL AUTO_INCREMENT,
        `DepartmentID` int(11) NOT NULL,
        `EmployeeID` int(11) NOT NULL,
        `CoordinatorType` enum('Primary','Backup','Interim') NOT NULL DEFAULT 'Primary',
        `EffectiveDate` date NOT NULL,
        `EndDate` date DEFAULT NULL,
        `IsActive` tinyint(1) NOT NULL DEFAULT 1,
        `AssignedBy` int(11) DEFAULT NULL,
        `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`CoordinatorID`),
        KEY `fk_coord_department` (`DepartmentID`),
        KEY `fk_coord_employee` (`EmployeeID`),
        KEY `fk_coord_assigned_by` (`AssignedBy`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $pdo->exec($sql);
    echo "<div class='success'>✓ Department HR Coordinators table created</div>";
    $executed++;
    
    // Step 4: Update OrganizationalStructure table
    echo "<h3>Enhancing OrganizationalStructure table...</h3>";
    $alterStatements = [
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `DepartmentCode` varchar(20) UNIQUE AFTER `DepartmentName`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `DepartmentType` enum('Clinical','Administrative','Support','Ancillary','Executive') DEFAULT 'Administrative' AFTER `DepartmentCode`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `Description` text AFTER `DepartmentType`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `ManagerID` int(11) DEFAULT NULL AFTER `Description`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `Budget` decimal(15,2) DEFAULT NULL AFTER `ManagerID`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `Location` varchar(255) DEFAULT NULL AFTER `Budget`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `IsActive` tinyint(1) NOT NULL DEFAULT 1 AFTER `Location`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `IsActive`",
        "ALTER TABLE `organizationalstructure` ADD COLUMN IF NOT EXISTS `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `CreatedAt`"
    ];
    
    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "<div class='warning'>Warning: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    echo "<div class='success'>✓ OrganizationalStructure table enhanced</div>";
    $executed++;
    
    // Step 5: Update Employees table
    echo "<h3>Enhancing Employees table...</h3>";
    $alterStatements = [
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `JobRoleID` int(11) DEFAULT NULL AFTER `JobTitle`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `EmployeeNumber` varchar(20) UNIQUE AFTER `JobRoleID`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `LicenseNumber` varchar(50) DEFAULT NULL AFTER `EmployeeNumber`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `LicenseExpiryDate` date DEFAULT NULL AFTER `LicenseNumber`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `Specialization` varchar(150) DEFAULT NULL AFTER `LicenseExpiryDate`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `EmploymentType` enum('Regular','Contractual','Probationary','Consultant','Part-time') DEFAULT 'Regular' AFTER `Specialization`",
        "ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `EmploymentStatus` enum('Active','On Leave','Suspended','Terminated','Retired') DEFAULT 'Active' AFTER `EmploymentType`"
    ];
    
    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "<div class='warning'>Warning: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    echo "<div class='success'>✓ Employees table enhanced</div>";
    $executed++;
    
    // Step 6: Insert HR Divisions data
    echo "<h3>Inserting HR Divisions data...</h3>";
    $divisionsData = [
        ['Hospital Administration', 'ADMIN', 'Hospital Director and Chief Administrative Officers', NULL],
        ['Human Resources', 'HR', 'Chief Human Resources Officer and HR Management', NULL],
        ['HR Administration & Compliance', 'HR-ADM', 'HR policy management, compliance, and HRIS administration', NULL],
        ['Recruitment & Staffing', 'HR-REC', 'Talent acquisition, recruitment, and onboarding processes', NULL],
        ['Compensation & Benefits', 'HR-CNB', 'Salary administration, payroll, and employee benefits management', NULL],
        ['Employee Relations & Engagement', 'HR-ENG', 'Employee relations, engagement programs, and conflict resolution', NULL],
        ['Training & Development', 'HR-TRN', 'Learning and development, training programs, and career development', NULL],
        ['Occupational Health & Safety', 'HR-OHS', 'Workplace safety, occupational health, and compliance', NULL]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO `hr_divisions` (`DivisionName`, `DivisionCode`, `Description`, `ParentDivisionID`) VALUES (?, ?, ?, ?)");
    foreach ($divisionsData as $division) {
        $stmt->execute($division);
    }
    
    // Update parent relationships
    $pdo->exec("UPDATE `hr_divisions` SET `ParentDivisionID` = (SELECT `DivisionID` FROM (SELECT * FROM `hr_divisions`) AS t WHERE `DivisionCode` = 'HR') WHERE `DivisionCode` IN ('HR-ADM', 'HR-REC', 'HR-CNB', 'HR-ENG', 'HR-TRN', 'HR-OHS')");
    
    echo "<div class='success'>✓ HR Divisions data inserted</div>";
    $executed++;
    
    // Step 7: Clear and insert organizational structure
    echo "<h3>Setting up Hospital Departments...</h3>";
    $pdo->exec("DELETE FROM `organizationalstructure` WHERE `DepartmentID` > 0");
    
    $departmentsData = [
        [1, 'Hospital Administration', 'ADMIN', 'Executive', 'Hospital Director and executive management', NULL],
        [2, 'Human Resources', 'HR', 'Administrative', 'Human Resources Department', 1],
        [10, 'Medical Services', 'MED', 'Clinical', 'Clinical operations and medical services', 1],
        [11, 'Nursing Services', 'NURS', 'Clinical', 'Nursing operations and patient care', 10],
        [12, 'Radiology Department', 'RAD', 'Clinical', 'Diagnostic imaging and radiology services', 10],
        [13, 'Laboratory Services', 'LAB', 'Clinical', 'Clinical laboratory and pathology services', 10],
        [14, 'Pharmacy Department', 'PHARM', 'Clinical', 'Pharmaceutical services and medication management', 10],
        [15, 'Emergency Department', 'ER', 'Clinical', 'Emergency and trauma care services', 10],
        [16, 'Surgery Department', 'SURG', 'Clinical', 'Surgical services and operating room management', 10],
        [20, 'Finance Department', 'FIN', 'Administrative', 'Financial management and accounting', 1],
        [21, 'Information Technology', 'IT', 'Administrative', 'IT services and system management', 1],
        [22, 'Legal Affairs', 'LEGAL', 'Administrative', 'Legal compliance and affairs', 1],
        [30, 'Facilities Management', 'FAC', 'Support', 'Building maintenance and facilities', 1],
        [31, 'Security Department', 'SEC', 'Support', 'Hospital security and safety', 30],
        [32, 'Housekeeping Services', 'HOUSE', 'Support', 'Cleaning and sanitation services', 30],
        [33, 'Food Services', 'FOOD', 'Support', 'Dietary and food service operations', 30],
        [50, 'HR Administration', 'HR-ADM', 'Administrative', 'HR policy, compliance, and administration', 2],
        [51, 'Recruitment & Staffing', 'HR-REC', 'Administrative', 'Talent acquisition and staffing', 2],
        [52, 'Compensation & Benefits', 'HR-CNB', 'Administrative', 'Payroll, compensation, and benefits', 2],
        [53, 'Employee Relations', 'HR-ENG', 'Administrative', 'Employee relations and engagement', 2],
        [54, 'Training & Development', 'HR-TRN', 'Administrative', 'Learning and development programs', 2],
        [55, 'Occupational Health & Safety', 'HR-OHS', 'Administrative', 'Workplace health and safety', 2]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `organizationalstructure` (`DepartmentID`, `DepartmentName`, `DepartmentCode`, `DepartmentType`, `Description`, `ParentDepartmentID`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($departmentsData as $dept) {
        $stmt->execute($dept);
    }
    
    echo "<div class='success'>✓ Hospital Departments created</div>";
    $executed++;
    
    // Step 8: Add new roles to roles table
    echo "<h3>Adding hospital-specific roles...</h3>";
    $newRoles = [
        'Hospital Director',
        'HR Director', 
        'HR Manager',
        'HR Officer',
        'HR Coordinator',
        'Department Manager',
        'Medical Staff',
        'Nursing Staff',
        'Support Staff'
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO `roles` (`RoleName`) VALUES (?)");
    foreach ($newRoles as $role) {
        $stmt->execute([$role]);
    }
    
    echo "<div class='success'>✓ Hospital-specific roles added</div>";
    $executed++;
    
    // Final verification
    echo "<h2>🔍 Final Verification</h2>";
    $verificationQueries = [
        "SELECT 'HR Divisions' as Entity, COUNT(*) as Count FROM hr_divisions WHERE IsActive = 1",
        "SELECT 'Departments' as Entity, COUNT(*) as Count FROM organizationalstructure WHERE IsActive = 1",
        "SELECT 'System Roles' as Entity, COUNT(*) as Count FROM roles"
    ];
    
    foreach ($verificationQueries as $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div class='success'>✓ {$result['Entity']}: {$result['Count']} records</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Verification failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>🎉 Hospital HR Structure Setup Complete!</h3>";
    echo "<p>Total SQL operations executed: $executed</p>";
    echo "<p>The following hospital-specific components have been created:</p>";
    echo "<ul>";
    echo "<li><strong>HR Divisions:</strong> Hospital Administration, Human Resources with sub-divisions</li>";
    echo "<li><strong>Department Structure:</strong> Executive, Clinical, Administrative, and Support departments</li>";
    echo "<li><strong>Enhanced Tables:</strong> Extended organizational structure and employee tables</li>";
    echo "<li><strong>Access Control:</strong> Hospital-specific roles and permissions</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🚀 Next Steps</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li><strong>Access Admin Panel:</strong> Navigate to your admin landing page</li>";
    echo "<li><strong>HR Core:</strong> Click on 'HR Core' → 'Organizational Structure'</li>";
    echo "<li><strong>View Structure:</strong> Explore the new hospital organizational hierarchy</li>";
    echo "<li><strong>Assign Employees:</strong> Start assigning employees to departments and roles</li>";
    echo "<li><strong>Set Coordinators:</strong> Assign HR coordinators to each department</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

?>

        <div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;'>
            <p><strong>🛠️ System Administration</strong></p>
            <p>For ongoing management of the hospital HR structure, use the admin panel's HR Core section. 
               The organizational structure module now supports comprehensive hospital management with 
               Philippine healthcare standards compliance.</p>
        </div>
    </div>
</body>
</html>
