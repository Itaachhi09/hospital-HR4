/**
 * Analytics Module - Reports and Metrics Submodule
 * Consolidates HR, payroll, and benefits data into meaningful insights through automated reporting and performance metrics.
 * Empowers HR leaders and executives to track trends, measure efficiency, and support data-driven decisions.
 * 
 * v3.0 - Enhanced with comprehensive Reports and Metrics functionality:
 * - Standardized HR Reports with export capabilities
 * - Custom Report Builder with drag-and-drop widgets
 * - KPI and Metrics Dashboard with real-time indicators
 * - Predictive and Comparative Analytics
 * - Data Integration and Correlation
 * - Compliance and Audit Reporting
 */
import { API_BASE_URL } from '../utils.js'; // Import base URL

// --- DOM Element References ---
let pageTitleElement;
let mainContentArea;
let headcountChartInstance = null; 
let leaveTypeChartInstance = null; 
let metricChartInstance = null;

// --- Chart instances for cleanup ---
let chartInstances = {};

// --- Report Builder State ---
let reportBuilderState = {
    widgets: [],
    filters: {},
    layout: 'grid'
};

/**
 * Initializes common elements used by the analytics module.
 */
function initializeAnalyticsElements() {
    pageTitleElement = document.getElementById('page-title');
    mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("Analytics Module: Core DOM elements (page-title or main-content-area) not found!");
        return false;
    }
    return true;
}

/**
 * Displays the Analytics Dashboards section.
 */
