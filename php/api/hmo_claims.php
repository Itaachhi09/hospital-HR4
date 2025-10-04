<?php
require_once __DIR__ . '/_api_bootstrap.php';
// Claims: Admins manage all claims. Employees can submit and view their own claims (based on Enrollment -> Employee)
api_require_auth();

try { global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // support history mode with filters: ?mode=history&employee_id=&status=&from=&to=
        if (isset($_GET['mode']) && $_GET['mode']==='history') {
            $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $from = isset($_GET['from']) ? trim($_GET['from']) : '';
            $to = isset($_GET['to']) ? trim($_GET['to']) : '';
            $params = [];
            // Use actual table names and explicit columns
            $sql = "SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.ClaimNumber, hc.ClaimType, hc.ProviderName AS ProviderName, hc.Description, hc.Amount, hc.ClaimDate, hc.SubmittedDate, hc.ApprovedDate, hc.Status AS ClaimStatus, hc.Comments, hc.ApprovedBy, hc.CreatedAt, hc.UpdatedAt, ehe.EmployeeID AS EnEmployeeID, e.FirstName, e.LastName, hp.PlanName FROM hmoclaims hc JOIN employeehmoenrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID WHERE 1=1";
            if ($employeeId>0) { $sql .= " AND ehe.EmployeeID=:emp"; $params[':emp']=$employeeId; }
            if ($status!=='') { $sql .= " AND hc.ClaimStatus=:status"; $params[':status']=$status; }
            if ($from!=='') { $sql .= " AND hc.ClaimDate>=:from"; $params[':from']=$from; }
            if ($to!=='') { $sql .= " AND hc.ClaimDate<=:to"; $params[':to']=$to; }
            $sql .= " ORDER BY hc.ClaimDate DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // normalize attachments
            foreach ($rows as &$r) {
                $att = $r['Attachments'] ?? null;
                if ($att === null || $att === '') $r['Attachments']=[]; else {
                    $dec = json_decode($att,true); if (is_array($dec)) $r['Attachments']=$dec; else $r['Attachments']=[];
                }
            }
            echo json_encode(['success'=>true,'claims'=>$rows]); exit;
        }

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.ClaimNumber, hc.ClaimType, hc.ProviderName AS ProviderName, hc.Description, hc.Amount, hc.ClaimDate, hc.SubmittedDate, hc.ApprovedDate, hc.Status AS ClaimStatus, hc.Comments, hc.ApprovedBy, hc.CreatedAt, hc.UpdatedAt, ehe.EmployeeID AS EnEmployeeID, e.FirstName, e.LastName, hp.PlanName FROM hmoclaims hc JOIN employeehmoenrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID WHERE hc.ClaimID=:id");
            $stmt->execute([':id'=>$id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'System Admin' && $role !== 'HR Admin') {
                $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
                if (!$row || (int)$row['EmployeeID'] !== $userEmpId) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
            }
            // normalize attachments
            $att = $row['Attachments'] ?? null;
            if ($att === null || $att === '') $row['Attachments']=[]; else { $dec = json_decode($att,true); if (is_array($dec)) $row['Attachments']=$dec; else $row['Attachments']=[]; }
            echo json_encode(['success'=>true,'claim'=>$row]); exit;
        }
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'System Admin' || $role === 'HR Admin') {
            $stmt = $pdo->query("SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.ClaimNumber, hc.ClaimType, hc.ProviderName AS ProviderName, hc.Description, hc.Amount, hc.ClaimDate, hc.SubmittedDate, hc.ApprovedDate, hc.Status AS ClaimStatus, hc.Comments, hc.ApprovedBy, hc.CreatedAt, hc.UpdatedAt, ehe.EmployeeID AS EnEmployeeID, e.FirstName, e.LastName, hp.PlanName FROM hmoclaims hc JOIN employeehmoenrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID ORDER BY hc.SubmittedDate DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.ClaimNumber, hc.ClaimType, hc.ProviderName AS ProviderName, hc.Description, hc.Amount, hc.ClaimDate, hc.SubmittedDate, hc.ApprovedDate, hc.Status AS ClaimStatus, hc.Comments, hc.ApprovedBy, hc.CreatedAt, hc.UpdatedAt, ehe.EmployeeID AS EnEmployeeID, e.FirstName, e.LastName, hp.PlanName FROM hmoclaims hc JOIN employeehmoenrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID WHERE ehe.EmployeeID=:emp ORDER BY hc.SubmittedDate DESC");
            $stmt->execute([':emp'=>$userEmpId]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // normalize attachments per row
        foreach ($rows as &$r) {
            $att = $r['Attachments'] ?? null;
            if ($att === null || $att === '') $r['Attachments']=[]; else { $dec = json_decode($att,true); if (is_array($dec)) $r['Attachments']=$dec; else $r['Attachments']=[]; }
        }
        echo json_encode(['success'=>true,'claims'=>$rows]); exit;
    }

    if ($method === 'POST') {
        // Accept multipart/form-data for file uploads, or JSON body
        $d = [];
        $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'],'multipart/form-data')!==false;
        if ($isMultipart) {
            // fields in $_POST
            $d = $_POST;
        } else {
            $d = api_read_json();
        }
        // If non-admin, ensure enrollment belongs to the employee in session
        $enrollmentId = (int)($d['enrollment_id'] ?? 0);
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'System Admin' && $role !== 'HR Admin') {
            $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
            $check = $pdo->prepare("SELECT EnrollmentID FROM employeehmoenrollments WHERE EnrollmentID=:id AND EmployeeID=:emp");
            $check->execute([':id'=>$enrollmentId, ':emp'=>$userEmpId]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
        }

    $sql = "INSERT INTO hmoclaims (EnrollmentID, ClaimDate, HospitalClinic, Diagnosis, Amount, Status, Comments, Attachments) VALUES (:EnrollmentID,:ClaimDate,:HospitalClinic,:Diagnosis,:ClaimAmount,:ClaimStatus,:Remarks,:Attachments)";
        $stmt = $pdo->prepare($sql);
        $attachmentsJson = null;
        // handle uploaded files if multipart
        if ($isMultipart && !empty($_FILES)) {
            // temporarily set empty attachments; we'll update after insert
            $attachmentsJson = json_encode([]);
        }
        $stmt->execute([
            ':EnrollmentID' => $enrollmentId,
            ':ClaimDate' => $d['claim_date'] ?? date('Y-m-d'),
            ':HospitalClinic' => $d['hospital_clinic'] ?? null,
            ':Diagnosis' => $d['diagnosis'] ?? null,
            ':ClaimAmount' => (float)($d['claim_amount'] ?? 0),
            ':ClaimStatus' => $d['claim_status'] ?? 'Pending',
            ':Remarks' => $d['remarks'] ?? null,
            ':Attachments' => $attachmentsJson,
        ]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'],'multipart/form-data')!==false;
        if ($isMultipart) $d = $_POST; else $d = api_read_json();
        // Permission: employees may update their own claim remarks or cancel; status change reserved for admins
        $role = $_SESSION['role_name'] ?? '';
    $pre = $pdo->prepare("SELECT hc.ClaimID, hc.EnrollmentID, hc.EmployeeID, hc.Amount, hc.Status AS ClaimStatus, hc.Attachments, ehe.EmployeeID FROM hmoclaims hc JOIN employeehmoenrollments ehe ON hc.EnrollmentID=ehe.EnrollmentID WHERE hc.ClaimID=:id");
        $pre->execute([':id'=>$id]); $rec = $pre->fetch(PDO::FETCH_ASSOC);
        if (!$rec) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
        if ($role !== 'System Admin' && $role !== 'HR Admin') {
            $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
            if ((int)$rec['EmployeeID'] !== $userEmpId) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
            // Allow employee to update remarks only
            $sql = "UPDATE hmoclaims SET Comments=:Remarks WHERE ClaimID=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':Remarks'=>$d['remarks'] ?? $rec['Remarks'], ':id'=>$id]);
            echo json_encode(['success'=>true]); exit;
        }

        // Admin flow: allow status update
    $sql = "UPDATE hmoclaims SET Status=:ClaimStatus, Comments=:Remarks WHERE ClaimID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ClaimStatus' => $d['claim_status'] ?? $rec['ClaimStatus'],
            ':Remarks' => $d['remarks'] ?? $rec['Remarks'],
            ':id' => $id,
        ]);
        // handle file uploads for attachments when multipart
        if ($isMultipart && !empty($_FILES)) {
            $uploadBase = __DIR__ . '/../../uploads/hmo_claims';
            @mkdir($uploadBase, 0755, true);
            $claimDir = $uploadBase . '/' . $id;
            @mkdir($claimDir, 0755, true);
            // fetch existing attachments
            $existing = [];
            if (!empty($rec['Attachments'])) { $dec = json_decode($rec['Attachments'], true); if (is_array($dec)) $existing = $dec; }
            foreach ($_FILES as $f) {
                if (is_array($f['name'])) {
                    for ($i=0;$i<count($f['name']);$i++) {
                        if ($f['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $tmp = $f['tmp_name'][$i]; $name = basename($f['name'][$i]);
                        $target = $claimDir . '/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $name);
                        if (move_uploaded_file($tmp, $target)) { $existing[] = str_replace('\\','/',$target); }
                    }
                } else {
                    if ($f['error'] !== UPLOAD_ERR_OK) continue;
                    $tmp = $f['tmp_name']; $name = basename($f['name']);
                    $target = $claimDir . '/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $name);
                    if (move_uploaded_file($tmp, $target)) { $existing[] = str_replace('\\','/',$target); }
                }
            }
            $attJson = json_encode(array_values($existing));
            $up = $pdo->prepare("UPDATE hmoclaims SET Attachments=:att WHERE ClaimID=:id");
            $up->execute([':att'=>$attJson, ':id'=>$id]);
        }
        echo json_encode(['success'=>true]); exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }

        // Only admins can delete
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'System Admin' && $role !== 'HR Admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if claim exists and get its status
            $check = $pdo->prepare("SELECT Status, ApprovedDate FROM hmoclaims WHERE ClaimID = :id");
            $check->execute([':id' => $id]);
            $claim = $check->fetch(PDO::FETCH_ASSOC);
            
            if (!$claim) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Claim not found']);
                exit;
            }
            
            // Don't allow deletion of approved claims
            if ($claim['Status'] === 'Approved' && !empty($claim['ApprovedDate'])) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete an approved claim']);
                exit;
            }

            // Delete any attachments first
            $stmt = $pdo->prepare("UPDATE hmoclaims SET Attachments = NULL WHERE ClaimID = :id");
            $stmt->execute([':id' => $id]);
            
            // Then delete the claim
            $stmt = $pdo->prepare("DELETE FROM hmoclaims WHERE ClaimID = :id");
            $stmt->execute([':id' => $id]);
            
            // Commit the transaction
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Delete claim error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Could not delete claim']);
        }
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) { error_log('API hmo_claims error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


