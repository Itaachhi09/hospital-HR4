/**
 * Advanced HR Analytics Dashboard
 * Comprehensive workforce, payroll, and benefits analytics with interactive visualizations
 * Integrates with Chart.js for data visualization
 */

import { API_BASE_URL } from '../utils.js';

// Chart instances for cleanup
let chartInstances = {};

/**
 * Display comprehensive HR Analytics Dashboard
 */
export async function displayHRAnalyticsDashboard() {
    const container = document.getElementById('main-content-area');
    const pageTitle = document.getElementById('page-title');
    
    if (!container || !pageTitle) return;
    
    pageTitle.textContent = 'HR Analytics Dashboard';
    
    // Render dashboard layout
    container.innerHTML = `
        <div class="space-y-6">
            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select id="dept-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                        <select id="period-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="6">Last 6 Months</option>
                            <option value="12" selected>Last 12 Months</option>
                            <option value="24">Last 24 Months</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select id="branch-filter" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="">All Branches</option>
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

            <!-- Overview KPI Cards -->
            <div id="overview-kpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="kpi-card-skeleton animate-pulse bg-gray-200 h-32 rounded-lg"></div>
                <div class="kpi-card-skeleton animate-pulse bg-gray-200 h-32 rounded-lg"></div>
                <div class="kpi-card-skeleton animate-pulse bg-gray-200 h-32 rounded-lg"></div>
                <div class="kpi-card-skeleton animate-pulse bg-gray-200 h-32 rounded-lg"></div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-4 px-4" id="analytics-tabs">
                        <button class="analytics-tab active py-4 px-3 border-b-2 border-[#594423] font-medium text-sm text-[#594423]" data-tab="overview">
                            <i class="fas fa-tachometer-alt mr-2"></i>Overview
                        </button>
                        <button class="analytics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="workforce">
                            <i class="fas fa-users mr-2"></i>Workforce
                        </button>
                        <button class="analytics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="payroll">
                            <i class="fas fa-money-bill-wave mr-2"></i>Payroll
                        </button>
                        <button class="analytics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="benefits">
                            <i class="fas fa-briefcase-medical mr-2"></i>Benefits
                        </button>
                        <button class="analytics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="training">
                            <i class="fas fa-graduation-cap mr-2"></i>Training
                        </button>
                        <button class="analytics-tab py-4 px-3 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="attendance">
                            <i class="fas fa-calendar-check mr-2"></i>Attendance
                        </button>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div id="analytics-tab-content" class="p-6">
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x text-gray-400 mb-4"></i>
                            <p class="text-gray-500">Loading analytics data...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="bg-white p-4 rounded-lg shadow-md border border-[#F7E6CA]">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#4E3B2A]">
                        <i class="fas fa-download mr-2"></i>Export Reports
                    </h3>
                    <div class="flex gap-2">
                        <button id="export-pdf-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                        <button id="export-excel-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </button>
                        <button id="export-csv-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-file-csv mr-2"></i>CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Initialize event listeners
    initializeEventListeners();
    
    // Load initial data
    await loadDashboardData();
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Tab switching
    document.querySelectorAll('.analytics-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });

    // Filter application
    document.getElementById('apply-filters-btn')?.addEventListener('click', async () => {
        await loadDashboardData();
    });

    // Refresh button
    document.getElementById('refresh-dashboard-btn')?.addEventListener('click', async () => {
        await loadDashboardData();
    });

    // Export buttons
    document.getElementById('export-pdf-btn')?.addEventListener('click', () => exportData('PDF'));
    document.getElementById('export-excel-btn')?.addEventListener('click', () => exportData('Excel'));
    document.getElementById('export-csv-btn')?.addEventListener('click', () => exportData('CSV'));
}

/**
 * Load dashboard data from API
 */
async function loadDashboardData() {
    try {
        // Get filters
        const filters = getFilters();
        const queryString = new URLSearchParams(filters).toString();
        
        // Fetch dashboard data
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-analytics/dashboard?${queryString}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load analytics');

        const data = result.data;

        // Render overview KPIs
        renderOverviewKPIs(data.overview);

        // Render active tab content
        const activeTab = document.querySelector('.analytics-tab.active')?.dataset.tab || 'overview';
        renderTabContent(activeTab, data);

    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showError('Failed to load analytics data. Please try again.');
    }
}

/**
 * Get current filter values
 */
function getFilters() {
    return {
        department_id: document.getElementById('dept-filter')?.value || '',
        branch_id: document.getElementById('branch-filter')?.value || '',
        months: document.getElementById('period-filter')?.value || '12'
    };
}

/**
 * Render overview KPI cards
 */
function renderOverviewKPIs(overview) {
    const container = document.getElementById('overview-kpis');
    if (!container) return;

    container.innerHTML = `
        <!-- Total Active Employees -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Total Active Employees</p>
                    <p class="text-3xl font-bold mt-2">${formatNumber(overview.total_active_employees || 0)}</p>
                    <p class="text-xs mt-1 opacity-75">
                        <span class="text-green-300">+${overview.monthly_new_hires || 0}</span> new hires this month
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Turnover Rate -->
        <div class="bg-gradient-to-br from-red-500 to-red-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Annual Turnover Rate</p>
                    <p class="text-3xl font-bold mt-2">${formatNumber(overview.annual_turnover_rate || 0)}%</p>
                    <p class="text-xs mt-1 opacity-75">
                        ${overview.monthly_separations || 0} separations this month
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-exchange-alt fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Monthly Payroll Cost -->
        <div class="bg-gradient-to-br from-green-500 to-green-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Monthly Payroll Cost</p>
                    <p class="text-3xl font-bold mt-2">₱${formatNumber(overview.total_monthly_payroll_cost || 0, 2)}</p>
                    <p class="text-xs mt-1 opacity-75">
                        Avg: ₱${formatNumber(overview.avg_salary || 0, 2)} per employee
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- HMO Benefits Cost -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">HMO Benefits Cost</p>
                    <p class="text-3xl font-bold mt-2">₱${formatNumber(overview.total_monthly_hmo_cost || 0, 2)}</p>
                    <p class="text-xs mt-1 opacity-75">
                        ${overview.active_hmo_enrollments || 0} active enrollments
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-briefcase-medical fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Average Tenure -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Avg Employee Tenure</p>
                    <p class="text-3xl font-bold mt-2">${formatNumber(overview.avg_employee_tenure_years || 0, 1)} yrs</p>
                    <p class="text-xs mt-1 opacity-75">
                        Retention is key to success
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-user-clock fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Attendance Rate -->
        <div class="bg-gradient-to-br from-teal-500 to-teal-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Attendance Rate</p>
                    <p class="text-3xl font-bold mt-2">${formatNumber(100 - (overview.absenteeism_rate || 0), 1)}%</p>
                    <p class="text-xs mt-1 opacity-75">
                        ${formatNumber(overview.absenteeism_rate || 0, 1)}% absenteeism
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-calendar-check fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Pending Leave Requests -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Pending Leave Requests</p>
                    <p class="text-3xl font-bold mt-2">${overview.pending_leave_requests || 0}</p>
                    <p class="text-xs mt-1 opacity-75">
                        Requires approval
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-clipboard-list fa-2x"></i>
                </div>
            </div>
        </div>

        <!-- Training Completion -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider opacity-90">Training Completion</p>
                    <p class="text-3xl font-bold mt-2">${formatNumber(overview.avg_training_completion_rate || 0, 1)}%</p>
                    <p class="text-xs mt-1 opacity-75">
                        ${overview.trainings_this_year || 0} trainings this year
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-full">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                </div>
            </div>
        </div>
    `;
}

/**
 * Switch between tabs
 */
async function switchTab(tabName) {
    // Update active tab
    document.querySelectorAll('.analytics-tab').forEach(tab => {
        tab.classList.remove('active', 'border-[#594423]', 'text-[#594423]');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    const activeTab = document.querySelector(`.analytics-tab[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('active', 'border-[#594423]', 'text-[#594423]');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }

    // Show loading state
    const contentContainer = document.getElementById('analytics-tab-content');
    if (!contentContainer) return;

    contentContainer.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x text-gray-400 mb-4"></i>
                <p class="text-gray-500">Loading ${tabName} analytics...</p>
            </div>
        </div>
    `;

    // Load tab-specific data
    try {
        const filters = getFilters();
        const queryString = new URLSearchParams(filters).toString();
        
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-analytics/${tabName}?${queryString}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load analytics');

        renderTabContent(tabName, { [tabName]: result.data });

    } catch (error) {
        console.error(`Error loading ${tabName} tab:`, error);
        contentContainer.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle fa-3x text-red-500 mb-4"></i>
                <p class="text-red-600">Failed to load ${tabName} analytics</p>
                <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-[#594423] text-white rounded-md">
                    Retry
                </button>
            </div>
        `;
    }
}

