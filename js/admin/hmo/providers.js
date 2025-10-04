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
                        <button id="refresh-providers" class="hmo-btn hmo-btn-primary">Refresh</button>
                        <button id="add-provider-btn" class="hmo-btn hmo-btn-success">Add Provider</button>
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
    tbody.innerHTML = `<tr><td class="p-6 text-center text-sm text-gray-500" colspan="5">No HMO providers found. You can add one using the "Add Provider" button.<div class="mt-2"><button id="empty-add-provider" class="hmo-btn hmo-btn-success">Add Provider</button></div><div class="mt-2 text-xs text-gray-400">Tip: you can seed sample data. On Windows/XAMPP run:<div class="mt-2 font-mono text-xs bg-black text-white inline-block p-2">"C:/xampp/mysql/bin/mysql.exe" -u root -p hr_integrated_db &lt; database/hmo_top7_seed.sql</div> or import <code>database/hmo_top7_seed.sql</code> via phpMyAdmin.</div></td></tr>`;
    } else {
        tbody.innerHTML = providers.map(p=>`<tr>
            <td class="p-3">${p.ProviderName}</td>
            <td class="p-3">${p.ContactPerson || ''} ${p.ContactNumber?('<br/>'+p.ContactNumber):''}</td>
            <td class="p-3">${p.Email||''}</td>
            <td class="p-3">${p.Status||''}</td>
            <td class="p-3">
                <button class="hmo-btn hmo-btn-secondary edit-provider" data-id="${p.ProviderID}">Edit</button>
                <button class="hmo-btn hmo-btn-danger delete-provider" data-id="${p.ProviderID}">Delete</button>
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
    // Tailwind-style overlay modal (avoids Bootstrap/jQuery and layout shift)
    container.innerHTML = `
        <div id="add-provider-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Add Provider</h5>
                    <button id="add-provider-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="addProviderForm" class="p-4 space-y-3">
                    <div class="space-y-1"><label class="block text-sm">Name</label><input name="provider_name" class="w-full p-2 border rounded" required/></div>
                    <div class="space-y-1"><label class="block text-sm">Description</label><textarea name="description" class="w-full p-2 border rounded"></textarea></div>
                    <div class="space-y-1"><label class="block text-sm">Contact Person</label><input name="contact_person" class="w-full p-2 border rounded"/></div>
                    <div class="space-y-1"><label class="block text-sm">Contact Number</label><input name="contact_number" class="w-full p-2 border rounded"/></div>
                    <div class="space-y-1"><label class="block text-sm">Email</label><input name="email" type="email" class="w-full p-2 border rounded"/></div>
                    <div class="space-y-1"><label class="block text-sm">Status</label>
                        <select name="status" class="w-full p-2 border rounded"><option value="Active" selected>Active</option><option value="Inactive">Inactive</option></select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="add-provider-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // close handlers
    document.getElementById('add-provider-close')?.addEventListener('click', ()=>{ document.getElementById('add-provider-overlay')?.remove(); });
    document.getElementById('add-provider-cancel')?.addEventListener('click', ()=>{ document.getElementById('add-provider-overlay')?.remove(); });

    document.getElementById('addProviderForm')?.addEventListener('submit', async e=>{
        e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
        try{
            const res = await fetch(`${API_BASE_URL}hmo_providers.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const j = await res.json(); if (j.success) { document.getElementById('add-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
        }catch(err){console.error(err); alert('Failed to add provider');}
    });
}

export async function showEditProviderModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_providers.php?id=${id}`, { credentials:'include' });
        const data = await r.json(); const p = data.provider || {};
        // Tailwind-style overlay modal for editing
        container.innerHTML = `
            <div id="edit-provider-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-xl mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Edit Provider</h5>
                        <button id="edit-provider-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <form id="editProviderForm" class="p-4 space-y-3">
                        <input type="hidden" name="id" value="${id}" />
                        <div class="space-y-1"><label class="block text-sm">Name</label><input name="provider_name" class="w-full p-2 border rounded" value="${p.ProviderName || ''}" required/></div>
                        <div class="space-y-1"><label class="block text-sm">Description</label><textarea name="description" class="w-full p-2 border rounded">${p.Description || ''}</textarea></div>
                        <div class="space-y-1"><label class="block text-sm">Contact Person</label><input name="contact_person" class="w-full p-2 border rounded" value="${p.ContactPerson || ''}"/></div>
                        <div class="space-y-1"><label class="block text-sm">Contact Number</label><input name="contact_number" class="w-full p-2 border rounded" value="${p.ContactNumber || ''}"/></div>
                        <div class="space-y-1"><label class="block text-sm">Email</label><input name="email" type="email" class="w-full p-2 border rounded" value="${p.Email || ''}"/></div>
                        <div class="space-y-1"><label class="block text-sm">Status</label>
                            <select name="status" class="w-full p-2 border rounded"><option value="Active" ${p.Status==='Active'?'selected':''}>Active</option><option value="Inactive" ${p.Status==='Inactive'?'selected':''}>Inactive</option></select>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="edit-provider-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('edit-provider-close')?.addEventListener('click', ()=>{ document.getElementById('edit-provider-overlay')?.remove(); });
        document.getElementById('edit-provider-cancel')?.addEventListener('click', ()=>{ document.getElementById('edit-provider-overlay')?.remove(); });

        document.getElementById('editProviderForm')?.addEventListener('submit', async e=>{
            e.preventDefault(); const fd = new FormData(e.target); const payload = {}; fd.forEach((v,k)=>payload[k]=v);
            try{
                const res = await fetch(`${API_BASE_URL}hmo_providers.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                const j = await res.json(); if (j.success) { document.getElementById('edit-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
            }catch(err){console.error(err); alert('Error updating provider');}
        });
    }catch(e){console.error(e); alert('Failed to load provider');}
}
