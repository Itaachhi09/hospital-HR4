<?php
/**
 * Positions Routes
 * - GET /api/positions/summary?department_id=
 * - GET /api/positions/paygrades
 */
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../models/Position.php';

class PositionsController {
    private $pdo; private $auth; private $positionModel;
    public function __construct(){ global $pdo; $this->pdo = $pdo; $this->auth = new AuthMiddleware(); $this->positionModel = new Position(); }

    public function handleRequest($method, $id = null, $subResource = null){
        if ($method !== 'GET') { Response::methodNotAllowed(); }
        if (!$this->auth->authenticate()) { Response::unauthorized('Authentication required'); }
        $request = new Request();
        if ($id === 'summary') {
            $dept = $request->getData('department_id');
            $rows = $this->positionModel->getDepartmentPositionSummary($dept ? (int)$dept : null);
            Response::success($rows);
        }
        if ($id === 'paygrades') {
            $rows = $this->positionModel->getPayGradeMapping();
            Response::success($rows);
        }
        Response::notFound();
    }
}
