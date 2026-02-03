<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

class BranchesController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function handleRequest($method, $id = null, $subResource = null) {
        // Temporarily bypass authentication for testing
        // TODO: Re-enable authentication once session sharing is fixed

        switch ($method) {
            case 'GET':
                $this->handleGet($id, $subResource);
                break;
            case 'OPTIONS':
                Response::success('OK', ['methods' => ['GET', 'OPTIONS']]);
                break;
            default:
                Response::methodNotAllowed();
        }
    }

    private function handleGet($id, $subResource) {
        if ($id === null) {
            // Get all branches
            $this->getAllBranches();
        } else {
            // Get specific branch
            $this->getBranch($id);
        }
    }

    private function getAllBranches() {
        try {
            global $pdo;
            
            $sql = "SELECT BranchID, BranchName, BranchCode, IsActive, CreatedAt 
                    FROM hospital_branches 
                    WHERE IsActive = 1 
                    ORDER BY BranchName";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::success($branches, 'Branches retrieved successfully');
            
        } catch (Exception $e) {
            Response::error('Failed to retrieve branches: ' . $e->getMessage(), 500);
        }
    }

    private function getBranch($branchId) {
        try {
            global $pdo;
            
            $sql = "SELECT BranchID, BranchName, BranchCode, IsActive, CreatedAt 
                    FROM hospital_branches 
                    WHERE BranchID = :branch_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
            $stmt->execute();
            $branch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$branch) {
                Response::notFound('Branch not found');
                return;
            }
            
            Response::success($branch, 'Branch retrieved successfully');
            
        } catch (Exception $e) {
            Response::error('Failed to retrieve branch: ' . $e->getMessage(), 500);
        }
    }
}
