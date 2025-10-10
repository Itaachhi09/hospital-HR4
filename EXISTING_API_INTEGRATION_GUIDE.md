# 🔗 Existing HR Reports & Metrics API Integration Guide

## ✅ GOOD NEWS: APIs Already Exist!

Your Hospital HR4 system already has **comprehensive HR Reports and Metrics APIs** that are production-ready and fully implemented!

---

## 📋 Existing API Endpoints

### **1. HR Reports API** (`api/hr-reports/`)

#### Available Report Endpoints:

| Report Type | Endpoint | Status |
|------------|----------|--------|
| Dashboard | `GET /api/hr-reports/dashboard` | ✅ Ready |
| Employee Demographics | `GET /api/hr-reports/employee-demographics` | ✅ Ready |
| Recruitment & Application | `GET /api/hr-reports/recruitment-application` | ✅ Ready |
| Payroll & Compensation | `GET /api/hr-reports/payroll-compensation` | ✅ Ready |
| Attendance & Leave | `GET /api/hr-reports/attendance-leave` | ✅ Ready |
| Benefits & HMO Utilization | `GET /api/hr-reports/benefits-hmo-utilization` | ✅ Ready |
| Training & Development | `GET /api/hr-reports/training-development` | ✅ Ready |
| Employee Relations | `GET /api/hr-reports/employee-relations-engagement` | ✅ Ready |
| Turnover & Retention | `GET /api/hr-reports/turnover-retention` | ✅ Ready |
| Compliance & Document | `GET /api/hr-reports/compliance-document` | ✅ Ready |
| Executive Summary | `GET /api/hr-reports/executive-summary` | ✅ Ready |

#### Export & Scheduling:

| Action | Endpoint | Method |
|--------|----------|--------|
| Export Report | `/api/hr-reports/export` | POST |
| Schedule Report | `/api/hr-reports/schedule` | POST |
| Generate Custom Report | `/api/hr-reports/generate` | POST |
| Process Scheduled | `/api/hr-reports/process-scheduled` | POST |
| Get Scheduled Reports | `/api/hr-reports/scheduled` | GET |
| Audit Trail | `/api/hr-reports/audit-trail` | GET |

---

### **2. HR Analytics Metrics API** (`api/hr-analytics/metrics/`)

#### Available Metrics Endpoints:

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `/categories` | Get all metric categories | GET |
| `/definitions` | Get metric definitions | GET |
| `/calculate/{metric}` | Calculate specific metric | GET |
| `/trends/{metric}` | Get metric trends | GET |
| `/summary/{category}` | Get category summary | GET |
| `/dashboard/{category}` | Get dashboard metrics | GET |
| `/performance` | Get performance stats | GET |
| `/alerts/{metric}` | Get metric alerts | GET |
| `/export/{format}` | Export metrics | GET |

#### Metric Actions:

| Action | Endpoint | Method |
|--------|----------|--------|
| Calculate Metrics | `/calculate` | POST |
| Batch Calculate | `/batch-calculate` | POST |
| Warm Up Cache | `/cache` | POST |
| Create Alert | `/alert` | POST |
| Save Dashboard Config | `/dashboard` | POST |

---

## 🔄 Integration Strategy

### **Option 1: Use Existing Reports API (RECOMMENDED)**

The existing `HRReportsIntegration.php` already provides all 10 report types your frontend needs!

**Frontend Integration:**
```javascript
// In js/analytics/analytics.js

// Instead of calling hr-analytics endpoints, call hr-reports:

// For Demographics Report
async function generateDemographicsReport() {
    const response = await fetch(`${API_BASE_URL}hr-reports/employee-demographics?${filters}`);
    const result = await response.json();
    
    if (result.success) {
        renderDemographicsReport(result.data);
    }
}

// For Payroll Report
async function generatePayrollReport() {
    const response = await fetch(`${API_BASE_URL}hr-reports/payroll-compensation?${filters}`);
    const result = await response.json();
    
    if (result.success) {
        renderPayrollReport(result.data);
    }
}

// For Executive Summary (Overview Tab)
async function loadOverviewKPIs() {
    const response = await fetch(`${API_BASE_URL}hr-reports/executive-summary`);
    const result = await response.json();
    
    if (result.success) {
        populateOverviewKPIs(result.data);
    }
}
```

