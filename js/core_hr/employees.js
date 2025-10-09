/**
 * Core HR - Employees Module (READ-ONLY VIEWER)
 * v3.0 - Refactored for HR Core integration - Read-only data visualization
 * v2.2 - Added View Details modal, Add New button, and placeholder Edit/Deactivate buttons.
 * v2.1 - Updated to display more comprehensive employee details in the table.
 * v2.0 - Refined rendering functions for XSS protection.
 * 
 * Purpose: Display consolidated employee data from HR1-HR3 systems
 * - No CRUD operations (add/update/delete)
 * - Read-only integration hub for employee data visualization
 */
import { API_BASE_URL } from '../utils.js'; // Import base URL

// Store employee data globally in this module for modal use
let allEmployeesData = [];
// Mappings for quick filters
let departmentNameToId = {};
let departmentIdToName = {};
// Client-side sort state
let currentSort = { key: null, dir: 'asc' };
// Debounce timer for search-as-you-type
let searchDebounceTimer = null;
let employeeDetailModal = null;
let employeeDetailModalOverlay = null;
let employeeDetailModalCloseBtn = null;
let employeeDetailModalCloseBtnFooter = null;

/**
 * Initializes modal elements if not already done.
 */
function initializeEmployeeModalElements() {
    if (!employeeDetailModal) {
        employeeDetailModal = document.getElementById('employee-detail-modal');
        employeeDetailModalOverlay = document.getElementById('modal-overlay-employee');
        employeeDetailModalCloseBtn = document.getElementById('modal-close-btn-employee');
        employeeDetailModalCloseBtnFooter = document.getElementById('modal-close-btn-employee-footer');

        if (employeeDetailModalCloseBtn) {
            employeeDetailModalCloseBtn.addEventListener('click', closeEmployeeDetailModal);
        }
        if (employeeDetailModalOverlay) {
            employeeDetailModalOverlay.addEventListener('click', closeEmployeeDetailModal);
        }
        if (employeeDetailModalCloseBtnFooter) {
            employeeDetailModalCloseBtnFooter.addEventListener('click', closeEmployeeDetailModal);
        }
    }
}


/**
 * Displays the Employee Section.
 * Fetches employee data and renders it in a table.
 */
