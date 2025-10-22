@echo off
setlocal enableextensions enabledelayedexpansion

cd /d %~dp0

if not exist .venv (
  echo Creating virtual environment...
  py -3 -m venv .venv
)

if not exist .venv\Scripts\python.exe (
  echo Venv not created correctly. Ensure Python 3 is installed and on PATH.
  pause
  exit /b 1
)

echo Installing dependencies...
.venv\Scripts\python -m pip install --upgrade pip >nul 2>&1
.venv\Scripts\python -m pip install -r requirements.txt

rem Ensure models directory exists
if not exist models (
  mkdir models
)

rem Attempt to copy trained artifacts from ANALYTICSAI if present
set SRC1=C:\xampp\htdocs\ANALYTICSAI\ml\artifacts
if exist "%SRC1%\scaler.joblib" (
  copy /Y "%SRC1%\scaler.joblib" models\ >nul
  copy /Y "%SRC1%\attrition_lr.joblib" models\ >nul
  copy /Y "%SRC1%\pay_reg.joblib" models\ >nul
  copy /Y "%SRC1%\iso.joblib" models\ >nul
  copy /Y "%SRC1%\promo_lr.joblib" models\ >nul
  copy /Y "%SRC1%\role_map.json" models\ >nul
  copy /Y "%SRC1%\dept_map.json" models\ >nul
)

rem Attempt to copy payroll anomaly model if available
set SRC2=C:\xampp\htdocs\PAYROLLAI\hr4\payroll_anomaly_model.pkl
if exist "%SRC2%" (
  copy /Y "%SRC2%" models\payroll_anomaly_model.pkl >nul
)

echo Starting Flask server on port 8000...
.venv\Scripts\python app.py

endlocal

