<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin']);

try { global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT hc.*, e.FirstName, e.LastName, hp.PlanName FROM HMOClaims hc JOIN EmployeeHMOEnrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN Employees e ON ehe.EmployeeID=e.EmployeeID JOIN HMOPlans hp ON ehe.PlanID=hp.PlanID WHERE ClaimID=:id");
            $stmt->execute([':id'=>(int)$_GET['id']]); echo json_encode(['success'=>true,'claim'=>$stmt->fetch(PDO::FETCH_ASSOC)]); exit;
        }
        $stmt = $pdo->query("SELECT hc.*, e.FirstName, e.LastName, hp.PlanName FROM HMOClaims hc JOIN EmployeeHMOEnrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN Employees e ON ehe.EmployeeID=e.EmployeeID JOIN HMOPlans hp ON ehe.PlanID=hp.PlanID ORDER BY hc.SubmittedDate DESC");
        echo json_encode(['success'=>true,'claims'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
    }

    if ($method === 'POST') {
        $d = api_read_json();
        $sql = "INSERT INTO HMOClaims (EnrollmentID, ClaimNumber, ClaimType, ProviderName, Description, Amount, ClaimDate, ReceiptPath) VALUES (:EnrollmentID, :ClaimNumber, :ClaimType, :ProviderName, :Description, :Amount, :ClaimDate, :ReceiptPath)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':EnrollmentID' => (int)($d['enrollment_id'] ?? 0),
            ':ClaimNumber' => 'HMO-'.date('Ymd').'-'.str_pad((string)rand(1,9999),4,'0',STR_PAD_LEFT),
            ':ClaimType' => $d['claim_type'] ?? 'General',
            ':ProviderName' => $d['provider_name'] ?? null,
            ':Description' => $d['description'] ?? '',
            ':Amount' => (float)($d['amount'] ?? 0),
            ':ClaimDate' => $d['claim_date'] ?? date('Y-m-d'),
            ':ReceiptPath' => $d['receipt_path'] ?? null,
        ]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $d = api_read_json();
        $sql = "UPDATE HMOClaims SET Status=:Status, ApprovedDate=:ApprovedDate, Comments=:Comments, ApprovedBy=:ApprovedBy WHERE ClaimID=:id";
        $status = $d['status'] ?? 'Under Review';
        $approvedDate = in_array($status, ['Approved','Paid']) ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':Status' => $status,
            ':ApprovedDate' => $approvedDate,
            ':Comments' => $d['comments'] ?? null,
            ':ApprovedBy' => $_SESSION['user_id'] ?? null,
            ':id' => $id,
        ]);
        echo json_encode(['success'=>true]); exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $stmt = $pdo->prepare("DELETE FROM HMOClaims WHERE ClaimID=:id");
        $stmt->execute([':id'=>$id]); echo json_encode(['success'=>true]); exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) { error_log('API hmo_claims error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


