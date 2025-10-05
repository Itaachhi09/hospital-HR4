<?php
/**
 * Org Structure Routes (REST)
 * - GET /api/org-structure => hierarchical org data
 */

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Department.php';

class OrgStructureController {
    private $pdo;
    private $auth;
    private $departmentModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware();
        $this->departmentModel = new Department();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        switch ($method) {
            case 'GET':
                return $this->getHierarchy();
            default:
                return Response::methodNotAllowed();
        }
    }

    private function getHierarchy() {
        if (!$this->auth->authenticate()) {
            Response::unauthorized('Authentication required');
        }
        $data = $this->departmentModel->getHospitalOrgHierarchy();
        Response::success($data);
    }
}
