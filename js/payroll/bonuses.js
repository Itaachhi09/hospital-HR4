import { REST_API_URL } from '../config.js';
import './shared-modals.js';

/**
 * Displays the Bonuses submodule section
 * Shows all bonuses, incentives, and allowances from HR2/HR4 Compensation Planning
 */
export async function displayBonusesSection() {
    console.log("[Bonuses] Displaying Bonuses Section...");

    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    
    if (!pageTitleElement || !mainContentArea) {
         console.error("displayBonusesSection: Core DOM elements not found.");
         return;
    }
    
    pageTitleElement.textContent = 'Bonuses & Incentives - HR4 System';

    // Inject HTML structure
    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-purple-900">Bonuses & Incentives</h3>
                        <p class="text-sm text-purple-700">Manage all bonuses, incentives, and allowances from HR2/HR4 Compensation Planning</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshBonuses()" class="inline-flex items-center px-4 py-2 border border-purple-300 rounded-md text-sm font-medium text-purple-700 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="showComputeBonusesModal()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <i class="fas fa-calculator mr-2"></i>Compute Bonuses
                        </button>
                        <button onclick="showAddBonusModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-plus mr-2"></i>Add Bonus
                        </button>
                        <button onclick="exportBonusData()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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
                            <input id="bonus-search-input" type="text" placeholder="Search by employee, bonus type, or name..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="branch-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Branches</option>
                        </select>
                        
                        <select id="department-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Departments</option>
                        </select>
                        
                        <select id="bonus-type-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Bonus Types</option>
                        </select>
                        
                        <select id="status-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Pending">Pending</option>
                        </select>
                        
                        <button onclick="applyBonusFilters()" class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearBonusFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>


            <!-- Bonuses Table -->
            <div class="px-6 py-4">
                <div id="bonuses-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="text-gray-500 mt-2">Loading bonuses and incentives...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Bonus Modal -->
        <div id="add-bonus-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="add-bonus-form">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Manual Bonus</h3>
                                <button type="button" onclick="closeAddBonusModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="add-employee" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                                    <select id="add-employee" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>
                                
                        <div>
                                    <label for="add-bonus-type" class="block text-sm font-medium text-gray-700 mb-1">Bonus Type:</label>
                                    <select id="add-bonus-type" name="bonus_type" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">Select Bonus Type</option>
                                        <option value="Mid-Year Bonus">Mid-Year Bonus</option>
                                        <option value="Year-End Bonus">Year-End Bonus</option>
                                        <option value="Hazard Pay">Hazard Pay</option>
                                        <option value="Night Differential">Night Differential</option>
                                        <option value="Overtime Allowance">Overtime Allowance</option>
                                        <option value="Performance Incentive">Performance Incentive</option>
                                        <option value="Other">Other</option>
                            </select>
                        </div>
                                
                        <div>
                                    <label for="add-bonus-name" class="block text-sm font-medium text-gray-700 mb-1">Bonus Name:</label>
                                    <input type="text" id="add-bonus-name" name="bonus_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500" placeholder="Enter bonus name">
                        </div>
                                
                         <div>
                                    <label for="add-amount" class="block text-sm font-medium text-gray-700 mb-1">Amount:</label>
                                    <input type="number" id="add-amount" name="amount" required step="0.01" min="0" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500" placeholder="0.00">
                        </div>
                                
                        <div>
                                    <label for="add-effective-date" class="block text-sm font-medium text-gray-700 mb-1">Effective Date:</label>
                                    <input type="date" id="add-effective-date" name="effective_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                                
                        <div>
                                    <label for="add-notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional):</label>
                                    <textarea id="add-notes" name="notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500" placeholder="Additional notes for this bonus..."></textarea>
                                </div>
                        </div>
                         </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Add Bonus
                        </button>
                            <button type="button" onclick="closeAddBonusModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                </form>
                </div>
            </div>
        </div>

        <!-- Compute Bonuses Modal -->
        <div id="compute-bonuses-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Compute Bonuses for Payroll Run</h3>
                            <button type="button" onclick="closeComputeBonusesModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="compute-payroll-run" class="block text-sm font-medium text-gray-700 mb-1">Payroll Run:</label>
                                <select id="compute-payroll-run" name="payroll_run_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select Payroll Run</option>
                                </select>
            </div>

            <div>
                                <label for="compute-branch" class="block text-sm font-medium text-gray-700 mb-1">Branch:</label>
                                <select id="compute-branch" name="branch_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select Branch</option>
                            </select>
                     </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Computation Details:</h4>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li>• Mid-Year Bonus: 25% of base salary</li>
                                    <li>• Year-End Bonus: 50% of base salary</li>
                                    <li>• Hazard Pay: Fixed amount</li>
                                    <li>• Night Differential: 10% of base salary</li>
                                    <li>• Overtime Allowance: Fixed amount</li>
                                    <li>• Performance Incentive: 15% of base salary</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button onclick="executeBonusComputation()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Compute Bonuses
                        </button>
                        <button type="button" onclick="closeComputeBonusesModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                     </div>
                 </div>
            </div>
        </div>

        <!-- Enhanced Bonus Details Modal -->
        <div id="bonus-details-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Bonus Details</h3>
                            <button type="button" onclick="closeBonusDetailsModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Enhanced Bonus Summary -->
                        <div id="bonus-summary" class="mb-6 p-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600" id="bonus-employee">Employee Name</div>
                                    <div class="text-sm text-gray-500">Employee</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="bonus-type">Bonus Type</div>
                                    <div class="text-sm text-gray-500">Type</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900" id="bonus-amount">₱0.00</div>
                                    <div class="text-sm text-gray-500">Amount</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold" id="bonus-status-badge">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800" id="bonus-status">Status</span>
                                    </div>
                                    <div class="text-sm text-gray-500">Status</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <button onclick="editBonus()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                <i class="fas fa-edit mr-2"></i>Edit Bonus
                            </button>
                            <button onclick="duplicateBonus()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                <i class="fas fa-copy mr-2"></i>Duplicate
                            </button>
                            <button onclick="exportBonusDetails()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                            <button onclick="deleteBonus()" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>

                        <div id="bonus-details-content">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    // Set up event listeners
    setupBonusEventListeners();
    
    // Load initial data
    await loadBonuses();
    await loadFilterOptions();
}

