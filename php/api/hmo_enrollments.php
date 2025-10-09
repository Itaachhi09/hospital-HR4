<?php
require_once __DIR__ . '/_api_bootstrap.php';
// Enrollments: Admins manage all records. Employees may view and create their own enrollments.
api_require_auth();

try { global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            // Select explicit columns and alias to expected keys
            $stmt = $pdo->prepare("SELECT ehe.EnrollmentID, ehe.EmployeeID, ehe.PlanID, ehe.Status AS EnrollmentStatus, COALESCE(ehe.MonthlyDeduction, ehe.MonthlyContribution, 0) AS MonthlyDeduction, ehe.EnrollmentDate, ehe.EffectiveDate, ehe.EndDate, e.FirstName, e.LastName, hp.PlanName FROM employeehmoenrollments ehe JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID WHERE ehe.EnrollmentID=:id");
            $stmt->execute([':id'=>$id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // If employee role, ensure they can only access their own record
            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'System Admin' && $role !== 'HR Admin') {
                $userEmpId = $_SESSION['employee_id'] ?? null;
                if (!$row || (int)$row['EmployeeID'] !== (int)$userEmpId) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
            }
            echo json_encode(['success'=>true,'enrollment'=>$row]); exit;
        }
        $role = $_SESSION['role_name'] ?? '';
        if ($role === 'System Admin' || $role === 'HR Admin') {
            $stmt = $pdo->query("SELECT ehe.EnrollmentID, ehe.EmployeeID, ehe.PlanID, ehe.Status AS EnrollmentStatus, COALESCE(ehe.MonthlyDeduction, ehe.MonthlyContribution, 0) AS MonthlyDeduction, ehe.EnrollmentDate, ehe.EffectiveDate, ehe.EndDate, e.FirstName, e.LastName, hp.PlanName FROM employeehmoenrollments ehe JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID ORDER BY ehe.CreatedAt DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $userEmpId = $_SESSION['employee_id'] ?? null;
            $stmt = $pdo->prepare("SELECT ehe.EnrollmentID, ehe.EmployeeID, ehe.PlanID, ehe.Status AS EnrollmentStatus, COALESCE(ehe.MonthlyDeduction, ehe.MonthlyContribution, 0) AS MonthlyDeduction, ehe.EnrollmentDate, ehe.EffectiveDate, ehe.EndDate, e.FirstName, e.LastName, hp.PlanName FROM employeehmoenrollments ehe JOIN employees e ON ehe.EmployeeID=e.EmployeeID JOIN hmoplans hp ON ehe.PlanID=hp.PlanID WHERE ehe.EmployeeID=:emp ORDER BY ehe.CreatedAt DESC");
            $stmt->execute([':emp' => (int)$userEmpId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success'=>true,'enrollments'=>$rows]); exit;
    }

    if ($method === 'POST') {
        $d = api_read_json();
        // If non-admin, force EmployeeID to the session's employee_id
        $role = $_SESSION['role_name'] ?? '';
        $employeeId = (int)($d['employee_id'] ?? 0);
        if ($role !== 'System Admin' && $role !== 'HR Admin') {
            $employeeId = (int)($_SESSION['employee_id'] ?? 0);
            if ($employeeId <= 0) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
        }

    // Use actual columns: EnrollmentDate/EffectiveDate/MonthlyDeduction
    $sql = "INSERT INTO employeehmoenrollments (EmployeeID, PlanID, MonthlyDeduction, EnrollmentDate, EffectiveDate, EndDate, Status) VALUES (:EmployeeID,:PlanID,:MonthlyDeduction,:EnrollmentDate,:EffectiveDate,:EndDate,:Status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':EmployeeID' => $employeeId,
            ':PlanID' => (int)($d['plan_id'] ?? 0),
            ':MonthlyDeduction' => $d['monthly_deduction'] ?? $d['monthly_contribution'] ?? 0,
            ':EnrollmentDate' => $d['enrollment_date'] ?? date('Y-m-d'),
            ':EffectiveDate' => $d['effective_date'] ?? date('Y-m-d'),
            ':EndDate' => $d['end_date'] ?? null,
            ':Status' => $d['status'] ?? 'Active',
        ]);
        $newId = $pdo->lastInsertId();

        // Create HMO-specific notification and global notification for the employee
        try {
            // hmo_notifications table
            $notifSql = "INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message) VALUES (:EmployeeID, :Type, :Title, :Message)";
            $notifStmt = $pdo->prepare($notifSql);
            $message = sprintf('You have been enrolled to plan ID %d (Enrollment #%d).', (int)$d['plan_id'], (int)$newId);
            $notifStmt->execute([
                ':EmployeeID' => (int)($d['employee_id'] ?? 0),
                ':Type' => 'ENROLLMENT',
                ':Title' => 'HMO Enrollment',
                ':Message' => $message
            ]);

            // global notifications table for user-facing badge - try to map EmployeeID -> UserID
            $userIdForEmployee = null;
            try {
                $mapStmt = $pdo->prepare("SELECT UserID FROM Users WHERE EmployeeID = :emp LIMIT 1");
                $mapStmt->execute([':emp' => (int)$d['employee_id']]);
                $urow = $mapStmt->fetch(PDO::FETCH_ASSOC);
                if ($urow && isset($urow['UserID'])) $userIdForEmployee = (int)$urow['UserID'];
            } catch (Throwable $me) {
                // ignore
            }
            if ($userIdForEmployee) {
                $globalNotifSql = "INSERT INTO Notifications (UserID, SenderUserID, NotificationType, Message, Link, IsRead) VALUES (:UserID, :SenderUserID, :NotificationType, :Message, :Link, 0)";
                $globalStmt = $pdo->prepare($globalNotifSql);
                $globalStmt->execute([
                    ':UserID' => $userIdForEmployee,
                    ':SenderUserID' => $_SESSION['user_id'] ?? $userIdForEmployee,
                    ':NotificationType' => 'HMO_ENROLLED',
                    ':Message' => $message,
                    ':Link' => '#my-hmo-benefits'
                ]);
            }
        } catch (Throwable $ne) {
            error_log('HMO enrollment notification error: '.$ne->getMessage());
            // non-fatal
        }

        // **NEW: Auto-sync with compensation module**
        try {
            require_once __DIR__ . '/../../api/integrations/HMOPayrollIntegration.php';
            $hmoIntegration = new HMOPayrollIntegration();
            $hmoIntegration->syncWithCompensation($employeeId);
        } catch (Throwable $syncErr) {
            error_log('HMO enrollment compensation sync error: ' . $syncErr->getMessage());
            // Non-fatal, continue
        }

        echo json_encode(['success'=>true,'id'=>$newId]); exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $d = api_read_json();
        // Fetch existing for permission check
    $pre = $pdo->prepare("SELECT EmployeeID FROM employeehmoenrollments WHERE EnrollmentID=:id"); $pre->execute([':id'=>$id]); $rec = $pre->fetch(PDO::FETCH_ASSOC);
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'System Admin' && $role !== 'HR Admin') {
            $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
            if (!$rec || (int)$rec['EmployeeID'] !== $userEmpId) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
        }

        $sql = "UPDATE employeehmoenrollments SET PlanID=:PlanID, MonthlyDeduction=:MonthlyDeduction, EnrollmentDate=:EnrollmentDate, EffectiveDate=:EffectiveDate, EndDate=:EndDate, Status=:Status WHERE EnrollmentID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':PlanID' => (int)($d['plan_id'] ?? 0),
            ':MonthlyDeduction' => $d['monthly_deduction'] ?? $d['monthly_contribution'] ?? 0,
            ':EnrollmentDate' => $d['enrollment_date'] ?? date('Y-m-d'),
            ':EffectiveDate' => $d['effective_date'] ?? date('Y-m-d'),
            ':EndDate' => $d['end_date'] ?? null,
            ':Status' => $d['status'] ?? 'Active',
            ':id' => $id,
        ]);
        // Create notification for update
        try {
            $message = sprintf('Your HMO enrollment (Enrollment #%d) has been updated.', (int)$id);
            $notifStmt = $pdo->prepare("INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message) VALUES (:EmployeeID, :Type, :Title, :Message)");
            $notifStmt->execute([':EmployeeID' => (int)($d['employee_id'] ?? 0), ':Type' => 'UPDATE', ':Title' => 'HMO Enrollment Updated', ':Message' => $message]);

            // Map to user and insert global notification
            $userIdForEmployee = null;
            try {
                $mapStmt = $pdo->prepare("SELECT UserID FROM Users WHERE EmployeeID = :emp LIMIT 1");
                $mapStmt->execute([':emp' => (int)($d['employee_id'] ?? 0)]);
                $urow = $mapStmt->fetch(PDO::FETCH_ASSOC);
                if ($urow && isset($urow['UserID'])) $userIdForEmployee = (int)$urow['UserID'];
            } catch (Throwable $me) {
                // ignore
            }
            if ($userIdForEmployee) {
                $globalStmt = $pdo->prepare("INSERT INTO Notifications (UserID, SenderUserID, NotificationType, Message, Link, IsRead) VALUES (:UserID, :SenderUserID, :NotificationType, :Message, :Link, 0)");
                $globalStmt->execute([
                    ':UserID' => $userIdForEmployee,
                    ':SenderUserID' => $_SESSION['user_id'] ?? $userIdForEmployee,
                    ':NotificationType' => 'HMO_UPDATED',
                    ':Message' => $message,
                    ':Link' => '#my-hmo-benefits'
                ]);
            }
        } catch (Throwable $ne) {
            error_log('HMO update notification error: '.$ne->getMessage());
        }

        // **NEW: Auto-sync with compensation module after update**
        try {
            require_once __DIR__ . '/../../api/integrations/HMOPayrollIntegration.php';
            $hmoIntegration = new HMOPayrollIntegration();
            $employeeIdToSync = (int)($d['employee_id'] ?? ($rec['EmployeeID'] ?? 0));
            if ($employeeIdToSync > 0) {
                $hmoIntegration->syncWithCompensation($employeeIdToSync);
            }
        } catch (Throwable $syncErr) {
            error_log('HMO enrollment update compensation sync error: ' . $syncErr->getMessage());
            // Non-fatal, continue
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

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Get enrollment details first
            $pre = $pdo->prepare("SELECT e.EmployeeID, e.PlanID, e.Status, emp.FirstName, emp.LastName, p.PlanName 
                                FROM employeehmoenrollments e 
                                JOIN employees emp ON e.EmployeeID = emp.EmployeeID 
                                JOIN hmoplans p ON e.PlanID = p.PlanID 
                                WHERE e.EnrollmentID = :id");
            $pre->execute([':id' => $id]);
            $rec = $pre->fetch(PDO::FETCH_ASSOC);

            if (!$rec) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Enrollment not found']);
                exit;
            }

            // Permission check
            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'System Admin' && $role !== 'HR Admin') {
                $userEmpId = (int)($_SESSION['employee_id'] ?? 0);
                if ((int)$rec['EmployeeID'] !== $userEmpId) {
                    $pdo->rollBack();
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
            }

            // Check for active claims
            $claimCheck = $pdo->prepare("SELECT COUNT(*) FROM hmoclaims WHERE EnrollmentID = :id AND Status != 'Denied'");
            $claimCheck->execute([':id' => $id]);
            if ($claimCheck->fetchColumn() > 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete enrollment: It has active or pending claims']);
                exit;
            }

            // If enrollment is active, only allow deletion by admins
            if ($rec['Status'] === 'Active' && $role !== 'System Admin' && $role !== 'HR Admin') {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete active enrollment. Please terminate it first.']);
                exit;
            }

            // Delete the enrollment
            $stmt = $pdo->prepare("DELETE FROM employeehmoenrollments WHERE EnrollmentID = :id");
            $stmt->execute([':id' => $id]);

            // Create notifications
            $employeeName = trim($rec['FirstName'] . ' ' . $rec['LastName']);
            $message = sprintf('HMO enrollment for %s (Plan: %s) has been removed.', 
                             $employeeName,
                             $rec['PlanName']);

            $notifStmt = $pdo->prepare("INSERT INTO hmo_notifications (EmployeeID, Type, Title, Message) VALUES (:EmployeeID, :Type, :Title, :Message)");
            $notifStmt->execute([
                ':EmployeeID' => $rec['EmployeeID'],
                ':Type' => 'UNENROLL',
                ':Title' => 'HMO Enrollment Removed',
                ':Message' => $message
            ]);

            // Add global notification
            $mapStmt = $pdo->prepare("SELECT UserID FROM Users WHERE EmployeeID = :emp LIMIT 1");
            $mapStmt->execute([':emp' => (int)$rec['EmployeeID']]);
            $urow = $mapStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($urow && isset($urow['UserID'])) {
                $globalStmt = $pdo->prepare("INSERT INTO Notifications (UserID, SenderUserID, NotificationType, Message, Link, IsRead) 
                    VALUES (:UserID, :SenderUserID, :NotificationType, :Message, :Link, 0)");
                $globalStmt->execute([
                    ':UserID' => (int)$urow['UserID'],
                    ':SenderUserID' => $_SESSION['user_id'] ?? (int)$urow['UserID'],
                    ':NotificationType' => 'HMO_UNENROLLED',
                    ':Message' => $message,
                    ':Link' => '#my-hmo-benefits'
                ]);
            }

            // Commit all changes
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Delete enrollment error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Could not delete enrollment']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('HMO enrollment error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) { error_log('API hmo_enrollments error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


