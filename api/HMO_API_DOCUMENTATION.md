HMO Module API Documentation

Overview

This document describes the HMO-related API endpoints, expected request/response payloads, role-based access rules, and how to run the provided SQL seed. The APIs are under `php/api/` and expect session-based authentication (login performed elsewhere).

Authentication & Sessions

- All endpoints call `api_require_auth()` and expect the session to contain `user_id` and `role_name`.
- `role_name` values used: `System Admin`, `HR Admin`, `Manager`, `Employee`.
- Admin roles (`System Admin`, `HR Admin`) can create/update/delete HMO resources. Employees may only view active plans, their own enrollments and claims as allowed by each endpoint.

Common notes

- API base: `php/api/` (example: `/php/api/hmo_plans.php`)
- All JSON requests should be sent with `Content-Type: application/json` and include session cookies (fetch credentials: 'include').
- Fields use snake_case in payloads (e.g., `provider_id`, `plan_name`).

Endpoints

1) HMO Providers
- GET `/php/api/hmo_providers.php` - returns list of providers (admins: all, others: active only)
- GET `/php/api/hmo_providers.php?id=123` - return single provider
- POST `/php/api/hmo_providers.php` - create provider (admin only)
  - payload: { provider_name, description, contact_person, contact_number, email, status }
- PUT `/php/api/hmo_providers.php?id=123` - update provider (admin only)
  - payload: { provider_name, description, contact_person, contact_number, email, status }
- DELETE `/php/api/hmo_providers.php?id=123` - soft-delete provider (admin only; sets status to Inactive)

2) HMO Plans
- GET `/php/api/hmo_plans.php` - returns plans; admins get all, others only Active
- GET `/php/api/hmo_plans.php?id=123` - return single plan; response `plan.Coverage` is always an array
- POST `/php/api/hmo_plans.php` - create plan (admin only)
  - payload example:
    {
      "provider_id": 1,
      "plan_name": "Silver Plan",
      "coverage": ["inpatient","outpatient"],
      "maximum_benefit_limit": 50000,
      "premium_cost": 1200.50,
      "status": "Active"
    }
  - Note: `coverage` accepts either an array of strings, or a CSV string (e.g. "inpatient,outpatient") for backward compatibility. The API will normalize to an array.
- PUT `/php/api/hmo_plans.php?id=123` - update plan (admin only)
  - payload same as POST. Coverage normalization rules apply.
- DELETE `/php/api/hmo_plans.php?id=123` - soft-delete plan (admin only; sets status to Inactive)

3) HMO Enrollments
- GET `/php/api/hmo_enrollments.php` - list enrollments (admins: all; employees: only their enrollments)
- GET `/php/api/hmo_enrollments.php?id=123` - single enrollment
- POST `/php/api/hmo_enrollments.php` - create enrollment (admin only)
  - payload: { employee_id, plan_id, start_date, end_date, status }
- PUT `/php/api/hmo_enrollments.php?id=123` - update enrollment (admin only)
  - payload: as above
- DELETE `/php/api/hmo_enrollments.php?id=123` - remove enrollment (admin only)

Examples

- Create enrollment (admin):

```js
fetch('/php/api/hmo_enrollments.php', {
  method: 'POST',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({
    employee_id: 42,
    plan_id: 3,
    start_date: '2025-10-01',
    end_date: '2026-09-30',
    status: 'Active'
  })
}).then(r=>r.json()).then(console.log);
```

- Update enrollment (admin):

```js
fetch('/php/api/hmo_enrollments.php?id=12', {
  method: 'PUT',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ plan_id:4, start_date:'2025-11-01', end_date:'2026-10-31', status:'Active' })
}).then(r=>r.json()).then(console.log);
```

- Terminate enrollment (admin or employee owning the enrollment):

```js
fetch('/php/api/hmo_enrollments.php?id=12', {
  method: 'PUT',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ status: 'Terminated', end_date: '2025-10-04' })
}).then(r=>r.json()).then(console.log);
```

- Delete enrollment (admin):

```bash
curl -b cookiefile -X DELETE "http://localhost/php/api/hmo_enrollments.php?id=12"
```

4) HMO Claims
- GET `/php/api/hmo_claims.php` - list claims (admins: all; employees: own claims only)
- GET `/php/api/hmo_claims.php?id=123` - single claim
- POST `/php/api/hmo_claims.php` - file a claim (employees may file for their enrollment; admins may create)
  - payload: { enrollment_id, claim_date, hospital_clinic, diagnosis, claim_amount, remarks }
- PUT `/php/api/hmo_claims.php?id=123` - update claim (admins can approve/deny via claim_status; employees can update remarks while pending)
  - payload: { claim_status, remarks, ... }