/**
 * Render tab content based on selected tab
 */
function renderTabContent(tabName, data) {
    const contentContainer = document.getElementById('analytics-tab-content');
    if (!contentContainer) return;

    // Clean up existing charts
    Object.values(chartInstances).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    });
    chartInstances = {};

    switch (tabName) {
        case 'overview':
            renderOverviewTab(contentContainer, data);
            break;
        case 'workforce':
            renderWorkforceTab(contentContainer, data.workforce);
            break;
        case 'payroll':
            renderPayrollTab(contentContainer, data.payroll);
            break;
        case 'benefits':
            renderBenefitsTab(contentContainer, data.benefits);
            break;
        case 'training':
            renderTrainingTab(contentContainer, data.training);
            break;
        case 'attendance':
            renderAttendanceTab(contentContainer, data.attendance);
            break;
        default:
            renderOverviewTab(contentContainer, data);
    }
}

/**
 * Render Overview Tab
 */
function renderOverviewTab(container, data) {
    container.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Headcount Trend -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Headcount Trend</h3>
                <canvas id="headcount-trend-chart" height="250"></canvas>
            </div>

            <!-- Department Distribution -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Headcount by Department</h3>
                <canvas id="dept-distribution-chart" height="250"></canvas>
            </div>

            <!-- Payroll Cost Trend -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Payroll Cost Trend</h3>
                <canvas id="payroll-trend-chart" height="250"></canvas>
            </div>

            <!-- Turnover Analysis -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Turnover by Department</h3>
                <canvas id="turnover-dept-chart" height="250"></canvas>
            </div>
        </div>
    `;

    // Render charts
    if (data.workforce?.headcount_trend) {
        renderLineChart('headcount-trend-chart', data.workforce.headcount_trend, 'month_name', 'headcount', 'Headcount');
    }

    if (data.workforce?.headcount_by_department) {
        renderBarChart('dept-distribution-chart', data.workforce.headcount_by_department, 'department_name', 'headcount', 'Employees');
    }

    if (data.payroll?.cost_trend) {
        renderLineChart('payroll-trend-chart', data.payroll.cost_trend, 'month_name', 'total_gross', 'Cost (₱)', true);
    }

    if (data.workforce?.turnover_by_department) {
        renderBarChart('turnover-dept-chart', data.workforce.turnover_by_department, 'department_name', 'turnover_rate', 'Turnover %');
    }
}

/**
 * Render Workforce Tab
 */
function renderWorkforceTab(container, workforce) {
    if (!workforce) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">No workforce data available</p>';
        return;
    }

    container.innerHTML = `
        <div class="space-y-6">
            <!-- Headcount Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Headcount by Employment Type -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Employment Type Distribution</h3>
                    <canvas id="employment-type-chart" height="200"></canvas>
                </div>

                <!-- Gender Distribution -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Gender Distribution</h3>
                    <canvas id="gender-distribution-chart" height="200"></canvas>
                </div>

                <!-- Age Distribution -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Age Distribution</h3>
                    <canvas id="age-distribution-chart" height="200"></canvas>
                </div>
            </div>

            <!-- Hiring Trends -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- New Hires Trend -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">New Hires Trend</h3>
                    <canvas id="new-hires-trend-chart" height="250"></canvas>
                </div>

                <!-- Separations Trend -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Separations Trend</h3>
                    <canvas id="separations-trend-chart" height="250"></canvas>
                </div>
            </div>
        </div>
    `;

    // Render charts
    if (workforce.headcount_by_employment_type) {
        renderPieChart('employment-type-chart', workforce.headcount_by_employment_type, 'employment_type', 'headcount');
    }

    if (workforce.gender_distribution) {
        renderPieChart('gender-distribution-chart', workforce.gender_distribution, 'gender', 'count');
    }

    if (workforce.age_distribution) {
        renderBarChart('age-distribution-chart', workforce.age_distribution, 'age_range', 'count', 'Employees');
    }

    if (workforce.new_hires_trend) {
        renderLineChart('new-hires-trend-chart', workforce.new_hires_trend, 'month_name', 'new_hires', 'New Hires');
    }

    if (workforce.separations_trend) {
        renderLineChart('separations-trend-chart', workforce.separations_trend, 'month_name', 'separations', 'Separations');
    }
}

/**
 * Render Payroll Tab
 */
function renderPayrollTab(container, payroll) {
    if (!payroll) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">No payroll data available</p>';
        return;
    }

    container.innerHTML = `
        <div class="space-y-6">
            <!-- Cost Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cost by Department -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Payroll Cost by Department</h3>
                    <canvas id="payroll-dept-chart" height="300"></canvas>
                </div>

                <!-- Cost Composition -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Cost Composition</h3>
                    <canvas id="cost-composition-chart" height="300"></canvas>
                </div>
            </div>

            <!-- Deductions & Bonuses -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Deduction Breakdown -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Deduction Breakdown</h3>
                    <canvas id="deduction-breakdown-chart" height="300"></canvas>
                </div>

                <!-- Bonus Analysis -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Bonus Distribution</h3>
                    <canvas id="bonus-analysis-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    `;

    // Render charts
    if (payroll.cost_by_department) {
        renderBarChart('payroll-dept-chart', payroll.cost_by_department, 'department_name', 'total_base_salary', 'Cost (₱)', true);
    }

    if (payroll.cost_composition) {
        renderPieChart('cost-composition-chart', payroll.cost_composition, 'component', 'amount', true);
    }

    if (payroll.deduction_breakdown) {
        renderBarChart('deduction-breakdown-chart', payroll.deduction_breakdown, 'DeductionType', 'total_amount', 'Amount (₱)', true);
    }

    if (payroll.bonus_analysis) {
        renderBarChart('bonus-analysis-chart', payroll.bonus_analysis, 'BonusType', 'total_amount', 'Amount (₱)', true);
    }
}

