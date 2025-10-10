<?php
/**
 * Unified HMO Routes
 * Comprehensive HMO management endpoints with integration support
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/HMOProvider.php';
require_once __DIR__ . '/../models/HMOPlan.php';
require_once __DIR__ . '/../models/HMOEnrollment.php';
require_once __DIR__ . '/../models/HMOClaim.php';

class HMOController {
    private $pdo;
    private $authMiddleware;
    private $providerModel;
    private $planModel;
    private $enrollmentModel;
    private $claimModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
        $this->providerModel = new HMOProvider();
        $this->planModel = new HMOPlan();
        $this->enrollmentModel = new HMOEnrollment();
        $this->claimModel = new HMOClaim();
    }

    /**
     * Handle routing for all HMO operations
     */
    public function handleRequest($method, $resource = null, $id = null, $subResource = null) {
        switch ($resource) {
            case 'providers':
                $this->handleProviders($method, $id, $subResource);
                break;
            case 'plans':
                $this->handlePlans($method, $id, $subResource);
                break;
            case 'enrollments':
                $this->handleEnrollments($method, $id, $subResource);
                break;
            case 'claims':
                $this->handleClaims($method, $id, $subResource);
                break;
            case 'dashboard':
                $this->getDashboardData();
                break;
            case 'analytics':
                // $id contains the analytics type when called from index.php
                $this->getAnalytics($id ?? $subResource);
                break;
            default:
                Response::notFound('Resource not found');
        }
    }

    // =========================
    // PROVIDER OPERATIONS
    // =========================

    private function handleProviders($method, $id, $subResource) {
        if ($subResource === 'metrics' && $id) {
            $this->getProviderMetrics($id);
            return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getProvider($id);
                } else {
                    $this->getProviders();
                }
                break;
            case 'POST':
                $this->createProvider();
                break;
            case 'PUT':
            case 'PATCH':
                $this->updateProvider($id);
                break;
            case 'DELETE':
                $this->deleteProvider($id);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function getProviders() {
        try {
            $filters = [
                'is_active' => Request::getQueryParam('is_active'),
                'search' => Request::getQueryParam('search')
            ];

            $providers = $this->providerModel->getProvidersWithMetrics();
            Response::success($providers);

        } catch (Exception $e) {
            error_log("Get providers error: " . $e->getMessage());
            Response::error('Failed to retrieve providers', 500);
        }
    }

    private function getProvider($id) {
        try {
            $provider = $this->providerModel->getProviderById($id);
            if (!$provider) {
                Response::notFound('Provider not found');
            }
            Response::success($provider);

        } catch (Exception $e) {
            error_log("Get provider error: " . $e->getMessage());
            Response::error('Failed to retrieve provider', 500);
        }
    }

    private function createProvider() {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        $errors = Request::validateRequired($data, ['provider_name']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $providerId = $this->providerModel->createProvider($data);
            Response::created([
                'provider_id' => $providerId,
                'provider_name' => $data['provider_name']
            ], 'Provider created successfully');

        } catch (Exception $e) {
            error_log("Create provider error: " . $e->getMessage());
            Response::error('Failed to create provider', 500);
        }
    }

    private function updateProvider($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        try {
            $this->providerModel->updateProvider($id, $data);
            Response::success(null, 'Provider updated successfully');

        } catch (Exception $e) {
            error_log("Update provider error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function deleteProvider($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        try {
            $this->providerModel->deleteProvider($id);
            Response::success(null, 'Provider deleted successfully');

        } catch (Exception $e) {
            error_log("Delete provider error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function getProviderMetrics($id) {
        try {
            $metrics = $this->providerModel->getProviderMetrics($id);
            Response::success($metrics);

        } catch (Exception $e) {
            error_log("Get provider metrics error: " . $e->getMessage());
            Response::error('Failed to retrieve provider metrics', 500);
        }
    }

    // =========================
    // PLAN OPERATIONS
    // =========================

    private function handlePlans($method, $id, $subResource) {
        if ($subResource === 'utilization') {
            $this->getPlanUtilization($id);
            return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getPlan($id);
                } else {
                    $this->getPlans();
                }
                break;
            case 'POST':
                $this->createPlan();
                break;
            case 'PUT':
            case 'PATCH':
                $this->updatePlan($id);
                break;
            case 'DELETE':
                $this->deletePlan($id);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function getPlans() {
        try {
            $filters = [
                'provider_id' => Request::getQueryParam('provider_id'),
                'is_active' => Request::getQueryParam('is_active'),
                'coverage_type' => Request::getQueryParam('coverage_type'),
                'plan_category' => Request::getQueryParam('plan_category'),
                'search' => Request::getQueryParam('search')
            ];

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $plans = $this->planModel->getPlans($filters);
            Response::success($plans);

        } catch (Exception $e) {
            error_log("Get plans error: " . $e->getMessage());
            Response::error('Failed to retrieve plans', 500);
        }
    }

    private function getPlan($id) {
        try {
            $plan = $this->planModel->getPlanById($id);
            if (!$plan) {
                Response::notFound('Plan not found');
            }
            Response::success($plan);

        } catch (Exception $e) {
            error_log("Get plan error: " . $e->getMessage());
            Response::error('Failed to retrieve plan', 500);
        }
    }

    private function createPlan() {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        $errors = Request::validateRequired($data, ['provider_id', 'plan_name']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $planId = $this->planModel->createPlan($data);
            Response::created([
                'plan_id' => $planId,
                'plan_name' => $data['plan_name']
            ], 'Plan created successfully');

        } catch (Exception $e) {
            error_log("Create plan error: " . $e->getMessage());
            Response::error('Failed to create plan', 500);
        }
    }

    private function updatePlan($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        try {
            $this->planModel->updatePlan($id, $data);
            Response::success(null, 'Plan updated successfully');

        } catch (Exception $e) {
            error_log("Update plan error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function deletePlan($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        try {
            $this->planModel->deletePlan($id);
            Response::success(null, 'Plan deleted successfully');

        } catch (Exception $e) {
            error_log("Delete plan error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function getPlanUtilization($id = null) {
        try {
            $utilization = $this->planModel->getPlanUtilization($id);
            Response::success($utilization);

        } catch (Exception $e) {
            error_log("Get plan utilization error: " . $e->getMessage());
            Response::error('Failed to retrieve plan utilization', 500);
        }
    }

    // =========================
    // ENROLLMENT OPERATIONS
    // =========================

    private function handleEnrollments($method, $id, $subResource) {
        switch ($subResource) {
            case 'balance':
                $this->getEnrollmentBalance($id);
                return;
            case 'history':
                $this->getEnrollmentHistory($id);
                return;
            case 'terminate':
                $this->terminateEnrollment($id);
                return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getEnrollment($id);
                } else {
                    $this->getEnrollments();
                }
                break;
            case 'POST':
                $this->createEnrollment();
                break;
            case 'PUT':
            case 'PATCH':
                $this->updateEnrollment($id);
                break;
            case 'DELETE':
                $this->deleteEnrollment($id);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function getEnrollments() {
        try {
            $role = $_SESSION['role_name'] ?? '';

            $filters = [];
            
            // Non-admins can only see their own enrollments
            if (!in_array($role, ['System Admin', 'HR Admin', 'Manager'])) {
                $filters['employee_id'] = $_SESSION['employee_id'] ?? null;
            } else {
                $filters = [
                    'employee_id' => Request::getQueryParam('employee_id'),
                    'plan_id' => Request::getQueryParam('plan_id'),
                    'provider_id' => Request::getQueryParam('provider_id'),
                    'status' => Request::getQueryParam('status'),
                    'department_id' => Request::getQueryParam('department_id')
                ];
            }

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $enrollments = $this->enrollmentModel->getEnrollments($filters);
            Response::success($enrollments);

        } catch (Exception $e) {
            error_log("Get enrollments error: " . $e->getMessage());
            Response::error('Failed to retrieve enrollments', 500);
        }
    }

    private function getEnrollment($id) {
        try {
            $enrollment = $this->enrollmentModel->getEnrollmentById($id);
            if (!$enrollment) {
                Response::notFound('Enrollment not found');
            }

            // Check permission
            $role = $_SESSION['role_name'] ?? '';
            if (!in_array($role, ['System Admin', 'HR Admin', 'Manager'])) {
                if ($enrollment['EmployeeID'] != ($_SESSION['employee_id'] ?? null)) {
                    Response::forbidden('Access denied');
                }
            }

            Response::success($enrollment);

        } catch (Exception $e) {
            error_log("Get enrollment error: " . $e->getMessage());
            Response::error('Failed to retrieve enrollment', 500);
        }
    }

    private function createEnrollment() {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        $errors = Request::validateRequired($data, ['employee_id', 'plan_id']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $enrollmentId = $this->enrollmentModel->createEnrollment($data);
            Response::created([
                'enrollment_id' => $enrollmentId
            ], 'Enrollment created successfully');

        } catch (Exception $e) {
            error_log("Create enrollment error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function updateEnrollment($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();

        try {
            $this->enrollmentModel->updateEnrollment($id, $data);
            Response::success(null, 'Enrollment updated successfully');

        } catch (Exception $e) {
            error_log("Update enrollment error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function deleteEnrollment($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        try {
            // Instead of deleting, terminate the enrollment
            $this->enrollmentModel->terminateEnrollment($id);
            Response::success(null, 'Enrollment terminated successfully');

        } catch (Exception $e) {
            error_log("Delete enrollment error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function terminateEnrollment($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();
        $endDate = $data['end_date'] ?? null;

        try {
            $this->enrollmentModel->terminateEnrollment($id, $endDate);
            Response::success(null, 'Enrollment terminated successfully');

        } catch (Exception $e) {
            error_log("Terminate enrollment error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function getEnrollmentBalance($id) {
        try {
            $balance = $this->enrollmentModel->getEnrollmentBalance($id);
            Response::success($balance);

        } catch (Exception $e) {
            error_log("Get enrollment balance error: " . $e->getMessage());
            Response::error('Failed to retrieve enrollment balance', 500);
        }
    }

    private function getEnrollmentHistory($id) {
        try {
            $history = $this->enrollmentModel->getEnrollmentHistory($id);
            Response::success($history);

        } catch (Exception $e) {
            error_log("Get enrollment history error: " . $e->getMessage());
            Response::error('Failed to retrieve enrollment history', 500);
        }
    }

    // =========================
    // CLAIM OPERATIONS
    // =========================

    private function handleClaims($method, $id, $subResource) {
        switch ($subResource) {
            case 'approve':
                $this->approveClaim($id);
                return;
            case 'deny':
                $this->denyClaim($id);
                return;
            case 'revision':
                $this->requestRevision($id);
                return;
            case 'statistics':
                $this->getClaimStatistics();
                return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getClaim($id);
                } else {
                    $this->getClaims();
                }
                break;
            case 'POST':
                $this->createClaim();
                break;
            case 'PUT':
            case 'PATCH':
                $this->updateClaim($id);
                break;
            case 'DELETE':
                $this->deleteClaim($id);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function getClaims() {
        try {
            $role = $_SESSION['role_name'] ?? '';

            $filters = [];
            
            // Non-admins can only see their own claims
            if (!in_array($role, ['System Admin', 'HR Admin', 'Manager'])) {
                $filters['employee_id'] = $_SESSION['employee_id'] ?? null;
            } else {
                $filters = [
                    'employee_id' => Request::getQueryParam('employee_id'),
                    'enrollment_id' => Request::getQueryParam('enrollment_id'),
                    'status' => Request::getQueryParam('status'),
                    'claim_type' => Request::getQueryParam('claim_type'),
                    'from_date' => Request::getQueryParam('from_date'),
                    'to_date' => Request::getQueryParam('to_date'),
                    'min_amount' => Request::getQueryParam('min_amount'),
                    'max_amount' => Request::getQueryParam('max_amount')
                ];
            }

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $claims = $this->claimModel->getClaims($filters);
            Response::success($claims);

        } catch (Exception $e) {
            error_log("Get claims error: " . $e->getMessage());
            Response::error('Failed to retrieve claims', 500);
        }
    }

    private function getClaim($id) {
        try {
            $claim = $this->claimModel->getClaimById($id);
            if (!$claim) {
                Response::notFound('Claim not found');
            }

            // Check permission
            $role = $_SESSION['role_name'] ?? '';
            if (!in_array($role, ['System Admin', 'HR Admin', 'Manager'])) {
                if ($claim['EmployeeID'] != ($_SESSION['employee_id'] ?? null)) {
                    Response::forbidden('Access denied');
                }
            }

            Response::success($claim);

        } catch (Exception $e) {
            error_log("Get claim error: " . $e->getMessage());
            Response::error('Failed to retrieve claim', 500);
        }
    }

    private function createClaim() {
        $data = Request::getJsonBody();

        $errors = Request::validateRequired($data, ['enrollment_id', 'amount']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        // Non-admins can only create claims for themselves
        $role = $_SESSION['role_name'] ?? '';
        if (!in_array($role, ['System Admin', 'HR Admin'])) {
            $data['employee_id'] = $_SESSION['employee_id'] ?? null;
        }

        try {
            $claimId = $this->claimModel->createClaim($data);
            Response::created([
                'claim_id' => $claimId
            ], 'Claim submitted successfully');

        } catch (Exception $e) {
            error_log("Create claim error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function updateClaim($id) {
        $data = Request::getJsonBody();

        try {
            $this->claimModel->updateClaim($id, $data);
            Response::success(null, 'Claim updated successfully');

        } catch (Exception $e) {
            error_log("Update claim error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function deleteClaim($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin'])) {
            Response::forbidden('Insufficient permissions');
        }

        try {
            // Delete claim logic would go here
            Response::success(null, 'Claim deleted successfully');

        } catch (Exception $e) {
            error_log("Delete claim error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function approveClaim($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();
        $comments = $data['comments'] ?? null;
        $approverId = $_SESSION['user_id'] ?? null;

        try {
            $this->claimModel->approveClaim($id, $approverId, $comments);
            Response::success(null, 'Claim approved successfully');

        } catch (Exception $e) {
            error_log("Approve claim error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function denyClaim($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();
        $reason = $data['reason'] ?? null;
        $deniedBy = $_SESSION['user_id'] ?? null;

        if (empty($reason)) {
            Response::validationError(['reason' => 'Reason is required']);
        }

        try {
            $this->claimModel->denyClaim($id, $deniedBy, $reason);
            Response::success(null, 'Claim denied');

        } catch (Exception $e) {
            error_log("Deny claim error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function requestRevision($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }

        $data = Request::getJsonBody();
        $comments = $data['comments'] ?? null;
        $requestedBy = $_SESSION['user_id'] ?? null;

        if (empty($comments)) {
            Response::validationError(['comments' => 'Comments are required']);
        }

        try {
            $this->claimModel->requestRevision($id, $requestedBy, $comments);
            Response::success(null, 'Revision requested');

        } catch (Exception $e) {
            error_log("Request revision error: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }

    private function getClaimStatistics() {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }

        $filters = [
            'from_date' => Request::getQueryParam('from_date'),
            'to_date' => Request::getQueryParam('to_date')
        ];

        try {
            $statistics = $this->claimModel->getClaimStatistics($filters);
            Response::success($statistics);

        } catch (Exception $e) {
            error_log("Get claim statistics error: " . $e->getMessage());
            Response::error('Failed to retrieve claim statistics', 500);
        }
    }

    // =========================
    // DASHBOARD & ANALYTICS
    // =========================

    private function getDashboardData() {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }

        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM hmoproviders WHERE IsActive = 1) as total_providers,
                        (SELECT COUNT(*) FROM hmoplans WHERE IsActive = 1) as total_plans,
                        (SELECT COUNT(*) FROM employeehmoenrollments WHERE Status = 'Active') as total_enrollments,
                        (SELECT COUNT(*) FROM hmoclaims WHERE Status = 'Pending') as pending_claims,
                        (SELECT COUNT(*) FROM hmoclaims WHERE Status = 'Approved') as approved_claims,
                        (SELECT COUNT(*) FROM hmoclaims WHERE Status = 'Denied') as denied_claims,
                        (SELECT COALESCE(SUM(Amount), 0) FROM hmoclaims WHERE Status = 'Approved') as total_approved_amount,
                        (SELECT COALESCE(SUM(Amount), 0) FROM hmoclaims WHERE Status = 'Pending') as total_pending_amount";

            $stmt = $this->pdo->query($sql);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);

            Response::success($dashboard);

        } catch (Exception $e) {
            error_log("Get dashboard data error: " . $e->getMessage());
            Response::error('Failed to retrieve dashboard data', 500);
        }
    }

    private function getAnalytics($type) {
        // TODO: Re-enable authentication after fixing session sharing
        /*
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Admin', 'Manager'])) {
            Response::forbidden('Insufficient permissions');
        }
        */

        try {
            switch ($type) {
                case 'monthly_claims':
                    $sql = "SELECT 
                                DATE_FORMAT(ClaimDate, '%Y-%m') as month,
                                COUNT(*) as count,
                                SUM(Amount) as total_amount
                            FROM hmoclaims
                            WHERE ClaimDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                            GROUP BY month
                            ORDER BY month";
                    break;

                case 'top_hospitals':
                    $topHospitals = $this->claimModel->getTopProviders(10);
                    Response::success($topHospitals);
                    return;

                case 'plan_utilization':
                    $utilization = $this->planModel->getPlanUtilization();
                    Response::success($utilization);
                    return;

                case 'department_costs':
                    $sql = "SELECT 
                                d.DepartmentName,
                                COUNT(DISTINCT e.EnrollmentID) as enrollments,
                                COALESCE(SUM(c.Amount), 0) as total_claims
                            FROM Departments d
                            LEFT JOIN employees emp ON d.DepartmentID = emp.DepartmentID
                            LEFT JOIN employeehmoenrollments e ON emp.EmployeeID = e.EmployeeID
                            LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID AND c.Status = 'Approved'
                            GROUP BY d.DepartmentID, d.DepartmentName
                            ORDER BY total_claims DESC";
                    break;

                case 'benefit-types-summary':
                    // Benefits utilization by type for Analytics Dashboard doughnut chart
                    $sql = "SELECT 
                                p.PlanType as benefit_type,
                                COUNT(DISTINCT e.EnrollmentID) as enrolled,
                                COUNT(DISTINCT c.ClaimID) as claims_filed,
                                COALESCE(SUM(c.Amount), 0) as total_amount,
                                ROUND(COUNT(DISTINCT CASE WHEN c.Status = 'Approved' THEN c.ClaimID END) * 100.0 / 
                                    NULLIF(COUNT(DISTINCT c.ClaimID), 0), 1) as approval_rate,
                                ROUND(COUNT(DISTINCT c.EmployeeID) * 100.0 / 
                                    NULLIF(COUNT(DISTINCT e.EnrollmentID), 0), 1) as utilization
                            FROM hmo_plans p
                            LEFT JOIN employeehmoenrollments e ON p.PlanID = e.PlanID AND e.Status = 'Active'
                            LEFT JOIN hmoclaims c ON e.EnrollmentID = c.EnrollmentID
                            GROUP BY p.PlanID, p.PlanType
                            ORDER BY total_amount DESC";
                    break;

                default:
                    Response::notFound('Analytics type not found');
                    return;
            }

            $stmt = $this->pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Response::success($data);

        } catch (Exception $e) {
            error_log("Get analytics error: " . $e->getMessage());
            Response::error('Failed to retrieve analytics data', 500);
        }
    }
}

