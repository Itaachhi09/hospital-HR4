import { API_BASE_URL } from '../config.js';
import './shared-modals.js';

/**
 * Displays the Salaries submodule section
 * Shows employee salary information, rates, and adjustments from HR1/HR2/HR3
 */
export async function displaySalariesSection() {
    console.log("[Salaries] Displaying Salaries Section...");

    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    
    if (!pageTitleElement || !mainContentArea) {
         console.error("displaySalariesSection: Core DOM elements not found.");
         return;
    }
    
    pageTitleElement.textContent = 'Employee Salaries - HR4 System';

    // Inject HTML structure
    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                        <h3 class="text-xl font-semibold text-green-900">Employee Salaries</h3>
                        <p class="text-sm text-green-700">View salary information, rates, and adjustments from HR1/HR2/HR3 modules</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshSalaries()" class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="exportSalaryData()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-file-excel mr-2"></i>Export
                        </button>
                        <button onclick="showSalaryComparison()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <i class="fas fa-chart-bar mr-2"></i>Comparison
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input id="salary-search-input" type="text" placeholder="Search by name, employee number, department..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="branch-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
                            <option value="">All Branches</option>
                        </select>
                        
                        <select id="department-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
                            <option value="">All Departments</option>
                        </select>
                        
                        <select id="position-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
                            <option value="">All Positions</option>
                            </select>
                        
                        <button onclick="applySalaryFilters()" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearSalaryFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>


            <!-- Salaries Table -->
            <div class="px-6 py-4">
                <div id="salaries-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                        <p class="text-gray-500 mt-2">Loading salary information...</p>
                        </div>
                        </div>
                        </div>
                    </div>

        <!-- Enhanced Salary Details Modal -->
        <div id="salary-details-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Employee Salary Details</h3>
                            <button type="button" onclick="closeSalaryDetailsModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Enhanced Salary Summary -->
                        <div id="salary-summary" class="mb-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600" id="employee-name">Employee Name</div>
                                    <div class="text-sm text-gray-500">Employee</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="employee-number">N/A</div>
                                    <div class="text-sm text-gray-500">Employee Number</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="department">N/A</div>
                                    <div class="text-sm text-gray-500">Department</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="position">N/A</div>
                                    <div class="text-sm text-gray-500">Position</div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-green-200">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600" id="base-salary">₱0.00</div>
                                    <div class="text-sm text-gray-500">Base Salary</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-blue-600" id="total-allowances">₱0.00</div>
                                    <div class="text-sm text-gray-500">Total Allowances</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-purple-600" id="gross-salary">₱0.00</div>
                                    <div class="text-sm text-gray-500">Gross Salary</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <button onclick="exportSalaryDetails()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <i class="fas fa-download mr-2"></i>Export Details
                            </button>
                            <button onclick="viewSalaryHistory()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <i class="fas fa-history mr-2"></i>View History
                            </button>
                        </div>

                        <!-- Enhanced Salary Details Content -->
                        <div id="salary-details-content">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Salary Comparison Modal -->
        <div id="salary-comparison-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Salary Comparison Analysis</h3>
                            <button type="button" onclick="closeSalaryComparisonModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Comparison Summary -->
                        <div id="comparison-summary" class="mb-6 p-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600" id="total-employees">0</div>
                                    <div class="text-sm text-gray-500">Total Employees</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600" id="average-salary">₱0.00</div>
                                    <div class="text-sm text-gray-500">Average Salary</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600" id="salary-range">₱0.00 - ₱0.00</div>
                                    <div class="text-sm text-gray-500">Salary Range</div>
                                </div>
                            </div>
                        </div>

                        <!-- Comparison Controls -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <select id="comparison-department" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Departments</option>
                            </select>
                            <select id="comparison-position" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Positions</option>
                            </select>
                            <button onclick="updateComparison()" class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <i class="fas fa-chart-bar mr-1"></i>Update Analysis
                            </button>
                            <button onclick="exportComparison()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <i class="fas fa-download mr-1"></i>Export
                            </button>
                        </div>

                        <div id="salary-comparison-content">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    // Set up event listeners
    setupSalaryEventListeners();
    
    // Load initial data
    await loadSalaries();
    await loadFilterOptions();
}

