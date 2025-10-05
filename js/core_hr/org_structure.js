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
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="functional" onclick="switchOrgView('functional')">
                            <i class="fas fa-project-diagram mr-2"></i>Functional View
                        </button>
                        <button class="nav-tab-btn py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-view="paygrade" onclick="switchOrgView('paygrade')">
                            <i class="fas fa-layer-group mr-2"></i>Pay Grade View
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

            <!-- Hierarchy Display with controls -->
            <div class="flex items-center justify-between">
                <div class="space-x-2">
                    <button id="org-zoom-in" class="px-2 py-1 border rounded text-sm" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                    <button id="org-zoom-out" class="px-2 py-1 border rounded text-sm" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                    <button id="org-reset" class="px-2 py-1 border rounded text-sm" title="Reset"><i class="fas fa-compress-arrows-alt"></i></button>
                </div>
                <div>
                    <button id="org-export" class="px-3 py-1 border rounded text-sm" title="Export to PDF"><i class="fas fa-file-pdf"></i> Export</button>
                </div>
            </div>
            <div id="hierarchy-display" class="bg-gray-50 rounded-lg p-6 overflow-hidden">
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
    hierarchyDisplay.innerHTML = `<div id="org-panzoom-container" class="cursor-grab"><div id="org-tree-root">${renderDepartmentTree(hierarchy)}</div></div>`;
    setupPanZoom();

    // Attach event delegation for edit/view buttons so clicks reliably call handlers
    hierarchyDisplay.querySelectorAll && hierarchyDisplay.addEventListener('click', function(evt){
        const editBtn = evt.target.closest && evt.target.closest('.btn-edit-dept');
        if (editBtn) {
            const id = editBtn.getAttribute('data-id'); if (id) return editDepartment(id);
        }
        const viewBtn = evt.target.closest && evt.target.closest('.btn-view-dept');
        if (viewBtn) {
            const id = viewBtn.getAttribute('data-id'); if (id) return viewDepartmentDetails(id);
        }
        const node = evt.target.closest && evt.target.closest('.department-node');
        if (node && node.dataset && node.dataset.deptId) {
            showDepartmentEmployees(node.dataset.deptId);
        }
    });

    // Drag-drop to reorder (admins only – assume server enforces RBAC)
    enableDragDropReorder();

    // export handler
    document.getElementById('org-export')?.addEventListener('click', exportOrgToPDF);
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
                <div class="department-node bg-white rounded-lg border border-gray-200 p-4 relative" draggable="true" data-dept-id="${dept.DepartmentID}" style="margin-left: ${level * 20}px;">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-${getDepartmentTypeColor(dept.DepartmentType)}-100 flex items-center justify-center">
                                <i class="fas ${getDepartmentTypeIcon(dept.DepartmentType)} text-${getDepartmentTypeColor(dept.DepartmentType)}-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">${dept.DepartmentName}</h4>
                                <div class="flex items-center space-x-4 text-sm text-gray-600" title="${tooltipText(dept)}">
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
                            <button data-id="${dept.DepartmentID}" class="btn-edit-dept p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button data-id="${dept.DepartmentID}" class="btn-view-dept p-2 text-green-600 hover:bg-green-50 rounded-lg" title="View Details">
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

function tooltipText(dept){
    const mgr = dept.ManagerName ? `Manager: ${dept.ManagerName}` : 'Manager: N/A';
    const code = dept.DepartmentCode ? `Code: ${dept.DepartmentCode}` : 'Code: N/A';
    const count = `Employees: ${dept.EmployeeCount || 0}`;
    return `${mgr} | ${code} | ${count}`;
}

