<?php
/**
 * HR Analytics Metrics API Routes
 * RESTful API endpoints for HR metrics framework
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../integrations/HRAnalyticsMetricsFramework.php';
require_once __DIR__ . '/../integrations/HRAnalyticsMetricsStorage.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../utils/Response.php';

class HRAnalyticsMetricsController {
    private $pdo;
    private $authMiddleware;
    private $metricsFramework;
    private $metricsStorage;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authMiddleware = new AuthMiddleware();
        $this->metricsFramework = new HRAnalyticsMetricsFramework();
        $this->metricsStorage = new HRAnalyticsMetricsStorage();
    }

    public function handleRequest($method, $id, $subResource) {
        // Authenticate user
        if (!$this->authMiddleware->authenticate()) {
            Response::error('Authentication required', 401);
            return;
        }

        switch ($method) {
            case 'GET':
                $this->handleGet($id, $subResource);
                break;
            case 'POST':
                $this->handlePost($id, $subResource);
                break;
            case 'PUT':
                $this->handlePut($id, $subResource);
                break;
            case 'DELETE':
                $this->handleDelete($id, $subResource);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
    }

    private function handleGet($id, $subResource) {
        try {
            switch ($id) {
                case 'categories':
                    $this->getMetricCategories();
                    break;
                case 'definitions':
                    $this->getMetricDefinitions($subResource);
                    break;
                case 'calculate':
                    $this->calculateMetric($subResource);
                    break;
                case 'trends':
                    $this->getMetricTrends($subResource);
                    break;
                case 'summary':
                    $this->getMetricsSummary($subResource);
                    break;
                case 'dashboard':
                    $this->getDashboardMetrics($subResource);
                    break;
                case 'performance':
                    $this->getPerformanceStats();
                    break;
                case 'alerts':
                    $this->getMetricAlerts($subResource);
                    break;
                case 'export':
                    $this->exportMetrics($subResource);
                    break;
                default:
                    Response::notFound('Resource not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to retrieve data: ' . $e->getMessage());
        }
    }

    private function handlePost($id, $subResource) {
        $input = Request::getJsonBody();
        if (!$input) {
            Response::error('Invalid JSON input', 400);
            return;
        }

        try {
            switch ($id) {
                case 'calculate':
                    $this->calculateMetrics($input);
                    break;
                case 'batch-calculate':
                    $this->batchCalculateMetrics($input);
                    break;
                case 'cache':
                    $this->warmUpCache($input);
                    break;
                case 'alert':
                    $this->createMetricAlert($input);
                    break;
                case 'dashboard':
                    $this->saveDashboardConfig($input);
                    break;
                default:
                    Response::notFound('Action not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to process request: ' . $e->getMessage());
        }
    }

    private function handlePut($id, $subResource) {
        $input = Request::getJsonBody();
        if (!$input) {
            Response::error('Invalid JSON input', 400);
            return;
        }

        try {
            switch ($id) {
                case 'alert':
                    $this->updateMetricAlert($subResource, $input);
                    break;
                case 'dashboard':
                    $this->updateDashboardConfig($subResource, $input);
                    break;
                default:
                    Response::notFound('Action not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to update: ' . $e->getMessage());
        }
    }

    private function handleDelete($id, $subResource) {
        try {
            switch ($id) {
                case 'alert':
                    $this->deleteMetricAlert($subResource);
                    break;
                case 'dashboard':
                    $this->deleteDashboardConfig($subResource);
                    break;
                case 'cache':
                    $this->clearCache($subResource);
                    break;
                default:
                    Response::notFound('Action not found');
            }
        } catch (Exception $e) {
            Response::error('Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Get all metric categories
     */
    private function getMetricCategories() {
        $categories = $this->metricsFramework->getMetricCategories();
        
        $categoryDetails = [];
        foreach ($categories as $category) {
            $metrics = $this->metricsFramework->getMetricsForCategory($category);
            $categoryDetails[] = [
                'category' => $category,
                'metric_count' => count($metrics),
                'metrics' => $metrics
            ];
        }
        
        Response::success('Metric categories retrieved successfully', $categoryDetails);
    }

    /**
     * Get metric definitions
     */
    private function getMetricDefinitions($subResource) {
        if ($subResource) {
            // Get specific metric definition
            $parts = explode('/', $subResource);
            if (count($parts) >= 2) {
                $category = $parts[0];
                $metricName = $parts[1];
                
                $definition = $this->metricsFramework->getMetricDefinition($category, $metricName);
                if ($definition) {
                    Response::success('Metric definition retrieved successfully', $definition);
                } else {
                    Response::notFound('Metric definition not found');
                }
            } else {
                Response::error('Invalid metric path');
            }
        } else {
            // Get all metric definitions
            $definitions = $this->metricsFramework->getAllMetricDefinitions();
            Response::success('All metric definitions retrieved successfully', $definitions);
        }
    }

    /**
     * Calculate a specific metric
     */
    private function calculateMetric($subResource) {
        $filters = Request::getQueryParams();
        $filters = $this->metricsFramework->validateFilters($filters);
        
        if (!$subResource) {
            Response::error('Metric path required');
            return;
        }
        
        $parts = explode('/', $subResource);
        if (count($parts) < 2) {
            Response::error('Invalid metric path');
            return;
        }
        
        $category = $parts[0];
        $metricName = $parts[1];
        
        // Check cache first
        $cacheKey = $this->metricsStorage->generateCacheKey($category, $metricName, $filters);
        $cachedData = $this->metricsStorage->getCachedMetric($cacheKey);
        
        if ($cachedData) {
            Response::success('Metric calculated successfully (cached)', $cachedData);
            return;
        }
        
        // Calculate metric
        $result = $this->metricsFramework->calculateMetric($category, $metricName, $filters);
        
        // Cache the result
        $this->metricsStorage->setCachedMetric($cacheKey, $result);
        
        Response::success('Metric calculated successfully', $result);
    }

    /**
     * Calculate multiple metrics
     */
    private function calculateMetrics($input) {
        $category = $input['category'] ?? null;
        $filters = $this->metricsFramework->validateFilters($input['filters'] ?? []);
        
        if ($category) {
            $results = $this->metricsFramework->calculateMetrics($category, $filters);
        } else {
            $results = $this->metricsFramework->calculateAllMetrics($filters);
        }
        
        Response::success('Metrics calculated successfully', $results);
    }

    /**
     * Batch calculate metrics
     */
    private function batchCalculateMetrics($input) {
        $metrics = $input['metrics'] ?? [];
        $filters = $this->metricsFramework->validateFilters($input['filters'] ?? []);
        
        $results = [];
        foreach ($metrics as $metric) {
            $category = $metric['category'];
            $metricName = $metric['metric_name'];
            
            try {
                $result = $this->metricsFramework->calculateMetric($category, $metricName, $filters);
                $results[$category][$metricName] = $result;
            } catch (Exception $e) {
                $results[$category][$metricName] = [
                    'error' => $e->getMessage(),
                    'value' => null
                ];
            }
        }
        
        Response::success('Batch metrics calculated successfully', $results);
    }

    /**
     * Get metric trends
     */
    private function getMetricTrends($subResource) {
        if (!$subResource) {
            Response::error('Metric path required');
            return;
        }
        
        $parts = explode('/', $subResource);
        if (count($parts) < 2) {
            Response::error('Invalid metric path');
            return;
        }
        
        $category = $parts[0];
        $metricName = $parts[1];
        $metricId = $category . '.' . $metricName;
        
        $periods = Request::getQueryParam('periods', 12);
        $trends = $this->metricsStorage->getMetricTrends($metricId, $periods);
        
        Response::success('Metric trends retrieved successfully', $trends);
    }

    /**
     * Get metrics summary
     */
    private function getMetricsSummary($subResource) {
        $category = $subResource;
        $period = Request::getQueryParam('period', 'current');
        
        $summary = $this->metricsStorage->getMetricsSummary($category, $period);
        
        Response::success('Metrics summary retrieved successfully', $summary);
    }

    /**
     * Get dashboard metrics
     */
    private function getDashboardMetrics($subResource) {
        $dashboardName = $subResource ?: 'default';
        $userId = $_SESSION['user_id'] ?? null;
        
        // Get dashboard configuration
        $sql = "SELECT config FROM metrics_dashboard_config 
                WHERE dashboard_name = :dashboard_name 
                AND (user_id = :user_id OR is_public = TRUE)
                ORDER BY is_public ASC, updated_at DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dashboard_name', $dashboardName);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            Response::notFound('Dashboard configuration not found');
            return;
        }
        
        $dashboardConfig = json_decode($config['config'], true);
        $metrics = $dashboardConfig['metrics'] ?? [];
        
        $results = [];
        foreach ($metrics as $metric) {
            $category = $metric['category'];
            $metricName = $metric['metric_name'];
            $filters = $metric['filters'] ?? [];
            
            try {
                $result = $this->metricsFramework->calculateMetric($category, $metricName, $filters);
                $results[] = [
                    'category' => $category,
                    'metric_name' => $metricName,
                    'data' => $result,
                    'position' => $metric['position'] ?? null
                ];
            } catch (Exception $e) {
                $results[] = [
                    'category' => $category,
                    'metric_name' => $metricName,
                    'error' => $e->getMessage(),
                    'position' => $metric['position'] ?? null
                ];
            }
        }
        
        Response::success('Dashboard metrics retrieved successfully', $results);
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats() {
        $stats = $this->metricsStorage->getPerformanceStats();
        
        Response::success('Performance statistics retrieved successfully', $stats);
    }

    /**
     * Get metric alerts
     */
    private function getMetricAlerts($subResource) {
        $sql = "SELECT * FROM metrics_alerts WHERE 1=1";
        $params = [];
        
        if ($subResource) {
            $sql .= " AND metric_id = :metric_id";
            $params[':metric_id'] = $subResource;
        }
        
        $sql .= " ORDER BY severity DESC, created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success('Metric alerts retrieved successfully', $alerts);
    }

    /**
     * Export metrics
     */
    private function exportMetrics($subResource) {
        $format = Request::getQueryParam('format', 'json');
        $metrics = Request::getQueryParam('metrics', '');
        $filters = Request::getQueryParams();
        
        if (empty($metrics)) {
            Response::error('Metrics parameter required');
            return;
        }
        
        $metricsList = explode(',', $metrics);
        $results = [];
        
        foreach ($metricsList as $metric) {
            $parts = explode('.', $metric);
            if (count($parts) >= 2) {
                $category = $parts[0];
                $metricName = $parts[1];
                
                try {
                    $result = $this->metricsFramework->calculateMetric($category, $metricName, $filters);
                    $results[$metric] = $result;
                } catch (Exception $e) {
                    $results[$metric] = ['error' => $e->getMessage()];
                }
            }
        }
        
        // Log export activity
        $this->logExportActivity($format, $metricsList, $filters);
        
        Response::success('Metrics exported successfully', $results);
    }

    /**
     * Warm up cache
     */
    private function warmUpCache($input) {
        $metrics = $input['metrics'] ?? [];
        
        $this->metricsStorage->warmUpCache($metrics);
        
        Response::success('Cache warmed up successfully');
    }

    /**
     * Create metric alert
     */
    private function createMetricAlert($input) {
        $requiredFields = ['metric_id', 'alert_name', 'condition_type', 'condition_value', 'operator', 'severity'];
        
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                Response::error("Missing required field: $field");
                return;
            }
        }
        
        $sql = "INSERT INTO metrics_alerts 
                (metric_id, alert_name, condition_type, condition_value, operator, severity) 
                VALUES (:metric_id, :alert_name, :condition_type, :condition_value, :operator, :severity)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $input['metric_id']);
        $stmt->bindValue(':alert_name', $input['alert_name']);
        $stmt->bindValue(':condition_type', $input['condition_type']);
        $stmt->bindValue(':condition_value', $input['condition_value']);
        $stmt->bindValue(':operator', $input['operator']);
        $stmt->bindValue(':severity', $input['severity']);
        
        if ($stmt->execute()) {
            Response::success('Metric alert created successfully');
        } else {
            Response::error('Failed to create metric alert');
        }
    }

    /**
     * Update metric alert
     */
    private function updateMetricAlert($alertId, $input) {
        $sql = "UPDATE metrics_alerts SET 
                alert_name = :alert_name,
                condition_type = :condition_type,
                condition_value = :condition_value,
                operator = :operator,
                severity = :severity,
                is_active = :is_active
                WHERE id = :alert_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':alert_id', $alertId);
        $stmt->bindValue(':alert_name', $input['alert_name'] ?? '');
        $stmt->bindValue(':condition_type', $input['condition_type'] ?? '');
        $stmt->bindValue(':condition_value', $input['condition_value'] ?? 0);
        $stmt->bindValue(':operator', $input['operator'] ?? '');
        $stmt->bindValue(':severity', $input['severity'] ?? '');
        $stmt->bindValue(':is_active', $input['is_active'] ?? true);
        
        if ($stmt->execute()) {
            Response::success('Metric alert updated successfully');
        } else {
            Response::error('Failed to update metric alert');
        }
    }

    /**
     * Delete metric alert
     */
    private function deleteMetricAlert($alertId) {
        $sql = "DELETE FROM metrics_alerts WHERE id = :alert_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':alert_id', $alertId);
        
        if ($stmt->execute()) {
            Response::success('Metric alert deleted successfully');
        } else {
            Response::error('Failed to delete metric alert');
        }
    }

    /**
     * Save dashboard configuration
     */
    private function saveDashboardConfig($input) {
        $userId = $_SESSION['user_id'] ?? null;
        $dashboardName = $input['dashboard_name'] ?? 'default';
        $config = $input['config'] ?? [];
        $isPublic = $input['is_public'] ?? false;
        
        $sql = "INSERT INTO metrics_dashboard_config 
                (dashboard_name, user_id, config, is_public) 
                VALUES (:dashboard_name, :user_id, :config, :is_public)
                ON DUPLICATE KEY UPDATE 
                config = VALUES(config),
                is_public = VALUES(is_public),
                updated_at = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dashboard_name', $dashboardName);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':config', json_encode($config));
        $stmt->bindValue(':is_public', $isPublic);
        
        if ($stmt->execute()) {
            Response::success('Dashboard configuration saved successfully');
        } else {
            Response::error('Failed to save dashboard configuration');
        }
    }

    /**
     * Update dashboard configuration
     */
    private function updateDashboardConfig($dashboardId, $input) {
        $sql = "UPDATE metrics_dashboard_config SET 
                dashboard_name = :dashboard_name,
                config = :config,
                is_public = :is_public,
                updated_at = NOW()
                WHERE id = :dashboard_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dashboard_id', $dashboardId);
        $stmt->bindValue(':dashboard_name', $input['dashboard_name'] ?? '');
        $stmt->bindValue(':config', json_encode($input['config'] ?? []));
        $stmt->bindValue(':is_public', $input['is_public'] ?? false);
        
        if ($stmt->execute()) {
            Response::success('Dashboard configuration updated successfully');
        } else {
            Response::error('Failed to update dashboard configuration');
        }
    }

    /**
     * Delete dashboard configuration
     */
    private function deleteDashboardConfig($dashboardId) {
        $sql = "DELETE FROM metrics_dashboard_config WHERE id = :dashboard_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dashboard_id', $dashboardId);
        
        if ($stmt->execute()) {
            Response::success('Dashboard configuration deleted successfully');
        } else {
            Response::error('Failed to delete dashboard configuration');
        }
    }

    /**
     * Clear cache
     */
    private function clearCache($pattern) {
        $deleted = $this->metricsStorage->clearCache($pattern);
        
        Response::success("Cache cleared successfully. $deleted items deleted.");
    }

    /**
     * Log export activity
     */
    private function logExportActivity($format, $metrics, $filters) {
        $userId = $_SESSION['user_id'] ?? null;
        
        $sql = "INSERT INTO metrics_export_log 
                (user_id, export_type, metrics_requested, filters, export_status) 
                VALUES (:user_id, :export_type, :metrics_requested, :filters, 'success')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':export_type', $format);
        $stmt->bindValue(':metrics_requested', json_encode($metrics));
        $stmt->bindValue(':filters', json_encode($filters));
        
        $stmt->execute();
    }
}
?>
