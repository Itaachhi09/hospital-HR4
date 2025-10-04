import { API_BASE_URL } from '../../utils.js';

export async function renderHMOEnrollments(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    try{
        const res = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { credentials:'include' }); const data = await res.json(); const enrollments = data.enrollments||[];
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">Employee Enrollments</h2>
                    <div>
                        <button id="refresh-enrollments" class="hmo-btn hmo-btn-primary">Refresh</button>
                        <button id="add-enrollment-btn" class="hmo-btn hmo-btn-success">Enroll Employee</button>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-enrollments-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Employee</th>
                                <th class="p-3">Plan</th>
                                <th class="p-3">Start</th>
                                <th class="p-3">End</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        ${enrollments.length === 0 ? `
                            <tr>
                                <td class="p-6 text-center text-sm text-gray-500" colspan="6">
                                    No employee enrollments found. Use "Enroll Employee" to add a new enrollment. Employees can also enroll themselves via the employee portal.
                                    <div class="mt-2"><button id="empty-add-enrollment" class="hmo-btn hmo-btn-success">Enroll Employee</button></div>
                                </td>
                            </tr>
                        ` : enrollments.map(e=>`<tr>
                            <td class="p-3">${e.FirstName?e.FirstName+' '+(e.LastName||''):e.EmployeeID}</td>
                            <td class="p-3">${e.PlanName||''}</td>
                            <td class="p-3">${e.StartDate||''}</td>
                            <td class="p-3">${e.EndDate||''}</td>
                            <td class="p-3">${e.Status||''}</td>
                            <td class="p-3">
                                <button class="hmo-btn hmo-btn-secondary edit-enrollment" data-id="${e.EnrollmentID}">Edit</button>
                                <button class="hmo-btn hmo-btn-warning terminate-enrollment" data-id="${e.EnrollmentID}">Terminate</button>
                                <button class="hmo-btn hmo-btn-danger delete-enrollment" data-id="${e.EnrollmentID}">Remove</button>
                            </td>
                        </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        document.getElementById('refresh-enrollments')?.addEventListener('click', ()=>renderHMOEnrollments(containerId));
        document.getElementById('add-enrollment-btn')?.addEventListener('click', ()=>showAddEnrollmentModal(containerId));
    document.getElementById('empty-add-enrollment')?.addEventListener('click', ()=>showAddEnrollmentModal(containerId));
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
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading enrollments</div>'}
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
