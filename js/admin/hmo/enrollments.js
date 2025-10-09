import { API_BASE_URL } from '../../utils.js';

export async function renderHMOEnrollments(containerId='main-content-area'){
    const container = document.getElementById(containerId); 
    if (!container) return;

    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="text-gray-500 mt-4">Loading enrollments...</p>
            </div>
        </div>
    `;

    try{
        const res = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { credentials:'include' });
        if (!res.ok) {
            const text = await res.text();
            console.error('API Error Response:', text);
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const data = await res.json(); 
        const enrollments = data.data?.enrollments || data.enrollments || [];

        // Calculate statistics
        const activeCount = enrollments.filter(e => e.Status === 'Active').length;
        const pendingCount = enrollments.filter(e => e.Status === 'Pending').length;

        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Enhanced Header -->
                <div class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white px-6 py-4 rounded-t-lg">
                    <div class="flex justify-between items-center">
                    <div>
                            <h2 class="text-2xl font-bold mb-1">HMO Enrollments</h2>
                            <p class="text-sm text-blue-100">Manage employee health insurance enrollments</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-enrollments" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="export-enrollments" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button id="add-enrollment-btn" class="px-4 py-2 bg-white text-blue-600 hover:bg-blue-50 font-semibold rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-user-plus"></i>
                                <span>Enroll Employee</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-users text-blue-600"></i>
                            <span class="text-sm text-gray-600">Total Enrollments:</span>
                            <span class="font-semibold text-gray-900">${enrollments.length}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-600">Active:</span>
                            <span class="font-semibold text-green-600">${activeCount}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-yellow-600"></i>
                            <span class="text-sm text-gray-600">Pending:</span>
                            <span class="font-semibold text-yellow-600">${pendingCount}</span>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="hmo-enrollments-table">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        ${enrollments.length === 0 ? `
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <i class="fas fa-user-shield text-gray-300 text-5xl"></i>
                                        <p class="text-gray-500 text-lg font-medium">No enrollments found</p>
                                        <p class="text-gray-400 text-sm">Start enrolling employees in HMO plans</p>
                                        <button id="empty-add-enrollment" class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                                            <i class="fas fa-user-plus mr-2"></i>Enroll First Employee
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ` : enrollments.map(e=>{
                            const employeeName = e.FirstName ? `${e.FirstName} ${e.LastName||''}`.trim() : `Employee #${e.EmployeeID}`;
                            let statusBadge = '';
                            if (e.Status === 'Active') {
                                statusBadge = '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Active</span>';
                            } else if (e.Status === 'Pending') {
                                statusBadge = '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>Pending</span>';
                            } else if (e.Status === 'Terminated') {
                                statusBadge = '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Terminated</span>';
                            } else {
                                statusBadge = '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">' + (e.Status || 'Unknown') + '</span>';
                            }
                            
                            return `
                            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">${employeeName}</div>
                                            <div class="text-xs text-gray-500">ID: ${e.EmployeeID}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">${e.PlanName || 'N/A'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">${e.StartDate || 'N/A'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">${e.EndDate || 'N/A'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button class="text-blue-600 hover:text-blue-800 edit-enrollment" data-id="${e.EnrollmentID}" title="Edit Enrollment">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        ${e.Status === 'Active' ? `<button class="text-orange-600 hover:text-orange-800 terminate-enrollment" data-id="${e.EnrollmentID}" title="Terminate Enrollment">
                                            <i class="fas fa-ban"></i>
                                        </button>` : ''}
                                        <button class="text-red-600 hover:text-red-800 delete-enrollment" data-id="${e.EnrollmentID}" title="Delete Enrollment">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                            </td>
                            </tr>`;
                        }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        document.getElementById('refresh-enrollments')?.addEventListener('click', ()=>renderHMOEnrollments(containerId));
        document.getElementById('add-enrollment-btn')?.addEventListener('click', ()=>showAddEnrollmentModal(containerId));
    document.getElementById('empty-add-enrollment')?.addEventListener('click', ()=>showAddEnrollmentModal(containerId));
        document.getElementById('export-enrollments')?.addEventListener('click', ()=>exportEnrollmentsToCSV(enrollments));
        container.querySelectorAll('.edit-enrollment').forEach(b=>b.addEventListener('click', async ev=>{ const id = ev.target.dataset.id; if (!id) return; showEditEnrollmentModal(id, containerId); }));
        container.querySelectorAll('.terminate-enrollment').forEach(b=>b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id; if (!confirm('Terminate enrollment?')) return; const r = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify({status:'Terminated'}) }); const j = await r.json(); if (j.success) renderHMOEnrollments(containerId); else alert(j.error||'Failed');
        }));
        container.querySelectorAll('.delete-enrollment').forEach(b=>b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id;
            
            // Get enrollment details first
            try {
                const response = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { 
                    credentials: 'include' 
                });
                const data = await response.json();
                const enrollment = data.enrollment;

                if (!enrollment) {
                    alert('Enrollment not found');
                    return;
                }

                const employeeName = `${enrollment.FirstName || ''} ${enrollment.LastName || ''}`.trim();
                const confirmMessage = `Are you sure you want to delete the enrollment for ${employeeName}?\n\nPlan: ${enrollment.PlanName}\nStatus: ${enrollment.Status}\n\nThis action cannot be undone.`;

                if (!confirm(confirmMessage)) {
                    return;
                }

                try {
                    const r = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { 
                        method: 'DELETE', 
                        credentials: 'include' 
                    });
                    const j = await r.json();
                    
                    if (j.success) {
                        // Force a complete refresh
                        try {
                            await renderHMOEnrollments(containerId);
                            console.log('Enrollment deleted and table refreshed');
                        } catch(err) {
                            console.error('Error refreshing enrollments:', err);
                            alert('Enrollment was deleted but the table refresh failed. Please refresh the page manually.');
                        }
                    } else {
                        alert(j.error || 'Failed to delete enrollment. It may have active claims or be in an invalid state.');
                    }
                } catch (error) {
                    console.error('Error deleting enrollment:', error);
                    alert('Failed to delete enrollment. Please check your connection and try again.');
                }
            } catch (error) {
                console.error('Error fetching enrollment details:', error);
                alert('Could not get enrollment details. Please try again.');
            }
        }));
    }catch(e){
        console.error(e); 
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <div class="flex items-center space-x-3 text-red-600">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Error Loading Enrollments</h3>
                        <p class="text-sm text-red-500 mt-1">${e.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

// CSV Export Function
function exportEnrollmentsToCSV(enrollments) {
    if (!enrollments || enrollments.length === 0) {
        alert('No enrollments to export');
        return;
    }

    // Define CSV headers
    const headers = ['Enrollment ID', 'Employee ID', 'Employee Name', 'Plan', 'Start Date', 'End Date', 'Status'];
    
    // Convert enrollments data to CSV rows
    const rows = enrollments.map(e => {
        const employeeName = e.FirstName ? `${e.FirstName} ${e.LastName||''}`.trim() : '';
        
        return [
            e.EnrollmentID || '',
            e.EmployeeID || '',
            employeeName,
            e.PlanName || '',
            e.StartDate || '',
            e.EndDate || '',
            e.Status || ''
        ];
    });

    // Combine headers and rows
    const csvContent = [
        headers.join(','),
        ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    ].join('\n');

    // Create and trigger download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `hmo_enrollments_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log(`Exported ${enrollments.length} enrollments to CSV`);
}

export async function showAddEnrollmentModal(containerId='main-content-area'){
    // Load plans list and employees for selection
    const pres = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials:'include' }); const pdata = await pres.json(); const plans = pdata.plans||[];
    const eres = await fetch(`${API_BASE_URL}get_employees.php`, { credentials:'include' }); const employees = await eres.json();
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div id="add-enrollment-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Add Enrollment</h5>
                    <button id="add-enrollment-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="addEnrollmentForm" class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm">Employee</label>
                        <select name="employee_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Employee</option>
                            ${ (Array.isArray(employees)?employees:[]).map(emp=>`<option value="${emp.EmployeeID}">${emp.FirstName||''} ${emp.LastName||''} (${emp.EmployeeID})</option>`).join('') }
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Plan</label>
                        <select name="plan_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Plan</option>
                            ${plans.map(p=>`<option value="${p.PlanID}">${p.PlanName} (${p.ProviderName||''})</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Start Date</label>
                        <input type="date" name="start_date" class="w-full p-2 border rounded" required/>
                    </div>
                    <div>
                        <label class="block text-sm">End Date</label>
                        <input type="date" name="end_date" class="w-full p-2 border rounded"/>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="add-enrollment-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.getElementById('add-enrollment-close')?.addEventListener('click', ()=>{ document.getElementById('add-enrollment-overlay')?.remove(); });
    document.getElementById('add-enrollment-cancel')?.addEventListener('click', ()=>{ document.getElementById('add-enrollment-overlay')?.remove(); });
    document.getElementById('addEnrollmentForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
        const res = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j = await res.json(); if (j.success) { document.getElementById('add-enrollment-overlay')?.remove(); renderHMOEnrollments(containerId); } else alert(j.error||'Failed');
    });
}

export async function showEditEnrollmentModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { credentials:'include' }); const data = await r.json(); const e = data.enrollment||{};
        const pres = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials:'include' }); const pdata = await pres.json(); const plans = pdata.plans||[];
        const planOptions = plans.map(p=>`<option value="${p.PlanID}" ${p.PlanID==e.PlanID?'selected':''}>${p.PlanName} (${p.ProviderName||''})</option>`).join('');
        // fetch employees for dropdown
        const eres = await fetch(`${API_BASE_URL}get_employees.php`, { credentials:'include' });
        const employees = await eres.json();
        const employeeOptions = (Array.isArray(employees)?employees:[]).map(emp => {
            const name = (emp.FirstName?emp.FirstName:'') + (emp.LastName?(' '+emp.LastName):'');
            return `<option value="${emp.EmployeeID}" ${emp.EmployeeID==e.EmployeeID?'selected':''}>${name} (${emp.EmployeeID})</option>`;
        }).join('');

        container.innerHTML = `
            <div id="edit-enrollment-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-xl mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Edit Enrollment</h5>
                        <button id="edit-enrollment-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <form id="editEnrollmentForm" class="p-4 space-y-3">
                        <input type="hidden" name="id" value="${id}" />
                        <div>
                            <label class="block text-sm">Employee</label>
                            <select name="employee_id" class="w-full p-2 border rounded" required>
                                <option value="">Select Employee</option>
                                ${employeeOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm">Plan</label>
                            <select name="plan_id" class="w-full p-2 border rounded" required>
                                <option value="">Select Plan</option>
                                ${planOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm">Start Date</label>
                            <input type="date" name="start_date" class="w-full p-2 border rounded" value="${e.StartDate||''}" required/>
                        </div>
                        <div>
                            <label class="block text-sm">End Date</label>
                            <input type="date" name="end_date" class="w-full p-2 border rounded" value="${e.EndDate||''}"/>
                        </div>
                        <div>
                            <label class="block text-sm">Status</label>
                            <select name="status" class="w-full p-2 border rounded"><option value="Active" ${e.Status==='Active'?'selected':''}>Active</option><option value="Terminated" ${e.Status==='Terminated'?'selected':''}>Terminated</option></select>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="edit-enrollment-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.getElementById('edit-enrollment-close')?.addEventListener('click', ()=>{ document.getElementById('edit-enrollment-overlay')?.remove(); });
        document.getElementById('edit-enrollment-cancel')?.addEventListener('click', ()=>{ document.getElementById('edit-enrollment-overlay')?.remove(); });
        document.getElementById('editEnrollmentForm')?.addEventListener('submit', async e2=>{
            e2.preventDefault(); const fd = new FormData(e2.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
            const res2 = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j2 = await res2.json(); if (j2.success) { document.getElementById('edit-enrollment-overlay')?.remove(); renderHMOEnrollments(containerId); } else alert(j2.error||'Failed');
        });
    }catch(e){console.error(e); alert('Failed to load enrollment');}
}
