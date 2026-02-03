/**
 * HR Analytics Dashboard Module
 * Transforms raw HR and payroll data into actionable insights through interactive analytics
 * Consolidates data from HR Core, Payroll, Compensation, and Benefits modules
 * 
 * API INTEGRATION STRATEGY:
 * - Dashboard Charts: New Analytics API (hr-analytics/*) - Chart-optimized
 * - Reports Module: Existing Reports API (hr-reports/*) - Full features + export
 * - Metrics Module: Existing Metrics API (hr-analytics/metrics/*) - Real-time KPIs
 */

import { LEGACY_API_URL, REST_API_URL } from '../utils.js';

// ========================================================================
// UTILITY FUNCTIONS
// ========================================================================

/**
 * Safely convert value to number and format with toFixed
 * Handles strings, null, undefined, and actual numbers
 */
function safeToFixed(value, decimals = 1) {
    const num = parseFloat(value);
    return isNaN(num) ? parseFloat(0).toFixed(decimals) : num.toFixed(decimals);
}

/**
 * Safely convert value to number
 */
function safeNumber(value, defaultValue = 0) {
    const num = parseFloat(value);
    return isNaN(num) ? defaultValue : num;
}

// ========================================================================
// API ENDPOINT CONFIGURATION
// ========================================================================

const API_ENDPOINTS = {
    // New Analytics API - Chart-optimized data
    charts: {
        executiveSummary: 'hr-analytics/executive-summary',
        headcountTrend: 'hr-analytics/headcount-trend',
        turnoverByDept: 'hr-analytics/turnover-by-department',
        payrollTrend: 'hr-analytics/payroll-trend',
        demographics: 'hr-analytics/employee-demographics',
        payrollCompensation: 'hr-analytics/payroll-compensation',
        benefitsHMO: 'hr-analytics/benefits-hmo',
        trainingDev: 'hr-analytics/training-development',
        benefitTypes: 'hmo/analytics/benefit-types-summary'
    },
    
    // Existing Reports API - Comprehensive reports with export
    reports: {
        dashboard: 'hr-reports/dashboard',
        demographics: 'hr-reports/employee-demographics',
        recruitment: 'hr-reports/recruitment-application',
        payroll: 'hr-reports/payroll-compensation',
        attendance: 'hr-reports/attendance-leave',
        benefits: 'hr-reports/benefits-hmo-utilization',
        training: 'hr-reports/training-development',
        relations: 'hr-reports/employee-relations-engagement',
        turnover: 'hr-reports/turnover-retention',
        compliance: 'hr-reports/compliance-document',
        executive: 'hr-reports/executive-summary',
        export: 'hr-reports/export',
        schedule: 'hr-reports/schedule',
        scheduled: 'hr-reports/scheduled'
    },
    
    // Existing Metrics API - Real-time KPIs
    metrics: {
        categories: 'hr-analytics/metrics/categories',
        dashboard: 'hr-analytics/metrics/dashboard/',
        calculate: 'hr-analytics/metrics/calculate/',
        trends: 'hr-analytics/metrics/trends/',
        summary: 'hr-analytics/metrics/summary/',
        alerts: 'hr-analytics/metrics/alerts/'
    }
};

// Chart instances for cleanup
let chartInstances = {};

// Shared elements
let pageTitleElement;
let mainContentArea;

// Current active tab
let currentTab = 'overview';

// ========================================================================
// HELPER FUNCTIONS
// ========================================================================

/**
 * Get date from range selector
 */
function getDateFromRange(range, type = 'from') {
    const now = new Date();
    let date = new Date();
    
    switch(range) {
        case 'this-month':
            date = type === 'from' ? new Date(now.getFullYear(), now.getMonth(), 1) : now;
            break;
        case 'last-month':
            if (type === 'from') {
                date = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            } else {
                date = new Date(now.getFullYear(), now.getMonth(), 0);
            }
            break;
        case 'this-quarter':
            const quarter = Math.floor(now.getMonth() / 3);
            date = type === 'from' ? new Date(now.getFullYear(), quarter * 3, 1) : now;
            break;
        case 'this-year':
            date = type === 'from' ? new Date(now.getFullYear(), 0, 1) : now;
            break;
        case 'last-year':
            if (type === 'from') {
                date = new Date(now.getFullYear() - 1, 0, 1);
            } else {
                date = new Date(now.getFullYear() - 1, 11, 31);
            }
            break;
        default:
            date = type === 'from' ? new Date(now.getFullYear(), 0, 1) : now;
    }
    
    return date.toISOString().split('T')[0];
}

/**
 * Build query string from filters
 */
function buildQueryString(filters) {
    const params = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            params.append(key, filters[key]);
        }
    });
    return params.toString();
}

/**
 * Initialize common elements
 */
function initializeElements() {
    pageTitleElement = document.getElementById('page-title');
    mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("Analytics Module: Core DOM elements not found!");
        return false;
    }
    return true;
}

/**
 * Clean up existing charts
 */
function cleanupCharts() {
    Object.values(chartInstances).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    });
    chartInstances = {};
}

/**
 * Fetch departments for filters
 */
async function fetchDepartments() {
    try {
        const response = await fetch(`${LEGACY_API_URL}get_org_structure.php`);
        if (!response.ok) return [];
        const data = await response.json();
        return data.data || [];
    } catch (error) {
        console.error('Error fetching departments:', error);
        return [];
    }
}

// ========================================================================
// DASHBOARD - Main Entry Point with Navigation Tabs
// ========================================================================

export async function displayAnalyticsDashboardsSection() {
    console.log('[Analytics] Loading HR Analytics Dashboard...');
    
    if (!initializeElements()) return;
    
    pageTitleElement.textContent = 'HR Analytics Dashboard';
    cleanupCharts();
    
    // Get departments for filters
    const departments = await fetchDepartments();
    const deptOptions = departments.map(d => 
        `<option value="${d.DepartmentID}">${d.DepartmentName}</option>`
    ).join('');
    
    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Global Filters -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-building mr-1"></i>Department
                        </label>
                        <select id="global-dept-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Departments</option>
                            ${deptOptions}
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt mr-1"></i>Date Range
                        </label>
                        <select id="global-date-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="1month">Last Month</option>
                            <option value="3months">Last 3 Months</option>
                            <option value="6months">Last 6 Months</option>
                            <option value="12months" selected>Last 12 Months</option>
                            <option value="ytd">Year to Date</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user-tag mr-1"></i>Employment Type
                        </label>
                        <select id="global-emptype-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Types</option>
                            <option value="Regular">Regular</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button id="apply-filters-btn" class="px-6 py-2.5 bg-gray-800 text-white rounded-md hover:bg-gray-900 transition-colors font-medium">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        <button id="refresh-dashboard-btn" class="px-6 py-2.5 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors font-medium">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button id="export-dashboard-btn" class="px-6 py-2.5 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" id="analytics-tabs">
                        <button class="analytics-tab active px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600" data-tab="overview">
                            <i class="fas fa-tachometer-alt mr-2"></i>Overview
                        </button>
                        <button class="analytics-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="workforce">
                            <i class="fas fa-users mr-2"></i>Workforce Analytics
                        </button>
                        <button class="analytics-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="payroll">
                            <i class="fas fa-money-bill-wave mr-2"></i>Payroll Insights
                        </button>
                        <button class="analytics-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="benefits">
                            <i class="fas fa-hand-holding-medical mr-2"></i>Benefits Utilization
                        </button>
                        <button class="analytics-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="training">
                            <i class="fas fa-graduation-cap mr-2"></i>Training & Performance
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div id="tab-content-area" class="p-6">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    `;
    
    // Setup tab navigation
    setupTabNavigation();
    
    // Load default tab (Overview)
    loadOverviewTab();
    
    // Setup event listeners
    setupGlobalEventListeners();
}

/**
 * Setup tab navigation
 */
function setupTabNavigation() {
    const tabs = document.querySelectorAll('.analytics-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active state
            tabs.forEach(t => {
                t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            tab.classList.add('active', 'border-blue-500', 'text-blue-600');
            tab.classList.remove('border-transparent', 'text-gray-500');
            
            // Load tab content
            const tabName = tab.dataset.tab;
            currentTab = tabName;
            loadTabContent(tabName);
        });
    });
}

/**
 * Load tab content based on selected tab
 */
function loadTabContent(tabName) {
    cleanupCharts();
    
    switch(tabName) {
        case 'overview':
            loadOverviewTab();
            break;
        case 'workforce':
            loadWorkforceTab();
            break;
        case 'payroll':
            loadPayrollTab();
            break;
        case 'benefits':
            loadBenefitsTab();
            break;
        case 'training':
            loadTrainingTab();
            break;
    }
}

/**
 * Setup global event listeners
 */
function setupGlobalEventListeners() {
    // Apply Filters button
    const applyBtn = document.getElementById('apply-filters-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            loadTabContent(currentTab);
            setTimeout(() => {
                applyBtn.innerHTML = '<i class="fas fa-filter mr-2"></i>Apply Filters';
            }, 1000);
        });
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refresh-dashboard-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
            loadTabContent(currentTab);
            setTimeout(() => {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Refresh';
            }, 1000);
        });
    }
    
    // Export button
    const exportBtn = document.getElementById('export-dashboard-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => exportDashboard());
    }
}

// ========================================================================
// TAB 1: OVERVIEW - Summary KPIs and Key Charts
// ========================================================================

async function loadOverviewTab() {
    const tabContent = document.getElementById('tab-content-area');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <!-- Top Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Active Employees -->
            <div class="bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-users" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Total Active Employees</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-total-employees">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-employees-change">Loading...</div>
                </div>
            </div>

            <!-- Monthly Headcount Change -->
            <div class="bg-gradient-to-br from-emerald-400 via-emerald-500 to-emerald-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-user-plus" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-user-plus text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Monthly Headcount Change</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-headcount-change">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-headcount-detail">Loading...</div>
                </div>
            </div>

            <!-- Turnover Rate -->
            <div class="bg-gradient-to-br from-red-400 via-red-500 to-red-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-exchange-alt" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-user-times text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Turnover Rate</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-turnover-rate">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-turnover-detail">Loading...</div>
                </div>
            </div>

            <!-- Payroll Cost per Department -->
            <div class="bg-gradient-to-br from-green-400 via-green-500 to-green-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-money-bill-wave" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Monthly Payroll Cost</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-payroll-cost">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80">This month</div>
                </div>
            </div>

            <!-- Benefit Utilization Rate -->
            <div class="bg-gradient-to-br from-purple-400 via-purple-500 to-purple-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-hand-holding-medical" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-hand-holding-medical text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Benefit Utilization Rate</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-benefit-utilization">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-benefit-detail">Loading...</div>
                </div>
            </div>

            <!-- Training & Competency Index -->
            <div class="bg-gradient-to-br from-indigo-400 via-indigo-500 to-indigo-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-graduation-cap" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-graduation-cap text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Training & Competency Index</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-training-index">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-training-detail">Loading...</div>
                </div>
            </div>

            <!-- Attendance Rate -->
            <div class="bg-gradient-to-br from-teal-400 via-teal-500 to-teal-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-calendar-check" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Attendance Rate</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-attendance-rate">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80">This month</div>
                </div>
            </div>

            <!-- Salary Equity & Pay Band Compliance -->
            <div class="bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 rounded-lg shadow-lg p-5 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10">
                    <i class="fas fa-balance-scale" style="font-size: 80px;"></i>
                </div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-balance-scale text-2xl"></i>
                        </div>
                    </div>
                    <div class="text-sm font-semibold uppercase tracking-wide opacity-90 mb-1">Pay Band Compliance</div>
                    <div class="text-3xl font-bold mb-2" id="kpi-pay-compliance">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-sm opacity-80" id="kpi-pay-detail">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Key Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Headcount Trend -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                    Headcount Trend (Last 12 Months)
                </h3>
                <canvas id="headcount-trend-chart"></canvas>
            </div>

            <!-- Turnover by Department -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-red-500 mr-2"></i>
                    Turnover by Department
                </h3>
                <canvas id="turnover-dept-chart"></canvas>
            </div>

            <!-- Payroll Cost Trend -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-area text-green-500 mr-2"></i>
                    Payroll Cost Trend
                </h3>
                <canvas id="payroll-trend-chart"></canvas>
            </div>

            <!-- Benefits Utilization -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
                    Benefits Utilization by Type
                </h3>
                <canvas id="benefits-utilization-chart"></canvas>
            </div>
        </div>
    `;
    
    // Load all data
    await Promise.all([
        loadOverviewKPIs(),
        loadOverviewCharts()
    ]);
}

/**
 * Load Overview KPIs
 * Uses hr-analytics/executive-summary for KPI data
 */
