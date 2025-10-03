import { API_BASE_URL } from '../../utils.js';

export async function renderHMOClaims(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    try{
        const res = await fetch(`${API_BASE_URL}hmo_claims.php`, { credentials:'include' }); const data = await res.json(); const claims = data.claims||[];
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">HMO Claims</h2>
                    <div>
                        <button id="refresh-claims" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="add-claim-btn" class="btn btn-sm btn-success">File Claim</button>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-claims-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Employee</th>
                                <th class="p-3">Plan</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Hospital/Clinic</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        ${claims.length === 0 ? `
                            <tr>
                                <td class="p-6 text-center text-sm text-gray-500" colspan="7">
                                    No claims found. Employees can file a claim using "File Claim". Admins can manage claims in this view.
                                    <div class="mt-2"><button id="empty-file-claim" class="btn btn-sm btn-success">File Claim</button></div>
                                </td>
                            </tr>
                        ` : claims.map(c=>`<tr>
                            <td class="p-3">${c.FirstName?c.FirstName+' '+(c.LastName||''):c.EmployeeID}</td>
                            <td class="p-3">${c.PlanName||''}</td>
                            <td class="p-3">${c.ClaimDate||''}</td>
                            <td class="p-3">${c.HospitalClinic||''}</td>
                            <td class="p-3">${c.ClaimAmount||''}</td>
                            <td class="p-3">${c.ClaimStatus||''}</td>
                            <td class="p-3">
                                <button class="btn btn-sm btn-primary manage-claim" data-id="${c.ClaimID}" data-employee="${c.EmployeeID}">Manage</button>
                                <button class="btn btn-sm btn-secondary view-attachments" data-id="${c.ClaimID}">Attachments</button>
                                <button class="btn btn-sm btn-danger delete-claim" data-id="${c.ClaimID}">Delete</button>
                            </td>
                        </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        document.getElementById('refresh-claims')?.addEventListener('click', ()=>renderHMOClaims(containerId));
        document.getElementById('add-claim-btn')?.addEventListener('click', ()=>showAddClaimModal(containerId));
    document.getElementById('empty-file-claim')?.addEventListener('click', ()=>showAddClaimModal(containerId));
        container.querySelectorAll('.manage-claim').forEach(b=>b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id; showManageModal([id], containerId);
        }));
        container.querySelectorAll('.view-attachments').forEach(b=>b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id; const r = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { credentials:'include' }); const j = await r.json(); const claim = j.claim||{};
            const container = document.getElementById('modalContainer'); if (!container) return; container.innerHTML = `<div class="modal fade" id="viewAttachmentsModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Attachments</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">${(claim.Attachments||[]).length===0?'<div>No attachments</div>':'<ul>'+(claim.Attachments||[]).map(a=>`<li><a href="${a.replace(/\\\\/g,'/') }" target="_blank">${a.split('/').pop()}</a></li>`).join('')+'</ul>'}</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>`;
            $('#viewAttachmentsModal').modal('show');
        }));
        container.querySelectorAll('.delete-claim').forEach(b=>b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id; if (!confirm('Delete claim?')) return; const r = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { method:'DELETE', credentials:'include' }); const j = await r.json(); if (j.success) renderHMOClaims(containerId); else alert(j.error||'Failed');
        }));
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading claims</div>'}
}

export async function showAddClaimModal(containerId='main-content-area'){
    // load enrollments for employee
    const pres = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { credentials:'include' }); const pdata = await pres.json(); const enrollments = pdata.enrollments||[];
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div class="modal fade" id="addClaimModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">File Claim</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="addClaimForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3"><label>Enrollment</label>
                            <select name="enrollment_id" class="form-control" required>
                                <option value="">Select Enrollment</option>
                                ${enrollments.map(e=>`<option value="${e.EnrollmentID}">${e.FirstName||''} ${e.LastName||''} - ${e.PlanName||''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="mb-3"><label>Claim Date</label><input type="date" name="claim_date" class="form-control" required/></div>
                        <div class="mb-3"><label>Hospital/Clinic</label><input name="hospital_clinic" class="form-control"/></div>
                        <div class="mb-3"><label>Diagnosis</label><textarea name="diagnosis" class="form-control"></textarea></div>
                        <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="claim_amount" class="form-control"/></div>
                        <div class="mb-3"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
                        <div class="mb-3"><label>Attachments (receipts)</label><input type="file" name="attachments[]" multiple class="form-control"/></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Submit</button></div>
                </form>
            </div></div>
        </div>
    `;
    $('#addClaimModal').modal('show');
    document.getElementById('addClaimForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const form = e.target; const fd = new FormData(form);
        const res = await fetch(`${API_BASE_URL}hmo_claims.php`, { method:'POST', credentials:'include', body: fd }); const j = await res.json(); if (j.success) { $('#addClaimModal').modal('hide'); renderHMOClaims(containerId); } else alert(j.error||'Failed');
    });
}

export async function showEditClaimModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { credentials:'include' }); const data = await r.json(); const c = data.claim || {};
        // enrollments list
        const pres = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { credentials:'include' }); const pdata = await pres.json(); const enrollments = pdata.enrollments||[];
        const enrollOptions = enrollments.map(e=>`<option value="${e.EnrollmentID}" ${e.EnrollmentID==c.EnrollmentID?'selected':''}>${e.FirstName||''} ${e.LastName||''} - ${e.PlanName||''}</option>`).join('');
        container.innerHTML = `
            <div class="modal fade" id="editClaimModal" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Edit Claim</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form id="editClaimForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="${id}" />
                            <div class="mb-3"><label>Enrollment</label>
                                <select name="enrollment_id" class="form-control" required>
                                    <option value="">Select Enrollment</option>
                                    ${enrollOptions}
                                </select>
                            </div>
                            <div class="mb-3"><label>Claim Date</label><input type="date" name="claim_date" class="form-control" value="${c.ClaimDate||''}" required/></div>
                            <div class="mb-3"><label>Hospital/Clinic</label><input name="hospital_clinic" class="form-control" value="${c.HospitalClinic||''}"/></div>
                            <div class="mb-3"><label>Diagnosis</label><textarea name="diagnosis" class="form-control">${c.Diagnosis||''}</textarea></div>
                            <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="claim_amount" class="form-control" value="${c.ClaimAmount||''}"/></div>
                            <div class="mb-3"><label>Status</label><select name="claim_status" class="form-control"><option value="Pending" ${c.ClaimStatus==='Pending'?'selected':''}>Pending</option><option value="Approved" ${c.ClaimStatus==='Approved'?'selected':''}>Approved</option><option value="Denied" ${c.ClaimStatus==='Denied'?'selected':''}>Denied</option></select></div>
                            <div class="mb-3"><label>Remarks</label><textarea name="remarks" class="form-control">${c.Remarks||''}</textarea></div>
                            <div class="mb-3"><label>Existing Attachments</label>
                                ${(c.Attachments||[]).length===0?'<div>No attachments</div>':'<ul>'+(c.Attachments||[]).map(a=>`<li><a href="${a.replace(/\\\\/g,'/') }" target="_blank">${a.split('/').pop()}</a></li>`).join('')+'</ul>'}
                            </div>
                            <div class="mb-3"><label>Add Attachments</label><input type="file" name="attachments[]" multiple class="form-control"/></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                    </form>
                </div></div>
            </div>
        `;
        $('#editClaimModal').modal('show');
        document.getElementById('editClaimForm')?.addEventListener('submit', async e=>{
            e.preventDefault(); const form = e.target; const fd = new FormData(form);
            const res2 = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { method:'PUT', credentials:'include', body: fd }); const j2 = await res2.json(); if (j2.success) { $('#editClaimModal').modal('hide'); renderHMOClaims(containerId); } else alert(j2.error||'Failed');
        });
    }catch(e){console.error(e); alert('Failed to load claim');}
}

// Manage modal for approve/deny with notes and bulk actions
export function showManageModal(claimIds=[], containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    const ids = Array.isArray(claimIds)?claimIds:([claimIds]);
    container.innerHTML = `
        <div class="modal fade" id="manageClaimsModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Manage Claim(s)</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="manageClaimsForm">
                    <div class="modal-body">
                        <div class="mb-3">Selected Claim IDs: <strong>${ids.join(', ')}</strong></div>
                        <div class="mb-3"><label>Action</label><select name="action" class="form-control"><option value="Approve">Approve</option><option value="Deny">Deny</option></select></div>
                        <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply</button></div>
                </form>
            </div></div>
        </div>
    `;
    $('#manageClaimsModal').modal('show');
    document.getElementById('manageClaimsForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const action = fd.get('action'); const notes = fd.get('notes');
        for (const id of ids) {
            const res = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ claim_status: action==='Approve'?'Approved':'Denied', remarks: notes }) });
            const j = await res.json(); if (!j.success) alert('Failed to update '+id);
        }
        $('#manageClaimsModal').modal('hide'); renderHMOClaims(containerId);
    });
}

// Claim History modal (filter by employee/status/date range)
export function showClaimHistoryModal(employeeId, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div class="modal fade" id="claimHistoryModal" tabindex="-1">
            <div class="modal-dialog modal-lg"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Claim History</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label>Status</label><select id="history-status" class="form-control"><option value="">All</option><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Denied">Denied</option></select></div>
                    <div class="mb-3"><label>From</label><input id="history-from" type="date" class="form-control"/></div>
                    <div class="mb-3"><label>To</label><input id="history-to" type="date" class="form-control"/></div>
                    <div id="history-results"></div>
                </div>
                <div class="modal-footer"><button id="history-refresh" class="btn btn-primary">Refresh</button><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div></div>
        </div>
    `;
    $('#claimHistoryModal').modal('show');
    async function loadHistory(){
        const status = document.getElementById('history-status').value; const from = document.getElementById('history-from').value; const to = document.getElementById('history-to').value;
        const q = new URLSearchParams({ mode:'history', employee_id:employeeId, status, from, to });
        const r = await fetch(`${API_BASE_URL}hmo_claims.php?`+q.toString(), { credentials:'include' }); const j = await r.json(); const rows = j.claims||[];
        document.getElementById('history-results').innerHTML = `<table class="w-full text-left"><thead><tr><th>Plan</th><th>Date</th><th>Hospital</th><th>Amount</th><th>Status</th><th>Remarks</th></tr></thead><tbody>${rows.map(rr=>`<tr><td>${rr.PlanName||''}</td><td>${rr.ClaimDate||''}</td><td>${rr.HospitalClinic||''}</td><td>${rr.ClaimAmount||''}</td><td>${rr.ClaimStatus||''}</td><td>${rr.Remarks||''}</td></tr>`).join('')}</tbody></table>`;
    }
    document.getElementById('history-refresh')?.addEventListener('click', loadHistory);
    loadHistory();
}
