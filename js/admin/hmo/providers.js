import { API_BASE_URL } from '../../utils.js';

export async function renderHMOProviders(containerId='main-content-area'){
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600"></div>
                <p class="text-gray-500 mt-4">Loading HMO providers...</p>
            </div>
        </div>
    `;
    
    try{
        const res = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers`, { credentials:'include' });
        if (!res.ok) {
            const text = await res.text();
            console.error('API Error Response:', text);
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const data = await res.json();
        const providers = data.data?.providers || data.providers || [];
        // store cache for filtering
        window._hmoProvidersCache = providers;
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header Section -->
                <div class="px-6 py-4 border-b border-gray-200 bg-cyan-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                            <h3 class="text-xl font-semibold text-cyan-900">HMO Providers</h3>
                            <p class="text-sm text-cyan-700 mt-1">Manage health maintenance organization providers and their information</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-providers" class="inline-flex items-center px-4 py-2 border border-cyan-300 rounded-md text-sm font-medium text-cyan-700 bg-white hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                                <i class="fas fa-sync mr-2"></i>Refresh
                            </button>
                            <button id="export-providers" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-md text-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                <i class="fas fa-file-excel mr-2"></i>Export
                            </button>
                            <button id="add-provider-btn" class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white rounded-md text-sm hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                                <i class="fas fa-plus mr-2"></i>Add Provider
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input id="hmo-provider-search" type="text" placeholder="Search providers by name, contact person, email..." 
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3">
                            <select id="hmo-provider-status-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            
                            <button onclick="clearProviderFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-times mr-1"></i>Clear
                            </button>
                        </div>
                    </div>
                    
                    <!-- Summary Stats -->
                    <div class="mt-4 flex items-center space-x-6 text-sm">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-hospital text-cyan-600"></i>
                            <span class="text-gray-600">Total Providers:</span>
                            <span class="font-semibold text-gray-900" id="total-providers-count">${providers.length}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-gray-600">Active:</span>
                            <span class="font-semibold text-gray-900" id="active-providers-count">${providers.filter(p => p.IsActive == 1).length}</span>
                        </div>
                    </div>
                </div>

                <!-- Providers Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="hmo-providers-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Provider
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact Information
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="hmo-providers-tbody" class="bg-white divide-y divide-gray-200">
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
        document.getElementById('export-providers')?.addEventListener('click', ()=>exportProvidersData());
        document.getElementById('empty-add-provider')?.addEventListener('click', ()=>showAddProviderModal(containerId));
        document.getElementById('hmo-provider-search')?.addEventListener('input', ()=>applyProviderFilters(containerId));
        document.getElementById('hmo-provider-status-filter')?.addEventListener('change', ()=>applyProviderFilters(containerId));
        
        // Make clear filters function global
        window.clearProviderFilters = () => {
            document.getElementById('hmo-provider-search').value = '';
            document.getElementById('hmo-provider-status-filter').value = '';
            applyProviderFilters(containerId);
        };
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
        // Handle IsActive (0/1) instead of Status ('Active'/'Inactive')
        if (status !== '' && String(p.IsActive) !== status) return false;
        if (!q) return true;
        return (p.ProviderName||'').toLowerCase().includes(q) || 
               (p.ContactPerson||'').toLowerCase().includes(q) || 
               (p.Email||'').toLowerCase().includes(q) ||
               (p.Description||'').toLowerCase().includes(q);
    });
    populateProvidersTbody(filtered, containerId);
    
    // Update counts
    document.getElementById('total-providers-count').textContent = filtered.length;
    document.getElementById('active-providers-count').textContent = filtered.filter(p => p.IsActive == 1).length;
}

