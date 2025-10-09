<?php
require_once __DIR__ . '/../api/config.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM employees");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'columns' => $cols], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