async function loadOverviewKPIs() {
    try {
        // Use the executive-summary endpoint which has the correct field structure
        const response = await fetch(`${REST_API_URL}hr-analytics/executive-summary`);
        const result = await response.json();
        
        console.log('[Analytics] Executive summary response:', result);
        
        if (result.success && result.data) {
            // The executive-summary returns data in result.data.overview structure
            const data = result.data.overview || result.data || {};
            
            // Small helper to safely set textContent if element exists
            const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
            const setHTML = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };

            // Total Active Employees
            setText('kpi-total-employees', (data.total_active_employees || 0).toLocaleString());
            const headcountChange = data.headcount_change || 0;
            setHTML('kpi-employees-change', `<i class="fas fa-arrow-${headcountChange >= 0 ? 'up' : 'down'} mr-1"></i>${headcountChange > 0 ? '+' : ''}${headcountChange} this month`);
            
            // Monthly Headcount Change
            setText('kpi-headcount-change', `${headcountChange > 0 ? '+' : ''}${headcountChange}`);
            setHTML('kpi-headcount-detail', `Net change this month`);
            
            // Turnover Rate
            const turnoverRate = parseFloat(data.annual_turnover_rate || 0);
            setText('kpi-turnover-rate', `${safeToFixed(turnoverRate, 1)}%`);
            setHTML('kpi-turnover-detail', `<i class="fas fa-arrow-down mr-1"></i>Annual rate`);
            
            // Payroll Cost
            const payrollCost = parseFloat(data.total_monthly_payroll || 0);
            setText('kpi-payroll-cost', `₱${safeNumber(payrollCost).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            
            // Benefit Utilization
            const benefitUtil = parseFloat(data.benefit_utilization || 0);
            setText('kpi-benefit-utilization', `${safeToFixed(benefitUtil, 1)}%`);
            setText('kpi-benefit-detail', `Utilization rate`);
            
            // Training Index
            const trainingIndex = parseFloat(data.training_index || 0);
            setText('kpi-training-index', `${safeToFixed(trainingIndex, 1)}`);
            setText('kpi-training-detail', `Competency score`);
            
            // Attendance Rate
            const attendanceRate = parseFloat(data.attendance_rate || 0);
            setText('kpi-attendance-rate', `${safeToFixed(attendanceRate, 1)}%`);
            
            // Pay Compliance
            const payCompliance = parseFloat(data.payband_compliance || 0);
            setText('kpi-pay-compliance', `${safeToFixed(payCompliance, 1)}%`);
            setText('kpi-pay-detail', 'Within pay bands');
        }
    } catch (error) {
        console.error('Error loading Overview KPIs:', error);
    }
}

/**
 * Load Overview Charts
 */
async function loadOverviewCharts() {
    await Promise.all([
        loadHeadcountTrendChart(),
        loadTurnoverByDeptChart(),
        loadPayrollTrendChart(),
        loadBenefitsUtilizationChart()
    ]);
}

/**
 * Load Headcount Trend Chart
 */
async function loadHeadcountTrendChart() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/headcount-trend`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ctx = document.getElementById('headcount-trend-chart');
            if (!ctx) return;
            
            const labels = result.data.map(d => d.month || '');
            const headcount = result.data.map(d => parseInt(d.total_headcount || 0));
            const hires = result.data.map(d => parseInt(d.new_hires || 0));
            const exits = result.data.map(d => parseInt(d.separations || 0));
            
            if (chartInstances.headcountTrend) chartInstances.headcountTrend.destroy();
            
            chartInstances.headcountTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Headcount',
                            data: headcount,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'New Hires',
                            data: hires,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Exits',
                            data: exits,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${context.parsed.y}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Total Headcount' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Hires / Exits' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading headcount trend chart:', error);
    }
}

/**
 * Load Turnover by Department Chart
 */
async function loadTurnoverByDeptChart() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/turnover-by-department`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ctx = document.getElementById('turnover-dept-chart');
            if (!ctx) return;
            
            const labels = result.data.map(d => d.department_name || 'Unknown');
            const turnoverRates = result.data.map(d => parseFloat(d.turnover_rate || 0));
            
            if (chartInstances.turnoverDept) chartInstances.turnoverDept.destroy();
            
            chartInstances.turnoverDept = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Turnover Rate (%)',
                        data: turnoverRates,
                        backgroundColor: turnoverRates.map(rate => 
                            rate > 15 ? '#EF4444' : rate > 10 ? '#F59E0B' : '#10B981'
                        ),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `Turnover Rate: ${context.parsed.y.toFixed(1)}%`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Turnover Rate (%)' },
                            ticks: {
                                callback: (value) => `${value}%`
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading turnover by department chart:', error);
    }
}

/**
 * Load Payroll Trend Chart
 */
async function loadPayrollTrendChart() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/payroll-trend`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ctx = document.getElementById('payroll-trend-chart');
            if (!ctx) return;
            
            const labels = result.data.map(d => d.month || '');
            const totalPayroll = result.data.map(d => parseFloat(d.total_payroll || 0));
            const basicPay = result.data.map(d => parseFloat(d.basic_pay || 0));
            const overtime = result.data.map(d => parseFloat(d.overtime || 0));
            const bonuses = result.data.map(d => parseFloat(d.bonuses || 0));
            
            if (chartInstances.payrollTrend) chartInstances.payrollTrend.destroy();
            
            chartInstances.payrollTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Payroll',
                            data: totalPayroll,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3
                        },
                        {
                            label: 'Basic Pay',
                            data: basicPay,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Overtime',
                            data: overtime,
                            borderColor: '#F59E0B',
                            backgroundColor: 'rgba(245, 158, 11, 0.05)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Bonuses',
                            data: bonuses,
                            borderColor: '#8B5CF6',
                            backgroundColor: 'rgba(139, 92, 246, 0.05)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ₱${context.parsed.y.toLocaleString('en-PH')}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => `₱${value.toLocaleString('en-PH')}`
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading payroll trend chart:', error);
    }
}

/**
 * Load Benefits Utilization Chart
 */
async function loadBenefitsUtilizationChart() {
    try {
        const response = await fetch(`${REST_API_URL}hmo/analytics/benefit-types-summary`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ctx = document.getElementById('benefits-utilization-chart');
            if (!ctx) return;
            
            const labels = result.data.map(d => d.benefit_type || 'Unknown');
            const amounts = result.data.map(d => parseFloat(d.total_amount || 0));
            
            if (chartInstances.benefitsUtil) chartInstances.benefitsUtil.destroy();
            
            chartInstances.benefitsUtil = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: amounts,
                        backgroundColor: [
                            '#8B5CF6',
                            '#EC4899',
                            '#F59E0B',
                            '#10B981',
                            '#3B82F6',
                            '#EF4444',
                            '#14B8A6',
                            '#F97316'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'right' },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ₱${context.parsed.toLocaleString('en-PH')}`
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading benefits utilization chart:', error);
    }
}

// ========================================================================
// TAB 2: WORKFORCE ANALYTICS
// ========================================================================

async function loadWorkforceTab() {
    const tabContent = document.getElementById('tab-content-area');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <!-- Workforce Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-blue-700 font-semibold">Total Workforce</div>
                        <i class="fas fa-users text-3xl text-blue-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900" id="wf-total">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-xs text-blue-600 mt-2">Active employees</div>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-green-700 font-semibold">Average Age</div>
                        <i class="fas fa-birthday-cake text-3xl text-green-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-green-900" id="wf-avg-age">--</div>
                    <div class="text-xs text-green-600 mt-2">Years</div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-purple-700 font-semibold">Average Tenure</div>
                        <i class="fas fa-clock text-3xl text-purple-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-purple-900" id="wf-avg-tenure">--</div>
                    <div class="text-xs text-purple-600 mt-2">Years of service</div>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-orange-700 font-semibold">Gender Diversity</div>
                        <i class="fas fa-venus-mars text-3xl text-orange-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-orange-900" id="wf-gender-ratio">--</div>
                    <div class="text-xs text-orange-600 mt-2">Male / Female ratio</div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-building text-blue-500 mr-2"></i>Headcount by Department
                    </h3>
                    <canvas id="wf-dept-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-user-tag text-green-500 mr-2"></i>Employment Type Distribution
                    </h3>
                    <canvas id="wf-emptype-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-venus-mars text-purple-500 mr-2"></i>Gender Distribution
                    </h3>
                    <canvas id="wf-gender-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-graduation-cap text-indigo-500 mr-2"></i>Education Level Distribution
                    </h3>
                    <canvas id="wf-education-chart"></canvas>
                </div>
            </div>
            
            <!-- Age Demographics -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-bar text-teal-500 mr-2"></i>Age Demographics
                </h3>
                <canvas id="wf-age-chart"></canvas>
            </div>
            
            <!-- Department Details Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Department Workforce Details</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Headcount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Age</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Tenure</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Male/Female</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regular %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="wf-dept-table">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Load workforce data
    loadWorkforceData();
}

/**
 * Load Workforce Analytics Data
 */
async function loadWorkforceData() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/employee-demographics`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary cards
            document.getElementById('wf-total').textContent = (data.overview?.total_headcount || 0).toLocaleString();
            document.getElementById('wf-avg-age').textContent = `${safeToFixed(data.overview?.avg_age, 1)} yrs`;
            document.getElementById('wf-avg-tenure').textContent = `${safeToFixed(data.overview?.avg_tenure_years, 1)} yrs`;
            document.getElementById('wf-gender-ratio').textContent = `${safeToFixed(data.overview?.male_percentage, 0)}/${safeToFixed(data.overview?.female_percentage, 0)}`;
            
            // Populate department table
            populateWorkforceDeptTable(data.department_distribution || []);
            
            // Load charts
            loadWorkforceCharts(data);
        }
    } catch (error) {
        console.error('Error loading workforce data:', error);
    }
}

/**
 * Load Workforce Charts
 */
function loadWorkforceCharts(data) {
    loadWfDeptChart(data.department_distribution || []);
    loadWfEmpTypeChart(data.employment_type_distribution || []);
    loadWfGenderChart(data.gender_distribution || {});
    loadWfEducationChart(data.education_distribution || []);
    loadWfAgeChart(data.age_distribution || []);
}

/**
 * Headcount by Department Chart
 */
function loadWfDeptChart(departments) {
    const ctx = document.getElementById('wf-dept-chart');
    if (!ctx) return;
    
    if (chartInstances['wfDept']) chartInstances['wfDept'].destroy();
    
    chartInstances['wfDept'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: departments.map(d => d.department),
            datasets: [{
                label: 'Headcount',
                data: departments.map(d => d.headcount),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Employees: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 10 }
                }
            }
        }
    });
}

/**
 * Employment Type Distribution Chart
 */
function loadWfEmpTypeChart(empTypes) {
    const ctx = document.getElementById('wf-emptype-chart');
    if (!ctx) return;
    
    if (chartInstances['wfEmpType']) chartInstances['wfEmpType'].destroy();
    
    chartInstances['wfEmpType'] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: empTypes.map(e => e.employment_type),
            datasets: [{
                data: empTypes.map(e => e.count),
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.parsed} (${((context.parsed / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1)}%)`
                    }
                }
            }
        }
    });
}

/**
 * Gender Distribution Chart
 */
function loadWfGenderChart(genderData) {
    const ctx = document.getElementById('wf-gender-chart');
    if (!ctx) return;
    
    if (chartInstances['wfGender']) chartInstances['wfGender'].destroy();
    
    chartInstances['wfGender'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: [
                    genderData.male || 0,
                    genderData.female || 0,
                    genderData.other || 0
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(168, 85, 247, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Education Level Distribution Chart
 */
function loadWfEducationChart(education) {
    const ctx = document.getElementById('wf-education-chart');
    if (!ctx) return;
    
    if (chartInstances['wfEducation']) chartInstances['wfEducation'].destroy();
    
    chartInstances['wfEducation'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: education.map(e => e.education_level),
            datasets: [{
                label: 'Employees',
                data: education.map(e => e.count),
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Count: ${context.parsed.x}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 5 }
                }
            }
        }
    });
}

/**
 * Age Demographics Chart
 */
function loadWfAgeChart(ageGroups) {
    const ctx = document.getElementById('wf-age-chart');
    if (!ctx) return;
    
    if (chartInstances['wfAge']) chartInstances['wfAge'].destroy();
    
    chartInstances['wfAge'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ageGroups.map(a => a.age_group),
            datasets: [{
                label: 'Employees',
                data: ageGroups.map(a => a.count),
                backgroundColor: 'rgba(20, 184, 166, 0.8)',
                borderColor: 'rgba(20, 184, 166, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Employees: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 10 }
                }
            }
        }
    });
}

/**
 * Populate Workforce Department Table
 */
function populateWorkforceDeptTable(departments) {
    const tbody = document.getElementById('wf-dept-table');
    if (!tbody) return;
    
    if (departments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = departments.map(dept => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${dept.department}</td>
            <td class="px-6 py-4 text-sm text-blue-600 font-semibold">${dept.headcount}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${dept.avg_age} yrs</td>
            <td class="px-6 py-4 text-sm text-gray-700">${dept.avg_tenure} yrs</td>
            <td class="px-6 py-4 text-sm text-gray-700">${dept.male_count}/${dept.female_count}</td>
            <td class="px-6 py-4 text-sm text-green-600">${dept.regular_percentage}%</td>
        </tr>
    `).join('');
}

// ========================================================================
// TAB 3: PAYROLL INSIGHTS
// ========================================================================

async function loadPayrollTab() {
    const tabContent = document.getElementById('tab-content-area');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <!-- Payroll Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-green-700 font-semibold">Total Monthly Payroll</div>
                        <i class="fas fa-money-bill-wave text-3xl text-green-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-green-900" id="pr-total-payroll">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-xs text-green-600 mt-2">Gross payroll cost</div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-blue-700 font-semibold">Average Salary</div>
                        <i class="fas fa-user-circle text-3xl text-blue-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-blue-900" id="pr-avg-salary">--</div>
                    <div class="text-xs text-blue-600 mt-2">Per employee</div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-purple-700 font-semibold">Overtime Cost</div>
                        <i class="fas fa-clock text-3xl text-purple-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-purple-900" id="pr-ot-cost">--</div>
                    <div class="text-xs text-purple-600 mt-2" id="pr-ot-ratio">-- % of total</div>
                </div>
                
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-amber-700 font-semibold">Pay Band Compliance</div>
                        <i class="fas fa-balance-scale text-3xl text-amber-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-amber-900" id="pr-compliance">--</div>
                    <div class="text-xs text-amber-600 mt-2">Within ranges</div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line text-green-500 mr-2"></i>Payroll Cost Trend (12 Months)
                    </h3>
                    <canvas id="pr-trend-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar text-blue-500 mr-2"></i>Salary Grade Distribution
                    </h3>
                    <canvas id="pr-grade-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-building text-indigo-500 mr-2"></i>Department Payroll Cost
                    </h3>
                    <canvas id="pr-dept-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie text-purple-500 mr-2"></i>Payroll Breakdown
                    </h3>
                    <canvas id="pr-breakdown-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 3 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-area text-orange-500 mr-2"></i>Overtime Trend
                    </h3>
                    <canvas id="pr-ot-trend-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-scatter text-teal-500 mr-2"></i>Salary vs Pay Band Range
                    </h3>
                    <canvas id="pr-paybands-chart"></canvas>
                </div>
            </div>
            
            <!-- Department Payroll Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Department Payroll Summary</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Pay</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deductions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Salary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="pr-dept-table">
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Load payroll data
    loadPayrollData();
}

/**
 * Load Payroll Data
 */
async function loadPayrollData() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/payroll-compensation`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary cards
            document.getElementById('pr-total-payroll').textContent = 
                `₱${(data.overview?.total_payroll || 0).toLocaleString('en-PH')}`;
            document.getElementById('pr-avg-salary').textContent = 
                `₱${safeNumber(data.overview?.avg_salary).toLocaleString('en-PH')}`;
            document.getElementById('pr-ot-cost').textContent = 
                `₱${safeNumber(data.overview?.total_overtime).toLocaleString('en-PH')}`;
            document.getElementById('pr-ot-ratio').textContent = 
                `${safeToFixed(data.overview?.ot_percentage, 1)}% of total`;
            document.getElementById('pr-compliance').textContent = 
                `${safeToFixed(data.overview?.pay_band_compliance, 1)}%`;
            
            // Populate department table
            populatePayrollDeptTable(data.department_data || []);
            
            // Load charts
            loadPayrollCharts(data);
        }
    } catch (error) {
        console.error('Error loading payroll data:', error);
    }
}

/**
 * Populate Payroll Department Table
 */
function populatePayrollDeptTable(departments) {
    const tbody = document.getElementById('pr-dept-table');
    if (!tbody) return;
    
    if (departments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = departments.map(dept => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${dept.department || dept.department_name || 'Unassigned'}</td>
            <td class="px-6 py-4 text-sm text-blue-600 font-semibold">${safeNumber(dept.employees).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-green-600">₱${safeNumber(dept.gross_pay).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-purple-600">₱${safeNumber(dept.overtime).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-orange-600">₱${safeNumber(dept.deductions).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-green-700 font-bold">₱${safeNumber(dept.net_pay).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-gray-700">₱${safeNumber(dept.avg_salary).toLocaleString()}</td>
        </tr>
    `).join('');
}

