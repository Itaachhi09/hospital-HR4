import { REST_API_URL } from '../../utils.js';

// Primary renderer for HMO Claims admin section
export async function renderHMOClaims(containerId = 'main-content-area') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="text-gray-500 mt-4">Loading HMO claims...</p>
            </div>
        </div>
    `;

    try {
        const res = await fetch(`${REST_API_URL}hmo/claims`, { credentials: 'include' });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const response = await res.json();
        const claims = response.data?.claims || response.claims || [];
        
        // Store cache for filtering
        window._hmoClaimsCache = claims;
        
        // Calculate statistics
        const pendingCount = claims.filter(c => c.ClaimStatus === 'Pending').length;
        const approvedCount = claims.filter(c => c.ClaimStatus === 'Approved').length;
        const deniedCount = claims.filter(c => c.ClaimStatus === 'Denied').length;
        
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Enhanced Header -->
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-4 rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold mb-1">HMO Claims</h2>
                            <p class="text-sm text-purple-100">Manage and process employee health insurance claims</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-claims" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="export-claims" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button id="add-claim-btn" class="px-4 py-2 bg-white text-purple-600 hover:bg-purple-50 font-semibold rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-plus-circle"></i>
                                <span>File Claim</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-file-medical text-purple-600"></i>
                            <span class="text-sm text-gray-600">Total Claims:</span>
                            <span class="font-semibold text-gray-900">${claims.length}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-yellow-600"></i>
                            <span class="text-sm text-gray-600">Pending:</span>
                            <span class="font-semibold text-yellow-600">${pendingCount}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-600">Approved:</span>
                            <span class="font-semibold text-green-600">${approvedCount}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-times-circle text-red-600"></i>
                            <span class="text-sm text-gray-600">Denied:</span>
                            <span class="font-semibold text-red-600">${deniedCount}</span>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[300px] relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input id="hmo-claim-search" type="text" placeholder="Search employee, plan, or hospital..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </div>
                        <select id="hmo-claim-status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Denied">Denied</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="hmo-claims-table">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Hospital/Clinic</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hmo-claims-tbody" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        `;

        // Populate table
        populateClaimsTbody(window._hmoClaimsCache, containerId);

        // Wire controls
        document.getElementById('refresh-claims')?.addEventListener('click', () => renderHMOClaims(containerId));
        document.getElementById('add-claim-btn')?.addEventListener('click', () => showAddClaimModal(containerId));
        document.getElementById('export-claims')?.addEventListener('click', exportClaimsToCSV);
        document.getElementById('hmo-claim-search')?.addEventListener('input', () => applyClaimFilters(containerId));
        document.getElementById('hmo-claim-status-filter')?.addEventListener('change', () => applyClaimFilters(containerId));

    } catch (e) {
        console.error('Error loading HMO claims', e);
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <div class="flex items-center space-x-3 text-red-600">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Error Loading Claims</h3>
                        <p class="text-sm text-red-500 mt-1">${e.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

function applyClaimFilters(containerId = 'main-content-area') {
    const q = (document.getElementById('hmo-claim-search')?.value || '').toLowerCase().trim();
    const status = (document.getElementById('hmo-claim-status-filter')?.value || '');
    const all = window._hmoClaimsCache || [];
    const filtered = all.filter(c => {
        if (status && c.ClaimStatus !== status) return false;
        if (!q) return true;
        const name = c.FirstName ? (c.FirstName + ' ' + (c.LastName || '')) : '';
        return name.toLowerCase().includes(q) ||
               (c.PlanName || '').toLowerCase().includes(q) ||
               (c.HospitalClinic || '').toLowerCase().includes(q);
    });
    populateClaimsTbody(filtered, containerId);
}

function populateClaimsTbody(claims, containerId = 'main-content-area') {
    const tbody = document.getElementById('hmo-claims-tbody');
    if (!tbody) return;

    if (!claims || claims.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <i class="fas fa-file-medical text-gray-300 text-5xl"></i>
                        <p class="text-gray-500 text-lg font-medium">No claims found</p>
                        <p class="text-gray-400 text-sm">File a new claim to get started</p>
                        <button id="empty-add-claim" class="mt-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-plus-circle mr-2"></i>File Your First Claim
                        </button>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('empty-add-claim')?.addEventListener('click', () => showAddClaimModal(containerId));
        return;
    }

    tbody.innerHTML = claims.map(c => {
        const name = c.FirstName ? (c.FirstName + (c.LastName ? ' ' + c.LastName : '')) : (c.EmployeeID || 'N/A');
        const statusBadge = c.ClaimStatus === 'Approved' 
            ? '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Approved</span>'
            : c.ClaimStatus === 'Denied'
            ? '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Denied</span>'
            : '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>Pending</span>';
        
        const amount = c.ClaimAmount ? `₱${parseFloat(c.ClaimAmount).toLocaleString()}` : 'N/A';
        
        return `
            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${c.PlanName || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${c.ClaimDate || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${c.HospitalClinic || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-medium">${amount}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                        <button class="text-blue-600 hover:text-blue-800 manage-claim" data-id="${c.ClaimID}" title="Manage Claim">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="text-purple-600 hover:text-purple-800 view-attachments" data-id="${c.ClaimID}" title="View Attachments">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-800 delete-claim" data-id="${c.ClaimID}" title="Delete Claim">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    // Wire row buttons
    tbody.querySelectorAll('.manage-claim').forEach(btn => btn.addEventListener('click', (ev) => {
        const id = ev.target.closest('button').dataset.id;
        showManageModal([id], containerId);
    }));

    tbody.querySelectorAll('.view-attachments').forEach(btn => btn.addEventListener('click', async (ev) => {
        const id = ev.target.closest('button').dataset.id;
        const r = await fetch(`${REST_API_URL}hmo/claims/${id}`, { credentials: 'include' });
        const j = await r.json();
        const claim = j.claim || {};
        const modal = document.getElementById('modalContainer'); if (!modal) return;
        const attachments = claim.Attachments || [];
        modal.innerHTML = `
            <div id="view-attachments-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Attachments</h5>
                        <button id="view-attachments-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <div class="p-4">
                        ${attachments.length === 0 ? '<div>No attachments</div>' : '<ul>' + attachments.map(a => `<li><a href="${a.replace(/\\/g,'/')}" target="_blank">${a.split('/').pop()}</a></li>`).join('') + '</ul>'}
                    </div>
                    <div class="flex justify-end p-4 border-t"><button id="view-attachments-close-btn" class="px-4 py-2 bg-gray-200 rounded">Close</button></div>
                </div>
            </div>`;
        document.getElementById('view-attachments-close')?.addEventListener('click', () => { document.getElementById('view-attachments-overlay')?.remove(); });
        document.getElementById('view-attachments-close-btn')?.addEventListener('click', () => { document.getElementById('view-attachments-overlay')?.remove(); });
    }));

    tbody.querySelectorAll('.delete-claim').forEach(btn => btn.addEventListener('click', async (ev) => {
        const id = ev.target.closest('button').dataset.id;
        if (!confirm('Are you sure you want to delete this claim?')) return;
        try {
            const r = await fetch(`${REST_API_URL}hmo/claims/${id}`, { method: 'DELETE', credentials: 'include' });
            const j = await r.json();
            if (j.success) renderHMOClaims(containerId); else alert(j.error || 'Failed to delete');
        } catch (e) { console.error(e); alert('Failed to delete claim'); }
    }));
}

// Export to CSV function
function exportClaimsToCSV() {
    const claims = window._hmoClaimsCache || [];
    if (claims.length === 0) {
        alert('No claims to export');
        return;
    }
    
    const headers = ['Employee', 'Plan', 'Date', 'Hospital/Clinic', 'Amount', 'Status'];
    const rows = claims.map(c => [
        c.FirstName ? (c.FirstName + ' ' + (c.LastName || '')) : '',
        c.PlanName || '',
        c.ClaimDate || '',
        c.HospitalClinic || '',
        c.ClaimAmount || '',
        c.ClaimStatus || ''
    ]);
    
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `hmo-claims-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Modal helpers (lightweight implementations)
export async function showAddClaimModal(containerId = 'main-content-area') {
    const modal = document.getElementById('modalContainer'); if (!modal) return;
    try {
        const pres = await fetch(`${LEGACY_API_URL}hmo_enrollments.php`, { credentials: 'include' });
        const pdata = await pres.json();
        const enrollments = pdata.enrollments || [];

        modal.innerHTML = `
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
                                ${enrollments.map(e => `<option value="${e.EnrollmentID}">${(e.FirstName||'') + ' ' + (e.LastName||'')} - ${e.PlanName||''}</option>`).join('')}
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
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="add-claim-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Submit</button>
                        </div>
                    </form>
                </div>
            </div>`;

        document.getElementById('add-claim-close')?.addEventListener('click', () => { document.getElementById('add-claim-overlay')?.remove(); });
        document.getElementById('add-claim-cancel')?.addEventListener('click', () => { document.getElementById('add-claim-overlay')?.remove(); });

        document.getElementById('addClaimForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            try {
                const res = await fetch(`${LEGACY_API_URL}hmo_claims.php`, { method: 'POST', credentials: 'include', body: fd });
                const j = await res.json(); if (j.success) { document.getElementById('add-claim-overlay')?.remove(); renderHMOClaims(containerId); } else alert(j.error || 'Failed');
            } catch (err) { console.error(err); alert('Failed to submit claim'); }
        });

    } catch (err) { console.error(err); alert('Failed to load enrollments'); }
}

export async function showEditClaimModal(id, containerId = 'main-content-area') {
    const modal = document.getElementById('modalContainer'); if (!modal) return;
    try {
        const r = await fetch(`${LEGACY_API_URL}hmo_claims.php?id=${id}`, { credentials: 'include' });
        const data = await r.json(); const c = data.claim || {};
        const pres = await fetch(`${LEGACY_API_URL}hmo_enrollments.php`, { credentials: 'include' });
        const pdata = await pres.json(); const enrollments = pdata.enrollments || [];

        modal.innerHTML = `
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
                                ${enrollments.map(e => `<option value="${e.EnrollmentID}" ${e.EnrollmentID == c.EnrollmentID ? 'selected' : ''}>${(e.FirstName||'') + ' ' + (e.LastName||'')} - ${e.PlanName||''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="edit-claim-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>`;

        document.getElementById('edit-claim-close')?.addEventListener('click', () => { document.getElementById('edit-claim-overlay')?.remove(); });
        document.getElementById('edit-claim-cancel')?.addEventListener('click', () => { document.getElementById('edit-claim-overlay')?.remove(); });

        document.getElementById('editClaimForm')?.addEventListener('submit', async (e) => {
            e.preventDefault(); const form = e.target; const fd = new FormData(form);
            try {
                const res2 = await fetch(`${LEGACY_API_URL}hmo_claims.php?id=${id}`, { method: 'PUT', credentials: 'include', body: fd });
                const j2 = await res2.json(); if (j2.success) { document.getElementById('edit-claim-overlay')?.remove(); renderHMOClaims(containerId); } else alert(j2.error || 'Failed');
            } catch (err) { console.error(err); alert('Failed to save claim'); }
        });

    } catch (err) { console.error(err); alert('Failed to load claim'); }
}

export function showManageModal(claimIds = [], containerId = 'main-content-area') {
    const modal = document.getElementById('modalContainer'); if (!modal) return;
    const ids = Array.isArray(claimIds) ? claimIds : [claimIds];

    modal.innerHTML = `
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
        </div>`;

    document.getElementById('manage-claims-close')?.addEventListener('click', () => { document.getElementById('manage-claims-overlay')?.remove(); });
    document.getElementById('manage-claims-cancel')?.addEventListener('click', () => { document.getElementById('manage-claims-overlay')?.remove(); });

    document.getElementById('manageClaimsForm')?.addEventListener('submit', async (e) => {
        e.preventDefault(); const fd = new FormData(e.target); const action = fd.get('action'); const notes = fd.get('notes');
        for (const id of ids) {
            try {
                const res = await fetch(`${LEGACY_API_URL}hmo_claims.php?id=${id}`, { method: 'PUT', credentials: 'include', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ claim_status: action === 'Approve' ? 'Approved' : 'Denied', remarks: notes }) });
                const j = await res.json(); if (!j.success) alert('Failed to update ' + id);
            } catch (err) { console.error(err); alert('Failed to update ' + id); }
        }
        document.getElementById('manage-claims-overlay')?.remove(); renderHMOClaims(containerId);
    });
}

export function showClaimHistoryModal(employeeId, containerId = 'main-content-area') {
    const modal = document.getElementById('modalContainer'); if (!modal) return;
    modal.innerHTML = `
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
        </div>`;

    document.getElementById('claim-history-close')?.addEventListener('click', () => { document.getElementById('claim-history-overlay')?.remove(); });
    document.getElementById('claim-history-close-btn')?.addEventListener('click', () => { document.getElementById('claim-history-overlay')?.remove(); });

    async function loadHistory() {
        const status = document.getElementById('history-status').value; const from = document.getElementById('history-from').value; const to = document.getElementById('history-to').value;
        const q = new URLSearchParams({ mode: 'history', employee_id: employeeId, status, from, to });
        try {
            const r = await fetch(`${LEGACY_API_URL}hmo_claims.php?` + q.toString(), { credentials: 'include' });
            const j = await r.json(); const rows = j.claims || [];
            document.getElementById('history-results').innerHTML = `<table class="w-full text-left"><thead><tr><th>Plan</th><th>Date</th><th>Hospital</th><th>Amount</th><th>Status</th><th>Remarks</th></tr></thead><tbody>` + rows.map(rr => `<tr><td>${rr.PlanName||''}</td><td>${rr.ClaimDate||''}</td><td>${rr.HospitalClinic||''}</td><td>${rr.ClaimAmount||''}</td><td>${rr.ClaimStatus||''}</td><td>${rr.Remarks||''}</td></tr>`).join('') + `</tbody></table>`;
        } catch (err) { console.error(err); document.getElementById('history-results').innerText = 'Failed to load history'; }
    }

    document.getElementById('history-refresh')?.addEventListener('click', loadHistory);
    loadHistory();
}

// Provide a generic initializer the dynamic loader can call
export async function initialize(containerId = 'main-content-area') {
    return renderHMOClaims(containerId);
}