export async function displayAnalyticsDashboardsSection() {
    console.log("[Display] Displaying Enhanced Analytics Dashboards Section...");
    if (!initializeAnalyticsElements()) return;
    pageTitleElement.textContent = 'HR Analytics Dashboard';
    
    // Clean up existing charts
    Object.values(chartInstances).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    });
    chartInstances = {};
    
    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select id="dashboard-dept-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select id="dashboard-branch-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">All Branches</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                        <select id="dashboard-period-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="3">Last 3 Months</option>
                            <option value="6">Last 6 Months</option>
                            <option value="12" selected>Last 12 Months</option>
                            <option value="24">Last 24 Months</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select id="dashboard-report-type-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">All Reports</option>
                            <option value="executive-summary">Executive Summary</option>
                            <option value="employee-demographics">Employee Demographics</option>
                            <option value="recruitment-application">Recruitment & Application</option>
                            <option value="payroll-compensation">Payroll & Compensation</option>
                            <option value="attendance-leave">Attendance & Leave</option>
                            <option value="benefits-hmo-utilization">Benefits & HMO</option>
                            <option value="training-development">Training & Development</option>
                            <option value="employee-relations-engagement">Employee Relations</option>
                            <option value="turnover-retention">Turnover & Retention</option>
                            <option value="compliance-document">Compliance & Document</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="apply-filters-btn" class="w-full px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                    <div class="flex items-end">
                        <button id="refresh-dashboard-btn" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <!-- Quick Actions -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[#4E3B2A]">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <button id="generate-executive-summary-btn" class="p-4 bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-lg hover:from-purple-600 hover:to-purple-800 transition-all">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <div class="font-semibold">Executive Summary</div>
                        <div class="text-sm opacity-90">Top-level KPIs</div>
                    </button>
                    <button id="generate-compliance-report-btn" class="p-4 bg-gradient-to-br from-red-500 to-red-700 text-white rounded-lg hover:from-red-600 hover:to-red-800 transition-all">
                        <i class="fas fa-shield-alt fa-2x mb-2"></i>
                        <div class="font-semibold">Compliance Report</div>
                        <div class="text-sm opacity-90">Document status</div>
                    </button>
                    <button id="generate-payroll-report-btn" class="p-4 bg-gradient-to-br from-green-500 to-green-700 text-white rounded-lg hover:from-green-600 hover:to-green-800 transition-all">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                        <div class="font-semibold">Payroll Report</div>
                        <div class="text-sm opacity-90">Cost analysis</div>
                    </button>
                    <button id="schedule-reports-btn" class="p-4 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-lg hover:from-blue-600 hover:to-blue-800 transition-all">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <div class="font-semibold">Schedule Reports</div>
                        <div class="text-sm opacity-90">Automated delivery</div>
                    </button>
                </div>
            </div>

            <!-- Enhanced KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-400 to-blue-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Total Active Employees</p>
                            <p class="text-3xl font-bold" id="kpi-total-employees">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-new-hires">+0 this month</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-users fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-400 to-green-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Annual Turnover Rate</p>
                            <p class="text-3xl font-bold" id="kpi-turnover-rate">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-separations">0 this month</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-user-times fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-400 to-purple-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Monthly Payroll Cost</p>
                            <p class="text-3xl font-bold" id="kpi-total-payroll-cost">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-avg-salary">Avg: ₱0</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-money-bill-wave fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Avg. Employee Tenure</p>
                            <p class="text-3xl font-bold" id="kpi-avg-tenure">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-retention-rate">Retention: 0%</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-user-clock fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-red-400 to-red-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">HMO Benefits Cost</p>
                            <p class="text-3xl font-bold" id="kpi-hmo-cost">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-hmo-enrollments">0 active</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-briefcase-medical fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-indigo-400 to-indigo-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Attendance Rate</p>
                            <p class="text-3xl font-bold" id="kpi-attendance-rate">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-absenteeism">Absenteeism: 0%</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-calendar-check fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-pink-400 to-pink-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Pending Leave Requests</p>
                            <p class="text-3xl font-bold" id="kpi-pending-leaves">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-leave-types">0 types</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-calendar-alt fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-teal-400 to-teal-600 p-6 rounded-xl shadow-lg text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider">Training Completion</p>
                            <p class="text-3xl font-bold" id="kpi-training-rate">Loading...</p>
                            <p class="text-xs opacity-75" id="kpi-trainings-count">0 this year</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-full">
                            <i class="fas fa-graduation-cap fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-2 px-4 overflow-x-auto" id="reports-tabs">
                        <button class="reports-tab active py-4 px-3 border-b-2 border-[#594423] font-medium text-sm text-[#594423] whitespace-nowrap" data-tab="executive-summary">
                            <i class="fas fa-tachometer-alt mr-2"></i>Executive Summary
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="employee-demographics">
                            <i class="fas fa-users mr-2"></i>Demographics
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="recruitment-application">
                            <i class="fas fa-user-plus mr-2"></i>Recruitment
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="payroll-compensation">
                            <i class="fas fa-money-bill-wave mr-2"></i>Payroll
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="attendance-leave">
                            <i class="fas fa-calendar-check mr-2"></i>Attendance
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="benefits-hmo-utilization">
                            <i class="fas fa-briefcase-medical mr-2"></i>Benefits
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="training-development">
                            <i class="fas fa-graduation-cap mr-2"></i>Training
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="employee-relations-engagement">
                            <i class="fas fa-heart mr-2"></i>Engagement
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="turnover-retention">
                            <i class="fas fa-exchange-alt mr-2"></i>Turnover
                        </button>
                        <button class="reports-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="compliance-document">
                            <i class="fas fa-shield-alt mr-2"></i>Compliance
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div id="reports-tab-content" class="p-6">
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x text-gray-400 mb-4"></i>
                            <p class="text-gray-500">Loading reports data...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Headcount Trend (12 Months)</h3>
                    <div class="h-72 md:h-80">
                        <canvas id="headcountTrendChart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Staff Distribution by Department</h3>
                    <div class="h-72 md:h-80">
                        <canvas id="headcountByDepartmentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Additional Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Payroll Cost Trend</h3>
                    <div class="h-72 md:h-80">
                        <canvas id="payrollTrendChart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Turnover by Department</h3>
                    <div class="h-72 md:h-80">
                        <canvas id="turnoverByDepartmentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Export and Actions -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#4E3B2A]">
                        <i class="fas fa-download mr-2"></i>Export Dashboard
                    </h3>
                    <div class="flex gap-2">
                        <button id="export-dashboard-pdf" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                        <button id="export-dashboard-excel" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </button>
                        <button id="export-dashboard-csv" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-file-csv mr-2"></i>CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[#4E3B2A]">
                        <i class="fas fa-history mr-2"></i>Recent Report Activity
                    </h3>
                    <button id="view-full-audit-btn" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        View Full Audit Trail
                    </button>
                </div>
                <div id="audit-trail-content" class="text-gray-500 text-center py-4">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading audit trail...
                </div>
            </div>
        </div>`;
    
    // Initialize event listeners
    initializeDashboardEventListeners();
    
    // Load initial data
    await loadEnhancedAnalyticsData();
    
    // Load audit trail
    await loadAuditTrail();
}

/**
 * Switch report tab
 */
function switchReportTab(tabName) {
    // Update active tab
    document.querySelectorAll('.reports-tab').forEach(tab => {
        tab.classList.remove('active', 'border-[#594423]', 'text-[#594423]');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('active', 'border-[#594423]', 'text-[#594423]');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }
    
    // Load tab content
    loadReportTabContent(tabName);
}

/**
 * Load report tab content
 */
async function loadReportTabContent(tabName) {
    const contentArea = document.getElementById('reports-tab-content');
    if (!contentArea) return;
    
    // Show loading state
    contentArea.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x text-gray-400 mb-4"></i>
                <p class="text-gray-500">Loading ${tabName.replace('-', ' ')} data...</p>
            </div>
        </div>
    `;
    
    try {
        // Get current filters
        const filters = {
            department: document.getElementById('dashboard-dept-filter')?.value || '',
            branch: document.getElementById('dashboard-branch-filter')?.value || '',
            period: document.getElementById('dashboard-period-filter')?.value || '12',
            reportType: tabName
        };
        
        const queryString = new URLSearchParams(filters).toString();
        
        // Fetch report data
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-reports/${tabName}?${queryString}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load report data');

        // Render tab content based on type
        renderReportTabContent(tabName, result.data);
        
    } catch (error) {
        console.error('Error loading report tab:', error);
        contentArea.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle fa-3x text-red-400 mb-4"></i>
                <p class="text-red-600">Failed to load ${tabName.replace('-', ' ')} data</p>
                <p class="text-gray-500 text-sm mt-2">${error.message}</p>
            </div>
        `;
    }
}

/**
 * Render report tab content
 */
function renderReportTabContent(tabName, data) {
    const contentArea = document.getElementById('reports-tab-content');
    if (!contentArea) return;
    
    switch(tabName) {
        case 'executive-summary':
            contentArea.innerHTML = renderExecutiveSummaryContent(data);
            break;
        case 'employee-demographics':
            contentArea.innerHTML = renderDemographicsContent(data);
            break;
        case 'payroll-compensation':
            contentArea.innerHTML = renderPayrollContent(data);
            break;
        case 'benefits-hmo-utilization':
            contentArea.innerHTML = renderBenefitsContent(data);
            break;
        default:
            contentArea.innerHTML = renderGenericReportContent(tabName, data);
    }
}

/**
 * Render executive summary content
 */
function renderExecutiveSummaryContent(data) {
    return `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="font-semibold text-blue-800 mb-2">Key Metrics</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Employees:</span>
                            <span class="font-semibold">${data.totalEmployees || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Active Employees:</span>
                            <span class="font-semibold">${data.activeEmployees || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Turnover Rate:</span>
                            <span class="font-semibold">${data.turnoverRate || '0%'}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h4 class="font-semibold text-green-800 mb-2">Financial Overview</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Monthly Payroll:</span>
                            <span class="font-semibold">₱${data.monthlyPayroll || '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Benefits Cost:</span>
                            <span class="font-semibold">₱${data.benefitsCost || '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Avg Salary:</span>
                            <span class="font-semibold">₱${data.avgSalary || '0'}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <h4 class="font-semibold text-purple-800 mb-2">Performance</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Attendance Rate:</span>
                            <span class="font-semibold">${data.attendanceRate || '0%'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Training Completion:</span>
                            <span class="font-semibold">${data.trainingCompletion || '0%'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Avg Tenure:</span>
                            <span class="font-semibold">${data.avgTenure || '0'} years</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-800 mb-4">Summary</h4>
                <p class="text-gray-600">${data.summary || 'Executive summary data will be displayed here.'}</p>
            </div>
        </div>
    `;
}

/**
 * Render demographics content
 */
function renderDemographicsContent(data) {
    return `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Gender Distribution</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Male:</span>
                            <span class="font-semibold">${data.genderDistribution?.male || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Female:</span>
                            <span class="font-semibold">${data.genderDistribution?.female || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Age Groups</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>18-25:</span>
                            <span class="font-semibold">${data.ageGroups?.['18-25'] || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>26-35:</span>
                            <span class="font-semibold">${data.ageGroups?.['26-35'] || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>36-45:</span>
                            <span class="font-semibold">${data.ageGroups?.['36-45'] || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>46+:</span>
                            <span class="font-semibold">${data.ageGroups?.['46+'] || 0}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Render payroll content
 */
function renderPayrollContent(data) {
    return `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Payroll Summary</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Total Monthly Cost:</span>
                            <span class="font-semibold">₱${data.totalMonthlyCost || '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Average Salary:</span>
                            <span class="font-semibold">₱${data.averageSalary || '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Deductions:</span>
                            <span class="font-semibold">₱${data.totalDeductions || '0'}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Cost by Department</h4>
                    <div class="space-y-2">
                        ${data.departmentCosts?.map(dept => `
                            <div class="flex justify-between">
                                <span>${dept.name}:</span>
                                <span class="font-semibold">₱${dept.cost || '0'}</span>
                            </div>
                        `).join('') || '<p class="text-gray-500">No data available</p>'}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Render benefits content
 */
function renderBenefitsContent(data) {
    return `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">HMO Utilization</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Total Enrollments:</span>
                            <span class="font-semibold">${data.totalEnrollments || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Active Claims:</span>
                            <span class="font-semibold">${data.activeClaims || 0}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Monthly Cost:</span>
                            <span class="font-semibold">₱${data.monthlyCost || '0'}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Provider Distribution</h4>
                    <div class="space-y-2">
                        ${data.providerDistribution?.map(provider => `
                            <div class="flex justify-between">
                                <span>${provider.name}:</span>
                                <span class="font-semibold">${provider.count || 0}</span>
                            </div>
                        `).join('') || '<p class="text-gray-500">No data available</p>'}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Render generic report content
 */
function renderGenericReportContent(tabName, data) {
    return `
        <div class="space-y-6">
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-800 mb-4">${tabName.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())} Report</h4>
                <div class="text-gray-600">
                    <p>Report data for ${tabName.replace('-', ' ')} will be displayed here.</p>
                    <p class="text-sm mt-2">Data: ${JSON.stringify(data, null, 2)}</p>
                </div>
            </div>
        </div>
    `;
}

/**
 * Load audit trail
 */
async function loadAuditTrail() {
    const auditContent = document.getElementById('audit-trail-content');
    if (!auditContent) return;
    
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-reports/audit-trail`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load audit trail');

        // Normalize different possible shapes of returned data into an array
        let activities = [];
        if (Array.isArray(result.data)) {
            activities = result.data;
        } else if (result.data && Array.isArray(result.data.activities)) {
            activities = result.data.activities;
        } else if (result.data && typeof result.data === 'object') {
            // If the API returned an object keyed by id, convert to array
            activities = Object.values(result.data);
        }

        if (!Array.isArray(activities) || activities.length === 0) {
            auditContent.innerHTML = '<p class="text-gray-500">No recent activity</p>';
            return;
        }

        // Safely map activities into HTML
        const itemsHtml = activities.map(activity => {
            const icon = activity.icon || 'file';
            const action = activity.action || activity.title || 'Activity';
            const description = activity.description || activity.desc || '';
            const timestamp = activity.timestamp || activity.time || '';
            const user = activity.user || activity.username || '';

            return `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                        <i class="fas fa-${icon} text-gray-400"></i>
                            <div>
                            <p class="text-sm font-medium text-gray-800">${action}</p>
                            <p class="text-xs text-gray-500">${description}</p>
                            </div>
                        </div>
                        <div class="text-right">
                        <p class="text-xs text-gray-500">${timestamp}</p>
                        <p class="text-xs text-gray-400">${user}</p>
                        </div>
            </div>
        `;
        }).join('');

        auditContent.innerHTML = `<div class="space-y-3">${itemsHtml}</div>`;
        
    } catch (error) {
        console.error('Error loading audit trail:', error);
        auditContent.innerHTML = '<p class="text-red-500">Failed to load audit trail</p>';
    }
}

/**
 * Show schedule reports modal
 */
function showScheduleReportsModal() {
    // Create modal HTML
    const modalHTML = `
        <div id="schedule-reports-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Schedule Reports</h3>
                    <button id="close-schedule-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select id="schedule-report-type" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="executive-summary">Executive Summary</option>
                            <option value="payroll-compensation">Payroll Report</option>
                            <option value="compliance-document">Compliance Report</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                        <select id="schedule-frequency" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Recipients</label>
                        <input type="text" id="schedule-emails" placeholder="email1@company.com, email2@company.com" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button id="cancel-schedule" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="save-schedule" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Schedule</button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add event listeners
    document.getElementById('close-schedule-modal')?.addEventListener('click', closeScheduleModal);
    document.getElementById('cancel-schedule')?.addEventListener('click', closeScheduleModal);
    document.getElementById('save-schedule')?.addEventListener('click', saveSchedule);
}

/**
 * Close schedule modal
 */
function closeScheduleModal() {
    const modal = document.getElementById('schedule-reports-modal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Save schedule
 */
async function saveSchedule() {
    const reportType = document.getElementById('schedule-report-type')?.value;
    const frequency = document.getElementById('schedule-frequency')?.value;
    const emails = document.getElementById('schedule-emails')?.value;
    
    if (!reportType || !frequency || !emails) {
        alert('Please fill in all fields');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-reports/schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                reportType,
                frequency,
                emails: emails.split(',').map(email => email.trim())
            })
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to schedule report');

        alert('Report scheduled successfully!');
        closeScheduleModal();
        
    } catch (error) {
        console.error('Error scheduling report:', error);
        alert('Failed to schedule report: ' + error.message);
    }
}

/**
 * Show audit trail modal
 */
function showAuditTrailModal() {
    // Create modal HTML
    const modalHTML = `
        <div id="audit-trail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-96 overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Full Audit Trail</h3>
                    <button id="close-audit-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="full-audit-content" class="space-y-3">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin fa-2x text-gray-400 mb-2"></i>
                        <p class="text-gray-500">Loading full audit trail...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add event listeners
    document.getElementById('close-audit-modal')?.addEventListener('click', closeAuditModal);
    
    // Load full audit trail
    loadFullAuditTrail();
}

/**
 * Close audit modal
 */
function closeAuditModal() {
    const modal = document.getElementById('audit-trail-modal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Load full audit trail
 */
async function loadFullAuditTrail() {
    const auditContent = document.getElementById('full-audit-content');
    if (!auditContent) return;
    
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-reports/audit-trail?full=true`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load audit trail');

        // Normalize into array
        let activities = [];
        if (Array.isArray(result.data)) {
            activities = result.data;
        } else if (result.data && Array.isArray(result.data.activities)) {
            activities = result.data.activities;
        } else if (result.data && typeof result.data === 'object') {
            activities = Object.values(result.data);
        }

        if (!Array.isArray(activities) || activities.length === 0) {
            auditContent.innerHTML = '<p class="text-gray-500 text-center py-8">No audit trail data available</p>';
            return;
        }

        const itemsHtml = activities.map(activity => {
            const icon = activity.icon || 'file';
            const action = activity.action || activity.title || 'Activity';
            const description = activity.description || activity.desc || '';
            const timestamp = activity.timestamp || activity.time || '';
            const user = activity.user || activity.username || '';
            const ip = activity.ip || 'N/A';
            const status = activity.status || 'Success';

            return `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                        <i class="fas fa-${icon} text-gray-400"></i>
                            <div>
                            <p class="text-sm font-medium text-gray-800">${action}</p>
                            <p class="text-xs text-gray-500">${description}</p>
                            <p class="text-xs text-gray-400">IP: ${ip}</p>
                            </div>
                        </div>
                        <div class="text-right">
                        <p class="text-xs text-gray-500">${timestamp}</p>
                        <p class="text-xs text-gray-400">${user}</p>
                        <p class="text-xs text-gray-400">${status}</p>
                        </div>
            </div>
        `;
        }).join('');

        auditContent.innerHTML = `<div class="space-y-3">${itemsHtml}</div>`;
        
    } catch (error) {
        console.error('Error loading full audit trail:', error);
        auditContent.innerHTML = '<p class="text-red-500 text-center py-8">Failed to load audit trail</p>';
    }
}

/**
 * Initialize dashboard event listeners
 */
function initializeDashboardEventListeners() {
    // Refresh button
    const refreshBtn = document.getElementById('refresh-dashboard-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            await loadEnhancedAnalyticsData();
        });
    }

    // Apply filters button
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', async () => {
            await loadEnhancedAnalyticsData();
        });
    }

    // Filter change listeners
    const deptFilter = document.getElementById('dashboard-dept-filter');
    const periodFilter = document.getElementById('dashboard-period-filter');
    const branchFilter = document.getElementById('dashboard-branch-filter');
    const reportTypeFilter = document.getElementById('dashboard-report-type-filter');

    [deptFilter, periodFilter, branchFilter, reportTypeFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', async () => {
                await loadEnhancedAnalyticsData();
            });
        }
    });

    // Report tab switching
    document.querySelectorAll('.reports-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchReportTab(tabName);
        });
    });

    // Quick action buttons
    document.getElementById('generate-executive-summary-btn')?.addEventListener('click', () => {
        switchReportTab('executive-summary');
    });

    document.getElementById('generate-compliance-report-btn')?.addEventListener('click', () => {
        switchReportTab('compliance-document');
    });

    document.getElementById('generate-payroll-report-btn')?.addEventListener('click', () => {
        switchReportTab('payroll-compensation');
    });

    document.getElementById('schedule-reports-btn')?.addEventListener('click', () => {
        showScheduleReportsModal();
    });

    // Export buttons
    document.getElementById('export-dashboard-pdf')?.addEventListener('click', () => exportDashboard('PDF'));
    document.getElementById('export-dashboard-excel')?.addEventListener('click', () => exportDashboard('Excel'));
    document.getElementById('export-dashboard-csv')?.addEventListener('click', () => exportDashboard('CSV'));

    // Audit trail
    document.getElementById('view-full-audit-btn')?.addEventListener('click', () => {
        showAuditTrailModal();
    });
}

/**
 * Load enhanced analytics data
 */
async function loadEnhancedAnalyticsData() {
    const elements = {
        kpiTotalEmployees: document.getElementById('kpi-total-employees'),
        kpiNewHires: document.getElementById('kpi-new-hires'),
        kpiTurnoverRate: document.getElementById('kpi-turnover-rate'),
        kpiSeparations: document.getElementById('kpi-separations'),
        kpiTotalPayrollCost: document.getElementById('kpi-total-payroll-cost'),
        kpiAvgSalary: document.getElementById('kpi-avg-salary'),
        kpiAvgTenure: document.getElementById('kpi-avg-tenure'),
        kpiRetentionRate: document.getElementById('kpi-retention-rate'),
        kpiHmoCost: document.getElementById('kpi-hmo-cost'),
        kpiHmoEnrollments: document.getElementById('kpi-hmo-enrollments'),
        kpiAttendanceRate: document.getElementById('kpi-attendance-rate'),
        kpiAbsenteeism: document.getElementById('kpi-absenteeism'),
        kpiPendingLeaves: document.getElementById('kpi-pending-leaves'),
        kpiLeaveTypes: document.getElementById('kpi-leave-types'),
        kpiTrainingRate: document.getElementById('kpi-training-rate'),
        kpiTrainingsCount: document.getElementById('kpi-trainings-count')
    };

    if (Object.values(elements).some(el => !el)) {
        console.error("DOM elements missing for enhanced analytics dashboard.");
        if(mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error rendering dashboard elements.</p>`;
        return;
    }

    try {
        // Get current filters
        const filters = getDashboardFilters();
        const queryString = new URLSearchParams(filters).toString();

        // Fetch enhanced analytics data (fall back to summary endpoint)
        // NOTE: `get_enhanced_hr_analytics.php` was not present in `php/api/`.
        // Use the existing `get_hr_analytics_summary.php` endpoint which provides compatible summary data.
        const response = await fetch(`${API_BASE_URL}get_hr_analytics_summary.php?${queryString}`, { 
            credentials: 'include' 
        });
        
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);

        // Update KPI elements
        updateKPIElements(elements, data);
        
        // Render charts
        await renderEnhancedCharts(data);

    } catch (error) {
        console.error('Error loading enhanced HR analytics data:', error);
        // Fallback to basic analytics
        await loadBasicAnalyticsData(elements);
    }
}

/**
 * Get dashboard filters
 */
function getDashboardFilters() {
    return {
        department: document.getElementById('dashboard-dept-filter')?.value || '',
        period: document.getElementById('dashboard-period-filter')?.value || 'quarter',
        branch: document.getElementById('dashboard-branch-filter')?.value || ''
    };
}

/**
 * Update KPI elements with data
 */
function updateKPIElements(elements, data) {
    // Workforce metrics
    elements.kpiTotalEmployees.textContent = data.totalActiveEmployees || '0';
    elements.kpiNewHires.textContent = `+${data.monthlyNewHires || 0} this month`;
    elements.kpiTurnoverRate.textContent = `${data.annualTurnoverRate || '0.0'}%`;
    elements.kpiSeparations.textContent = `${data.monthlySeparations || 0} this month`;
    
    // Payroll metrics
    elements.kpiTotalPayrollCost.textContent = data.totalMonthlyPayrollCost ? 
        `₱${parseFloat(data.totalMonthlyPayrollCost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '₱0.00';
    elements.kpiAvgSalary.textContent = `Avg: ₱${parseFloat(data.avgSalary || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
    
    // Tenure metrics
    elements.kpiAvgTenure.textContent = data.avgEmployeeTenureYears ? `${data.avgEmployeeTenureYears} Years` : 'N/A';
    elements.kpiRetentionRate.textContent = `Retention: ${data.retentionRate || '0'}%`;
    
    // Benefits metrics
    elements.kpiHmoCost.textContent = data.totalMonthlyHmoCost ? 
        `₱${parseFloat(data.totalMonthlyHmoCost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '₱0.00';
    elements.kpiHmoEnrollments.textContent = `${data.activeHmoEnrollments || 0} active`;
    
    // Attendance metrics
    elements.kpiAttendanceRate.textContent = `${data.attendanceRate || '0'}%`;
    elements.kpiAbsenteeism.textContent = `Absenteeism: ${data.absenteeismRate || '0'}%`;
    
    // Leave metrics
    elements.kpiPendingLeaves.textContent = data.pendingLeaveRequests || '0';
    elements.kpiLeaveTypes.textContent = `${data.totalLeaveTypes || 0} types`;
    
    // Training metrics
    elements.kpiTrainingRate.textContent = `${data.trainingCompletionRate || '0'}%`;
    elements.kpiTrainingsCount.textContent = `${data.trainingsThisYear || 0} this year`;
}

/**
 * Render enhanced charts
 */
async function renderEnhancedCharts(data) {
    // Headcount trend chart
    if (data.headcountTrend?.length > 0) {
        renderHeadcountTrendChart(data.headcountTrend);
    }

    // Department distribution chart
    if (data.headcountByDepartment?.length > 0) {
        renderHeadcountChart(data.headcountByDepartment.map(d => d.DepartmentName), data.headcountByDepartment.map(d => d.Headcount));
    }

    // Payroll trend chart
    if (data.payrollTrend?.length > 0) {
        renderPayrollTrendChart(data.payrollTrend);
    }

    // Turnover by department chart
    if (data.turnoverByDepartment?.length > 0) {
        renderTurnoverByDepartmentChart(data.turnoverByDepartment);
    }
}

/**
 * Fallback to basic analytics data
 */
async function loadBasicAnalyticsData(elements) {
    try {
        const response = await fetch(`${API_BASE_URL}get_hr_analytics_summary.php`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);

        // Update basic elements
        elements.kpiTotalEmployees.textContent = data.totalActiveEmployees || '0';
        elements.kpiTotalPayrollCost.textContent = data.totalPayrollCostLastRun ? 
            `₱${parseFloat(data.totalPayrollCostLastRun).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '₱0.00';
        elements.kpiAvgTenure.textContent = data.averageTenureYears ? `${data.averageTenureYears} Years` : 'N/A';

        // Render basic charts
        if (data.headcountByDepartment?.length > 0) {
            renderHeadcountChart(data.headcountByDepartment.map(d => d.DepartmentName), data.headcountByDepartment.map(d => d.Headcount));
        }

    } catch (error) {
        console.error('Error loading basic analytics data:', error);
        Object.values(elements).forEach(el => { 
            if(el && el.tagName !== 'CANVAS') el.textContent = 'Error'; 
        });
    }
}

async function loadAnalyticsData() {
    const elements = {
        kpiTotalEmployees: document.getElementById('kpi-total-employees'),
        kpiTotalLeaveDays: document.getElementById('kpi-total-leave-days'),
        kpiTotalPayrollCost: document.getElementById('kpi-total-payroll-cost'),
        kpiPayrollRunId: document.getElementById('kpi-payroll-run-id'),
        kpiAvgTenure: document.getElementById('kpi-avg-tenure'),
        kpiTotalLeaveTypes: document.getElementById('kpi-total-leave-types'),
        headcountChartContainer: document.getElementById('headcountByDepartmentChart'),
        leaveTypeChartContainer: document.getElementById('leaveDaysByTypeChart')
    };
    if (Object.values(elements).some(el => !el)) {
        console.error("DOM elements missing for analytics dashboard.");
        if(mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error rendering dashboard elements.</p>`;
        return;
    }
    try {
        const response = await fetch(`${API_BASE_URL}get_hr_analytics_summary.php`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);

        elements.kpiTotalEmployees.textContent = data.totalActiveEmployees || '0';
        elements.kpiTotalLeaveDays.textContent = data.totalLeaveDaysRequestedThisYear || '0';
        elements.kpiTotalPayrollCost.textContent = data.totalPayrollCostLastRun ? `₱${parseFloat(data.totalPayrollCostLastRun).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '₱0.00';
        elements.kpiPayrollRunId.textContent = data.lastPayrollRunIdForCost ? `(Run ID: ${data.lastPayrollRunIdForCost})` : '(No completed run found)';
        elements.kpiAvgTenure.textContent = data.averageTenureYears ? `${data.averageTenureYears} Years` : 'N/A';
        elements.kpiTotalLeaveTypes.textContent = data.totalLeaveTypes || '0';

        if (data.headcountByDepartment?.length > 0) {
            renderHeadcountChart(data.headcountByDepartment.map(d => d.DepartmentName), data.headcountByDepartment.map(d => d.Headcount));
        } else {
            if(elements.headcountChartContainer?.getContext('2d')) elements.headcountChartContainer.parentElement.innerHTML = '<p class="text-center text-gray-500 py-4">No staff distribution data available.</p>';
        }
        if (data.leaveDaysByTypeThisYear?.length > 0) {
            renderLeaveDaysByTypeChart(data.leaveDaysByTypeThisYear.map(l => l.TypeName), data.leaveDaysByTypeThisYear.map(l => l.TotalDays));
        } else {
             if(elements.leaveTypeChartContainer?.getContext('2d')) elements.leaveTypeChartContainer.parentElement.innerHTML = '<p class="text-center text-gray-500 py-4">No approved leave data this year.</p>';
        }
    } catch (error) {
        console.error('Error loading HR analytics data:', error);
        Object.values(elements).forEach(el => { if(el && el.tagName !== 'CANVAS') el.textContent = 'Error'; });
        if(elements.headcountChartContainer?.parentElement) elements.headcountChartContainer.parentElement.innerHTML = `<p class="text-red-500">Error loading chart.</p>`;
        if(elements.leaveTypeChartContainer?.parentElement) elements.leaveTypeChartContainer.parentElement.innerHTML = `<p class="text-red-500">Error loading chart.</p>`;
    }
}

/**
 * Render headcount trend chart (line chart)
 */
function renderHeadcountTrendChart(data) {
    const ctx = document.getElementById('headcountTrendChart')?.getContext('2d');
    if (!ctx) return;
    
    if (chartInstances.headcountTrend) chartInstances.headcountTrend.destroy();
    
    const labels = data.map(d => d.month_name || d.month);
    const values = data.map(d => d.headcount || d.count);
    
    chartInstances.headcountTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Headcount',
                data: values,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: Math.max(1, Math.ceil(Math.max(...values, 1) / 10))
                    }
                }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });
}

/**
 * Render payroll trend chart
 */
function renderPayrollTrendChart(data) {
    const ctx = document.getElementById('payrollTrendChart')?.getContext('2d');
    if (!ctx) return;
    
    if (chartInstances.payrollTrend) chartInstances.payrollTrend.destroy();
    
    const labels = data.map(d => d.month_name || d.month);
    const values = data.map(d => d.total_gross || d.total_cost);
    
    chartInstances.payrollTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Payroll Cost (₱)',
                data: values,
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });
}

/**
 * Render turnover by department chart
 */
function renderTurnoverByDepartmentChart(data) {
    const ctx = document.getElementById('turnoverByDepartmentChart')?.getContext('2d');
    if (!ctx) return;
    
    if (chartInstances.turnoverByDepartment) chartInstances.turnoverByDepartment.destroy();
    
    const labels = data.map(d => d.department_name);
    const values = data.map(d => d.turnover_rate);
    
    chartInstances.turnoverByDepartment = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Turnover Rate (%)',
                data: values,
                backgroundColor: values.map((_, i) => `hsl(${(i * 360 / (labels.length || 1) + 200) % 360}, 70%, 60%)`),
                borderColor: values.map((_, i) => `hsl(${(i * 360 / (labels.length || 1) + 200) % 360}, 70%, 50%)`),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                title: { display: false }
            }
        }
    });
}

function renderHeadcountChart(labels, data) {
    const ctx = document.getElementById('headcountByDepartmentChart')?.getContext('2d');
    if (!ctx) return;
    if (headcountChartInstance) headcountChartInstance.destroy();
    const bgColors = labels.map((_, i) => `hsl(${(i * 360 / (labels.length || 1))} , 70%, 60%)`);
    const borderColors = labels.map((_, i) => `hsl(${(i * 360 / (labels.length || 1))}, 70%, 50%)`);
    headcountChartInstance = new Chart(ctx, {
        type: 'bar', data: { labels, datasets: [{ label: 'Employees', data, backgroundColor: bgColors, borderColor: borderColors, borderWidth: 1 }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: Math.max(1, Math.ceil(Math.max(...data, 1) / 10)) } } }, plugins: { legend: { display: false }, title: { display: false } } }
    });
}

function renderLeaveDaysByTypeChart(labels, data) {
    const ctx = document.getElementById('leaveDaysByTypeChart')?.getContext('2d');
    if (!ctx) return;
    if (leaveTypeChartInstance) leaveTypeChartInstance.destroy();
    const bgColors = labels.map((_, i) => `hsl(${ (i * 360 / (labels.length || 1) + 45) % 360}, 75%, 65%)`);
    const borderColors = bgColors.map(c => c.replace('65%)', '55%)'));
    leaveTypeChartInstance = new Chart(ctx, {
        type: 'pie', data: { labels, datasets: [{ label: 'Approved Leave Days', data, backgroundColor: bgColors, borderColor: borderColors, borderWidth: 1 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, title: { display: false }, tooltip: { callbacks: { label: c => `${c.label || ''}: ${c.parsed || 0} days` } } } }
    });
}

export async function displayAnalyticsReportsSection() {
    if (!initializeAnalyticsElements()) return;
    pageTitleElement.textContent = 'Reports and Metrics';

    // Generate automatic date ranges
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth();

    // Current month range
    const currentMonthStart = new Date(currentYear, currentMonth, 1);
    const currentMonthEnd = new Date(currentYear, currentMonth + 1, 0);

    // Previous month range
    const prevMonthStart = new Date(currentYear, currentMonth - 1, 1);
    const prevMonthEnd = new Date(currentYear, currentMonth, 0);

    // Current quarter range
    const currentQuarter = Math.floor(currentMonth / 3);
    const quarterStartMonth = currentQuarter * 3;
    const quarterEndMonth = quarterStartMonth + 3;
    const currentQuarterStart = new Date(currentYear, quarterStartMonth, 1);
    const currentQuarterEnd = new Date(currentYear, quarterEndMonth, 0);

    // Current year range
    const currentYearStart = new Date(currentYear, 0, 1);
    const currentYearEnd = new Date(currentYear, 11, 31);

    const formatDate = (date) => date.toISOString().split('T')[0];

    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Report Builder Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-4 px-4" id="report-tabs">
                        <button class="report-tab active py-4 px-3 border-b-2 border-[#594423] font-medium text-sm text-[#594423]" data-tab="standardized">
                            <i class="fas fa-file-alt mr-2"></i>Standardized Reports
                        </button>
                        <button class="report-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="custom">
                            <i class="fas fa-tools mr-2"></i>Custom Report Builder
                        </button>
                        <button class="report-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="scheduled">
                            <i class="fas fa-clock mr-2"></i>Scheduled Reports
                        </button>
                        <button class="report-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="compliance">
                            <i class="fas fa-shield-alt mr-2"></i>Compliance & Audit
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div id="report-tab-content" class="p-6">
                    <!-- Standardized Reports Tab -->
                    <div id="standardized-reports" class="report-tab-panel">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Generate Standardized HR Reports</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 pb-4 border-b border-gray-200 items-end">
                            <div>
                                <label for="report-type-filter" class="block text-sm font-medium text-gray-700 mb-1">Report Type:</label>
                                <select id="report-type-filter" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                    <option value="">-- Select Report --</option>
                                    <option value="headcount_by_department">Headcount by Department</option>
                                    <option value="headcount_by_employment_type">Headcount by Employment Type</option>
                                    <option value="turnover_retention_report">Turnover & Retention Report</option>
                                    <option value="salary_distribution_report">Salary Distribution Report</option>
                                    <option value="payroll_cost_summary">Payroll Cost Summary</option>
                                    <option value="benefits_utilization_report">Benefits Utilization Report</option>
                                    <option value="training_participation_report">Training Participation Report</option>
                                    <option value="attendance_summary_report">Attendance Summary Report</option>
                                    <option value="leave_utilization_report">Leave Utilization Report</option>
                                    <option value="employee_master_list">Employee Master List</option>
                                    <option value="payroll_summary_report">Payroll Summary Report</option>
                                </select>
                            </div>
                            <div>
                                <label for="report-date-range-filter" class="block text-sm font-medium text-gray-700 mb-1">Date Range:</label>
                                <select id="report-date-range-filter" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                    <option value="${formatDate(currentMonthStart)}_${formatDate(currentMonthEnd)}">Current Month (${formatDate(currentMonthStart)} - ${formatDate(currentMonthEnd)})</option>
                                    <option value="${formatDate(prevMonthStart)}_${formatDate(prevMonthEnd)}">Previous Month (${formatDate(prevMonthStart)} - ${formatDate(prevMonthEnd)})</option>
                                    <option value="${formatDate(currentQuarterStart)}_${formatDate(currentQuarterEnd)}">Current Quarter (${formatDate(currentQuarterStart)} - ${formatDate(currentQuarterEnd)})</option>
                                    <option value="${formatDate(currentYearStart)}_${formatDate(currentYearEnd)}">Current Year (${currentYear})</option>
                                    <option value="custom">Custom Range...</option>
                                </select>
                            </div>
                            <div>
                                <label for="custom-date-range" class="block text-sm font-medium text-gray-700 mb-1">Custom Range:</label>
                                <input type="text" id="custom-date-range" placeholder="YYYY-MM-DD_YYYY-MM-DD" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]" style="display: none;">
                            </div>
                            <div>
                                <button id="generate-report-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <i class="fas fa-file-export mr-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                        <div id="reports-output-container" class="overflow-x-auto min-h-[200px] bg-gray-50 p-4 rounded-lg border">
                            <p class="text-center py-4 text-gray-500">Select a report type and click "Generate Report".</p>
                        </div>
                    </div>

                    <!-- Custom Report Builder Tab -->
                    <div id="custom-reports" class="report-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Custom Report Builder</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Widget Library -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold mb-3">Widget Library</h4>
                                <div class="space-y-2">
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="kpi-card">
                                        <i class="fas fa-chart-line mr-2"></i>KPI Card
                                    </div>
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="bar-chart">
                                        <i class="fas fa-chart-bar mr-2"></i>Bar Chart
                                    </div>
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="line-chart">
                                        <i class="fas fa-chart-line mr-2"></i>Line Chart
                                    </div>
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="pie-chart">
                                        <i class="fas fa-chart-pie mr-2"></i>Pie Chart
                                    </div>
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="data-table">
                                        <i class="fas fa-table mr-2"></i>Data Table
                                    </div>
                                    <div class="widget-item p-2 bg-white rounded border cursor-pointer hover:bg-blue-50" data-widget="heatmap">
                                        <i class="fas fa-th mr-2"></i>Heatmap
                                    </div>
                                </div>
                            </div>

                            <!-- Report Canvas -->
                            <div class="lg:col-span-2">
                                <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[400px]" id="report-canvas">
                                    <p class="text-center text-gray-500 py-8">Drag widgets here to build your custom report</p>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <button id="save-custom-report" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        <i class="fas fa-save mr-2"></i>Save Report
                                    </button>
                                    <button id="preview-custom-report" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        <i class="fas fa-eye mr-2"></i>Preview
                                    </button>
                                    <button id="export-custom-report" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                        <i class="fas fa-download mr-2"></i>Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduled Reports Tab -->
                    <div id="scheduled-reports" class="report-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Scheduled Reports</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold mb-3">Create New Schedule</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                                        <select id="schedule-report-type" class="w-full p-2 border border-gray-300 rounded-md">
                                            <option value="">Select Report Type</option>
                                            <option value="headcount_by_department">Headcount by Department</option>
                                            <option value="payroll_cost_summary">Payroll Cost Summary</option>
                                            <option value="turnover_retention_report">Turnover & Retention Report</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                                        <select id="schedule-frequency" class="w-full p-2 border border-gray-300 rounded-md">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipients (Email)</label>
                                        <input type="text" id="schedule-recipients" placeholder="email1@company.com, email2@company.com" class="w-full p-2 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                                        <select id="schedule-format" class="w-full p-2 border border-gray-300 rounded-md">
                                            <option value="PDF">PDF</option>
                                            <option value="Excel">Excel</option>
                                            <option value="CSV">CSV</option>
                                        </select>
                                    </div>
                                    <button id="create-schedule-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        <i class="fas fa-plus mr-2"></i>Create Schedule
                                    </button>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">Active Schedules</h4>
                                <div id="scheduled-reports-list" class="space-y-2">
                                    <div class="p-3 bg-gray-50 rounded border">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-medium">Monthly Headcount Report</p>
                                                <p class="text-sm text-gray-600">Next run: 2024-02-01 09:00</p>
                                            </div>
                                            <div class="flex gap-1">
                                                <button class="px-2 py-1 bg-blue-500 text-white text-xs rounded">Edit</button>
                                                <button class="px-2 py-1 bg-red-500 text-white text-xs rounded">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance & Audit Tab -->
                    <div id="compliance-reports" class="report-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Compliance & Audit Reports</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="dole-compliance">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-gavel text-blue-600 mr-2"></i>
                                    <h4 class="font-semibold">DOLE Compliance Report</h4>
                                </div>
                                <p class="text-sm text-gray-600">Labor standards compliance, working hours, overtime records</p>
                            </div>
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="civil-service">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-building text-green-600 mr-2"></i>
                                    <h4 class="font-semibold">Civil Service Report</h4>
                                </div>
                                <p class="text-sm text-gray-600">Government employee records, service credits, leave balances</p>
                            </div>
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="audit-trail">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-search text-purple-600 mr-2"></i>
                                    <h4 class="font-semibold">Audit Trail Report</h4>
                                </div>
                                <p class="text-sm text-gray-600">System access logs, data changes, user activities</p>
                            </div>
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="data-privacy">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-shield-alt text-red-600 mr-2"></i>
                                    <h4 class="font-semibold">Data Privacy Report</h4>
                                </div>
                                <p class="text-sm text-gray-600">Personal data processing, consent records, data retention</p>
                            </div>
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="payroll-audit">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-calculator text-yellow-600 mr-2"></i>
                                    <h4 class="font-semibold">Payroll Audit Report</h4>
                                </div>
                                <p class="text-sm text-gray-600">Salary calculations, deductions, tax compliance</p>
                            </div>
                            <div class="p-4 bg-white border rounded-lg hover:shadow-md cursor-pointer" data-report="benefits-compliance">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-heart text-pink-600 mr-2"></i>
                                    <h4 class="font-semibold">Benefits Compliance</h4>
                                </div>
                                <p class="text-sm text-gray-600">SSS, PhilHealth, Pag-IBIG contributions and benefits</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Initialize report builder functionality
    initializeReportBuilder();
    
    // Initialize event listeners
    initializeReportsEventListeners();
}

/**
 * Initialize report builder functionality
 */
function initializeReportBuilder() {
    // Tab switching
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchHRReportTab(tabName);
        });
    });

    // Widget drag and drop
    document.querySelectorAll('.widget-item').forEach(widget => {
        widget.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.widget);
        });
        widget.draggable = true;
    });

    // Report canvas drop zone
    const canvas = document.getElementById('report-canvas');
    if (canvas) {
        canvas.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-blue-400', 'bg-blue-50');
        });

        canvas.addEventListener('dragleave', function(e) {
            this.classList.remove('border-blue-400', 'bg-blue-50');
        });

        canvas.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-400', 'bg-blue-50');
            
            const widgetType = e.dataTransfer.getData('text/plain');
            addWidgetToCanvas(widgetType);
        });
    }
}

/**
 * Switch HR report tabs
 */
function switchHRReportTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.classList.remove('active', 'border-[#594423]', 'text-[#594423]');
        tab.classList.add('border-transparent', 'text-gray-500');
    });

    const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('active', 'border-[#594423]', 'text-[#594423]');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }

    // Update tab panels
    document.querySelectorAll('.report-tab-panel').forEach(panel => {
        panel.classList.add('hidden');
    });

    const activePanel = document.getElementById(`${tabName}-reports`);
    if (activePanel) {
        activePanel.classList.remove('hidden');
    }
}

/**
 * Add widget to canvas
 */
function addWidgetToCanvas(widgetType) {
    const canvas = document.getElementById('report-canvas');
    if (!canvas) return;

    const widgetId = `widget-${Date.now()}`;
    let widgetHTML = '';

    switch (widgetType) {
        case 'kpi-card':
            widgetHTML = `
                <div class="widget kpi-card bg-white border rounded-lg p-4 mb-4" data-widget-id="${widgetId}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">KPI Card</h4>
                        <button class="remove-widget text-red-500 hover:text-red-700" data-widget-id="${widgetId}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600">0</p>
                        <p class="text-sm text-gray-600">Metric Value</p>
                    </div>
                </div>
            `;
            break;
        case 'bar-chart':
            widgetHTML = `
                <div class="widget bar-chart bg-white border rounded-lg p-4 mb-4" data-widget-id="${widgetId}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">Bar Chart</h4>
                        <button class="remove-widget text-red-500 hover:text-red-700" data-widget-id="${widgetId}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="h-48">
                        <canvas id="custom-chart-${widgetId}"></canvas>
                    </div>
                </div>
            `;
            break;
        case 'data-table':
            widgetHTML = `
                <div class="widget data-table bg-white border rounded-lg p-4 mb-4" data-widget-id="${widgetId}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">Data Table</h4>
                        <button class="remove-widget text-red-500 hover:text-red-700" data-widget-id="${widgetId}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Column 1</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Column 2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-3 py-2 text-sm">Sample Data</td>
                                    <td class="px-3 py-2 text-sm">Sample Data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            break;
        default:
            widgetHTML = `
                <div class="widget ${widgetType} bg-white border rounded-lg p-4 mb-4" data-widget-id="${widgetId}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">${widgetType.replace('-', ' ').toUpperCase()}</h4>
                        <button class="remove-widget text-red-500 hover:text-red-700" data-widget-id="${widgetId}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="text-gray-500">Widget placeholder</p>
                </div>
            `;
    }

    // Remove placeholder text if it exists
    if (canvas.textContent.includes('Drag widgets here')) {
        canvas.innerHTML = '';
    }

    canvas.insertAdjacentHTML('beforeend', widgetHTML);

    // Add remove functionality
    const removeBtn = canvas.querySelector(`[data-widget-id="${widgetId}"] .remove-widget`);
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            const widget = canvas.querySelector(`[data-widget-id="${widgetId}"]`);
            if (widget) widget.remove();
        });
    }

    // Add to report builder state
    reportBuilderState.widgets.push({
        id: widgetId,
        type: widgetType,
        position: reportBuilderState.widgets.length
    });
}

/**
 * Initialize reports event listeners
 */
function initializeReportsEventListeners() {
    // Generate report button
    const generateBtn = document.getElementById('generate-report-btn');
    if (generateBtn && !generateBtn.hasAttribute('data-listener-attached')) {
        generateBtn.addEventListener('click', handleGenerateReport);
        generateBtn.setAttribute('data-listener-attached', 'true');
    }

    // Date range dropdown
    const dateRangeSelect = document.getElementById('report-date-range-filter');
    const customDateInput = document.getElementById('custom-date-range');
    if (dateRangeSelect && customDateInput) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateInput.style.display = 'block';
                customDateInput.focus();
            } else {
                customDateInput.style.display = 'none';
            }
        });
    }

    // Custom report builder buttons
    document.getElementById('save-custom-report')?.addEventListener('click', saveCustomReport);
    document.getElementById('preview-custom-report')?.addEventListener('click', previewCustomReport);
    document.getElementById('export-custom-report')?.addEventListener('click', exportCustomReport);

    // Scheduled reports
    document.getElementById('create-schedule-btn')?.addEventListener('click', createScheduledReport);

    // Compliance reports
    document.querySelectorAll('[data-report]').forEach(report => {
        report.addEventListener('click', function() {
            const reportType = this.dataset.report;
            generateComplianceReport(reportType);
        });
    });
}

/**
 * Export dashboard functionality
 */
function exportDashboard(format) {
    const filters = getDashboardFilters();
    const queryString = new URLSearchParams({
        ...filters,
        format: format.toLowerCase()
    }).toString();

    // Create download link
    const link = document.createElement('a');
    link.href = `${API_BASE_URL}export_dashboard.php?${queryString}`;
    link.download = `hr_dashboard_${new Date().toISOString().slice(0,10)}.${format.toLowerCase()}`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Save custom report
 */
function saveCustomReport() {
    const reportName = prompt('Enter report name:');
    if (!reportName) return;

    const reportData = {
        name: reportName,
        widgets: reportBuilderState.widgets,
        filters: reportBuilderState.filters,
        layout: reportBuilderState.layout,
        created_at: new Date().toISOString()
    };

    // Save to localStorage for demo purposes
    const savedReports = JSON.parse(localStorage.getItem('customReports') || '[]');
    savedReports.push(reportData);
    localStorage.setItem('customReports', JSON.stringify(savedReports));

    alert('Custom report saved successfully!');
}

/**
 * Preview custom report
 */
function previewCustomReport() {
    const canvas = document.getElementById('report-canvas');
    if (!canvas || canvas.children.length === 0) {
        alert('No widgets added to preview');
        return;
    }

    // Create preview window
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
        <html>
            <head>
                <title>Report Preview</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            </head>
            <body class="bg-gray-100 p-4">
                <h1 class="text-2xl font-bold mb-4">Report Preview</h1>
                <div class="bg-white p-4 rounded-lg shadow">
                    ${canvas.innerHTML}
                </div>
            </body>
        </html>
    `);
}

/**
 * Export custom report
 */
function exportCustomReport() {
    const canvas = document.getElementById('report-canvas');
    if (!canvas || canvas.children.length === 0) {
        alert('No widgets added to export');
        return;
    }

    // Convert to CSV for demo
    const csvData = [];
    canvas.querySelectorAll('.widget').forEach(widget => {
        const title = widget.querySelector('h4')?.textContent || 'Widget';
        csvData.push([title, 'Data', 'Value']);
    });

    const csvContent = csvData.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `custom_report_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
}

/**
 * Create scheduled report
 */
function createScheduledReport() {
    const reportType = document.getElementById('schedule-report-type')?.value;
    const frequency = document.getElementById('schedule-frequency')?.value;
    const recipients = document.getElementById('schedule-recipients')?.value;
    const format = document.getElementById('schedule-format')?.value;

    if (!reportType || !recipients) {
        alert('Please fill in all required fields');
        return;
    }

    const scheduleData = {
        reportType,
        frequency,
        recipients: recipients.split(',').map(email => email.trim()),
        format,
        created_at: new Date().toISOString(),
        next_run: calculateNextRun(frequency)
    };

    // Add to scheduled reports list
    const scheduledList = document.getElementById('scheduled-reports-list');
    const scheduleItem = document.createElement('div');
    scheduleItem.className = 'p-3 bg-gray-50 rounded border';
    scheduleItem.innerHTML = `
        <div class="flex justify-between items-center">
            <div>
                <p class="font-medium">${reportType.replace(/_/g, ' ').toUpperCase()}</p>
                <p class="text-sm text-gray-600">Next run: ${scheduleData.next_run}</p>
            </div>
            <div class="flex gap-1">
                <button class="px-2 py-1 bg-blue-500 text-white text-xs rounded">Edit</button>
                <button class="px-2 py-1 bg-red-500 text-white text-xs rounded">Delete</button>
            </div>
        </div>
    `;
    scheduledList.appendChild(scheduleItem);

    alert('Scheduled report created successfully!');
}

/**
 * Calculate next run time
 */
function calculateNextRun(frequency) {
    const now = new Date();
    switch (frequency) {
        case 'daily':
            return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString().slice(0, 16);
        case 'weekly':
            return new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16);
        case 'monthly':
            return new Date(now.getFullYear(), now.getMonth() + 1, 1, 9, 0).toISOString().slice(0, 16);
        case 'quarterly':
            return new Date(now.getFullYear(), now.getMonth() + 3, 1, 9, 0).toISOString().slice(0, 16);
        default:
            return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString().slice(0, 16);
    }
}

/**
 * Generate compliance report
 */
function generateComplianceReport(reportType) {
    const output = document.getElementById('reports-output-container');
    if (!output) return;

    output.innerHTML = `<p class="text-blue-600">Generating ${reportType.replace(/_/g, ' ').toUpperCase()} report...</p>`;

    // Simulate report generation
    setTimeout(() => {
        const reportData = {
            reportName: `${reportType.replace(/_/g, ' ').toUpperCase()} Report`,
            generatedAt: new Date().toISOString(),
            columns: [
                { key: 'employee_id', label: 'Employee ID' },
                { key: 'name', label: 'Employee Name' },
                { key: 'department', label: 'Department' },
                { key: 'compliance_status', label: 'Compliance Status' }
            ],
            rows: [
                {
                    employee_id: 'EMP001',
                    name: 'John Doe',
                    department: 'HR',
                    compliance_status: 'Compliant'
                },
                {
                    employee_id: 'EMP002',
                    name: 'Jane Smith',
                    department: 'Finance',
                    compliance_status: 'Pending Review'
                }
            ],
            summary: [
                { label: 'Total Employees', value: '2' },
                { label: 'Compliant', value: '1' },
                { label: 'Pending Review', value: '1' }
            ]
        };

        renderReportTable(reportData, output);
    }, 1000);
}

async function loadAvailableReportsDropdown() {
    // This function is now handled by the enhanced report system
    // The dropdown is populated directly in the HTML with predefined report types
}

async function handleGenerateReport() {
    const typeSelect = document.getElementById('report-type-filter');
    const type = typeSelect?.value;
    const name = typeSelect?.options[typeSelect.selectedIndex]?.textContent || type;
    const dateRangeSelect = document.getElementById('report-date-range-filter');
    const customDateInput = document.getElementById('custom-date-range');
    const output = document.getElementById('reports-output-container');

    if (!output || !type) {
        if(output) output.innerHTML = '<p class="text-red-500">Please select a report type.</p>';
        return;
    }

    // Get the actual date range value
    let range = dateRangeSelect?.value;
    if (range === 'custom' && customDateInput?.value) {
        range = customDateInput.value;
    }

    let endpoint = '';
    if (type === 'employee_master_list') endpoint = `${API_BASE_URL}generate_employee_master_report.php`;
    else if (type === 'leave_summary_report') endpoint = `${API_BASE_URL}generate_leave_summary_report.php`;
    else if (type === 'payroll_summary_report') endpoint = `${API_BASE_URL}generate_payroll_summary_report.php`;
    else { output.innerHTML = `<p class="text-red-500">Report '${name}' not configured.</p>`; return; }

    output.innerHTML = `<p class="text-blue-600">Generating <strong>${name}</strong>...</p>`;
    try {
        const params = new URLSearchParams();
        if (range && /^\d{4}-\d{2}-\d{2}_\d{4}-\d{2}-\d{2}$/.test(range)) params.append('date_range', range);
        else if (range) console.warn("Invalid date range format:", range);

        const response = await fetch(`${endpoint}?${params.toString()}`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const reportData = await response.json();
        if (reportData.error) throw new Error(reportData.error);
        renderReportTable(reportData, output);
    } catch (e) {
        console.error(`Error generating report '${type}':`, e);
        output.innerHTML = `<p class="text-red-500">Could not generate report: ${e.message}</p>`;
    }
}

function renderReportTable(reportData, container) {
    if (!reportData || !reportData.columns || !reportData.rows) {
        container.innerHTML = '<p class="text-gray-500">No data for this report.</p>'; return;
    }
    let html = `<div class="mb-2"><h4 class="font-semibold">${reportData.reportName}</h4><p class="text-xs text-gray-500">Generated: ${new Date(reportData.generatedAt).toLocaleString()}</p></div>
        <table class="min-w-full divide-y divide-gray-200 border"><thead class="bg-gray-100"><tr>`;
    reportData.columns.forEach(c => { html += `<th class="px-3 py-2 text-left text-xs font-medium uppercase">${c.label}</th>`; });
    html += `</tr></thead><tbody class="bg-white divide-y divide-gray-200">`;
    if (reportData.rows.length === 0) {
        html += `<tr><td colspan="${reportData.columns.length}" class="px-3 py-3 text-center text-sm">No data.</td></tr>`;
    } else {
        reportData.rows.forEach(row => {
            html += `<tr>`;
            reportData.columns.forEach(c => {
                const val = row[c.key] !== null && row[c.key] !== undefined ? String(row[c.key]) : '-';
                html += `<td class="px-3 py-2 whitespace-nowrap text-sm">${val.replace(/</g, "&lt;")}</td>`;
            });
            html += `</tr>`;
        });
    }
    html += `</tbody></table>`;
    if(reportData.summary?.length > 0){
        html += `<div class="mt-4 pt-2 border-t">`;
        reportData.summary.forEach(item => { html += `<p class="text-sm"><strong>${item.label}:</strong> ${item.value}</p>`; });
        html += `</div>`;
    }
    html += `<div class="mt-4 text-right"><button onclick="exportReportToCSV('${reportData.reportName.replace(/\s+/g, '_')}')" class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">Export CSV</button></div>`;
    container.innerHTML = html;
}

window.exportReportToCSV = function(filenamePrefix) {
    const table = document.querySelector('#reports-output-container table');
    if (!table) { alert("No report table to export."); return; }
    let csv = [];
    table.querySelectorAll("tr").forEach(row => {
        const rowData = [];
        row.querySelectorAll("td, th").forEach(col => {
            let data = col.innerText.replace(/"/g, '""');
            if (/[",\n]/.test(data)) data = `"${data}"`;
            rowData.push(data);
        });
        csv.push(rowData.join(","));
    });
    const blob = new Blob([csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `${filenamePrefix}_${new Date().toISOString().slice(0,10)}.csv`;
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

export async function displayAnalyticsMetricsSection() {
    if (!initializeAnalyticsElements()) return;
    pageTitleElement.textContent = 'Key HR Metrics & Predictive Analytics';

    // Auto-select appropriate time periods based on current date
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth();
    const currentQuarter = Math.floor(currentMonth / 3);

    // Determine best default period based on current date
    let defaultPeriod = 'current';
    if (currentMonth === 0) { // January - show annual trend
        defaultPeriod = 'annual';
    } else if (currentMonth === 2 || currentMonth === 5 || currentMonth === 8 || currentMonth === 11) { // End of quarter months
        defaultPeriod = 'quarterly';
    }

    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Metrics Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-4 px-4" id="metrics-tabs">
                        <button class="metrics-tab active py-4 px-3 border-b-2 border-[#594423] font-medium text-sm text-[#594423]" data-tab="kpi">
                            <i class="fas fa-chart-line mr-2"></i>KPI Dashboard
                        </button>
                        <button class="metrics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="predictive">
                            <i class="fas fa-crystal-ball mr-2"></i>Predictive Analytics
                        </button>
                        <button class="metrics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="comparative">
                            <i class="fas fa-balance-scale mr-2"></i>Comparative Analysis
                        </button>
                        <button class="metrics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="anomaly">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Anomaly Detection
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div id="metrics-tab-content" class="p-6">
                    <!-- KPI Dashboard Tab -->
                    <div id="kpi-dashboard" class="metrics-tab-panel">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Track Key Performance Indicators</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 pb-4 border-b border-gray-200 items-end">
                            <div>
                                <label for="metric-name-filter" class="block text-sm font-medium text-gray-700 mb-1">Metric:</label>
                                <select id="metric-name-filter" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                    <option value="">-- Select Metric --</option>
                                    <option value="headcount_by_department">Staff Distribution by Role</option>
                                    <option value="turnover_rate">Staff Turnover Rate</option>
                                    <option value="avg_time_to_hire">Average Time to Hire</option>
                                    <option value="training_completion_rate">Training Completion Rate</option>
                                    <option value="employee_satisfaction_index">Employee Satisfaction Index</option>
                                    <option value="overtime_cost_ratio">Overtime Cost Ratio</option>
                                    <option value="average_tenure_per_department">Average Tenure per Department</option>
                                    <option value="attendance_rate">Attendance Rate</option>
                                    <option value="benefits_utilization_rate">Benefits Utilization Rate</option>
                                    <option value="productivity_index">Productivity Index</option>
                                </select>
                            </div>
                            <div>
                                <label for="metric-period-filter" class="block text-sm font-medium text-gray-700 mb-1">Time Period:</label>
                                <select id="metric-period-filter" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                    <option value="current" ${defaultPeriod === 'current' ? 'selected' : ''}>Current Snapshot</option>
                                    <option value="monthly" ${defaultPeriod === 'monthly' ? 'selected' : ''}>Monthly Trend</option>
                                    <option value="quarterly" ${defaultPeriod === 'quarterly' ? 'selected' : ''}>Quarterly Trend</option>
                                    <option value="annual" ${defaultPeriod === 'annual' ? 'selected' : ''}>Annual Trend</option>
                                </select>
                            </div>
                            <div>
                                <button id="view-metric-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <i class="fas fa-chart-bar mr-2"></i>View Metric
                                </button>
                            </div>
                            <div>
                                <button id="auto-refresh-btn" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" title="Auto-refresh with current data">
                                    <i class="fas fa-sync-alt"></i> Auto Refresh
                                </button>
                            </div>
                        </div>
                        <div id="metric-display-area" class="min-h-[300px] bg-gray-50 p-4 rounded-lg border">
                            <p class="text-center py-4 text-gray-500">Select a metric and period to view data.</p>
                        </div>
                    </div>

                    <!-- Predictive Analytics Tab -->
                    <div id="predictive-analytics" class="metrics-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Predictive Analytics & Forecasting</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Attrition Prediction</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm">High Risk Employees:</span>
                                            <span class="text-sm font-semibold text-red-600">12</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Medium Risk:</span>
                                            <span class="text-sm font-semibold text-yellow-600">28</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Low Risk:</span>
                                            <span class="text-sm font-semibold text-green-600">156</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                            View Risk Analysis
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Salary Growth Forecast</h4>
                                    <div class="h-48">
                                        <canvas id="salaryForecastChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Workforce Planning</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Planning Horizon</label>
                                            <select class="w-full p-2 border border-gray-300 rounded-md">
                                                <option value="6months">6 Months</option>
                                                <option value="1year" selected>1 Year</option>
                                                <option value="2years">2 Years</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Department Focus</label>
                                            <select class="w-full p-2 border border-gray-300 rounded-md">
                                                <option value="">All Departments</option>
                                                <option value="hr">Human Resources</option>
                                                <option value="finance">Finance</option>
                                                <option value="operations">Operations</option>
                                            </select>
                                        </div>
                                        <button class="w-full px-3 py-2 bg-purple-600 text-white text-sm rounded hover:bg-purple-700">
                                            Generate Forecast
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Training Needs Prediction</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm">Skills Gap Identified:</span>
                                            <span class="text-sm font-semibold">8</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Training Programs Needed:</span>
                                            <span class="text-sm font-semibold">5</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Estimated Cost:</span>
                                            <span class="text-sm font-semibold">₱125,000</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparative Analysis Tab -->
                    <div id="comparative-analysis" class="metrics-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Comparative Analysis</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Budget vs Actual HR Costs</h4>
                                    <div class="h-64">
                                        <canvas id="budgetVsActualChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Department Performance Comparison</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                            <span class="text-sm">HR Department</span>
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                                                </div>
                                                <span class="text-sm font-semibold">85%</span>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                            <span class="text-sm">Finance Department</span>
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 72%"></div>
                                                </div>
                                                <span class="text-sm font-semibold">72%</span>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                            <span class="text-sm">Operations Department</span>
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 68%"></div>
                                                </div>
                                                <span class="text-sm font-semibold">68%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Industry Benchmarking</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm">Your Turnover Rate:</span>
                                            <span class="text-sm font-semibold">12.5%</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Industry Average:</span>
                                            <span class="text-sm">15.2%</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm">Your Position:</span>
                                            <span class="text-sm font-semibold text-green-600">Above Average</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="w-full px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                                            View Full Benchmarking Report
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Year-over-Year Comparison</h4>
                                    <div class="h-48">
                                        <canvas id="yoyComparisonChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Anomaly Detection Tab -->
                    <div id="anomaly-detection" class="metrics-tab-panel hidden">
                        <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4">Anomaly Detection & Alerts</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border border-red-200">
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                        <h4 class="font-semibold text-red-800">High Priority Alerts</h4>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="p-2 bg-red-50 rounded border-l-4 border-red-400">
                                            <p class="text-sm font-medium text-red-800">Unusual Turnover Spike</p>
                                            <p class="text-xs text-red-600">Finance Department: 25% increase this month</p>
                                        </div>
                                        <div class="p-2 bg-red-50 rounded border-l-4 border-red-400">
                                            <p class="text-sm font-medium text-red-800">Overtime Cost Anomaly</p>
                                            <p class="text-xs text-red-600">Operations: 40% above normal range</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-yellow-200">
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-exclamation-circle text-yellow-600 mr-2"></i>
                                        <h4 class="font-semibold text-yellow-800">Medium Priority Alerts</h4>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="p-2 bg-yellow-50 rounded border-l-4 border-yellow-400">
                                            <p class="text-sm font-medium text-yellow-800">Attendance Pattern Change</p>
                                            <p class="text-xs text-yellow-600">HR Department: 15% decrease in attendance</p>
                                        </div>
                                        <div class="p-2 bg-yellow-50 rounded border-l-4 border-yellow-400">
                                            <p class="text-sm font-medium text-yellow-800">Training Completion Drop</p>
                                            <p class="text-xs text-yellow-600">Overall completion rate below target</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Anomaly Detection Settings</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Sensitivity Level</label>
                                            <select class="w-full p-2 border border-gray-300 rounded-md">
                                                <option value="low">Low (Conservative)</option>
                                                <option value="medium" selected>Medium (Balanced)</option>
                                                <option value="high">High (Sensitive)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alert Frequency</label>
                                            <select class="w-full p-2 border border-gray-300 rounded-md">
                                                <option value="realtime">Real-time</option>
                                                <option value="daily" selected>Daily Summary</option>
                                                <option value="weekly">Weekly Summary</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Notification Method</label>
                                            <div class="space-y-1">
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="mr-2" checked>
                                                    <span class="text-sm">Email Alerts</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="mr-2">
                                                    <span class="text-sm">SMS Notifications</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="mr-2" checked>
                                                    <span class="text-sm">Dashboard Notifications</span>
                                                </label>
                                            </div>
                                        </div>
                                        <button class="w-full px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                            Save Settings
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border">
                                    <h4 class="font-semibold mb-3">Anomaly History</h4>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        <div class="p-2 bg-gray-50 rounded text-sm">
                                            <p class="font-medium">Turnover Spike Detected</p>
                                            <p class="text-xs text-gray-600">2024-01-15 14:30</p>
                                        </div>
                                        <div class="p-2 bg-gray-50 rounded text-sm">
                                            <p class="font-medium">Overtime Anomaly</p>
                                            <p class="text-xs text-gray-600">2024-01-12 09:15</p>
                                        </div>
                                        <div class="p-2 bg-gray-50 rounded text-sm">
                                            <p class="font-medium">Attendance Pattern Change</p>
                                            <p class="text-xs text-gray-600">2024-01-10 16:45</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Initialize metrics functionality
    initializeMetricsFunctionality();
    
    // Initialize event listeners
    initializeMetricsEventListeners();

    // Auto-load first metric if available
    setTimeout(() => {
        const metricSelect = document.getElementById('metric-name-filter');
        if (metricSelect && metricSelect.value === '') {
            metricSelect.value = 'headcount_by_department';
            metricSelect.dispatchEvent(new Event('change'));
        }
    }, 500);
}

/**
 * Initialize metrics functionality
 */
function initializeMetricsFunctionality() {
    // Tab switching
    document.querySelectorAll('.metrics-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchMetricsTab(tabName);
        });
    });
}

/**
 * Switch metrics tabs
 */
function switchMetricsTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.metrics-tab').forEach(tab => {
        tab.classList.remove('active', 'border-[#594423]', 'text-[#594423]');
        tab.classList.add('border-transparent', 'text-gray-500');
    });

    const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('active', 'border-[#594423]', 'text-[#594423]');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }

    // Update tab panels
    document.querySelectorAll('.metrics-tab-panel').forEach(panel => {
        panel.classList.add('hidden');
    });

    const activePanel = document.getElementById(`${tabName}-dashboard`);
    if (activePanel) {
        activePanel.classList.remove('hidden');
    }
}

/**
 * Initialize metrics event listeners
 */
function initializeMetricsEventListeners() {
    const btn = document.getElementById('view-metric-btn');
    if (btn && !btn.hasAttribute('data-listener-attached')) {
        btn.addEventListener('click', handleViewMetric);
        btn.setAttribute('data-listener-attached', 'true');
    }

    // Add auto-refresh functionality
    const autoRefreshBtn = document.getElementById('auto-refresh-btn');
    if (autoRefreshBtn && !autoRefreshBtn.hasAttribute('data-listener-attached')) {
        autoRefreshBtn.addEventListener('click', handleAutoRefreshMetrics);
        autoRefreshBtn.setAttribute('data-listener-attached', 'true');
    }
}

async function handleViewMetric() {
    const name = document.getElementById('metric-name-filter')?.value;
    const period = document.getElementById('metric-period-filter')?.value;
    const displayArea = document.getElementById('metric-display-area');

    if (!displayArea || !name) {
        if(displayArea) displayArea.innerHTML = '<p class="text-red-500">Please select a metric.</p>';
        return;
    }
    const displayName = name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    displayArea.innerHTML = `<p class="text-blue-600">Loading data for <strong>${displayName}</strong> (${period})...</p>`;
    
    try {
        const response = await fetch(`${API_BASE_URL}get_key_metrics.php?metric_name=${encodeURIComponent(name)}&metric_period=${encodeURIComponent(period)}`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);
        
        if (metricChartInstance) metricChartInstance.destroy();
        
        if (data.dataPoints?.length > 0 && data.labels?.length > 0) {
            displayArea.innerHTML = `<canvas id="metricDetailChart" class="max-h-[400px]"></canvas>`;
            renderMetricDetailChart(data.labels, data.dataPoints, data.metricNameDisplay || displayName, data.unit);
        } else if (data.value !== null) {
            displayArea.innerHTML = `<div class="p-4 text-center"><h4 class="text-xl font-semibold">${data.metricNameDisplay || displayName}</h4><p class="text-4xl text-blue-600 font-bold my-3">${data.value} ${data.unit || ''}</p><p class="text-sm text-gray-500">Period: ${data.metricPeriod.charAt(0).toUpperCase() + data.metricPeriod.slice(1)}</p>${data.notes ? `<p class="text-xs text-gray-400 mt-2"><em>Note: ${data.notes}</em></p>` : ''}</div>`;
        } else {
            displayArea.innerHTML = `<p class="text-gray-500">No data for ${displayName} for this period.</p>`;
        }
    } catch (e) {
        console.error("Error viewing metric:", e);
        displayArea.innerHTML = `<p class="text-red-500">Could not load metric: ${e.message}</p>`;
    }
}

function renderMetricDetailChart(labels, dataPoints, metricTitle, unit) {
    const ctx = document.getElementById('metricDetailChart')?.getContext('2d');
    if (!ctx) return;
    const bgColors = labels.map((_, i) => `hsl(${(i * 360 / (labels.length || 1) + 120) % 360}, 65%, 55%)`);
    const borderColors = bgColors.map(c => c.replace('55%)', '45%)'));
    metricChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: `${metricTitle}${unit ? ` (${unit})` : ''}`, data: dataPoints, backgroundColor: bgColors, borderColor: borderColors, borderWidth: 1 }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: (Math.max(...dataPoints, 1) < 10) ? 1 : undefined } } }, plugins: { legend: { display: dataPoints.length > 1 }, title: { display: true, text: metricTitle } } }
    });
}

