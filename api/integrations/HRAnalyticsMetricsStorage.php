<?php
/**
 * HR Analytics Metrics Storage & Caching System
 * Handles pre-aggregated data storage and performance optimization
 */

require_once __DIR__ . '/../config.php';

class HRAnalyticsMetricsStorage {
    private $pdo;
    private $cache;
    private $cacheExpiry = 3600; // 1 hour

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->initializeCache();
    }

    /**
     * Initialize cache system
     */
    private function initializeCache() {
        // Use Redis if available, otherwise use file-based cache
        if (class_exists('Redis')) {
            try {
                $this->cache = new Redis();
                $this->cache->connect('127.0.0.1', 6379);
            } catch (Exception $e) {
                $this->cache = new FileCache();
            }
        } else {
            $this->cache = new FileCache();
        }
    }

    /**
     * Store pre-aggregated metric data
     */
    public function storeMetricData($metricId, $category, $metricName, $value, $period, $filters = []) {
        $sql = "INSERT INTO metrics_summary 
                (metric_id, category, metric_name, value, period, filters, last_updated) 
                VALUES (:metric_id, :category, :metric_name, :value, :period, :filters, NOW())
                ON DUPLICATE KEY UPDATE 
                value = VALUES(value), 
                last_updated = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $metricId);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':metric_name', $metricName);
        $stmt->bindValue(':value', json_encode($value));
        $stmt->bindValue(':period', $period);
        $stmt->bindValue(':filters', json_encode($filters));
        
        return $stmt->execute();
    }

    /**
     * Retrieve stored metric data
     */
    public function getStoredMetricData($metricId, $period = null, $filters = []) {
        $sql = "SELECT value, last_updated FROM metrics_summary 
                WHERE metric_id = :metric_id";
        
        $params = [':metric_id' => $metricId];
        
        if ($period) {
            $sql .= " AND period = :period";
            $params[':period'] = $period;
        }
        
        if (!empty($filters)) {
            $sql .= " AND filters = :filters";
            $params[':filters'] = json_encode($filters);
        }
        
        $sql .= " ORDER BY last_updated DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'value' => json_decode($result['value'], true),
                'last_updated' => $result['last_updated'],
                'cached' => true
            ];
        }
        
        return null;
    }

    /**
     * Check if metric data is fresh
     */
    public function isMetricDataFresh($metricId, $maxAge = 3600) {
        $sql = "SELECT last_updated FROM metrics_summary 
                WHERE metric_id = :metric_id 
                ORDER BY last_updated DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $metricId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        $lastUpdated = strtotime($result['last_updated']);
        $now = time();
        
        return ($now - $lastUpdated) < $maxAge;
    }

    /**
     * Get cached metric data
     */
    public function getCachedMetric($cacheKey) {
        return $this->cache->get($cacheKey);
    }

    /**
     * Set cached metric data
     */
    public function setCachedMetric($cacheKey, $data, $expiry = null) {
        $expiry = $expiry ?? $this->cacheExpiry;
        return $this->cache->set($cacheKey, $data, $expiry);
    }

    /**
     * Generate cache key
     */
    public function generateCacheKey($category, $metricName, $filters = []) {
        return 'hr_metrics:' . md5($category . '.' . $metricName . '.' . serialize($filters));
    }

    /**
     * Batch store metrics
     */
    public function batchStoreMetrics($metricsData) {
        $sql = "INSERT INTO metrics_summary 
                (metric_id, category, metric_name, value, period, filters, last_updated) 
                VALUES (:metric_id, :category, :metric_name, :value, :period, :filters, NOW())
                ON DUPLICATE KEY UPDATE 
                value = VALUES(value), 
                last_updated = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($metricsData as $data) {
            $stmt->bindValue(':metric_id', $data['metric_id']);
            $stmt->bindValue(':category', $data['category']);
            $stmt->bindValue(':metric_name', $data['metric_name']);
            $stmt->bindValue(':value', json_encode($data['value']));
            $stmt->bindValue(':period', $data['period']);
            $stmt->bindValue(':filters', json_encode($data['filters']));
            $stmt->execute();
        }
        
        return true;
    }

    /**
     * Get metrics summary for dashboard
     */
    public function getMetricsSummary($category = null, $period = 'current') {
        $sql = "SELECT category, metric_name, value, last_updated 
                FROM metrics_summary 
                WHERE 1=1";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        if ($period === 'current') {
            $sql .= " AND period = DATE_FORMAT(NOW(), '%Y-%m')";
        } elseif ($period === 'last_month') {
            $sql .= " AND period = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')";
        }
        
        $sql .= " ORDER BY category, metric_name";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        foreach ($results as $row) {
            $summary[$row['category']][$row['metric_name']] = [
                'value' => json_decode($row['value'], true),
                'last_updated' => $row['last_updated']
            ];
        }
        
        return $summary;
    }

    /**
     * Clean old metric data
     */
    public function cleanOldMetricData($daysToKeep = 365) {
        $sql = "DELETE FROM metrics_summary 
                WHERE last_updated < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $daysToKeep);
        
        return $stmt->execute();
    }

    /**
     * Get metric trends
     */
    public function getMetricTrends($metricId, $periods = 12) {
        $sql = "SELECT period, value, last_updated 
                FROM metrics_summary 
                WHERE metric_id = :metric_id 
                ORDER BY period DESC 
                LIMIT :periods";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':metric_id', $metricId);
        $stmt->bindValue(':periods', $periods);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trends = [];
        foreach ($results as $row) {
            $trends[] = [
                'period' => $row['period'],
                'value' => json_decode($row['value'], true),
                'last_updated' => $row['last_updated']
            ];
        }
        
        return array_reverse($trends); // Return in chronological order
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats() {
        $sql = "SELECT 
                    COUNT(*) as total_metrics,
                    COUNT(DISTINCT category) as categories,
                    COUNT(DISTINCT metric_name) as unique_metrics,
                    MIN(last_updated) as oldest_data,
                    MAX(last_updated) as newest_data,
                    AVG(TIMESTAMPDIFF(MINUTE, last_updated, NOW())) as avg_age_minutes
                FROM metrics_summary";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Warm up cache with frequently accessed metrics
     */
    public function warmUpCache($frequentlyAccessedMetrics) {
        foreach ($frequentlyAccessedMetrics as $metric) {
            $cacheKey = $this->generateCacheKey(
                $metric['category'], 
                $metric['metric_name'], 
                $metric['filters'] ?? []
            );
            
            $data = $this->getStoredMetricData(
                $metric['metric_id'], 
                $metric['period'] ?? null, 
                $metric['filters'] ?? []
            );
            
            if ($data) {
                $this->setCachedMetric($cacheKey, $data);
            }
        }
    }

    /**
     * Clear cache
     */
    public function clearCache($pattern = null) {
        if ($pattern) {
            return $this->cache->deletePattern($pattern);
        } else {
            return $this->cache->flush();
        }
    }
}

/**
 * File-based cache implementation
 */
class FileCache {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expiry'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $expiry = 3600) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expiry' => time() + $expiry
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function delete($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    public function deletePattern($pattern) {
        $files = glob($this->cacheDir . '*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (fnmatch($pattern, basename($file))) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    public function flush() {
        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
}
?>
