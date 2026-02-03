/**
 * Payroll V2 - Automated Payroll Runs Module
 * Multi-branch, versioned, auditable payroll system
 */
import { REST_API_URL } from '../utils.js';
import './shared-modals.js';

/**
 * Displays the Payroll V2 Runs section.
 */
export async function displayPayrollRunsSection() {
    console.log("[Display] Displaying Payroll V2 Runs Section...");
    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("displayPayrollRunsSection: Core DOM elements not found.");
        return;
    }
    pageTitleElement.textContent = 'Payroll Runs - HR4 System';

    // Inject HTML structure
    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-blue-900">Payroll Runs Dashboard</h3>
                        <p class="text-sm text-blue-700">Process payroll per cutoff - pulls DTR from HR3, salary rates from HR1/HR2</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshPayrollRuns()" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="showCreateRunModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i>New Payroll Run
                        </button>
                        <button onclick="exportPayrollSummary()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-file-excel mr-2"></i>Export Summary
                        </button>
                        <button onclick="showVersionLog()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <i class="fas fa-history mr-2"></i>Version Log
                        </button>
                        <button onclick="showSalariesModule()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-money-bill-wave mr-2"></i>Salaries
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
                            <input id="payroll-search-input" type="text" placeholder="Search by branch or period..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="branch-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Branches</option>
                        </select>
                        
                        <select id="status-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="Draft">Draft</option>
                            <option value="Processing">Processing</option>
                            <option value="Completed">Completed</option>
                            <option value="Approved">Approved</option>
                            <option value="Locked">Locked</option>
                        </select>
                        
                        <button onclick="applyPayrollFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearPayrollFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>


            <!-- Payroll Runs Table -->
            <div class="px-6 py-4">
                <div id="payroll-runs-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-500 mt-2">Loading payroll runs...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Payroll Run Modal -->
        <div id="create-run-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="create-payroll-run-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Create New Payroll Run</h3>
                                <button type="button" onclick="closeCreateRunModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                        <div>
                                    <label for="create-branch" class="block text-sm font-medium text-gray-700 mb-1">Branch:</label>
                                    <select id="create-branch" name="branch_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Branch</option>
                                    </select>
                        </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                                        <label for="create-start-date" class="block text-sm font-medium text-gray-700 mb-1">Pay Period Start:</label>
                                        <input type="date" id="create-start-date" name="pay_period_start" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                                        <label for="create-end-date" class="block text-sm font-medium text-gray-700 mb-1">Pay Period End:</label>
                                        <input type="date" id="create-end-date" name="pay_period_end" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                                
                                <div>
                                    <label for="create-pay-date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date:</label>
                                    <input type="date" id="create-pay-date" name="pay_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                                    <label for="create-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional):</label>
                                    <textarea id="create-notes" name="notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Additional notes for this payroll run..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Create Run
                            </button>
                            <button type="button" onclick="closeCreateRunModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enhanced View Run Details Modal -->
        <div id="view-run-details-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Payroll Run Details</h3>
                            <button type="button" onclick="closeViewRunDetailsModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Enhanced Run Summary -->
                        <div id="run-summary" class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600" id="run-id">#0</div>
                                    <div class="text-sm text-gray-500">Run ID</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold" id="run-status-badge">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800" id="run-status">Unknown</span>
                                    </div>
                                    <div class="text-sm text-gray-500">Status</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="run-period">N/A to N/A</div>
                                    <div class="text-sm text-gray-500">Pay Period</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="run-employees">0</div>
                                    <div class="text-sm text-gray-500">Employees</div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-blue-200">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600" id="run-gross-pay">₱0.00</div>
                                    <div class="text-sm text-gray-500">Total Gross Pay</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-red-600" id="run-deductions">₱0.00</div>
                                    <div class="text-sm text-gray-500">Total Deductions</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-blue-600" id="run-net-pay">₱0.00</div>
                                    <div class="text-sm text-gray-500">Total Net Pay</div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Action Buttons -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <button id="process-run-btn" onclick="processRunFromModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i class="fas fa-play mr-2"></i>Process Run
                            </button>
                            <button id="approve-run-btn" onclick="approveRunFromModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i class="fas fa-check mr-2"></i>Approve Run
                            </button>
                            <button id="lock-run-btn" onclick="lockRunFromModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i class="fas fa-lock mr-2"></i>Lock Run
                            </button>
                            <button onclick="exportRunPayslips()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-download mr-2"></i>Export Payslips
                            </button>
                        </div>

                        <!-- Enhanced Payslips Section -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">Employee Payslips</h4>
                                <div class="flex items-center space-x-4">
                                    <div class="text-sm text-gray-500">
                                        <span id="payslips-count">0</span> employees
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label for="payslips-per-page" class="text-sm text-gray-500">Per page:</label>
                                        <select id="payslips-per-page" onchange="changePayslipsPerPage()" class="text-sm border border-gray-300 rounded-md px-2 py-1">
                                            <option value="10">10</option>
                                            <option value="25" selected>25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="payslips-loading" class="text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p class="text-gray-500 mt-2">Loading payslips...</p>
                            </div>
                            
                            <div id="payslips-table-container" class="hidden">
                                <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Pay</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="payslips-tbody" class="bg-white divide-y divide-gray-200">
                                            <!-- Payslips will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Enhanced Pagination -->
                                <div id="payslips-pagination" class="mt-4 flex items-center justify-between">
                                    <!-- Pagination will be loaded here -->
                                </div>
                            </div>
                            
                            <div id="payslips-empty" class="hidden text-center py-12">
                                <i class="fas fa-file-invoice text-gray-300 text-6xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No payslips found</h3>
                                <p class="text-gray-500">This payroll run doesn't have any payslips yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div id="confirmation-modal" class="fixed inset-0 z-60 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="confirmation-title">Confirm Action</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" id="confirmation-message">Are you sure you want to perform this action?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirmation-confirm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm
                        </button>
                        <button type="button" onclick="closeConfirmationModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

    // Add a modal for viewing run details (inserted programmatically after main HTML)
    const existingViewModal = document.getElementById('view-run-modal');
    if (!existingViewModal) {
        const viewModalHtml = `
            <div id="view-run-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Payroll Run Details</h3>
                                <button type="button" onclick="closeViewRunModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div id="view-run-content" class="space-y-3 text-sm text-gray-700">
                                <!-- Filled dynamically -->
                                <div class="text-center text-gray-500">Loading...</div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" onclick="closeViewRunModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = viewModalHtml;
        document.body.appendChild(wrapper.firstElementChild);
    }

    // Add listeners after HTML injection
    requestAnimationFrame(async () => {
        await loadBranches();
        await loadPayrollRuns();
        
        const createRunForm = document.getElementById('create-payroll-run-form');
        if (createRunForm) {
            if (!createRunForm.hasAttribute('data-listener-attached')) {
                createRunForm.addEventListener('submit', handleCreatePayrollRun);
                createRunForm.setAttribute('data-listener-attached', 'true');
            }
        }
    });
}

/**
 * Load branches for dropdowns
 */
async function loadBranches() {
    try {
        // For now, use a simple hardcoded list. In production, this would come from an API
        const branches = [
            { BranchID: 1, BranchCode: 'MAIN', BranchName: 'Main Hospital' }
        ];
        
        const branchFilter = document.getElementById('branch-filter');
        const createBranch = document.getElementById('create-branch');
        
        if (branchFilter) {
            branchFilter.innerHTML = '<option value="">All Branches</option>' + 
                branches.map(b => `<option value="${b.BranchID}">${b.BranchName}</option>`).join('');
        }
        
        if (createBranch) {
            createBranch.innerHTML = '<option value="">Select Branch</option>' + 
                branches.map(b => `<option value="${b.BranchID}">${b.BranchName}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading branches:', error);
    }
}