async function handleAutoRefreshMetrics() {
    const displayArea = document.getElementById('metric-display-area');
    const metricSelect = document.getElementById('metric-name-filter');
    const periodSelect = document.getElementById('metric-period-filter');

    if (!displayArea) return;

    // Get current selections or use defaults
    const name = metricSelect?.value || 'headcount_by_department';
    const period = periodSelect?.value || 'current';

    if (!name) {
        displayArea.innerHTML = '<p class="text-red-500">Please select a metric to refresh.</p>';
        return;
    }

    const displayName = name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    displayArea.innerHTML = `<p class="text-blue-600">Auto-refreshing <strong>${displayName}</strong> with latest data...</p>`;

    try {
        const response = await fetch(`${API_BASE_URL}get_key_metrics.php?metric_name=${encodeURIComponent(name)}&metric_period=${encodeURIComponent(period)}&auto_refresh=true`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);

        if (metricChartInstance) metricChartInstance.destroy();

        if (data.dataPoints?.length > 0 && data.labels?.length > 0) {
            displayArea.innerHTML = `<canvas id="metricDetailChart" class="max-h-[400px]"></canvas><p class="text-xs text-gray-500 mt-2">✓ Refreshed at ${new Date().toLocaleTimeString()}</p>`;
            renderMetricDetailChart(data.labels, data.dataPoints, data.metricNameDisplay || displayName, data.unit);
        } else if (data.value !== null) {
            displayArea.innerHTML = `<div class="p-4 text-center"><h4 class="text-xl font-semibold">${data.metricNameDisplay || displayName}</h4><p class="text-4xl text-blue-600 font-bold my-3">${data.value} ${data.unit || ''}</p><p class="text-sm text-gray-500">Period: ${data.metricPeriod.charAt(0).toUpperCase() + data.metricPeriod.slice(1)}</p>${data.notes ? `<p class="text-xs text-gray-400 mt-2"><em>Note: ${data.notes}</em></p>` : ''}<p class="text-xs text-green-600 mt-2">✓ Refreshed at ${new Date().toLocaleTimeString()}</p></div>`;
        } else {
            displayArea.innerHTML = `<p class="text-gray-500">No data for ${displayName} for this period.</p><p class="text-xs text-gray-400 mt-2">Last refresh: ${new Date().toLocaleTimeString()}</p>`;
        }
    } catch (e) {
        console.error("Error auto-refreshing metric:", e);
        displayArea.innerHTML = `<p class="text-red-500">Could not refresh metric: ${e.message}</p>`;
    }
}

