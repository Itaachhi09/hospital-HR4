<?php
/**
 * Hospital HR Structure Setup Script
 * Executes the hospital-specific organizational structure setup
 */

header('Content-Type: text/html; charset=UTF-8');

require_once 'php/db_connect.php';

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

if (!isset($pdo)) {
    echo "<div class='error'>❌ Database connection failed. Please check your database configuration.</div>";
    exit;
}

try {
    echo "<h2>📋 Executing Hospital HR Structure Setup</h2>";
    echo "<div class='info'>Reading SQL script: hospital_hr_structure_update.sql</div>";
    
    $sqlFile = 'hospital_hr_structure_update.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read SQL file: $sqlFile");
    }
    
    // Remove comments and split into individual statements
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    $statements = explode(';', $sql);
    
    $executed = 0;
    $errors = 0;
    $results = [];
    
    echo "<h2>🔧 Executing SQL Statements</h2>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            // Handle special cases for dynamic SQL
            if (strpos($statement, 'SET @fk_exists') !== false || 
                strpos($statement, 'SET @sql') !== false ||
                strpos($statement, 'PREPARE stmt') !== false ||
                strpos($statement, 'EXECUTE stmt') !== false ||
                strpos($statement, 'DEALLOCATE PREPARE') !== false) {
                $pdo->exec($statement);
                $executed++;
                continue;
            }
            
            $stmt = $pdo->prepare($statement);
            $stmt->execute();
            $executed++;
            
            // Capture results for verification queries
            if (strpos($statement, 'SELECT ') === 0) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($result)) {
                    $results[] = $result;
                }
            }
            
        } catch (PDOException $e) {
            $errors++;
            echo "<div class='warning'>⚠️ Statement warning: " . htmlspecialchars($e->getMessage()) . "</div>";
            if (strpos($e->getMessage(), 'Duplicate') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                echo "<div class='sql-output'>" . htmlspecialchars(substr($statement, 0, 200)) . "...</div>";
            }
        }
    }
    
    echo "<h2>📊 Setup Summary</h2>";
    echo "<div class='info'>";
    echo "<strong>SQL statements executed:</strong> $executed<br>";
    echo "<strong>Warnings/Skipped:</strong> $errors<br>";
    echo "</div>";
    
    // Display verification results
    if (!empty($results)) {
        echo "<h2>✅ Verification Results</h2>";
        foreach ($results as $result) {
            if (!empty($result)) {
                echo "<div class='success'>";
                foreach ($result as $row) {
                    if (isset($row['Status']) && isset($row['Count'])) {
                        echo "<strong>{$row['Status']}:</strong> {$row['Count']}<br>";
                    } elseif (isset($row['DivisionName'])) {
                        echo "<strong>{$row['DivisionName']} ({$row['DivisionCode']}):</strong> {$row['RoleCount']} roles";
                        if ($row['ParentDivision']) echo " (under {$row['ParentDivision']})";
                        echo "<br>";
                    }
                }
                echo "</div>";
            }
        }
    }
    
    // Additional verification queries
    echo "<h2>🔍 Final Verification</h2>";
    
    $verificationQueries = [
        "SELECT 'HR Divisions' as Entity, COUNT(*) as Count FROM hr_divisions WHERE IsActive = 1",
        "SELECT 'Job Roles' as Entity, COUNT(*) as Count FROM hospital_job_roles WHERE IsActive = 1", 
        "SELECT 'Departments' as Entity, COUNT(*) as Count FROM organizationalstructure WHERE IsActive = 1",
        "SELECT 'System Roles' as Entity, COUNT(*) as Count FROM roles"
    ];
    
    foreach ($verificationQueries as $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div class='success'>✓ {$result['Entity']}: {$result['Count']} records</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Verification failed for {$query}: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>🎉 Hospital HR Structure Setup Complete!</h3>";
    echo "<p>The following hospital-specific components have been created:</p>";
    echo "<ul>";
    echo "<li><strong>HR Divisions:</strong> Hospital Administration, Human Resources with sub-divisions</li>";
    echo "<li><strong>Department Structure:</strong> Executive, Clinical, Administrative, and Support departments</li>";
    echo "<li><strong>Job Roles:</strong> Comprehensive hospital job roles from Executive to Staff level</li>";
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
    
    echo "<h2>🔗 Available Features</h2>";
    echo "<div class='info'>";
    echo "<ul>";
    echo "<li><strong>Hospital Hierarchy:</strong> View complete departmental structure</li>";
    echo "<li><strong>HR Divisions:</strong> Manage HR functional areas</li>";
    echo "<li><strong>Job Roles:</strong> Define and manage hospital-specific positions</li>";
    echo "<li><strong>HR Coordinators:</strong> Assign coordinators to departments</li>";
    echo "<li><strong>Role-based Access:</strong> Different permissions for different user types</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<h2>🔧 Troubleshooting</h2>";
    echo "<div class='warning'>";
    echo "<ul>";
    echo "<li>Check that your database connection is working</li>";
    echo "<li>Ensure your database user has CREATE, INSERT, and ALTER privileges</li>";
    echo "<li>Verify that the required tables (employees, organizationalstructure) exist</li>";
    echo "<li>Check the PHP error log for detailed error messages</li>";
    echo "</ul>";
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