/**
 * Load Payroll Charts
 */
function loadPayrollCharts(data) {
    loadPrTrendChart(data.payroll_trend || []);
    loadPrGradeChart(data.salary_grade_distribution || []);
    loadPrDeptChart(data.department_payroll || []);
    loadPrBreakdownChart(data.payroll_breakdown || {});
    loadPrOtTrendChart(data.overtime_trend || []);
    loadPrPaybandsChart(data.salary_bands || []);
}

/**
 * Payroll Cost Trend Chart (12 Months)
 */
function loadPrTrendChart(trendData) {
    const ctx = document.getElementById('pr-trend-chart');
    if (!ctx) return;
    
    if (chartInstances['prTrend']) chartInstances['prTrend'].destroy();
    
    chartInstances['prTrend'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(t => t.month),
            datasets: [{
                label: 'Total Payroll',
                data: trendData.map(t => t.total_payroll),
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `₱${context.parsed.y.toLocaleString()}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                }
            }
        }
    });
}

/**
 * Salary Grade Distribution Chart
 */
function loadPrGradeChart(gradeData) {
    const ctx = document.getElementById('pr-grade-chart');
    if (!ctx) return;
    
    if (chartInstances['prGrade']) chartInstances['prGrade'].destroy();
    
    chartInstances['prGrade'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: gradeData.map(g => g.grade),
            datasets: [{
                label: 'Employees',
                data: gradeData.map(g => g.count),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Employees: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 5 }
                }
            }
        }
    });
}

/**
 * Department Payroll Cost Chart
 */
function loadPrDeptChart(deptData) {
    const ctx = document.getElementById('pr-dept-chart');
    if (!ctx) return;
    
    if (chartInstances['prDept']) chartInstances['prDept'].destroy();
    
    chartInstances['prDept'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: deptData.map(d => d.department),
            datasets: [{
                label: 'Gross Payroll',
                data: deptData.map(d => d.gross_pay),
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `₱${context.parsed.x.toLocaleString()}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                }
            }
        }
    });
}

/**
 * Payroll Breakdown Chart (Pie)
 */
function loadPrBreakdownChart(breakdown) {
    const ctx = document.getElementById('pr-breakdown-chart');
    if (!ctx) return;
    
    if (chartInstances['prBreakdown']) chartInstances['prBreakdown'].destroy();
    
    chartInstances['prBreakdown'] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Basic Salary', 'Overtime', 'Bonuses', 'Allowances'],
            datasets: [{
                data: [
                    breakdown.basic_salary || 0,
                    breakdown.overtime || 0,
                    breakdown.bonuses || 0,
                    breakdown.allowances || 0
                ],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(59, 130, 246, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ₱${context.parsed.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Overtime Trend Chart
 */
function loadPrOtTrendChart(otData) {
    const ctx = document.getElementById('pr-ot-trend-chart');
    if (!ctx) return;
    
    if (chartInstances['prOtTrend']) chartInstances['prOtTrend'].destroy();
    
    chartInstances['prOtTrend'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: otData.map(o => o.month),
            datasets: [{
                label: 'OT Hours',
                data: otData.map(o => o.ot_hours),
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                borderColor: 'rgba(249, 115, 22, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'OT Cost',
                data: otData.map(o => o.ot_cost),
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                borderColor: 'rgba(168, 85, 247, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            if (context.datasetIndex === 0) {
                                return `${context.dataset.label}: ${context.parsed.y} hrs`;
                            } else {
                                return `${context.dataset.label}: ₱${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Hours' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Cost (₱)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

/**
 * Salary vs Pay Band Range Chart (Scatter)
 */
function loadPrPaybandsChart(bandData) {
    const ctx = document.getElementById('pr-paybands-chart');
    if (!ctx) return;
    
    if (chartInstances['prPaybands']) chartInstances['prPaybands'].destroy();
    
    const scatterData = bandData.map(emp => ({
        x: emp.grade_midpoint || 0,
        y: emp.actual_salary || 0,
        label: emp.employee_name || 'Employee'
    }));
    
    chartInstances['prPaybands'] = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Employee Salaries',
                data: scatterData,
                backgroundColor: 'rgba(20, 184, 166, 0.6)',
                borderColor: 'rgba(20, 184, 166, 1)',
                borderWidth: 1,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => [
                            `Grade Midpoint: ₱${context.parsed.x.toLocaleString()}`,
                            `Actual Salary: ₱${context.parsed.y.toLocaleString()}`
                        ]
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Pay Band Midpoint (₱)' },
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                },
                y: {
                    title: { display: true, text: 'Actual Salary (₱)' },
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                }
            }
        }
    });
}

// ========================================================================
// TAB 4: BENEFITS UTILIZATION
// ========================================================================

async function loadBenefitsTab() {
    const tabContent = document.getElementById('tab-content-area');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <!-- Benefits Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-purple-700 font-semibold">Total Benefits Cost</div>
                        <i class="fas fa-hand-holding-usd text-3xl text-purple-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-purple-900" id="bf-total-cost">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-xs text-purple-600 mt-2">Monthly expense</div>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-green-700 font-semibold">HMO Utilization Rate</div>
                        <i class="fas fa-heartbeat text-3xl text-green-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-green-900" id="bf-utilization">--</div>
                    <div class="text-xs text-green-600 mt-2">Of enrolled</div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-blue-700 font-semibold">Total Claims</div>
                        <i class="fas fa-file-medical text-3xl text-blue-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900" id="bf-claims-count">--</div>
                    <div class="text-xs text-blue-600 mt-2">This month</div>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-orange-700 font-semibold">Avg Processing Time</div>
                        <i class="fas fa-hourglass-half text-3xl text-orange-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-orange-900" id="bf-processing-time">--</div>
                    <div class="text-xs text-orange-600 mt-2">Days</div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line text-purple-500 mr-2"></i>Benefits Cost Trend (12 Months)
                    </h3>
                    <canvas id="bf-trend-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-doughnut text-green-500 mr-2"></i>Claims by HMO Provider
                    </h3>
                    <canvas id="bf-provider-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie text-blue-500 mr-2"></i>Benefit Type Distribution
                    </h3>
                    <canvas id="bf-types-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar text-indigo-500 mr-2"></i>Monthly Claims Volume
                    </h3>
                    <canvas id="bf-volume-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 3 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-percentage text-teal-500 mr-2"></i>Claims Approval Rate
                    </h3>
                    <canvas id="bf-approval-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-list-ol text-amber-500 mr-2"></i>Top 10 Claim Categories
                    </h3>
                    <canvas id="bf-categories-chart"></canvas>
                </div>
            </div>
            
            <!-- HMO Provider Performance Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">HMO Provider Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrolled</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claims Filed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Processing Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="bf-provider-table">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Load benefits data
    loadBenefitsData();
}

/**
 * Load Benefits Data
 */
async function loadBenefitsData() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/benefits-hmo`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary cards
            document.getElementById('bf-total-cost').textContent = 
                `₱${safeNumber(data.overview?.total_benefits_cost).toLocaleString('en-PH')}`;
            document.getElementById('bf-utilization').textContent = 
                `${safeToFixed(data.overview?.hmo_utilization, 1)}%`;
            document.getElementById('bf-claims-count').textContent = 
                safeNumber(data.overview?.total_claims).toLocaleString();
            document.getElementById('bf-processing-time').textContent = 
                `${safeToFixed(data.overview?.avg_processing_time, 1)}`;
            
            // Populate provider table
            populateBenefitsProviderTable(data.provider_data || []);
            
            // Load charts
            loadBenefitsCharts(data);
        }
    } catch (error) {
        console.error('Error loading benefits data:', error);
    }
}

/**
 * Populate Benefits Provider Table
 */
function populateBenefitsProviderTable(providers) {
    const tbody = document.getElementById('bf-provider-table');
    if (!tbody) return;
    
    if (providers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = providers.map(provider => {
        const approvalRate = (provider.approved / provider.claims_filed * 100) || 0;
        const rateColor = approvalRate >= 90 ? 'text-green-600' : approvalRate >= 70 ? 'text-yellow-600' : 'text-red-600';
        
        return `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${provider.provider}</td>
            <td class="px-6 py-4 text-sm text-blue-600">${provider.enrolled}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${provider.claims_filed}</td>
            <td class="px-6 py-4 text-sm ${rateColor} font-semibold">${approvalRate.toFixed(1)}%</td>
            <td class="px-6 py-4 text-sm text-purple-600">₱${provider.avg_cost.toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-orange-600">${provider.processing_time} days</td>
        </tr>
    `}).join('');
}

/**
 * Load Benefits Charts
 */
function loadBenefitsCharts(data) {
    loadBfTrendChart(data.benefits_trend || []);
    loadBfProviderChart(data.provider_claims || []);
    loadBfTypesChart(data.benefit_types || []);
    loadBfVolumeChart(data.monthly_volume || []);
    loadBfApprovalChart(data.approval_stats || {});
    loadBfCategoriesChart(data.claim_categories || []);
}

/**
 * Benefits Cost Trend Chart
 */
function loadBfTrendChart(trendData) {
    const ctx = document.getElementById('bf-trend-chart');
    if (!ctx) return;
    
    if (chartInstances['bfTrend']) chartInstances['bfTrend'].destroy();
    
    chartInstances['bfTrend'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(t => t.month),
            datasets: [{
                label: 'Benefits Cost',
                data: trendData.map(t => t.total_cost),
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                borderColor: 'rgba(168, 85, 247, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `₱${context.parsed.y.toLocaleString()}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                }
            }
        }
    });
}

/**
 * Claims by HMO Provider Chart
 */
function loadBfProviderChart(providers) {
    const ctx = document.getElementById('bf-provider-chart');
    if (!ctx) return;
    
    if (chartInstances['bfProvider']) chartInstances['bfProvider'].destroy();
    
    chartInstances['bfProvider'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: providers.map(p => p.provider),
            datasets: [{
                data: providers.map(p => p.claim_count),
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} claims (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Benefit Type Distribution Chart
 */
function loadBfTypesChart(types) {
    const ctx = document.getElementById('bf-types-chart');
    if (!ctx) return;
    
    if (chartInstances['bfTypes']) chartInstances['bfTypes'].destroy();
    
    chartInstances['bfTypes'] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: types.map(t => t.benefit_type),
            datasets: [{
                data: types.map(t => t.utilization),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(20, 184, 166, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed}% utilization`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Monthly Claims Volume Chart
 */
function loadBfVolumeChart(volumeData) {
    const ctx = document.getElementById('bf-volume-chart');
    if (!ctx) return;
    
    if (chartInstances['bfVolume']) chartInstances['bfVolume'].destroy();
    
    chartInstances['bfVolume'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: volumeData.map(v => v.month),
            datasets: [{
                label: 'Claims Filed',
                data: volumeData.map(v => v.filed),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }, {
                label: 'Claims Approved',
                data: volumeData.map(v => v.approved),
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 10 }
                }
            }
        }
    });
}

/**
 * Claims Approval Rate Chart
 */
function loadBfApprovalChart(approvalData) {
    const ctx = document.getElementById('bf-approval-chart');
    if (!ctx) return;
    
    if (chartInstances['bfApproval']) chartInstances['bfApproval'].destroy();
    
    const approvalRate = approvalData.approval_rate || 0;
    const pendingRate = approvalData.pending_rate || 0;
    const rejectionRate = approvalData.rejection_rate || 0;
    
    chartInstances['bfApproval'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [approvalRate, pendingRate, rejectionRate],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.parsed}%`
                    }
                }
            }
        }
    });
}

/**
 * Top 10 Claim Categories Chart
 */
function loadBfCategoriesChart(categories) {
    const ctx = document.getElementById('bf-categories-chart');
    if (!ctx) return;
    
    if (chartInstances['bfCategories']) chartInstances['bfCategories'].destroy();
    
    // Take top 10 categories
    const top10 = categories.slice(0, 10);
    
    chartInstances['bfCategories'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top10.map(c => c.category),
            datasets: [{
                label: 'Claims',
                data: top10.map(c => c.count),
                backgroundColor: 'rgba(245, 158, 11, 0.8)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Claims: ${context.parsed.x}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 5 }
                }
            }
        }
    });
}

// ========================================================================
// TAB 5: TRAINING & PERFORMANCE
// ========================================================================

async function loadTrainingTab() {
    const tabContent = document.getElementById('tab-content-area');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <!-- Training Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-indigo-700 font-semibold">Participation Rate</div>
                        <i class="fas fa-user-graduate text-3xl text-indigo-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-indigo-900" id="tr-participation">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="text-xs text-indigo-600 mt-2">Of all employees</div>
                </div>
                
                <div class="bg-gradient-to-br from-teal-50 to-teal-100 border border-teal-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-teal-700 font-semibold">Avg Training Hours</div>
                        <i class="fas fa-clock text-3xl text-teal-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-teal-900" id="tr-avg-hours">--</div>
                    <div class="text-xs text-teal-600 mt-2">Per employee</div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-purple-700 font-semibold">Training Cost</div>
                        <i class="fas fa-dollar-sign text-3xl text-purple-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-purple-900" id="tr-cost">--</div>
                    <div class="text-xs text-purple-600 mt-2">This month</div>
                </div>
                
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-amber-700 font-semibold">Competency Score</div>
                        <i class="fas fa-chart-line text-3xl text-amber-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-amber-900" id="tr-competency">--</div>
                    <div class="text-xs text-amber-600 mt-2">Improvement index</div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line text-indigo-500 mr-2"></i>Training Attendance Trend
                    </h3>
                    <canvas id="tr-attendance-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-doughnut text-teal-500 mr-2"></i>Training Type Distribution
                    </h3>
                    <canvas id="tr-types-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar text-purple-500 mr-2"></i>Department Training Hours
                    </h3>
                    <canvas id="tr-dept-hours-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-radar text-blue-500 mr-2"></i>Competency Score by Department
                    </h3>
                    <canvas id="tr-competency-chart"></canvas>
                </div>
            </div>
            
            <!-- Charts Row 3 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-area text-green-500 mr-2"></i>Training Cost vs Budget
                    </h3>
                    <canvas id="tr-cost-budget-chart"></canvas>
                </div>
                
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-certificate text-orange-500 mr-2"></i>Certifications Earned (Monthly)
                    </h3>
                    <canvas id="tr-certs-chart"></canvas>
                </div>
            </div>
            
            <!-- Training Programs Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Training Programs Summary</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendees</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tr-programs-table">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Department Training Performance Table -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Department Training Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Participation %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Competency Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certifications</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tr-dept-table">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Load training data
    loadTrainingData();
}

/**
 * Load Training Data
 */
async function loadTrainingData() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/training-development`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary cards
            document.getElementById('tr-participation').textContent = 
                `${safeToFixed(data.overview?.participation_rate, 1)}%`;
            document.getElementById('tr-avg-hours').textContent = 
                `${safeToFixed(data.overview?.avg_training_hours, 1)} hrs`;
            document.getElementById('tr-cost').textContent = 
                `₱${safeNumber(data.overview?.total_cost).toLocaleString('en-PH')}`;
            document.getElementById('tr-competency').textContent = 
                `${safeToFixed(data.overview?.competency_score, 1)}`;
            
            // Populate tables
            populateTrainingProgramsTable(data.training_data || []);
            populateTrainingDeptTable(data.department_performance || []);
            
            // Load charts
            loadTrainingCharts(data);
        }
    } catch (error) {
        console.error('Error loading training data:', error);
    }
}

/**
 * Populate Training Programs Table
 */
function populateTrainingProgramsTable(programs) {
    const tbody = document.getElementById('tr-programs-table');
    if (!tbody) return;
    
    if (programs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = programs.map(program => {
        const completionColor = program.completion >= 80 ? 'text-green-600' : program.completion >= 60 ? 'text-yellow-600' : 'text-red-600';
        const statusClass = program.status === 'Completed' ? 'bg-green-100 text-green-800' : program.status === 'Ongoing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
        
        return `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${program.program}</td>
            <td class="px-6 py-4 text-sm text-blue-600">${program.attended}/${program.invited}</td>
            <td class="px-6 py-4 text-sm ${completionColor} font-semibold">${program.completion}%</td>
            <td class="px-6 py-4 text-sm text-purple-600">${program.avg_score}/100</td>
            <td class="px-6 py-4 text-sm text-orange-600">₱${program.cost.toLocaleString()}</td>
            <td class="px-6 py-4 text-sm">
                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">${program.status}</span>
            </td>
        </tr>
    `}).join('');
}

/**
 * Populate Training Department Table
 */
function populateTrainingDeptTable(departments) {
    const tbody = document.getElementById('tr-dept-table');
    if (!tbody) return;
    
    if (departments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = departments.map(dept => {
        const participationColor = dept.participation >= 75 ? 'text-green-600' : dept.participation >= 50 ? 'text-yellow-600' : 'text-red-600';
        
        return `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${dept.department}</td>
            <td class="px-6 py-4 text-sm ${participationColor} font-semibold">${dept.participation}%</td>
            <td class="px-6 py-4 text-sm text-teal-600">${dept.avg_hours} hrs</td>
            <td class="px-6 py-4 text-sm text-amber-600 font-semibold">${dept.competency_score}</td>
            <td class="px-6 py-4 text-sm text-indigo-600">${dept.certifications}</td>
        </tr>
    `}).join('');
}

/**
 * Load Training Charts
 */
function loadTrainingCharts(data) {
    loadTrAttendanceChart(data.attendance_trend || []);
    loadTrTypesChart(data.training_types || []);
    loadTrDeptHoursChart(data.department_hours || []);
    loadTrCompetencyChart(data.department_competency || []);
    loadTrCostBudgetChart(data.cost_vs_budget || []);
    loadTrCertsChart(data.certifications_trend || []);
}

/**
 * Training Attendance Trend Chart
 */
function loadTrAttendanceChart(attendanceData) {
    const ctx = document.getElementById('tr-attendance-chart');
    if (!ctx) return;
    
    if (chartInstances['trAttendance']) chartInstances['trAttendance'].destroy();
    
    chartInstances['trAttendance'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: attendanceData.map(a => a.month),
            datasets: [{
                label: 'Participants',
                data: attendanceData.map(a => a.participants),
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Completions',
                data: attendanceData.map(a => a.completions),
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 10 }
                }
            }
        }
    });
}

/**
 * Training Type Distribution Chart
 */
function loadTrTypesChart(types) {
    const ctx = document.getElementById('tr-types-chart');
    if (!ctx) return;
    
    if (chartInstances['trTypes']) chartInstances['trTypes'].destroy();
    
    chartInstances['trTypes'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: types.map(t => t.training_type),
            datasets: [{
                data: types.map(t => t.count),
                backgroundColor: [
                    'rgba(20, 184, 166, 0.8)',
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Department Training Hours Chart
 */
function loadTrDeptHoursChart(deptData) {
    const ctx = document.getElementById('tr-dept-hours-chart');
    if (!ctx) return;
    
    if (chartInstances['trDeptHours']) chartInstances['trDeptHours'].destroy();
    
    chartInstances['trDeptHours'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: deptData.map(d => d.department),
            datasets: [{
                label: 'Total Hours',
                data: deptData.map(d => d.total_hours),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderColor: 'rgba(168, 85, 247, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.parsed.y} hours`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 50 }
                }
            }
        }
    });
}

