AI_SERVER - Flask REST API for HR and Payroll ML
===============================================

Contents
--------
- app.py             Main Flask API (port 8000)
- models\            Folder containing trained models (copied on first start)
- requirements.txt   Python dependencies
- config.json        DB config (MySQL 127.0.0.1, root, hospital_hr_db)
- start_server.bat   Windows script to set up venv, install deps, copy models, run server

Windows Quick Start
-------------------
1) Open PowerShell or CMD
2) cd C:\xampp\htdocs\ANALYTICSAI\AI_SERVER
3) Run: start_server.bat
   - Creates .venv, installs requirements, copies models from:
     - C:\xampp\htdocs\ANALYTICSAI\ml\artifacts
     - C:\xampp\htdocs\PAYROLLAI\hr4\payroll_anomaly_model.pkl
   - Starts Flask on http://localhost:8000

Manual Setup
------------
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
python app.py

API Routes
----------
POST /predict/hr
  Input (JSON):
    { "records": [
        {
          "EmployeeID": 123,
          "Role": "Nurse",
          "Department": "ER",
          "Age": 29,
          "Tenure": 2.3,
          "BaseMonthlySalary": 18000,
          "OvertimeHours": 12,
          "AttendanceScore": 92,
          "PerformanceRating": 4,
          "HMOClaimsAnnual": 15000,
          "MonthlyHoursWorked": 170,
          "MonthlyOvertime": 8,
          "MonthlyLateCount": 1,
          "MonthlyAbsenceCount": 0
        }
      ] }
  Output (JSON):
    { "items": [
        {
          "employee_id": 123,
          "attrition_prob": 0.21,
          "next_month_net_pay": 22500.5,
          "overtime_anomaly": false,
          "promotion_prob": 0.12,
          "attrition_risk": 0.21,
          "next_month_net": 22500.5,
          "overtime_flag": false,
          "promotion_chance": 0.12
        }
      ] }

POST /predict/payroll
  Input (JSON):
    { "records": [ { /* payroll feature columns expected by your model */ } ] }
  Output (JSON):
    { "items": [ { "anomaly_flag": true, "anomaly_score": 0.87 } ] }

Testing with Postman
--------------------
- URL: http://localhost:8000/predict/hr  (POST, Body: raw JSON)
- URL: http://localhost:8000/predict/payroll  (POST, Body: raw JSON)

Calling from PHP (example)
--------------------------
<?php
$data = [ 'records' => [ [ 'EmployeeID' => 123, 'Role' => 'Nurse', 'Department' => 'ER', 'Age' => 29, 'Tenure' => 2.3, 'BaseMonthlySalary' => 18000, 'OvertimeHours' => 12, 'AttendanceScore' => 92, 'PerformanceRating' => 4, 'HMOClaimsAnnual' => 15000 ] ] ];
$ch = curl_init('http://localhost:8000/predict/hr');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$out = curl_exec($ch);
curl_close($ch);
echo $out;
?>

Relocating AI_SERVER
--------------------
- You can move this folder to another system, e.g., C:\xampp\htdocs\HR4\AI_SERVER
- Update config.json if DB host/user/password differ
- Run start_server.bat again on the new system

Offline Operation
-----------------
- No external APIs are required. All predictions run locally.

