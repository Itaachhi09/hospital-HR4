import { REST_API_URL } from '../../utils.js';

export async function renderHMOProviders(containerId='main-content-area'){
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="text-gray-500 mt-4">Loading HMO providers...</p>
            </div>
        </div>
    `;
    
    try{
        const res = await fetch(`${REST_API_URL}hmo/providers`, { credentials:'include' });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const response = await res.json();
        const providers = response.data?.providers || response.providers || [];
        
        // store cache for filtering
        window._hmoProvidersCache = providers;
        
        // Calculate active providers count
        const activeCount = providers.filter(p => p.IsActive === 1).length;
        
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Enhanced Header -->
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-4 rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold mb-1">HMO Providers</h2>
                            <p class="text-sm text-purple-100">Manage health insurance provider partnerships</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-providers" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="export-providers" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button id="add-provider-btn" class="px-4 py-2 bg-white text-purple-600 hover:bg-purple-50 font-semibold rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Provider</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-hospital text-purple-600"></i>
                            <span class="text-sm text-gray-600">Total Providers:</span>
                            <span class="font-semibold text-gray-900">${providers.length}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-600">Active:</span>
                            <span class="font-semibold text-green-600">${activeCount}</span>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[300px] relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input id="hmo-provider-search" type="text" placeholder="Search providers, contact, or email..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </div>
                        <select id="hmo-provider-status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="hmo-providers-table">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Provider Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Person</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hmo-providers-tbody" class="bg-white divide-y divide-gray-200"></tbody>
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
        document.getElementById('export-providers')?.addEventListener('click', exportProvidersToCSV);
        document.getElementById('hmo-provider-search')?.addEventListener('input', ()=>applyProviderFilters(containerId));
        document.getElementById('hmo-provider-status-filter')?.addEventListener('change', ()=>applyProviderFilters(containerId));
    }catch(e){
        console.error(e); 
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <div class="flex items-center space-x-3 text-red-600">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Error Loading Providers</h3>
                        <p class="text-sm text-red-500 mt-1">${e.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

function applyProviderFilters(containerId='main-content-area'){
    const q = (document.getElementById('hmo-provider-search')?.value || '').toLowerCase().trim();
    const status = (document.getElementById('hmo-provider-status-filter')?.value || '');
    const all = window._hmoProvidersCache || [];
    const filtered = all.filter(p=>{
        if (status !== '') {
            const isActive = parseInt(status) === 1;
            if ((p.IsActive === 1) !== isActive) return false;
        }
        if (!q) return true;
        return (p.ProviderName||'').toLowerCase().includes(q) || 
               (p.ContactPerson||'').toLowerCase().includes(q) || 
               (p.ContactEmail||'').toLowerCase().includes(q);
    });
    populateProvidersTbody(filtered, containerId);
}

function populateProvidersTbody(providers, containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    const tbody = document.getElementById('hmo-providers-tbody'); if (!tbody) return;
    
    if (!providers || providers.length === 0){
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <i class="fas fa-hospital text-gray-300 text-5xl"></i>
                        <p class="text-gray-500 text-lg font-medium">No HMO providers found</p>
                        <p class="text-gray-400 text-sm">Add a new provider to get started</p>
                        <button id="empty-add-provider" class="mt-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-plus-circle mr-2"></i>Add Your First Provider
                        </button>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('empty-add-provider')?.addEventListener('click', ()=>showAddProviderModal(containerId));
        return;
    }
    
    tbody.innerHTML = providers.map(p=>{
        const isActive = p.IsActive === 1;
        const statusBadge = isActive 
            ? '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Active</span>'
            : '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Inactive</span>';
        
        return `
            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${p.ProviderName || 'N/A'}</div>
                    ${p.CompanyName ? `<div class="text-xs text-gray-500">${p.CompanyName}</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${p.ContactPerson || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${p.ContactEmail || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${p.ContactPhone || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                        <button class="text-blue-600 hover:text-blue-800 view-provider" data-id="${p.ProviderID}" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-purple-600 hover:text-purple-800 edit-provider" data-id="${p.ProviderID}" title="Edit Provider">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-800 delete-provider" data-id="${p.ProviderID}" title="Delete Provider">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    // wire row buttons
    tbody.querySelectorAll('.edit-provider').forEach(b=>b.addEventListener('click', ev=>{ 
        const id = ev.target.closest('button').dataset.id; 
        if (!id) return; 
        showEditProviderModal(id, containerId); 
    }));
    
    tbody.querySelectorAll('.delete-provider').forEach(b=>{
        b.addEventListener('click', async ev=>{
            const id = ev.target.closest('button').dataset.id;
            const provider = window._hmoProvidersCache.find(p => p.ProviderID === Number(id));
            if (!provider) {
                alert('Provider not found');
                return;
            }
            if (!confirm(`Are you sure you want to delete the provider "${provider.ProviderName}"? This action cannot be undone.`)) {
                return;
            }
            try {
                const r = await fetch(`${REST_API_URL}hmo/providers?id=${id}`, { 
                    method: 'DELETE', 
                    credentials: 'include' 
                });
                const j = await r.json();
                if (j.success) {
                    await renderHMOProviders(containerId);
                } else {
                    alert(j.error || 'Failed to delete provider.');
                }
            } catch(err) {
                console.error('Error deleting provider:', err);
                alert('Failed to delete provider. Please check your connection and try again.');
            }
        });
    });
}

// Export to CSV function
function exportProvidersToCSV() {
    const providers = window._hmoProvidersCache || [];
    if (providers.length === 0) {
        alert('No providers to export');
        return;
    }
    
    const headers = ['Provider Name', 'Company Name', 'Contact Person', 'Email', 'Phone', 'Status'];
    const rows = providers.map(p => [
        p.ProviderName || '',
        p.CompanyName || '',
        p.ContactPerson || '',
        p.ContactEmail || '',
        p.ContactPhone || '',
        p.IsActive === 1 ? 'Active' : 'Inactive'
    ]);
    
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `hmo-providers-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
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
            const res = await fetch(`${REST_API_URL}hmo/providers`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const j = await res.json(); if (j.success) { document.getElementById('add-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
        }catch(err){console.error(err); alert('Failed to add provider');}
    });
}

export async function showEditProviderModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${REST_API_URL}hmo/providers?id=${id}`, { credentials:'include' });
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
                const res = await fetch(`${REST_API_URL}hmo/providers?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                const j = await res.json(); if (j.success) { document.getElementById('edit-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
            }catch(err){console.error(err); alert('Error updating provider');}
        });
    }catch(e){console.error(e); alert('Failed to load provider');}
}