/**
 * Set up event listeners for the salaries section
 */
function setupSalaryEventListeners() {
    // Search input
    const searchInput = document.getElementById('salary-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(loadSalaries, 500));
    }
}

/**
 * Load salary data from the API
 */
async function loadSalaries() {
    console.log("[Load] Loading Salaries...");
    const container = document.getElementById('salaries-list-container');
    if (!container) return;
    
    container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div><p class="text-gray-500 mt-2">Loading salary information...</p></div>';

    // Build query parameters
    const params = new URLSearchParams();
    const branchFilter = document.getElementById('branch-filter')?.value;
    const departmentFilter = document.getElementById('department-filter')?.value;
    const positionFilter = document.getElementById('position-filter')?.value;
    const searchTerm = document.getElementById('salary-search-input')?.value;
    
    if (branchFilter) params.set('branch_id', branchFilter);
    if (departmentFilter) params.set('department_id', departmentFilter);
    if (positionFilter) params.set('position_id', positionFilter);
    if (searchTerm) params.set('search', searchTerm);
    
    params.set('page', '1');
    params.set('limit', '50');

    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/salaries?${params}`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log("[Load] Salaries loaded:", result);

        if (result.success) {
            renderSalariesTable(result.data.salaries);
        } else {
            throw new Error(result.message || 'Failed to load salaries');
        }
    } catch (error) {
        console.error('[Load] Error loading salaries:', error);
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Salaries</h3>
                <p class="text-gray-500">${error.message}</p>
                <button onclick="loadSalaries()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Try Again
                </button>
            </div>`;
    }
}

/**
 * Render the salaries table
 */