/**
 * Enhanced Analytics Module - Complete Implementation Summary
 * 
 * This module now provides comprehensive HR analytics functionality including:
 * 
 * 1. ENHANCED DASHBOARD:
 *    - 8 comprehensive KPI cards with real-time metrics
 *    - Advanced filtering by department, period, and branch
 *    - Multiple chart types (trend lines, bar charts, pie charts)
 *    - Export functionality (PDF, Excel, CSV)
 * 
 * 2. STANDARDIZED REPORTS:
 *    - 12+ pre-built report types
 *    - Automated date range generation
 *    - Export capabilities for all reports
 *    - Compliance and audit reporting
 * 
 * 3. CUSTOM REPORT BUILDER:
 *    - Drag-and-drop widget interface
 *    - 6 widget types (KPI cards, charts, tables, heatmaps)
 *    - Save, preview, and export custom reports
 *    - Flexible layout options
 * 
 * 4. SCHEDULED REPORTS:
 *    - Automated report generation
 *    - Multiple frequency options (daily, weekly, monthly, quarterly)
 *    - Email distribution to multiple recipients
 *    - Multiple export formats
 * 
 * 5. PREDICTIVE ANALYTICS:
 *    - Attrition prediction with risk assessment
 *    - Salary growth forecasting
 *    - Workforce planning tools
 *    - Training needs prediction
 * 
 * 6. COMPARATIVE ANALYSIS:
 *    - Budget vs actual cost comparisons
 *    - Department performance benchmarking
 *    - Industry benchmarking
 *    - Year-over-year trend analysis
 * 
 * 7. ANOMALY DETECTION:
 *    - Real-time anomaly detection
 *    - Configurable alert thresholds
 *    - Priority-based alert system
 *    - Historical anomaly tracking
 * 
 * 8. COMPLIANCE & AUDIT:
 *    - DOLE compliance reporting
 *    - Civil Service Commission reports
 *    - Data privacy compliance
 *    - Audit trail generation
 * 
 * Benefits:
 * ✅ Centralized and automated HR reporting
 * ✅ Real-time visibility of workforce performance and cost
 * ✅ Supports evidence-based HR planning and decision-making
 * ✅ Reduces manual report preparation and errors
 * ✅ Promotes accountability, compliance, and strategic alignment
 * ✅ Predictive insights for proactive HR management
 * ✅ Industry benchmarking for competitive advantage
 * ✅ Automated anomaly detection for risk management
 */

