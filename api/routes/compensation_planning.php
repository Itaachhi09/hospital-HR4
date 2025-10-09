<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/ErrorHandler.php';
require_once __DIR__ . '/../models/SalaryGrades.php';
require_once __DIR__ . '/../models/PayBands.php';
require_once __DIR__ . '/../models/EmployeeGradeMapping.php';
require_once __DIR__ . '/../models/PayAdjustmentWorkflows.php';
require_once __DIR__ . '/../models/CompensationSimulations.php';
require_once __DIR__ . '/../models/IncentiveTypes.php';
require_once __DIR__ . '/../models/SalaryAdjustments.php';
require_once __DIR__ . '/../models/GradeRevisions.php';
require_once __DIR__ . '/../models/WorkflowEngine.php';

use App\Models\SalaryGrades;
use App\Models\PayBands;
use App\Models\EmployeeGradeMapping;
use App\Models\PayAdjustmentWorkflows;
use App\Models\CompensationSimulations;
use App\Models\IncentiveTypes;
use App\Models\SalaryAdjustments;
use App\Models\GradeRevisions;
use App\Models\WorkflowEngine;

// Set content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize error handler
$errorHandler = new ErrorHandler();


try {
    // Get database connection from global config
    global $pdo;
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Initialize models
    $salaryGrades = new SalaryGrades($pdo);
    $payBands = new PayBands($pdo);
    $employeeMapping = new EmployeeGradeMapping($pdo);
    $workflows = new PayAdjustmentWorkflows($pdo);
    $simulations = new CompensationSimulations($pdo);
    $incentiveTypes = new IncentiveTypes($pdo);
    $salaryAdjustments = new SalaryAdjustments($pdo);
    $salaryAdjustments->seedReasons();
    $gradeRevisions = new GradeRevisions($pdo);
    $workflowEngine = new WorkflowEngine($pdo);

    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    

    // Route the request
    switch ($method) {
        case 'GET':
            handleGetRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations);
            break;
        case 'POST':
            handlePostRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations);
            break;
        case 'PUT':
            handlePutRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations);
            break;
        case 'DELETE':
            handleDeleteRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    $errorHandler->handle($e);
}

function handleGetRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations, $incentiveTypes = null, $salaryAdjustments = null, $gradeRevisions = null, $workflowEngine = null)
{
    $endpoint = $pathParts[count($pathParts) - 1] ?? '';
    
    switch ($endpoint) {
        case 'salary-grades':
            $filters = $_GET;
            $result = $salaryGrades->getAll($filters);
            echo json_encode($result);
            break;
            
        case 'salary-grades-steps':
            $gradeId = $_GET['grade_id'] ?? null;
            if (!$gradeId) {
                http_response_code(400);
                echo json_encode(['error' => 'Grade ID is required']);
                return;
            }
            $result = $salaryGrades->getSteps($gradeId);
            echo json_encode($result);
            break;
            
        case 'pay-bands':
            $filters = $_GET;
            $result = $payBands->getAll($filters);
            echo json_encode($result);
            break;
            
        case 'employee-mappings':
            $filters = $_GET;
            $result = $employeeMapping->getAll($filters);
            echo json_encode($result);
            break;

        case 'employee-mapping-overview':
            $stats = $employeeMapping->getOverviewStats();
            echo json_encode(['success'=>true,'data'=>$stats]);
            break;

        case 'employee-mapping-history':
            $emp = $_GET['employee_id'] ?? null;
            if (!$emp) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'employee_id required']); break; }
            $hist = $employeeMapping->getHistory((int)$emp);
            echo json_encode(['success'=>true,'data'=>$hist]);
            break;
            
        case 'grade-distribution':
            $result = $employeeMapping->getGradeDistribution();
            echo json_encode($result);
            break;
            
        case 'workflows':
            $filters = $_GET;
            $result = $workflows->getAll($filters);
            echo json_encode($result);
            break;
            
        case 'workflow-details':
            $workflowId = $_GET['workflow_id'] ?? null;
            if (!$workflowId) {
                http_response_code(400);
                echo json_encode(['error' => 'Workflow ID is required']);
                return;
            }
            $result = $workflows->getWorkflowDetails($workflowId);
            echo json_encode($result);
            break;
            
        case 'simulations':
            $filters = $_GET;
            $result = $simulations->getAll($filters);
            echo json_encode($result);
            break;
            
        case 'simulation-statistics':
            $result = $simulations->getStatistics();
            echo json_encode($result);
            break;

        case 'incentive-types':
            $filters = $_GET;
            $result = $incentiveTypes->getAll($filters);
            echo json_encode(['success'=>true,'data'=>$result]);
            break;

        case 'salary-adjustments':
            $filters = $_GET;
            $result = $salaryAdjustments->list($filters);
            echo json_encode(['success'=>true,'data'=>$result]);
            break;

        case 'adjustment-reasons':
            echo json_encode(['success'=>true,'data'=>$salaryAdjustments->reasons()]);
            break;

        case 'grade-revisions':
            $filters = $_GET;
            $result = $gradeRevisions->list($filters);
            echo json_encode(['success'=>true,'data'=>$result]);
            break;

        case 'workflows-config':
            echo json_encode(['success'=>true,'data'=>$workflowEngine->listDefinitions()]);
            break;

        case 'workflows-my-approvals':
            $uid = $_GET['user_id'] ?? null; $roles = $_GET['roles'] ?? '';
            $list = $workflowEngine->myApprovals((int)$uid, is_array($roles)? $roles : explode(',', (string)$roles));
            echo json_encode(['success'=>true,'data'=>$list]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

function handlePostRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations, $incentiveTypes = null, $salaryAdjustments = null, $gradeRevisions = null, $workflowEngine = null)
{
    $endpoint = $pathParts[count($pathParts) - 1] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    switch ($endpoint) {
        case 'salary-grades':
            $result = $salaryGrades->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Salary grade created successfully', 'id' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create salary grade']);
            }
            break;
            
        case 'salary-grades-steps':
            $gradeId = $input['grade_id'] ?? null;
            if (!$gradeId) {
                http_response_code(400);
                echo json_encode(['error' => 'Grade ID is required']);
                return;
            }
            $result = $salaryGrades->addStep($gradeId, $input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Salary step created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create salary step']);
            }
            break;
            
        case 'pay-bands':
            $result = $payBands->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Pay band created successfully', 'id' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create pay band']);
            }
            break;
            
        case 'employee-mappings':
            $result = $employeeMapping->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Employee mapping created successfully', 'id' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create employee mapping']);
            }
            break;
            
        case 'workflows':
            $result = $workflows->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Workflow created successfully', 'id' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create workflow']);
            }
            break;
            
        case 'workflows-calculate-impact':
            $workflowId = $input['workflow_id'] ?? null;
            if (!$workflowId) {
                http_response_code(400);
                echo json_encode(['error' => 'Workflow ID is required']);
                return;
            }
            $result = $workflows->calculateImpact($workflowId);
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to calculate impact']);
            }
            break;
            
        case 'workflows-create-details':
            $workflowId = $input['workflow_id'] ?? null;
            $employees = $input['employees'] ?? [];
            $adjustmentValue = $input['adjustment_value'] ?? 0;
            $adjustmentType = $input['adjustment_type'] ?? 'Percentage';
            
            if (!$workflowId || empty($employees)) {
                http_response_code(400);
                echo json_encode(['error' => 'Workflow ID and employees are required']);
                return;
            }
            
            $result = $workflows->createWorkflowDetails($workflowId, $employees, $adjustmentValue, $adjustmentType);
            if ($result) {
                echo json_encode(['message' => 'Workflow details created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create workflow details']);
            }
            break;
            
        case 'simulations':
            $result = $simulations->create($input);
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Simulation created successfully', 'id' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create simulation']);
            }
            break;

        case 'incentive-types':
            $result = $incentiveTypes->create($input);
            if ($result) { http_response_code(201); echo json_encode(['success'=>true,'id'=>$result]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to create incentive type']); }
            break;

        case 'salary-adjustments':
            $result = $salaryAdjustments->create($input);
            if ($result) { http_response_code(201); echo json_encode(['success'=>true,'id'=>$result]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to create salary adjustment']); }
            break;

        case 'grade-revisions':
            $result = $gradeRevisions->create($input);
            if ($result) { http_response_code(201); echo json_encode(['success'=>true,'id'=>$result]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to create grade revision']); }
            break;

        case 'workflows-config':
            $id = $workflowEngine->createDefinition($input);
            if ($id) { http_response_code(201); echo json_encode(['success'=>true,'id'=>$id]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to create workflow']); }
            break;

        case 'workflows-start':
            $id = $workflowEngine->startInstance((int)$input['definition_id'], (string)$input['entity_type'], (int)$input['entity_id'], (int)($input['initiated_by']??0));
            if ($id) { http_response_code(201); echo json_encode(['success'=>true,'instance_id'=>$id]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to start workflow']); }
            break;
            
        case 'simulations-run':
            $simulationType = $input['simulation_type'] ?? '';
            $parameters = $input['parameters'] ?? [];
            
            switch ($simulationType) {
                case 'Grade Adjustment':
                    $result = $simulations->runGradeAdjustmentSimulation($parameters);
                    break;
                case 'Department Adjustment':
                    $result = $simulations->runDepartmentAdjustmentSimulation($parameters);
                    break;
                case 'Position Adjustment':
                    $result = $simulations->runPositionAdjustmentSimulation($parameters);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid simulation type']);
                    return;
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

function handlePutRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations, $incentiveTypes = null, $salaryAdjustments = null, $gradeRevisions = null, $workflowEngine = null)
{
    $endpoint = $pathParts[count($pathParts) - 1] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        return;
    }
    
    switch ($endpoint) {
        case 'salary-grades':
            $result = $salaryGrades->update($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Salary grade updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update salary grade']);
            }
            break;
            
        case 'salary-grades-approve':
            $result = $salaryGrades->approve($id, $input['approved_by'] ?? null);
            if ($result) {
                echo json_encode(['message' => 'Salary grade approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to approve salary grade']);
            }
            break;
            
        case 'salary-grades-steps':
            $result = $salaryGrades->updateStep($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Salary step updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update salary step']);
            }
            break;
            
        case 'pay-bands':
            $result = $payBands->update($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Pay band updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update pay band']);
            }
            break;
            
        case 'employee-mappings':
            $result = $employeeMapping->update($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Employee mapping updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update employee mapping']);
            }
            break;
            
        case 'workflows':
            $result = $workflows->update($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Workflow updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update workflow']);
            }
            break;
            
        case 'workflows-approve':
            $result = $workflows->approve($id, $input['approved_by'] ?? null);
            if ($result) {
                echo json_encode(['message' => 'Workflow approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to approve workflow']);
            }
            break;
            
        case 'workflows-implement':
            $result = $workflows->implement($id, $input['implemented_by'] ?? null);
            if ($result) {
                echo json_encode(['message' => 'Workflow implemented successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to implement workflow']);
            }
            break;
            
        case 'simulations':
            $result = $simulations->update($id, $input);
            if ($result) {
                echo json_encode(['message' => 'Simulation updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update simulation']);
            }
            break;

        case 'incentive-types':
            $result = $incentiveTypes->update($id, $input);
            if ($result) { echo json_encode(['success'=>true]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to update incentive type']); }
            break;

        case 'employee-mappings-approve':
            $mid = $input['id'] ?? null; $by = $input['user_id'] ?? null;
            if (!$mid) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id required']); break; }
            $ok = $employeeMapping->approve((int)$mid, $by);
            echo json_encode(['success'=>$ok]);
            break;

        case 'salary-adjustments':
            $result = $salaryAdjustments->update((int)$id, $input);
            if ($result) { echo json_encode(['success'=>true]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to update salary adjustment']); }
            break;

        case 'salary-adjustments-status':
            $sid = $input['id'] ?? null; $status = $input['status'] ?? null; $by = $input['user_id'] ?? null;
            if (!$sid || !$status) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id and status required']); break; }
            $ok = $salaryAdjustments->setStatus((int)$sid, $status, $by);
            echo json_encode(['success'=>$ok]);
            break;

        case 'grade-revisions-status':
            $rid = $input['id'] ?? null; $status = $input['status'] ?? null; $by = $input['user_id'] ?? null;
            if (!$rid || !$status) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id and status required']); break; }
            $ok = $gradeRevisions->setStatus((int)$rid, $status, $by);
            echo json_encode(['success'=>$ok]);
            break;

        case 'grade-revisions-implement':
            $rid = $input['id'] ?? null; $by = $input['user_id'] ?? null;
            if (!$rid) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id required']); break; }
            $ok = $gradeRevisions->implement((int)$rid, (int)$by);
            echo json_encode(['success'=>$ok]);
            break;

        case 'workflows-config':
            $id = $input['id'] ?? null; if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id required']); break; }
            $ok = $workflowEngine->updateDefinition((int)$id, $input);
            echo json_encode(['success'=>$ok]);
            break;

        case 'workflows-act':
            $iid = $input['instance_id'] ?? null; $action = $input['action'] ?? null; $uid = $input['user_id'] ?? null; $c = $input['comment'] ?? null;
            if (!$iid || !$action) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'instance_id and action required']); break; }
            $ok = $workflowEngine->act((int)$iid, $action, (int)$uid, (string)$c);
            echo json_encode(['success'=>$ok]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

function handleDeleteRequest($pathParts, $salaryGrades, $payBands, $employeeMapping, $workflows, $simulations, $incentiveTypes = null, $salaryAdjustments = null, $gradeRevisions = null, $workflowEngine = null)
{
    $endpoint = $pathParts[count($pathParts) - 1] ?? '';
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        return;
    }
    
    switch ($endpoint) {
        case 'workflows-config':
            $ok = $workflowEngine->deleteDefinition((int)$id);
            echo json_encode(['success'=>$ok]);
            break;
        case 'salary-grades':
            $result = $salaryGrades->delete($id);
            if ($result) {
                echo json_encode(['message' => 'Salary grade deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete salary grade']);
            }
            break;
            
        case 'salary-grades-steps':
            $result = $salaryGrades->deleteStep($id);
            if ($result) {
                echo json_encode(['message' => 'Salary step deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete salary step']);
            }
            break;
            
        case 'pay-bands':
            $result = $payBands->delete($id);
            if ($result) {
                echo json_encode(['message' => 'Pay band deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete pay band']);
            }
            break;
            
        case 'employee-mappings':
            $result = $employeeMapping->delete($id);
            if ($result) {
                echo json_encode(['message' => 'Employee mapping deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete employee mapping']);
            }
            break;
            
        case 'workflows':
            $result = $workflows->delete($id);
            if ($result) {
                echo json_encode(['message' => 'Workflow deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete workflow']);
            }
            break;
            
        case 'simulations':
            $result = $simulations->delete($id);
            if ($result) {
                echo json_encode(['message' => 'Simulation deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete simulation']);
            }
            break;

        case 'incentive-types':
            $result = $incentiveTypes->delete((int)$id);
            if ($result) { echo json_encode(['success'=>true]); }
            else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to delete incentive type']); }
            break;

        case 'salary-adjustments':
            $stmt = $salaryAdjustments->setStatus((int)$id, 'Rejected', null); // Or hard delete if desired
            echo json_encode(['success'=>true]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}
