## REST Router Overview

This repository now includes a safe, non-destructive REST routing layer that delegates to existing PHP API scripts without changing their logic.

### Entry Points

- REST router: `php/api/rest.php`
- Apache rewrite: `.htaccess` routes `/api/*` to the REST router

If your web root is the repo root, requests to `/api/...` will be handled by the REST router.

### Why this approach

- Preserves existing script behavior (queries, auth checks, sessions)
- Adds clean RESTful URLs and consistent CORS/OPTIONS handling
- Avoids refactoring risk by mapping routes to current scripts

### Example Mappings

- GET `/api/users` -> `php/api/get_users.php`
- GET `/api/employees` -> `php/api/get_employees.php`
- GET `/api/salaries` -> `php/api/get_salaries.php`
- POST `/api/login` -> `php/api/login.php`
- GET `/api/leave-types` -> `php/api/get_leave_types.php`
- POST `/api/leave-types` -> `php/api/add_leave_type.php`
- PUT `/api/leave-types` -> `php/api/update_leave_type.php`
- PATCH `/api/leave-requests/status` -> `php/api/update_leave_request_status.php`
- GET `/api/timesheets` -> `php/api/get_timesheets.php`
- GET `/api/timesheets/{id}` -> `php/api/get_timesheet_details.php` (id is provided as `?timesheet_id=`)
- PATCH `/api/timesheets/status` -> `php/api/update_timesheet_status.php`
- GET `/api/hmo/providers` -> `php/api/get_hmo_providers.php`
- GET `/api/hmo/plans` -> `php/api/get_hmo_plans.php`
- GET `/api/hmo/claims` -> `php/api/get_hmo_claims.php`
- PATCH `/api/hmo/claims/status` -> `php/api/update_claims_status.php`
- GET `/api/hmo/enrollments` -> `php/api/get_hmo_enrollments.php`
- GET `/api/hmo/benefits` -> `php/api/get_benefits.php`
- GET `/api/payroll/runs` -> `php/api/get_payroll_runs.php`
- GET `/api/payslips` -> `php/api/get_payslips.php`
- GET `/api/payslips/details` -> `php/api/get_payslip_details.php`
- GET `/api/dashboard/summary` -> `php/api/get_dashboard_summary.php`
- GET `/api/analytics/summary` -> `php/api/get_hr_analytics_summary.php`
- GET `/api/analytics/key-metrics` -> `php/api/get_key_metrics.php`
- GET `/api/documents` -> `php/api/get_documents.php`
- POST `/api/documents` -> `php/api/upload_document.php`

You can extend mappings inside `php/api/rest.php` by adding entries to the `$routes` array.

### CORS and Preflight

`php/api/rest.php` sets permissive CORS headers and responds to `OPTIONS` with `200`. Tune `Access-Control-Allow-Origin` and `Allow-Headers` for production.

### Server Configuration

Apache:

1. Ensure `mod_rewrite` is enabled.
2. Place this repository at the document root or configure `DocumentRoot` accordingly.
3. The included `.htaccess` will rewrite `/api/*` to `php/api/rest.php`.

Nginx (example):

```
location /api/ {
    try_files $uri /php/api/rest.php$is_args$args;
}
```

### Backwards Compatibility

- Existing scripts remain callable directly, e.g., `php/api/get_employees.php`.
- The router simply provides RESTful aliases, minimizing risk to current consumers.

### Notes

- If any mapped script requires sessions, it will continue to manage them internally.
- For path parameters (e.g., `/api/timesheets/123`), the router sets `$_GET['timesheet_id']` to maintain compatibility.