- DELETE `/php/api/hmo_claims.php?id=123` - delete claim (admin only)

Examples

- File a claim (employee):

```js
fetch('/php/api/hmo_claims.php', {
  method: 'POST',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ enrollment_id: 1, claim_date: '2025-10-04', hospital_clinic: 'ACME Hospital', diagnosis: 'Flu', claim_amount: 1500.00, remarks: 'ER visit' })
}).then(r=>r.json()).then(console.log);
```

- Approve a claim (admin):

```js
fetch('/php/api/hmo_claims.php?id=12', {
  method: 'PUT',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ claim_status: 'Approved', remarks: 'Approved by HR Admin' })
}).then(r=>r.json()).then(console.log);
```

- Deny a claim (admin):

```js
fetch('/php/api/hmo_claims.php?id=12', {
  method: 'PUT',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ claim_status: 'Denied', remarks: 'Missing receipts' })
}).then(r=>r.json()).then(console.log);
```

- Employee updating remarks (allowed):

```js
fetch('/php/api/hmo_claims.php?id=12', {
  method: 'PUT',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({ remarks: 'Additional notes from employee' })
}).then(r=>r.json()).then(console.log);
```

Smoke tests

I added smoke-test scripts under `api/tests/` similar to enrollments. To exercise claims flows, create/copy a cookie file with a valid session and run the scripts (Node + node-fetch required):

```bash
npm install node-fetch@2
node api/tests/claims_smoketests_admin.js
node api/tests/claims_smoketests_employee.js
```

The Postman collection `api/postman_collection.json` also includes HMO Claims requests (File Claim, Update, Approve, Deny, Delete) which you can import directly into Postman.

5) HMO Dashboard
- GET `/php/api/hmo_dashboard.php?mode=summary` - returns counts (providers, plans, active_enrollments, claims breakdown)
- GET `/php/api/hmo_dashboard.php?mode=monthly_claims&year=2025` - monthly claim totals
- GET `/php/api/hmo_dashboard.php?mode=top_hospitals&limit=10` - top hospitals by claim count/amount
- GET `/php/api/hmo_dashboard.php?mode=plan_utilization&limit=10` - plans with highest utilization

Database seed

A SQL file with schema and seeds is available at `database/hmo_upgrade_schema.sql` (or `database/hmo_schema_and_seed.sql` depending on repository). To run the seed on XAMPP MySQL:

1. Open phpMyAdmin or use mysql CLI.
2. Create/select your database (for example `hospital_hr`).
3. Import the SQL file or run:

```bash
# from git bash or WSL where mysql client is available
mysql -u root -p hospital_hr < "c:/NEWXAMPP/htdocs/hospital-HR4/database/hmo_upgrade_schema.sql"
```

Test accounts

- Ensure you have a user session created by the app. To test admin flows, login with a user where `role_name` is `System Admin` or `HR Admin` in the sessions table or via the app login.

Examples (fetch in browser / JS)

- Fetch plans (as admin):

fetch('/php/api/hmo_plans.php', { credentials:'include' }).then(r=>r.json()).then(console.log);

- Create plan (admin):

fetch('/php/api/hmo_plans.php', { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ provider_id:1, plan_name:'Gold', coverage:['inpatient','dental'], maximum_benefit_limit:100000, premium_cost:2000, status:'Active' }) }).then(r=>r.json()).then(console.log);

Notes & troubleshooting

- If your UI shows empty lists, confirm seed ran and records exist in HMOProviders/HMOPlans.
- If coverage appears as a CSV string in older code paths, the updated API will normalize and return coverage as an array.
- For large lists, consider adding server-side search/pagination later; current UI uses client-side filtering for convenience.

Contact

If you'd like, I can also add Postman collection examples and update the repo README with step-by-step integration notes.

Smoke tests

There are two Node-based smoke test scripts in `api/tests/` to exercise enrollment endpoints:

- `api/tests/enrollment_smoketests_admin.js` - admin flow (create, update, terminate, delete)
- `api/tests/enrollment_smoketests_employee.js` - employee flow (create forced to session employee_id, update, terminate, attempt delete)

Usage:

1. Install node dependencies (in repo root):

```bash
npm install node-fetch@2
```

2. Save your session cookie into `cookie.txt` (admin) or `cookie_employee.txt` (employee). The scripts read the cookie file and send it in the Cookie header.

3. Run the script:

```bash
node api/tests/enrollment_smoketests_admin.js
node api/tests/enrollment_smoketests_employee.js
```

Adjust the scripts if your dev server is not at `http://localhost` or if you need to use a different cookie storage approach.