/**
 * Render Benefits Tab
 */
function renderBenefitsTab(container, benefits) {
    if (!benefits) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">No benefits data available</p>';
        return;
    }

    container.innerHTML = `
        <div class="space-y-6">
            <!-- HMO Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-400 to-blue-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Active Enrollments</p>
                    <p class="text-2xl font-bold">${benefits.hmo_overview?.active_enrollments || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-green-400 to-green-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Pending Claims</p>
                    <p class="text-2xl font-bold">${benefits.hmo_overview?.pending_claims || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-400 to-purple-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Monthly HMO Cost</p>
                    <p class="text-2xl font-bold">₱${formatNumber(benefits.hmo_overview?.monthly_premium_cost || 0, 2)}</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Avg Processing Time</p>
                    <p class="text-2xl font-bold">${formatNumber(benefits.hmo_overview?.avg_processing_days || 0)} days</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- HMO by Department -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">HMO Cost by Department</h3>
                    <canvas id="hmo-dept-chart" height="300"></canvas>
                </div>

                <!-- Provider Utilization -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Plan Utilization</h3>
                    <canvas id="provider-util-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    `;

    // Render charts
    if (benefits.hmo_by_department) {
        renderBarChart('hmo-dept-chart', benefits.hmo_by_department, 'DepartmentName', 'monthly_premium_cost', 'Cost (₱)', true);
    }

    if (benefits.provider_utilization) {
        renderBarChart('provider-util-chart', benefits.provider_utilization, 'PlanName', 'enrollment_count', 'Enrollments');
    }
}

