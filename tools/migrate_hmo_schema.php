<?php
/**
 * HMO Schema Migration Script
 * Run this once to update your database with enhanced HMO schema
 */

require_once 'php/db_connect.php';

echo "=== HMO Schema Migration ===\n\n";

try {
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/../database/hmo_schema_and_seed.sql';
    
    if (!file_exists($schemaFile)) {
        die("Error: Schema file not found at: $schemaFile\n");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^SET/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for CREATE/ALTER statements
            if (preg_match('/^(CREATE|ALTER)\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $statement, $matches)) {
                echo "✓ {$matches[1]} TABLE {$matches[2]}\n";
            }
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Executed: $executed statements\n";
    echo "Errors: $errors\n";
    
    if ($errors == 0) {
        echo "\n✅ Migration completed successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Optionally run: mysql -u root hospital_hr < database/hmo_top7_seed_compat.sql\n";
        echo "2. Clear your browser cache (Ctrl+Shift+R)\n";
        echo "3. Refresh the HMO module\n";
    } else {
        echo "\n⚠️  Migration completed with some errors. Check the output above.\n";
    }
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

