<?php
require_once __DIR__ . '/_api_bootstrap.php';
// Providers: Admins can manage. Employees may list active providers (read-only).
api_require_auth();

try {
    global $pdo;
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // List or single
        if (isset($_GET['id'])) {
            // Map actual DB columns to API expected keys (ContactNumber, Email, Status)
            $stmt = $pdo->prepare("SELECT ProviderID, ProviderName, Description, ContactPerson, COALESCE(ContactPhone, PhoneNumber) AS ContactNumber, COALESCE(ContactEmail, Email) AS Email, CASE WHEN COALESCE(IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status FROM hmoproviders WHERE ProviderID = :id");
            $stmt->execute([':id' => (int)$_GET['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'provider' => $row]);
        } else {
            // Non-admins only see active providers
            $role = $_SESSION['role_name'] ?? '';
            if (in_array($role, ['System Admin','HR Admin'], true)) {
                $stmt = $pdo->query("SELECT ProviderID, ProviderName, Description, ContactPerson, COALESCE(ContactPhone, PhoneNumber) AS ContactNumber, COALESCE(ContactEmail, Email) AS Email, CASE WHEN COALESCE(IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status FROM hmoproviders ORDER BY ProviderName");
            } else {
                $stmt = $pdo->query("SELECT ProviderID, ProviderName, Description, ContactPerson, COALESCE(ContactPhone, PhoneNumber) AS ContactNumber, COALESCE(ContactEmail, Email) AS Email, CASE WHEN COALESCE(IsActive,1)=1 THEN 'Active' ELSE 'Inactive' END AS Status FROM hmoproviders WHERE COALESCE(IsActive,1)=1 ORDER BY ProviderName");
            }
            echo json_encode(['success' => true, 'providers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        exit;
    }

    if ($method === 'POST') {
        // Only admins can create
        api_require_auth(['System Admin','HR Admin']);
        $d = api_read_json();
        // Use actual DB columns: ContactPhone, ContactEmail, IsActive
    $sql = "INSERT INTO hmoproviders (ProviderName, Description, ContactPerson, ContactPhone, ContactEmail, IsActive) VALUES (:ProviderName,:Description,:ContactPerson,:ContactPhone,:ContactEmail,:IsActive)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderName' => trim($d['provider_name'] ?? ''),
            ':Description' => $d['description'] ?? null,
            ':ContactPerson' => $d['contact_person'] ?? null,
            ':ContactPhone' => $d['contact_number'] ?? $d['contact_phone'] ?? null,
            ':ContactEmail' => $d['email'] ?? $d['contact_email'] ?? null,
            ':IsActive' => (isset($d['status']) && strtolower($d['status'])==='inactive') ? 0 : 1,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        // Only admins can update
        api_require_auth(['System Admin','HR Admin']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $d = api_read_json();
    $sql = "UPDATE hmoproviders SET ProviderName=:ProviderName, Description=:Description, ContactPerson=:ContactPerson, ContactPhone=:ContactPhone, ContactEmail=:ContactEmail, IsActive=:IsActive WHERE ProviderID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ProviderName' => trim($d['provider_name'] ?? ''),
            ':Description' => $d['description'] ?? null,
            ':ContactPerson' => $d['contact_person'] ?? null,
            ':ContactPhone' => $d['contact_number'] ?? $d['contact_phone'] ?? null,
            ':ContactEmail' => $d['email'] ?? $d['contact_email'] ?? null,
            ':IsActive' => (isset($d['status']) && strtolower($d['status'])==='inactive') ? 0 : 1,
            ':id' => $id,
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        // Only admins can delete
        api_require_auth(['System Admin','HR Admin']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if provider is referenced in other tables
            $check = $pdo->prepare("SELECT COUNT(*) FROM hmoplans WHERE ProviderID = :id");
            $check->execute([':id' => $id]);
            if ($check->fetchColumn() > 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete provider: It has associated plans']);
                exit;
            }
            
            // If no references exist, perform the actual delete
            $stmt = $pdo->prepare("DELETE FROM hmoproviders WHERE ProviderID = :id");
            $stmt->execute([':id' => $id]);
            
            // Commit the transaction
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Delete provider error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Could not delete provider. It may be referenced by other records.']);
        }
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


