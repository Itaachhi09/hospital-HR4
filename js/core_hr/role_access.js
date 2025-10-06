/**
 * Core HR - Role & Access Viewer Module
 * v1.0 - Read-only role and access matrix viewer for HR Core integration
 * 
 * Purpose: Display system access and responsibilities across HR modules
 * - No CRUD operations (read-only)
 * - Role-based access matrix visualization
 * - System permissions overview
 */

import { API_BASE_URL } from '../utils.js';

// Global state for role and access data
let roleAccessData = {
    roles: [],
    permissions: [],
    users: [],
    modules: []
};

/**
 * Display Role & Access Viewer section
 */
export async function displayRoleAccessSection() {
    console.log("[Role Access] Displaying Role & Access Viewer Section...");
    const pageTitleElement = document.getElementById('page-title');
    const pageSubtitleElement = document.getElementById('page-subtitle');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !mainContentArea) {
        console.error("displayRoleAccessSection: Core DOM elements not found.");
        return;
    }

    pageTitleElement.textContent = 'Role & Access Matrix';
    pageSubtitleElement.textContent = 'System access and responsibilities across HR modules';

    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header Section -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Role & Access Matrix</h3>
                        <p class="text-sm text-gray-600">System access and responsibilities across HR modules</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshRoleAccess()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="exportRoleAccess()" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- System Overview -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Access Control Overview</h3>
                <div id="access-overview" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center py-4">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-500 mt-2">Loading access data...</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Role Access Tabs">
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="roles" onclick="switchRoleAccessView('roles')">
                            <i class="fas fa-user-shield mr-2"></i>Roles & Permissions
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="users" onclick="switchRoleAccessView('users')">
                            <i class="fas fa-users mr-2"></i>User Access
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="modules" onclick="switchRoleAccessView('modules')">
                            <i class="fas fa-cubes mr-2"></i>Module Access
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="matrix" onclick="switchRoleAccessView('matrix')">
                            <i class="fas fa-table mr-2"></i>Access Matrix
                        </button>
                    </nav>
                </div>

                <!-- View Content Area -->
                <div id="role-access-view-content" class="p-6">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-500 mt-2">Loading role and access data...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Initialize role access data
    await loadRoleAccessData();
    switchRoleAccessView('roles');
}

/**
 * Load role and access data
 */
async function loadRoleAccessData() {
    console.log("[Role Access] Loading role and access data...");
    
    try {
        // Load data from all systems
        const [rolesData, permissionsData, usersData, modulesData] = await Promise.all([
            loadRolesFromAllSystems(),
            loadPermissionsFromAllSystems(),
            loadUsersFromAllSystems(),
            loadModulesFromAllSystems()
        ]);

        roleAccessData = {
            roles: rolesData,
            permissions: permissionsData,
            users: usersData,
            modules: modulesData
        };

        console.log("[Role Access] Data loaded successfully:", roleAccessData);
        renderAccessOverview();
    } catch (error) {
        console.error("[Role Access] Error loading data:", error);
        showErrorState();
    }
}

/**
 * Load roles from all HR systems
 */
async function loadRolesFromAllSystems() {
    try {
        // This will be expanded to fetch from HR1, HR2, HR3 systems
        // For now, return mock data
        return [
            {
                id: 1,
                name: 'HR Administrator',
                description: 'Full access to all HR systems',
                level: 'Admin',
                systems: ['HR1', 'HR2', 'HR3', 'HR4'],
                permissions: ['read', 'write', 'delete', 'manage']
            },
            {
                id: 2,
                name: 'Department Manager',
                description: 'Access to department-specific data',
                level: 'Manager',
                systems: ['HR1', 'HR3'],
                permissions: ['read', 'write']
            },
            {
                id: 3,
                name: 'HR Coordinator',
                description: 'Limited access to employee data',
                level: 'Coordinator',
                systems: ['HR1'],
                permissions: ['read']
            },
            {
                id: 4,
                name: 'Employee',
                description: 'Self-service access only',
                level: 'Employee',
                systems: ['HR1'],
                permissions: ['read_own']
            }
        ];
    } catch (error) {
        console.error("[Role Access] Error loading roles:", error);
        return [];
    }
}

/**
 * Load permissions from all HR systems
 */
async function loadPermissionsFromAllSystems() {
    try {
        // This will be expanded to fetch from HR1, HR2, HR3 systems
        return [
            { id: 1, name: 'View Employees', module: 'Employee Directory', description: 'View employee information' },
            { id: 2, name: 'View Documents', module: 'Document Viewer', description: 'View employee documents' },
            { id: 3, name: 'View Org Structure', module: 'Organizational Structure', description: 'View organizational hierarchy' },
            { id: 4, name: 'Manage Users', module: 'User Management', description: 'Create and manage user accounts' },
            { id: 5, name: 'View Reports', module: 'Reports', description: 'Access to HR reports' },
            { id: 6, name: 'Manage Roles', module: 'Role Management', description: 'Manage user roles and permissions' }
        ];
    } catch (error) {
        console.error("[Role Access] Error loading permissions:", error);
        return [];
    }
}