/**
 * Render Training Tab
 */
function renderTrainingTab(container, training) {
    if (!training || !training.training_completion) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">No training data available</p>';
        return;
    }

    const completion = training.training_completion;
    
    container.innerHTML = `
        <div class="space-y-6">
            <!-- Training Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-400 to-indigo-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Total Trainings</p>
                    <p class="text-2xl font-bold">${completion.total_trainings || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-teal-400 to-teal-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Employees Trained</p>
                    <p class="text-2xl font-bold">${completion.employees_trained || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-pink-400 to-pink-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Completion Rate</p>
                    <p class="text-2xl font-bold">${formatNumber(completion.completion_rate || 0, 1)}%</p>
                </div>
                <div class="bg-gradient-to-br from-orange-400 to-orange-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Total Hours</p>
                    <p class="text-2xl font-bold">${formatNumber(completion.total_training_hours || 0)}</p>
                </div>
            </div>

            <!-- Training Hours by Department -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Training Hours by Department</h3>
                <canvas id="training-dept-chart" height="300"></canvas>
            </div>
        </div>
    `;

    // Render chart
    if (training.training_hours_by_department) {
        renderBarChart('training-dept-chart', training.training_hours_by_department, 'department_name', 'total_hours', 'Hours');
    }
}

/**
 * Render Attendance Tab
 */
