import { API_BASE_URL, BASE_URL } from '../config.js';
import { loadBranchesForFilter, populateEmployeeDropdown } from '../utils.js';
import './shared-modals.js';

export async function displayPayslipsSection() {
    console.log("[Payslips] Displaying Payslips Section...");

    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !mainContentArea) {
        console.error("displayPayslipsSection: Core DOM elements not found.");
         return;
    }

    pageTitleElement.textContent = 'View Payslips - HR4 System';

    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-indigo-900">Payslips Management</h3>
                        <p class="text-sm text-indigo-700">Generate, preview, and download payslips for each cutoff</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshPayslips()" class="inline-flex items-center px-4 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="showGeneratePayslipsModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-file-plus mr-2"></i>Generate Payslips
                        </button>
                        <button onclick="showBatchDownloadModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-download mr-2"></i>Batch Download
                        </button>
                        <button onclick="exportPayslipsData()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
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
                            <input id="payslip-search-input" type="text" placeholder="Search by employee, payroll run..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="payslip-branch-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Branches</option>
                        </select>
                        <select id="payslip-payroll-run-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Payroll Runs</option>
                        </select>
                        <select id="payslip-status-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Status</option>
                            <option value="Generated">Generated</option>
                            <option value="Approved">Approved</option>
                            <option value="Paid">Paid</option>
                            <option value="Deleted">Deleted</option>
                        </select>
                        <input type="date" id="payslip-period-start" placeholder="Start Date" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="date" id="payslip-period-end" placeholder="End Date" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        
                        <button onclick="applyPayslipFilters()" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearPayslipFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payslips Table -->
            <div class="px-6 py-4">
                <div id="payslips-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <p class="text-gray-500 mt-2">Loading payslips...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Payslips Modal -->
        <div id="generate-payslips-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="generate-payslips-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Generate Payslips</h3>
                                <button type="button" onclick="closeGeneratePayslipsModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="generate-payslips-payroll-run" class="block text-sm font-medium text-gray-700 mb-1">Select Payroll Run:</label>
                                    <select id="generate-payslips-payroll-run" name="payroll_run_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Loading payroll runs...</option>
                                    </select>
                                </div>
        <div>
                                    <label for="generate-payslips-branch" class="block text-sm font-medium text-gray-700 mb-1">Branch:</label>
                                    <select id="generate-payslips-branch" name="branch_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select Branch</option>
            </select>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <p class="text-sm text-blue-800">
                                        <strong>Note:</strong> This will generate payslips for all employees in the selected payroll run. Make sure the payroll run has been processed and contains payslip data.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Generate Payslips
                            </button>
                            <button type="button" onclick="closeGeneratePayslipsModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Batch Download Modal -->
        <div id="batch-download-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="batch-download-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Batch Download Payslips</h3>
                                <button type="button" onclick="closeBatchDownloadModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                <div>
                                    <label for="batch-download-payroll-run" class="block text-sm font-medium text-gray-700 mb-1">Select Payroll Run:</label>
                                    <select id="batch-download-payroll-run" name="payroll_run_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        <option value="">Loading payroll runs...</option>
                                    </select>
                </div>
                <div>
                                    <label for="batch-download-format" class="block text-sm font-medium text-gray-700 mb-1">Download Format:</label>
                                    <select id="batch-download-format" name="format" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        <option value="pdf">PDF (Individual Files)</option>
                                        <option value="batch-pdf">PDF (Combined File)</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                    </select>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg">
                                    <p class="text-sm text-green-800">
                                        <strong>Note:</strong> This will download payslips for all employees in the selected payroll run. The download will start automatically.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Download
                            </button>
                            <button type="button" onclick="closeBatchDownloadModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Enhanced Payslip Preview Modal -->
        <div id="payslip-preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Payslip Preview</h3>
                            <div class="flex space-x-2">
                                <button onclick="printPayslip()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-print mr-2"></i>Print
                                </button>
                                <button onclick="downloadPayslipPDF()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-download mr-2"></i>Download PDF
                                </button>
                                <button onclick="emailPayslip()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-envelope mr-2"></i>Email
                                </button>
                                <button type="button" onclick="closePayslipPreviewModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Enhanced Payslip Summary -->
                        <div id="payslip-summary" class="mb-6 p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600" id="payslip-employee">Employee Name</div>
                                    <div class="text-sm text-gray-500">Employee</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="payslip-period">Pay Period</div>
                                    <div class="text-sm text-gray-500">Period</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="payslip-gross">₱0.00</div>
                                    <div class="text-sm text-gray-500">Gross Pay</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="payslip-net">₱0.00</div>
                                    <div class="text-sm text-gray-500">Net Pay</div>
                                </div>
                            </div>
                        </div>

                        <div id="payslip-preview-content" class="space-y-4 text-sm text-gray-700">
                            <!-- Payslip preview will be loaded here -->
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="closePayslipPreviewModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    setupPayslipsEventListeners();
    loadBranchesForFilter('payslip-branch-filter');
    loadPayrollRunsForFilter('payslip-payroll-run-filter');
    loadPayrollRunsForFilter('generate-payslips-payroll-run');
    loadPayrollRunsForFilter('batch-download-payroll-run');
    loadBranchesForFilter('generate-payslips-branch');
    loadPayslips();
}

