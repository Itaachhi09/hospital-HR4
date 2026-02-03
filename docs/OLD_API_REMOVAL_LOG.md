# Old API Files Removal Log

## Overview

This document tracks the removal of old API files from the legacy architecture to avoid confusion with the new modular v2 API structure.

## Removal Strategy

### Files to Remove
- All files in `modules/*/api/` directories (legacy individual endpoint files)
- These are being replaced by the new v2 API structure in `modules/*/api/v2/` and `modules/*/routes/`

### Files to Keep
- `modules/*/routes/` - New routing structure
- `modules/*/models/` - Data models
- `modules/*/services/` - Service layer
- `public/api/index.php` - Main API gateway (will be updated to use v2)
- `public/api/v2/index.php` - New v2 API gateway

## Removal Date
Date: January 2025

## Removal Summary
- **Total Files Removed**: 131 files
- **Status**: ✅ Complete
- **Script Used**: `tools/remove_old_apis.ps1`

## Removed Files

### Auth Module
- [x] modules/auth/api/add_user.php
- [x] modules/auth/api/change_password.php
- [x] modules/auth/api/check_session.php
- [x] modules/auth/api/delete_user.php
- [x] modules/auth/api/get_user_profile.php
- [x] modules/auth/api/get_users.php
- [x] modules/auth/api/login.php
- [x] modules/auth/api/logout.php
- [x] modules/auth/api/request_2fa_code.php
- [x] modules/auth/api/reset_password.php
- [x] modules/auth/api/test_connection.php
- [x] modules/auth/api/update_user.php
- [x] modules/auth/api/verify_2fa.php

### Employees Module
- [x] modules/employees/api/add_employee_and_user.php
- [x] modules/employees/api/admin_update_employee_profile.php
- [x] modules/employees/api/create_employee_from_recruit.php
- [x] modules/employees/api/delete_employee.php
- [x] modules/employees/api/get_employees.php
- [x] modules/employees/api/get_hospital_org_structure.php
- [x] modules/employees/api/get_org_structure.php
- [x] modules/employees/api/manage_hr_structure.php
- [x] modules/employees/api/update_user_profile.php

### Payroll Module
- [x] modules/payroll/api/add_bonus.php
- [x] modules/payroll/api/add_deduction.php
- [x] modules/payroll/api/add_incentive.php
- [x] modules/payroll/api/add_salary_adjustment.php
- [x] modules/payroll/api/add_update_salary.php
- [x] modules/payroll/api/create_payroll_run.php
- [x] modules/payroll/api/generate_payslip_html.php
- [x] modules/payroll/api/get_bonuses.php
- [x] modules/payroll/api/get_deductions.php
- [x] modules/payroll/api/get_incentives.php
- [x] modules/payroll/api/get_payslip_details.php
- [x] modules/payroll/api/get_payslips.php
- [x] modules/payroll/api/get_payroll_runs.php
- [x] modules/payroll/api/get_salary_adjustments.php
- [x] modules/payroll/api/get_salaries.php
- [x] modules/payroll/api/process_payroll_run.php

### HMO Module
- [x] modules/hmo/api/add_claim_type.php
- [x] modules/hmo/api/add_employee_enrollment.php
- [x] modules/hmo/api/delete_claim_type.php
- [x] modules/hmo/api/get_claims.php
- [x] modules/hmo/api/get_claim_types.php
- [x] modules/hmo/api/get_employee_enrollments.php
- [x] modules/hmo/api/seed_hmo.php
- [x] modules/hmo/api/submit_claim.php
- [x] modules/hmo/api/update_claim_type.php
- [x] modules/hmo/api/update_claims_status.php
- [x] modules/hmo/api/upload_attachment.php

### Benefits Module
- [x] modules/benefits/api/add_benefit.php
- [x] modules/benefits/api/add_benefits_category.php
- [x] modules/benefits/api/assign_employee_benefit.php
- [x] modules/benefits/api/delete_employee_benefit.php
- [x] modules/benefits/api/get_benefits.php
- [x] modules/benefits/api/get_benefits_categories.php
- [x] modules/benefits/api/get_employee_benefits.php