/**
 * Competency Score by Department Chart (Radar)
 */
function loadTrCompetencyChart(competencyData) {
    const ctx = document.getElementById('tr-competency-chart');
    if (!ctx) return;
    
    if (chartInstances['trCompetency']) chartInstances['trCompetency'].destroy();
    
    chartInstances['trCompetency'] = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: competencyData.map(c => c.department),
            datasets: [{
                label: 'Pre-Training Score',
                data: competencyData.map(c => c.pre_score),
                backgroundColor: 'rgba(249, 115, 22, 0.2)',
                borderColor: 'rgba(249, 115, 22, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(249, 115, 22, 1)',
                pointBorderColor: '#fff',
                pointRadius: 4
            }, {
                label: 'Post-Training Score',
                data: competencyData.map(c => c.post_score),
                backgroundColor: 'rgba(34, 197, 94, 0.2)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                pointBorderColor: '#fff',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { stepSize: 20 }
                }
            }
        }
    });
}

/**
 * Training Cost vs Budget Chart
 */
function loadTrCostBudgetChart(costData) {
    const ctx = document.getElementById('tr-cost-budget-chart');
    if (!ctx) return;
    
    if (chartInstances['trCostBudget']) chartInstances['trCostBudget'].destroy();
    
    chartInstances['trCostBudget'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: costData.map(c => c.month),
            datasets: [{
                label: 'Budget',
                data: costData.map(c => c.budget),
                borderColor: 'rgba(168, 85, 247, 1)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4
            }, {
                label: 'Actual Cost',
                data: costData.map(c => c.actual_cost),
                borderColor: 'rgba(34, 197, 94, 1)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ₱${context.parsed.y.toLocaleString()}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `₱${(value / 1000).toFixed(0)}K`
                    }
                }
            }
        }
    });
}

/**
 * Certifications Earned Chart (Monthly)
 */
function loadTrCertsChart(certsData) {
    const ctx = document.getElementById('tr-certs-chart');
    if (!ctx) return;
    
    if (chartInstances['trCerts']) chartInstances['trCerts'].destroy();
    
    chartInstances['trCerts'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: certsData.map(c => c.month),
            datasets: [{
                label: 'Certifications',
                data: certsData.map(c => c.cert_count),
                backgroundColor: 'rgba(249, 115, 22, 0.2)',
                borderColor: 'rgba(249, 115, 22, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Certifications: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 5 }
                }
            }
        }
    });
}

// ========================================================================
// EXPORT FUNCTIONALITY
// ========================================================================

function exportDashboard() {
    const exportMenu = document.createElement('div');
    exportMenu.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    exportMenu.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Export Dashboard</h3>
            <div class="space-y-3">
                <button onclick="window.exportToPDF()" class="w-full px-4 py-3 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors flex items-center justify-center">
                    <i class="fas fa-file-pdf mr-2"></i>Export as PDF
                </button>
                <button onclick="window.exportToExcel()" class="w-full px-4 py-3 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i>Export as Excel
                </button>
                <button onclick="window.exportToCSV()" class="w-full px-4 py-3 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors flex items-center justify-center">
                    <i class="fas fa-file-csv mr-2"></i>Export as CSV
                </button>
                <button onclick="this.closest('.fixed').remove()" class="w-full px-4 py-3 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(exportMenu);
}

// Global export functions
window.exportToPDF = function() {
    alert('PDF export functionality will be implemented with jsPDF library');
    document.querySelector('.fixed.inset-0').remove();
};

window.exportToExcel = function() {
    alert('Excel export functionality will be implemented with SheetJS library');
    document.querySelector('.fixed.inset-0').remove();
};

window.exportToCSV = function() {
    alert('CSV export functionality will be implemented');
    document.querySelector('.fixed.inset-0').remove();
};

// ========================================================================
// REPORTS AND METRICS VIEWS (Unchanged from previous version)
// ========================================================================

// ========================================================================
// REPORTS MODULE - Comprehensive HR Analytics Reports
// ========================================================================

export async function displayAnalyticsReportsSection() {
    console.log('[Analytics] Loading Reports Module...');
    
    if (!initializeElements()) return;
    
    pageTitleElement.textContent = 'HR Analytics Reports';
    cleanupCharts();
    
    // Get departments for filters
    const departments = await fetchDepartments();
    const deptOptions = departments.map(d => 
        `<option value="${d.DepartmentID}">${d.DepartmentName}</option>`
    ).join('');
    
    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Report Selection and Filters -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>Generate HR Report
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Report Type -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-file-alt mr-1"></i>Report Type
                        </label>
                        <select id="report-type-select" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Report Type --</option>
                            <option value="demographics">📋 Employee Demographics Report</option>
                            <option value="recruitment">🧾 Recruitment & Application Report</option>
                            <option value="payroll">💰 Payroll & Compensation Report</option>
                            <option value="attendance">⏰ Attendance & Leave Report</option>
                            <option value="benefits">🩺 Benefits & HMO Utilization Report</option>
                            <option value="training">🎓 Training & Development Report</option>
                            <option value="relations">❤️ Employee Relations & Engagement Report</option>
                            <option value="turnover">🚪 Turnover & Retention Report</option>
                            <option value="compliance">🧾 Compliance & Document Report</option>
                            <option value="executive">📊 Executive / Management Summary</option>
                        </select>
                    </div>
                    
                    <!-- Department Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-building mr-1"></i>Department
                        </label>
                        <select id="report-dept-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Departments</option>
                            ${deptOptions}
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt mr-1"></i>Date Range
                        </label>
                        <select id="report-date-range" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="1month">Last Month</option>
                            <option value="3months">Last 3 Months</option>
                            <option value="6months">Last 6 Months</option>
                            <option value="12months" selected>Last 12 Months</option>
                            <option value="ytd">Year to Date</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                </div>
                
                <!-- Custom Date Range (Hidden by default) -->
                <div id="custom-date-range" class="grid grid-cols-2 gap-4 mb-4 hidden">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" id="report-from-date" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" id="report-to-date" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button id="generate-report-btn" class="px-6 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-play mr-2"></i>Generate Report
                    </button>
                    <button id="export-pdf-btn" class="px-6 py-2.5 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors font-medium" disabled>
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <button id="export-excel-btn" class="px-6 py-2.5 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors font-medium" disabled>
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                    <button id="export-csv-btn" class="px-6 py-2.5 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors font-medium" disabled>
                        <i class="fas fa-file-csv mr-2"></i>Export CSV
                    </button>
                    <button id="schedule-report-btn" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors font-medium">
                        <i class="fas fa-clock mr-2"></i>Schedule Report
                    </button>
                </div>
            </div>
            
            <!-- Report Content Area -->
            <div id="report-content-area" class="bg-white rounded-lg shadow-md border border-gray-200 p-6 hidden">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800" id="report-title">Report Preview</h3>
                    <div class="text-sm text-gray-500" id="report-metadata">
                        Generated: <span id="report-timestamp"></span>
                    </div>
                </div>
                <div id="report-visualization"></div>
            </div>
            
            <!-- Recent Reports -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-history mr-2"></i>Recent Reports
                    </h3>
                    <button id="clear-history-btn" class="text-sm text-red-600 hover:text-red-700">
                        <i class="fas fa-trash mr-1"></i>Clear History
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameters</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="recent-reports-tbody">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No reports generated yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Scheduled Reports -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-calendar-alt mr-2"></i>Scheduled Reports
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Run</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="scheduled-reports-tbody">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No scheduled reports
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Setup event listeners
    setupReportsEventListeners();
    
    // Load scheduled reports
    loadScheduledReports();
}

