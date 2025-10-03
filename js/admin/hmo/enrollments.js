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
                        <button id="refresh-enrollments" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="add-enrollment-btn" class="btn btn-sm btn-success">Enroll Employee</button>
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
                                    <div class="mt-2"><button id="empty-add-enrollment" class="btn btn-sm btn-success">Enroll Employee</button></div>
                                </td>
                            </tr>
                        ` : enrollments.map(e=>`<tr>
                            <td class="p-3">${e.FirstName?e.FirstName+' '+(e.LastName||''):e.EmployeeID}</td>
                            <td class="p-3">${e.PlanName||''}</td>
                            <td class="p-3">${e.StartDate||''}</td>
                            <td class="p-3">${e.EndDate||''}</td>
                            <td class="p-3">${e.Status||''}</td>
                            <td class="p-3">
                                <button class="btn btn-sm btn-secondary edit-enrollment" data-id="${e.EnrollmentID}">Edit</button>
                                <button class="btn btn-sm btn-warning terminate-enrollment" data-id="${e.EnrollmentID}">Terminate</button>
                                <button class="btn btn-sm btn-danger delete-enrollment" data-id="${e.EnrollmentID}">Remove</button>
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
            const id = ev.target.dataset.id; if (!confirm('Remove enrollment?')) return; const r = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { method:'DELETE', credentials:'include' }); const j = await r.json(); if (j.success) renderHMOEnrollments(containerId); else alert(j.error||'Failed');
        }));
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading enrollments</div>'}
}

export async function showAddEnrollmentModal(containerId='main-content-area'){
    // Load plans list and employees for selection
    const pres = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials:'include' }); const pdata = await pres.json(); const plans = pdata.plans||[];
    const eres = await fetch(`${API_BASE_URL}get_employees.php`, { credentials:'include' }); const employees = await eres.json();
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div class="modal fade" id="addEnrollmentModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Enrollment</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="addEnrollmentForm">
                    <div class="modal-body">
                        <div class="mb-3"><label>Employee</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">Select Employee</option>
                                ${ (Array.isArray(employees)?employees:[]).map(emp=>`<option value="${emp.EmployeeID}">${emp.FirstName||''} ${emp.LastName||''} (${emp.EmployeeID})</option>`).join('') }
                            </select>
                        </div>
                        <div class="mb-3"><label>Plan</label>
                            <select name="plan_id" class="form-control" required>
                                <option value="">Select Plan</option>
                                ${plans.map(p=>`<option value="${p.PlanID}">${p.PlanName} (${p.ProviderName||''})</option>`).join('')}
                            </select>
                        </div>
                        <div class="mb-3"><label>Start Date</label><input type="date" name="start_date" class="form-control" required/></div>
                        <div class="mb-3"><label>End Date</label><input type="date" name="end_date" class="form-control"/></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                </form>
            </div></div>
        </div>
    `;
    $('#addEnrollmentModal').modal('show');
    document.getElementById('addEnrollmentForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
        const res = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j = await res.json(); if (j.success) { $('#addEnrollmentModal').modal('hide'); renderHMOEnrollments(containerId); } else alert(j.error||'Failed');
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
            <div class="modal fade" id="editEnrollmentModal" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Edit Enrollment</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form id="editEnrollmentForm">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="${id}" />
                            <div class="mb-3"><label>Employee</label>
                                <select name="employee_id" class="form-control" required>
                                    <option value="">Select Employee</option>
                                    ${employeeOptions}
                                </select>
                            </div>
                            <div class="mb-3"><label>Plan</label>
                                <select name="plan_id" class="form-control" required>
                                    <option value="">Select Plan</option>
                                    ${planOptions}
                                </select>
                            </div>
                            <div class="mb-3"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="${e.StartDate||''}" required/></div>
                            <div class="mb-3"><label>End Date</label><input type="date" name="end_date" class="form-control" value="${e.EndDate||''}"/></div>
                            <div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="Active" ${e.Status==='Active'?'selected':''}>Active</option><option value="Terminated" ${e.Status==='Terminated'?'selected':''}>Terminated</option></select></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                    </form>
                </div></div>
            </div>
        `;
        $('#editEnrollmentModal').modal('show');
        document.getElementById('editEnrollmentForm')?.addEventListener('submit', async e2=>{
            e2.preventDefault(); const fd = new FormData(e2.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
            const res2 = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j2 = await res2.json(); if (j2.success) { $('#editEnrollmentModal').modal('hide'); renderHMOEnrollments(containerId); } else alert(j2.error||'Failed');
        });
    }catch(e){console.error(e); alert('Failed to load enrollment');}
}
