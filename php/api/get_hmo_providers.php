<?php
/**
 * API Endpoint: Get HMO Providers
 * Retrieves all HMO providers with their details
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db_connect.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

try {
    // Ensure table exists; if missing, return empty providers gracefully
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'HMOProviders'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => true, 'providers' => []]);
        exit;
    }

    $sql = "SELECT 
            ProviderID as id,
            ProviderName as name,
            CompanyName as company_name,
            ContactPerson as contact_person,
            ContactEmail as contact_email,
            ContactPhone as contact_phone,
            PhoneNumber as phone_number,
            Email as email,
            Address as address,
            Website as website,
            Logo as logo,
            Description as description,
            EstablishedYear as established_year,
            AccreditationNumber as accreditation_number,
            ServiceAreas as service_areas,
            IsActive as is_active,
            CreatedAt as created_at,
            UpdatedAt as updated_at
        FROM HMOProviders 
        WHERE COALESCE(IsActive, 0) = 1
        ORDER BY ProviderName ASC";

    $stmt = $pdo->query($sql);
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'providers' => $providers
    ]);

} catch (PDOException $e) {
    error_log("Get HMO Providers Error: " . $e->getMessage());
    // Return empty list gracefully to avoid breaking UI
    echo json_encode(['success' => true, 'providers' => []]);
} catch (Exception $e) {
    error_log("Get HMO Providers Error: " . $e->getMessage());
    echo json_encode(['success' => true, 'providers' => []]);
}
exit;
?>