/**
 * Setup Reports event listeners
 */
function setupReportsEventListeners() {
    // Date range selector
    const dateRangeSelect = document.getElementById('report-date-range');
    const customDateRange = document.getElementById('custom-date-range');
    if (dateRangeSelect && customDateRange) {
        dateRangeSelect.addEventListener('change', (e) => {
            if (e.target.value === 'custom') {
                customDateRange.classList.remove('hidden');
            } else {
                customDateRange.classList.add('hidden');
            }
        });
    }
    
    // Generate Report button
    const generateBtn = document.getElementById('generate-report-btn');
    if (generateBtn) {
        generateBtn.addEventListener('click', async () => {
            const reportType = document.getElementById('report-type-select').value;
            if (!reportType) {
                alert('Please select a report type');
                return;
            }
            
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            
            await generateReport(reportType);
            
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-play mr-2"></i>Generate Report';
            
            // Enable export buttons
            document.getElementById('export-pdf-btn').disabled = false;
            document.getElementById('export-excel-btn').disabled = false;
            document.getElementById('export-csv-btn').disabled = false;
        });
    }
    
    // Export buttons
    document.getElementById('export-pdf-btn')?.addEventListener('click', () => exportReport('pdf'));
    document.getElementById('export-excel-btn')?.addEventListener('click', () => exportReport('excel'));
    document.getElementById('export-csv-btn')?.addEventListener('click', () => exportReport('csv'));
    
    // Schedule Report button
    document.getElementById('schedule-report-btn')?.addEventListener('click', () => showScheduleModal());
    
    // Clear History button
    document.getElementById('clear-history-btn')?.addEventListener('click', () => clearReportHistory());
}

/**
 * Generate Report based on selected type
 */
async function generateReport(reportType) {
    const deptId = document.getElementById('report-dept-filter').value;
    const dateRange = document.getElementById('report-date-range').value;
    
    // Show report content area
    const reportArea = document.getElementById('report-content-area');
    const reportTitle = document.getElementById('report-title');
    const reportTimestamp = document.getElementById('report-timestamp');
    const reportVisualization = document.getElementById('report-visualization');
    
    if (reportArea) reportArea.classList.remove('hidden');
    if (reportTimestamp) reportTimestamp.textContent = new Date().toLocaleString();
    
    // Set report title
    const reportTitles = {
        'demographics': 'Employee Demographics Report',
        'recruitment': 'Recruitment & Application Report',
        'payroll': 'Payroll & Compensation Report',
        'attendance': 'Attendance & Leave Report',
        'benefits': 'Benefits & HMO Utilization Report',
        'training': 'Training & Development Report',
        'relations': 'Employee Relations & Engagement Report',
        'turnover': 'Turnover & Retention Report',
        'compliance': 'Compliance & Document Report',
        'executive': 'Executive / Management Summary'
    };
    
    if (reportTitle) reportTitle.textContent = reportTitles[reportType] || 'Report';
    
    // Generate appropriate report
    try {
        let reportHTML = '';
        
        switch(reportType) {
            case 'demographics':
                reportHTML = await generateDemographicsReport(deptId, dateRange);
                break;
            case 'recruitment':
                reportHTML = await generateRecruitmentReport(deptId, dateRange);
                break;
            case 'payroll':
                reportHTML = await generatePayrollReport(deptId, dateRange);
                break;
            case 'attendance':
                reportHTML = await generateAttendanceReport(deptId, dateRange);
                break;
            case 'benefits':
                reportHTML = await generateBenefitsReport(deptId, dateRange);
                break;
            case 'training':
                reportHTML = await generateTrainingReport(deptId, dateRange);
                break;
            case 'relations':
                reportHTML = await generateRelationsReport(deptId, dateRange);
                break;
            case 'turnover':
                reportHTML = await generateTurnoverReport(deptId, dateRange);
                break;
            case 'compliance':
                reportHTML = await generateComplianceReport(deptId, dateRange);
                break;
            case 'executive':
                reportHTML = await generateExecutiveReport(deptId, dateRange);
                break;
        }
        
        if (reportVisualization) {
            reportVisualization.innerHTML = reportHTML;
        }
        
        // Add to recent reports
        addToRecentReports(reportTitles[reportType], deptId, dateRange);
        
        // Log report generation for audit trail
        logReportGeneration(reportType, deptId, dateRange);
        
    } catch (error) {
        console.error('Error generating report:', error);
        if (reportVisualization) {
            reportVisualization.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                    <p class="text-red-600">Error generating report. Please try again.</p>
                </div>
            `;
        }
    }
}

/**
 * Generate Demographics Report - Using Existing Reports API
 */
async function generateDemographicsReport(deptId, dateRange) {
    try {
        // Use existing Reports API for comprehensive report with export capability
        const params = new URLSearchParams({ 
            department_id: deptId || '', 
            date_range: dateRange,
            from_date: getDateFromRange(dateRange, 'from'),
            to_date: getDateFromRange(dateRange, 'to')
        });
        
        const response = await fetch(`${REST_API_URL}${API_ENDPOINTS.reports.demographics}?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Total Headcount</div>
                            <div class="text-2xl font-bold text-blue-700">${(data.overview?.total_headcount || 0).toLocaleString()}</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Average Age</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.avg_age, 1)} yrs</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Average Tenure</div>
                            <div class="text-2xl font-bold text-purple-700">${safeToFixed(data.overview?.avg_tenure_years, 1)} yrs</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Departments</div>
                            <div class="text-2xl font-bold text-orange-700">${(data.department_distribution?.length || 0)}</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Gender Distribution</h4>
                            <canvas id="gender-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Employment Type</h4>
                            <canvas id="emptype-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Age Distribution</h4>
                            <canvas id="age-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Department Headcount</h4>
                            <canvas id="dept-headcount-chart"></canvas>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating demographics report:', error);
        return '<p class="text-red-600">Error loading demographics data</p>';
    }
}

/**
 * Generate Recruitment & Application Report
 */
async function generateRecruitmentReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/recruitment-application?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Applications Received</div>
                            <div class="text-2xl font-bold text-blue-700">${(data.overview?.total_applications || 0).toLocaleString()}</div>
                            <div class="text-xs text-blue-500 mt-1">Total applicants</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Hired</div>
                            <div class="text-2xl font-bold text-green-700">${(data.overview?.total_hired || 0).toLocaleString()}</div>
                            <div class="text-xs text-green-500 mt-1">Successful hires</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Time-to-Hire</div>
                            <div class="text-2xl font-bold text-purple-700">${safeToFixed(data.overview?.avg_time_to_hire, 1)} days</div>
                            <div class="text-xs text-purple-500 mt-1">Average duration</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Acceptance Rate</div>
                            <div class="text-2xl font-bold text-orange-700">${safeToFixed(data.overview?.acceptance_rate, 1)}%</div>
                            <div class="text-xs text-orange-500 mt-1">Offer success</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-sm text-red-600 font-medium">Vacancy Rate</div>
                            <div class="text-2xl font-bold text-red-700">${safeToFixed(data.overview?.vacancy_rate, 1)}%</div>
                            <div class="text-xs text-red-500 mt-1">Open positions</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Recruitment Funnel</h4>
                            <canvas id="recruitment-funnel-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Time-to-Hire by Department</h4>
                            <canvas id="time-to-hire-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Recruitment Trend</h4>
                            <canvas id="recruitment-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Source of Hire</h4>
                            <canvas id="source-of-hire-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recruitment Data Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Recent Applications by Position</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applications</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Screened</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interviewed</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offered</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hired</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg. Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.position_data || []).map(pos => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${pos.position}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${pos.applications}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${pos.screened}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${pos.interviewed}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${pos.offered}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 font-semibold">${pos.hired}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${pos.avg_time} days</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating recruitment report:', error);
        return '<p class="text-red-600">Error loading recruitment data</p>';
    }
}

/**
 * Generate Payroll & Compensation Report
 */