function setupPanZoom(){
    const container = document.getElementById('org-panzoom-container');
    const tree = document.getElementById('org-tree-root');
    if (!container || !tree) return;
    let scale = 1, min=0.5, max=2.0;
    const apply = ()=>{ tree.style.transform = `scale(${scale})`; tree.style.transformOrigin = '0 0'; };
    document.getElementById('org-zoom-in')?.addEventListener('click', ()=>{ scale = Math.min(max, scale+0.1); apply(); });
    document.getElementById('org-zoom-out')?.addEventListener('click', ()=>{ scale = Math.min(max, Math.max(min, scale-0.1)); apply(); });
    document.getElementById('org-reset')?.addEventListener('click', ()=>{ scale = 1; apply(); container.scrollTo({left:0, top:0}); });
    // pan with drag
    let isDown=false, startX=0, startY=0, scrollLeft=0, scrollTop=0;
    container.style.overflow = 'auto';
    container.addEventListener('mousedown', (e)=>{ isDown=true; container.classList.add('cursor-grabbing'); startX=e.pageX; startY=e.pageY; scrollLeft=container.scrollLeft; scrollTop=container.scrollTop; });
    container.addEventListener('mouseleave', ()=>{ isDown=false; container.classList.remove('cursor-grabbing'); });
    container.addEventListener('mouseup', ()=>{ isDown=false; container.classList.remove('cursor-grabbing'); });
    container.addEventListener('mousemove', (e)=>{ if (!isDown) return; const dx=e.pageX-startX, dy=e.pageY-startY; container.scrollLeft = scrollLeft - dx; container.scrollTop = scrollTop - dy; });
}

