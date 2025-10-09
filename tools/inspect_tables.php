<?php
require_once __DIR__ . '/../api/config.php';
$tables = [
    'payroll_runs_v2',
    'payroll_v2_runs',
    'payroll_branch_config',
    'payroll_v2_branch_configs',
    'payslips_v2',
    'employeesalaries',
    'timesheets',
    'payroll_bonuses',
    'employee_branch_assignments'
];
$out = [];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$t`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out[$t] = $cols;
    } catch (Exception $e) {
        $out[$t] = ['error' => $e->getMessage()];
    }
}
echo json_encode($out, JSON_PRETTY_PRINT);