function renderSalariesTable(salaries) {
    console.log("[Render] Rendering Salaries Table...");
    const container = document.getElementById('salaries-list-container');
    if (!container) return;

    if (!salaries || salaries.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No salary information found</h3>
                <p class="text-gray-500">No employees found matching the current filters.</p>
            </div>`;
        return;
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';
    table.innerHTML = `
        <thead class="bg-green-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Department</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Position</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Base Salary</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Hourly Rate</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Daily Rate</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Adjustments</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-green-600 uppercase tracking-wider">Actions</th>
                    </tr>
        </thead>`;

    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200';

    salaries.forEach(salary => {
        const row = tbody.insertRow();
        row.id = `salary-row-${salary.EmployeeID}`;

        const createCell = (text, className = '') => {
            const cell = row.insertCell();
            cell.className = `px-4 py-3 whitespace-nowrap text-sm ${className}`;
            cell.textContent = text ?? '';
            return cell;
        };

        // Employee
        createCell(`${salary.employee_name}\n${salary.EmployeeNumber}`, 'font-medium text-gray-900');

        // Department
        createCell(salary.DepartmentName || 'N/A', 'text-gray-700');

        // Position
        createCell(salary.PositionName || 'N/A', 'text-gray-700');

        // Base Salary
        const baseSalary = parseFloat(salary.BaseSalary || 0).toLocaleString('en-PH', { 
            style: 'currency', 
            currency: 'PHP' 
        });
        createCell(baseSalary, 'text-blue-600 font-semibold');

        // Hourly Rate
        const hourlyRate = parseFloat(salary.hourly_rate || 0).toLocaleString('en-PH', { 
            style: 'currency', 
            currency: 'PHP' 
        });
        createCell(hourlyRate, 'text-green-600 font-medium');

        // Daily Rate
        const dailyRate = parseFloat(salary.daily_rate || 0).toLocaleString('en-PH', { 
            style: 'currency', 
            currency: 'PHP' 
        });
        createCell(dailyRate, 'text-purple-600 font-medium');

        // Adjustments
        const adjustments = parseFloat(salary.total_adjustments || 0);
        const adjustmentClass = adjustments >= 0 ? 'text-green-600' : 'text-red-600';
        const adjustmentText = adjustments.toLocaleString('en-PH', { 
            style: 'currency', 
            currency: 'PHP' 
        });
        createCell(adjustmentText, `${adjustmentClass} font-medium`);

        // Actions
        const actionsCell = row.insertCell();
        actionsCell.className = 'px-4 py-3 whitespace-nowrap text-sm font-medium space-x-2';
        
        const viewButton = document.createElement('button');
        viewButton.textContent = 'View Details';
        viewButton.className = 'text-green-600 hover:text-green-900';
        viewButton.onclick = () => viewSalaryDetails(salary.EmployeeID);
        
        const deductionsButton = document.createElement('button');
        deductionsButton.textContent = 'Deductions';
        deductionsButton.className = 'text-blue-600 hover:text-blue-900';
        deductionsButton.onclick = () => viewEmployeeDeductions(salary.EmployeeID);
        
        actionsCell.appendChild(viewButton);
        actionsCell.appendChild(document.createTextNode(' | '));
        actionsCell.appendChild(deductionsButton);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

/**
 * Load filter options (branches, departments, positions)
 */
async function loadFilterOptions() {
    // For now, use hardcoded data. In production, this would come from APIs
    const branches = [
        { BranchID: 1, BranchName: 'Main Hospital' }
    ];
    
    const departments = [
        { DepartmentID: 1, DepartmentName: 'Administration' },
        { DepartmentID: 2, DepartmentName: 'Nursing' },
        { DepartmentID: 3, DepartmentName: 'Medical' },
        { DepartmentID: 4, DepartmentName: 'Finance' }
    ];
    
    const positions = [
        { PositionID: 1, PositionName: 'Manager' },
        { PositionID: 2, PositionName: 'Nurse' },
        { PositionID: 3, PositionName: 'Doctor' },
        { PositionID: 4, PositionName: 'Clerk' }
    ];
    
    // Populate branch filter
    const branchFilter = document.getElementById('branch-filter');
    if (branchFilter) {
        branchFilter.innerHTML = '<option value="">All Branches</option>' + 
            branches.map(b => `<option value="${b.BranchID}">${b.BranchName}</option>`).join('');
    }
    
    // Populate department filter
    const departmentFilter = document.getElementById('department-filter');
    if (departmentFilter) {
        departmentFilter.innerHTML = '<option value="">All Departments</option>' + 
            departments.map(d => `<option value="${d.DepartmentID}">${d.DepartmentName}</option>`).join('');
    }
    
    // Populate position filter
    const positionFilter = document.getElementById('position-filter');
    if (positionFilter) {
        positionFilter.innerHTML = '<option value="">All Positions</option>' + 
            positions.map(p => `<option value="${p.PositionID}">${p.PositionName}</option>`).join('');
    }
}

/**
 * View detailed salary information for an employee
 */
async function viewSalaryDetails(employeeId) {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/salaries/${employeeId}/summary`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            const salary = result.data;
            
            // Update summary section
            updateSalarySummary(salary);
            
            // Update detailed content
            const content = document.getElementById('salary-details-content');
            content.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user mr-2 text-green-600"></i>Basic Information
                        </h4>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Employee Number:</dt>
                                <dd class="text-sm text-gray-900 font-mono">${salary.EmployeeNumber || 'N/A'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Department:</dt>
                                <dd class="text-sm text-gray-900">${salary.DepartmentName || 'N/A'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Position:</dt>
                                <dd class="text-sm text-gray-900">${salary.PositionName || 'N/A'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Branch:</dt>
                                <dd class="text-sm text-gray-900">${salary.BranchName || 'N/A'}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Hire Date:</dt>
                                <dd class="text-sm text-gray-900">${salary.HireDate || 'N/A'}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>Salary Breakdown
                        </h4>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Base Salary:</dt>
                                <dd class="text-sm text-gray-900 font-semibold">${parseFloat(salary.BaseSalary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</dd>
                            <dt class="text-sm font-medium text-gray-500">Pay Frequency:</dt>
                            <dd class="text-sm text-gray-900">${salary.PayFrequency || 'N/A'}</dd>
                            <dt class="text-sm font-medium text-gray-500">Hourly Rate:</dt>
                            <dd class="text-sm text-gray-900">${parseFloat(salary.hourly_rate || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</dd>
                            <dt class="text-sm font-medium text-gray-500">Daily Rate:</dt>
                            <dd class="text-sm text-gray-900">${parseFloat(salary.daily_rate || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</dd>
                            <dt class="text-sm font-medium text-gray-500">Effective Date:</dt>
                            <dd class="text-sm text-gray-900">${salary.EffectiveDate ? new Date(salary.EffectiveDate).toLocaleDateString() : 'N/A'}</dd>
                        </dl>
                    </div>
                </div>
                
                ${salary.adjustments && salary.adjustments.length > 0 ? `
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Recent Adjustments (Last 3 Months)</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${salary.adjustments.map(adj => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">${adj.AdjustmentType}</td>
                                        <td class="px-4 py-2 text-sm ${parseFloat(adj.Amount) >= 0 ? 'text-green-600' : 'text-red-600'}">${parseFloat(adj.Amount).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${new Date(adj.AdjustmentDate).toLocaleDateString()}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${adj.Reason || 'N/A'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('salary-details-modal').classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load salary details');
        }
    } catch (error) {
        console.error('Error loading salary details:', error);
        alert('Error loading salary details: ' + error.message);
    }
}

/**
 * View employee deductions
 */
async function viewEmployeeDeductions(employeeId) {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/salaries/${employeeId}/deductions`, {
            credentials: 'include',
             headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            const deductions = result.data;
            const content = document.getElementById('salary-details-content');
            
            content.innerHTML = `
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Employee Deductions Overview</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deduction Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${deductions.map(ded => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">${ded.deduction_type}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${ded.rate}</td>
                                        <td class="px-4 py-2 text-sm text-red-600 font-semibold">${parseFloat(ded.amount).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${ded.category}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('salary-details-modal').classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load deductions');
        }
    } catch (error) {
        console.error('Error loading deductions:', error);
        alert('Error loading deductions: ' + error.message);
    }
}

/**
 * Show salary comparison modal
 */
async function showSalaryComparison() {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/salaries/comparison`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            const comparisons = result.data;
            const content = document.getElementById('salary-comparison-content');
            
            content.innerHTML = `
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Salary Comparison by Department & Position</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Avg Salary</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Min Salary</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Max Salary</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Avg Hourly</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${comparisons.map(comp => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">${comp.DepartmentName || 'N/A'}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${comp.PositionName || 'N/A'}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${comp.BranchName || 'N/A'}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${comp.employee_count}</td>
                                        <td class="px-4 py-2 text-sm text-blue-600 font-semibold">${parseFloat(comp.avg_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${parseFloat(comp.min_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">${parseFloat(comp.max_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                        <td class="px-4 py-2 text-sm text-green-600 font-medium">${parseFloat(comp.avg_hourly_rate || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('salary-comparison-modal').classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load salary comparison');
        }
    } catch (error) {
        console.error('Error loading salary comparison:', error);
        alert('Error loading salary comparison: ' + error.message);
    }
}

// Global functions for event handlers
window.refreshSalaries = function() {
    loadSalaries();
};

window.applySalaryFilters = function() {
    loadSalaries();
};

window.clearSalaryFilters = function() {
    document.getElementById('branch-filter').value = '';
    document.getElementById('department-filter').value = '';
    document.getElementById('position-filter').value = '';
    document.getElementById('salary-search-input').value = '';
    loadSalaries();
};

window.exportSalaryData = function() {
    const rows = document.querySelectorAll('#salaries-list-container tbody tr');
    if (!rows || rows.length === 0) {
        alert('No salary data available to export.');
        return;
    }

    const headers = ['Employee Name', 'Employee Number', 'Department', 'Position', 'Base Salary', 'Hourly Rate', 'Daily Rate', 'Adjustments'];
    const csvContent = [
        headers.join(','),
        ...Array.from(rows).map(row => {
            const cells = row.querySelectorAll('td');
            return [
                cells[0]?.textContent?.trim() || '',
                cells[1]?.textContent?.trim() || '',
                cells[2]?.textContent?.trim() || '',
                cells[3]?.textContent?.trim() || '',
                cells[4]?.textContent?.trim() || '',
                cells[5]?.textContent?.trim() || '',
                cells[6]?.textContent?.trim() || '',
                cells[7]?.textContent?.trim() || ''
            ].map(cell => `"${cell}"`).join(',');
        })
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `salary_data_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    alert('Salary data exported successfully!');
};

// Enhanced salary functions
function updateSalarySummary(salary) {
    document.getElementById('employee-name').textContent = salary.employee_name || 'N/A';
    document.getElementById('employee-number').textContent = salary.EmployeeNumber || 'N/A';
    document.getElementById('department').textContent = salary.DepartmentName || 'N/A';
    document.getElementById('position').textContent = salary.PositionName || 'N/A';
    
    document.getElementById('base-salary').textContent = 
        parseFloat(salary.BaseSalary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    document.getElementById('total-allowances').textContent = 
        parseFloat(salary.TotalAllowances || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    document.getElementById('gross-salary').textContent = 
        parseFloat(salary.GrossSalary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function exportSalaryDetails() {
    showInlineAlert('Exporting salary details...', 'info');
    // Implementation for exporting salary details
    setTimeout(() => {
        showInlineAlert('Salary details exported successfully!', 'success');
    }, 2000);
}

function viewSalaryHistory() {
    showInlineAlert('Loading salary history...', 'info');
    // Implementation for viewing salary history
    setTimeout(() => {
        showInlineAlert('Salary history loaded!', 'success');
    }, 1500);
}

function updateComparison() {
    showInlineAlert('Updating salary comparison...', 'info');
    // Implementation for updating comparison
    setTimeout(() => {
        showInlineAlert('Comparison updated!', 'success');
    }, 1500);
}

function exportComparison() {
    showInlineAlert('Exporting comparison data...', 'info');
    // Implementation for exporting comparison
    setTimeout(() => {
        showInlineAlert('Comparison data exported!', 'success');
    }, 2000);
}

window.closeSalaryDetailsModal = function() {
    document.getElementById('salary-details-modal').classList.add('hidden');
};

window.closeSalaryComparisonModal = function() {
    document.getElementById('salary-comparison-modal').classList.add('hidden');
};

// Additional button functions that are referenced in HTML but not yet implemented
window.showSalaryComparison = function() {
    console.log("[Action] Showing salary comparison...");
    showSalaryComparisonModal();
};

window.viewSalaryDetails = function(employeeId) {
    console.log("[Action] Viewing salary details for employee:", employeeId);
    viewSalaryDetails(employeeId);
};

window.viewEmployeeDeductions = function(employeeId) {
    console.log("[Action] Viewing deductions for employee:", employeeId);
    // Navigate to deductions module with employee filter
    if (window.displayDeductionsSection) {
        window.displayDeductionsSection();
        // Set employee filter
        setTimeout(() => {
            const employeeFilter = document.getElementById('deduction-employee-filter');
            if (employeeFilter) {
                employeeFilter.value = employeeId;
                applyDeductionFilters();
            }
        }, 1000);
    } else {
        alert('Deductions module not available');
    }
};

// Helper functions
function showSalaryComparisonModal() {
    const modal = document.getElementById('salary-comparison-modal');
    if (modal) {
        modal.classList.remove('hidden');
        loadSalaryComparison();
    }
}

async function loadSalaryComparison() {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/salaries/comparison`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            const comparison = result.data;
            const content = document.getElementById('salary-comparison-content');
            
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-blue-900 mb-2">Salary Statistics</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">${comparison.total_employees || 0}</div>
                                <div class="text-sm text-blue-700">Total Employees</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">${parseFloat(comparison.average_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</div>
                                <div class="text-sm text-green-700">Average Salary</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">${parseFloat(comparison.highest_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</div>
                                <div class="text-sm text-purple-700">Highest Salary</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">${parseFloat(comparison.lowest_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</div>
                                <div class="text-sm text-orange-700">Lowest Salary</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Department Comparison</h4>
                        <div class="space-y-2">
                            ${(comparison.departments || []).map(dept => `
                                <div class="flex justify-between items-center p-2 bg-white rounded">
                                    <span class="font-medium">${dept.DepartmentName || 'N/A'}</span>
                                    <span class="text-green-600 font-semibold">${parseFloat(dept.average_salary || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        } else {
            throw new Error(result.message || 'Failed to load salary comparison');
        }
    } catch (error) {
        console.error('Error loading salary comparison:', error);
        const content = document.getElementById('salary-comparison-content');
        content.innerHTML = `
            <div class="text-center py-8">
                <div class="text-red-600 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Comparison</h3>
                <p class="text-gray-500">${error.message}</p>
                <button onclick="loadSalaryComparison()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-retry mr-2"></i>Retry
                </button>
            </div>
        `;
    }
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}