export async function displayEmployeeSection() {
    console.log("[Display] Displaying Employee Section...");
    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("displayEmployeeSection: Core DOM elements not found.");
        return;
    }
    pageTitleElement.textContent = 'Employee Directory (Read-Only)';
    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header Section with Total Count and Search -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-users text-gray-500"></i>
                            <span class="text-sm text-gray-600">Total Employee:</span>
                            <span class="text-sm font-semibold text-gray-900" id="total-employee-count">Loading...</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input id="emp-search-input" type="text" placeholder="Search employee" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
                        <button id="emp-filter-toggle" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter mr-2"></i>
                            Filter
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters (Initially Hidden) -->
                <div id="advanced-filters" class="hidden mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select id="emp-filter-dept" class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">All Departments</option>
                    </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="emp-filter-status" class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Suspended">Suspended</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Retired">Retired</option>
                    </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                            <select id="emp-filter-type" class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="Regular">Regular</option>
                        <option value="Contractual">Contractual</option>
                        <option value="Probationary">Probationary</option>
                        <option value="Consultant">Consultant</option>
                        <option value="Part-time">Part-time</option>
                    </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                            <input id="emp-filter-title" type="text" placeholder="Job title contains" 
                                   class="w-full p-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 mt-4">
                        <button id="emp-apply-filters" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Apply Filters
                        </button>
                        <button id="emp-clear-filters" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Clear
                    </button>
                    </div>
                </div>
            </div>
            
            <!-- Employee Table Container -->
            <div id="employee-list-container" class="overflow-x-auto">
                <p class="text-center py-4">Loading employees...</p>
            </div>
        </div>`;
    
    requestAnimationFrame(async () => {
        initializeEmployeeModalElements(); // Ensure modal elements are ready
        const exportBtn = document.getElementById('export-employees-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                exportEmployeeData();
            });
        }
        // Populate departments dropdown
        await populateDepartmentsFilter();
        // Attach filter toggle listener
        attachFilterToggleListener();
        // Wire filter actions
        const applyBtn = document.getElementById('emp-apply-filters');
        const clearBtn = document.getElementById('emp-clear-filters');
        const searchInput = document.getElementById('emp-search-input');
        if (applyBtn) applyBtn.addEventListener('click', () => loadEmployees(buildCurrentFilterParams()));
        if (clearBtn) clearBtn.addEventListener('click', () => { resetFilters(); loadEmployees(); });
        if (searchInput) {
            // Enter to search
            searchInput.addEventListener('keydown', (e)=>{ if (e.key === 'Enter') loadEmployees(buildCurrentFilterParams()); });
            // Debounced search-as-you-type
            searchInput.addEventListener('input', () => {
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => loadEmployees(buildCurrentFilterParams()), 350);
            });
        }
        // Global quick-filter listeners (dept/status clicks inside the table)
        attachQuickFilterListeners();
        await loadEmployees();
    });
}

/**
 * Fetches employee data from the API.
 */
async function loadEmployees(params = null, retryCount = 0) {
    console.log("[Load] Loading Employees...");
    const container = document.getElementById('employee-list-container');
    if (!container) {
         // element might not be in DOM yet due to navigation or async rendering
         if (retryCount < 3) {
             // wait a bit and retry silently
             await new Promise(r => setTimeout(r, 120));
             return loadEmployees(params, retryCount + 1);
         }
         // final abort with a non-fatal log
         console.debug('[Employees] employee-list-container still not found after retries; aborting load.');
         return;
    };
    try {
        let url = `${API_BASE_URL}get_employees.php`;
        if (params) {
            const qs = new URLSearchParams(params).toString();
            url += `?${qs}`;
        }
        const response = await fetch(url);
        if (!response.ok) {
            // If the server signals authentication is required, redirect to login
            if (response.status === 401) {
                console.info('[loadEmployees] Unauthorized (401). Redirecting to login.');
                window.location.href = 'index.php';
                return;
            }
            // Fallback to client-side filter if we already have data
            if (allEmployeesData && allEmployeesData.length > 0) {
                console.warn('[loadEmployees] Server returned', response.status, '— falling back to client-side filtering');
                const filtered = filterEmployeesClient(allEmployeesData, params || {});
                renderEmployeeTable(filtered);
                return;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const employees = await response.json();
        if (employees.error) {
             // If backend returned an authentication error message, redirect to login
             if (typeof employees.error === 'string' && /auth/i.test(employees.error)) {
                 console.info('[loadEmployees] API returned authentication error. Redirecting to login.');
                 window.location.href = 'index.php';
                 return;
             }
             console.error("[loadEmployees] API returned error:", employees.error);
             container.innerHTML = `<p class="text-red-500 text-center py-4">Error: ${employees.error}</p>`;
        } else {
             allEmployeesData = employees; // Store for modal
             renderEmployeeTable(employees);
        }
    } catch (error) {
        console.error('[loadEmployees] Error loading employees:', error);
        // Final fallback to cached data if present
        if (allEmployeesData && allEmployeesData.length > 0) {
            const paramsObj = params || {};
            const fallback = filterEmployeesClient(allEmployeesData, paramsObj);
            const banner = document.createElement('div');
            banner.className = 'p-3 mb-2 text-sm text-orange-700 bg-orange-50 border border-orange-200 rounded';
            banner.textContent = 'Network/server issue during search. Showing cached results.';
            container.innerHTML = '';
            container.appendChild(banner);
            renderEmployeeTable(fallback);
        } else {
        container.innerHTML = `<p class="text-red-500 text-center py-4">Could not load employee data. ${error.message}</p>`;
        }
    }
}

/**
 * Read current filters from the UI and return as query params
 */
function buildCurrentFilterParams(){
    const params = {};
    const q = document.getElementById('emp-search-input')?.value?.trim();
    const dept = document.getElementById('emp-filter-dept')?.value;
    const status = document.getElementById('emp-filter-status')?.value;
    const type = document.getElementById('emp-filter-type')?.value;
    const title = document.getElementById('emp-filter-title')?.value?.trim();
    if (q) params.search = q;
    if (dept) params.department_id = dept;
    if (status) params.employment_status = status;
    if (type) params.employment_type = type;
    if (title) params.job_title = title;
    return params;
}

/**
 * Reset filters to default state
 */
function resetFilters(){
    const ids = ['emp-search-input','emp-filter-dept','emp-filter-status','emp-filter-type','emp-filter-title'];
    ids.forEach(id=>{ const el = document.getElementById(id); if (!el) return; if (el.tagName === 'SELECT') el.value = ''; else el.value = ''; });
}

/**
 * Client-side filtering fallback when API errors occur
 */
function filterEmployeesClient(sourceEmployees, params){
    if (!Array.isArray(sourceEmployees)) return [];
    const search = (params?.search || '').toString().toLowerCase();
    const deptId = params?.department_id ? String(params.department_id) : '';
    const empStatus = (params?.employment_status || '').toString().toLowerCase();
    const empType = (params?.employment_type || '').toString().toLowerCase();
    const jobTitle = (params?.job_title || '').toString().toLowerCase();
    return sourceEmployees.filter(emp => {
        let ok = true;
        if (search) {
            const name = `${emp.FirstName || ''} ${emp.LastName || ''}`.toLowerCase();
            const email = (emp.Email || '').toLowerCase();
            const title = (emp.JobTitle || '').toLowerCase();
            ok = ok && (name.includes(search) || email.includes(search) || title.includes(search));
        }
        if (deptId) ok = ok && String(emp.DepartmentID || '') === deptId;
        if (empStatus) {
            const s = (emp.EmploymentStatus || emp.Status || (emp.IsActive == 1 ? 'Active' : 'Inactive')).toString().toLowerCase();
            ok = ok && s.includes(empStatus);
        }
        if (empType) ok = ok && (emp.EmploymentType || '').toString().toLowerCase().includes(empType);
        if (jobTitle) ok = ok && (emp.JobTitle || '').toString().toLowerCase().includes(jobTitle);
        return ok;
    });
}

/**
 * Populate department dropdown options from API
 */
async function populateDepartmentsFilter(){
    const select = document.getElementById('emp-filter-dept');
    if (!select) return;
    try{
        const res = await fetch(`${API_BASE_URL}get_org_structure.php`);
        const data = await res.json();
        if (Array.isArray(data)){
            data.forEach(d=>{
                if (!d.DepartmentID || !d.DepartmentName) return;
                // Build lookup tables for quick filtering by name/id
                departmentNameToId[d.DepartmentName] = d.DepartmentID;
                departmentIdToName[d.DepartmentID] = d.DepartmentName;
                const opt = document.createElement('option');
                opt.value = d.DepartmentID;
                opt.textContent = d.DepartmentName;
                select.appendChild(opt);
            });
        }
    }catch(err){ console.warn('Failed to load departments for filter', err); }
}

 /**
 * Renders the employee data into an HTML table.
 * @param {Array} employees - An array of employee objects.
 */
function renderEmployeeTable(employees) {
    console.log("[Render] Rendering Employee Table...");
    const container = document.getElementById('employee-list-container');
    if (!container) return;

    // Update total count
    const totalCountElement = document.getElementById('total-employee-count');
    if (totalCountElement) {
        totalCountElement.textContent = `${employees.length} employees`;
    }

    container.innerHTML = '';

    if (!employees || employees.length === 0) {
        const noDataMessage = document.createElement('div');
        noDataMessage.className = 'text-center py-12';
        noDataMessage.innerHTML = `
            <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-500 text-lg">No employees found</p>
            <p class="text-gray-400 text-sm mt-2">Try adjusting your search or filter criteria</p>
        `;
        container.appendChild(noDataMessage);
        return;
    }

    // If there is an active sort, apply client-side sort
    let rowsToRender = Array.isArray(employees) ? employees.slice() : [];
    // Normalize derived fields used for sorting (e.g., Status)
    rowsToRender.forEach(r => {
        if (typeof r.Status === 'undefined') {
            r.Status = (r.IsActive == 1) ? 'Active' : 'Inactive';
        }
    });
    if (currentSort && currentSort.key) {
        const dir = currentSort.dir === 'desc' ? -1 : 1;
        rowsToRender.sort((a, b) => {
            const va = (a[currentSort.key] || '').toString().toLowerCase();
            const vb = (b[currentSort.key] || '').toString().toLowerCase();
            if (va < vb) return -1 * dir;
            if (va > vb) return 1 * dir;
            // Special-case for name to make it stable by first name then last name
            if (currentSort.key === 'LastName') {
                const fa = (a.FirstName || '').toLowerCase();
                const fb = (b.FirstName || '').toLowerCase();
                if (fa < fb) return -1 * dir;
                if (fa > fb) return 1 * dir;
            }
            return 0;
        });
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';

    // Table Header
    const thead = table.createTHead();
    thead.className = 'bg-gray-50';
    const headerRow = thead.insertRow();
    
    // Header cells with sort indicators
    const headers = [
        { text: '', class: 'w-4' }, // Checkbox column
        { text: 'Name', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100', sortKey: 'LastName' },
        { text: 'Department', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100', sortKey: 'DepartmentName' },
        { text: 'Position', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100', sortKey: 'JobTitle' },
        { text: 'Status', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100', sortKey: 'Status' },
        { text: 'Join Date', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100', sortKey: 'HireDate' },
        { text: 'Actions', class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider' }
    ];
    
    headers.forEach(header => {
        const th = document.createElement('th');
        th.scope = 'col';
        th.className = header.class;
        th.innerHTML = header.text + (header.text && header.text !== '' && header.text !== 'Actions' ? ' <i class="fas fa-sort text-gray-400"></i>' : '');
        if (header.sortKey) {
            th.dataset.sortKey = header.sortKey;
        }
        headerRow.appendChild(th);
    });

    // Table Body
    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200 employee-actions-container';

    rowsToRender.forEach(emp => {
        const row = tbody.insertRow();
        row.id = `emp-row-${emp.EmployeeID}`;
        row.className = 'hover:bg-gray-50 transition-colors duration-150';

        // Checkbox column
        const checkboxCell = row.insertCell();
        checkboxCell.className = 'px-6 py-4 whitespace-nowrap w-4';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded';
        checkbox.dataset.employeeId = emp.EmployeeID;
        checkboxCell.appendChild(checkbox);

        // Name column with profile picture
        const nameCell = row.insertCell();
        nameCell.className = 'px-6 py-4 whitespace-nowrap';
        const fullName = `${emp.FirstName || ''} ${emp.MiddleName || ''} ${emp.LastName || ''} ${emp.Suffix || ''}`.replace(/\s+/g, ' ').trim();

        nameCell.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-medium text-sm">
                        ${(emp.FirstName || '').charAt(0)}${(emp.LastName || '').charAt(0)}
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">${fullName}</div>
                    <div class="text-sm text-gray-500">${emp.Email || ''}</div>
                </div>
            </div>
        `;

        // Department column
        const deptCell = row.insertCell();
        deptCell.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
        const deptName = emp.DepartmentName || '-';
        const deptId = emp.DepartmentID || departmentNameToId[deptName];
        // Make department clickable to apply quick filter
        if (deptId) {
            deptCell.innerHTML = `<button type="button" class="text-blue-600 hover:underline dept-filter-link" data-dept-id="${deptId}">${deptName}</button>`;
        } else {
            deptCell.textContent = deptName;
        }

        // Position column
        const positionCell = row.insertCell();
        positionCell.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
        positionCell.textContent = emp.JobTitle || '-';

        // Status column with colored badge
        const statusCell = row.insertCell();
        statusCell.className = 'px-6 py-4 whitespace-nowrap';
        const status = emp.Status || (emp.IsActive == 1 ? 'Active' : 'Inactive');
        let statusClass = 'bg-gray-100 text-gray-800';
        if (status === 'Active') statusClass = 'bg-green-100 text-green-800';
        else if (status === 'On Leave') statusClass = 'bg-yellow-100 text-yellow-800';
        else if (status === 'Inactive' || status === 'Terminated') statusClass = 'bg-red-100 text-red-800';
        
        statusCell.innerHTML = `
            <button type="button" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass} status-filter-chip" data-status="${status}">
                ${status}
            </button>
        `;

        // Join Date column
        const joinDateCell = row.insertCell();
        joinDateCell.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-500';
        joinDateCell.textContent = emp.HireDateFormatted || emp.HireDate || '-';

        // Actions column with dropdown menu
        const actionsCell = row.insertCell();
        actionsCell.className = 'px-6 py-4 whitespace-nowrap text-sm font-medium';
        
        const actionsDropdown = document.createElement('div');
        actionsDropdown.className = 'relative inline-block text-left';
        actionsDropdown.innerHTML = `
            <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full p-1 toggle-actions-btn" data-employee-id="${emp.EmployeeID}">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div id="actions-menu-${emp.EmployeeID}" class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1">
                    <button type="button" data-employee-id="${emp.EmployeeID}" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 view-employee-btn">
                        <i class="fas fa-eye mr-3 text-gray-400"></i>
                        View Profile
                    </button>
                    <button type="button" data-employee-id="${emp.EmployeeID}" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 edit-employee-btn">
                        <i class="fas fa-edit mr-3 text-gray-400"></i>
                        Edit Details
                    </button>
                    <button type="button" data-employee-id="${emp.EmployeeID}" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 view-performance-btn">
                        <i class="fas fa-chart-bar mr-3 text-gray-400"></i>
                        View Performance
                    </button>
                    <button type="button" data-employee-id="${emp.EmployeeID}" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 delete-employee-btn">
                        <i class="fas fa-trash mr-3 text-red-400"></i>
                        Delete Employee
                    </button>
                </div>
            </div>
        `;
        actionsCell.appendChild(actionsDropdown);
    });

    container.appendChild(table);
    attachEmployeeActionListeners(); // Attach listeners after table is rendered
    attachSortListeners(table); // Enable client-side sorting
}

