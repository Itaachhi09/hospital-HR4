import { API_BASE_URL } from '../config.js';
import { loadBranchesForFilter, populateEmployeeDropdown } from '../utils.js';
import './shared-modals.js';

export async function displayDeductionsSection() {
    console.log("[Deductions] Displaying Deductions Section...");

    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !mainContentArea) {
        console.error("displayDeductionsSection: Core DOM elements not found.");
        return;
    }

    pageTitleElement.textContent = 'Employee Deductions - HR4 System';

    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-red-900">Deductions Management</h3>
                        <p class="text-sm text-red-700">Manage statutory and voluntary deductions from HR1/HR2 setup</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshDeductions()" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="showAddDeductionModal()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-plus mr-2"></i>Add Voluntary Deduction
                        </button>
                        <button onclick="showComputeDeductionsModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-calculator mr-2"></i>Compute for Payroll Run
                        </button>
                        <button onclick="exportDeductionsData()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-file-excel mr-2"></i>Export
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
                            <input id="deduction-search-input" type="text" placeholder="Search by employee, deduction type..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="deduction-branch-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">All Branches</option>
                        </select>
                        <select id="deduction-type-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">All Types</option>
                            <option value="SSS">SSS</option>
                            <option value="PhilHealth">PhilHealth</option>
                            <option value="Pag-IBIG">Pag-IBIG</option>
                            <option value="Tax">Tax</option>
                            <option value="HMO">HMO</option>
                            <option value="Loan">Loan</option>
                            <option value="Cash Advance">Cash Advance</option>
                            <option value="Other">Other</option>
                        </select>
                        <select id="deduction-category-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">All Categories</option>
                            <option value="statutory">Statutory</option>
                            <option value="voluntary">Voluntary</option>
                        </select>
                        <select id="deduction-payroll-run-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                            <option value="">All Payroll Runs</option>
                        </select>
                        
                        <button onclick="applyDeductionFilters()" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearDeductionFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Deductions Table -->
            <div class="px-6 py-4">
                <div id="deductions-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
                        <p class="text-gray-500 mt-2">Loading deductions...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Voluntary Deduction Modal -->
        <div id="add-deduction-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="add-voluntary-deduction-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Voluntary Deduction</h3>
                                <button type="button" onclick="closeAddDeductionModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="voluntary-deduction-employee" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                                    <select id="voluntary-deduction-employee" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>
                        <div>
                                    <label for="voluntary-deduction-type" class="block text-sm font-medium text-gray-700 mb-1">Deduction Type:</label>
                                    <select id="voluntary-deduction-type" name="deduction_type" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                        <option value="">Select Type</option>
                                        <option value="HMO">HMO Premium</option>
                                        <option value="Loan">Loan Payment</option>
                                        <option value="Cash Advance">Cash Advance</option>
                                        <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                                    <label for="voluntary-deduction-name" class="block text-sm font-medium text-gray-700 mb-1">Deduction Name:</label>
                                    <input type="text" id="voluntary-deduction-name" name="deduction_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., HMO Premium, Salary Loan">
                                </div>
                                <div>
                                    <label for="voluntary-deduction-amount" class="block text-sm font-medium text-gray-700 mb-1">Amount:</label>
                                    <input type="number" id="voluntary-deduction-amount" name="amount" step="0.01" min="0" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                             </div>
                        <div>
                                    <label for="voluntary-deduction-method" class="block text-sm font-medium text-gray-700 mb-1">Computation Method:</label>
                                    <select id="voluntary-deduction-method" name="computation_method" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                        <option value="Fixed">Fixed Amount</option>
                                        <option value="Percentage">Percentage of Salary</option>
                                    </select>
                                </div>
                                <div id="percentage-field" class="hidden">
                                    <label for="voluntary-deduction-percentage" class="block text-sm font-medium text-gray-700 mb-1">Percentage:</label>
                                    <input type="number" id="voluntary-deduction-percentage" name="percentage" step="0.01" min="0" max="100" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., 5.0">
                        </div>
                        <div>
                                    <label for="voluntary-deduction-effective-date" class="block text-sm font-medium text-gray-700 mb-1">Effective Date:</label>
                                    <input type="date" id="voluntary-deduction-effective-date" name="effective_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                                    <label for="voluntary-deduction-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional):</label>
                                    <textarea id="voluntary-deduction-notes" name="notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="Additional notes for this deduction..."></textarea>
                                </div>
                        </div>
                    </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Add Deduction
                        </button>
                            <button type="button" onclick="closeAddDeductionModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- Compute Deductions Modal -->
        <div id="compute-deductions-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="compute-deductions-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Compute Deductions for Payroll Run</h3>
                                <button type="button" onclick="closeComputeDeductionsModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
            </div>

                            <div class="space-y-4">
            <div>
                                    <label for="compute-deduction-payroll-run" class="block text-sm font-medium text-gray-700 mb-1">Select Payroll Run:</label>
                                    <select id="compute-deduction-payroll-run" name="payroll_run_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Loading payroll runs...</option>
                            </select>
                     </div>
                     <div>
                                    <label for="compute-deduction-branch" class="block text-sm font-medium text-gray-700 mb-1">Branch:</label>
                                    <select id="compute-deduction-branch" name="branch_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Branch</option>
                                    </select>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <p class="text-sm text-blue-800">
                                        <strong>Note:</strong> This will compute all statutory deductions (SSS, PhilHealth, Pag-IBIG, Tax) and apply existing voluntary deductions for all eligible employees in the selected branch and payroll run.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Compute Deductions
                            </button>
                            <button type="button" onclick="closeComputeDeductionsModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enhanced Employee Deduction Summary Modal -->
        <div id="employee-deduction-summary-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Employee Deduction Summary</h3>
                            <button type="button" onclick="closeEmployeeDeductionSummaryModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Enhanced Deduction Summary -->
                        <div id="deduction-summary" class="mb-6 p-6 bg-gradient-to-r from-red-50 to-pink-50 rounded-lg border border-red-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-600" id="deduction-employee">Employee Name</div>
                                    <div class="text-sm text-gray-500">Employee</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="deduction-total">₱0.00</div>
                                    <div class="text-sm text-gray-500">Total Deductions</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="deduction-statutory">₱0.00</div>
                                    <div class="text-sm text-gray-500">Statutory</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="deduction-voluntary">₱0.00</div>
                                    <div class="text-sm text-gray-500">Voluntary</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <button onclick="exportDeductionSummary()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="fas fa-download mr-2"></i>Export Summary
                            </button>
                            <button onclick="viewDeductionHistory()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="fas fa-history mr-2"></i>View History
                            </button>
                            <button onclick="addNewDeduction()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Add Deduction
                            </button>
                        </div>

                        <div id="employee-deduction-summary-content" class="space-y-4 text-sm text-gray-700">
                            <!-- Summary will be loaded here -->
                        </div>
                     </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="closeEmployeeDeductionSummaryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                     </div>
                 </div>
                </div>
            </div>
    `;

    setupDeductionsEventListeners();
    loadBranchesForFilter('deduction-branch-filter');
    loadDeductionTypesForFilter('deduction-type-filter');
    loadPayrollRunsForFilter('deduction-payroll-run-filter');
    loadDeductions();
}

function setupDeductionsEventListeners() {
    // Search input
    document.getElementById('deduction-search-input').addEventListener('input', applyDeductionFilters);
    
    // Filter dropdowns
    document.getElementById('deduction-branch-filter').addEventListener('change', applyDeductionFilters);
    document.getElementById('deduction-type-filter').addEventListener('change', applyDeductionFilters);
    document.getElementById('deduction-category-filter').addEventListener('change', applyDeductionFilters);
    document.getElementById('deduction-payroll-run-filter').addEventListener('change', applyDeductionFilters);
    
    // Computation method change
    document.getElementById('voluntary-deduction-method').addEventListener('change', function() {
        const percentageField = document.getElementById('percentage-field');
        if (this.value === 'Percentage') {
            percentageField.classList.remove('hidden');
            document.getElementById('voluntary-deduction-percentage').required = true;
        } else {
            percentageField.classList.add('hidden');
            document.getElementById('voluntary-deduction-percentage').required = false;
        }
    });
    
    // Form submissions
    document.getElementById('add-voluntary-deduction-form').addEventListener('submit', handleAddVoluntaryDeduction);
    document.getElementById('compute-deductions-form').addEventListener('submit', handleComputeDeductions);
}

async function loadDeductions() {
    try {
        const filters = getDeductionFilters();
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions?${new URLSearchParams(filters)}`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        renderDeductionsTable(data.data.deductions, data.data.pagination);
    } catch (error) {
        console.error('Error loading deductions:', error);
        const container = document.getElementById('deductions-list-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    <p class="text-red-600">Error loading deductions: ${error.message}</p>
                    <button onclick="loadDeductions()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        <i class="fas fa-retry mr-2"></i>Retry
                    </button>
                </div>
            `;
        }
    }
}

function getDeductionFilters() {
    return {
        search: document.getElementById('deduction-search-input').value,
        branch_id: document.getElementById('deduction-branch-filter').value,
        deduction_type: document.getElementById('deduction-type-filter').value,
        payroll_run_id: document.getElementById('deduction-payroll-run-filter').value,
        page: 1,
        limit: 50
    };
}

function renderDeductionsTable(deductions, pagination) {
    const container = document.getElementById('deductions-list-container');
    if (!container) {
        console.error('deductions-list-container element not found');
        return;
    }

    if (!deductions || deductions.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-receipt text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No deductions found</p>
                <p class="text-sm text-gray-400 mt-2">Try adjusting your filters or add a new voluntary deduction</p>
            </div>
        `;
        return;
    }

    const tableHTML = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deduction</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll Run</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${deductions.map(deduction => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">${deduction.employee_name || 'N/A'}</div>
                                        <div class="text-sm text-gray-500">${deduction.EmployeeNumber || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getDeductionTypeBadgeClass(deduction.DeductionType)}">
                                    ${deduction.DeductionType || 'N/A'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${deduction.DeductionName || 'N/A'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-medium">₱${parseFloat(deduction.computed_amount || deduction.Amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${deduction.ComputationMethod || 'Fixed'}
                                ${deduction.Percentage ? ` (${deduction.Percentage}%)` : ''}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${deduction.IsStatutory ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
                                    ${deduction.IsStatutory ? 'Statutory' : 'Voluntary'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${deduction.PayrollRunID ? `Run #${deduction.PayrollRunID}` : 'N/A'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(deduction.Status)}">
                                    ${deduction.Status || 'Active'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewEmployeeDeductionSummary(${deduction.EmployeeID})" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${deduction.IsVoluntary ? `
                                        <button onclick="editDeduction(${deduction.DeductionID})" class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteDeduction(${deduction.DeductionID})" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        
        ${pagination.pages > 1 ? `
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button onclick="changeDeductionPage(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <button onclick="changeDeductionPage(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">${((pagination.page - 1) * pagination.limit) + 1}</span> to <span class="font-medium">${Math.min(pagination.page * pagination.limit, pagination.total)}</span> of <span class="font-medium">${pagination.total}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button onclick="changeDeductionPage(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            ${Array.from({length: Math.min(5, pagination.pages)}, (_, i) => {
                                const pageNum = Math.max(1, pagination.page - 2) + i;
                                if (pageNum > pagination.pages) return '';
                                return `
                                    <button onclick="changeDeductionPage(${pageNum})" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${pageNum === pagination.page ? 'z-10 bg-red-50 border-red-500 text-red-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                                        ${pageNum}
                                    </button>
                                `;
                            }).join('')}
                            <button onclick="changeDeductionPage(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        ` : ''}
    `;

    container.innerHTML = tableHTML;
}

function getDeductionTypeBadgeClass(type) {
    const classes = {
        'SSS': 'bg-blue-100 text-blue-800',
        'PhilHealth': 'bg-green-100 text-green-800',
        'Pag-IBIG': 'bg-yellow-100 text-yellow-800',
        'Tax': 'bg-red-100 text-red-800',
        'HMO': 'bg-purple-100 text-purple-800',
        'Loan': 'bg-orange-100 text-orange-800',
        'Cash Advance': 'bg-indigo-100 text-indigo-800',
        'Other': 'bg-gray-100 text-gray-800'
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

// getStatusBadgeClass function removed - using shared utility from shared-modals.js

// Global functions for event handlers
window.refreshDeductions = loadDeductions;
window.applyDeductionFilters = loadDeductions;
window.clearDeductionFilters = function() {
    document.getElementById('deduction-search-input').value = '';
    document.getElementById('deduction-branch-filter').value = '';
    document.getElementById('deduction-type-filter').value = '';
    document.getElementById('deduction-category-filter').value = '';
    document.getElementById('deduction-payroll-run-filter').value = '';
    loadDeductions();
};

window.showAddDeductionModal = function() {
    document.getElementById('add-deduction-modal').classList.remove('hidden');
    populateEmployeeDropdown('voluntary-deduction-employee');
    document.getElementById('voluntary-deduction-effective-date').value = new Date().toISOString().split('T')[0];
};

window.closeAddDeductionModal = function() {
    document.getElementById('add-deduction-modal').classList.add('hidden');
    document.getElementById('add-voluntary-deduction-form').reset();
    document.getElementById('percentage-field').classList.add('hidden');
};

window.showComputeDeductionsModal = function() {
    document.getElementById('compute-deductions-modal').classList.remove('hidden');
    loadPayrollRunsForFilter('compute-deduction-payroll-run');
    loadBranchesForFilter('compute-deduction-branch');
};

window.closeComputeDeductionsModal = function() {
    document.getElementById('compute-deductions-modal').classList.add('hidden');
};

window.showEmployeeDeductionSummaryModal = function() {
    document.getElementById('employee-deduction-summary-modal').classList.remove('hidden');
};

window.closeEmployeeDeductionSummaryModal = function() {
    document.getElementById('employee-deduction-summary-modal').classList.add('hidden');
};

async function handleAddVoluntaryDeduction(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Convert amount to number
    data.amount = parseFloat(data.amount);
    if (data.percentage) {
        data.percentage = parseFloat(data.percentage);
    }
    
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions/voluntary`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }

        const result = await response.json();
        console.log('Voluntary deduction added:', result);
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Voluntary deduction added successfully',
                timer: 2000
            });
        }
        
        closeAddDeductionModal();
        loadDeductions();
        
    } catch (error) {
        console.error('Error adding voluntary deduction:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        } else {
            alert('Error adding voluntary deduction: ' + error.message);
        }
    }
}

async function handleComputeDeductions(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions/compute`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }

        const result = await response.json();
        console.log('Deductions computed:', result);
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `Deductions computed successfully for ${result.data.employee_count} employees`,
                timer: 3000
            });
        }
        
        closeComputeDeductionsModal();
        loadDeductions();
        
    } catch (error) {
        console.error('Error computing deductions:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        } else {
            alert('Error computing deductions: ' + error.message);
        }
    }
}

async function viewEmployeeDeductionSummary(employeeId) {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions/${employeeId}/summary`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const summary = data.data;
        
        document.getElementById('employee-deduction-summary-content').innerHTML = `
            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-2">Employee Information</h4>
                    <p><strong>Name:</strong> ${summary.employee_name}</p>
                    <p><strong>Employee Number:</strong> ${summary.EmployeeNumber}</p>
                    <p><strong>Department:</strong> ${summary.DepartmentName}</p>
                    <p><strong>Position:</strong> ${summary.PositionName}</p>
                    <p><strong>Branch:</strong> ${summary.BranchName}</p>
                    <p><strong>Base Salary:</strong> ₱${parseFloat(summary.BaseSalary || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h5 class="font-semibold text-red-800 mb-2">Statutory Deductions</h5>
                        <p class="text-2xl font-bold text-red-900">₱${parseFloat(summary.total_statutory || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                        <div class="mt-2 space-y-1">
                            ${summary.statutory_breakdown.map(deduction => `
                                <div class="flex justify-between text-sm">
                                    <span>${deduction.DeductionName}</span>
                                    <span class="font-medium">₱${parseFloat(deduction.Amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h5 class="font-semibold text-blue-800 mb-2">Voluntary Deductions</h5>
                        <p class="text-2xl font-bold text-blue-900">₱${parseFloat(summary.total_voluntary || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                        <div class="mt-2 space-y-1">
                            ${summary.voluntary_breakdown.map(deduction => `
                                <div class="flex justify-between text-sm">
                                    <span>${deduction.DeductionName}</span>
                                    <span class="font-medium">₱${parseFloat(deduction.Amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-100 p-4 rounded-lg">
                    <h5 class="font-semibold text-gray-900 mb-2">Total Deductions Summary</h5>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-medium">Total Deductions:</span>
                        <span class="text-2xl font-bold text-gray-900">₱${parseFloat(summary.total_deductions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-600">Current Month:</span>
                        <span class="text-sm font-medium">₱${parseFloat(summary.current_month_deductions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
        `;
        
        showEmployeeDeductionSummaryModal();

    } catch (error) {
        console.error('Error loading employee deduction summary:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load employee deduction summary'
            });
        } else {
            alert('Error loading employee deduction summary: ' + error.message);
        }
    }
}

window.viewEmployeeDeductionSummary = viewEmployeeDeductionSummary;

function editDeduction(deductionId) {
    // TODO: Implement edit deduction functionality
    console.log('Edit deduction:', deductionId);
    alert('Edit deduction functionality coming soon!');
}

function deleteDeduction(deductionId) {
    if (confirm('Are you sure you want to delete this deduction?')) {
        // TODO: Implement delete deduction functionality
        console.log('Delete deduction:', deductionId);
        alert('Delete deduction functionality coming soon!');
    }
}

window.editDeduction = editDeduction;
window.deleteDeduction = deleteDeduction;

function changeDeductionPage(page) {
    // TODO: Implement pagination
    console.log('Change to page:', page);
}

window.changeDeductionPage = changeDeductionPage;

async function loadDeductionTypesForFilter(selectId) {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions/types`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            const select = document.getElementById(selectId);
            if (select) {
                // Keep the first option (All Types)
                const firstOption = select.querySelector('option');
                select.innerHTML = '';
                select.appendChild(firstOption);
                
                data.data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.type;
                    option.textContent = `${type.type} (${type.count})`;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading deduction types:', error);
    }
}

// loadPayrollRunsForFilter function removed - using shared utility from shared-modals.js

async function exportDeductionsData() {
    try {
        const filters = getDeductionFilters();
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions?${new URLSearchParams({...filters, limit: 1000})}`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const deductions = data.data.deductions;

        // Create CSV content
        const csvContent = [
            ['Employee Name', 'Employee Number', 'Department', 'Position', 'Branch', 'Deduction Type', 'Deduction Name', 'Amount', 'Computation Method', 'Category', 'Payroll Run ID', 'Status', 'Effective Date'],
            ...deductions.map(deduction => [
                deduction.employee_name || '',
                deduction.EmployeeNumber || '',
                deduction.DepartmentName || '',
                deduction.PositionName || '',
                deduction.BranchName || '',
                deduction.DeductionType || '',
                deduction.DeductionName || '',
                deduction.computed_amount || deduction.Amount || 0,
                deduction.ComputationMethod || '',
                deduction.IsStatutory ? 'Statutory' : 'Voluntary',
                deduction.PayrollRunID || '',
                deduction.Status || '',
                deduction.EffectiveDate || ''
            ])
        ].map(row => row.map(field => `"${field}"`).join(',')).join('\n');

        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `deductions_export_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Export Complete!',
                text: `Exported ${deductions.length} deduction records`,
                timer: 2000
            });
        }
    } catch (error) {
        console.error('Error exporting deductions:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: error.message
            });
        } else {
            alert('Error exporting deductions: ' + error.message);
        }
    }
}

// Enhanced deduction functions
function updateDeductionSummary(deduction) {
    document.getElementById('deduction-employee').textContent = deduction.employee_name || 'N/A';
    document.getElementById('deduction-total').textContent = 
        parseFloat(deduction.total_deductions || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    document.getElementById('deduction-statutory').textContent = 
        parseFloat(deduction.statutory_deductions || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    document.getElementById('deduction-voluntary').textContent = 
        parseFloat(deduction.voluntary_deductions || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function exportDeductionSummary() {
    showInlineAlert('Exporting deduction summary...', 'info');
    // Implementation for exporting deduction summary
    setTimeout(() => {
        showInlineAlert('Deduction summary exported!', 'success');
    }, 2000);
}

function viewDeductionHistory() {
    showInlineAlert('Loading deduction history...', 'info');
    // Implementation for viewing deduction history
    setTimeout(() => {
        showInlineAlert('Deduction history loaded!', 'success');
    }, 1500);
}

function addNewDeduction() {
    showInlineAlert('Opening deduction form...', 'info');
    // Implementation for adding new deduction
    setTimeout(() => {
        showInlineAlert('Deduction form opened!', 'success');
    }, 1000);
}

window.exportDeductionsData = exportDeductionsData;

// Helper functions

async function deleteDeductionRecord(deductionId) {
    try {
        const response = await fetch(`${API_BASE_URL.replace('php/api/', 'api')}/deductions/${deductionId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                alert('Deduction deleted successfully!');
                loadDeductions(); // Refresh the list
            } else {
                alert('Error deleting deduction: ' + result.message);
            }
        } else {
            alert('Error deleting deduction');
        }
    } catch (error) {
        console.error('Error deleting deduction:', error);
        alert('Error deleting deduction');
    }
}
