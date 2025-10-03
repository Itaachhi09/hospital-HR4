<?php
/**
 * HMO Module Setup Script
 * This script creates the necessary database tables and sample data for the HMO module
 */

require_once 'php/db_connect.php';

if (!isset($pdo)) {
    die('Database connection failed. Please check your database configuration.');
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>HMO Module Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .sql-output { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>HMO Module Setup</h1>
        <p>This script will create the necessary database tables and sample data for the HMO & Benefits module.</p>";

try {
    // Read and execute the HMO tables SQL
    $sqlFile = 'create_hmo_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    echo "<h2>Creating HMO Database Tables</h2>";
    echo "<div class='info'>Reading SQL from: $sqlFile</div>";

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty(trim($statement)) || strpos(trim($statement), '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($statement);
            
            // Extract table name for better reporting
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Created table: " . $matches[1] . "</div>";
            } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Inserted sample data into: " . $matches[1] . "</div>";
            } else {
                echo "<div class='success'>✓ Executed SQL statement successfully</div>";
            }
            
            $successCount++;
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='sql-output'>" . htmlspecialchars(substr($statement, 0, 200)) . "...</div>";
            $errorCount++;
        }
    }

    echo "<h2>Setup Summary</h2>";
    echo "<div class='info'>";
    echo "<strong>Total SQL statements executed:</strong> " . ($successCount + $errorCount) . "<br>";
    echo "<strong>Successful:</strong> $successCount<br>";
    echo "<strong>Errors:</strong> $errorCount<br>";
    echo "</div>";

    if ($errorCount === 0) {
        echo "<div class='success'>";
        echo "<h3>🎉 Setup Complete!</h3>";
        echo "<p>The HMO module has been successfully set up with the following components:</p>";
        echo "<ul>";
        echo "<li><strong>HMO Providers Table:</strong> Stores HMO provider information</li>";
        echo "<li><strong>HMO Plans Table:</strong> Stores HMO plan details and coverage information</li>";
        echo "<li><strong>Employee HMO Enrollments Table:</strong> Tracks employee HMO enrollments</li>";
        echo "<li><strong>HMO Claims Table:</strong> Manages HMO claim submissions and approvals</li>";
        echo "<li><strong>HMO Notifications Table:</strong> Handles HMO-related notifications</li>";
        echo "<li><strong>Sample Data:</strong> Pre-loaded with Philippine HMO providers and plans</li>";
        echo "</ul>";
        echo "</div>";

        // Verify table creation
        echo "<h2>Table Verification</h2>";
        $tables = ['HMOProviders', 'HMOPlans', 'EmployeeHMOEnrollments', 'HMOClaims', 'hmo_notifications'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<div class='success'>✓ Table '$table' exists with $count records</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>✗ Table '$table' verification failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        echo "<h2>Next Steps</h2>";
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li><strong>Access Admin Panel:</strong> Navigate to your admin landing page</li>";
        echo "<li><strong>HMO Management:</strong> Click on 'HMO & Benefits' in the sidebar</li>";
        echo "<li><strong>Manage Providers:</strong> Add, edit, or remove HMO providers</li>";
        echo "<li><strong>Manage Plans:</strong> Create and configure HMO benefit plans</li>";
        echo "<li><strong>Employee Enrollments:</strong> Enroll employees in HMO plans</li>";
        echo "<li><strong>Claims Processing:</strong> Review and approve HMO claims</li>";
        echo "</ol>";
        echo "</div>";

        echo "<h2>API Endpoints Available</h2>";
        echo "<div class='info'>";
        echo "<ul>";
        echo "<li><code>GET /php/api/get_hmo_providers.php</code> - Get all HMO providers</li>";
        echo "<li><code>GET /php/api/get_hmo_plans.php</code> - Get all HMO plans</li>";
        echo "<li><code>GET /php/api/get_hmo_enrollments.php</code> - Get employee enrollments</li>";
        echo "<li><code>GET /php/api/get_employee_hmo_benefits.php</code> - Get employee's HMO benefits</li>";
        echo "<li><code>POST /php/api/save_hmo_provider.php</code> - Create/update HMO provider</li>";
        echo "<li><code>POST /php/api/save_hmo_plan.php</code> - Create/update HMO plan</li>";
        echo "<li><code>POST /php/api/save_hmo_enrollment.php</code> - Create HMO enrollment</li>";
        echo "<li><code>POST /php/api/submit_hmo_claim.php</code> - Submit HMO claim</li>";
        echo "</ul>";
        echo "</div>";

    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Setup Incomplete</h3>";
        echo "<p>Some errors occurred during setup. Please review the errors above and ensure:</p>";
        echo "<ul>";
        echo "<li>Your database user has sufficient privileges</li>";
        echo "<li>The required tables don't already exist with different schemas</li>";
        echo "<li>Your database supports the required features (foreign keys, etc.)</li>";
        echo "</ul>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>Fatal Error</h3>";
    echo "<p>Setup failed with error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "
        <h2>Troubleshooting</h2>
        <div class='info'>
            <p>If you encounter issues:</p>
            <ul>
                <li>Check that your database connection is working</li>
                <li>Ensure your database user has CREATE, INSERT, and ALTER privileges</li>
                <li>Verify that the <code>employees</code> and <code>users</code> tables exist</li>
                <li>Check the PHP error log for detailed error messages</li>
            </ul>
        </div>
        
        <div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;'>
            <p><strong>Need Help?</strong></p>
            <p>If you need assistance with the HMO module setup or configuration, please check the documentation or contact your system administrator.</p>
        </div>
    </div>
</body>
</html>";
?>