/**
 * Attaches event listeners for employee actions.
 */
function attachEmployeeActionListeners() {
    const container = document.querySelector('.employee-actions-container');
    if (container) {
        // Remove existing listener to prevent duplicates if re-rendering
        container.removeEventListener('click', handleEmployeeActionClick);
        container.addEventListener('click', handleEmployeeActionClick);
    }
}

/**
 * Handles clicks on action buttons in the employee table.
 * @param {Event} event
 */
function handleEmployeeActionClick(event) {
    const targetButton = event.target.closest('button');
    if (!targetButton) return;

    const employeeId = targetButton.dataset.employeeId;

    // Toggle actions menu
    if (targetButton.classList.contains('toggle-actions-btn')) {
        event.stopPropagation();
        toggleEmployeeActions(employeeId);
        return;
    }

    if (targetButton.classList.contains('view-employee-btn')) {
        const employee = allEmployeesData.find(emp => emp.EmployeeID == employeeId);
        if (employee) {
            openEmployeeDetailModal(employee);
        } else {
            safeAlert('Error', 'Could not find employee details.', 'error');
        }
        return;
    }
    if (targetButton.classList.contains('edit-employee-btn')) {
        if (employeeId) editEmployeeDetails(employeeId);
        return;
    }
    if (targetButton.classList.contains('view-performance-btn')) {
        if (employeeId) viewEmployeePerformance(employeeId);
        return;
    }
    if (targetButton.classList.contains('delete-employee-btn')) {
        if (employeeId) deleteEmployee(employeeId);
        return;
    }
    if (targetButton.classList.contains('export-employee-btn')) {
        const employee = allEmployeesData.find(emp => emp.EmployeeID == employeeId);
        if (employee) {
            exportSingleEmployeeData(employee);
        } else {
            safeAlert('Error', 'Could not find employee details for export.', 'error');
        }
        return;
    }
}