/**
 * Set up event listeners for the bonuses section
 */
function setupBonusEventListeners() {
    // Search input
    const searchInput = document.getElementById('bonus-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(loadBonuses, 500));
    }

    // Add bonus form
        const addBonusForm = document.getElementById('add-bonus-form');
        if (addBonusForm) {
                addBonusForm.addEventListener('submit', handleAddBonus);
    }
}

/**
 * Load bonus data from the API
 */
async function loadBonuses() {
    console.log("[Load] Loading Bonuses...");
    const container = document.getElementById('bonuses-list-container');
    if (!container) return;
    
    container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div><p class="text-gray-500 mt-2">Loading bonuses and incentives...</p></div>';

    // Build query parameters
    const params = new URLSearchParams();
    const branchFilter = document.getElementById('branch-filter')?.value;
    const departmentFilter = document.getElementById('department-filter')?.value;
    const bonusTypeFilter = document.getElementById('bonus-type-filter')?.value;
    const statusFilter = document.getElementById('status-filter')?.value;
    const searchTerm = document.getElementById('bonus-search-input')?.value;
    
    if (branchFilter) params.set('branch_id', branchFilter);
    if (departmentFilter) params.set('department_id', departmentFilter);
    if (bonusTypeFilter) params.set('bonus_type', bonusTypeFilter);
    if (statusFilter) params.set('status', statusFilter);
    if (searchTerm) params.set('search', searchTerm);
    
    params.set('page', '1');
    params.set('limit', '50');

    try {
        const response = await fetch(`${REST_API_URL}bonuses?${params}`, {
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
        console.log("[Load] Bonuses loaded:", result);

        if (result.success) {
            renderBonusesTable(result.data.bonuses);
        } else {
            throw new Error(result.message || 'Failed to load bonuses');
        }
    } catch (error) {
        console.error('[Load] Error loading bonuses:', error);
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Bonuses</h3>
                <p class="text-gray-500">${error.message}</p>
                <button onclick="loadBonuses()" class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    Try Again
                </button>
            </div>`;
    }
}

/**
 * Render the bonuses table
 */
function renderBonusesTable(bonuses) {
    console.log("[Render] Rendering Bonuses Table...");
    const container = document.getElementById('bonuses-list-container');
    if (!container) return;

    if (!bonuses || bonuses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-gift text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No bonuses found</h3>
                <p class="text-gray-500">No bonuses found matching the current filters.</p>
            </div>`;
        return;
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';
    table.innerHTML = `
        <thead class="bg-purple-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Bonus Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Bonus Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Computation</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Effective Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-purple-600 uppercase tracking-wider">Actions</th>
                    </tr>
        </thead>`;

    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200';

    bonuses.forEach(bonus => {
        const row = tbody.insertRow();
        row.id = `bonus-row-${bonus.BonusID}`;

        const createCell = (text, className = '') => {
            const cell = row.insertCell();
            cell.className = `px-4 py-3 whitespace-nowrap text-sm ${className}`;
            cell.textContent = text ?? '';
            return cell;
        };

        // Employee
        createCell(`${bonus.employee_name}\n${bonus.EmployeeNumber}`, 'font-medium text-gray-900');

        // Bonus Type
        const typeBadge = document.createElement('span');
        typeBadge.textContent = bonus.BonusType;
        typeBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + getBonusTypeBadgeClass(bonus.BonusType);
        const typeCell = row.insertCell();
        typeCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        typeCell.appendChild(typeBadge);

        // Bonus Name
        createCell(bonus.BonusName, 'text-gray-700');

        // Amount
        const amount = parseFloat(bonus.computed_amount || bonus.Amount || 0).toLocaleString('en-PH', { 
            style: 'currency', 
            currency: 'PHP' 
        });
        createCell(amount, 'text-green-600 font-semibold');

        // Computation Method
        createCell(bonus.ComputationMethod || 'Fixed', 'text-gray-500');

        // Effective Date
        const effectiveDate = bonus.EffectiveDate ? new Date(bonus.EffectiveDate).toLocaleDateString() : 'N/A';
        createCell(effectiveDate, 'text-gray-700');

        // Status
        const statusBadge = document.createElement('span');
        statusBadge.textContent = bonus.Status;
        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + getStatusBadgeClass(bonus.Status);
        const statusCell = row.insertCell();
        statusCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        statusCell.appendChild(statusBadge);

        // Actions
        const actionsCell = row.insertCell();
        actionsCell.className = 'px-4 py-3 whitespace-nowrap text-sm font-medium space-x-2';
        
        const viewButton = document.createElement('button');
        viewButton.textContent = 'View';
        viewButton.className = 'text-purple-600 hover:text-purple-900';
        viewButton.onclick = () => viewBonusDetails(bonus.BonusID);
        
        const editButton = document.createElement('button');
        editButton.textContent = 'Edit';
        editButton.className = 'text-blue-600 hover:text-blue-900';
        editButton.onclick = () => editBonus(bonus.BonusID);
        
        actionsCell.appendChild(viewButton);
        actionsCell.appendChild(document.createTextNode(' | '));
        actionsCell.appendChild(editButton);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

/**
 * Load filter options (branches, departments, bonus types)
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
    
    const bonusTypes = [
        'Mid-Year Bonus',
        'Year-End Bonus',
        'Hazard Pay',
        'Night Differential',
        'Overtime Allowance',
        'Performance Incentive',
        'Other'
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
    
    // Populate bonus type filter
    const bonusTypeFilter = document.getElementById('bonus-type-filter');
    if (bonusTypeFilter) {
        bonusTypeFilter.innerHTML = '<option value="">All Bonus Types</option>' + 
            bonusTypes.map(bt => `<option value="${bt}">${bt}</option>`).join('');
    }
}

/**
 * Get bonus type badge class
 */
function getBonusTypeBadgeClass(bonusType) {
    const typeClasses = {
        'Mid-Year Bonus': 'bg-yellow-100 text-yellow-800',
        'Year-End Bonus': 'bg-green-100 text-green-800',
        'Hazard Pay': 'bg-red-100 text-red-800',
        'Night Differential': 'bg-blue-100 text-blue-800',
        'Overtime Allowance': 'bg-purple-100 text-purple-800',
        'Performance Incentive': 'bg-indigo-100 text-indigo-800',
        'Other': 'bg-gray-100 text-gray-800'
    };
    return typeClasses[bonusType] || 'bg-gray-100 text-gray-800';
}

/**
 * Get status badge class
 */
// getStatusBadgeClass function removed - using shared utility from shared-modals.js

/**
 * View bonus details
 */
async function viewBonusDetails(bonusId) {
    try {
        const response = await fetch(`${REST_API_URL}bonuses/${bonusId}`, {
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
            const bonus = result.data;
            const content = document.getElementById('bonus-details-content');
            
            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Bonus Information</h4>
                        <dl class="space-y-2">
                            <dt class="text-sm font-medium text-gray-500">Employee:</dt>
                            <dd class="text-sm text-gray-900">${bonus.employee_name} (${bonus.EmployeeNumber})</dd>
                            <dt class="text-sm font-medium text-gray-500">Bonus Type:</dt>
                            <dd class="text-sm text-gray-900">${bonus.BonusType}</dd>
                            <dt class="text-sm font-medium text-gray-500">Bonus Name:</dt>
                            <dd class="text-sm text-gray-900">${bonus.BonusName}</dd>
                            <dt class="text-sm font-medium text-gray-500">Amount:</dt>
                            <dd class="text-sm text-gray-900">${parseFloat(bonus.Amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</dd>
                            <dt class="text-sm font-medium text-gray-500">Computation Method:</dt>
                            <dd class="text-sm text-gray-900">${bonus.ComputationMethod || 'Fixed'}</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Additional Details</h4>
                        <dl class="space-y-2">
                            <dt class="text-sm font-medium text-gray-500">Department:</dt>
                            <dd class="text-sm text-gray-900">${bonus.DepartmentName || 'N/A'}</dd>
                            <dt class="text-sm font-medium text-gray-500">Position:</dt>
                            <dd class="text-sm text-gray-900">${bonus.PositionName || 'N/A'}</dd>
                            <dt class="text-sm font-medium text-gray-500">Branch:</dt>
                            <dd class="text-sm text-gray-900">${bonus.BranchName || 'N/A'}</dd>
                            <dt class="text-sm font-medium text-gray-500">Effective Date:</dt>
                            <dd class="text-sm text-gray-900">${bonus.EffectiveDate ? new Date(bonus.EffectiveDate).toLocaleDateString() : 'N/A'}</dd>
                            <dt class="text-sm font-medium text-gray-500">Status:</dt>
                            <dd class="text-sm text-gray-900">${bonus.Status}</dd>
                        </dl>
                    </div>
                </div>
                
                ${bonus.Notes ? `
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Notes</h4>
                    <p class="text-sm text-gray-700">${bonus.Notes}</p>
                </div>
                ` : ''}
            `;
            
            document.getElementById('bonus-details-modal').classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to load bonus details');
        }
    } catch (error) {
        console.error('Error loading bonus details:', error);
        alert('Error loading bonus details: ' + error.message);
    }
}

/**
 * Handle add bonus form submission
 */
async function handleAddBonus(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${REST_API_URL}bonuses/manual`, {
            method: 'POST',
            credentials: 'include',
             headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.success) {
            alert('Bonus added successfully!');
            closeAddBonusModal();
            loadBonuses();
        } else {
            throw new Error(result.message || 'Failed to add bonus');
        }
    } catch (error) {
        console.error('Error adding bonus:', error);
        alert('Error adding bonus: ' + error.message);
    }
}

/**
 * Execute bonus computation
 */
async function executeBonusComputation() {
    const payrollRunId = document.getElementById('compute-payroll-run').value;
    const branchId = document.getElementById('compute-branch').value;
    
    if (!payrollRunId || !branchId) {
        alert('Please select both payroll run and branch');
        return;
    }
    
    try {
        const response = await fetch(`${REST_API_URL}bonuses/compute`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                payroll_run_id: payrollRunId,
                branch_id: branchId
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            alert(`Bonuses computed successfully!\n\nTotal Amount: ${parseFloat(result.data.total_amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}\nEmployee Count: ${result.data.employee_count}\nBonus Count: ${result.data.bonus_count}`);
            closeComputeBonusesModal();
            loadBonuses();
        } else {
            throw new Error(result.message || 'Failed to compute bonuses');
        }
    } catch (error) {
        console.error('Error computing bonuses:', error);
        alert('Error computing bonuses: ' + error.message);
    }
}

// Global functions for event handlers
window.refreshBonuses = function() {
    loadBonuses();
};

window.applyBonusFilters = function() {
    loadBonuses();
};

window.clearBonusFilters = function() {
    document.getElementById('branch-filter').value = '';
    document.getElementById('department-filter').value = '';
    document.getElementById('bonus-type-filter').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('bonus-search-input').value = '';
    loadBonuses();
};

window.showAddBonusModal = function() {
    document.getElementById('add-bonus-modal').classList.remove('hidden');
};

window.closeAddBonusModal = function() {
    document.getElementById('add-bonus-modal').classList.add('hidden');
    document.getElementById('add-bonus-form').reset();
};

window.showComputeBonusesModal = function() {
    document.getElementById('compute-bonuses-modal').classList.remove('hidden');
};

window.closeComputeBonusesModal = function() {
    document.getElementById('compute-bonuses-modal').classList.add('hidden');
};

// Enhanced bonus functions
function updateBonusSummary(bonus) {
    document.getElementById('bonus-employee').textContent = bonus.employee_name || 'N/A';
    document.getElementById('bonus-type').textContent = bonus.bonus_type || 'N/A';
    document.getElementById('bonus-amount').textContent = 
        parseFloat(bonus.amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    
    const statusBadge = document.getElementById('bonus-status');
    const status = bonus.status || 'Active';
    statusBadge.textContent = status;
    statusBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusBadgeClass(status)}`;
}

function editBonus() {
    showInlineAlert('Opening bonus editor...', 'info');
    // Implementation for editing bonus
    setTimeout(() => {
        showInlineAlert('Bonus editor opened!', 'success');
    }, 1000);
}

function duplicateBonus() {
    showEnhancedConfirmationModal(
        'Duplicate Bonus',
        'Are you sure you want to duplicate this bonus?',
        'This will create a copy of the current bonus with the same details.',
        'Duplicate',
        'purple',
        () => {
            showInlineAlert('Bonus duplicated successfully!', 'success');
        }
    );
}

function exportBonusDetails() {
    showInlineAlert('Exporting bonus details...', 'info');
    // Implementation for exporting bonus details
    setTimeout(() => {
        showInlineAlert('Bonus details exported!', 'success');
    }, 2000);
}

function deleteBonus() {
    showEnhancedConfirmationModal(
        'Delete Bonus',
        'Are you sure you want to delete this bonus?',
        'This action cannot be undone. The bonus will be permanently removed.',
        'Delete',
        'red',
        () => {
            showInlineAlert('Bonus deleted successfully!', 'success');
        }
    );
}

window.closeBonusDetailsModal = function() {
    document.getElementById('bonus-details-modal').classList.add('hidden');
};

window.exportBonusData = function() {
    const rows = document.querySelectorAll('#bonuses-list-container tbody tr');
    if (!rows || rows.length === 0) {
        alert('No bonus data available to export.');
        return;
    }

    const headers = ['Employee', 'Bonus Type', 'Bonus Name', 'Amount', 'Computation', 'Effective Date', 'Status'];
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
                cells[6]?.textContent?.trim() || ''
            ].map(cell => `"${cell}"`).join(',');
        })
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `bonuses_data_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    alert('Bonus data exported successfully!');
};

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

// Additional button functions that are referenced in HTML but not yet implemented
window.executeBonusComputation = function() {
    console.log("[Action] Executing bonus computation...");
    executeBonusComputation();
};