/**
 * Fetches payroll run records from the V2 API.
 */
async function loadPayrollRuns() {
    console.log("[Load] Loading Payroll V2 Runs...");
    const container = document.getElementById('payroll-runs-list-container');
    if (!container) return;
    container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="text-gray-500 mt-2">Loading payroll runs...</p></div>';

    // Build query parameters
    const params = new URLSearchParams();
    const branchFilter = document.getElementById('branch-filter')?.value;
    const statusFilter = document.getElementById('status-filter')?.value;
    const searchTerm = document.getElementById('payroll-search-input')?.value;
    
    if (branchFilter) params.set('branch_id', branchFilter);
    if (statusFilter) params.set('status', statusFilter);
    if (searchTerm) params.set('search', searchTerm);
    
    params.set('page', '1');
    params.set('limit', '50');

    const url = `${REST_API_URL}payroll-v2?${params.toString()}`;

    try {
        const response = await fetch(url, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (result.success && result.data) {
            renderPayrollRunsTable(result.data.items || [], result.data.pagination || {});
        } else {
            console.error("Error fetching payroll runs:", result.message);
            container.innerHTML = `<p class="text-red-500 text-center py-4">Error: ${result.message || 'Failed to load payroll runs'}</p>`;
        }
    } catch (error) {
        console.error('Error loading payroll runs:', error);
        container.innerHTML = `<p class="text-red-500 text-center py-4">Could not load payroll runs. ${error.message}</p>`;
    }
}

/**
 * Renders the payroll runs data into an HTML table.
 * @param {Array} payrollRuns - An array of payroll run objects.
 */
function renderPayrollRunsTable(payrollRuns, pagination = {}) {
    console.log("[Render] Rendering Payroll V2 Runs Table...");
    const container = document.getElementById('payroll-runs-list-container');
    if (!container) return;

    if (!payrollRuns || !Array.isArray(payrollRuns) || payrollRuns.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-calculator text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No payroll runs found</h3>
                <p class="text-gray-500">Create your first payroll run to get started.</p>
            </div>`;
        return;
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';
    table.innerHTML = `
        <thead class="bg-blue-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Run ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Branch</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Pay Period</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Payment Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Version</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Employees</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Gross Pay</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Deductions</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Net Pay</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Actions</th>
                </tr>
        </thead>`;

    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200';

    payrollRuns.forEach(run => {
        const row = tbody.insertRow();
        row.id = `pr-row-${run.PayrollRunID}`;

        const createCell = (text, className = '') => {
            const cell = row.insertCell();
            cell.className = `px-4 py-3 whitespace-nowrap text-sm ${className}`;
            cell.textContent = text ?? '';
            return cell;
        };

        // Run ID
        createCell(`#${run.PayrollRunID}`, 'font-mono text-gray-600');

        // Branch
        createCell(run.BranchName || 'Unknown', 'font-medium text-gray-900');

        // Pay Period
        const periodStart = new Date(run.PayPeriodStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const periodEnd = new Date(run.PayPeriodEnd).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        createCell(`${periodStart} - ${periodEnd}`, 'text-gray-700');

        // Payment Date
        const payDate = new Date(run.PayDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        createCell(payDate, 'text-gray-700');

        // Status
        const statusCell = row.insertCell();
        statusCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        const statusBadge = document.createElement('span');
        statusBadge.textContent = run.Status;
        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + getStatusBadgeClass(run.Status);
        statusCell.appendChild(statusBadge);

        // Version
        createCell(`v${run.Version || 1}`, 'text-gray-500 font-mono');

        // Employee Count
        createCell(run.TotalEmployees || 0, 'text-gray-700');

        // Gross Pay
        const grossPay = parseFloat(run.TotalGrossPay || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
        createCell(grossPay, 'text-blue-600 font-medium');

        // Deductions
        const deductions = parseFloat(run.TotalDeductions || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
        createCell(deductions, 'text-red-600 font-medium');

        // Total Net Pay
        const netPay = parseFloat(run.TotalNetPay || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
        createCell(netPay, 'text-green-600 font-semibold');

        // Actions
        const actionsCell = row.insertCell();
        actionsCell.className = 'px-4 py-3 whitespace-nowrap text-sm font-medium space-x-2';
        
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'flex items-center space-x-2';
        
        // View Details
        const viewBtn = document.createElement('button');
        viewBtn.innerHTML = '<i class="fas fa-eye mr-1"></i>View';
        viewBtn.className = 'inline-flex items-center px-3 py-1 border border-blue-300 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500';
        viewBtn.onclick = () => viewRunDetails(run.PayrollRunID);
        actionsDiv.appendChild(viewBtn);

        // Process (if Draft)
        if (run.Status === 'Draft') {
            const processBtn = document.createElement('button');
            processBtn.innerHTML = '<i class="fas fa-cogs mr-1"></i>Process';
            processBtn.className = 'inline-flex items-center px-3 py-1 border border-green-300 rounded-md text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500';
            processBtn.onclick = () => processRun(run.PayrollRunID);
            actionsDiv.appendChild(processBtn);
        }

        // Approve (if Completed)
        if (run.Status === 'Completed') {
            const approveBtn = document.createElement('button');
            approveBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Approve';
            approveBtn.className = 'inline-flex items-center px-3 py-1 border border-purple-300 rounded-md text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-purple-500';
            approveBtn.onclick = () => approveRun(run.PayrollRunID);
            actionsDiv.appendChild(approveBtn);
        }

        // Lock (if Approved)
        if (run.Status === 'Approved') {
            const lockBtn = document.createElement('button');
            lockBtn.innerHTML = '<i class="fas fa-lock mr-1"></i>Lock';
            lockBtn.className = 'inline-flex items-center px-3 py-1 border border-red-300 rounded-md text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500';
            lockBtn.onclick = () => lockRun(run.PayrollRunID);
            actionsDiv.appendChild(lockBtn);
        }
        
        actionsCell.appendChild(actionsDiv);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

// getStatusBadgeClass function removed - using shared utility from shared-modals.js

// Modal functions
window.showCreateRunModal = function() {
    const modal = document.getElementById('create-run-modal');
    if (modal) {
        modal.classList.remove('hidden');
        // Set default dates
        const today = new Date();
        const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        const payDate = new Date(today.getFullYear(), today.getMonth() + 1, 15);
        
        document.getElementById('create-start-date').value = startDate.toISOString().split('T')[0];
        document.getElementById('create-end-date').value = endDate.toISOString().split('T')[0];
        document.getElementById('create-pay-date').value = payDate.toISOString().split('T')[0];
    }
};

window.closeCreateRunModal = function() {
    const modal = document.getElementById('create-run-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('create-payroll-run-form').reset();
    }
};

// Filter functions
window.applyPayrollFilters = function() {
    loadPayrollRuns();
};

window.clearPayrollFilters = function() {
    document.getElementById('branch-filter').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('payroll-search-input').value = '';
    loadPayrollRuns();
};

window.refreshPayrollRuns = function() {
    loadPayrollRuns();
};

// Export functionality
window.exportPayrollSummary = function() {
    const runs = document.querySelectorAll('#payroll-runs-list-container tbody tr');
    if (!runs || runs.length === 0) {
        alert('No payroll runs available to export.');
        return;
    }

    // Create CSV content
    const headers = ['Run ID', 'Branch', 'Pay Period', 'Payment Date', 'Status', 'Version', 'Employees', 'Gross Pay', 'Deductions', 'Net Pay'];
    const csvContent = [
        headers.join(','),
        ...Array.from(runs).map(row => {
            const cells = row.querySelectorAll('td');
            return [
                cells[0]?.textContent?.trim() || '',
                cells[1]?.textContent?.trim() || '',
                cells[2]?.textContent?.trim() || '',
                cells[3]?.textContent?.trim() || '',
                cells[4]?.textContent?.trim() || '',
                cells[5]?.textContent?.trim() || '',
                cells[6]?.textContent?.trim() || '',
                cells[7]?.textContent?.trim() || '',
                cells[8]?.textContent?.trim() || '',
                cells[9]?.textContent?.trim() || ''
            ].map(cell => `"${cell}"`).join(',');
        })
    ].join('\n');

    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `payroll_summary_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    alert('Payroll summary exported successfully!');
};

// Version log functionality
window.showVersionLog = function() {
    // For now, show a simple alert. In production, this would open a detailed modal
    alert('Version Log Feature:\n\n• Track all payroll run versions\n• Reprocess previous runs for corrections\n• Audit trail of all changes\n• Rollback capabilities\n\nThis feature will be implemented in the next update.');
};

// Salaries module functionality
window.showSalariesModule = async function() {
    try {
        // Import and display the salaries module
        const { displaySalariesSection } = await import('./salaries.js');
        await displaySalariesSection();
    } catch (error) {
        console.error('Error loading salaries module:', error);
        alert('Error loading salaries module: ' + error.message);
    }
};

/**
 * Handles the submission of the create payroll run form.
 */
async function handleCreatePayrollRun(event) {
    event.preventDefault();
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    if (!form || !submitButton) {
        console.error("Create Payroll Run form elements missing.");
        return;
    }

    // Client-side validation
    const branchId = form.elements['branch_id'].value;
    const startDate = form.elements['pay_period_start'].value;
    const endDate = form.elements['pay_period_end'].value;
    const paymentDate = form.elements['pay_date'].value;

    if (!branchId || !startDate || !endDate || !paymentDate) {
        alert('All fields are required.');
        return;
    }
    if (endDate < startDate) {
        alert('End Date cannot be before Start Date.');
        return;
    }
     if (paymentDate < endDate) {
        alert('Payment Date cannot be before Pay Period End Date.');
        return;
    }

    const formData = {
        branch_id: parseInt(branchId),
        pay_period_start: startDate,
        pay_period_end: endDate,
        pay_date: paymentDate,
        notes: form.elements['notes'].value || null
    };

    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';

    try {
        const response = await fetch(`${REST_API_URL}payroll-v2`, {
            method: 'POST',
            credentials: 'include',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }

        alert('Payroll run created successfully!');
        closeCreateRunModal();
        await loadPayrollRuns(); // Refresh the list

    } catch (error) {
        console.error('Error creating payroll run:', error);
        alert(`Error: ${error.message}`);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Create Run';
    }
}

// Action functions
async function viewRunDetails(runId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}`, {
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            // Populate and show the run details modal
            const run = result.data;
            const content = document.getElementById('view-run-content');
            if (content) {
                content.innerHTML = `
                    <div class="space-y-2">
                        <div><strong>Payroll Run #:</strong> ${run.PayrollRunID}</div>
                        <div><strong>Branch:</strong> ${run.BranchName || 'N/A'}</div>
                        <div><strong>Period:</strong> ${run.PayPeriodStart} to ${run.PayPeriodEnd}</div>
                        <div><strong>Payment Date:</strong> ${run.PayDate || 'N/A'}</div>
                        <div><strong>Status:</strong> ${run.Status || 'N/A'}</div>
                        <div><strong>Employees:</strong> ${run.TotalEmployees || 0}</div>
                        <div><strong>Total Gross Pay:</strong> ₱${parseFloat(run.TotalGrossPay || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                        <div><strong>Total Deductions:</strong> ₱${parseFloat(run.TotalDeductions || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                        <div><strong>Total Net Pay:</strong> ₱${parseFloat(run.TotalNetPay || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                        <div class="pt-2 text-sm text-gray-500">Notes: ${run.Notes || 'None'}</div>
                    </div>
                `;
            }
            const modal = document.getElementById('view-run-modal');
            if (modal) modal.classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load run details');
        }
    } catch (error) {
        console.error('Error viewing run details:', error);
        // Show a simple inline alert dialog if modal isn't available
        const modalContent = document.getElementById('view-run-content');
        if (modalContent) {
            modalContent.innerHTML = `<div class="text-red-600">Error loading run details: ${error.message}</div>`;
            const modal = document.getElementById('view-run-modal');
            if (modal) modal.classList.remove('hidden');
        } else {
            alert(`Error: ${error.message}`);
        }
    }
}

window.closeViewRunModal = function() {
    const modal = document.getElementById('view-run-modal');
    if (modal) modal.classList.add('hidden');
};

async function processRun(runId) {
    if (!confirm(`Are you sure you want to process Payroll Run #${runId}? This will calculate payslips for all eligible employees.`)) {
        return;
    }

    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/process`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            alert('Payroll run processed successfully!');
            await loadPayrollRuns();
        } else {
            throw new Error(result.message || 'Failed to process run');
        }
    } catch (error) {
        console.error('Error processing run:', error);
        alert(`Error: ${error.message}`);
    }
}

async function approveRun(runId) {
    if (!confirm(`Are you sure you want to approve Payroll Run #${runId}?`)) {
        return;
    }

    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/approve`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            alert('Payroll run approved successfully!');
            await loadPayrollRuns();
        } else {
            throw new Error(result.message || 'Failed to approve run');
        }
    } catch (error) {
        console.error('Error approving run:', error);
        alert(`Error: ${error.message}`);
    }
}

async function lockRun(runId) {
    if (!confirm(`Are you sure you want to lock Payroll Run #${runId}? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/lock`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            alert('Payroll run locked successfully!');
            await loadPayrollRuns();
        } else {
            throw new Error(result.message || 'Failed to lock run');
        }
    } catch (error) {
        console.error('Error locking run:', error);
        alert(`Error: ${error.message}`);
    }
}

// Additional button functions that are referenced in HTML but not yet implemented
window.viewRunDetails = function(runId) {
    console.log("[Action] Viewing run details for:", runId);
    showRunDetailsModal(runId);
};

window.processRun = function(runId) {
    console.log("[Action] Processing run:", runId);
    showConfirmationModal(
        'Process Payroll Run',
        `Are you sure you want to process Payroll Run #${runId}? This will compute all payslips.`,
        () => processPayrollRun(runId)
    );
};

window.approveRun = function(runId) {
    console.log("[Action] Approving run:", runId);
    showConfirmationModal(
        'Approve Payroll Run',
        `Are you sure you want to approve Payroll Run #${runId}?`,
        () => approvePayrollRun(runId)
    );
};

window.lockRun = function(runId) {
    console.log("[Action] Locking run:", runId);
    if (confirm('Are you sure you want to lock this payroll run? This action cannot be undone.')) {
        lockPayrollRun(runId);
    }
};

// Helper functions for payroll run actions
// processPayrollRun function removed - using enhanced version defined later in the file

// approvePayrollRun and lockPayrollRun functions removed - using enhanced versions defined later in the file

// Enhanced View Run Details Modal Functions
let currentRunId = null;
let currentRunData = null;
let currentPayslipsPage = 1;
let currentPayslipsPerPage = 25;
let currentPayslipsTotal = 0;

async function showRunDetailsModal(runId) {
    currentRunId = runId;
    currentPayslipsPage = 1;
    currentPayslipsPerPage = 25;
    
    // Show the modal
    const modal = document.getElementById('view-run-details-modal');
    modal.classList.remove('hidden');
    
    // Load run details
    await loadRunDetails(runId);
    
    // Load payslips
    await loadRunPayslips(runId);
}

function closeViewRunDetailsModal() {
    const modal = document.getElementById('view-run-details-modal');
    modal.classList.add('hidden');
    currentRunId = null;
    currentRunData = null;
    currentPayslipsPage = 1;
    currentPayslipsPerPage = 25;
    currentPayslipsTotal = 0;
}

async function loadRunDetails(runId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}`, {
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            currentRunData = result.data;
            updateRunSummary(result.data);
            updateActionButtons(result.data);
        } else {
            throw new Error(result.message || 'Failed to load run details');
        }
    } catch (error) {
        console.error('Error loading run details:', error);
        showInlineAlert(`Error loading run details: ${error.message}`, 'error');
    }
}

function updateRunSummary(runData) {
    // Update basic info
    document.getElementById('run-id').textContent = `#${runData.PayrollRunID || 'N/A'}`;
    document.getElementById('run-period').textContent = 
        `${runData.PayPeriodStart || 'N/A'} to ${runData.PayPeriodEnd || 'N/A'}`;
    document.getElementById('run-employees').textContent = runData.TotalEmployees || 0;
    
    // Update financial info
    document.getElementById('run-gross-pay').textContent = 
        `₱${parseFloat(runData.TotalGrossPay || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
    document.getElementById('run-deductions').textContent = 
        `₱${parseFloat(runData.TotalDeductions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
    document.getElementById('run-net-pay').textContent = 
        `₱${parseFloat(runData.TotalNetPay || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
    
    // Update status badge
    const statusBadge = document.getElementById('run-status');
    const status = runData.Status || 'Unknown';
    statusBadge.textContent = status;
    statusBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusBadgeClass(status)}`;
}

function updateActionButtons(runData) {
    const processBtn = document.getElementById('process-run-btn');
    const approveBtn = document.getElementById('approve-run-btn');
    const lockBtn = document.getElementById('lock-run-btn');
    
    const status = runData.Status || '';
    const employeeCount = runData.TotalEmployees || 0;
    
    // Enable/disable buttons based on status and employee count
    processBtn.disabled = status === 'Processing' || status === 'Completed' || status === 'Approved' || status === 'Locked' || employeeCount === 0;
    approveBtn.disabled = status !== 'Completed';
    lockBtn.disabled = status !== 'Approved';
    
    // Update button text and tooltips
    if (status === 'Processing') {
        processBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        processBtn.title = 'Payroll run is currently being processed';
    } else if (employeeCount === 0) {
        processBtn.innerHTML = '<i class="fas fa-play mr-2"></i>Process Run';
        processBtn.title = 'Cannot process: No employees found for this run';
    } else {
        processBtn.innerHTML = '<i class="fas fa-play mr-2"></i>Process Run';
        processBtn.title = 'Process payroll run and generate payslips';
    }
    
    approveBtn.title = status === 'Completed' ? 'Approve this payroll run' : 'Only completed runs can be approved';
    lockBtn.title = status === 'Approved' ? 'Lock this payroll run (cannot be undone)' : 'Only approved runs can be locked';
}

async function loadRunPayslips(runId, page = 1, perPage = 25) {
    const loadingEl = document.getElementById('payslips-loading');
    const tableContainer = document.getElementById('payslips-table-container');
    const emptyEl = document.getElementById('payslips-empty');
    
    loadingEl.classList.remove('hidden');
    tableContainer.classList.add('hidden');
    emptyEl.classList.add('hidden');
    
    try {
        // Build query parameters for pagination
        const params = new URLSearchParams({
            page: page,
            limit: perPage
        });
        
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/payslips?${params.toString()}`, {
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        loadingEl.classList.add('hidden');
        
        if (result.success && result.data && result.data.length > 0) {
            currentPayslipsTotal = result.pagination?.total || result.data.length;
            renderPayslipsTable(result.data, result.pagination);
            tableContainer.classList.remove('hidden');
        } else {
            emptyEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading payslips:', error);
        loadingEl.classList.add('hidden');
        emptyEl.classList.remove('hidden');
        emptyEl.innerHTML = `
            <i class="fas fa-exclamation-triangle text-red-300 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Payslips</h3>
            <p class="text-red-500">${error.message}</p>
        `;
    }
}

function renderPayslipsTable(payslips, pagination = {}) {
    const tbody = document.getElementById('payslips-tbody');
    const countEl = document.getElementById('payslips-count');
    
    countEl.textContent = pagination.total || payslips.length;
    
    tbody.innerHTML = payslips.map(payslip => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                            ${payslip.FirstName || ''} ${payslip.LastName || ''}
                        </div>
                        <div class="text-sm text-gray-500">
                            ${payslip.JobTitle || 'N/A'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ₱${parseFloat(payslip.BasicSalary || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ₱${parseFloat(payslip.OvertimePay || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ₱${parseFloat(payslip.TotalDeductions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ₱${parseFloat(payslip.NetIncome || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPayslipStatusBadgeClass(payslip.Status || 'Generated')}">
                    ${payslip.Status || 'Generated'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-3">
                    <button onclick="previewPayslipFromModal(${payslip.PayslipID})" 
                            class="text-blue-600 hover:text-blue-900 transition-colors" 
                            title="Preview Payslip">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="downloadPayslipFromModal(${payslip.PayslipID})" 
                            class="text-green-600 hover:text-green-900 transition-colors" 
                            title="Download PDF">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Render pagination
    renderPayslipsPagination(pagination);
}

function renderPayslipsPagination(pagination) {
    const paginationEl = document.getElementById('payslips-pagination');
    if (!paginationEl) return;
    
    const totalPages = pagination.total_pages || 1;
    const currentPage = pagination.current_page || 1;
    const hasNext = pagination.has_next || false;
    const hasPrev = pagination.has_prev || false;
    
    if (totalPages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }
    
    let paginationHTML = `
        <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="changePayslipsPage(${currentPage - 1})" 
                        ${!hasPrev ? 'disabled' : ''} 
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button onclick="changePayslipsPage(${currentPage + 1})" 
                        ${!hasNext ? 'disabled' : ''} 
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">${((currentPage - 1) * currentPayslipsPerPage) + 1}</span>
                        to <span class="font-medium">${Math.min(currentPage * currentPayslipsPerPage, pagination.total || 0)}</span>
                        of <span class="font-medium">${pagination.total || 0}</span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
    `;
    
    // Previous button
    paginationHTML += `
        <button onclick="changePayslipsPage(${currentPage - 1})" 
                ${!hasPrev ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        paginationHTML += `
            <button onclick="changePayslipsPage(${i})" 
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${isActive ? 
                        'z-10 bg-blue-50 border-blue-500 text-blue-600' : 
                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                ${i}
            </button>
        `;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="changePayslipsPage(${currentPage + 1})" 
                ${!hasNext ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    paginationHTML += `
                    </nav>
                </div>
            </div>
        </div>
    `;
    
    paginationEl.innerHTML = paginationHTML;
}

function getPayslipStatusBadgeClass(status) {
    const classes = {
        'Generated': 'bg-blue-100 text-blue-800',
        'Processed': 'bg-green-100 text-green-800',
        'Approved': 'bg-purple-100 text-purple-800',
        'Locked': 'bg-red-100 text-red-800',
        'Error': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

// Pagination functions
function changePayslipsPage(page) {
    if (page < 1) return;
    currentPayslipsPage = page;
    loadRunPayslips(currentRunId, page, currentPayslipsPerPage);
}

function changePayslipsPerPage() {
    const perPageSelect = document.getElementById('payslips-per-page');
    if (perPageSelect) {
        currentPayslipsPerPage = parseInt(perPageSelect.value);
        currentPayslipsPage = 1;
        loadRunPayslips(currentRunId, 1, currentPayslipsPerPage);
    }
}

// Action functions from modal
function processRunFromModal() {
    if (!currentRunId) return;
    
    // Check if there are employees
    if (currentRunData && currentRunData.TotalEmployees === 0) {
        showInlineAlert('Cannot process payroll run: No employees found for this run.', 'warning');
        return;
    }
    
    showEnhancedConfirmationModal(
        'Process Payroll Run',
        `Are you sure you want to process Payroll Run #${currentRunId}?`,
        'This will compute all payslips for eligible employees. This action may take several minutes.',
        'Process',
        'blue',
        () => processPayrollRun(currentRunId)
    );
}

function approveRunFromModal() {
    if (!currentRunId) return;
    
    showEnhancedConfirmationModal(
        'Approve Payroll Run',
        `Are you sure you want to approve Payroll Run #${currentRunId}?`,
        'This will mark the payroll run as approved and ready for locking.',
        'Approve',
        'green',
        () => approvePayrollRun(currentRunId)
    );
}

function lockRunFromModal() {
    if (!currentRunId) return;
    
    showEnhancedConfirmationModal(
        'Lock Payroll Run',
        `Are you sure you want to lock Payroll Run #${currentRunId}?`,
        'This action cannot be undone. The payroll run will be permanently locked.',
        'Lock',
        'red',
        () => lockPayrollRun(currentRunId)
    );
}

// Payslip actions from modal
async function previewPayslipFromModal(payslipId) {
    try {
        // Import the preview function from payslips.js
        const { previewPayslip } = await import('./payslips.js');
        if (previewPayslip) {
            previewPayslip(payslipId);
        } else {
            // Fallback to direct API call
            const response = await fetch(`${REST_API_URL}payslips/${payslipId}/preview`, {
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (response.ok) {
                const result = await response.json();
                showPayslipPreviewModal(result.data);
            } else {
                throw new Error('Failed to load payslip preview');
            }
        }
    } catch (error) {
        console.error('Error previewing payslip:', error);
        showInlineAlert(`Error previewing payslip: ${error.message}`, 'error');
    }
}

async function downloadPayslipFromModal(payslipId) {
    try {
        // Import the download function from payslips.js
        const { downloadPayslipPDF } = await import('./payslips.js');
        if (downloadPayslipPDF) {
            downloadPayslipPDF(payslipId);
        } else {
            // Fallback to direct download
            const response = await fetch(`${REST_API_URL}payslips/${payslipId}/pdf`, {
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `payslip_${payslipId}_${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showInlineAlert('Payslip downloaded successfully!', 'success');
            } else {
                throw new Error('Failed to download payslip');
            }
        }
    } catch (error) {
        console.error('Error downloading payslip:', error);
        showInlineAlert(`Error downloading payslip: ${error.message}`, 'error');
    }
}

// Export function for run payslips
async function exportRunPayslips() {
    if (!currentRunId) return;
    
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${currentRunId}/export`, {
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payroll_run_${currentRunId}_payslips_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showInlineAlert('Payslips exported successfully!', 'success');
        } else {
            throw new Error('Failed to export payslips');
        }
    } catch (error) {
        console.error('Error exporting payslips:', error);
        showInlineAlert(`Error exporting payslips: ${error.message}`, 'error');
    }
}

// Enhanced Confirmation Modal Functions
// showEnhancedConfirmationModal, createEnhancedConfirmationModal, and closeEnhancedConfirmationModal functions removed - using shared utilities from shared-modals.js

// Legacy confirmation modal (kept for compatibility)
function showConfirmationModal(title, message, onConfirm) {
    document.getElementById('confirmation-title').textContent = title;
    document.getElementById('confirmation-message').textContent = message;
    
    const modal = document.getElementById('confirmation-modal');
    const confirmBtn = document.getElementById('confirmation-confirm');
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Add new event listener
    newConfirmBtn.addEventListener('click', () => {
        closeConfirmationModal();
        onConfirm();
    });
    
    modal.classList.remove('hidden');
}

function closeConfirmationModal() {
    const modal = document.getElementById('confirmation-modal');
    modal.classList.add('hidden');
}

// Inline Alert Functions
// showInlineAlert and closeInlineAlert functions removed - using shared utilities from shared-modals.js

// Payslip Preview Modal (fallback)
function showPayslipPreviewModal(payslipData) {
    // Create a simple preview modal if the main one isn't available
    const modalHTML = `
        <div id="payslip-preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Payslip Preview</h3>
                            <button type="button" onclick="closePayslipPreviewModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div id="payslip-preview-content">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('payslip-preview-modal');
    if (existingModal) existingModal.remove();
    
    // Add new modal
    const wrapper = document.createElement('div');
    wrapper.innerHTML = modalHTML;
    document.body.appendChild(wrapper.firstElementChild);
    
    // Show modal
    const modal = document.getElementById('payslip-preview-modal');
    modal.classList.remove('hidden');
}

function closePayslipPreviewModal() {
    const modal = document.getElementById('payslip-preview-modal');
    if (modal) modal.classList.add('hidden');
}

// Update the existing action functions to refresh the modal
async function processPayrollRun(runId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/process`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            showInlineAlert('Payroll run processed successfully!', 'success');
            await loadPayrollRuns();
            // Refresh modal if it's open
            if (currentRunId === runId) {
                await loadRunDetails(runId);
                await loadRunPayslips(runId, currentPayslipsPage, currentPayslipsPerPage);
            }
        } else {
            throw new Error(result.message || 'Failed to process run');
        }
    } catch (error) {
        console.error('Error processing run:', error);
        showInlineAlert(`Error processing run: ${error.message}`, 'error');
    }
}

async function approvePayrollRun(runId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/approve`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            showInlineAlert('Payroll run approved successfully!', 'success');
            await loadPayrollRuns();
            // Refresh modal if it's open
            if (currentRunId === runId) {
                await loadRunDetails(runId);
                await loadRunPayslips(runId, currentPayslipsPage, currentPayslipsPerPage);
            }
        } else {
            throw new Error(result.message || 'Failed to approve run');
        }
    } catch (error) {
        console.error('Error approving run:', error);
        showInlineAlert(`Error approving run: ${error.message}`, 'error');
    }
}

async function lockPayrollRun(runId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/${runId}/lock`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        
        if (result.success) {
            showInlineAlert('Payroll run locked successfully!', 'success');
            await loadPayrollRuns();
            // Refresh modal if it's open
            if (currentRunId === runId) {
                await loadRunDetails(runId);
                await loadRunPayslips(runId, currentPayslipsPage, currentPayslipsPerPage);
            }
        } else {
            throw new Error(result.message || 'Failed to lock run');
        }
    } catch (error) {
        console.error('Error locking run:', error);
        showInlineAlert(`Error locking run: ${error.message}`, 'error');
    }
}

// Global functions for pagination and modal actions
window.changePayslipsPage = changePayslipsPage;
window.changePayslipsPerPage = changePayslipsPerPage;
// closeInlineAlert and closeEnhancedConfirmationModal are available from shared-modals.js
window.closePayslipPreviewModal = closePayslipPreviewModal;