function populateProvidersTbody(providers, containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    const tbody = document.getElementById('hmo-providers-tbody'); if (!tbody) return;
    if (!providers || providers.length === 0){
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="rounded-full bg-gray-100 p-4">
                            <i class="fas fa-hospital text-4xl text-gray-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">No providers found</h3>
                            <p class="text-sm text-gray-500 mt-1">Get started by adding your first HMO provider</p>
                        </div>
                        <button id="empty-add-provider" class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white rounded-md text-sm hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            <i class="fas fa-plus mr-2"></i>Add Provider
                        </button>
                        <div class="text-xs text-gray-400 mt-4">
                            <p>Tip: Import sample data from <code class="bg-gray-100 px-2 py-1 rounded">database/hmo_top7_seed.sql</code></p>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = providers.map(p => {
            const statusBadge = p.IsActive == 1 
                ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Active</span>'
                : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><i class="fas fa-times-circle mr-1"></i>Inactive</span>';
            
            return `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-cyan-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-hospital text-cyan-600"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${p.ProviderName || 'N/A'}</div>
                                ${p.Description ? `<div class="text-sm text-gray-500">${p.Description.substring(0, 50)}${p.Description.length > 50 ? '...' : ''}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${p.ContactPerson || '-'}</div>
                        ${p.ContactEmail || p.ContactPhone ? `<div class="text-sm text-gray-500">${p.ContactPhone || p.ContactEmail || ''}</div>` : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${p.Email || '-'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="text-cyan-600 hover:text-cyan-900 mr-3 edit-provider" data-id="${p.ProviderID}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-900 delete-provider" data-id="${p.ProviderID}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
            </td>
                </tr>
            `;
        }).join('');
    }
    // wire row buttons
    tbody.querySelectorAll('.edit-provider').forEach(b=>b.addEventListener('click', ev=>{ const id = ev.target.dataset.id; if (!id) return; showEditProviderModal(id, containerId); }));
    tbody.querySelectorAll('.delete-provider').forEach(async b=>{
        b.addEventListener('click', async ev=>{
            const id = ev.target.dataset.id;
            console.log('Deleting provider with ID:', id);
            // Convert ID to number since it comes from dataset as string
            const provider = window._hmoProvidersCache.find(p => p.ProviderID === Number(id));
            if (!provider) {
                alert('Provider not found');
                return;
            }
            if (!confirm(`Are you sure you want to delete the provider "${provider.ProviderName}"? This action cannot be undone.`)) {
                return;
            }
            try {
                const r = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers?id=${id}`, { 
                    method: 'DELETE', 
                    credentials: 'include' 
                });
                const j = await r.json();
                if (j.success) {
                    // Force a complete refresh
                    try {
                        const res = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers`, { 
                            credentials: 'include',
                            cache: 'no-cache' // Prevent browser caching
                        });
                        const data = await res.json();
                        window._hmoProvidersCache = data.providers || [];
                        await renderHMOProviders(containerId);
                        console.log('Provider deleted and table refreshed');
                    } catch(err) {
                        console.error('Error refreshing providers:', err);
                        alert('Provider was deleted but the table refresh failed. Please refresh the page manually.');
                    }
                } else {
                    alert(j.error || 'Failed to delete provider. The provider may be in use by other records.');
                }
            } catch(err) {
                console.error('Error deleting provider:', err);
                alert('Failed to delete provider. Please check your connection and try again.');
            }
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
            const res = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const j = await res.json(); if (j.success) { document.getElementById('add-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
        }catch(err){console.error(err); alert('Failed to add provider');}
    });
}

export async function showEditProviderModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers?id=${id}`, { credentials:'include' });
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
                const res = await fetch(`${API_BASE_URL}hmo_unified.php/hmo_providers?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                const j = await res.json(); if (j.success) { document.getElementById('edit-provider-overlay')?.remove(); renderHMOProviders(containerId); } else alert(j.error||'Failed');
            }catch(err){console.error(err); alert('Error updating provider');}
        });
    }catch(e){console.error(e); alert('Failed to load provider');}
}

/**
 * Export providers data to CSV
 */
function exportProvidersData() {
    const providers = window._hmoProvidersCache || [];
    if (providers.length === 0) {
        alert('No provider data to export');
        return;
    }
    
    // CSV headers
    const headers = ['Provider Name', 'Description', 'Contact Person', 'Contact Email', 'Contact Phone', 'Email', 'Status', 'Created At'];
    
    // CSV rows
    const rows = providers.map(p => [
        p.ProviderName || '',
        (p.Description || '').replace(/"/g, '""'), // Escape quotes
        p.ContactPerson || '',
        p.ContactEmail || '',
        p.ContactPhone || '',
        p.Email || '',
        p.IsActive == 1 ? 'Active' : 'Inactive',
        p.CreatedAt || ''
    ]);
    
    // Create CSV content
    let csvContent = headers.join(',') + '\n';
    rows.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `hmo_providers_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
