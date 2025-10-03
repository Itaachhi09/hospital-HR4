<?php
/**
 * Dev-only endpoint: seed HMO providers & plans from database/hmo_top7_seed.sql
 * Usage: while running locally and logged in as System Admin/HR Admin, visit:
 *   /php/api/seed_hmo.php
 * This will execute the SQL file using PDO. Restricted to localhost and admin roles.
 */
require_once __DIR__ . '/_api_bootstrap.php';

// Restrict to local requests only
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1','::1','localhost'], true)) {
    http_response_code(403);
    echo json_encode(['error'=>'Forbidden: seeding only allowed from localhost']);
    exit;
}

// Require admin role
if (empty($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['System Admin','HR Admin'], true)) {
    http_response_code(403);
    echo json_encode(['error'=>'Forbidden: admin role required']);
    exit;
}

try{
    // Prefer compatibility-only seed if present
    $compat = __DIR__ . '/../database/hmo_top7_seed_compat.sql';
    $fallback = __DIR__ . '/../database/hmo_top7_seed.sql';
    if (file_exists($compat)) {
        $sqlFile = $compat;
    } else {
        $sqlFile = $fallback;
    }
    if (!file_exists($sqlFile)) {
        http_response_code(500);
        echo json_encode(['error'=>'Seed SQL file not found', 'path'=>$sqlFile]);
        exit;
    }
    $sql = file_get_contents($sqlFile);
    if ($sql === false || trim($sql) === '') {
        http_response_code(500);
        echo json_encode(['error'=>'Seed SQL file empty or unreadable']);
        exit;
    }

    // PDO connection is available via _api_bootstrap (as $pdo)
    // Execute as multiple statements
    $pdo->beginTransaction();
    $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    foreach($stmts as $stmt){
        if ($stmt === '') continue;
        try{
            $pdo->exec($stmt);
        }catch(Throwable $e){
            // If a statement fails, roll back and return the error
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error'=>'SQL execution failed','message'=>$e->getMessage(),'stmt'=>substr($stmt,0,200)]);
            exit;
        }
    }
    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'HMO seed executed']);
}catch(Throwable $e){
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'Server error','message'=>$e->getMessage()]);
}

?>