/**
 * Populates and opens the employee detail modal.
 * @param {object} emp - The employee data object.
 */
function openEmployeeDetailModal(emp) {
    if (!employeeDetailModal) {
        console.error("Employee detail modal not initialized.");
        Swal.fire('UI Error', 'Cannot display employee details modal.', 'error');
        return;
    }
    const S = (value, placeholder = 'N/A') => value || placeholder;
    const webRootPath = '/hr34/';

    let photoHtml = `<div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-3xl">
                        ${S(emp.FirstName, '?').charAt(0)}${S(emp.LastName, '?').charAt(0)}
                     </div>`;
    if (emp.EmployeePhotoPath) {
        const photoUrl = emp.EmployeePhotoPath.startsWith('http') ? emp.EmployeePhotoPath : `${webRootPath}${emp.EmployeePhotoPath}`;
        photoHtml = `<img src="${S(photoUrl)}" alt="Profile Photo" class="h-24 w-24 rounded-full object-cover border">`;
    }
    
    const fullName = `${S(emp.FirstName)} ${S(emp.MiddleName)} ${S(emp.LastName)} ${S(emp.Suffix)}`.replace(/\s+/g, ' ').trim();

    const contentDiv = document.getElementById('employee-detail-content');
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="flex items-center space-x-4 mb-4">
                ${photoHtml}
                <div>
                    <h4 class="text-xl font-semibold text-[#4E3B2A]">${fullName}</h4>
                    <p class="text-gray-600">${S(emp.JobTitle)}</p>
                    <p class="text-sm text-gray-500">${S(emp.DepartmentName)}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                <p><strong class="font-medium text-gray-600">Employee ID:</strong> ${S(emp.EmployeeID)}</p>
                <p><strong class="font-medium text-gray-600">Status:</strong> <span class="${emp.IsActive == 1 ? 'text-green-600' : 'text-red-600'} font-semibold">${S(emp.Status)}</span></p>
                
                <p><strong class="font-medium text-gray-600">Work Email:</strong> ${S(emp.Email)}</p>
                <p><strong class="font-medium text-gray-600">Personal Email:</strong> ${S(emp.PersonalEmail)}</p>
                <p><strong class="font-medium text-gray-600">Phone:</strong> ${S(emp.PhoneNumber)}</p>
                <p><strong class="font-medium text-gray-600">Hire Date:</strong> ${S(emp.HireDateFormatted || emp.HireDate)}</p>
                
                <p><strong class="font-medium text-gray-600">Date of Birth:</strong> ${S(emp.DateOfBirthFormatted || emp.DateOfBirth)}</p>
                <p><strong class="font-medium text-gray-600">Gender:</strong> ${S(emp.Gender)}</p>
                <p><strong class="font-medium text-gray-600">Marital Status:</strong> ${S(emp.MaritalStatus)}</p>
                <p><strong class="font-medium text-gray-600">Nationality:</strong> ${S(emp.Nationality)}</p>
                
                <p class="md:col-span-2"><strong class="font-medium text-gray-600">Address:</strong> ${S(emp.AddressLine1)} ${S(emp.AddressLine2, '')}, ${S(emp.City)}, ${S(emp.StateProvince)} ${S(emp.PostalCode)}, ${S(emp.Country)}</p>
                
                <p><strong class="font-medium text-gray-600">Manager:</strong> ${S(emp.ManagerName)}</p>
                <p><strong class="font-medium text-gray-600">User ID:</strong> ${S(emp.UserID)}</p>

                <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-3 pt-2 border-t">Emergency Contact</h5>
                <p><strong class="font-medium text-gray-600">Name:</strong> ${S(emp.EmergencyContactName)}</p>
                <p><strong class="font-medium text-gray-600">Relationship:</strong> ${S(emp.EmergencyContactRelationship)}</p>
                <p><strong class="font-medium text-gray-600">Phone:</strong> ${S(emp.EmergencyContactPhone)}</p>
                
                ${emp.TerminationDate ? `
                    <h5 class="md:col-span-2 text-md font-semibold text-gray-700 mt-3 pt-2 border-t">Termination Info</h5>
                    <p><strong class="font-medium text-gray-600">Termination Date:</strong> ${S(emp.TerminationDateFormatted || emp.TerminationDate)}</p>
                    <p class="md:col-span-2"><strong class="font-medium text-gray-600">Reason:</strong> ${S(emp.TerminationReason)}</p>
                ` : ''}
            </div>
        `;
    }
    employeeDetailModal.classList.remove('hidden');
    employeeDetailModal.classList.add('flex');
}

/**
 * Closes the employee detail modal.
 */
function closeEmployeeDetailModal() {
    if (employeeDetailModal) {
        employeeDetailModal.classList.add('hidden');
        employeeDetailModal.classList.remove('flex');
    }
}

/**
 * Export all employee data
 */
function exportEmployeeData() {
    console.log("[Export] Exporting all employee data...");
    
    if (!allEmployeesData || allEmployeesData.length === 0) {
        Swal.fire('No Data', 'No employee data available to export.', 'warning');
        return;
    }

    // Show export options
    Swal.fire({
        title: 'Export Employee Data',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'CSV',
        cancelButtonText: 'PDF',
        showDenyButton: true,
        denyButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            exportToCSV();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportToPDF();
        }
    });
}

/**
 * Export single employee data
 */
function exportSingleEmployeeData(employee) {
    console.log("[Export] Exporting single employee data:", employee.EmployeeID);
    
    Swal.fire({
        title: 'Export Employee Data',
        text: `Export data for ${employee.FirstName} ${employee.LastName}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'CSV',
        cancelButtonText: 'PDF',
        showDenyButton: true,
        denyButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            exportSingleToCSV(employee);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportSingleToPDF(employee);
        }
    });
}