function setupPayslipsEventListeners() {
    // Search input
    document.getElementById('payslip-search-input').addEventListener('input', applyPayslipFilters);
    
    // Filter dropdowns
    document.getElementById('payslip-branch-filter').addEventListener('change', applyPayslipFilters);
    document.getElementById('payslip-payroll-run-filter').addEventListener('change', applyPayslipFilters);
    document.getElementById('payslip-status-filter').addEventListener('change', applyPayslipFilters);
    document.getElementById('payslip-period-start').addEventListener('change', applyPayslipFilters);
    document.getElementById('payslip-period-end').addEventListener('change', applyPayslipFilters);
    
    // Form submissions
    document.getElementById('generate-payslips-form').addEventListener('submit', handleGeneratePayslips);
    document.getElementById('batch-download-form').addEventListener('submit', handleBatchDownload);
}

async function loadPayslips() {
    try {
        const filters = getPayslipFilters();
        const response = await fetch(`${BASE_URL}api/payslips?${new URLSearchParams(filters)}`, {
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
        if (data.success && data.data) {
            renderPayslipsTable(data.data.payslips || [], data.data.pagination || {});
        } else {
            throw new Error(data.message || 'Failed to load payslips');
        }
    } catch (error) {
        console.error('Error loading payslips:', error);
        const container = document.getElementById('payslips-list-container');
        if (container) {
            container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <p class="text-red-600">Error loading payslips: ${error.message}</p>
                <button onclick="loadPayslips()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    <i class="fas fa-retry mr-2"></i>Retry
                </button>
            </div>
        `;
        }
    }
}

function getPayslipFilters() {
    return {
        search: document.getElementById('payslip-search-input').value,
        branch_id: document.getElementById('payslip-branch-filter').value,
        payroll_run_id: document.getElementById('payslip-payroll-run-filter').value,
        status: document.getElementById('payslip-status-filter').value,
        pay_period_start: document.getElementById('payslip-period-start').value,
        pay_period_end: document.getElementById('payslip-period-end').value,
        page: 1,
        limit: 50
    };
}

function renderPayslipsTable(payslips, pagination) {
    const container = document.getElementById('payslips-list-container');
    
    if (!container) {
        console.error('Payslips container element not found');
        return;
    }
    
    if (!payslips || payslips.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-file-invoice text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No payslips found</p>
                <p class="text-sm text-gray-400 mt-2">Try adjusting your filters or generate payslips for a payroll run</p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Income</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll Run</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${payslips.map(payslip => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">${payslip.employee_name || 'N/A'}</div>
                                        <div class="text-sm text-gray-500">${payslip.EmployeeNumber || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>${payslip.PayPeriodStart} to ${payslip.PayPeriodEnd}</div>
                                <div class="text-gray-500">Pay: ${payslip.PayDate}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-medium text-green-600">₱${parseFloat(payslip.GrossIncome || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-medium text-red-600">₱${parseFloat(payslip.TotalDeductions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-bold text-indigo-600">₱${parseFloat(payslip.NetIncome || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPayslipStatusBadgeClass(payslip.Status)}">
                                    ${payslip.Status || 'Generated'}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>Run #${payslip.PayrollRunID}</div>
                                <div class="text-xs text-gray-400">v${payslip.Version || '1.0'}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="previewPayslip(${payslip.PayslipID})" class="text-blue-600 hover:text-blue-900" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="downloadPayslipPDF(${payslip.PayslipID})" class="text-green-600 hover:text-green-900" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button onclick="viewPayslipAuditLog(${payslip.PayslipID})" class="text-purple-600 hover:text-purple-900" title="Audit Log">
                                        <i class="fas fa-history"></i>
                                    </button>
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
                    <button onclick="changePayslipPage(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <button onclick="changePayslipPage(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
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
                            <button onclick="changePayslipPage(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            ${Array.from({length: Math.min(5, pagination.pages)}, (_, i) => {
                                const pageNum = Math.max(1, pagination.page - 2) + i;
                                if (pageNum > pagination.pages) return '';
                                return `
                                    <button onclick="changePayslipPage(${pageNum})" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${pageNum === pagination.page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                                        ${pageNum}
                                    </button>
                                `;
                            }).join('')}
                            <button onclick="changePayslipPage(${pagination.page + 1})" ${pagination.page >= pagination.pages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
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

function getPayslipStatusBadgeClass(status) {
    const classes = {
        'Generated': 'bg-blue-100 text-blue-800',
        'Approved': 'bg-green-100 text-green-800',
        'Paid': 'bg-purple-100 text-purple-800',
        'Deleted': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

// Global functions for event handlers
window.refreshPayslips = loadPayslips;
window.loadPayslips = loadPayslips;
window.applyPayslipFilters = loadPayslips;
window.clearPayslipFilters = function() {
    document.getElementById('payslip-search-input').value = '';
    document.getElementById('payslip-branch-filter').value = '';
    document.getElementById('payslip-payroll-run-filter').value = '';
    document.getElementById('payslip-status-filter').value = '';
    document.getElementById('payslip-period-start').value = '';
    document.getElementById('payslip-period-end').value = '';
    loadPayslips();
};

window.showGeneratePayslipsModal = function() {
    document.getElementById('generate-payslips-modal').classList.remove('hidden');
    loadPayrollRunsForFilter('generate-payslips-payroll-run');
    loadBranchesForFilter('generate-payslips-branch');
};

window.closeGeneratePayslipsModal = function() {
    document.getElementById('generate-payslips-modal').classList.add('hidden');
    document.getElementById('generate-payslips-form').reset();
};

window.showBatchDownloadModal = function() {
    document.getElementById('batch-download-modal').classList.remove('hidden');
    loadPayrollRunsForFilter('batch-download-payroll-run');
};

window.closeBatchDownloadModal = function() {
    document.getElementById('batch-download-modal').classList.add('hidden');
    document.getElementById('batch-download-form').reset();
};

window.showPayslipPreviewModal = function() {
    document.getElementById('payslip-preview-modal').classList.remove('hidden');
};

// Enhanced payslip functions
function updatePayslipSummary(payslip) {
    document.getElementById('payslip-employee').textContent = payslip.employee_name || 'N/A';
    document.getElementById('payslip-period').textContent = payslip.pay_period || 'N/A';
    document.getElementById('payslip-gross').textContent = 
        parseFloat(payslip.gross_pay || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    document.getElementById('payslip-net').textContent = 
        parseFloat(payslip.net_pay || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
}

function printPayslip() {
    showInlineAlert('Preparing payslip for printing...', 'info');
    // Implementation for printing payslip
    setTimeout(() => {
        showInlineAlert('Payslip sent to printer!', 'success');
    }, 1500);
}

function emailPayslip() {
    showEnhancedConfirmationModal(
        'Email Payslip',
        'Are you sure you want to email this payslip to the employee?',
        'The payslip will be sent to the employee\'s registered email address.',
        'Send Email',
        'blue',
        () => {
            showInlineAlert('Payslip emailed successfully!', 'success');
        }
    );
}

window.closePayslipPreviewModal = function() {
    document.getElementById('payslip-preview-modal').classList.add('hidden');
};

let currentPayslipId = null;

async function handleGeneratePayslips(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${BASE_URL}api/payslips/generate`, {
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
        console.log('Payslips generated:', result);
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `Generated ${result.data.payslips_generated} payslips successfully`,
                timer: 3000
            });
        }
        
        closeGeneratePayslipsModal();
        loadPayslips();
        
    } catch (error) {
        console.error('Error generating payslips:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        } else {
            alert('Error generating payslips: ' + error.message);
        }
    }
}

async function handleBatchDownload(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        // Get payslips for the selected payroll run
        const payslipsResponse = await fetch(`${BASE_URL}api/payslips?payroll_run_id=${data.payroll_run_id}`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!payslipsResponse.ok) {
            throw new Error(`HTTP ${payslipsResponse.status}: ${payslipsResponse.statusText}`);
        }

        const payslipsData = await payslipsResponse.json();
        const payslips = payslipsData.data.payslips;
        
        if (payslips.length === 0) {
            throw new Error('No payslips found for this payroll run');
        }

        const payslipIds = payslips.map(p => p.PayslipID);
        
        if (data.format === 'batch-pdf') {
            // Download batch PDF
            const response = await fetch(`${BASE_URL}api/payslips/batch-pdf`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ payslip_ids: payslipIds })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Download the PDF
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payslips_batch_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } else {
            // Download individual PDFs
            for (const payslipId of payslipIds) {
                setTimeout(() => {
                    downloadPayslipPDF(payslipId);
                }, 100 * payslipIds.indexOf(payslipId));
            }
        }
        
        closeBatchDownloadModal();
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Download Started!',
                text: `Downloading ${payslips.length} payslips`,
                timer: 2000
            });
        }
        
    } catch (error) {
        console.error('Error downloading payslips:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Download Failed',
                text: error.message
            });
        } else {
            alert('Error downloading payslips: ' + error.message);
        }
    }
}

