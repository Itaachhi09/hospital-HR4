<?php
require_once __DIR__ . '/../config.php';

class Payslips {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all payslips with comprehensive data
     */
    public function getAllPayslips($filters = []) {
        $sql = "SELECT 
                    p.PayslipID,
                    p.PayrollID as PayrollRunID,
                    p.EmployeeID,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.EmployeeNumber,
                    'N/A' as DepartmentName,
                    'N/A' as PositionName,
                    'N/A' as BranchName,
                    p.PayPeriodStartDate as PayPeriodStart,
                    p.PayPeriodEndDate as PayPeriodEnd,
                    p.PaymentDate as PayDate,
                    p.BasicSalary,
                    p.OvertimeHours,
                    p.OvertimePay,
                    p.NightDifferentialPay as NightDiffPay,
                    p.OtherEarnings as Allowances,
                    p.BonusesTotal as Bonuses,
                    p.GrossIncome,
                    p.SSS_Contribution,
                    p.PhilHealth_Contribution,
                    p.PagIBIG_Contribution,
                    p.WithholdingTax,
                    p.OtherDeductionsTotal as OtherDeductions,
                    p.TotalDeductions,
                    p.NetIncome,
                    'Generated' as Status,
                    CURDATE() as GeneratedAt,
                    '1.0' as Version,
                    'Draft' as PayrollRunStatus
                FROM payslips p
                LEFT JOIN employees e ON p.EmployeeID = e.EmployeeID
                WHERE 1=1";

        $params = [];
        
        // Apply filters
        if (!empty($filters['branch_id'])) {
            // Branch filtering not available in current schema
            // $sql .= " AND e.BranchID = :branch_id";
            // $params[':branch_id'] = $filters['branch_id'];
        }
        
        if (!empty($filters['department_id'])) {
            // Department filtering not available in current schema
            // $sql .= " AND e.DepartmentID = :department_id";
            // $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['payroll_run_id'])) {
            $sql .= " AND p.PayrollID = :payroll_run_id";
            $params[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND p.EmployeeID = :employee_id";
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['pay_period_start'])) {
            $sql .= " AND p.PayPeriodStartDate >= :pay_period_start";
            $params[':pay_period_start'] = $filters['pay_period_start'];
        }
        
        if (!empty($filters['pay_period_end'])) {
            $sql .= " AND p.PayPeriodEndDate <= :pay_period_end";
            $params[':pay_period_end'] = $filters['pay_period_end'];
        }
        
        if (!empty($filters['status'])) {
            // Status filtering not available in current schema
            // $sql .= " AND p.Status = :status";
            // $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                     OR e.EmployeeNumber LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY p.PayPeriodStartDate DESC, e.LastName, e.FirstName";

        // Add pagination
        $page = max(1, $filters['page'] ?? 1);
        $limit = max(1, min(100, $filters['limit'] ?? 50));
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM payslips_v2 p
                    LEFT JOIN employees e ON p.EmployeeID = e.EmployeeID
                    LEFT JOIN departments dept ON e.DepartmentID = dept.DepartmentID
                    WHERE 1=1";
        
        $countParams = [];
        if (!empty($filters['branch_id'])) {
            $countSql .= " AND e.BranchID = :branch_id";
            $countParams[':branch_id'] = $filters['branch_id'];
        }
        if (!empty($filters['department_id'])) {
            $countSql .= " AND e.DepartmentID = :department_id";
            $countParams[':department_id'] = $filters['department_id'];
        }
        if (!empty($filters['payroll_run_id'])) {
            $countSql .= " AND p.PayrollRunID = :payroll_run_id";
            $countParams[':payroll_run_id'] = $filters['payroll_run_id'];
        }
        if (!empty($filters['employee_id'])) {
            $countSql .= " AND p.EmployeeID = :employee_id";
            $countParams[':employee_id'] = $filters['employee_id'];
        }
        if (!empty($filters['pay_period_start'])) {
            $countSql .= " AND p.PayPeriodStart >= :pay_period_start";
            $countParams[':pay_period_start'] = $filters['pay_period_start'];
        }
        if (!empty($filters['pay_period_end'])) {
            $countSql .= " AND p.PayPeriodEnd <= :pay_period_end";
            $countParams[':pay_period_end'] = $filters['pay_period_end'];
        }
        if (!empty($filters['status'])) {
            $countSql .= " AND p.Status = :status";
            $countParams[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $countSql .= " AND (CONCAT(e.FirstName, ' ', e.LastName) LIKE :search 
                         OR e.EmployeeNumber LIKE :search 
                         OR dept.DepartmentName LIKE :search 
                         OR pos.PositionName LIKE :search)";
            $countParams[':search'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->pdo->prepare($countSql);
        foreach ($countParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'payslips' => $payslips,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get specific payslip details
     */
    public function getPayslip($payslipId) {
        $sql = "SELECT 
                    p.*,
                    CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                    e.FirstName,
                    e.LastName,
                    e.EmployeeNumber,
                    e.Email,
                    e.PhoneNumber,
                    e.Address,
                    dept.DepartmentName,
                    pos.PositionName,
                    hb.BranchName,
                    hb.BranchAddress,
                    pr.Version,
                    pr.Status as PayrollRunStatus,
                    pr.Notes as PayrollRunNotes
                FROM payslips_v2 p
                LEFT JOIN employees e ON p.EmployeeID = e.EmployeeID
                LEFT JOIN payroll_v2_runs pr ON p.PayrollRunID = pr.PayrollRunID
                WHERE p.PayslipID = :payslip_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':payslip_id', $payslipId, PDO::PARAM_INT);
        $stmt->execute();

        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payslip) {
            // Parse details JSON if available
            if ($payslip['DetailsJSON']) {
                $payslip['details'] = json_decode($payslip['DetailsJSON'], true);
            }
        }

        return $payslip;
    }

    /**
     * Get payslip preview data for web viewing
     */
    public function getPayslipPreview($payslipId) {
        $payslip = $this->getPayslip($payslipId);
        
        if (!$payslip) {
            return null;
        }

        // Format the payslip data for preview
        $preview = [
            'payslip_id' => $payslip['PayslipID'],
            'employee_info' => [
                'name' => $payslip['employee_name'],
                'employee_number' => $payslip['EmployeeNumber'],
                'department' => $payslip['DepartmentName'],
                'position' => $payslip['PositionName'],
                'branch' => $payslip['BranchName']
            ],
            'pay_period' => [
                'start' => $payslip['PayPeriodStart'],
                'end' => $payslip['PayPeriodEnd'],
                'pay_date' => $payslip['PayDate']
            ],
            'earnings' => [
                'basic_salary' => (float)$payslip['BasicSalary'],
                'overtime_pay' => (float)$payslip['OvertimePay'],
                'night_diff_pay' => (float)$payslip['NightDiffPay'],
                'allowances' => (float)$payslip['Allowances'],
                'bonuses' => (float)$payslip['Bonuses'],
                'gross_income' => (float)$payslip['GrossIncome']
            ],
            'deductions' => [
                'sss_contribution' => (float)$payslip['SSS_Contribution'],
                'philhealth_contribution' => (float)$payslip['PhilHealth_Contribution'],
                'pagibig_contribution' => (float)$payslip['PagIBIG_Contribution'],
                'withholding_tax' => (float)$payslip['WithholdingTax'],
                'other_deductions' => (float)$payslip['OtherDeductions'],
                'total_deductions' => (float)$payslip['TotalDeductions']
            ],
            'net_pay' => (float)$payslip['NetIncome'],
            'status' => $payslip['Status'],
            'generated_at' => $payslip['GeneratedAt'],
            'payroll_run' => [
                'id' => $payslip['PayrollRunID'],
                'version' => $payslip['Version'],
                'status' => $payslip['PayrollRunStatus']
            ]
        ];

        return $preview;
    }

    /**
     * Get payslip summary for a payroll run
     */
    public function getPayslipSummary($payrollRunId) {
        $sql = "SELECT 
                    pr.PayrollRunID,
                    pr.PayPeriodStart,
                    pr.PayPeriodEnd,
                    pr.PayDate,
                    pr.Version,
                    pr.Status as PayrollRunStatus,
                    hb.BranchName,
                    COUNT(p.PayslipID) as total_payslips,
                    SUM(p.GrossIncome) as total_gross_income,
                    SUM(p.TotalDeductions) as total_deductions,
                    SUM(p.NetIncome) as total_net_income,
                    AVG(p.GrossIncome) as average_gross_income,
                    AVG(p.NetIncome) as average_net_income,
                    MIN(p.GeneratedAt) as first_generated,
                    MAX(p.GeneratedAt) as last_generated
                FROM payroll_v2_runs pr
                LEFT JOIN payslips_v2 p ON pr.PayrollRunID = p.PayrollRunID
                LEFT JOIN hospital_branches hb ON pr.BranchID = hb.BranchID
                WHERE pr.PayrollRunID = :payroll_run_id
                GROUP BY pr.PayrollRunID";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':payroll_run_id', $payrollRunId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get payslip audit log
     */
    public function getPayslipAuditLog($payslipId) {
        $sql = "SELECT 
                    al.AuditLogID,
                    al.Action,
                    al.Details,
                    al.UserID,
                    al.IPAddress,
                    al.UserAgent,
                    al.CreatedAt,
                    u.Username
                FROM payroll_v2_audit_logs al
                LEFT JOIN users u ON al.UserID = u.UserID
                WHERE al.PayslipID = :payslip_id
                ORDER BY al.CreatedAt DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':payslip_id', $payslipId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate payslips for a payroll run
     */
    public function generatePayslipsForPayrollRun($payrollRunId, $branchId) {
        // Get payroll run details
        $payrollSql = "SELECT * FROM payroll_v2_runs WHERE PayrollRunID = :run_id AND BranchID = :branch_id";
        $payrollStmt = $this->pdo->prepare($payrollSql);
        $payrollStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $payrollStmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $payrollStmt->execute();
        $payrollRun = $payrollStmt->fetch(PDO::FETCH_ASSOC);

        if (!$payrollRun) {
            throw new Exception('Payroll run not found');
        }

        // Check if payslips already exist
        $existingSql = "SELECT COUNT(*) as count FROM payslips_v2 WHERE PayrollRunID = :run_id";
        $existingStmt = $this->pdo->prepare($existingSql);
        $existingStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $existingStmt->execute();
        $existingCount = $existingStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($existingCount > 0) {
            throw new Exception('Payslips already exist for this payroll run');
        }

        // Get payslip data from payroll_v2_runs processing
        $payslipsSql = "SELECT 
                            pv2.PayrollRunID,
                            pv2.EmployeeID,
                            pv2.BranchID,
                            pv2.PayPeriodStart,
                            pv2.PayPeriodEnd,
                            pv2.PayDate,
                            pv2.BasicSalary,
                            pv2.OvertimeHours,
                            pv2.OvertimePay,
                            pv2.NightDiffPay,
                            pv2.Allowances,
                            pv2.Bonuses,
                            pv2.GrossIncome,
                            pv2.SSS_Contribution,
                            pv2.PhilHealth_Contribution,
                            pv2.PagIBIG_Contribution,
                            pv2.WithholdingTax,
                            pv2.OtherDeductions,
                            pv2.TotalDeductions,
                            pv2.NetIncome,
                            pv2.DetailsJSON
                        FROM payslips_v2 pv2
                        WHERE pv2.PayrollRunID = :run_id";

        $payslipsStmt = $this->pdo->prepare($payslipsSql);
        $payslipsStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $payslipsStmt->execute();
        $payslips = $payslipsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($payslips)) {
            throw new Exception('No payslip data found for this payroll run. Please process the payroll run first.');
        }

        // Update payslip status to 'Generated'
        $updateSql = "UPDATE payslips_v2 SET Status = 'Generated' WHERE PayrollRunID = :run_id";
        $updateStmt = $this->pdo->prepare($updateSql);
        $updateStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Log the generation
        $this->logPayslipAction($payrollRunId, 'GENERATE_PAYSLIPS', 'Generated payslips for payroll run', null);

        return [
            'payroll_run_id' => $payrollRunId,
            'branch_id' => $branchId,
            'payslips_generated' => count($payslips),
            'total_gross_income' => array_sum(array_column($payslips, 'GrossIncome')),
            'total_deductions' => array_sum(array_column($payslips, 'TotalDeductions')),
            'total_net_income' => array_sum(array_column($payslips, 'NetIncome'))
        ];
    }

    /**
     * Regenerate payslips for a payroll run
     */
    public function regeneratePayslipsForPayrollRun($payrollRunId, $branchId) {
        // Delete existing payslips
        $deleteSql = "DELETE FROM payslips_v2 WHERE PayrollRunID = :run_id";
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->bindValue(':run_id', $payrollRunId, PDO::PARAM_INT);
        $deleteStmt->execute();

        // Log the regeneration
        $this->logPayslipAction($payrollRunId, 'REGENERATE_PAYSLIPS', 'Regenerated payslips for payroll run', null);

        // Generate new payslips
        return $this->generatePayslipsForPayrollRun($payrollRunId, $branchId);
    }

    /**
     * Export payslips to HR Docs and Finance
     */
    public function exportPayslips($payslipIds, $format = 'pdf') {
        $placeholders = str_repeat('?,', count($payslipIds) - 1) . '?';
        $sql = "SELECT * FROM payslips_v2 WHERE PayslipID IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payslipIds);
        $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($payslips)) {
            throw new Exception('No payslips found for export');
        }

        // Log the export
        $this->logPayslipAction(null, 'EXPORT_PAYSLIPS', "Exported {$format} format for " . count($payslips) . " payslips", json_encode($payslipIds));

        return [
            'payslips_exported' => count($payslips),
            'format' => $format,
            'export_timestamp' => date('Y-m-d H:i:s'),
            'payslip_ids' => $payslipIds
        ];
    }

    /**
     * Update payslip
     */
    public function updatePayslip($payslipId, $data) {
        $allowedFields = ['Status', 'Notes'];
        $updateFields = [];
        $params = [':payslip_id' => $payslipId];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE payslips_v2 SET " . implode(', ', $updateFields) . " WHERE PayslipID = :payslip_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Log the update
        $this->logPayslipAction($payslipId, 'UPDATE_PAYSLIP', 'Updated payslip details', json_encode($data));

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete payslip (soft delete)
     */
    public function deletePayslip($payslipId) {
        $sql = "UPDATE payslips_v2 SET Status = 'Deleted' WHERE PayslipID = :payslip_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':payslip_id', $payslipId, PDO::PARAM_INT);
        $stmt->execute();

        // Log the deletion
        $this->logPayslipAction($payslipId, 'DELETE_PAYSLIP', 'Soft deleted payslip', null);

        return $stmt->rowCount() > 0;
    }

    /**
     * Generate payslip PDF content (simplified HTML to PDF)
     */
    public function generatePayslipPDFContent($payslip) {
        // This is a simplified implementation
        // In production, use a proper PDF library like TCPDF or mPDF
        
        $html = $this->generatePayslipHTML($payslip);
        
        // For now, return HTML content
        // In production, convert HTML to PDF using a library
        return $html;
    }

    /**
     * Generate batch payslip PDF content
     */
    public function generateBatchPayslipPDFContent($payslipIds) {
        $placeholders = str_repeat('?,', count($payslipIds) - 1) . '?';
        $sql = "SELECT p.*, CONCAT(e.FirstName, ' ', e.LastName) as employee_name, e.EmployeeNumber
                FROM payslips_v2 p
                LEFT JOIN employees e ON p.EmployeeID = e.EmployeeID
                WHERE p.PayslipID IN ($placeholders)
                ORDER BY e.LastName, e.FirstName";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payslipIds);
        $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<html><head><title>Batch Payslips</title></head><body>';
        foreach ($payslips as $payslip) {
            $html .= $this->generatePayslipHTML($payslip);
            $html .= '<div style="page-break-after: always;"></div>';
        }
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate payslip HTML for PDF conversion
     */
    private function generatePayslipHTML($payslip) {
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ccc;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2563eb; margin: 0;">HMVH Hospital</h1>
                <h2 style="color: #1e40af; margin: 10px 0;">PAYSLIP</h2>
                <p style="margin: 5px 0;">Pay Period: ' . $payslip['PayPeriodStart'] . ' to ' . $payslip['PayPeriodEnd'] . '</p>
                <p style="margin: 5px 0;">Pay Date: ' . $payslip['PayDate'] . '</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #374151;">Employee Information</h3>
                    <p style="margin: 5px 0;"><strong>Name:</strong> ' . $payslip['employee_name'] . '</p>
                    <p style="margin: 5px 0;"><strong>Employee #:</strong> ' . $payslip['EmployeeNumber'] . '</p>
                    <p style="margin: 5px 0;"><strong>Department:</strong> ' . $payslip['DepartmentName'] . '</p>
                    <p style="margin: 5px 0;"><strong>Position:</strong> ' . $payslip['PositionName'] . '</p>
                </div>
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #374151;">Payslip Details</h3>
                    <p style="margin: 5px 0;"><strong>Payslip ID:</strong> #' . $payslip['PayslipID'] . '</p>
                    <p style="margin: 5px 0;"><strong>Payroll Run:</strong> #' . $payslip['PayrollRunID'] . '</p>
                    <p style="margin: 5px 0;"><strong>Status:</strong> ' . $payslip['Status'] . '</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 15px 0; color: #059669;">Earnings</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Basic Salary</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['BasicSalary'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Overtime Pay</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['OvertimePay'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Night Differential</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['NightDiffPay'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Allowances</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['Allowances'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Bonuses</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['Bonuses'], 2) . '</td>
                        </tr>
                        <tr style="background-color: #f3f4f6;">
                            <td style="padding: 8px; font-weight: bold;">Total Gross Income</td>
                            <td style="padding: 8px; font-weight: bold; text-align: right;">₱' . number_format($payslip['GrossIncome'], 2) . '</td>
                        </tr>
                    </table>
                </div>
                
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 15px 0; color: #dc2626;">Deductions</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">SSS Contribution</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['SSS_Contribution'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">PhilHealth Contribution</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['PhilHealth_Contribution'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Pag-IBIG Contribution</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['PagIBIG_Contribution'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Withholding Tax</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['WithholdingTax'], 2) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">Other Deductions</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">₱' . number_format($payslip['OtherDeductions'], 2) . '</td>
                        </tr>
                        <tr style="background-color: #fef2f2;">
                            <td style="padding: 8px; font-weight: bold;">Total Deductions</td>
                            <td style="padding: 8px; font-weight: bold; text-align: right;">₱' . number_format($payslip['TotalDeductions'], 2) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #1f2937; color: white;">
                <h2 style="margin: 0 0 10px 0;">NET PAY</h2>
                <h1 style="margin: 0; font-size: 2em;">₱' . number_format($payslip['NetIncome'], 2) . '</h1>
            </div>
            
            <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #6b7280;">
                <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
                <p>This is a computer-generated payslip. No signature required.</p>
            </div>
        </div>';

        return $html;
    }

    /**
     * Log payslip action for audit trail
     */
    private function logPayslipAction($payslipId, $action, $details, $metadata = null) {
        $sql = "INSERT INTO payroll_v2_audit_logs (PayslipID, Action, Details, Metadata, UserID, IPAddress, UserAgent) 
                VALUES (:payslip_id, :action, :details, :metadata, :user_id, :ip_address, :user_agent)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':payslip_id', $payslipId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':details', $details);
        $stmt->bindValue(':metadata', $metadata);
        $stmt->bindValue(':user_id', $_SESSION['user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
        $stmt->execute();
    }
}