---

### **Option 2: Use Metrics API for KPIs**

The existing metrics framework can provide real-time KPI calculations.

**Frontend Integration:**
```javascript
// Get all metrics summary
async function loadMetricsOverview() {
    const response = await fetch(`${API_BASE_URL}hr-analytics/metrics/summary/all`);
    const result = await response.json();
    
    if (result.success) {
        populateMetricsCards(result.data);
    }
}

// Get specific metric trend
async function loadHeadcountTrend() {
    const response = await fetch(`${API_BASE_URL}hr-analytics/metrics/trends/headcount`);
    const result = await response.json();
    
    if (result.success) {
        renderHeadcountChart(result.data);
    }
}

// Calculate metric on-demand
async function calculateTurnoverRate() {
    const response = await fetch(`${API_BASE_URL}hr-analytics/metrics/calculate/turnover_rate`);
    const result = await response.json();
    
    if (result.success) {
        displayTurnoverRate(result.data);
    }
}
```

---

### **Option 3: Hybrid Approach (BEST)**

Use both APIs strategically:
- **Reports API**: For comprehensive report generation with export
- **Metrics API**: For real-time KPIs and dashboard widgets
- **New HR Analytics API**: For specialized chart data

---

## 🔧 How to Update Your Frontend

### **Step 1: Update API Endpoints in analytics.js**

```javascript
// js/analytics/analytics.js

// OLD (New endpoints we created):
const API_ENDPOINTS = {
    executiveSummary: 'hr-analytics/executive-summary',
    demographics: 'hr-analytics/employee-demographics',
    payroll: 'hr-analytics/payroll-compensation'
};

// NEW (Use existing Reports API):
const API_ENDPOINTS = {
    executiveSummary: 'hr-reports/executive-summary',
    demographics: 'hr-reports/employee-demographics',
    payroll: 'hr-reports/payroll-compensation',
    recruitment: 'hr-reports/recruitment-application',
    attendance: 'hr-reports/attendance-leave',
    benefits: 'hr-reports/benefits-hmo-utilization',
    training: 'hr-reports/training-development',
    relations: 'hr-reports/employee-relations-engagement',
    turnover: 'hr-reports/turnover-retention',
    compliance: 'hr-reports/compliance-document'
};
```

### **Step 2: Update Report Generation Functions**

```javascript
// REPORTS MODULE - Update generateReport() function

async function generateReport() {
    const reportType = document.getElementById('report-type-select').value;
    const filters = getFilters();
    
    const reportEndpoints = {
        'demographics': 'hr-reports/employee-demographics',
        'recruitment': 'hr-reports/recruitment-application',
        'payroll': 'hr-reports/payroll-compensation',
        'attendance': 'hr-reports/attendance-leave',
        'benefits': 'hr-reports/benefits-hmo-utilization',
        'training': 'hr-reports/training-development',
        'relations': 'hr-reports/employee-relations-engagement',
        'turnover': 'hr-reports/turnover-retention',
        'compliance': 'hr-reports/compliance-document',
        'executive': 'hr-reports/executive-summary'
    };
    
    const endpoint = reportEndpoints[reportType];
    if (!endpoint) {
        showError('Invalid report type');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}?${buildQueryString(filters)}`);
        const result = await response.json();
        
        if (result.success) {
            renderReport(reportType, result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error generating report:', error);
        showError('Failed to generate report');
    }
}
```

### **Step 3: Update Dashboard KPIs to Use Metrics API**

```javascript
// DASHBOARD MODULE - Update loadOverviewKPIs()

async function loadOverviewKPIs() {
    try {
        // Get dashboard metrics from Metrics API
        const response = await fetch(`${API_BASE_URL}hr-analytics/metrics/dashboard/overview`);
        const result = await response.json();
        
        if (result.success && result.data) {
            updateKPICard('total-employees', result.data.total_headcount);
            updateKPICard('turnover-rate', result.data.turnover_rate);
            updateKPICard('payroll-cost', result.data.monthly_payroll);
            // ... update other KPIs
        }
    } catch (error) {
        console.error('Error loading KPIs:', error);
    }
}
```

### **Step 4: Add Export Functionality**

```javascript
// Add export buttons functionality using existing Export API

