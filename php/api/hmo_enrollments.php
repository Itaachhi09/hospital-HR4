<?php
require_once __DIR__ . '/_api_bootstrap.php';
api_require_auth(['System Admin','HR Admin']);

try { global $pdo; $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT ehe.*, e.FirstName, e.LastName, hp.PlanName FROM EmployeeHMOEnrollments ehe JOIN Employees e ON ehe.EmployeeID=e.EmployeeID JOIN HMOPlans hp ON ehe.PlanID=hp.PlanID WHERE EnrollmentID=:id");
            $stmt->execute([':id'=>(int)$_GET['id']]); echo json_encode(['success'=>true,'enrollment'=>$stmt->fetch(PDO::FETCH_ASSOC)]); exit;
        }
        $stmt = $pdo->query("SELECT ehe.*, e.FirstName, e.LastName, hp.PlanName FROM EmployeeHMOEnrollments ehe JOIN Employees e ON ehe.EmployeeID=e.EmployeeID JOIN HMOPlans hp ON ehe.PlanID=hp.PlanID ORDER BY ehe.CreatedAt DESC");
        echo json_encode(['success'=>true,'enrollments'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
    }

    if ($method === 'POST') {
        $d = api_read_json();
        $sql = "INSERT INTO EmployeeHMOEnrollments (EmployeeID, PlanID, Status, MonthlyDeduction, EnrollmentDate, EffectiveDate, EndDate, DependentInfo) VALUES (:EmployeeID,:PlanID,:Status,:MonthlyDeduction,:EnrollmentDate,:EffectiveDate,:EndDate,:DependentInfo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':EmployeeID' => (int)($d['employee_id'] ?? 0),
            ':PlanID' => (int)($d['plan_id'] ?? 0),
            ':Status' => $d['status'] ?? 'Active',
            ':MonthlyDeduction' => (float)($d['monthly_deduction'] ?? 0),
            ':EnrollmentDate' => $d['enrollment_date'] ?? date('Y-m-d'),
            ':EffectiveDate' => $d['effective_date'] ?? date('Y-m-d'),
            ':EndDate' => $d['end_date'] ?? null,
            ':DependentInfo' => $d['dependent_info'] ?? null,
        ]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
    }

    if ($method === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $d = api_read_json();
        $sql = "UPDATE EmployeeHMOEnrollments SET EmployeeID=:EmployeeID, PlanID=:PlanID, Status=:Status, MonthlyDeduction=:MonthlyDeduction, EnrollmentDate=:EnrollmentDate, EffectiveDate=:EffectiveDate, EndDate=:EndDate, DependentInfo=:DependentInfo WHERE EnrollmentID=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':EmployeeID' => (int)($d['employee_id'] ?? 0),
            ':PlanID' => (int)($d['plan_id'] ?? 0),
            ':Status' => $d['status'] ?? 'Active',
            ':MonthlyDeduction' => (float)($d['monthly_deduction'] ?? 0),
            ':EnrollmentDate' => $d['enrollment_date'] ?? date('Y-m-d'),
            ':EffectiveDate' => $d['effective_date'] ?? date('Y-m-d'),
            ':EndDate' => $d['end_date'] ?? null,
            ':DependentInfo' => $d['dependent_info'] ?? null,
            ':id' => $id,
        ]);
        echo json_encode(['success'=>true]); exit;
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; if ($id<=0){http_response_code(400);echo json_encode(['error'=>'Missing id']);exit;}
        $stmt = $pdo->prepare("DELETE FROM EmployeeHMOEnrollments WHERE EnrollmentID=:id");
        $stmt->execute([':id'=>$id]);
        echo json_encode(['success'=>true]); exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
} catch (Throwable $e) { error_log('API hmo_enrollments error: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