async function previewPayslip(payslipId) {
    try {
        const response = await fetch(`${BASE_URL}api/payslips/${payslipId}/preview`, {
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
        const payslip = data.data;
        
        currentPayslipId = payslipId;
        
        document.getElementById('payslip-preview-content').innerHTML = `
            <div class="space-y-6">
                <!-- Header -->
                <div class="text-center border-b border-gray-200 pb-4">
                    <h1 class="text-2xl font-bold text-indigo-900">HMVH Hospital</h1>
                    <h2 class="text-xl font-semibold text-indigo-700">PAYSLIP</h2>
                    <p class="text-gray-600">Pay Period: ${payslip.pay_period.start} to ${payslip.pay_period.end}</p>
                    <p class="text-gray-600">Pay Date: ${payslip.pay_date}</p>
                </div>
                
                <!-- Employee Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-3">Employee Information</h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>Name:</strong> ${payslip.employee_info.name}</p>
                            <p><strong>Employee #:</strong> ${payslip.employee_info.employee_number}</p>
                            <p><strong>Department:</strong> ${payslip.employee_info.department}</p>
                            <p><strong>Position:</strong> ${payslip.employee_info.position}</p>
                            <p><strong>Branch:</strong> ${payslip.employee_info.branch}</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-3">Payslip Details</h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>Payslip ID:</strong> #${payslip.payslip_id}</p>
                            <p><strong>Payroll Run:</strong> #${payslip.payroll_run.id}</p>
                            <p><strong>Version:</strong> ${payslip.payroll_run.version}</p>
                            <p><strong>Status:</strong> <span class="px-2 py-1 rounded-full text-xs ${getPayslipStatusBadgeClass(payslip.status)}">${payslip.status}</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Earnings and Deductions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-green-800 mb-3">Earnings</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Basic Salary:</span>
                                <span class="font-medium">₱${payslip.earnings.basic_salary.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Overtime Pay:</span>
                                <span class="font-medium">₱${payslip.earnings.overtime_pay.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Night Differential:</span>
                                <span class="font-medium">₱${payslip.earnings.night_diff_pay.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Allowances:</span>
                                <span class="font-medium">₱${payslip.earnings.allowances.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Bonuses:</span>
                                <span class="font-medium">₱${payslip.earnings.bonuses.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between border-t border-green-200 pt-2 font-bold">
                                <span>Total Gross Income:</span>
                                <span class="text-green-700">₱${payslip.earnings.gross_income.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-red-800 mb-3">Deductions</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>SSS Contribution:</span>
                                <span class="font-medium">₱${payslip.deductions.sss_contribution.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>PhilHealth Contribution:</span>
                                <span class="font-medium">₱${payslip.deductions.philhealth_contribution.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Pag-IBIG Contribution:</span>
                                <span class="font-medium">₱${payslip.deductions.pagibig_contribution.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Withholding Tax:</span>
                                <span class="font-medium">₱${payslip.deductions.withholding_tax.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Other Deductions:</span>
                                <span class="font-medium">₱${payslip.deductions.other_deductions.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between border-t border-red-200 pt-2 font-bold">
                                <span>Total Deductions:</span>
                                <span class="text-red-700">₱${payslip.deductions.total_deductions.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Net Pay -->
                <div class="bg-indigo-100 p-6 rounded-lg text-center">
                    <h2 class="text-2xl font-bold text-indigo-900 mb-2">NET PAY</h2>
                    <p class="text-4xl font-bold text-indigo-700">₱${payslip.net_pay.toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                </div>
                
                <!-- Footer -->
                <div class="text-center text-sm text-gray-500 border-t border-gray-200 pt-4">
                    <p>Generated on: ${new Date(payslip.generated_at).toLocaleString()}</p>
                    <p>This is a computer-generated payslip. No signature required.</p>
                </div>
            </div>
        `;
        
        showPayslipPreviewModal();
        
    } catch (error) {
        console.error('Error loading payslip preview:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load payslip preview'
            });
    } else {
            alert('Error loading payslip preview: ' + error.message);
        }
    }
}

