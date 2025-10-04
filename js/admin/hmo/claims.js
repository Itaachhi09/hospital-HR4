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
                        <button id="refresh-claims" class="hmo-btn hmo-btn-primary">Refresh</button>
                        <button id="add-claim-btn" class="hmo-btn hmo-btn-success">File Claim</button>
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
                                    <div class="mt-2"><button id="empty-file-claim" class="hmo-btn hmo-btn-success">File Claim</button></div>
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
                                <button class="hmo-btn hmo-btn-primary manage-claim" data-id="${c.ClaimID}" data-employee="${c.EmployeeID}">Manage</button>
                                <button class="hmo-btn hmo-btn-secondary view-attachments" data-id="${c.ClaimID}">Attachments</button>
                                <button class="hmo-btn hmo-btn-danger delete-claim" data-id="${c.ClaimID}">Delete</button>
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
            const container = document.getElementById('modalContainer'); if (!container) return;
            container.innerHTML = `
                <div id="view-attachments-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
                        <div class="flex items-center justify-between px-4 py-3 border-b">
                            <h5 class="text-lg font-semibold">Attachments</h5>
                            <button id="view-attachments-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                        </div>
                        <div class="p-4">
                            ${(claim.Attachments||[]).length===0?'<div>No attachments</div>':'<ul>'+(claim.Attachments||[]).map(a=>`<li><a href="${a.replace(/\\\\/g,'/') }" target="_blank">${a.split('/').pop()}</a></li>`).join('')+'</ul>'}
                        </div>
                        <div class="flex justify-end p-4 border-t"><button id="view-attachments-close-btn" class="px-4 py-2 bg-gray-200 rounded">Close</button></div>
                    </div>
                </div>
            `;
            document.getElementById('view-attachments-close')?.addEventListener('click', ()=>{ document.getElementById('view-attachments-overlay')?.remove(); });
            document.getElementById('view-attachments-close-btn')?.addEventListener('click', ()=>{ document.getElementById('view-attachments-overlay')?.remove(); });
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
        <div id="add-claim-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">File Claim</h5>
                    <button id="add-claim-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="addClaimForm" enctype="multipart/form-data" class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm">Enrollment</label>
                        <select name="enrollment_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Enrollment</option>
                            ${enrollments.map(e=>`<option value="${e.EnrollmentID}">${e.FirstName||''} ${e.LastName||''} - ${e.PlanName||''}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Claim Date</label>
                        <input type="date" name="claim_date" class="w-full p-2 border rounded" required/>
                    </div>
                    <div>
                        <label class="block text-sm">Hospital/Clinic</label>
                        <input name="hospital_clinic" class="w-full p-2 border rounded"/>
                    </div>
                    <div>
                        <label class="block text-sm">Diagnosis</label>
                        <textarea name="diagnosis" class="w-full p-2 border rounded"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm">Amount</label>
                        <input type="number" step="0.01" name="claim_amount" class="w-full p-2 border rounded"/>
                    </div>
                    <div>
                        <label class="block text-sm">Remarks</label>
                        <textarea name="remarks" class="w-full p-2 border rounded"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm">Attachments (receipts)</label>
                        <input type="file" name="attachments[]" multiple class="w-full"/>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="add-claim-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.getElementById('add-claim-close')?.addEventListener('click', ()=>{ document.getElementById('add-claim-overlay')?.remove(); });
    document.getElementById('add-claim-cancel')?.addEventListener('click', ()=>{ document.getElementById('add-claim-overlay')?.remove(); });
    document.getElementById('addClaimForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const form = e.target; const fd = new FormData(form);
        const res = await fetch(`${API_BASE_URL}hmo_claims.php`, { method:'POST', credentials:'include', body: fd }); const j = await res.json(); if (j.success) { document.getElementById('add-claim-overlay')?.remove(); renderHMOClaims(containerId); } else alert(j.error||'Failed');
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
            <div id="edit-claim-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Edit Claim</h5>
                        <button id="edit-claim-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <form id="editClaimForm" enctype="multipart/form-data" class="p-4 space-y-3">
                        <input type="hidden" name="id" value="${id}" />
                        <div>
                            <label class="block text-sm">Enrollment</label>
                            <select name="enrollment_id" class="w-full p-2 border rounded" required>
                                <option value="">Select Enrollment</option>
                                ${enrollOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm">Claim Date</label>
                            <input type="date" name="claim_date" class="w-full p-2 border rounded" value="${c.ClaimDate||''}" required/>
                        </div>
                        <div>
                            <label class="block text-sm">Hospital/Clinic</label>
                            <input name="hospital_clinic" class="w-full p-2 border rounded" value="${c.HospitalClinic||''}"/>
                        </div>
                        <div>
                            <label class="block text-sm">Diagnosis</label>
                            <textarea name="diagnosis" class="w-full p-2 border rounded">${c.Diagnosis||''}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm">Amount</label>
                            <input type="number" step="0.01" name="claim_amount" class="w-full p-2 border rounded" value="${c.ClaimAmount||''}"/>
                        </div>
                        <div>
                            <label class="block text-sm">Status</label>
                            <select name="claim_status" class="w-full p-2 border rounded"><option value="Pending" ${c.ClaimStatus==='Pending'?'selected':''}>Pending</option><option value="Approved" ${c.ClaimStatus==='Approved'?'selected':''}>Approved</option><option value="Denied" ${c.ClaimStatus==='Denied'?'selected':''}>Denied</option></select>
                        </div>
                        <div>
                            <label class="block text-sm">Remarks</label>
                            <textarea name="remarks" class="w-full p-2 border rounded">${c.Remarks||''}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm">Existing Attachments</label>
                            ${(c.Attachments||[]).length===0?'<div>No attachments</div>':'<ul>'+(c.Attachments||[]).map(a=>`<li><a href="${a.replace(/\\\\/g,'/') }" target="_blank">${a.split('/').pop()}</a></li>`).join('')+'</ul>'}
                        </div>
                        <div>
                            <label class="block text-sm">Add Attachments</label>
                            <input type="file" name="attachments[]" multiple class="w-full"/>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="edit-claim-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.getElementById('edit-claim-close')?.addEventListener('click', ()=>{ document.getElementById('edit-claim-overlay')?.remove(); });
        document.getElementById('edit-claim-cancel')?.addEventListener('click', ()=>{ document.getElementById('edit-claim-overlay')?.remove(); });
        document.getElementById('editClaimForm')?.addEventListener('submit', async e=>{
            e.preventDefault(); const form = e.target; const fd = new FormData(form);
            const res2 = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { method:'PUT', credentials:'include', body: fd }); const j2 = await res2.json(); if (j2.success) { document.getElementById('edit-claim-overlay')?.remove(); renderHMOClaims(containerId); } else alert(j2.error||'Failed');
        });
    }catch(e){console.error(e); alert('Failed to load claim');}
}

// Manage modal for approve/deny with notes and bulk actions
export function showManageModal(claimIds=[], containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    const ids = Array.isArray(claimIds)?claimIds:([claimIds]);
    container.innerHTML = `
        <div id="manage-claims-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Manage Claim(s)</h5>
                    <button id="manage-claims-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="manageClaimsForm" class="p-4 space-y-3">
                    <div>Selected Claim IDs: <strong>${ids.join(', ')}</strong></div>
                    <div>
                        <label class="block text-sm">Action</label>
                        <select name="action" class="w-full p-2 border rounded"><option value="Approve">Approve</option><option value="Deny">Deny</option></select>
                    </div>
                    <div>
                        <label class="block text-sm">Notes</label>
                        <textarea name="notes" class="w-full p-2 border rounded"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="manage-claims-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.getElementById('manage-claims-close')?.addEventListener('click', ()=>{ document.getElementById('manage-claims-overlay')?.remove(); });
    document.getElementById('manage-claims-cancel')?.addEventListener('click', ()=>{ document.getElementById('manage-claims-overlay')?.remove(); });
    document.getElementById('manageClaimsForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const action = fd.get('action'); const notes = fd.get('notes');
        for (const id of ids) {
            const res = await fetch(`${API_BASE_URL}hmo_claims.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ claim_status: action==='Approve'?'Approved':'Denied', remarks: notes }) });
            const j = await res.json(); if (!j.success) alert('Failed to update '+id);
        }
        document.getElementById('manage-claims-overlay')?.remove(); renderHMOClaims(containerId);
    });
}

// Claim History modal (filter by employee/status/date range)
export function showClaimHistoryModal(employeeId, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div id="claim-history-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Claim History</h5>
                    <button id="claim-history-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="p-4">
                    <div class="mb-3"><label class="block text-sm">Status</label><select id="history-status" class="w-full p-2 border rounded"><option value="">All</option><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Denied">Denied</option></select></div>
                    <div class="mb-3"><label class="block text-sm">From</label><input id="history-from" type="date" class="w-full p-2 border rounded"/></div>
                    <div class="mb-3"><label class="block text-sm">To</label><input id="history-to" type="date" class="w-full p-2 border rounded"/></div>
                    <div id="history-results"></div>
                </div>
                <div class="flex justify-end p-4 border-t"><button id="history-refresh" class="px-4 py-2 bg-[#594423] text-white rounded">Refresh</button><button id="claim-history-close-btn" class="ml-2 px-4 py-2 bg-gray-200 rounded">Close</button></div>
            </div>
        </div>
    `;
    document.getElementById('claim-history-close')?.addEventListener('click', ()=>{ document.getElementById('claim-history-overlay')?.remove(); });
    document.getElementById('claim-history-close-btn')?.addEventListener('click', ()=>{ document.getElementById('claim-history-overlay')?.remove(); });
    async function loadHistory(){
        const status = document.getElementById('history-status').value; const from = document.getElementById('history-from').value; const to = document.getElementById('history-to').value;
        const q = new URLSearchParams({ mode:'history', employee_id:employeeId, status, from, to });
        const r = await fetch(`${API_BASE_URL}hmo_claims.php?`+q.toString(), { credentials:'include' }); const j = await r.json(); const rows = j.claims||[];
        document.getElementById('history-results').innerHTML = `<table class="w-full text-left"><thead><tr><th>Plan</th><th>Date</th><th>Hospital</th><th>Amount</th><th>Status</th><th>Remarks</th></tr></thead><tbody>${rows.map(rr=>`<tr><td>${rr.PlanName||''}</td><td>${rr.ClaimDate||''}</td><td>${rr.HospitalClinic||''}</td><td>${rr.ClaimAmount||''}</td><td>${rr.ClaimStatus||''}</td><td>${rr.Remarks||''}</td></tr>`).join('')}</tbody></table>`;
    }
    document.getElementById('history-refresh')?.addEventListener('click', loadHistory);
    loadHistory();
}