/**
 * Export to CSV format
 */
function exportToCSV() {
    try {
        const headers = ['Employee ID', 'First Name', 'Last Name', 'Email', 'Job Title', 'Department', 'Status', 'Hire Date'];
        const csvContent = [
            headers.join(','),
            ...allEmployeesData.map(emp => [
                emp.EmployeeID || '',
                `"${emp.FirstName || ''}"`,
                `"${emp.LastName || ''}"`,
                `"${emp.Email || ''}"`,
                `"${emp.JobTitle || ''}"`,
                `"${emp.DepartmentName || ''}"`,
                `"${emp.Status || ''}"`,
                `"${emp.HireDateFormatted || emp.HireDate || ''}"`
            ].join(','))
        ].join('\n');

        downloadFile(csvContent, 'employees.csv', 'text/csv');
        Swal.fire('Success', 'Employee data exported to CSV successfully!', 'success');
    } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire('Error', 'Failed to export CSV file.', 'error');
    }
}

/**
 * Export single employee to CSV
 */
function exportSingleToCSV(employee) {
    try {
        const headers = ['Employee ID', 'First Name', 'Last Name', 'Email', 'Job Title', 'Department', 'Status', 'Hire Date'];
        const csvContent = [
            headers.join(','),
            [
                employee.EmployeeID || '',
                `"${employee.FirstName || ''}"`,
                `"${employee.LastName || ''}"`,
                `"${employee.Email || ''}"`,
                `"${employee.JobTitle || ''}"`,
                `"${employee.DepartmentName || ''}"`,
                `"${employee.Status || ''}"`,
                `"${employee.HireDateFormatted || employee.HireDate || ''}"`
            ].join(',')
        ].join('\n');

        downloadFile(csvContent, `employee_${employee.EmployeeID}.csv`, 'text/csv');
        Swal.fire('Success', 'Employee data exported to CSV successfully!', 'success');
    } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire('Error', 'Failed to export CSV file.', 'error');
    }
}

