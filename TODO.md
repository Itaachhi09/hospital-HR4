# Hospital HR System Transformation TODO

## Plan Summary
Transform the existing HR system into a hospital HR system by updating labels, terms, and data to hospital-specific context without breaking logic.

## Steps to Complete

1. **Update Database Schema (hr_integrated_db.sql)**
   - Change table names if needed (e.g., employees to staff)
   - Update field names and data (departments: Administration, Emergency, Surgery, Nursing; job roles: Doctor, Nurse, Technician; claim types: Medical Supplies, Equipment)
   - Update sample data to hospital context

2. **Update Main Pages**
   - Update index.php: Ensure all text reflects hospital context
   - Update admin_landing.php: Change title, footer, menu labels
   - Update employee_landing.php: Change labels to hospital terms

3. **Update Benefits Pages**
   - Update hmo_benefits.php: Adapt to hospital benefits
   - Update hmo_management.php: Update labels and terms

4. **Update JavaScript Files**
   - Update js/main.js: Change labels and text
   - Update js/dashboard/dashboard.js: Update dashboard labels
   - Update other relevant JS files

5. **Update PHP API Files**
   - Update php/api/get_employees.php: Change response labels
   - Update php/api/get_dashboard_summary.php: Update summary labels
   - Update other relevant API files

6. **Testing and Verification**
   - Test system functionality
   - Verify database connections
   - Check API responses

## Progress
- [x] Step 1: Update Database Schema
- [ ] Step 2: Update Main Pages
- [ ] Step 3: Update Benefits Pages
- [ ] Step 4: Update JavaScript Files
- [ ] Step 5: Update PHP API Files
- [ ] Step 6: Testing and Verification