/**
 * Load users from all HR systems
 */
async function loadUsersFromAllSystems() {
    try {
        // This will be expanded to fetch from HR1, HR2, HR3 systems
        return [
            {
                id: 1,
                name: 'John Admin',
                email: 'john.admin@hospital.com',
                role: 'HR Administrator',
                department: 'Human Resources',
                lastLogin: '2024-01-15 09:30:00',
                status: 'Active'
            },
            {
                id: 2,
                name: 'Jane Manager',
                email: 'jane.manager@hospital.com',
                role: 'Department Manager',
                department: 'Nursing',
                lastLogin: '2024-01-15 08:45:00',
                status: 'Active'
            },
            {
                id: 3,
                name: 'Bob Coordinator',
                email: 'bob.coordinator@hospital.com',
                role: 'HR Coordinator',
                department: 'Human Resources',
                lastLogin: '2024-01-14 16:20:00',
                status: 'Active'
            }
        ];
    } catch (error) {
        console.error("[Role Access] Error loading users:", error);
        return [];
    }
}

/**
 * Load modules from all HR systems
 */
async function loadModulesFromAllSystems() {
    try {
        return [
            {
                id: 1,
                name: 'HR1 - Talent Acquisition',
                description: 'Recruitment and hiring processes',
                systems: ['HR1'],
                permissions: ['view_applicants', 'manage_positions', 'schedule_interviews']
            },
            {
                id: 2,
                name: 'HR2 - Talent Development',
                description: 'Training and career development',
                systems: ['HR2'],
                permissions: ['view_training', 'manage_courses', 'track_progress']
            },
            {
                id: 3,
                name: 'HR3 - Workforce Operations',
                description: 'Time management and attendance',
                systems: ['HR3'],
                permissions: ['view_attendance', 'manage_schedules', 'process_leaves']
            },
            {
                id: 4,
                name: 'HR4 - Compensation & Analytics',
                description: 'Payroll and HR analytics',
                systems: ['HR4'],
                permissions: ['view_payroll', 'generate_reports', 'manage_benefits']
            }
        ];
    } catch (error) {
        console.error("[Role Access] Error loading modules:", error);
        return [];
    }
}

/**
 * Render access overview
 */
function renderAccessOverview() {
    const overview = document.getElementById('access-overview');
    if (!overview) return;

    const stats = [
        { label: 'Total Roles', value: roleAccessData.roles.length, icon: 'fa-user-shield', color: 'blue' },
        { label: 'Active Users', value: roleAccessData.users.filter(u => u.status === 'Active').length, icon: 'fa-users', color: 'green' },
        { label: 'System Modules', value: roleAccessData.modules.length, icon: 'fa-cubes', color: 'purple' },
        { label: 'Permissions', value: roleAccessData.permissions.length, icon: 'fa-key', color: 'orange' }
    ];

    overview.innerHTML = stats.map(stat => `
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <div class="flex items-center justify-center mb-2">
                <i class="fas ${stat.icon} text-${stat.color}-600 text-2xl"></i>
            </div>
            <div class="text-2xl font-bold text-gray-900">${stat.value}</div>
            <div class="text-sm text-gray-600">${stat.label}</div>
        </div>
    `).join('');
}

/**
 * Switch between different role access views
 */
window.switchRoleAccessView = function(view) {
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
        case 'roles':
            renderRolesView();
            break;
        case 'users':
            renderUsersView();
            break;
        case 'modules':
            renderModulesView();
            break;
        case 'matrix':
            renderAccessMatrixView();
            break;
    }
};

/**
 * Render Roles & Permissions View
 */
