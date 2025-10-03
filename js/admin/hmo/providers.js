import { API_BASE_URL } from '../../utils.js';

export async function renderHMOProviders(containerId='main-content-area'){
    const container = document.getElementById(containerId);
    if (!container) return;
    try{
        const res = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials:'include' });
        const data = await res.json();
        const providers = data.providers || [];
        // store cache for filtering
        window._hmoProvidersCache = providers;
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">HMO Providers</h2>
                    <div>
                        <button id="refresh-providers" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="add-provider-btn" class="btn btn-sm btn-success">Add Provider</button>
                    </div>
                </div>
                <div class="flex items-center gap-4 mb-3">
                    <input id="hmo-provider-search" placeholder="Search providers..." class="form-control" style="max-width:320px;" />
                    <select id="hmo-provider-status-filter" class="form-control" style="max-width:180px;"><option value="">All statuses</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-providers-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Name</th>
                                <th class="p-3">Contact</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hmo-providers-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        // initial populate
        populateProvidersTbody(window._hmoProvidersCache, containerId);

        // wire controls
        document.getElementById('refresh-providers')?.addEventListener('click', ()=>renderHMOProviders(containerId));
        document.getElementById('add-provider-btn')?.addEventListener('click', ()=>showAddProviderModal(containerId));
        document.getElementById('empty-add-provider')?.addEventListener('click', ()=>showAddProviderModal(containerId));
        document.getElementById('hmo-provider-search')?.addEventListener('input', ()=>applyProviderFilters(containerId));
        document.getElementById('hmo-provider-status-filter')?.addEventListener('change', ()=>applyProviderFilters(containerId));
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading providers</div>'}
}

function applyProviderFilters(containerId='main-content-area'){
    const q = (document.getElementById('hmo-provider-search')?.value || '').toLowerCase().trim();
    const status = (document.getElementById('hmo-provider-status-filter')?.value || '');
    const all = window._hmoProvidersCache || [];
    const filtered = all.filter(p=>{
        if (status && ((p.Status||'') !== status)) return false;
        if (!q) return true;
        return (p.ProviderName||'').toLowerCase().includes(q) || (p.ContactPerson||'').toLowerCase().includes(q) || (p.Email||'').toLowerCase().includes(q);
    });
    populateProvidersTbody(filtered, containerId);
}

function populateProvidersTbody(providers, containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    const tbody = document.getElementById('hmo-providers-tbody'); if (!tbody) return;
    if (!providers || providers.length === 0){
    tbody.innerHTML = `<tr><td class="p-6 text-center text-sm text-gray-500" colspan="5">No HMO providers found. You can add one using the "Add Provider" button.<div class="mt-2"><button id="empty-add-provider" class="btn btn-sm btn-success">Add Provider</button></div><div class="mt-2 text-xs text-gray-400">Tip: you can seed sample data. On Windows/XAMPP run:<div class="mt-2 font-mono text-xs bg-black text-white inline-block p-2">"C:/xampp/mysql/bin/mysql.exe" -u root -p hr_integrated_db &lt; database/hmo_top7_seed.sql</div> or import <code>database/hmo_top7_seed.sql</code> via phpMyAdmin.</div></td></tr>`;
    } else {
        tbody.innerHTML = providers.map(p=>`<tr>
            <td class="p-3">${p.ProviderName}</td>
            <td class="p-3">${p.ContactPerson || ''} ${p.ContactNumber?('<br/>'+p.ContactNumber):''}</td>
            <td class="p-3">${p.Email||''}</td>
            <td class="p-3">${p.Status||''}</td>
            <td class="p-3">
                <button class="btn btn-sm btn-secondary edit-provider" data-id="${p.ProviderID}">Edit</button>
                <button class="btn btn-sm btn-danger delete-provider" data-id="${p.ProviderID}">Delete</button>
            </td>
        </tr>`).join('');
    }
    // wire row buttons
    tbody.querySelectorAll('.edit-provider').forEach(b=>b.addEventListener('click', ev=>{ const id = ev.target.dataset.id; if (!id) return; showEditProviderModal(id, containerId); }));
    tbody.querySelectorAll('.delete-provider').forEach(async b=>{
        b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id; if (!confirm('Delete provider?')) return;
            try{
                const r = await fetch(`${API_BASE_URL}hmo_providers.php?id=${id}`, { method:'DELETE', credentials:'include' });
                const j = await r.json(); if (j.success) renderHMOProviders(containerId); else alert(j.error||'Failed');
            }catch(err){console.error(err); alert('Delete failed');}
        });
    });
    // empty-add-provider button wiring (if present)
    document.getElementById('empty-add-provider')?.addEventListener('click', ()=>showAddProviderModal(containerId));
}

export function showAddProviderModal(containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div class="modal fade" id="addProviderModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Provider</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="addProviderForm">
                                    <div class="modal-body">
                                    <div class="mb-3"><label>Name</label><input name="provider_name" class="form-control" required/></div>
                                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                                    <div class="mb-3"><label>Contact Person</label><input name="contact_person" class="form-control"/></div>
                                    <div class="mb-3"><label>Contact Number</label><input name="contact_number" class="form-control"/></div>
                                    <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control"/></div>
                                    <div class="mb-3"><label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="Active" selected>Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                </form>
            </div></div>
        </div>
    `;
    $('#addProviderModal').modal('show');
    document.getElementById('addProviderForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
        const res = await fetch(`${API_BASE_URL}hmo_providers.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const j = await res.json(); if (j.success) { $('#addProviderModal').modal('hide'); renderHMOProviders(containerId); } else alert(j.error||'Failed');
    });
}

export async function showEditProviderModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_providers.php?id=${id}`, { credentials:'include' });
        const data = await r.json(); const p = data.provider || {};
        container.innerHTML = `
            <div class="modal fade" id="editProviderModal" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Edit Provider</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form id="editProviderForm">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="${id}" />
                            <div class="mb-3"><label>Name</label><input name="provider_name" class="form-control" value="${p.ProviderName || ''}" required/></div>
                            <div class="mb-3"><label>Description</label><textarea name="description" class="form-control">${p.Description || ''}</textarea></div>
                            <div class="mb-3"><label>Contact Person</label><input name="contact_person" class="form-control" value="${p.ContactPerson || ''}"/></div>
                            <div class="mb-3"><label>Contact Number</label><input name="contact_number" class="form-control" value="${p.ContactNumber || ''}"/></div>
                            <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control" value="${p.Email || ''}"/></div>
                            <div class="mb-3"><label>Status</label>
                                <select name="status" class="form-control"><option value="Active" ${p.Status==='Active'?'selected':''}>Active</option><option value="Inactive" ${p.Status==='Inactive'?'selected':''}>Inactive</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                    </form>
                </div></div>
            </div>
        `;
        $('#editProviderModal').modal('show');
        document.getElementById('editProviderForm')?.addEventListener('submit', async e=>{
            e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
            try{
                const res = await fetch(`${API_BASE_URL}hmo_providers.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                const j = await res.json(); if (j.success) { $('#editProviderModal').modal('hide'); renderHMOProviders(containerId); } else alert(j.error||'Failed');
            }catch(err){console.error(err); alert('Error updating provider');}
        });
    }catch(e){console.error(e); alert('Failed to load provider');}
}
