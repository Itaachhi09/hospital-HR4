# Hospital HR System - Complete Module Capabilities

**System:** Hospital HR Management System (HR4)  
**Last Updated:** January 24, 2026  
**Total Modules:** 13 Core Modules

---

## Table of Contents

1. [Authentication & User Management](#1-authentication--user-management)
2. [Employee Management](#2-employee-management)
3. [Payroll Management](#3-payroll-management)
4. [Benefits Management](#4-benefits-management)
5. [HMO Management](#5-hmo-management)
6. [Leave Management](#6-leave-management)
7. [Time & Attendance](#7-time--attendance)
8. [HR Analytics](#8-hr-analytics)
9. [HR Reports](#9-hr-reports)
10. [Core HR & Documents](#10-core-hr--documents)
11. [Organizational Structure](#11-organizational-structure)
12. [Dashboard](#12-dashboard)
13. [Notifications](#13-notifications)
14. [Integrations](#14-integrations)

---

## 1. Authentication & User Management

### 1.1 Authentication Operations
- **User Login**
  - Username/email-based authentication
  - Password verification with bcrypt hashing
  - JWT token generation
  - Session management
  - Last login tracking
  - Account status validation (active/inactive)
  - Support for partial username matching

- **User Logout**
  - Session destruction
  - Cookie cleanup
  - Complete session termination

- **Session Management**
  - Check active session status
  - Session validation
  - User information retrieval from session
  - HMO enrollment status in session

- **Two-Factor Authentication (2FA)** - *Currently Disabled*
  - 2FA code generation
  - Email-based 2FA delivery
  - Code verification
  - Expiry management (10-minute window)

- **Password Reset**
  - Password reset token generation
  - Email-based reset link delivery
  - Secure token validation
  - Password update functionality

### 1.2 User Management Operations
- **User CRUD Operations**
  - Create new user accounts
  - View all users with pagination
  - View single user details
  - Update user information
  - Soft delete users
  - User activation/deactivation

- **User Filtering & Search**
  - Filter by role ID
  - Filter by active status
  - Search by username/email
  - Pagination support

- **User Profile Management**
  - Update username
  - Change password (with validation)
  - Update role assignments
  - Enable/disable 2FA
  - Activate/deactivate accounts

- **User Validation**
  - Username uniqueness check
  - Password strength validation (minimum 8 characters)
  - Email format validation
  - Role assignment validation

### 1.3 Authorization & Access Control
- **Role-Based Access Control (RBAC)**
  - System Admin - Full access
  - HR Manager - HR operations access
  - HR Staff - Limited HR access
  - Employee - Self-service access
  - Manager - Team management access

- **Permission Checks**
  - User creation (Admin/HR Manager only)
  - User deletion (Admin/HR Manager only)
  - User updates (Self or Admin/HR Manager)
  - Role-based endpoint access

---

## 2. Employee Management

### 2.1 Employee CRUD Operations
- **Create Employee**
  - Full employee profile creation
  - Personal information (name, DOB, gender, marital status, nationality)
  - Contact information (email, phone, address)
  - Emergency contact details
  - Employment information (job title, department, manager, hire date)
  - Automatic user account creation (optional)
  - Initial salary setup (optional)
  - Employee from recruitment conversion

- **View Employees**
  - List all employees with pagination
  - View single employee details
  - Employee profile with complete information
  - Employee benefits view
  - Employee salary information
  - Employee documents list

- **Update Employee**
  - Update personal information
  - Update contact details
  - Update employment information
  - Update emergency contacts
  - Change department/position
  - Update manager assignment
  - Activate/deactivate employee

- **Delete Employee**
  - Soft delete employees
  - Preserve historical data
  - Admin/HR Manager only

### 2.2 Employee Filtering & Search
- **Advanced Filtering**
  - Filter by department ID
  - Filter by employment status (Active, On Leave, Suspended)
  - Filter by employment type (Regular, Contractual, Probationary)
  - Filter by manager ID
  - Filter by job title
  - Search by name, email, employee ID
  - Active/inactive status filter

- **Pagination**
  - Configurable page size
  - Total count and page information
  - Next/previous page navigation

### 2.3 Employee Profile Management
- **Profile Updates**
  - Self-service profile updates (employees)
  - Admin profile updates (HR/Admin)
  - Email validation
  - Address management
  - Emergency contact updates

- **Employee Data**
  - Complete employee information
  - Employment history
  - Department assignment
  - Manager hierarchy
  - Position details

### 2.4 Department Management
- **Department CRUD**
  - Create departments
  - View all departments
  - View single department
  - Update department information
  - Delete departments
  - Department hierarchy management

- **Department Operations**
  - Get employees by department
  - Department reordering (drag-drop)
  - Parent-child relationships
  - Department statistics

### 2.5 Position Management
- **Position Operations**
  - Get department position summary
  - View pay grade mappings
  - Position reports and graphs
  - Department-based position filtering

---

## 3. Payroll Management

### 3.1 Payroll Runs
- **Create Payroll Run**
  - Define pay period (start/end dates)
  - Set payment date
  - Add notes/descriptions
  - Draft status creation

- **View Payroll Runs**
  - List all payroll runs with pagination
  - Filter by status (Draft, Processing, Completed, Failed)
  - Filter by pay period dates
  - View single payroll run details
  - View payslips for a payroll run

- **Process Payroll**
  - Execute payroll calculation
  - Generate payslips
  - Calculate deductions
  - Apply bonuses and incentives
  - Process salary adjustments
  - Status tracking (Draft → Processing → Completed)

- **Update Payroll Run**
  - Modify pay period dates
  - Update payment date
  - Change status
  - Update notes

- **Delete Payroll Run**
  - Delete draft payroll runs
  - Prevent deletion of completed runs

### 3.2 Salary Management
- **Salary Operations**
  - View all salaries with filters
  - Filter by branch, department, position
  - Search functionality
  - View employee salary details
  - Get employee salary summary
  - View salary comparison data
  - View employee deductions overview

- **Salary Updates**
  - Add new salary record
  - Update existing salary
  - Salary adjustments
  - Effective date management
  - Salary history tracking

- **Salary Adjustments**
  - Create salary adjustments
  - Adjustment types (increase, decrease, promotion)
  - Effective date setting
  - Reason/justification tracking
  - Approval workflow support

### 3.3 Bonuses Management
- **Bonus Operations**
  - Create bonuses
  - View all bonuses
  - Filter bonuses
  - Update bonus information
  - Delete bonuses
  - Bonus assignment to employees

- **Bonus Types**
  - Performance bonuses
  - Annual bonuses
  - Special occasion bonuses
  - Custom bonus types

### 3.4 Deductions Management
- **Deduction Operations**
  - Create deductions
  - View all deductions
  - Filter deductions
  - Update deduction information
  - Delete deductions
  - Deduction assignment to employees

- **Deduction Types**
  - Tax deductions
  - Insurance deductions
  - Loan deductions
  - Custom deductions

### 3.5 Incentives Management
- **Incentive Operations**
  - Create incentives
  - View all incentives
  - Filter incentives
  - Update incentive information
  - Delete incentives
  - Incentive type management

### 3.6 Payslips
- **Payslip Operations**
  - Generate payslips for payroll runs
  - View payslip details
  - Download payslip (HTML/PDF)
  - View payslip history
  - Filter payslips by employee, date, status

- **Payslip Information**
  - Gross salary
  - Deductions breakdown
  - Bonuses and incentives
  - Net pay calculation
  - Pay period information
  - Payment date

### 3.7 Payroll V2 (Advanced)
- **Advanced Payroll Features**
  - Enhanced payroll processing
  - Multiple pay frequency support
  - Complex calculation rules
  - Pay band management
  - Salary grade management
  - Grade revisions tracking
  - Employee grade mapping

---

## 4. Benefits Management

### 4.1 Benefits CRUD Operations
- **Create Benefits**
  - Define benefit name and description
  - Set benefit type
  - Configure amount (fixed or percentage)
  - Set active status
  - Benefit category assignment

- **View Benefits**
  - List all benefits with pagination
  - Filter by benefit type
  - Filter by active status
  - Search functionality
  - View single benefit details

- **Update Benefits**
  - Update benefit information
  - Modify amounts
  - Change benefit type
  - Activate/deactivate benefits

- **Delete Benefits**
  - Soft delete benefits
  - Preserve historical assignments

### 4.2 Benefit Categories
- **Category Management**
  - Create benefit categories
  - View all categories
  - Update category information
  - Category descriptions

### 4.3 Employee Benefit Assignment
- **Assignment Operations**
  - Assign benefits to employees
  - Set benefit amount per employee
  - Define start and end dates
  - Set assignment status
  - Add notes to assignments

- **Employee Benefit Viewing**
  - View all benefits for an employee
  - View active benefits
  - View benefit history
  - Benefit details and amounts

### 4.4 Benefit Types
- **Supported Types**
  - Health insurance
  - Life insurance
  - Retirement plans
  - Transportation allowance
  - Meal allowance
  - Custom benefit types

---

## 5. HMO Management

### 5.1 HMO Providers
- **Provider Operations**
  - Create HMO providers
  - View all providers
  - View provider details
  - Update provider information
  - Delete providers
  - Provider metrics and statistics

- **Provider Information**
  - Provider name and code
  - Contact information
  - Active status
  - Enrollment statistics
  - Claims statistics

### 5.2 HMO Plans
- **Plan Operations**
  - Create HMO plans
  - View all plans
  - View plan details
  - Update plan information
  - Delete plans
  - Plan assignment to providers

- **Plan Information**
  - Plan name and description
  - Coverage details
  - Premium amounts
  - Benefits included
  - Provider association

### 5.3 Employee Enrollments
- **Enrollment Operations**
  - Enroll employees in HMO plans
  - View all enrollments
  - View employee enrollments
  - Update enrollment information
  - Cancel enrollments
  - Enrollment history

- **Enrollment Details**
  - Employee information
  - Plan and provider
  - Enrollment date
  - Coverage period
  - Dependent information
  - Status tracking

### 5.4 HMO Claims
- **Claim Operations**
  - Submit claims
  - View all claims
  - View employee claims
  - View claim details
  - Update claim status
  - Delete claims
  - Claim attachment upload

- **Claim Types**
  - Create claim types
  - View claim types
  - Update claim types
  - Delete claim types
  - Claim type categories

- **Claim Status Management**
  - Submitted
  - Under Review
  - Approved
  - Rejected
  - Paid
  - Status change tracking

- **Claim Attachments**
  - Upload supporting documents
  - View attachments
  - Download attachments
  - File type validation
  - Size limits (5MB)

### 5.5 HMO Dashboard & Analytics
- **Dashboard Features**
  - Enrollment overview
  - Claims statistics
  - Provider performance
  - Cost analysis
  - Utilization metrics

- **Analytics**
  - Benefit types summary
  - Claims by type
  - Provider comparison
  - Cost trends
  - Enrollment trends

---

## 6. Leave Management

### 6.1 Leave Types
- **Leave Type Operations**
  - Create leave types
  - View all leave types
  - View single leave type
  - Update leave type information
  - Delete leave types

- **Leave Type Configuration**
  - Type name and description
  - Maximum days allowed
  - Carry-over rules
  - Accrual rules
  - Active status

### 6.2 Leave Requests
- **Request Operations**
  - Submit leave requests
  - View all leave requests
  - View employee leave requests
  - View single request details
  - Update request status
  - Delete leave requests

- **Leave Request Status**
  - Pending
  - Approved
  - Rejected
  - Cancelled
  - Status change tracking

### 6.3 Leave Balances
- **Balance Operations**
  - View employee leave balances
  - Balance by leave type
  - Annual balance tracking
  - Used days calculation
  - Available days calculation
  - Pending days calculation

### 6.4 Leave Management Features
- **Approval Workflow**
  - Manager approval
  - HR approval
  - Multi-level approvals
  - Approval notifications

- **Leave Calculations**
  - Automatic balance updates
  - Accrual calculations
  - Carry-over processing
  - Proration for partial years

**Note:** Leave module is currently disabled for HR3 integration but API endpoints are preserved.

---

## 7. Time & Attendance

### 7.1 Attendance Records
- **Attendance Operations**
  - Create attendance records
  - View all attendance records
  - View employee attendance
  - View single attendance record
  - Update attendance records
  - Delete attendance records

- **Attendance Types**
  - Clock in/out
  - Manual entry
  - Overtime tracking
  - Absence recording

### 7.2 Schedules
- **Schedule Operations**
  - Create work schedules
  - View all schedules
  - View employee schedules
  - Update schedules
  - Delete schedules

- **Schedule Information**
  - Work days
  - Work hours
  - Break times
  - Schedule templates

### 7.3 Shifts
- **Shift Operations**
  - Create shifts
  - View all shifts
  - View employee shifts
  - Update shifts
  - Delete shifts

- **Shift Management**
  - Shift names and codes
  - Start and end times
  - Break periods
  - Shift rotation

### 7.4 Timesheets
- **Timesheet Operations**
  - Create timesheets
  - View all timesheets
  - View employee timesheets
  - View timesheet details
  - Update timesheet status
  - Approve/reject timesheets

- **Timesheet Information**
  - Work hours
  - Overtime hours
  - Regular hours
  - Date range
  - Status tracking

- **Timesheet Status**
  - Draft
  - Submitted
  - Approved
  - Rejected
  - Status change workflow

**Note:** Attendance module is currently disabled for HR3 integration but API endpoints are preserved.

---

## 8. HR Analytics

### 8.1 Executive Summary
- **Key Performance Indicators (KPIs)**
  - Total headcount
  - Active employees
  - New hires (30 days)
  - Turnover rate
  - Average tenure
  - Department distribution
  - Payroll costs
  - Benefits utilization

### 8.2 Workforce Analytics
- **Headcount Trends**
  - 12-month headcount trend
  - Monthly/quarterly/yearly views
  - Department breakdown
  - Position breakdown

- **Turnover Analysis**
  - Turnover rate by department
  - Turnover trends over time
  - Voluntary vs involuntary
  - Exit reasons analysis

- **Employee Demographics**
  - Age distribution
  - Gender distribution
  - Department distribution
  - Position distribution
  - Tenure distribution
  - Geographic distribution

### 8.3 Payroll Analytics
- **Payroll Trends**
  - Payroll cost trends
  - Basic salary trends
  - Overtime costs
  - Bonus trends
  - Department cost breakdown

- **Compensation Analysis**
  - Salary ranges by position
  - Salary distribution
  - Pay grade analysis
  - Compensation ratios
  - Market comparison data

### 8.4 Benefits Analytics
- **Benefits Utilization**
  - Benefits enrollment rates
  - HMO utilization
  - Claims analysis
  - Cost per employee
  - Benefits ROI

- **HMO Analytics**
  - Enrollment by provider
  - Claims by type
  - Cost trends
  - Utilization rates

### 8.5 Training & Development Analytics
- **Training Metrics**
  - Training completion rates
  - Training hours per employee
  - Training costs
  - Skill development tracking
  - Performance improvement metrics

### 8.6 HR Analytics Metrics Framework
- **Metric Categories**
  - Workforce metrics
  - Compensation metrics
  - Benefits metrics
  - Performance metrics
  - Retention metrics

- **Metric Operations**
  - Calculate specific metrics
  - View metric definitions
  - Get metric trends
  - Metric summaries
  - Dashboard metrics
  - Performance statistics
  - Metric alerts
  - Export metrics

- **Advanced Features**
  - Custom metric definitions
  - Metric filtering
  - Time period selection
  - Department/position filtering
  - Metric caching
  - Automated calculations

### 8.7 Analytics Dashboard
- **Dashboard Views**
  - Executive dashboard
  - HR manager dashboard
  - Department dashboard
  - Custom dashboards

- **Visualizations**
  - Charts and graphs
  - Trend lines
  - Comparative analysis
  - Drill-down capabilities

---

## 9. HR Reports

### 9.1 Report Types
- **Employee Demographics Report**
  - Complete employee information
  - Department breakdown
  - Position analysis
  - Demographic statistics

- **Recruitment & Application Report**
  - Application tracking
  - Recruitment funnel
  - Time-to-hire metrics
  - Source analysis

- **Payroll & Compensation Report**
  - Payroll summary
  - Salary analysis
  - Bonus and incentive report
  - Deduction analysis

- **Attendance & Leave Report**
  - Attendance summary
  - Leave utilization
  - Absenteeism analysis
  - Overtime report

- **Benefits & HMO Utilization Report**
  - Benefits enrollment
  - HMO claims analysis
  - Cost analysis
  - Utilization trends

- **Training & Development Report**
  - Training completion
  - Skill development
  - Performance improvement
  - Training ROI

- **Employee Relations & Engagement Report**
  - Engagement metrics
  - Survey results
  - Feedback analysis
  - Satisfaction scores

- **Turnover & Retention Report**
  - Turnover rates
  - Retention analysis
  - Exit interview data
  - Retention strategies

- **Compliance & Document Report**
  - Document status
  - Compliance tracking
  - Expiring documents
  - Missing documents

- **Executive Summary Report**
  - High-level overview
  - Key metrics
  - Strategic insights
  - Recommendations

### 9.2 Report Operations
- **Report Generation**
  - Generate reports on-demand
  - Custom date ranges
  - Department/position filters
  - Employee filters

- **Report Export**
  - Export to PDF
  - Export to Excel
  - Export to CSV
  - Custom formats

- **Report Scheduling**
  - Schedule recurring reports
  - Email delivery
  - Automated generation
  - Schedule management

- **Report Access Control**
  - Role-based access
  - Department restrictions
  - User permissions
  - Audit trail

### 9.3 Report Dashboard
- **Dashboard Features**
  - Report list
  - Quick access
  - Recent reports
  - Favorite reports
  - Report categories

### 9.4 Audit Trail
- **Audit Features**
  - Report access logging
  - User activity tracking
  - Report generation history
  - Export tracking

---

## 10. Core HR & Documents

### 10.1 Document Management
- **Document Operations**
  - Upload documents
  - View all documents
  - View employee documents
  - Download documents
  - Delete documents
  - Secure document access

- **Document Types**
  - Employee contracts
  - Certificates
  - Licenses
  - Training certificates
  - Performance reviews
  - Custom document types

- **Document Features**
  - File type validation (PDF, DOC, DOCX, JPG, PNG)
  - Size limits (5MB)
  - Secure storage
  - Per-employee organization
  - Document expiration tracking
  - Auto-categorization

### 10.2 Document Security
- **Access Control**
  - Employee self-access
  - HR/Admin full access
  - Role-based permissions
  - Secure download tokens
  - Time-limited access

### 10.3 Document Analytics
- **Analytics Features**
  - Document count by type
  - Expiring documents list
  - Missing documents tracking
  - Document compliance status

### 10.4 HR Core Integration
- **Integration Features**
  - HR Core document sync
  - Integration status
  - Document preview
  - Secure download links

---

## 11. Organizational Structure

### 11.1 Organization Hierarchy
- **Hierarchy Operations**
  - View complete org structure
  - Hierarchical tree view
  - Department relationships
  - Manager-employee relationships
  - Position mapping

### 11.2 Department Management
- **Department Operations**
  - Create departments
  - Update departments
  - Delete departments
  - Reorder departments (drag-drop)
  - Parent-child relationships
  - Department types

### 11.3 Organization Snapshots
- **Snapshot Operations**
  - Create org structure snapshots
  - View historical snapshots
  - Compare snapshots
  - Snapshot naming and notes
  - Timestamp tracking

### 11.4 Hospital-Specific Structure
- **Hospital Org Structure**
  - Hospital-specific hierarchy
  - Department organization
  - Position assignments
  - Manager assignments
  - Employee counts per department

---

## 12. Dashboard

### 12.1 Role-Based Dashboards
- **System Admin/HR Admin Dashboard**
  - Total employees count
  - Active employees count
  - Inactive employees count
  - Pending leave requests (placeholder)
  - Total departments
  - Recent hires (last 30 days)
  - Employee status distribution chart
  - Leave requests by type chart (placeholder)
  - Employee distribution by department chart

- **Manager Dashboard**
  - Team members count
  - Pending team leave requests
  - Pending timesheets for team
  - Pending claims for team
  - Open tasks summary

- **Employee Dashboard**
  - Available leave days
  - Pending claims count
  - Upcoming payslip date
  - My documents count
  - Leave summary chart (Available, Used, Pending)

### 12.2 Dashboard Features
- **Real-Time Data**
  - Live statistics
  - Current counts
  - Recent activity

- **Visualizations**
  - Charts and graphs
  - Distribution visualizations
  - Trend indicators

- **Quick Actions**
  - Quick access to modules
  - Common tasks
  - Notifications

---

## 13. Notifications

### 13.1 Notification Operations
- **Notification Management**
  - Get all notifications
  - Get unread notifications
  - Mark notification as read
  - Delete notifications
  - Notification filtering

### 13.2 Notification Types
- **System Notifications**
  - Leave request updates
  - Timesheet approvals
  - Payroll notifications
  - Document expirations
  - HMO claim updates

### 13.3 Notification Features
- **Features**
  - Real-time notifications
  - Unread count tracking
  - Notification badges
  - Notification history
  - User-specific notifications

---

## 14. Integrations

### 14.1 HR Core Integration
- **Integration Features**
  - Employee data sync
  - Document sync
  - Data synchronization
  - Integration status
  - Sync history

### 14.2 Payroll Integration
- **Integration Features**
  - Payroll data sync
  - Salary updates
  - Bulk salary updates
  - Payroll impact calculations
  - Integration status

### 14.3 Finance Integration
- **Integration Features**
  - Post payroll to finance
  - Budget allocation
  - Cost center mapping
  - Financial reporting
  - Integration status

### 14.4 Analytics Integration
- **Integration Features**
  - Analytics data sync
  - Metric calculations
  - Report generation
  - Data aggregation
  - Integration status

### 14.5 Compensation Planning Integration
- **Integration Features**
  - Compensation plan creation
  - Salary simulations
  - Plan linking
  - Impact analysis
  - Approval workflows

### 14.6 HMO Integration
- **HMO-Payroll Integration**
  - HMO deductions sync
  - Payroll integration
  - Cost calculations

- **HMO-Analytics Integration**
  - HMO analytics sync
  - Utilization metrics
  - Cost analysis

### 14.7 Reporting System Integration
- **Integration Features**
  - Report generation
  - Export handling
  - Scheduling
  - Access control
  - Audit trail

---

## System-Wide Features

### Security Features
- JWT token authentication
- Session-based authentication
- Role-based access control (RBAC)
- Password hashing (bcrypt)
- Input validation and sanitization
- SQL injection protection (prepared statements)
- CORS configuration
- Secure file uploads

### Data Management
- Pagination support
- Filtering and search
- Sorting capabilities
- Data validation
- Error handling
- Transaction support

### API Features
- RESTful API design
- JSON responses
- Standard HTTP status codes
- Error handling
- Request validation
- Response formatting

### User Experience
- Role-based dashboards
- Real-time notifications
- Responsive design
- Intuitive navigation
- Quick actions
- Search functionality

---

## Module Status Summary

| Module | Status | Notes |
|--------|--------|-------|
| Authentication & User Management | ✅ Active | Full functionality |
| Employee Management | ✅ Active | Full functionality |
| Payroll Management | ✅ Active | Full functionality |
| Benefits Management | ✅ Active | Full functionality |
| HMO Management | ✅ Active | Full functionality |
| Leave Management | ⚠️ Disabled | Ready for HR3 integration |
| Time & Attendance | ⚠️ Disabled | Ready for HR3 integration |
| HR Analytics | ✅ Active | Full functionality |
| HR Reports | ✅ Active | Full functionality |
| Core HR & Documents | ✅ Active | Full functionality |
| Organizational Structure | ✅ Active | Full functionality |
| Dashboard | ✅ Active | Full functionality |
| Notifications | ✅ Active | Full functionality |
| Integrations | ✅ Active | Full functionality |

---

## API Endpoint Summary

### Total Endpoints: 150+

**By Module:**
- Authentication: 8 endpoints
- User Management: 5 endpoints
- Employee Management: 15+ endpoints
- Payroll Management: 25+ endpoints
- Benefits Management: 10+ endpoints
- HMO Management: 20+ endpoints
- Leave Management: 9 endpoints (disabled)
- Attendance: 10 endpoints (disabled)
- HR Analytics: 15+ endpoints
- HR Reports: 20+ endpoints
- Documents: 8+ endpoints
- Organizational Structure: 5+ endpoints
- Dashboard: 1 endpoint
- Notifications: 4 endpoints
- Integrations: 10+ endpoints

---

**Document Version:** 1.0  
**Last Review:** January 24, 2026
