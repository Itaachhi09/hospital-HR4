<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin']);

try {
    global $pdo;
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // List or single
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM HMOProviders WHERE ProviderID = :id");
            $stmt->execute([':id' => (int)$_GET['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'provider' => $row]);
        } else {
            $stmt = $pdo->query("SELECT * FROM HMOProviders WHERE COALESCE(IsActive,0)=1 ORDER BY ProviderName");
            echo json_encode(['success' => true, 'providers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        exit;
    }

    if ($method === 'POST') {
        $d = api_read_json();
        $sql = "INSERT INTO HMOProviders (ProviderName,CompanyName,ContactPerson,ContactEmail,ContactPhone,Email,PhoneNumber,Address,Website,IsActive)
                VALUES (:ProviderName,:CompanyName,:ContactPerson,:ContactEmail,:ContactPhone,:Email,:PhoneNumber,:Address,:Website,:IsActive)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderName' => trim($d['provider_name'] ?? ''),
            ':CompanyName' => $d['company_name'] ?? null,
            ':ContactPerson' => $d['contact_person'] ?? null,
            ':ContactEmail' => $d['contact_email'] ?? null,
            ':ContactPhone' => $d['contact_phone'] ?? null,
            ':Email' => $d['email'] ?? null,
            ':PhoneNumber' => $d['phone_number'] ?? null,
            ':Address' => $d['address'] ?? null,
            ':Website' => $d['website'] ?? null,
            ':IsActive' => isset($d['is_active']) ? (int)$d['is_active'] : 1,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $d = api_read_json();
        $sql = "UPDATE HMOProviders SET ProviderName=:ProviderName,CompanyName=:CompanyName,ContactPerson=:ContactPerson,ContactEmail=:ContactEmail,ContactPhone=:ContactPhone,Email=:Email,PhoneNumber=:PhoneNumber,Address=:Address,Website=:Website,IsActive=:IsActive WHERE ProviderID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderName' => trim($d['provider_name'] ?? ''),
            ':CompanyName' => $d['company_name'] ?? null,
            ':ContactPerson' => $d['contact_person'] ?? null,
            ':ContactEmail' => $d['contact_email'] ?? null,
            ':ContactPhone' => $d['contact_phone'] ?? null,
            ':Email' => $d['email'] ?? null,
            ':PhoneNumber' => $d['phone_number'] ?? null,
            ':Address' => $d['address'] ?? null,
            ':Website' => $d['website'] ?? null,
            ':IsActive' => isset($d['is_active']) ? (int)$d['is_active'] : 1,
            ':id' => $id,
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $stmt = $pdo->prepare("UPDATE HMOProviders SET IsActive=0 WHERE ProviderID=:id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $e) {
    error_log('API hmo_providers error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>