function renderAttendanceTab(container, attendance) {
    if (!attendance) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">No attendance data available</p>';
        return;
    }

    const rate = attendance.attendance_rate || {};
    
    container.innerHTML = `
        <div class="space-y-6">
            <!-- Attendance Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-green-400 to-green-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Attendance Rate</p>
                    <p class="text-2xl font-bold">${formatNumber(rate.attendance_rate || 0, 1)}%</p>
                </div>
                <div class="bg-gradient-to-br from-red-400 to-red-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Absenteeism Rate</p>
                    <p class="text-2xl font-bold">${formatNumber(rate.absenteeism_rate || 0, 1)}%</p>
                </div>
                <div class="bg-gradient-to-br from-blue-400 to-blue-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Present Days</p>
                    <p class="text-2xl font-bold">${rate.present_count || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 p-4 rounded-lg text-white">
                    <p class="text-sm opacity-90">Absent Days</p>
                    <p class="text-2xl font-bold">${rate.absent_count || 0}</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Absenteeism Trend -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Absenteeism Trend</h3>
                    <canvas id="absenteeism-trend-chart" height="300"></canvas>
                </div>

                <!-- Attendance by Department -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Attendance by Department</h3>
                    <canvas id="attendance-dept-chart" height="300"></canvas>
                </div>
            </div>

            <!-- Leave Utilization -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Leave Utilization</h3>
                <canvas id="leave-util-chart" height="300"></canvas>
            </div>
        </div>
    `;

    // Render charts
    if (attendance.absenteeism_trend) {
        renderLineChart('absenteeism-trend-chart', attendance.absenteeism_trend, 'month_name', 'absenteeism_rate', 'Absenteeism %');
    }

    if (attendance.attendance_by_department) {
        renderBarChart('attendance-dept-chart', attendance.attendance_by_department, 'department_name', 'attendance_rate', 'Attendance %');
    }

    if (attendance.leave_utilization) {
        renderBarChart('leave-util-chart', attendance.leave_utilization, 'LeaveTypeName', 'total_days_used', 'Days');
    }
}

