<?php
/**
 * HMO Routes
 * Handles HMO management operations
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/HMO.php';

class HMOController {
    private $pdo;
    private $authMiddleware;
    private $hmoModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
        $this->hmoModel = new HMO();
    }

    /**
     * Handle HMO requests
     */
    public function handleRequest($method, $id = null, $subResource = null) {
        // Normalize segments: if $id is a resource name (providers, plans, enrollments)
        $segment1 = $id;
        $segment2 = $subResource;

        // Helper to check numeric id
        $isNumericId = is_numeric($segment1);

        switch ($method) {
            case 'GET':
                if ($segment1 === null) {
                    // GET /api/hmo -> list plans
                    $this->getHMOPlans();
                } elseif ($segment1 === 'providers') {
                    if ($segment2 !== null && is_numeric($segment2)) {
                        $this->getHMOProvider((int)$segment2);
                    } else {
                        $this->getHMOProviders();
                    }
                } elseif ($segment1 === 'enrollments') {
                    if ($segment2 !== null && is_numeric($segment2)) {
                        // treat as specific enrollment id (not commonly used)
                        $this->getHMOEnrollments();
                    } else {
                        $this->getHMOEnrollments();
                    }
                } elseif ($segment1 === 'plans') {
                    if ($segment2 !== null && is_numeric($segment2)) {
                        $this->getHMOPlan((int)$segment2);
                    } else {
                        $this->getHMOPlans();
                    }
                } elseif ($isNumericId) {
                    // GET /api/hmo/{id}
                    $this->getHMOPlan((int)$segment1);
                } else {
                    Response::notFound();
                }
                break;

            case 'POST':
                if ($segment1 === 'providers') {
                    $this->createHMOProvider();
                } elseif ($segment1 === 'enrollments') {
                    $this->createHMOEnrollment();
                } elseif ($segment1 === 'plans' || $segment1 === null) {
                    $this->createHMOPlan();
                } else {
                    Response::methodNotAllowed();
                }
                break;

            case 'PUT':
            case 'PATCH':
                if ($segment1 === 'providers' && is_numeric($segment2)) {
                    $this->updateHMOProvider((int)$segment2);
                } elseif ($segment1 === 'enrollments' && is_numeric($segment2)) {
                    $this->updateHMOEnrollment((int)$segment2);
                } elseif (($segment1 === 'plans' && is_numeric($segment2)) || $isNumericId) {
                    $planId = $isNumericId ? (int)$segment1 : (int)$segment2;
                    $this->updateHMOPlan($planId);
                } else {
                    Response::methodNotAllowed();
                }
                break;

            case 'DELETE':
                if ($segment1 === 'providers' && is_numeric($segment2)) {
                    $this->deleteHMOProvider((int)$segment2);
                } elseif ($segment1 === 'enrollments' && is_numeric($segment2)) {
                    $this->terminateHMOEnrollment((int)$segment2);
                } elseif (($segment1 === 'plans' && is_numeric($segment2)) || $isNumericId) {
                    $planId = $isNumericId ? (int)$segment1 : (int)$segment2;
                    $this->deleteHMOPlan($planId);
                } else {
                    Response::methodNotAllowed();
                }
                break;

            default:
                Response::methodNotAllowed();
        }
    }

    /**
     * Get single HMO provider
     */
    private function getHMOProvider($id) {
        try {
            $provider = $this->hmoModel->getProvider((int)$id);
            
            if (!$provider) {
                Response::notFound('HMO provider not found');
                return;
            }

            Response::success($provider);

        } catch (Exception $e) {
            error_log("Get HMO provider error: " . $e->getMessage());
            Response::error('Failed to retrieve HMO provider', 500);
        }
    }

    /**
     * Get all HMO plans
     */
    private function getHMOPlans() {
        $request = new Request();
        $pagination = $request->getPagination();
        
        $filters = [
            'provider_id' => $request->getData('provider_id'),
            'is_active' => $request->getData('is_active')
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            $hmoPlans = $this->hmoModel->listPlans(
                $pagination['page'],
                $pagination['limit'],
                $filters
            );

            Response::success($hmoPlans);

        } catch (Exception $e) {
            error_log("Get HMO plans error: " . $e->getMessage());
            Response::error('Failed to retrieve HMO plans', 500);
        }
    }

    /**
     * Get single HMO plan
     */
    private function getHMOPlan($id) {
        try {
            $plan = $this->hmoModel->getPlan((int)$id);
            
            if (!$plan) {
                Response::notFound('HMO plan not found');
                return;
            }

            Response::success($plan);

        } catch (Exception $e) {
            error_log("Get HMO plan error: " . $e->getMessage());
            Response::error('Failed to retrieve HMO plan', 500);
        }
    }

    /**
     * Get HMO providers
     */
    private function getHMOProviders() {
        try {
            $request = new Request();
            $activeOnly = $request->getData('active_only', true);
            $providers = $this->hmoModel->listProviders((bool)$activeOnly);
            Response::success($providers);

        } catch (Exception $e) {
            error_log("Get HMO providers error: " . $e->getMessage());
            Response::error('Failed to retrieve HMO providers', 500);
        }
    }

    /**
     * Get HMO enrollments
     */
    private function getHMOEnrollments() {
        $request = new Request();
        $pagination = $request->getPagination();
        
        $filters = [
            'employee_id' => $request->getData('employee_id'),
            'status' => $request->getData('status')
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            $enrollments = $this->hmoModel->listEnrollments(
                $pagination['page'],
                $pagination['limit'],
                $filters
            );

            Response::success($enrollments);

        } catch (Exception $e) {
            error_log("Get HMO enrollments error: " . $e->getMessage());
            Response::error('Failed to retrieve HMO enrollments', 500);
        }
    }

    /**
     * Create new HMO plan
     */
    private function createHMOPlan() {
        Response::success([], 'Create HMO plan endpoint - implementation pending');
        // Check authorization - only admins and HR can create HMO plans
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to create HMO plans');
        }

        $request = new Request();
        $data = $request->getData();

        $errors = $request->validateRequired(['provider_id', 'plan_name', 'monthly_premium']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $planData = [
                'provider_id' => (int)$data['provider_id'],
                'plan_name' => $data['plan_name'],
                'description' => $data['description'] ?? null,
                'coverage_type' => $data['coverage_type'] ?? 'Comprehensive',
                'monthly_premium' => $data['monthly_premium'],
                'annual_limit' => $data['annual_limit'] ?? null,
                'room_board_limit' => $data['room_board_limit'] ?? null,
                'doctor_visit_limit' => $data['doctor_visit_limit'] ?? null,
                'emergency_limit' => $data['emergency_limit'] ?? null,
                'coverage_limit' => $data['coverage_limit'] ?? $data['annual_limit'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'effective_date' => $data['effective_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ];

            $planId = $this->hmoModel->createPlan($planData);

            Response::created([
                'plan_id' => $planId,
                'plan_name' => $planData['plan_name']
            ], 'HMO plan created successfully');

        } catch (Exception $e) {
            error_log("Create HMO plan error: " . $e->getMessage());
            Response::error('Failed to create HMO plan', 500);
        }
    }

    /**
     * Create new HMO enrollment
     */
    private function createHMOEnrollment() {
        // Check authorization - only admins and HR can create HMO enrollments
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to create HMO enrollments');
        }

        $request = new Request();
        $data = $request->getData();

        $errors = $request->validateRequired(['employee_id', 'plan_id', 'monthly_deduction']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $enrollmentData = [
                'employee_id' => (int)$data['employee_id'],
                'plan_id' => (int)$data['plan_id'],
                'status' => 'Active',
                'monthly_deduction' => $data['monthly_deduction'],
                'enrollment_date' => isset($data['enrollment_date']) ? $data['enrollment_date'] : date('Y-m-d'),
                'effective_date' => isset($data['effective_date']) ? $data['effective_date'] : date('Y-m-d')
            ];

            $enrollmentId = $this->hmoModel->createEnrollment($enrollmentData);

            Response::created([
                'enrollment_id' => $enrollmentId,
                'employee_id' => $enrollmentData['employee_id'],
                'plan_id' => $enrollmentData['plan_id']
            ], 'HMO enrollment created successfully');

        } catch (Exception $e) {
            error_log("Create HMO enrollment error: " . $e->getMessage());
            Response::error('Failed to create HMO enrollment', 500);
        }
    }

    /**
     * Update HMO plan
     */
    private function updateHMOPlan($id) {
        // Check authorization
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to update HMO plans');
        }

        $request = new Request();
        $data = $request->getData();

        $errors = $request->validateRequired(['provider_id', 'plan_name', 'monthly_premium']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $planData = [
                'provider_id' => (int)$data['provider_id'],
                'plan_name' => $data['plan_name'],
                'description' => $data['description'] ?? null,
                'coverage_type' => $data['coverage_type'] ?? 'Comprehensive',
                'monthly_premium' => $data['monthly_premium'],
                'annual_limit' => $data['annual_limit'] ?? null,
                'room_board_limit' => $data['room_board_limit'] ?? null,
                'doctor_visit_limit' => $data['doctor_visit_limit'] ?? null,
                'emergency_limit' => $data['emergency_limit'] ?? null,
                'coverage_limit' => $data['coverage_limit'] ?? $data['annual_limit'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'effective_date' => $data['effective_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ];

            $success = $this->hmoModel->updatePlan((int)$id, $planData);

            if ($success) {
                Response::success([
                    'plan_id' => (int)$id,
                    'plan_name' => $planData['plan_name']
                ], 'HMO plan updated successfully');
            } else {
                Response::error('Failed to update HMO plan', 500);
            }

        } catch (Exception $e) {
            error_log("Update HMO plan error: " . $e->getMessage());
            Response::error('Failed to update HMO plan', 500);
        }
    }

    /**
     * Delete HMO plan
     */
    

    /**
     * Delete HMO plan (soft delete)
     */
    private function deleteHMOPlan($id) {
        // Check authorization - only admins and HR can delete HMO plans
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to delete HMO plans');
        }

        try {
            // Check if plan exists
                $existingPlan = $this->hmoModel->getPlan((int)$id);
            if (!$existingPlan) {
                Response::notFound('HMO plan not found');
                return;
            }

                $success = $this->hmoModel->softDeletePlan((int)$id);

            if ($success) {
                Response::success([
                    'plan_id' => $id
                ], 'HMO plan deleted successfully');
            } else {
                Response::error('Failed to delete HMO plan', 500);
            }

        } catch (Exception $e) {
            error_log("Delete HMO plan error: " . $e->getMessage());
            Response::error('Failed to delete HMO plan', 500);
        }
    }

    /**
     * Create HMO provider
     */
    private function createHMOProvider() {
        // Check authorization - only admins and HR can create HMO providers
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to create HMO providers');
        }

        $request = new Request();
        $data = $request->getData();

        $errors = $request->validateRequired(['provider_name']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $providerData = [
                'provider_name' => $data['provider_name'],
                'contact_person' => $data['contact_person'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'phone_number' => $data['phone_number'] ?? $data['contact_phone'] ?? null,
                'email' => $data['email'] ?? $data['contact_email'] ?? null,
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'is_active' => $data['is_active'] ?? 1
            ];

                $providerId = $this->hmoModel->createProvider($providerData);

            Response::created([
                'provider_id' => $providerId,
                'provider_name' => $providerData['provider_name']
            ], 'HMO provider created successfully');

        } catch (Exception $e) {
            error_log("Create HMO provider error: " . $e->getMessage());
            Response::error('Failed to create HMO provider', 500);
        }
    }

    /**
     * Update HMO provider
     */
    private function updateHMOProvider($id) {
        // Check authorization - only admins and HR can update HMO providers
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to update HMO providers');
        }

        $request = new Request();
        $data = $request->getData();

        $errors = $request->validateRequired(['provider_name']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            // Check if provider exists
                $existingProvider = $this->hmoModel->getProvider((int)$id);
            if (!$existingProvider) {
                Response::notFound('HMO provider not found');
                return;
            }

            $providerData = [
                'provider_name' => $data['provider_name'],
                'contact_person' => $data['contact_person'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'phone_number' => $data['phone_number'] ?? $data['contact_phone'] ?? null,
                'email' => $data['email'] ?? $data['contact_email'] ?? null,
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'is_active' => $data['is_active'] ?? 1
            ];

                $success = $this->hmoModel->updateProvider((int)$id, $providerData);

            if ($success) {
                Response::success([
                    'provider_id' => $id,
                    'provider_name' => $providerData['provider_name']
                ], 'HMO provider updated successfully');
            } else {
                Response::error('Failed to update HMO provider', 500);
            }

        } catch (Exception $e) {
            error_log("Update HMO provider error: " . $e->getMessage());
            Response::error('Failed to update HMO provider', 500);
        }
    }

    /**
     * Delete HMO provider (soft delete)
     */
    private function deleteHMOProvider($id) {
        // Check authorization - only admins and HR can delete HMO providers
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to delete HMO providers');
        }

        try {
            // Check if provider exists
                $existingProvider = $this->hmoModel->getProvider((int)$id);
            if (!$existingProvider) {
                Response::notFound('HMO provider not found');
                return;
            }

                $success = $this->hmoModel->softDeleteProvider((int)$id);

            if ($success) {
                Response::success([
                    'provider_id' => $id
                ], 'HMO provider deleted successfully');
            } else {
                Response::error('Failed to delete HMO provider', 500);
            }

        } catch (Exception $e) {
            error_log("Delete HMO provider error: " . $e->getMessage());
            Response::error('Failed to delete HMO provider', 500);
        }
    }

    /**
     * Update HMO enrollment
     */
    private function updateHMOEnrollment($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to update enrollments');
        }

        $request = new Request();
        $data = $request->getData();

        try {
            $success = $this->hmoModel->updateEnrollment((int)$id, $data);
            if ($success) {
                Response::success(['enrollment_id' => (int)$id], 'Enrollment updated');
            } else {
                Response::error('Failed to update enrollment', 500);
            }
        } catch (Exception $e) {
            error_log('Update enrollment error: '.$e->getMessage());
            Response::error('Failed to update enrollment', 500);
        }
    }

    /**
     * Terminate HMO enrollment
     */
    private function terminateHMOEnrollment($id) {
        if (!$this->authMiddleware->hasAnyRole(['System Admin', 'HR Manager'])) {
            Response::forbidden('Insufficient permissions to terminate enrollments');
        }

        try {
            $success = $this->hmoModel->terminateEnrollment((int)$id);
            if ($success) {
                Response::success(['enrollment_id' => (int)$id], 'Enrollment terminated');
            } else {
                Response::error('Failed to terminate enrollment', 500);
            }
        } catch (Exception $e) {
            error_log('Terminate enrollment error: '.$e->getMessage());
            Response::error('Failed to terminate enrollment', 500);
        }
    }
}
