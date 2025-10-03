/**
 * Hospital HR Organizational Structure Module
 * v4.0 - Enhanced for Philippine hospital-specific HR management
 * Supports HR divisions, job roles, department coordinators, and comprehensive org hierarchy
 */
import { API_BASE_URL, populateEmployeeDropdown } from '../utils.js';

let hospitalOrgData = {}; // Store comprehensive hospital organizational data
let currentView = 'hierarchy'; // Current view: hierarchy, divisions, roles, coordinators

export async function displayOrgStructureSection() {
    console.log("[Display] Displaying Hospital Organizational Structure Section...");
    const pageTitleElement = document.getElementById('page-title');
    const pageSubtitleElement = document.getElementById('page-subtitle');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !mainContentArea) {
        console.error("displayOrgStructureSection: Core DOM elements not found.");
        return;
    }

    pageTitleElement.textContent = 'Hospital Organizational Structure';
    pageSubtitleElement.textContent = 'Manage hospital departments, HR divisions, job roles, and coordinators';

    mainContentArea.innerHTML = `
        <div class="p-6 space-y-6">
            <!-- Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="hierarchy" onclick="switchOrgView('hierarchy')">
                            <i class="fas fa-sitemap mr-2"></i>Hospital Hierarchy
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="divisions" onclick="switchOrgView('divisions')">
                            <i class="fas fa-building mr-2"></i>HR Divisions
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="roles" onclick="switchOrgView('roles')">
                            <i class="fas fa-users-cog mr-2"></i>Job Roles
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="coordinators" onclick="switchOrgView('coordinators')">
                            <i class="fas fa-user-tie mr-2"></i>HR Coordinators
                        </button>
                    </nav>
                </div>

                <!-- View Content Area -->
                <div id="org-view-content" class="p-6">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-500 mt-2">Loading organizational structure...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Initialize view
    await loadHospitalOrgData();
    switchOrgView('hierarchy');
}

// ===================== DATA LOADING FUNCTIONS =====================

/**
 * Load comprehensive hospital organizational data
 */
async function loadHospitalOrgData() {
    try {
        const response = await fetch(`${API_BASE_URL}get_hospital_org_structure.php`);
        const data = await response.json();
        
        if (data.success) {
            hospitalOrgData = data.data;
            console.log('Hospital org data loaded:', hospitalOrgData);
        } else {
            console.error('Failed to load hospital org data:', data.message);
            hospitalOrgData = { divisions: [], departments: [], roles: [], coordinators: [] };
        }
    } catch (error) {
        console.error('Error loading hospital org data:', error);
        hospitalOrgData = { divisions: [], departments: [], roles: [], coordinators: [] };
    }
}

// ===================== VIEW SWITCHING FUNCTIONS =====================

/**
 * Switch between different organizational views
 */
window.switchOrgView = function(view) {
    currentView = view;
    
    // Update tab styling
    document.querySelectorAll('.nav-tab-btn').forEach(btn => {
        const btnView = btn.getAttribute('data-view');
        if (btnView === view) {
            btn.classList.add('border-blue-500', 'text-blue-600');
            btn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        } else {
            btn.classList.remove('border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }
    });
    
    // Render appropriate view
    switch (view) {
        case 'hierarchy':
            renderHierarchyView();
            break;
        case 'divisions':
            renderDivisionsView();
            break;
        case 'roles':
            renderRolesView();
            break;
        case 'coordinators':
            renderCoordinatorsView();
            break;
    }
};

// ===================== HIERARCHY VIEW =====================

function renderHierarchyView() {
    const contentArea = document.getElementById('org-view-content');
    contentArea.innerHTML = `
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Hospital Organizational Hierarchy</h3>
                    <p class="text-sm text-gray-600">Complete hospital departmental structure</p>
                </div>
                <button onclick="showAddDepartmentModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Department
                </button>
            </div>

            <!-- Hierarchy Display -->
            <div id="hierarchy-display" class="bg-gray-50 rounded-lg p-6">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <p class="text-gray-500 mt-2">Loading hierarchy...</p>
                </div>
            </div>
            </div>
        `;
        
    renderHospitalHierarchy();
}

function renderHospitalHierarchy() {
    const hierarchyDisplay = document.getElementById('hierarchy-display');
    if (!hierarchyDisplay) return;
    
    const departments = hospitalOrgData.departments || [];
    
    if (departments.length === 0) {
        hierarchyDisplay.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-building text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">No departments found. Please run the database setup first.</p>
                <button onclick="setupHospitalStructure()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Setup Hospital Structure
                </button>
            </div>
        `;
        return;
    }

    // Build hierarchy tree
    const hierarchy = buildDepartmentHierarchy(departments);
    hierarchyDisplay.innerHTML = renderDepartmentTree(hierarchy);
}

