<?php
// Link a created item (grade, band, workflow, simulation) to a Compensation Plan.
// Creates the mapping table if it doesn't exist.

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }

try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) throw new Exception('DB connection failed');

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS CompensationPlanItems (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        PlanID INT NOT NULL,
        ItemType VARCHAR(50) NOT NULL,
        ItemID INT NOT NULL,
        Metadata JSON NULL,
        CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_plan_item (PlanID, ItemType, ItemID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

    $planId = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;
    $itemType = isset($input['item_type']) ? trim($input['item_type']) : '';
    $itemId = isset($input['item_id']) ? (int)$input['item_id'] : 0;
    $metadata = isset($input['metadata']) ? json_encode($input['metadata']) : null;

    if ($planId <= 0 || $itemId <= 0 || $itemType === '') { http_response_code(400); echo json_encode(['error'=>'plan_id, item_type, item_id are required']); exit; }

    $sql = "INSERT INTO CompensationPlanItems (PlanID, ItemType, ItemID, Metadata) VALUES (:plan, :type, :id, :meta)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':plan', $planId, PDO::PARAM_INT);
    $stmt->bindValue(':type', $itemType, PDO::PARAM_STR);
    $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
    if ($metadata === null) { $stmt->bindValue(':meta', null, PDO::PARAM_NULL); } else { $stmt->bindValue(':meta', $metadata, PDO::PARAM_STR); }
    $stmt->execute();

    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    error_log('link_compensation_plan_item error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
}
exit;
?>