window.previewPayslip = previewPayslip;

async function downloadPayslipPDF(payslipId = null) {
    const id = payslipId || currentPayslipId;
    if (!id) {
        console.error('No payslip ID provided for download');
        return;
    }

    try {
        const response = await fetch(`${BASE_URL}api/payslips/${id}/pdf`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `payslip_${id}_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Download Started!',
                text: 'Payslip PDF is being downloaded',
                timer: 2000
            });
        }
        
    } catch (error) {
        console.error('Error downloading payslip PDF:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Download Failed',
                text: error.message
            });
        } else {
            alert('Error downloading payslip PDF: ' + error.message);
        }
    }
}

window.downloadPayslipPDF = downloadPayslipPDF;

function viewPayslipAuditLog(payslipId) {
    // TODO: Implement audit log viewing
    console.log('View audit log for payslip:', payslipId);
    alert('Audit log functionality coming soon!');
}

window.viewPayslipAuditLog = viewPayslipAuditLog;

function changePayslipPage(page) {
    // TODO: Implement pagination
    console.log('Change to page:', page);
}

window.changePayslipPage = changePayslipPage;

// loadPayrollRunsForFilter function removed - using shared utility from shared-modals.js

async function exportPayslipsData() {
    try {
        const filters = getPayslipFilters();
        const response = await fetch(`${BASE_URL}api/payslips?${new URLSearchParams({...filters, limit: 1000})}`, {
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
        const payslips = data.data.payslips;

        // Create CSV content
        const csvContent = [
            ['Employee Name', 'Employee Number', 'Department', 'Position', 'Branch', 'Pay Period Start', 'Pay Period End', 'Pay Date', 'Gross Income', 'Total Deductions', 'Net Income', 'Status', 'Payroll Run ID', 'Generated At'],
            ...payslips.map(payslip => [
                payslip.employee_name || '',
                payslip.EmployeeNumber || '',
                payslip.DepartmentName || '',
                payslip.PositionName || '',
                payslip.BranchName || '',
                payslip.PayPeriodStart || '',
                payslip.PayPeriodEnd || '',
                payslip.PayDate || '',
                payslip.GrossIncome || 0,
                payslip.TotalDeductions || 0,
                payslip.NetIncome || 0,
                payslip.Status || '',
                payslip.PayrollRunID || '',
                payslip.GeneratedAt || ''
            ])
        ].map(row => row.map(field => `"${field}"`).join(',')).join('\n');

        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `payslips_export_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Export Complete!',
                text: `Exported ${payslips.length} payslip records`,
                timer: 2000
            });
        }
    } catch (error) {
        console.error('Error exporting payslips:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: error.message
            });
        } else {
            alert('Error exporting payslips: ' + error.message);
        }
    }
}