/**
 * Export to PDF format (simplified)
 */
function exportToPDF() {
    Swal.fire({
        title: 'PDF Export',
        text: 'PDF export functionality will be implemented in a future update. For now, please use the CSV export option.',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

/**
 * Export single employee to PDF
 */
function exportSingleToPDF(employee) {
    Swal.fire({
        title: 'PDF Export',
        text: 'PDF export functionality will be implemented in a future update. For now, please use the CSV export option.',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

/**
 * Download file utility
 */
function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

/**
 * Toggle advanced filters visibility
 */
function attachFilterToggleListener() {
    const filterToggle = document.getElementById('emp-filter-toggle');
    const advancedFilters = document.getElementById('advanced-filters');
    
    if (filterToggle && advancedFilters) {
        filterToggle.addEventListener('click', () => {
            const isHidden = advancedFilters.classList.contains('hidden');
            if (isHidden) {
                advancedFilters.classList.remove('hidden');
                filterToggle.innerHTML = '<i class="fas fa-filter mr-2"></i>Hide Filters';
            } else {
                advancedFilters.classList.add('hidden');
                filterToggle.innerHTML = '<i class="fas fa-filter mr-2"></i>Filter';
            }
        });
    }
}

/**
 * Toggle employee actions dropdown menu
 */
window.toggleEmployeeActions = function(employeeId) {
    // Close all other dropdowns first
    document.querySelectorAll('[id^="actions-menu-"]').forEach(menu => {
        if (menu.id !== `actions-menu-${employeeId}`) {
            menu.classList.add('hidden');
        }
    });
    
    // Toggle current dropdown
    const menu = document.getElementById(`actions-menu-${employeeId}`);
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

/**
 * Close dropdowns when clicking outside
 */
document.addEventListener('click', function(event) {
    if (!event.target.closest('[id^="actions-menu-"]') && !event.target.closest('button[onclick*="toggleEmployeeActions"]')) {
        document.querySelectorAll('[id^="actions-menu-"]').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

/**
 * Attach quick-filter listeners for department and status clicks
 */
function attachQuickFilterListeners() {
    const container = document.getElementById('employee-list-container');
    if (!container) return;
    // Remove any previous handler to avoid duplicates
    container.removeEventListener('click', quickFilterHandler, true);
    container.addEventListener('click', quickFilterHandler, true);
}

function quickFilterHandler(e) {
    const deptBtn = e.target.closest('.dept-filter-link');
    const statusChip = e.target.closest('.status-filter-chip');
    if (deptBtn) {
        const deptId = deptBtn.getAttribute('data-dept-id');
        const select = document.getElementById('emp-filter-dept');
        if (select && deptId) {
            select.value = deptId;
        }
        loadEmployees(buildCurrentFilterParams());
        return;
    }
    if (statusChip) {
        const status = statusChip.getAttribute('data-status');
        const select = document.getElementById('emp-filter-status');
        if (select && status) {
            select.value = status;
        }
        loadEmployees(buildCurrentFilterParams());
        return;
    }
}

/**
 * Attach client-side sort listeners on table headers
 */
function attachSortListeners(tableEl) {
    const thead = tableEl.querySelector('thead');
    if (!thead) return;
    thead.querySelectorAll('th[data-sort-key]').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.sortKey;
            if (!key) return;
            if (currentSort.key === key) {
                currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.dir = 'asc';
            }
            // Re-render using cached data with new sort
            renderEmployeeTable(allEmployeesData);
        });
    });
}

/**
 * View employee profile (placeholder)
 */
window.viewEmployeeProfile = function(employeeId) {
    console.log('View profile for employee:', employeeId);
    // This would open a modal or navigate to profile page
    const employee = allEmployeesData.find(emp => emp.EmployeeID == employeeId);
    if (employee) {
        openEmployeeDetailModal(employee);
    } else {
        safeAlert('View Profile', `Employee ${employeeId} not found in current list.`, 'warning');
    }
};

/**
 * Edit employee details (placeholder)
 */
window.editEmployeeDetails = function(employeeId) {
    console.log('Edit details for employee:', employeeId);
    const employee = allEmployeesData.find(emp => emp.EmployeeID == employeeId);
    if (!employee) { safeAlert('Error', 'Employee not found in current list.', 'error'); return; }
    showEditEmployeeModal(employee);
};

/**
 * View employee performance (placeholder)
 */
window.viewEmployeePerformance = function(employeeId) {
    console.log('View performance for employee:', employeeId);
    showPerformanceModal(employeeId);
};

/**
 * Delete employee (placeholder)
 */
window.deleteEmployee = function(employeeId) {
    console.log('Delete employee:', employeeId);
    // This would show a confirmation dialog
    if (window.Swal && typeof Swal.fire === 'function') {
    Swal.fire({
        title: 'Delete Employee',
        text: `Are you sure you want to delete employee ID: ${employeeId}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
                performEmployeeDelete(employeeId);
            }
        });
    } else {
        const ok = confirm(`Delete employee ID ${employeeId}?`);
        if (ok) performEmployeeDelete(employeeId);
    }
};

// Fallback alert helper (works without SweetAlert)
function safeAlert(title, text, icon) {
    if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({ title, text, icon, confirmButtonText: 'OK' });
    } else {
        alert(`${title}: ${text}`);
    }
}

/**
 * Show edit employee modal with form and save to API
 */
function showEditEmployeeModal(emp) {
    const container = document.getElementById('modalContainer');
    if (!container) { safeAlert('UI Error', 'Modal container not found.', 'error'); return; }
    const modalId = 'edit-employee-modal';
    const overlayId = 'edit-employee-overlay';
    const deptOptions = Object.entries(departmentIdToName).map(([id, name]) => `<option value="${id}">${name}</option>`).join('');
    container.innerHTML = `
        <div id="${modalId}" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div id="${overlayId}" class="fixed inset-0 bg-gray-500/70"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full sm:max-w-xl p-6">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-semibold text-[#4E3B2A]">Edit Employee</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" id="edit-emp-close-btn"><i class="fa-solid fa-times text-xl"></i></button>
                </div>
                <form id="edit-employee-form" class="mt-4 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">First Name</label>
                            <input type="text" id="edit-first-name" class="w-full p-2 border rounded" required value="${emp.FirstName || ''}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Last Name</label>
                            <input type="text" id="edit-last-name" class="w-full p-2 border rounded" required value="${emp.LastName || ''}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Email</label>
                            <input type="email" id="edit-email" class="w-full p-2 border rounded" required value="${emp.Email || ''}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Job Title</label>
                            <input type="text" id="edit-job-title" class="w-full p-2 border rounded" required value="${emp.JobTitle || ''}">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Department</label>
                            <select id="edit-department" class="w-full p-2 border rounded">
                                <option value="">-- Select Department --</option>
                                ${deptOptions}
                            </select>
                        </div>
                        <div class="flex items-center mt-6">
                            <input id="edit-is-active" type="checkbox" class="h-4 w-4 mr-2" ${emp.IsActive == 1 ? 'checked' : ''}>
                            <label for="edit-is-active" class="text-sm text-gray-700">Active</label>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-2 border-t">
                        <button type="button" id="edit-emp-cancel" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded hover:bg-[#4E3B2A]">Save</button>
                    </div>
                    <div id="edit-emp-status" class="text-sm pt-2"></div>
                </form>
            </div>
        </div>`;
    // Set selected department
    const deptSel = document.getElementById('edit-department');
    if (deptSel && emp.DepartmentID) deptSel.value = emp.DepartmentID;
    const close = () => { const m = document.getElementById(modalId); if (m) m.remove(); };
    document.getElementById('edit-emp-close-btn')?.addEventListener('click', close);
    document.getElementById(overlayId)?.addEventListener('click', close);
    document.getElementById('edit-emp-cancel')?.addEventListener('click', close);
    const form = document.getElementById('edit-employee-form');
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const status = document.getElementById('edit-emp-status');
        if (status) { status.className = 'text-sm text-blue-600'; status.textContent = 'Saving...'; }
        try {
            const payload = {
                employee_id_to_update: parseInt(emp.EmployeeID),
                first_name: document.getElementById('edit-first-name').value.trim(),
                last_name: document.getElementById('edit-last-name').value.trim(),
                email: document.getElementById('edit-email').value.trim(),
                job_title: document.getElementById('edit-job-title').value.trim(),
                department_id: deptSel?.value ? parseInt(deptSel.value) : null,
                is_active_employee: document.getElementById('edit-is-active').checked ? 1 : 0
            };
            const res = await fetch(`${API_BASE_URL}admin_update_employee_profile.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            if (!res.ok) { const errText = await res.text(); throw new Error(`HTTP ${res.status} ${errText}`); }
            const data = await res.json();
            safeAlert('Saved', data.message || 'Employee updated.', 'success');
            close();
            await loadEmployees(buildCurrentFilterParams());
        } catch (err) {
            console.error('Save employee failed', err);
            if (status) { status.className = 'text-sm text-red-600'; status.textContent = `Error: ${err.message}`; }
        }
    });
}

/**
 * Call delete API to soft-delete employee
 */
async function performEmployeeDelete(employeeId) {
    try {
        const res = await fetch(`${API_BASE_URL}delete_employee.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ employee_id: parseInt(employeeId) }) });
        if (!res.ok) { const t = await res.text(); throw new Error(`HTTP ${res.status} ${t}`); }
        const data = await res.json();
        safeAlert('Deleted', data.message || 'Employee deleted.', 'success');
        await loadEmployees(buildCurrentFilterParams());
    } catch (err) {
        console.error('Delete employee failed', err);
        safeAlert('Error', `Failed to delete employee: ${err.message}`, 'error');
    }
}

/**
 * Show simple performance modal using timesheets API
 */
async function showPerformanceModal(employeeId) {
    try {
        const res = await fetch(`${API_BASE_URL}get_timesheets.php?employee_id=${encodeURIComponent(employeeId)}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const timesheets = await res.json();
        const list = Array.isArray(timesheets) ? timesheets.slice(0, 6) : [];
        const rows = list.map(ts => `<tr><td class="px-4 py-2 text-sm">${ts.PeriodStartDateFormatted || ts.PeriodStartDate || ''} - ${ts.PeriodEndDateFormatted || ts.PeriodEndDate || ''}</td><td class="px-4 py-2 text-sm text-right">${ts.TotalHoursWorked ?? '-'}</td><td class="px-4 py-2 text-sm text-right">${ts.OvertimeHours ?? '-'}</td><td class="px-4 py-2 text-sm">${ts.Status || ''}</td></tr>`).join('');
        const container = document.getElementById('modalContainer');
        if (!container) { safeAlert('UI Error', 'Modal container not found.', 'error'); return; }
        const modalId = 'perf-modal';
        const overlayId = 'perf-overlay';
        container.innerHTML = `
            <div id="${modalId}" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div id="${overlayId}" class="fixed inset-0 bg-gray-500/70"></div>
                <div class="relative bg-white rounded-lg shadow-xl w-full sm:max-w-2xl p-6">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <h3 class="text-lg font-semibold text-[#4E3B2A]">Recent Performance (Timesheets)</h3>
                        <button type="button" id="perf-close" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-times text-xl"></i></button>
                    </div>
                    <div class="mt-4">
                        ${rows ? `<table class="w-full border border-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs text-gray-600">Period</th><th class="px-4 py-2 text-right text-xs text-gray-600">Hours</th><th class="px-4 py-2 text-right text-xs text-gray-600">OT</th><th class="px-4 py-2 text-left text-xs text-gray-600">Status</th></tr></thead><tbody>${rows || ''}</tbody></table>` : '<p class="text-gray-500">No recent timesheets found.</p>'}
                    </div>
                    <div class="pt-4 flex justify-end gap-2 border-t">
                        <button type="button" id="perf-close-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Close</button>
                    </div>
                </div>
            </div>`;
        const close = () => { const m = document.getElementById(modalId); if (m) m.remove(); };
        document.getElementById('perf-close')?.addEventListener('click', close);
        document.getElementById('perf-close-btn')?.addEventListener('click', close);
        document.getElementById(overlayId)?.addEventListener('click', close);
    } catch (err) {
        console.error('Load performance failed', err);
        safeAlert('Error', 'Could not load performance data.', 'error');
    }
}