async function showDepartmentEmployees(deptId){
    try{
        const res = await fetch(`${API_BASE_URL}get_employees.php?department_id=${encodeURIComponent(deptId)}`);
        const list = await res.json();
        if (!Array.isArray(list)) throw new Error('Unexpected response');
        const rows = list.map(e=> `<tr><td class="px-3 py-1 text-sm">${e.EmployeeID}</td><td class="px-3 py-1 text-sm">${e.FirstName||''} ${e.LastName||''}</td><td class="px-3 py-1 text-sm">${e.JobTitle||''}</td><td class="px-3 py-1 text-sm">${e.Email||''}</td></tr>`).join('');
        const html = `<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 border"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs text-gray-500">ID</th><th class="px-3 py-2 text-left text-xs text-gray-500">Name</th><th class="px-3 py-2 text-left text-xs text-gray-500">Title</th><th class="px-3 py-2 text-left text-xs text-gray-500">Email</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        await Swal.fire({ title: 'Department Employees', html, width: '800px', showCloseButton: true, confirmButtonText: 'Close' });
    }catch(err){ console.error(err); Swal.fire('Error','Failed to load employee list','error'); }
}

async function exportOrgToPDF(){
    try{
        // Lightweight client-side export using print dialog
        const el = document.getElementById('org-tree-root'); if (!el) return;
        const printWin = window.open('', 'PRINT', 'height=800,width=1000');
        if (!printWin) return;
        printWin.document.write(`<html><head><title>Org Chart</title><style>body{font-family:Arial} .department-node{border:1px solid #ddd; margin:6px; padding:6px; border-radius:6px}</style></head><body>${el.innerHTML}</body></html>`);
        printWin.document.close();
        printWin.focus();
        printWin.print();
        printWin.close();
    }catch(err){ console.error('Export failed', err); Swal.fire('Export Failed','Could not export to PDF/print','error'); }
}

function enableDragDropReorder(){
    const container = document.getElementById('org-tree-root'); if (!container) return;
    let draggedId = null;
    container.addEventListener('dragstart', e=>{
        const node = e.target.closest('.department-node');
        if (!node) return;
        draggedId = node.dataset.deptId;
        e.dataTransfer.setData('text/plain', draggedId);
        e.dataTransfer.dropEffect = 'move';
    });
    container.addEventListener('dragover', e=>{
        const node = e.target.closest('.department-node');
        if (!node) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });
    container.addEventListener('drop', async e=>{
        const target = e.target.closest('.department-node');
        if (!target) return;
        e.preventDefault();
        const newParentId = target.dataset.deptId;
        if (!draggedId || draggedId === newParentId) return;
        // Confirm and send reorder
        const res = await Swal.fire({ title: 'Move Department', text: 'Set new parent for this department?', icon: 'question', showCancelButton: true });
        if (!res.isConfirmed) return;
        try{
            const payload = { moves: [{ department_id: Number(draggedId), new_parent_id: Number(newParentId) }] };
            const resp = await fetch(`${API_BASE_URL.replace(/php\/api\/$/, 'api/')}departments/reorder`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await resp.json();
            if (!resp.ok || data.success === false) throw new Error(data.message || 'Failed');
            await loadHospitalOrgData();
            renderHierarchyView();
            Swal.fire('Updated','Department moved','success');
        }catch(err){ console.error(err); Swal.fire('Error', String(err.message||err), 'error'); }
    });
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

// ----------------- Department CRUD UI Helpers -----------------
// Show Add Department modal
window.showAddDepartmentModal = async function() {
    // Create overlay appended to body so it's centered regardless of container position
    const depts = (hospitalOrgData.departments || []).map(d => `<option value="${d.DepartmentID}">${d.DepartmentName}</option>`).join('');
    const overlay = document.createElement('div');
    overlay.id = 'add-dept-overlay';
    overlay.className = 'fixed inset-0 z-60 flex items-center justify-center bg-black/40';
    // Inline fallback styles to ensure centering even if utility classes aren't applied
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.background = 'rgba(0,0,0,0.4)';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Add Department</h5>
                    <button id="add-dept-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="addDeptForm" class="p-4 space-y-3">
                    <div><label class="block text-sm">Department Name</label><input name="DepartmentName" class="w-full p-2 border rounded" required/></div>
                    <div><label class="block text-sm">Department Code</label><input name="DepartmentCode" class="w-full p-2 border rounded" required/></div>
                    <div><label class="block text-sm">Department Type</label>
                        <select name="DepartmentType" class="w-full p-2 border rounded" required>
                            <option value="Executive">Executive</option>
                            <option value="Clinical">Clinical</option>
                            <option value="Administrative">Administrative</option>
                            <option value="Support">Support</option>
                            <option value="Ancillary">Ancillary</option>
                        </select>
                    </div>
                    <div><label class="block text-sm">Parent Department</label>
                        <select name="ParentDepartmentID" class="w-full p-2 border rounded"><option value="">None</option>${depts}</select>
                    </div>
                    <div><label class="block text-sm">Manager</label>
                        <select id="add-dept-manager" name="ManagerID" class="w-full p-2 border rounded"><option value="">-- Select Manager --</option></select>
                    </div>
                    <div><label class="block text-sm">Description</label><textarea name="Description" class="w-full p-2 border rounded" rows="3"></textarea></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="add-dept-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Append overlay to body and wire handlers
    // Ensure the modal inner container is centered using transform fallback
    document.body.appendChild(overlay);
    const addModalInner = overlay.querySelector('div');
    if (addModalInner) {
        addModalInner.style.position = 'fixed';
        addModalInner.style.left = '50%';
        addModalInner.style.top = '50%';
        addModalInner.style.transform = 'translate(-50%, -50%)';
        addModalInner.style.maxWidth = '800px';
        addModalInner.style.width = 'calc(100% - 48px)';
        addModalInner.style.margin = '0';
    }
    // Populate manager select
    populateEmployeeDropdown('add-dept-manager', false).catch(()=>{});

    overlay.querySelector('#add-dept-close')?.addEventListener('click', ()=>{ overlay.remove(); });
    overlay.querySelector('#add-dept-cancel')?.addEventListener('click', ()=>{ overlay.remove(); });

    overlay.querySelector('#addDeptForm')?.addEventListener('submit', async e=>{
        e.preventDefault();
        const fd = new FormData(e.target); const payload = {};
        fd.forEach((v,k)=>{ if (v !== '') payload[k]= v; });
        try {
            const res = await fetch(`${API_BASE_URL}manage_hr_structure.php?entity=department`, { method: 'POST', credentials: 'include', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const j = await res.json();
            if (j.success) { overlay.remove(); await loadHospitalOrgData(); switchOrgView(currentView); Swal.fire('Success','Department created','success'); }
            else Swal.fire('Error', j.error || 'Failed to create department','error');
        } catch (err) { console.error(err); Swal.fire('Error','Server error while creating department','error'); }
    });
}

// Edit department - show modal prefilled
window.editDepartment = function(id) {
    const dept = (hospitalOrgData.departments || []).find(d=>Number(d.DepartmentID)===Number(id));
    if (!dept) { Swal.fire('Error','Department not found','error'); return; }
    // Append overlay to body so it centers regardless of container
    const depts = (hospitalOrgData.departments || []).filter(d=>d.DepartmentID!=id).map(d => `<option value="${d.DepartmentID}" ${d.DepartmentID==dept.ParentDepartmentID?'selected':''}>${d.DepartmentName}</option>`).join('');
    const overlay = document.createElement('div');
    overlay.id = 'edit-dept-overlay';
    overlay.className = 'fixed inset-0 z-60 flex items-center justify-center bg-black/40';
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.background = 'rgba(0,0,0,0.4)';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Edit Department</h5>
                    <button id="edit-dept-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="editDeptForm" class="p-4 space-y-3">
                    <input type="hidden" name="DepartmentID" value="${dept.DepartmentID}" />
                    <div><label class="block text-sm">Department Name</label><input name="DepartmentName" class="w-full p-2 border rounded" value="${dept.DepartmentName}" required/></div>
                    <div><label class="block text-sm">Department Code</label><input name="DepartmentCode" class="w-full p-2 border rounded" value="${dept.DepartmentCode||''}" required/></div>
                    <div><label class="block text-sm">Department Type</label>
                        <select name="DepartmentType" class="w-full p-2 border rounded" required>
                            <option ${dept.DepartmentType==='Executive'?'selected':''}>Executive</option>
                            <option ${dept.DepartmentType==='Clinical'?'selected':''}>Clinical</option>
                            <option ${dept.DepartmentType==='Administrative'?'selected':''}>Administrative</option>
                            <option ${dept.DepartmentType==='Support'?'selected':''}>Support</option>
                            <option ${dept.DepartmentType==='Ancillary'?'selected':''}>Ancillary</option>
                        </select>
                    </div>
                    <div><label class="block text-sm">Parent Department</label>
                        <select name="ParentDepartmentID" class="w-full p-2 border rounded"><option value="">None</option>${depts}</select>
                    </div>
                    <div><label class="block text-sm">Manager</label>
                        <select id="edit-dept-manager" name="ManagerID" class="w-full p-2 border rounded"><option value="">-- Select Manager --</option></select>
                    </div>
                    <div><label class="block text-sm">Description</label><textarea name="Description" class="w-full p-2 border rounded" rows="3">${dept.Description||''}</textarea></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="edit-dept-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Attach overlay and wire handlers
    document.body.appendChild(overlay);
    const editModalInner = overlay.querySelector('div');
    if (editModalInner) {
        editModalInner.style.position = 'fixed';
        editModalInner.style.left = '50%';
        editModalInner.style.top = '50%';
        editModalInner.style.transform = 'translate(-50%, -50%)';
        editModalInner.style.maxWidth = '800px';
        editModalInner.style.width = 'calc(100% - 48px)';
        editModalInner.style.margin = '0';
    }
    // Populate manager select and set value when ready
    populateEmployeeDropdown('edit-dept-manager', false).then(()=>{
        if (dept.ManagerID) {
            const sel = overlay.querySelector('#edit-dept-manager'); if (sel) sel.value = dept.ManagerID;
        }
    }).catch(()=>{});

    overlay.querySelector('#edit-dept-close')?.addEventListener('click', ()=>{ overlay.remove(); });
    overlay.querySelector('#edit-dept-cancel')?.addEventListener('click', ()=>{ overlay.remove(); });

    overlay.querySelector('#editDeptForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>{ if (v !== '') payload[k]= v; });
        try {
            const id = payload.DepartmentID;
            const res = await fetch(`${API_BASE_URL}manage_hr_structure.php?entity=department&id=${id}`, { method: 'PUT', credentials: 'include', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const j = await res.json();
            if (j.success) { overlay.remove(); await loadHospitalOrgData(); switchOrgView(currentView); Swal.fire('Success','Department updated','success'); }
            else Swal.fire('Error', j.error || 'Failed to update department','error');
        } catch(err){ console.error(err); Swal.fire('Error','Server error while updating department','error'); }
    });
}

// View department details
window.viewDepartmentDetails = function(id) {
    const dept = (hospitalOrgData.departments || []).find(d=>Number(d.DepartmentID)===Number(id));
    // Append overlay to body for proper centering
    if (!dept) { Swal.fire('Error','Department not found','error'); return; }
    const overlay = document.createElement('div');
    overlay.id = 'view-dept-overlay';
    overlay.className = 'fixed inset-0 z-60 flex items-center justify-center bg-black/40';
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.background = 'rgba(0,0,0,0.4)';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Department Details</h5>
                    <button id="view-dept-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="p-4">
                    <h4 class="text-lg font-semibold mb-2">${dept.DepartmentName}</h4>
                    <div class="text-sm text-gray-600"><strong>Code:</strong> ${dept.DepartmentCode||'N/A'}</div>
                    <div class="text-sm text-gray-600"><strong>Type:</strong> ${dept.DepartmentType||'N/A'}</div>
                    <div class="text-sm text-gray-600"><strong>Manager:</strong> ${dept.ManagerName||'N/A'}</div>
                    <div class="text-sm text-gray-600 mt-2"><strong>Description:</strong><div class="mt-1 text-gray-700">${dept.Description||''}</div></div>
                </div>
                <div class="flex justify-end p-4 border-t"><button id="view-dept-close-btn" class="px-4 py-2 bg-gray-200 rounded">Close</button></div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    const viewModalInner = overlay.querySelector('div');
    if (viewModalInner) {
        viewModalInner.style.position = 'fixed';
        viewModalInner.style.left = '50%';
        viewModalInner.style.top = '50%';
        viewModalInner.style.transform = 'translate(-50%, -50%)';
        viewModalInner.style.maxWidth = '800px';
        viewModalInner.style.width = 'calc(100% - 48px)';
        viewModalInner.style.margin = '0';
    }
    overlay.querySelector('#view-dept-close')?.addEventListener('click', ()=>{ overlay.remove(); });
    overlay.querySelector('#view-dept-close-btn')?.addEventListener('click', ()=>{ overlay.remove(); });
}

// Delete Department (hard check via API which performs soft delete)
window.deleteDepartment = async function(id) {
    const confirmed = await Swal.fire({ title: 'Delete Department', text: 'Are you sure you want to delete this department? This will deactivate it if there are no active employees.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' });
    if (!confirmed.isConfirmed) return;
    try {
        const res = await fetch(`${API_BASE_URL}manage_hr_structure.php?entity=department`, { method: 'DELETE', credentials: 'include', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
        // Note: API expects id as query param; supporting both
        const j = await res.json();
        if (j.success) { await loadHospitalOrgData(); switchOrgView(currentView); Swal.fire('Deleted','Department deactivated','success'); }
        else if (j.employee_count) { Swal.fire('Cannot delete', `Department has ${j.employee_count} active employees`, 'error'); }
        else Swal.fire('Error', j.error || 'Failed to delete department','error');
    } catch(err){ console.error(err); Swal.fire('Error','Server error while deleting department','error'); }
}

