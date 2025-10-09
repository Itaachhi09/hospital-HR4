<?php
/**
 * CRUD Audit Tool for Hospital HR System
 * Discovers and validates all CRUD operations across the system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CRUDAuditTool {
    private $basePath;
    private $findings = [];
    private $stats = [
        'total_apis' => 0,
        'total_crud_operations' => 0,
        'endpoints_by_method' => ['GET' => 0, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0],
        'modules' => []
    ];

    public function __construct($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * Main audit function
     */
    public function runAudit() {
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║    HOSPITAL HR SYSTEM - CRUD FUNCTIONALITY AUDIT TOOL         ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

        echo "🔍 Phase 1: API Endpoint Discovery\n";
        echo str_repeat("─", 65) . "\n";
        $this->discoverAPIs();

        echo "\n🔍 Phase 2: Frontend CRUD Button Discovery\n";
        echo str_repeat("─", 65) . "\n";
        $this->discoverFrontendCRUD();

        echo "\n📊 Phase 3: Generating Report\n";
        echo str_repeat("─", 65) . "\n";
        $this->generateReport();
    }

    /**
     * Discover all API endpoints
     */
    private function discoverAPIs() {
        $apiDirs = [
            'php/api' => 'Legacy API',
            'api/routes' => 'Modern Routes'
        ];

        foreach ($apiDirs as $dir => $label) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) continue;

            echo "\n📂 Scanning: $label ($dir)\n";
            
            $files = glob($path . '/*.php');
            foreach ($files as $file) {
                $this->analyzeAPIFile($file, $label);
            }
        }

        echo "\n✅ API Discovery Complete\n";
        echo "   Total API files: " . $this->stats['total_apis'] . "\n";
    }

    /**
     * Analyze individual API file
     */
    private function analyzeAPIFile($file, $category) {
        $this->stats['total_apis']++;
        $filename = basename($file);
        $content = file_get_contents($file);

        // Detect CRUD operations
        $operations = [];
        
        if (preg_match('/(POST|CREATE|INSERT|add_|create_)/i', $content)) {
            $operations[] = 'CREATE';
            $this->stats['endpoints_by_method']['POST']++;
        }
        
        if (preg_match('/(GET|SELECT|get_|fetch)/i', $content)) {
            $operations[] = 'READ';
            $this->stats['endpoints_by_method']['GET']++;
        }
        
        if (preg_match('/(PUT|PATCH|UPDATE|update_|edit)/i', $content)) {
            $operations[] = 'UPDATE';
            $this->stats['endpoints_by_method']['PUT']++;
        }
        
        if (preg_match('/(DELETE|delete_|remove_)/i', $content)) {
            $operations[] = 'DELETE';
            $this->stats['endpoints_by_method']['DELETE']++;
        }

        if (!empty($operations)) {
            $this->findings['apis'][] = [
                'file' => $filename,
                'category' => $category,
                'operations' => $operations,
                'path' => str_replace($this->basePath . '/', '', $file)
            ];
            $this->stats['total_crud_operations'] += count($operations);
        }

        // Show progress
        if ($this->stats['total_apis'] % 10 == 0) {
            echo ".";
        }
    }

    /**
     * Discover frontend CRUD buttons
     */
    private function discoverFrontendCRUD() {
        $jsPath = $this->basePath . '/js';
        $jsFiles = $this->getJSFiles($jsPath);

        echo "📂 Scanning JavaScript files for CRUD buttons\n";
        
        $buttonPatterns = [
            'ADD' => '/(add.*btn|btn.*add|create.*button|new.*button)/i',
            'EDIT' => '/(edit.*btn|btn.*edit|update.*button|modify.*button)/i',
            'DELETE' => '/(delete.*btn|btn.*delete|remove.*button)/i',
            'VIEW' => '/(view.*btn|btn.*view|details.*button|show.*button)/i'
        ];

        foreach ($jsFiles as $file) {
            $this->analyzeJSFile($file, $buttonPatterns);
        }

        echo "\n✅ Frontend Discovery Complete\n";
    }

    /**
     * Get all JavaScript files recursively
     */
    private function getJSFiles($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }

    /**
     * Analyze JS file for CRUD patterns
     */
    private function analyzeJSFile($file, $patterns) {
        $content = file_get_contents($file);
        $filename = basename($file);
        $module = $this->identifyModule($file);

        $foundButtons = [];
        
        foreach ($patterns as $operation => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $foundButtons[$operation] = count($matches[0]);
            }
        }

        // Count fetch calls
        $fetchCount = substr_count($content, 'fetch(');
        
        if (!empty($foundButtons) || $fetchCount > 0) {
            $this->findings['frontend'][] = [
                'file' => $filename,
                'module' => $module,
                'buttons' => $foundButtons,
                'api_calls' => $fetchCount,
                'path' => str_replace($this->basePath . '/', '', $file)
            ];
        }
    }

    /**
     * Identify module from file path
     */
    private function identifyModule($file) {
        if (strpos($file, 'payroll') !== false) return 'Payroll';
        if (strpos($file, 'hmo') !== false) return 'HMO';
        if (strpos($file, 'core_hr') !== false) return 'HR Core';
        if (strpos($file, 'leave') !== false) return 'Leave';
        if (strpos($file, 'attendance') !== false) return 'Attendance';
        if (strpos($file, 'analytics') !== false) return 'Analytics';
        if (strpos($file, 'compensation') !== false) return 'Compensation';
        if (strpos($file, 'admin') !== false) return 'Admin';
        if (strpos($file, 'employee') !== false) return 'Employee Portal';
        return 'General';
    }

    /**
     * Generate comprehensive report
     */
    private function generateReport() {
        $report = "\n\n";
        $report .= "╔═══════════════════════════════════════════════════════════════╗\n";
        $report .= "║                    AUDIT REPORT SUMMARY                       ║\n";
        $report .= "╚═══════════════════════════════════════════════════════════════╝\n\n";

        $report .= "📊 STATISTICS\n";
        $report .= str_repeat("─", 65) . "\n";
        $report .= sprintf("Total API Files: %d\n", $this->stats['total_apis']);
        $report .= sprintf("Total CRUD Operations: %d\n", $this->stats['total_crud_operations']);
        $report .= "\nOperations by HTTP Method:\n";
        $report .= sprintf("  GET (Read):    %d\n", $this->stats['endpoints_by_method']['GET']);
        $report .= sprintf("  POST (Create): %d\n", $this->stats['endpoints_by_method']['POST']);
        $report .= sprintf("  PUT (Update):  %d\n", $this->stats['endpoints_by_method']['PUT']);
        $report .= sprintf("  DELETE:        %d\n", $this->stats['endpoints_by_method']['DELETE']);

        // Module breakdown
        $report .= "\n📦 MODULE BREAKDOWN\n";
        $report .= str_repeat("─", 65) . "\n";
        $moduleStats = [];
        foreach ($this->findings['frontend'] ?? [] as $item) {
            $module = $item['module'];
            if (!isset($moduleStats[$module])) {
                $moduleStats[$module] = ['files' => 0, 'api_calls' => 0];
            }
            $moduleStats[$module]['files']++;
            $moduleStats[$module]['api_calls'] += $item['api_calls'];
        }

        foreach ($moduleStats as $module => $stats) {
            $report .= sprintf("%-20s Files: %2d  API Calls: %3d\n", 
                $module, $stats['files'], $stats['api_calls']);
        }

        echo $report;

        // Save to file
        file_put_contents(
            $this->basePath . '/CRUD_AUDIT_RESULTS.txt',
            $report . "\n\nDetailed findings:\n" . print_r($this->findings, true)
        );

        echo "\n✅ Full report saved to: CRUD_AUDIT_RESULTS.txt\n";
    }
}

// Run the audit
$basePath = dirname(__DIR__);
$auditor = new CRUDAuditTool($basePath);
$auditor->runAudit();
?>