// ===================== DIVISIONS VIEW =====================
function renderDivisionsView() {
    const contentArea = document.getElementById('org-view-content');
    const divisions = hospitalOrgData.divisions || [];
    contentArea.innerHTML = `
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">HR Divisions</h3>
                    <p class="text-sm text-gray-600">Administrative groupings across departments</p>
                </div>
            </div>
            ${divisions.length === 0 ? `
                <div class="text-center py-12 text-gray-500">
                    No divisions found.
                </div>
            ` : `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${divisions.map(d => `
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-gray-900">${d.DivisionName}</h4>
                                    <p class="text-sm text-gray-600">${d.Description || ''}</p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `}
        </div>
    `;
}

// ===================== ROLES VIEW =====================
function renderRolesView() {
    const contentArea = document.getElementById('org-view-content');
    const roles = hospitalOrgData.roles || [];
    contentArea.innerHTML = `
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Job Roles</h3>
                    <p class="text-sm text-gray-600">Roles across the hospital organizational structure</p>
                </div>
            </div>
            ${roles.length === 0 ? `
                <div class="text-center py-12 text-gray-500">No roles found.</div>
            ` : `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${roles.map(r => `
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">${r.RoleName}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">${r.DepartmentName || ''}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">${r.Description || ''}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `}
        </div>
    `;
}

// ===================== COORDINATORS VIEW =====================
function renderCoordinatorsView() {
    const contentArea = document.getElementById('org-view-content');
    const coordinators = hospitalOrgData.coordinators || [];
    contentArea.innerHTML = `
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">HR Coordinators</h3>
                    <p class="text-sm text-gray-600">Departmental coordinators and points of contact</p>
                </div>
            </div>
            ${coordinators.length === 0 ? `
                <div class="text-center py-12 text-gray-500">No coordinators found.</div>
            ` : `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinator</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${coordinators.map(c => `
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">${c.CoordinatorName || ''}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">${c.DepartmentName || ''}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">${c.Email || ''}${c.PhoneNumber ? ' • ' + c.PhoneNumber : ''}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `}
        </div>
    `;
}

// Expose renderers for inline onclick usage
window.renderDivisionsView = renderDivisionsView;
window.renderRolesView = renderRolesView;
window.renderCoordinatorsView = renderCoordinatorsView;

function buildDepartmentHierarchy(departments) {
    const departmentMap = {};
    const rootDepartments = [];
    
    // Create department map
    departments.forEach(dept => {
        departmentMap[dept.DepartmentID] = { ...dept, children: [] };
    });
    
    // Build parent-child relationships
    departments.forEach(dept => {
        if (dept.ParentDepartmentID && departmentMap[dept.ParentDepartmentID]) {
            departmentMap[dept.ParentDepartmentID].children.push(departmentMap[dept.DepartmentID]);
        } else {
            rootDepartments.push(departmentMap[dept.DepartmentID]);
        }
    });
    
    return rootDepartments;
}

function renderDepartmentTree(departments, level = 0) {
    return `
        <div class="space-y-2">
            ${departments.map(dept => `
                <div class="department-node bg-white rounded-lg border border-gray-200 p-4" style="margin-left: ${level * 20}px;">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-${getDepartmentTypeColor(dept.DepartmentType)}-100 flex items-center justify-center">
                                <i class="fas ${getDepartmentTypeIcon(dept.DepartmentType)} text-${getDepartmentTypeColor(dept.DepartmentType)}-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">${dept.DepartmentName}</h4>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span class="px-2 py-1 bg-${getDepartmentTypeColor(dept.DepartmentType)}-100 text-${getDepartmentTypeColor(dept.DepartmentType)}-800 rounded-full text-xs">
                                        ${dept.DepartmentType}
                                    </span>
                                    <span><i class="fas fa-code mr-1"></i>${dept.DepartmentCode || 'N/A'}</span>
                                    <span><i class="fas fa-users mr-1"></i>${dept.EmployeeCount || 0} employees</span>
                                    ${dept.ManagerName ? `<span><i class="fas fa-user-tie mr-1"></i>${dept.ManagerName}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="editDepartment(${dept.DepartmentID})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewDepartmentDetails(${dept.DepartmentID})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    ${dept.Description ? `<p class="text-gray-600 text-sm mt-2">${dept.Description}</p>` : ''}
                    ${dept.children && dept.children.length > 0 ? renderDepartmentTree(dept.children, level + 1) : ''}
                </div>
            `).join('')}
        </div>
    `;
}

// ===================== UTILITY FUNCTIONS =====================

function getDepartmentTypeColor(type) {
    const colors = {
        'Executive': 'purple',
        'Clinical': 'green', 
        'Administrative': 'blue',
        'Support': 'yellow',
        'Ancillary': 'indigo'
    };
    return colors[type] || 'gray';
}

function getDepartmentTypeIcon(type) {
    const icons = {
        'Executive': 'fa-crown',
        'Clinical': 'fa-user-md',
        'Administrative': 'fa-building',
        'Support': 'fa-tools',
        'Ancillary': 'fa-cogs'
    };
    return icons[type] || 'fa-building';
}

window.setupHospitalStructure = function() {
    Swal.fire({
        title: 'Setup Hospital Structure',
        text: 'This will create the hospital organizational structure with departments, HR divisions, and job roles.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Setup Now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
        Swal.fire({
                title: 'Setting up...',
                text: 'Creating hospital organizational structure',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Note: The database structure was already created when the SQL file was run
            // Just refresh the data
            loadHospitalOrgData().then(() => {
                Swal.fire('Success!', 'Hospital structure is ready.', 'success');
                renderHierarchyView();
            });
        }
    });
};