function renderRolesView() {
    const contentArea = document.getElementById('role-access-view-content');
    const roles = roleAccessData.roles || [];
    
    contentArea.innerHTML = `
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Roles & Permissions</h3>
                    <p class="text-sm text-gray-600">System roles and their associated permissions</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="exportRoles()" class="px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                ${roles.map(role => `
                    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-user-shield text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">${role.name}</h4>
                                    <p class="text-sm text-gray-500">${role.level}</p>
                                </div>
                            </div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                ${role.systems.length} Systems
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4">${role.description}</p>
                        
                        <div class="space-y-3">
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Accessible Systems:</h5>
                                <div class="flex flex-wrap gap-1">
                                    ${role.systems.map(system => `
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                            ${system}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Permissions:</h5>
                                <div class="flex flex-wrap gap-1">
                                    ${role.permissions.map(permission => `
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                            ${permission}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

/**
 * Render Users View
 */
function renderUsersView() {
    const contentArea = document.getElementById('role-access-view-content');
    const users = roleAccessData.users || [];
    
    contentArea.innerHTML = `
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">User Access</h3>
                    <p class="text-sm text-gray-600">Current users and their access levels</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="exportUsers()" class="px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${users.map(user => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-700">
                                                        ${user.name.split(' ').map(n => n[0]).join('')}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">${user.name}</div>
                                                <div class="text-sm text-gray-500">${user.email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.role}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.department}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${user.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                            ${user.status}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.lastLogin}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

/**
 * Render Modules View
 */
function renderModulesView() {
    const contentArea = document.getElementById('role-access-view-content');
    const modules = roleAccessData.modules || [];
    
    contentArea.innerHTML = `
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">System Modules</h3>
                    <p class="text-sm text-gray-600">Available HR system modules and their capabilities</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="exportModules()" class="px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ${modules.map(module => `
                    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-cube text-purple-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">${module.name}</h4>
                                    <p class="text-sm text-gray-500">${module.systems.join(', ')}</p>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4">${module.description}</p>
                        
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Available Permissions:</h5>
                            <div class="flex flex-wrap gap-1">
                                ${module.permissions.map(permission => `
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                        ${permission}
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

/**
 * Render Access Matrix View
 */
function renderAccessMatrixView() {
    const contentArea = document.getElementById('role-access-view-content');
    const roles = roleAccessData.roles || [];
    const permissions = roleAccessData.permissions || [];
    
    contentArea.innerHTML = `
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Access Matrix</h3>
                    <p class="text-sm text-gray-600">Role-based permission matrix across all HR systems</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="exportMatrix()" class="px-3 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                ${permissions.map(permission => `
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${permission.name}</th>
                                `).join('')}
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${roles.map(role => `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${role.name}</td>
                                    ${permissions.map(permission => `
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${hasPermission(role, permission) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                ${hasPermission(role, permission) ? 'Yes' : 'No'}
                                            </span>
                                        </td>
                                    `).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

/**
 * Check if role has permission
 */
function hasPermission(role, permission) {
    // Simple logic - in real implementation, this would check against actual permission data
    if (role.level === 'Admin') return true;
    if (role.level === 'Manager' && permission.module !== 'Role Management') return true;
    if (role.level === 'Coordinator' && permission.module === 'Employee Directory') return true;
    if (role.level === 'Employee' && permission.name.includes('View')) return true;
    return false;
}

/**
 * Export functions
 */
window.exportRoles = function() {
    console.log("[Export] Exporting roles data...");
    exportToCSV(roleAccessData.roles, 'roles.csv');
};

window.exportUsers = function() {
    console.log("[Export] Exporting users data...");
    exportToCSV(roleAccessData.users, 'users.csv');
};

window.exportModules = function() {
    console.log("[Export] Exporting modules data...");
    exportToCSV(roleAccessData.modules, 'modules.csv');
};

window.exportMatrix = function() {
    console.log("[Export] Exporting access matrix...");
    // Implementation for matrix export
    Swal.fire('Export', 'Access matrix export functionality will be implemented.', 'info');
};

/**
 * Export to CSV utility
 */
function exportToCSV(data, filename) {
    if (!data || data.length === 0) {
        Swal.fire('No Data', 'No data available to export.', 'warning');
        return;
    }

    try {
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
        ].join('\n');

        downloadFile(csvContent, filename, 'text/csv');
        Swal.fire('Success', 'Data exported to CSV successfully!', 'success');
    } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire('Error', 'Failed to export CSV file.', 'error');
    }
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
 * Refresh role and access data
 */
window.refreshRoleAccess = function() {
    console.log('Refreshing role and access data...');
    loadRoleAccessData();
};

/**
 * Export role and access data
 */
window.exportRoleAccess = function() {
    console.log('Exporting role and access data...');
    Swal.fire({
        title: 'Export Role & Access Data',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'CSV',
        cancelButtonText: 'PDF',
        showDenyButton: true,
        denyButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            exportRoleAccessToCSV();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportRoleAccessToPDF();
        }
    });
};

/**
 * Show error state
 */
function showErrorState() {
    const contentArea = document.getElementById('role-access-view-content');
    if (contentArea) {
        contentArea.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-red-400 text-4xl mb-4"></i>
                <p class="text-red-600 text-lg font-medium">Error Loading Role & Access Data</p>
                <p class="text-gray-600 mt-2">Unable to connect to HR systems. Please try again later.</p>
                <button onclick="loadRoleAccessData()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Retry
                </button>
            </div>
        `;
    }
}