async function generatePayrollReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/payroll-compensation?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Total Payroll Cost</div>
                            <div class="text-2xl font-bold text-green-700">₱${(data.overview?.total_payroll || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-green-500 mt-1">Gross payroll</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Average Salary</div>
                            <div class="text-2xl font-bold text-blue-700">₱${(data.overview?.avg_salary || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-blue-500 mt-1">Per employee</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Overtime Pay</div>
                            <div class="text-2xl font-bold text-purple-700">₱${(data.overview?.total_overtime || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-purple-500 mt-1">${safeToFixed(data.overview?.ot_percentage, 1)}% of total</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Total Deductions</div>
                            <div class="text-2xl font-bold text-orange-700">₱${(data.overview?.total_deductions || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-orange-500 mt-1">${safeToFixed(data.overview?.deduction_rate, 1)}% rate</div>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <div class="text-sm text-teal-600 font-medium">Net Pay</div>
                            <div class="text-2xl font-bold text-teal-700">₱${(data.overview?.total_net_pay || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-teal-500 mt-1">Take-home pay</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Payroll Cost Trend</h4>
                            <canvas id="payroll-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Salary Grade Distribution</h4>
                            <canvas id="salary-grade-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Payroll Breakdown</h4>
                            <canvas id="payroll-breakdown-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Payroll by Department</h4>
                            <canvas id="dept-payroll-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Payroll Summary Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Department Payroll Summary</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Pay</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deductions</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Salary</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.department_data || []).map(dept => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${dept.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${dept.employees}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">₱${dept.gross_pay.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-purple-600">₱${dept.overtime.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">₱${dept.deductions.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 font-semibold">₱${dept.net_pay.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">₱${dept.avg_salary.toLocaleString()}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating payroll report:', error);
        return '<p class="text-red-600">Error loading payroll data</p>';
    }
}

/**
 * Generate Attendance & Leave Report
 */
async function generateAttendanceReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/attendance-leave?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Attendance Rate</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.attendance_rate, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Present days</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-sm text-red-600 font-medium">Absenteeism Rate</div>
                            <div class="text-2xl font-bold text-red-700">${safeToFixed(data.overview?.absenteeism_rate, 1)}%</div>
                            <div class="text-xs text-red-500 mt-1">Absent days</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Late Arrivals</div>
                            <div class="text-2xl font-bold text-orange-700">${(data.overview?.late_count || 0).toLocaleString()}</div>
                            <div class="text-xs text-orange-500 mt-1">${safeToFixed(data.overview?.late_rate, 1)}% of employees</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Overtime Hours</div>
                            <div class="text-2xl font-bold text-purple-700">${(data.overview?.total_overtime_hours || 0).toLocaleString()}</div>
                            <div class="text-xs text-purple-500 mt-1">Total OT hours</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Leave Utilization</div>
                            <div class="text-2xl font-bold text-blue-700">${safeToFixed(data.overview?.leave_utilization, 1)}%</div>
                            <div class="text-xs text-blue-500 mt-1">Of entitlement</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Daily Attendance Heatmap</h4>
                            <canvas id="attendance-heatmap-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Leave Type Usage</h4>
                            <canvas id="leave-type-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Absenteeism Trend</h4>
                            <canvas id="absenteeism-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Overtime by Department</h4>
                            <canvas id="overtime-dept-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Attendance Summary Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Department Attendance Summary</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absent Days</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late Count</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Hours</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leaves Taken</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.department_data || []).map(dept => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${dept.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${dept.employees}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 font-semibold">${dept.attendance_rate}%</td>
                                            <td class="px-4 py-3 text-sm text-red-600">${dept.absent_days}</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">${dept.late_count}</td>
                                            <td class="px-4 py-3 text-sm text-purple-600">${dept.ot_hours}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${dept.leaves_taken}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating attendance report:', error);
        return '<p class="text-red-600">Error loading attendance data</p>';
    }
}

/**
 * Generate Benefits & HMO Utilization Report
 */
async function generateBenefitsReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/benefits-hmo?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Total Benefits Cost</div>
                            <div class="text-2xl font-bold text-blue-700">₱${(data.overview?.total_benefits_cost || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-blue-500 mt-1">All benefits</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">HMO Utilization</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.hmo_utilization, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Enrolled employees</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Total Claims</div>
                            <div class="text-2xl font-bold text-purple-700">${(data.overview?.total_claims || 0).toLocaleString()}</div>
                            <div class="text-xs text-purple-500 mt-1">Filed claims</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Avg Claim Cost</div>
                            <div class="text-2xl font-bold text-orange-700">₱${(data.overview?.avg_claim_cost || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-orange-500 mt-1">Per claim</div>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <div class="text-sm text-teal-600 font-medium">Processing Time</div>
                            <div class="text-2xl font-bold text-teal-700">${safeToFixed(data.overview?.avg_processing_time, 1)} days</div>
                            <div class="text-xs text-teal-500 mt-1">Average</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Benefits Cost Distribution</h4>
                            <canvas id="benefits-cost-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Claims per HMO Provider</h4>
                            <canvas id="claims-provider-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Monthly Claims Trend</h4>
                            <canvas id="claims-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Benefit Type Utilization</h4>
                            <canvas id="benefit-type-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Benefits Utilization Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">HMO Provider Summary</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrolled</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claims Filed</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Cost</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Cost</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Processing Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.provider_data || []).map(provider => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${provider.provider}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${provider.enrolled}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${provider.claims_filed}</td>
                                            <td class="px-4 py-3 text-sm text-green-600">${provider.approved}</td>
                                            <td class="px-4 py-3 text-sm text-purple-600">₱${provider.total_cost.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">₱${provider.avg_cost.toLocaleString()}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${provider.processing_time} days</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating benefits report:', error);
        return '<p class="text-red-600">Error loading benefits data</p>';
    }
}

/**
 * Generate Training & Development Report
 */
async function generateTrainingReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/training-development?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Training Sessions</div>
                            <div class="text-2xl font-bold text-blue-700">${(data.overview?.total_sessions || 0).toLocaleString()}</div>
                            <div class="text-xs text-blue-500 mt-1">Conducted</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Participation Rate</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.participation_rate, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Employee engagement</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Total Cost</div>
                            <div class="text-2xl font-bold text-purple-700">₱${(data.overview?.total_cost || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-purple-500 mt-1">Training budget</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Cost per Employee</div>
                            <div class="text-2xl font-bold text-orange-700">₱${(data.overview?.cost_per_employee || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-orange-500 mt-1">Average</div>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <div class="text-sm text-teal-600 font-medium">Certifications</div>
                            <div class="text-2xl font-bold text-teal-700">${(data.overview?.certifications_earned || 0).toLocaleString()}</div>
                            <div class="text-xs text-teal-500 mt-1">Earned</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Training Cost per Department</h4>
                            <canvas id="training-cost-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Competency Improvement Score</h4>
                            <canvas id="competency-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Training Participation Trend</h4>
                            <canvas id="training-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Training Type Distribution</h4>
                            <canvas id="training-type-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Training Summary Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Training Attendance by Department</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Training Program</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invited</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attended</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Score</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.training_data || []).map(training => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${training.program}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${training.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${training.invited}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${training.attended}</td>
                                            <td class="px-4 py-3 text-sm text-green-600 font-semibold">${training.completion}%</td>
                                            <td class="px-4 py-3 text-sm text-purple-600">${training.avg_score}</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">₱${training.cost.toLocaleString()}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating training report:', error);
        return '<p class="text-red-600">Error loading training data</p>';
    }
}

/**
 * Generate Employee Relations & Engagement Report
 */
async function generateRelationsReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/employee-relations?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Engagement Index</div>
                            <div class="text-2xl font-bold text-purple-700">${safeToFixed(data.overview?.engagement_index, 1)}/5.0</div>
                            <div class="text-xs text-purple-500 mt-1">Overall score</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Participation Rate</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.participation_rate, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Activity engagement</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Recognition Events</div>
                            <div class="text-2xl font-bold text-blue-700">${(data.overview?.recognition_events || 0).toLocaleString()}</div>
                            <div class="text-xs text-blue-500 mt-1">This period</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Disciplinary Cases</div>
                            <div class="text-2xl font-bold text-orange-700">${(data.overview?.disciplinary_cases || 0).toLocaleString()}</div>
                            <div class="text-xs text-orange-500 mt-1">${safeToFixed(data.overview?.case_rate, 2)}% rate</div>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <div class="text-sm text-teal-600 font-medium">Avg Resolution Time</div>
                            <div class="text-2xl font-bold text-teal-700">${safeToFixed(data.overview?.avg_resolution_time, 1)} days</div>
                            <div class="text-xs text-teal-500 mt-1">Case closure</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Engagement Index Gauge</h4>
                            <canvas id="engagement-gauge-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Case Frequency Trend</h4>
                            <canvas id="case-frequency-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Recognition Events per Month</h4>
                            <canvas id="recognition-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Employee Feedback Themes</h4>
                            <canvas id="feedback-themes-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Survey Results Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Engagement Survey Results by Department</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responses</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engagement Score</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Participation %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cases</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recognitions</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.department_data || []).map(dept => {
                                        const statusColor = dept.engagement_score >= 4 ? 'text-green-600' : dept.engagement_score >= 3 ? 'text-yellow-600' : 'text-red-600';
                                        const statusIcon = dept.engagement_score >= 4 ? 'fa-smile' : dept.engagement_score >= 3 ? 'fa-meh' : 'fa-frown';
                                        return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${dept.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${dept.responses}</td>
                                            <td class="px-4 py-3 text-sm text-purple-600 font-semibold">${dept.engagement_score}/5.0</td>
                                            <td class="px-4 py-3 text-sm text-green-600">${dept.participation}%</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">${dept.cases}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${dept.recognitions}</td>
                                            <td class="px-4 py-3 text-sm ${statusColor}">
                                                <i class="fas ${statusIcon} mr-1"></i>${dept.status}
                                            </td>
                                        </tr>
                                    `}).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating relations report:', error);
        return '<p class="text-red-600">Error loading employee relations data</p>';
    }
}

/**
 * Generate Turnover & Retention Report
 */
async function generateTurnoverReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/turnover-retention?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-sm text-red-600 font-medium">Turnover Rate</div>
                            <div class="text-2xl font-bold text-red-700">${safeToFixed(data.overview?.turnover_rate, 1)}%</div>
                            <div class="text-xs text-red-500 mt-1">Annual rate</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">Retention Rate</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.retention_rate, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Staying employees</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Total Exits</div>
                            <div class="text-2xl font-bold text-orange-700">${(data.overview?.total_exits || 0).toLocaleString()}</div>
                            <div class="text-xs text-orange-500 mt-1">This period</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Voluntary Exits</div>
                            <div class="text-2xl font-bold text-purple-700">${(data.overview?.voluntary_exits || 0).toLocaleString()}</div>
                            <div class="text-xs text-purple-500 mt-1">${safeToFixed(data.overview?.voluntary_percentage, 1)}% of total</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Avg Exit Tenure</div>
                            <div class="text-2xl font-bold text-blue-700">${safeToFixed(data.overview?.avg_exit_tenure, 1)} yrs</div>
                            <div class="text-xs text-blue-500 mt-1">Years of service</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Turnover Rate Trend</h4>
                            <canvas id="turnover-trend-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Voluntary vs Involuntary</h4>
                            <canvas id="exit-type-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Top Reasons for Exit</h4>
                            <canvas id="exit-reasons-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Retention by Department</h4>
                            <canvas id="dept-retention-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Turnover Summary Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Turnover by Department</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Headcount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resignations</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terminations</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turnover %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Retention %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Tenure</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.department_data || []).map(dept => {
                                        const turnoverColor = dept.turnover < 10 ? 'text-green-600' : dept.turnover < 20 ? 'text-yellow-600' : 'text-red-600';
                                        return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${dept.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${dept.headcount}</td>
                                            <td class="px-4 py-3 text-sm text-orange-600">${dept.resignations}</td>
                                            <td class="px-4 py-3 text-sm text-red-600">${dept.terminations}</td>
                                            <td class="px-4 py-3 text-sm ${turnoverColor} font-semibold">${dept.turnover}%</td>
                                            <td class="px-4 py-3 text-sm text-green-600">${dept.retention}%</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${dept.avg_tenure} yrs</td>
                                        </tr>
                                    `}).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating turnover report:', error);
        return '<p class="text-red-600">Error loading turnover data</p>';
    }
}

/**
 * Generate Compliance & Document Report
 */
async function generateComplianceReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/compliance-documents?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-sm text-green-600 font-medium">License Compliance</div>
                            <div class="text-2xl font-bold text-green-700">${safeToFixed(data.overview?.license_compliance, 1)}%</div>
                            <div class="text-xs text-green-500 mt-1">Valid licenses</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Document Completion</div>
                            <div class="text-2xl font-bold text-blue-700">${safeToFixed(data.overview?.doc_completion, 1)}%</div>
                            <div class="text-xs text-blue-500 mt-1">Complete files</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="text-sm text-red-600 font-medium">Expiring Soon</div>
                            <div class="text-2xl font-bold text-red-700">${(data.overview?.expiring_count || 0).toLocaleString()}</div>
                            <div class="text-xs text-red-500 mt-1">Within 30 days</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="text-sm text-orange-600 font-medium">Contract Renewals</div>
                            <div class="text-2xl font-bold text-orange-700">${(data.overview?.renewal_count || 0).toLocaleString()}</div>
                            <div class="text-xs text-orange-500 mt-1">Due this month</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-sm text-purple-600 font-medium">Audit Compliance</div>
                            <div class="text-2xl font-bold text-purple-700">${safeToFixed(data.overview?.audit_compliance, 1)}%</div>
                            <div class="text-xs text-purple-500 mt-1">Overall score</div>
                        </div>
                    </div>
                    
                    <!-- Expiring Documents Alert -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-3 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-red-800 mb-2">Critical: Documents Expiring Soon</h4>
                                <p class="text-sm text-red-700 mb-3">${data.overview?.expiring_count || 0} documents require immediate attention for renewal.</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="bg-white rounded p-2">
                                        <div class="text-xs text-gray-600">Professional Licenses</div>
                                        <div class="text-lg font-bold text-red-600">${data.expiring_breakdown?.licenses || 0}</div>
                                    </div>
                                    <div class="bg-white rounded p-2">
                                        <div class="text-xs text-gray-600">Employment Contracts</div>
                                        <div class="text-lg font-bold text-orange-600">${data.expiring_breakdown?.contracts || 0}</div>
                                    </div>
                                    <div class="bg-white rounded p-2">
                                        <div class="text-xs text-gray-600">Clearances</div>
                                        <div class="text-lg font-bold text-yellow-600">${data.expiring_breakdown?.clearances || 0}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Compliance Rate by Type</h4>
                            <canvas id="compliance-rate-chart"></canvas>
                        </div>
                        <div class="bg-gray-50 border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3">Document Status Distribution</h4>
                            <canvas id="doc-status-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Expiring Documents Table -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Expiring Documents - Action Required</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.expiring_documents || []).map(doc => {
                                        const urgency = doc.days_left <= 7 ? 'bg-red-100 text-red-800' : doc.days_left <= 14 ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800';
                                        return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${doc.employee_name}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${doc.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${doc.document_type}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${doc.expiry_date}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-xs rounded-full ${urgency}">${doc.days_left} days</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">${doc.status}</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <button class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-bell mr-1"></i>Notify
                                                </button>
                                            </td>
                                        </tr>
                                    `}).join('') || '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No expiring documents</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Department Compliance Summary -->
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h4 class="font-semibold text-gray-800">Compliance Summary by Department</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Employees</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Complete Files</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion %</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiring Docs</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compliance Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${(data.department_compliance || []).map(dept => {
                                        const statusColor = dept.completion >= 90 ? 'text-green-600' : dept.completion >= 70 ? 'text-yellow-600' : 'text-red-600';
                                        const statusIcon = dept.completion >= 90 ? 'fa-check-circle' : dept.completion >= 70 ? 'fa-exclamation-circle' : 'fa-times-circle';
                                        return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${dept.department}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">${dept.total_employees}</td>
                                            <td class="px-4 py-3 text-sm text-blue-600">${dept.complete_files}</td>
                                            <td class="px-4 py-3 text-sm ${statusColor} font-semibold">${dept.completion}%</td>
                                            <td class="px-4 py-3 text-sm text-red-600">${dept.expiring}</td>
                                            <td class="px-4 py-3 text-sm ${statusColor}">
                                                <i class="fas ${statusIcon} mr-1"></i>${dept.status}
                                            </td>
                                        </tr>
                                    `}).join('') || '<tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">No data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating compliance report:', error);
        return '<p class="text-red-600">Error loading compliance data</p>';
    }
}

/**
 * Generate Executive / Management Summary Report
 */
async function generateExecutiveReport(deptId, dateRange) {
    try {
        const params = new URLSearchParams({ department_id: deptId || '', date_range: dateRange });
        const response = await fetch(`${REST_API_URL}hr-analytics/executive-summary?${params}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            return `
                <div class="space-y-6">
                    <!-- Executive Summary Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-lg p-6">
                        <h3 class="text-2xl font-bold mb-2">Executive HR Summary</h3>
                        <p class="text-blue-100">Comprehensive overview of HR performance metrics</p>
                        <div class="mt-4 text-sm text-blue-100">
                            <i class="fas fa-calendar-alt mr-2"></i>Period: ${dateRange} | 
                            <i class="fas fa-clock ml-3 mr-2"></i>Generated: ${new Date().toLocaleString()}
                        </div>
                    </div>
                    
                    <!-- Key Performance Indicators -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Headcount -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-blue-700 font-semibold">Total Headcount</div>
                                <i class="fas fa-users text-2xl text-blue-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-blue-900">${(data.kpi_metrics?.total_active_employees || 0).toLocaleString()}</div>
                            <div class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>${data.kpi_metrics?.monthly_new_hires || 0} new hires
                            </div>
                        </div>
                        
                        <!-- Turnover -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-red-700 font-semibold">Turnover Rate</div>
                                <i class="fas fa-exchange-alt text-2xl text-red-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-red-900">${(data.kpi_metrics?.turnover_rate || 0).toFixed(1)}%</div>
                            <div class="text-xs text-red-600 mt-1">
                                ${data.kpi_metrics?.monthly_exits || 0} exits this month
                            </div>
                        </div>
                        
                        <!-- Payroll Cost -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-green-700 font-semibold">Monthly Payroll</div>
                                <i class="fas fa-money-bill-wave text-2xl text-green-500"></i>
                            </div>
                            <div class="text-2xl font-bold text-green-900">₱${(data.kpi_metrics?.monthly_payroll_cost || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-green-600 mt-1">
                                Budget variance: ${(data.kpi_metrics?.budget_variance || 0).toFixed(1)}%
                            </div>
                        </div>
                        
                        <!-- Engagement -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-purple-700 font-semibold">Engagement Score</div>
                                <i class="fas fa-heart text-2xl text-purple-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-purple-900">${(data.kpi_metrics?.engagement_score || 0).toFixed(1)}/5.0</div>
                            <div class="text-xs text-purple-600 mt-1">
                                ${(data.kpi_metrics?.survey_participation || 0).toFixed(1)}% participation
                            </div>
                        </div>
                        
                        <!-- Attendance -->
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 border border-teal-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-teal-700 font-semibold">Attendance Rate</div>
                                <i class="fas fa-calendar-check text-2xl text-teal-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-teal-900">${(data.kpi_metrics?.attendance_rate || 0).toFixed(1)}%</div>
                            <div class="text-xs text-teal-600 mt-1">
                                ${(data.kpi_metrics?.overtime_hours || 0).toLocaleString()} OT hours
                            </div>
                        </div>
                        
                        <!-- Benefits -->
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-indigo-700 font-semibold">Benefits Cost</div>
                                <i class="fas fa-hand-holding-medical text-2xl text-indigo-500"></i>
                            </div>
                            <div class="text-2xl font-bold text-indigo-900">₱${(data.kpi_metrics?.monthly_benefits_cost || 0).toLocaleString('en-PH')}</div>
                            <div class="text-xs text-indigo-600 mt-1">
                                ${data.kpi_metrics?.claims_count || 0} claims processed
                            </div>
                        </div>
                        
                        <!-- Training -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-amber-700 font-semibold">Training Hours</div>
                                <i class="fas fa-graduation-cap text-2xl text-amber-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-amber-900">${(data.kpi_metrics?.avg_training_hours || 0).toFixed(1)}</div>
                            <div class="text-xs text-amber-600 mt-1">
                                Hours per employee
                            </div>
                        </div>
                        
                        <!-- Compliance -->
                        <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-rose-700 font-semibold">Compliance Index</div>
                                <i class="fas fa-clipboard-check text-2xl text-rose-500"></i>
                            </div>
                            <div class="text-3xl font-bold text-rose-900">${(data.kpi_metrics?.compliance_index || 0).toFixed(1)}%</div>
                            <div class="text-xs text-rose-600 mt-1">
                                ${data.kpi_metrics?.expiring_docs || 0} docs expiring soon
                            </div>
                        </div>
                    </div>
                    
                    <!-- Key Trends Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-semibold text-gray-800 mb-4">Workforce Trend (12 Months)</h4>
                            <canvas id="exec-workforce-chart"></canvas>
                        </div>
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-semibold text-gray-800 mb-4">Turnover Rate Trend</h4>
                            <canvas id="exec-turnover-chart"></canvas>
                        </div>
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-semibold text-gray-800 mb-4">Payroll vs Budget</h4>
                            <canvas id="exec-payroll-chart"></canvas>
                        </div>
                        <div class="bg-white border rounded-lg p-6">
                            <h4 class="font-semibold text-gray-800 mb-4">Department Headcount</h4>
                            <canvas id="exec-dept-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Alerts and Recommendations -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 mb-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Key Alerts & Recommendations
                        </h4>
                        <ul class="space-y-2">
                            ${(data.alerts || []).map(alert => `
                                <li class="flex items-start text-sm text-yellow-800">
                                    <i class="fas fa-chevron-right mr-2 mt-1"></i>
                                    <span>${alert}</span>
                                </li>
                            `).join('') || '<li class="text-sm text-gray-600">No critical alerts at this time.</li>'}
                        </ul>
                    </div>
                    
                    <!-- Action Items -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-3">
                            <i class="fas fa-tasks mr-2"></i>Recommended Actions
                        </h4>
                        <ul class="space-y-2">
                            ${(data.recommendations || []).map(rec => `
                                <li class="flex items-start text-sm text-blue-800">
                                    <i class="fas fa-check-circle mr-2 mt-1"></i>
                                    <span>${rec}</span>
                                </li>
                            `).join('') || '<li class="text-sm text-gray-600">All metrics are within acceptable ranges.</li>'}
                        </ul>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error generating executive report:', error);
        return '<p class="text-red-600">Error loading executive summary data</p>';
    }
}

/**
 * Export Report
 */
/**
 * Export Report - Using Existing Reports API Export
 */
async function exportReport(format) {
    const reportType = document.getElementById('report-type-select').value;
    if (!reportType) {
        alert('Please generate a report first');
        return;
    }
    
    const deptId = document.getElementById('report-dept-filter').value;
    const dateRange = document.getElementById('report-date-range').value;
    
    try {
        // Show loading indicator
        const exportBtn = event ? event.target : null;
        if (exportBtn) {
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
        }
        
        // Prepare export data using Existing Reports API
        const exportData = {
            report_type: reportType,
            format: format.toUpperCase(), // 'PDF', 'Excel', 'CSV'
            filters: {
                department_id: deptId || '',
                date_range: dateRange,
                from_date: getDateFromRange(dateRange, 'from'),
                to_date: getDateFromRange(dateRange, 'to')
            },
            include_charts: true,
            include_summary: true
        };
        
        const response = await fetch(`${REST_API_URL}${API_ENDPOINTS.reports.export}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(exportData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // If download URL is provided, open it
            if (result.data && result.data.download_url) {
                window.open(result.data.download_url, '_blank');
            } else if (result.data && result.data.html_content && format.toLowerCase() === 'pdf') {
                // For PDF, create a temporary window with HTML content
                const printWindow = window.open('', '_blank');
                printWindow.document.write(result.data.html_content);
                printWindow.document.close();
                printWindow.print();
            } else if (result.data && result.data.csv_data && format.toLowerCase() === 'csv') {
                // For CSV, trigger download
                downloadCSV(result.data.csv_data, `${reportType}_report_${new Date().toISOString().split('T')[0]}.csv`);
            } else {
                alert('Export successful! Check your downloads folder.');
            }
        } else {
            throw new Error(result.message || 'Export failed');
        }
        
    } catch (error) {
        console.error('Error exporting report:', error);
        alert('Failed to export report. Please try again.');
    } finally {
        // Restore button
        if (exportBtn) {
            exportBtn.disabled = false;
            exportBtn.innerHTML = `<i class="fas fa-file-${format === 'pdf' ? 'pdf' : format === 'excel' ? 'excel' : 'csv'} mr-2"></i>Export ${format.toUpperCase()}`;
        }
    }
}

/**
 * Download CSV helper function
 */
function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Show Schedule Modal - Using Existing Reports API
 */
function showScheduleModal() {
    const reportType = document.getElementById('report-type-select').value;
    if (!reportType) {
        alert('Please select a report type first');
        return;
    }
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Schedule Report</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                    <select id="schedule-frequency" class="w-full p-2 border rounded-md">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                    <select id="schedule-format" class="w-full p-2 border rounded-md">
                        <option value="PDF">PDF</option>
                        <option value="Excel">Excel</option>
                        <option value="CSV">CSV</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Recipients</label>
                    <input type="text" id="schedule-recipients" placeholder="email@example.com, email2@example.com" class="w-full p-2 border rounded-md">
                    <p class="text-xs text-gray-500 mt-1">Separate multiple emails with commas</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send Time</label>
                    <input type="time" id="schedule-time" value="08:00" class="w-full p-2 border rounded-md">
                </div>
                <div class="flex gap-2">
                    <button onclick="this.closest('.fixed').remove()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button id="confirm-schedule-btn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Schedule</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Add event listener for schedule button
    document.getElementById('confirm-schedule-btn').addEventListener('click', async () => {
        const frequency = document.getElementById('schedule-frequency').value;
        const format = document.getElementById('schedule-format').value;
        const recipients = document.getElementById('schedule-recipients').value;
        const sendTime = document.getElementById('schedule-time').value;
        
        if (!recipients) {
            alert('Please enter at least one email recipient');
            return;
        }
        
        await scheduleReport(reportType, frequency, format, recipients, sendTime);
        modal.remove();
    });
}

/**
 * Schedule Report - Using Existing Reports API
 */
async function scheduleReport(reportType, frequency, format, recipients, sendTime) {
    try {
        const deptId = document.getElementById('report-dept-filter').value;
        const dateRange = document.getElementById('report-date-range').value;
        
        const scheduleData = {
            report_type: reportType,
            frequency: frequency,
            format: format,
            recipients: recipients.split(',').map(email => email.trim()),
            send_time: sendTime,
            filters: {
                department_id: deptId || '',
                date_range: dateRange,
                from_date: getDateFromRange(dateRange, 'from'),
                to_date: getDateFromRange(dateRange, 'to')
            },
            created_by: window.currentUser?.id || 'system',
            created_at: new Date().toISOString()
        };
        
        const response = await fetch(`${REST_API_URL}${API_ENDPOINTS.reports.schedule}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(scheduleData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Report scheduled successfully!');
            // Reload scheduled reports list
            loadScheduledReports();
        } else {
            throw new Error(result.message || 'Failed to schedule report');
        }
        
    } catch (error) {
        console.error('Error scheduling report:', error);
        alert('Failed to schedule report. Please try again.');
    }
}

/**
 * Add to recent reports
 */
function addToRecentReports(reportName, deptId, dateRange) {
    const tbody = document.getElementById('recent-reports-tbody');
    if (!tbody) return;
    
    // Remove "no reports" message
    if (tbody.children.length === 1 && tbody.children[0].children.length === 1) {
        tbody.innerHTML = '';
    }
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${reportName}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Dept: ${deptId || 'All'}, Range: ${dateRange}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${window.currentUser?.name || 'Admin'}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date().toLocaleString()}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">
            <i class="fas fa-download mr-2"></i>Download
        </td>
    `;
    tbody.insertBefore(row, tbody.firstChild);
    
    // Keep only last 10 reports
    while (tbody.children.length > 10) {
        tbody.removeChild(tbody.lastChild);
    }
}

/**
 * Load scheduled reports - Using Existing Reports API
 */
async function loadScheduledReports() {
    try {
        const response = await fetch(`${REST_API_URL}${API_ENDPOINTS.reports.scheduled}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            populateScheduledReportsTable(result.data);
        }
    } catch (error) {
        console.error('Error loading scheduled reports:', error);
    }
}

/**
 * Populate Scheduled Reports Table
 */
function populateScheduledReportsTable(scheduledReports) {
    const tbody = document.getElementById('scheduled-reports-tbody');
    if (!tbody) return;
    
    if (!scheduledReports || scheduledReports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No scheduled reports</td></tr>';
        return;
    }
    
    tbody.innerHTML = scheduledReports.map(schedule => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${schedule.report_name || schedule.report_type}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${schedule.frequency}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${schedule.format}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${schedule.next_run || 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="deleteScheduledReport(${schedule.id})" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Delete Scheduled Report
 */
async function deleteScheduledReport(scheduleId) {
    if (!confirm('Are you sure you want to delete this scheduled report?')) {
        return;
    }
    
    try {
        const response = await fetch(`${REST_API_URL}${API_ENDPOINTS.reports.scheduled}/${scheduleId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Scheduled report deleted successfully');
            loadScheduledReports(); // Reload list
        } else {
            throw new Error(result.message || 'Failed to delete scheduled report');
        }
    } catch (error) {
        console.error('Error deleting scheduled report:', error);
        alert('Failed to delete scheduled report');
    }
}

/**
 * Clear report history
 */
function clearReportHistory() {
    if (confirm('Are you sure you want to clear all report history?')) {
        const tbody = document.getElementById('recent-reports-tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No reports generated yet</td></tr>';
        }
    }
}

/**
 * Log report generation for audit trail
 */
async function logReportGeneration(reportType, deptId, dateRange) {
    try {
        await fetch(`${REST_API_URL}hr-analytics/log-report`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                report_type: reportType,
                department_id: deptId,
                date_range: dateRange,
                generated_by: window.currentUser?.id || 'system',
                generated_at: new Date().toISOString()
            })
        });
    } catch (error) {
        console.error('Error logging report generation:', error);
    }
}

// ========================================================================
// METRICS MODULE - HR Analytics Metrics Framework
// ========================================================================

export async function displayAnalyticsMetricsSection() {
    console.log('[Analytics] Loading Metrics Framework...');
    
    if (!initializeElements()) return;
    
    pageTitleElement.textContent = 'HR Analytics Metrics Framework';
    cleanupCharts();
    
    // Get departments for filters
    const departments = await fetchDepartments();
    const deptOptions = departments.map(d => 
        `<option value="${d.DepartmentID}">${d.DepartmentName}</option>`
    ).join('');
    
    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Global Filters -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-building mr-1"></i>Department
                        </label>
                        <select id="metrics-dept-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Departments</option>
                            ${deptOptions}
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt mr-1"></i>Time Period
                        </label>
                        <select id="metrics-period-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="current">Current Month</option>
                            <option value="quarter" selected>Current Quarter</option>
                            <option value="ytd">Year to Date</option>
                            <option value="12months">Last 12 Months</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-layer-group mr-1"></i>Metric Category
                        </label>
                        <select id="metrics-category-filter" class="w-full p-2.5 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Categories</option>
                            <option value="demographics">1️⃣ Demographics</option>
                            <option value="recruitment">2️⃣ Recruitment</option>
                            <option value="payroll">3️⃣ Payroll & Compensation</option>
                            <option value="attendance">4️⃣ Attendance & Leave</option>
                            <option value="benefits">5️⃣ Benefits & HMO</option>
                            <option value="training">6️⃣ Training & Development</option>
                            <option value="relations">7️⃣ Employee Relations</option>
                            <option value="turnover">8️⃣ Turnover & Retention</option>
                            <option value="compliance">9️⃣ Compliance & Audit</option>
                            <option value="executive">🔟 Executive KPIs</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button id="apply-metrics-filter-btn" class="px-6 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium">
                            <i class="fas fa-filter mr-2"></i>Apply
                        </button>
                        <button id="refresh-metrics-btn" class="px-6 py-2.5 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors font-medium">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button id="export-metrics-btn" class="px-6 py-2.5 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Metrics Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="border-b border-gray-200 overflow-x-auto">
                    <nav class="flex -mb-px" id="metrics-tabs">
                        <button class="metrics-tab active px-4 py-3 text-sm font-medium border-b-2 border-blue-500 text-blue-600 whitespace-nowrap" data-category="overview">
                            <i class="fas fa-th-large mr-1"></i>Overview
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="demographics">
                            <i class="fas fa-users mr-1"></i>Demographics
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="recruitment">
                            <i class="fas fa-user-plus mr-1"></i>Recruitment
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="payroll">
                            <i class="fas fa-money-bill mr-1"></i>Payroll
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="attendance">
                            <i class="fas fa-calendar-check mr-1"></i>Attendance
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="benefits">
                            <i class="fas fa-hand-holding-medical mr-1"></i>Benefits
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="training">
                            <i class="fas fa-graduation-cap mr-1"></i>Training
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="relations">
                            <i class="fas fa-heart mr-1"></i>Relations
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="turnover">
                            <i class="fas fa-door-open mr-1"></i>Turnover
                        </button>
                        <button class="metrics-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-category="compliance">
                            <i class="fas fa-clipboard-check mr-1"></i>Compliance
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div id="metrics-tab-content" class="p-6">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    `;
    
    // Setup tab navigation
    setupMetricsTabNavigation();
    
    // Load default tab (Overview)
    loadMetricsOverview();
    
    // Setup event listeners
    setupMetricsEventListeners();
}

/**
 * Setup metrics tab navigation
 */
function setupMetricsTabNavigation() {
    const tabs = document.querySelectorAll('.metrics-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active state
            tabs.forEach(t => {
                t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            tab.classList.add('active', 'border-blue-500', 'text-blue-600');
            tab.classList.remove('border-transparent', 'text-gray-500');
            
            // Load tab content
            const category = tab.dataset.category;
            loadMetricsCategory(category);
        });
    });
}

/**
 * Setup metrics event listeners
 */
function setupMetricsEventListeners() {
    // Apply Filter button
    document.getElementById('apply-metrics-filter-btn')?.addEventListener('click', () => {
        const activeTab = document.querySelector('.metrics-tab.active');
        const category = activeTab?.dataset.category || 'overview';
        loadMetricsCategory(category);
    });
    
    // Refresh button
    document.getElementById('refresh-metrics-btn')?.addEventListener('click', () => {
        const activeTab = document.querySelector('.metrics-tab.active');
        const category = activeTab?.dataset.category || 'overview';
        loadMetricsCategory(category);
    });
    
    // Export button
    document.getElementById('export-metrics-btn')?.addEventListener('click', () => {
        alert('Exporting metrics data...');
    });
}

/**
 * Load metrics category
 */
function loadMetricsCategory(category) {
    cleanupCharts();
    
    switch(category) {
        case 'overview':
            loadMetricsOverview();
            break;
        case 'demographics':
            loadDemographicsMetrics();
            break;
        case 'recruitment':
            loadRecruitmentMetrics();
            break;
        case 'payroll':
            loadPayrollMetrics();
            break;
        case 'attendance':
            loadAttendanceMetrics();
            break;
        case 'benefits':
            loadBenefitsMetrics();
            break;
        case 'training':
            loadTrainingMetrics();
            break;
        case 'relations':
            loadRelationsMetrics();
            break;
        case 'turnover':
            loadTurnoverMetrics();
            break;
        case 'compliance':
            loadComplianceMetrics();
            break;
    }
}

/**
 * OVERVIEW TAB - All Key Metrics Summary
 */
async function loadMetricsOverview() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-800">Key Performance Indicators - Overview</h3>
            
            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Headcount -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-blue-700 font-semibold">Total Headcount</div>
                        <i class="fas fa-users text-2xl text-blue-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900" id="metric-total-headcount">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-blue-600 mt-1" id="metric-headcount-trend">Loading...</div>
                </div>
                
                <!-- Turnover Rate -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-red-700 font-semibold">Turnover Rate (YTD)</div>
                        <i class="fas fa-exchange-alt text-2xl text-red-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-red-900" id="metric-turnover-rate">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-red-600 mt-1">Annual: <span id="metric-turnover-annual">--</span></div>
                </div>
                
                <!-- Total Payroll Cost -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-green-700 font-semibold">Total Payroll Cost</div>
                        <i class="fas fa-money-bill-wave text-2xl text-green-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-green-900" id="metric-payroll-cost">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-green-600 mt-1">vs Budget: <span id="metric-payroll-budget">--</span></div>
                </div>
                
                <!-- Engagement Score -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-purple-700 font-semibold">Engagement Score</div>
                        <i class="fas fa-heart text-2xl text-purple-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-purple-900" id="metric-engagement-score">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-purple-600 mt-1">Survey responses: <span id="metric-engagement-responses">--</span></div>
                </div>
                
                <!-- Attendance Rate -->
                <div class="bg-gradient-to-br from-teal-50 to-teal-100 border border-teal-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-teal-700 font-semibold">Attendance Rate</div>
                        <i class="fas fa-calendar-check text-2xl text-teal-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-teal-900" id="metric-attendance-rate">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-teal-600 mt-1">Absenteeism: <span id="metric-absenteeism">--</span></div>
                </div>
                
                <!-- Benefits Utilization -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-indigo-700 font-semibold">Benefits Utilization</div>
                        <i class="fas fa-hand-holding-medical text-2xl text-indigo-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-indigo-900" id="metric-benefits-util">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-indigo-600 mt-1">Claims: <span id="metric-benefits-claims">--</span></div>
                </div>
                
                <!-- Training Participation -->
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-amber-700 font-semibold">Training Participation</div>
                        <i class="fas fa-graduation-cap text-2xl text-amber-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-amber-900" id="metric-training-participation">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-amber-600 mt-1">Avg hours: <span id="metric-training-hours">--</span></div>
                </div>
                
                <!-- Compliance Index -->
                <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-rose-700 font-semibold">Compliance Index</div>
                        <i class="fas fa-clipboard-check text-2xl text-rose-500"></i>
                    </div>
                    <div class="text-3xl font-bold text-rose-900" id="metric-compliance-index">
                        <i class="fas fa-spinner fa-spin text-xl"></i>
                    </div>
                    <div class="text-xs text-rose-600 mt-1">Expiring docs: <span id="metric-compliance-expiring">--</span></div>
                </div>
            </div>
            
            <!-- Metrics Comparison Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <h4 class="font-semibold text-gray-800">All Metrics Summary</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Previous Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="metrics-summary-tbody">
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading metrics...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Key Trend Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Headcount Trend</h4>
                    <canvas id="overview-headcount-chart"></canvas>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Turnover Trend (YTD)</h4>
                    <canvas id="overview-turnover-chart"></canvas>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Total Payroll vs Budget</h4>
                    <canvas id="overview-payroll-chart"></canvas>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Benefit Utilization Trend</h4>
                    <canvas id="overview-benefits-chart"></canvas>
                </div>
            </div>
        </div>
    `;
    
    // Load all overview metrics
    await loadOverviewMetricsData();
}

/**
 * Load Overview Metrics Data
 */
async function loadOverviewMetricsData() {
    try {
        const response = await fetch(`${REST_API_URL}hr-analytics/metrics-overview`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update KPI cards (with null checks for DOM elements)
            const updateElement = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            };
            const updateElementHTML = (id, html) => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = html;
            };
            
            updateElement('metric-total-headcount', (data.total_headcount || 0).toLocaleString());
            updateElementHTML('metric-headcount-trend', `<i class="fas fa-arrow-up"></i> +${data.headcount_change || 0} this month`);
            
            updateElement('metric-turnover-rate', `${(data.turnover_rate || 0).toFixed(1)}%`);
            updateElement('metric-turnover-annual', `${(data.annual_turnover || 0).toFixed(1)}%`);
            
            updateElement('metric-payroll-cost', `₱${(data.payroll_cost || 0).toLocaleString('en-PH')}`);
            updateElement('metric-payroll-budget', `${(data.budget_variance || 0).toFixed(1)}%`);
            
            updateElement('metric-engagement-score', `${(data.engagement_score || 0).toFixed(1)}`);
            updateElement('metric-engagement-responses', (data.survey_responses || 0).toLocaleString());
            
            updateElement('metric-attendance-rate', `${(data.attendance_rate || 0).toFixed(1)}%`);
            updateElement('metric-absenteeism', `${(data.absenteeism_rate || 0).toFixed(1)}%`);
            
            updateElement('metric-benefits-util', `${(data.benefits_utilization || 0).toFixed(1)}%`);
            updateElement('metric-benefits-claims', (data.total_claims || 0).toLocaleString());
            
            updateElement('metric-training-participation', `${(data.training_participation || 0).toFixed(1)}%`);
            updateElement('metric-training-hours', `${(data.avg_training_hours || 0).toFixed(1)} hrs`);
            
            updateElement('metric-compliance-index', `${(data.compliance_index || 0).toFixed(1)}%`);
            updateElement('metric-compliance-expiring', (data.expiring_docs || 0).toLocaleString());
            
            // Populate metrics table
            populateMetricsSummaryTable(data.all_metrics || []);
        }
    } catch (error) {
        console.error('Error loading overview metrics:', error);
    }
}

/**
 * Populate Metrics Summary Table
 */
function populateMetricsSummaryTable(metrics) {
    const tbody = document.getElementById('metrics-summary-tbody');
    if (!tbody) return;
    
    if (metrics.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No metrics data available</td></tr>';
        return;
    }
    
    tbody.innerHTML = metrics.map(metric => {
        const change = ((metric.current - metric.previous) / metric.previous * 100) || 0;
        const trendIcon = change > 0 ? '<i class="fas fa-arrow-up text-green-500"></i>' : change < 0 ? '<i class="fas fa-arrow-down text-red-500"></i>' : '<i class="fas fa-minus text-gray-400"></i>';
        const statusColor = metric.status === 'good' ? 'text-green-600' : metric.status === 'warning' ? 'text-yellow-600' : 'text-red-600';
        const statusIcon = metric.status === 'good' ? 'fa-check-circle' : metric.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle';
        
        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${metric.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">${metric.current_display || metric.current}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${metric.previous_display || metric.previous}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ${change >= 0 ? 'text-green-600' : 'text-red-600'}">${change.toFixed(1)}%</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${trendIcon}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ${statusColor}">
                    <i class="fas ${statusIcon} mr-1"></i>${metric.status}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${metric.category}</td>
            </tr>
        `;
    }).join('');
}

/**
 * Load Demographics Metrics
 */
async function loadDemographicsMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    
    tabContent.innerHTML = `
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-800">1️⃣ Employee Demographics Metrics</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Headcount by Department -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Headcount by Department</h4>
                    <canvas id="demo-dept-chart"></canvas>
                </div>
                
                <!-- Gender Ratio -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Gender Ratio</h4>
                    <canvas id="demo-gender-chart"></canvas>
                </div>
                
                <!-- Average Age -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Average Age Distribution</h4>
                    <canvas id="demo-age-chart"></canvas>
                </div>
                
                <!-- Employment Type Ratio -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="font-semibold text-gray-800 mb-4">Employment Type Distribution</h4>
                    <canvas id="demo-emptype-chart"></canvas>
                </div>
            </div>
            
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-sm text-blue-700 font-semibold">Total Headcount</div>
                    <div class="text-2xl font-bold text-blue-900">0</div>
                    <div class="text-xs text-blue-600">Active employees</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-sm text-green-700 font-semibold">Average Age</div>
                    <div class="text-2xl font-bold text-green-900">0 yrs</div>
                    <div class="text-xs text-green-600">Workforce age</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-sm text-purple-700 font-semibold">Average Tenure</div>
                    <div class="text-2xl font-bold text-purple-900">0 yrs</div>
                    <div class="text-xs text-purple-600">Employee loyalty</div>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="text-sm text-orange-700 font-semibold">Gender Diversity</div>
                    <div class="text-2xl font-bold text-orange-900">50/50</div>
                    <div class="text-xs text-orange-600">Male/Female ratio</div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Load other metric categories (simplified for now)
 */
async function loadRecruitmentMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-user-plus text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">2️⃣ Recruitment Metrics</h3><p class="text-gray-500">Detailed recruitment metrics loading...</p></div>`;
}

async function loadPayrollMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-money-bill-wave text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">3️⃣ Payroll & Compensation Metrics</h3><p class="text-gray-500">Detailed payroll metrics loading...</p></div>`;
}

async function loadAttendanceMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">4️⃣ Attendance & Leave Metrics</h3><p class="text-gray-500">Detailed attendance metrics loading...</p></div>`;
}

async function loadBenefitsMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-hand-holding-medical text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">5️⃣ Benefits & HMO Metrics</h3><p class="text-gray-500">Detailed benefits metrics loading...</p></div>`;
}

async function loadTrainingMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-graduation-cap text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">6️⃣ Training & Development Metrics</h3><p class="text-gray-500">Detailed training metrics loading...</p></div>`;
}

async function loadRelationsMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-heart text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">7️⃣ Employee Relations & Engagement Metrics</h3><p class="text-gray-500">Detailed relations metrics loading...</p></div>`;
}

async function loadTurnoverMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-door-open text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">8️⃣ Turnover & Retention Metrics</h3><p class="text-gray-500">Detailed turnover metrics loading...</p></div>`;
}

async function loadComplianceMetrics() {
    const tabContent = document.getElementById('metrics-tab-content');
    if (!tabContent) return;
    tabContent.innerHTML = `<div class="text-center py-12"><i class="fas fa-clipboard-check text-6xl text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-700 mb-2">9️⃣ Compliance & Audit Metrics</h3><p class="text-gray-500">Detailed compliance metrics loading...</p></div>`;
}