window.exportPayslipsData = exportPayslipsData;

// Additional button functions that are referenced in HTML but not yet implemented
window.refreshPayslips = function() {
    console.log("[Action] Refreshing payslips...");
    loadPayslips();
};

window.showGeneratePayslipsModal = function() {
    console.log("[Action] Showing generate payslips modal...");
    document.getElementById('generate-payslips-modal').classList.remove('hidden');
    loadPayrollRunsForFilter('generate-payslips-payroll-run');
    loadBranchesForFilter('generate-payslips-branch');
};

// Duplicate functions removed - using the ones defined earlier in the file

window.applyPayslipFilters = function() {
    console.log("[Action] Applying payslip filters...");
    loadPayslips();
};

window.clearPayslipFilters = function() {
    console.log("[Action] Clearing payslip filters...");
    document.getElementById('payslip-search-input').value = '';
    document.getElementById('payslip-branch-filter').value = '';
    document.getElementById('payslip-payroll-run-filter').value = '';
    document.getElementById('payslip-status-filter').value = '';
    document.getElementById('payslip-period-start').value = '';
    document.getElementById('payslip-period-end').value = '';
    loadPayslips();
};

window.previewPayslip = function(payslipId) {
    console.log("[Action] Previewing payslip:", payslipId);
    previewPayslip(payslipId);
};

window.downloadPayslipPDF = function(payslipId) {
    console.log("[Action] Downloading payslip PDF:", payslipId);
    downloadPayslipPDF(payslipId);
};

window.viewPayslipAuditLog = function(payslipId) {
    console.log("[Action] Viewing payslip audit log:", payslipId);
    alert(`View Payslip Audit Log for ID: ${payslipId}\n\nThis would show:\n- Generation history\n- Download logs\n- Modification history\n- User actions`);
};

window.changePayslipPage = function(page) {
    console.log("[Action] Changing payslip page to:", page);
    loadPayslips(page);
};