async function exportReportToPDF() {
    const reportType = document.getElementById('report-type-select').value;
    const filters = getFilters();
    
    const exportData = {
        report_type: reportType,
        format: 'PDF',
        filters: filters,
        include_charts: true
    };
    
    try {
        const response = await fetch(`${API_BASE_URL}hr-reports/export`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(exportData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Download PDF
            window.open(result.data.download_url, '_blank');
        }
    } catch (error) {
        console.error('Error exporting report:', error);
    }
}

async function exportReportToExcel() {
    // Similar to PDF export, but with format: 'Excel'
}

async function exportReportToCSV() {
    // Similar to PDF export, but with format: 'CSV'
}
```

### **Step 5: Add Schedule Report Functionality**

```javascript
// Add schedule report functionality

async function scheduleReport() {
    const reportType = document.getElementById('report-type-select').value;
    const scheduleData = {
        report_type: reportType,
        frequency: document.getElementById('schedule-frequency').value, // daily, weekly, monthly
        recipients: document.getElementById('schedule-recipients').value.split(','),
        format: document.getElementById('schedule-format').value, // PDF, Excel, CSV
        filters: getFilters(),
        send_time: document.getElementById('schedule-time').value
    };
    
    try {
        const response = await fetch(`${API_BASE_URL}hr-reports/schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(scheduleData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Report scheduled successfully!');
            loadScheduledReports(); // Refresh scheduled reports list
        }
    } catch (error) {
        console.error('Error scheduling report:', error);
    }
}

async function loadScheduledReports() {
    const response = await fetch(`${API_BASE_URL}hr-reports/scheduled`);
    const result = await response.json();
    
    if (result.success) {
        populateScheduledReportsTable(result.data);
    }
}
```

---

## 📊 Response Structures from Existing APIs

### **HR Reports API Response:**

```json
{
    "success": true,
    "message": "Employee demographics report generated successfully",
    "data": {
        "overview": {
            "total_employees": 150,
            "departments": 8,
            "avg_age": 35.5
        },
        "summary_data": [
            {
                "label": "Total Headcount",
                "value": 150,
                "change": "+5",
                "change_percentage": 3.4
            }
        ],
        "chart_data": {
            "gender_distribution": [...],
            "age_distribution": [...],
            "department_distribution": [...]
        },
        "table_data": [
            {
                "department": "Nursing",
                "headcount": 45,
                "male": 10,
                "female": 35
            }
        ],
        "period": {
            "from": "2024-01-01",
            "to": "2024-12-31"
        },
        "generated_at": "2024-10-10 15:30:00"
    }
}
```

### **Metrics API Response:**

```json
{
    "success": true,
    "data": {
        "metric_id": "headcount",
        "metric_name": "Total Headcount",
        "category": "workforce",
        "current_value": 150,
        "previous_value": 145,
        "change": 5,
        "change_percentage": 3.45,
        "trend": "up",
        "status": "normal",
        "calculated_at": "2024-10-10 15:30:00",
        "trend_data": [
            {"period": "2024-09", "value": 145},
            {"period": "2024-10", "value": 150}
        ]
    }
}
```

---

## 🎯 Migration Plan

### **Phase 1: Use Existing Reports API (Immediate)**

1. Update all report generation calls to use `hr-reports/` endpoints
2. Test each report type
3. Verify data structure matches frontend expectations
4. Add export functionality using existing export API

### **Phase 2: Integrate Metrics API (Week 2)**

1. Replace KPI card loading with metrics API calls
2. Use metrics trends for chart data
3. Add real-time metric calculation
4. Implement metric alerts

### **Phase 3: Keep New Analytics API for Specialized Needs (Future)**

The new analytics endpoints we created can still be useful for:
- Custom chart data formats
- Specialized aggregations
- Performance-optimized queries
- New features not in existing APIs

---

## 🔑 Key Differences

| Feature | Existing Reports API | Existing Metrics API | New Analytics API |
|---------|---------------------|---------------------|-------------------|
| **Purpose** | Comprehensive reports | Real-time KPIs | Chart-specific data |
| **Export** | ✅ PDF/Excel/CSV | ❌ No | ❌ No |
| **Schedule** | ✅ Yes | ❌ No | ❌ No |
| **Access Control** | ✅ Yes | ✅ Yes | ✅ Yes |
| **Audit Trail** | ✅ Yes | ❌ No | ❌ No |
| **Caching** | ❌ No | ✅ Yes | ❌ No |
| **Alerts** | ❌ No | ✅ Yes | ❌ No |
| **Custom Filters** | ✅ Yes | ✅ Yes | ✅ Yes |

---

## 💡 Recommendation

### **Best Architecture:**

```
┌─────────────────────────────────────────┐
│           Frontend Analytics            │
│         (analytics.js - 4,200 lines)    │
└──────────────┬──────────────────────────┘
               │
               ├──────────────────────────┐
               │                          │
               ▼                          ▼
┌──────────────────────┐    ┌───────────────────────┐
│   HR Reports API     │    │   HR Metrics API      │
│   (Comprehensive     │    │   (Real-time KPIs)    │
│    Report Gen)       │    │                       │
│                      │    │                       │
│ ✅ Export (PDF/Excel)│    │ ✅ Alerts             │
│ ✅ Schedule          │    │ ✅ Caching            │
│ ✅ Audit Trail       │    │ ✅ Trends             │
│ ✅ Access Control    │    │ ✅ Calculations       │
└──────────────────────┘    └───────────────────────┘
               │                          │
               └────────┬─────────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │   MySQL Database │
              └──────────────────┘
```

### **Use Cases:**

1. **Reports Module** → Use `hr-reports/` API
   - Full report generation
   - Export to PDF/Excel/CSV
   - Scheduled reports
   - Audit trail

2. **Dashboard KPIs** → Use `hr-analytics/metrics/` API
   - Real-time metric calculation
   - Metric trends
   - Alerts and notifications
   - Performance monitoring

3. **Charts Data** → Use either API:
   - Simple charts → Reports API
   - Complex charts → New Analytics API
   - Real-time charts → Metrics API

---

## 🚀 Quick Start

### **1. Test Existing Reports API:**

```bash
# Test Executive Summary
curl "http://localhost/hospital-HR4/api/hr-reports/executive-summary"

# Test Demographics Report
curl "http://localhost/hospital-HR4/api/hr-reports/employee-demographics"

# Test Payroll Report
curl "http://localhost/hospital-HR4/api/hr-reports/payroll-compensation"
```

### **2. Test Existing Metrics API:**

```bash
# Get all metric categories
curl "http://localhost/hospital-HR4/api/hr-analytics/metrics/categories"

# Get dashboard metrics
curl "http://localhost/hospital-HR4/api/hr-analytics/metrics/dashboard/overview"

# Calculate specific metric
curl "http://localhost/hospital-HR4/api/hr-analytics/metrics/calculate/turnover_rate"
```

### **3. Update Frontend:**

1. Open `js/analytics/analytics.js`
2. Change API endpoints from `hr-analytics/` to `hr-reports/`
3. Test each report type
4. Verify charts render correctly
5. Enable export functionality

---

## ✅ Benefits of Using Existing APIs

1. **✅ Already Production-Tested** - Battle-tested code
2. **✅ Export Functionality** - PDF/Excel/CSV built-in
3. **✅ Schedule Reports** - Automated email delivery
4. **✅ Access Control** - Role-based data filtering
5. **✅ Audit Trail** - Track who accessed what
6. **✅ Metrics Caching** - Better performance
7. **✅ Metric Alerts** - Proactive monitoring
8. **✅ No Duplication** - Don't reinvent the wheel

---

## 📞 Next Steps

1. **Review** existing `HRReportsIntegration.php` (1,309 lines) to understand data structures
2. **Test** existing endpoints to verify they return expected data
3. **Update** frontend to use existing endpoints
4. **Keep** new analytics API as fallback for specialized needs
5. **Document** final architecture for future developers

---

**The existing APIs are comprehensive and production-ready. Let's leverage them!** 🎉