### Leave Module
- [x] modules/leave/api/add_leave_type.php
- [x] modules/leave/api/delete_leave_request.php
- [x] modules/leave/api/delete_leave_type.php
- [x] modules/leave/api/get_leave_balances.php
- [x] modules/leave/api/get_leave_requests.php
- [x] modules/leave/api/get_leave_types.php
- [x] modules/leave/api/submit_leave_request.php
- [x] modules/leave/api/update_leave_request_status.php
- [x] modules/leave/api/update_leave_type.php

### Attendance Module
- [x] modules/attendance/api/add_attendance.php
- [x] modules/attendance/api/add_schedule.php
- [x] modules/attendance/api/add_shift.php
- [x] modules/attendance/api/add_timesheet.php
- [x] modules/attendance/api/get_attendance.php
- [x] modules/attendance/api/get_schedules.php
- [x] modules/attendance/api/get_shifts.php
- [x] modules/attendance/api/get_timesheet_details.php
- [x] modules/attendance/api/get_timesheets.php
- [x] modules/attendance/api/update_timesheet_status.php

### Analytics Module
- [x] modules/analytics/api/get_enhanced_hr_analytics.php
- [x] modules/analytics/api/get_enhanced_hr_analytics.php.bak
- [x] modules/analytics/api/get_hr_analytics_summary.php
- [x] modules/analytics/api/get_hr_analytics_summary.php.bak
- [x] modules/analytics/api/get_key_metrics.php
- [x] modules/analytics/api/get_key_metrics.php.bak

### Core HR Module
- [x] modules/core_hr/api/delete_document.php
- [x] modules/core_hr/api/get_documents.php
- [x] modules/core_hr/api/upload_document.php

### Dashboard Module
- [x] modules/dashboard/api/get_dashboard_summary.php

### Reports Module
- [x] modules/reports/api/generate_employee_master_report.php
- [x] modules/reports/api/generate_leave_summary_report.php
- [x] modules/reports/api/generate_payroll_summary_report.php
- [x] modules/reports/api/get_hr_reports_list.php

### Notifications Module
- [x] modules/notifications/api/delete_notification.php
- [x] modules/notifications/api/get_notifications.php
- [x] modules/notifications/api/mark_notification_read.php

### Integrations Module
- [x] modules/integrations/api/add_compensation_plan.php
- [x] modules/integrations/api/AnalyticsIntegration.php
- [x] modules/integrations/api/CompensationIntegrations.php
- [x] modules/integrations/api/FinanceIntegration.php
- [x] modules/integrations/api/get_compensation_plans.php
- [x] modules/integrations/api/HMOPayrollIntegration.php
- [x] modules/integrations/api/HRCoreIntegration.php
- [x] modules/integrations/api/HRReportsAccessControl.php
- [x] modules/integrations/api/HRReportsAuditTrail.php
- [x] modules/integrations/api/HRReportsExportHandler.php
- [x] modules/integrations/api/HRReportsIntegration.php
- [x] modules/integrations/api/HRReportsScheduler.php
- [x] modules/integrations/api/HRReportsSystem.php
- [x] modules/integrations/api/link_compensation_plan_item.php
- [x] modules/integrations/api/PayrollIntegration.php
- [x] modules/integrations/api/post_payroll_to_finance.php
- [x] modules/integrations/api/ReportingSystem.php

## Notes

- All functionality from these old API files should be migrated to the new v2 API structure
- The new structure uses:
  - `modules/*/api/v2/` for versioned API controllers
  - `modules/*/routes/` for routing
  - `modules/*/services/` for business logic
  - DTOs for data transfer
  - Event bus for async communication

## Migration Status

- ✅ Architecture designed
- ✅ Core services implemented
- ✅ Old APIs removed (131 files)
- ⏳ New v2 APIs being implemented

## Post-Removal Notes

- All old API endpoint files have been successfully removed
- API directories (`modules/*/api/`) are kept but are now empty
- These directories will be used for v2 API controllers (`modules/*/api/v2/`)
- The new architecture uses:
  - `modules/*/routes/` for routing (already in place)
  - `modules/*/api/v2/` for versioned controllers (to be implemented)
  - `modules/*/services/` for business logic
  - DTOs for data transfer
  - Event bus for async communication