// =====================================================
// CHART RENDERING FUNCTIONS
// =====================================================

/**
 * Render line chart
 */
function renderLineChart(canvasId, data, labelKey, valueKey, label, isCurrency = false) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !data) return;

    const labels = data.map(item => item[labelKey]);
    const values = data.map(item => parseFloat(item[valueKey]) || 0);

    chartInstances[canvasId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                borderColor: 'rgb(89, 68, 35)',
                backgroundColor: 'rgba(89, 68, 35, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            if (isCurrency) {
                                return label + ': ₱' + formatNumber(value, 2);
                            }
                            return label + ': ' + formatNumber(value);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (isCurrency) {
                                return '₱' + formatNumber(value);
                            }
                            return formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Render bar chart
 */
function renderBarChart(canvasId, data, labelKey, valueKey, label, isCurrency = false) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !data) return;

    const labels = data.map(item => item[labelKey]);
    const values = data.map(item => parseFloat(item[valueKey]) || 0);

    const colors = labels.map((_, i) => `hsl(${(i * 360 / labels.length)}, 70%, 60%)`);

    chartInstances[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                backgroundColor: colors,
                borderColor: colors.map(c => c.replace('60%)', '50%)')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            if (isCurrency) {
                                return label + ': ₱' + formatNumber(value, 2);
                            }
                            return label + ': ' + formatNumber(value);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (isCurrency) {
                                return '₱' + formatNumber(value);
                            }
                            return formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Render pie chart
 */
function renderPieChart(canvasId, data, labelKey, valueKey, isCurrency = false) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !data) return;

    const labels = data.map(item => item[labelKey]);
    const values = data.map(item => parseFloat(item[valueKey]) || 0);

    const colors = labels.map((_, i) => `hsl(${(i * 360 / labels.length)}, 70%, 60%)`);

    chartInstances[canvasId] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed;
                            let label = context.label;
                            if (isCurrency) {
                                return label + ': ₱' + formatNumber(value, 2);
                            }
                            return label + ': ' + formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

// =====================================================
// EXPORT FUNCTIONS
// =====================================================

/**
 * Export data in various formats
 */
async function exportData(format) {
    const filters = getFilters();
    const activeTab = document.querySelector('.analytics-tab.active')?.dataset.tab || 'overview';

    try {
        const endpoint = format === 'PDF' ? 'export-pdf' : format === 'Excel' ? 'export-excel' : 'export-csv';
        
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/hr-analytics/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                report_type: activeTab,
                filters: filters
            })
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        
        if (format === 'CSV' && result.success && result.data.csv_data) {
            // Download CSV
            const blob = new Blob([result.data.csv_data], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `hr_analytics_${activeTab}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showSuccess(`${format} report downloaded successfully`);
        } else if (format === 'PDF' && result.success && result.data.html_content) {
            // Download HTML content that can be printed to PDF
            const blob = new Blob([result.data.html_content], { type: 'text/html' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = result.data.filename || `hr_analytics_${activeTab}_${new Date().toISOString().split('T')[0]}.html`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showSuccess(`${format} report downloaded successfully (HTML format - use browser print to PDF)`);
        } else {
            showSuccess(`${format} export prepared successfully`);
        }

    } catch (error) {
        console.error('Error exporting data:', error);
        showError(`Failed to export ${format} report`);
    }
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Format number with commas and decimals
 */
function formatNumber(value, decimals = 0) {
    const num = parseFloat(value) || 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Show success message
 */
function showSuccess(message) {
    // TODO: Implement toast notification
    alert(message);
}

/**
 * Show error message
 */
function showError(message) {
    // TODO: Implement toast notification
    alert(message);
}

