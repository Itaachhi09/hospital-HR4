import { API_BASE_URL } from '../../utils.js';

export async function renderHMOPlans(containerId='main-content-area'){
    const container = document.getElementById(containerId); 
    if (!container) return;

    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="text-gray-500 mt-4">Loading HMO plans...</p>
            </div>
        </div>
    `;

    try{
        const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials:'include' });
        if (!res.ok) {
            const text = await res.text();
            console.error('API Error Response:', text);
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const data = await res.json(); 
        const plans = data.data?.plans || data.plans || [];
        
        // load providers for filter and selects
        const pres = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials:'include' }); 
        const pdata = await pres.json(); 
        const providers = pdata.data?.providers || pdata.providers || [];
        
        window._hmoPlansCache = plans;
        window._hmoProvidersForPlans = providers;
        // Calculate active plans count
        const activeCount = plans.filter(p => p.IsActive === 1 || p.Status === 'Active').length;
        
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Enhanced Header -->
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-4 rounded-t-lg">
                    <div class="flex justify-between items-center">
                    <div>
                            <h2 class="text-2xl font-bold mb-1">HMO Benefit Plans</h2>
                            <p class="text-sm text-purple-100">Manage health insurance plans and coverage options</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-plans" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                            <button id="export-plans" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                            <button id="add-plan-btn" class="px-4 py-2 bg-white text-purple-600 hover:bg-purple-50 font-semibold rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Plan</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-file-medical text-purple-600"></i>
                            <span class="text-sm text-gray-600">Total Plans:</span>
                            <span class="font-semibold text-gray-900">${plans.length}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-600">Active:</span>
                            <span class="font-semibold text-green-600">${activeCount}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-hospital text-purple-600"></i>
                            <span class="text-sm text-gray-600">Providers:</span>
                            <span class="font-semibold text-gray-900">${providers.length}</span>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[300px] relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input id="hmo-plan-search" type="text" placeholder="Search plans, coverage, or providers..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </div>
                        <select id="hmo-plan-provider-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Providers</option>
                            ${providers.map(p=>`<option value="${p.ProviderID}">${p.ProviderName}</option>`).join('')}
                        </select>
                        <select id="hmo-plan-status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="hmo-plans-table">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200">
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Coverage</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Max Benefit</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Premium</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hmo-plans-tbody" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        `;
        populatePlansTbody(window._hmoPlansCache || []);
        document.getElementById('refresh-plans')?.addEventListener('click', ()=>renderHMOPlans(containerId));
        document.getElementById('add-plan-btn')?.addEventListener('click', ()=>showAddPlanModal(containerId));
        document.getElementById('empty-add-plan')?.addEventListener('click', ()=>showAddPlanModal(containerId));
        document.getElementById('export-plans')?.addEventListener('click', exportPlansToCSV);
        document.getElementById('hmo-plan-search')?.addEventListener('input', ()=>applyPlanFilters());
        document.getElementById('hmo-plan-provider-filter')?.addEventListener('change', ()=>applyPlanFilters());
        document.getElementById('hmo-plan-status-filter')?.addEventListener('change', ()=>applyPlanFilters());
    }catch(e){
        console.error(e); 
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <div class="flex items-center space-x-3 text-red-600">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Error Loading Plans</h3>
                        <p class="text-sm text-red-500 mt-1">${e.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

function applyPlanFilters(){
    const q = (document.getElementById('hmo-plan-search')?.value||'').toLowerCase().trim();
    const provider = (document.getElementById('hmo-plan-provider-filter')?.value||'');
    const status = (document.getElementById('hmo-plan-status-filter')?.value||'');
    const all = window._hmoPlansCache || [];
    const filtered = all.filter(p=>{
        if (provider && String(p.ProviderID) !== String(provider)) return false;
        if (status !== '') {
            const isActive = parseInt(status) === 1;
            if ((p.IsActive === 1 || p.IsActive === '1' || p.Status === 'Active') !== isActive) return false;
        }
        if (!q) return true;
        const cov = p.Coverage ? (Array.isArray(p.Coverage)?p.Coverage.join(' '):JSON.parse(p.Coverage||'[]').join(' ')) : '';
        return (p.PlanName||'').toLowerCase().includes(q) || (p.ProviderName||'').toLowerCase().includes(q) || cov.toLowerCase().includes(q);
    });
    populatePlansTbody(filtered);
}

function populatePlansTbody(plans){
    const tbody = document.getElementById('hmo-plans-tbody'); if (!tbody) return;
    if (!plans || plans.length === 0){
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <i class="fas fa-file-medical text-gray-300 text-5xl"></i>
                        <p class="text-gray-500 text-lg font-medium">No benefit plans found</p>
                        <p class="text-gray-400 text-sm">Add a new plan to get started</p>
                        <button id="empty-add-plan" class="mt-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-plus-circle mr-2"></i>Add Your First Plan
                        </button>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('empty-add-plan')?.addEventListener('click', ()=>showAddPlanModal());
        return;
    }
    tbody.innerHTML = plans.map(p=>{
        const isActive = p.IsActive === 1 || p.IsActive === '1' || p.Status === 'Active';
        const statusBadge = isActive 
            ? '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Active</span>'
            : '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Inactive</span>';
        
        const coverage = p.Coverage ? (Array.isArray(p.Coverage) ? p.Coverage : JSON.parse(p.Coverage||'[]')) : [];
        const coverageText = coverage.length > 0 ? coverage.slice(0, 3).join(', ') + (coverage.length > 3 ? '...' : '') : 'N/A';
        
        const maxBenefit = p.MaximumBenefitLimit ? `₱${parseFloat(p.MaximumBenefitLimit).toLocaleString()}` : 'N/A';
        const premium = p.PremiumCost || p.MonthlyPremium ? `₱${parseFloat(p.PremiumCost || p.MonthlyPremium).toLocaleString()}` : 'N/A';
        
        return `
            <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${p.PlanName || 'N/A'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">${p.ProviderName || 'N/A'}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-700" title="${coverage.join(', ')}">${coverageText}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-medium">${maxBenefit}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${premium}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                        <button class="text-blue-600 hover:text-blue-800 view-plan" data-id="${p.PlanID}" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-purple-600 hover:text-purple-800 edit-plan" data-id="${p.PlanID}" title="Edit Plan">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-800 delete-plan" data-id="${p.PlanID}" title="Delete Plan">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
        </td>
            </tr>
        `;
    }).join('');
    tbody.querySelectorAll('.view-plan').forEach(b=>b.addEventListener('click', ev=>{ const id = ev.target.dataset.id; if (!id) return; showPlanDetailsModal(id); }));
    tbody.querySelectorAll('.edit-plan').forEach(b=>b.addEventListener('click', ev=>{ const id = ev.target.dataset.id; if (!id) return; showEditPlanModal(id); }));
    tbody.querySelectorAll('.delete-plan').forEach(b=>b.addEventListener('click', async ev=>{ 
        const id = ev.target.dataset.id;
        const plan = window._hmoPlansCache.find(p => Number(p.PlanID) === Number(id));
        if (!plan) {
            alert('Plan not found');
            return;
        }
        if (!confirm(`Are you sure you want to delete the plan "${plan.PlanName}"? This action cannot be undone.`)) {
            return;
        }
        try {
            const r = await fetch(`${API_BASE_URL}hmo_plans.php?id=${id}`, { 
                method: 'DELETE', 
                credentials: 'include' 
            }); 
            const j = await r.json(); 
            if (j.success) {
                // Force a complete refresh
                try {
                    const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { 
                        credentials: 'include',
                        cache: 'no-cache' // Prevent browser caching
                    });
                    const data = await res.json();
                    window._hmoPlansCache = data.plans || [];
                    await renderHMOPlans(containerId);
                    console.log('Plan deleted and table refreshed');
                } catch(err) {
                    console.error('Error refreshing plans:', err);
                    alert('Plan was deleted but the table refresh failed. Please refresh the page manually.');
                }
            } else {
                alert(j.error || 'Failed to delete plan. It may have enrollments or claims.');
            }
        } catch (error) {
            console.error('Error deleting plan:', error);
            alert('Failed to delete plan. Please check your connection and try again.');
        }
    }));
}

export async function showAddPlanModal(containerId='main-content-area'){
    // load providers for select
    const pres = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials:'include' }); const pdata = await pres.json(); const providers = pdata.providers||[];
    const container = document.getElementById('modalContainer'); if (!container) return;
    container.innerHTML = `
        <div id="add-plan-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h5 class="text-lg font-semibold">Add Benefit Plan</h5>
                    <button id="add-plan-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <form id="addPlanForm" class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm">Provider</label>
                        <select name="provider_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Provider</option>
                            ${providers.map(p=>`<option value="${p.ProviderID}">${p.ProviderName}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Plan Name</label>
                        <input name="plan_name" class="w-full p-2 border rounded" required/>
                    </div>
                    <div>
                        <label class="block text-sm">Coverage</label>
                        <div class="flex flex-wrap gap-3 text-sm">
                            <label><input type="checkbox" name="coverage_option" value="inpatient"/> Inpatient</label>
                            <label><input type="checkbox" name="coverage_option" value="outpatient"/> Outpatient</label>
                            <label><input type="checkbox" name="coverage_option" value="emergency"/> Emergency</label>
                            <label><input type="checkbox" name="coverage_option" value="preventive"/> Preventive</label>
                            <label><input type="checkbox" name="coverage_option" value="dental"/> Dental</label>
                            <label><input type="checkbox" name="coverage_option" value="maternity"/> Maternity</label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm">Maximum Benefit Limit</label>
                        <input type="number" step="0.01" name="maximum_benefit_limit" class="w-full p-2 border rounded"/>
                    </div>
                    <div>
                        <label class="block text-sm">Premium Cost</label>
                        <input type="number" step="0.01" name="premium_cost" class="w-full p-2 border rounded"/>
                    </div>
                    <div>
                        <label class="block text-sm">Eligibility</label>
                        <select name="eligibility" class="w-full p-2 border rounded">
                            <option value="Individual">Individual</option>
                            <option value="Family">Family</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Accredited Hospitals (one per line or comma separated)</label>
                        <textarea name="accredited_hospitals" class="w-full p-2 border rounded" rows="3" placeholder="e.g. St. Luke's Medical Center\nThe Medical City"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="add-plan-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.getElementById('add-plan-close')?.addEventListener('click', ()=>{ document.getElementById('add-plan-overlay')?.remove(); });
    document.getElementById('add-plan-cancel')?.addEventListener('click', ()=>{ document.getElementById('add-plan-overlay')?.remove(); });
    document.getElementById('addPlanForm')?.addEventListener('submit', async e=>{
        e.preventDefault();
        const payload = {};
        const fd = new FormData(e.target);
        fd.forEach((v,k)=>{ if (k!=='coverage_option') payload[k]=v; });
        const checks = Array.from(e.target.querySelectorAll('input[name="coverage_option"]:checked')).map(c=>c.value);
        if (checks.length) payload.coverage = checks;
        if (payload.accredited_hospitals) payload.accredited_hospitals = payload.accredited_hospitals.trim();
        if (payload.eligibility) payload.eligibility = payload.eligibility.trim();
        const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j = await res.json();
        if (j.success) { document.getElementById('add-plan-overlay')?.remove(); renderHMOPlans(containerId); } else alert(j.error||'Failed');
    });
}

export async function showEditPlanModal(id, containerId='main-content-area'){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_plans.php?id=${id}`, { credentials:'include' }); const data = await r.json(); const p = data.plan || {};
        const pres = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials:'include' }); const pdata = await pres.json(); const providers = pdata.providers||[];
        const provOptions = providers.map(pp=>`<option value="${pp.ProviderID}" ${pp.ProviderID==p.ProviderID?'selected':''}>${pp.ProviderName}</option>`).join('');
    const coverageVal = Array.isArray(p.Coverage)?p.Coverage.join(', '):(p.Coverage?JSON.parse(p.Coverage).join(', '):'');
    const accreditedVal = Array.isArray(p.AccreditedHospitals)?p.AccreditedHospitals.join('\n'):(p.AccreditedHospitals?JSON.parse(p.AccreditedHospitals).join('\n'):'');
    const eligibilityVal = p.Eligibility || 'Individual';
        container.innerHTML = `
            <div id="edit-plan-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Edit Benefit Plan</h5>
                        <button id="edit-plan-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <form id="editPlanForm" class="p-4 space-y-3">
                        <input type="hidden" name="id" value="${id}" />
                        <div>
                            <label class="block text-sm">Provider</label>
                            <select name="provider_id" class="w-full p-2 border rounded" required>
                                <option value="">Select Provider</option>
                                ${provOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm">Plan Name</label>
                            <input name="plan_name" class="w-full p-2 border rounded" value="${p.PlanName||''}" required/>
                        </div>
                        <div>
                            <label class="block text-sm">Coverage</label>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <label><input type="checkbox" name="coverage_option" value="inpatient" ${coverageVal.includes('inpatient')?'checked':''}/> Inpatient</label>
                                <label><input type="checkbox" name="coverage_option" value="outpatient" ${coverageVal.includes('outpatient')?'checked':''}/> Outpatient</label>
                                <label><input type="checkbox" name="coverage_option" value="emergency" ${coverageVal.includes('emergency')?'checked':''}/> Emergency</label>
                                <label><input type="checkbox" name="coverage_option" value="preventive" ${coverageVal.includes('preventive')?'checked':''}/> Preventive</label>
                                <label><input type="checkbox" name="coverage_option" value="dental" ${coverageVal.includes('dental')?'checked':''}/> Dental</label>
                                <label><input type="checkbox" name="coverage_option" value="maternity" ${coverageVal.includes('maternity')?'checked':''}/> Maternity</label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm">Maximum Benefit Limit</label>
                            <input type="number" step="0.01" name="maximum_benefit_limit" class="w-full p-2 border rounded" value="${p.MaximumBenefitLimit||''}"/>
                        </div>
                        <div>
                            <label class="block text-sm">Premium Cost</label>
                            <input type="number" step="0.01" name="premium_cost" class="w-full p-2 border rounded" value="${p.PremiumCost||''}"/>
                        </div>
                        <div>
                            <label class="block text-sm">Eligibility</label>
                            <select name="eligibility" class="w-full p-2 border rounded">
                                <option value="Individual" ${eligibilityVal==='Individual'?'selected':''}>Individual</option>
                                <option value="Family" ${eligibilityVal==='Family'?'selected':''}>Family</option>
                                <option value="Corporate" ${eligibilityVal==='Corporate'?'selected':''}>Corporate</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm">Accredited Hospitals (one per line)</label>
                            <textarea name="accredited_hospitals" class="w-full p-2 border rounded" rows="3">${accreditedVal}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm">Status</label>
                            <select name="status" class="w-full p-2 border rounded"><option value="Active" ${p.Status==='Active'?'selected':''}>Active</option><option value="Inactive" ${p.Status==='Inactive'?'selected':''}>Inactive</option></select>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="edit-plan-cancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.getElementById('edit-plan-close')?.addEventListener('click', ()=>{ document.getElementById('edit-plan-overlay')?.remove(); });
        document.getElementById('edit-plan-cancel')?.addEventListener('click', ()=>{ document.getElementById('edit-plan-overlay')?.remove(); });
        document.getElementById('editPlanForm')?.addEventListener('submit', async e=>{
            e.preventDefault(); const payload = {}; const fd = new FormData(e.target); fd.forEach((v,k)=>{ if (k!=='coverage_option') payload[k]=v; });
            const checks = Array.from(e.target.querySelectorAll('input[name="coverage_option"]:checked')).map(c=>c.value);
            if (checks.length) payload.coverage = checks;
            if (payload.accredited_hospitals) payload.accredited_hospitals = payload.accredited_hospitals.trim();
            if (payload.eligibility) payload.eligibility = payload.eligibility.trim();
            const res = await fetch(`${API_BASE_URL}hmo_plans.php?id=${id}`, { method:'PUT', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }); const j = await res.json(); if (j.success) { document.getElementById('edit-plan-overlay')?.remove(); renderHMOPlans(containerId); } else alert(j.error||'Failed');
        });
    }catch(e){console.error(e); alert('Failed to load plan');}
}

export async function showPlanDetailsModal(id){
    const container = document.getElementById('modalContainer'); if (!container) return;
    try{
        const r = await fetch(`${API_BASE_URL}hmo_plans.php?id=${id}`, { credentials:'include' }); const data = await r.json(); const p = data.plan||{};
        // fetch provider details
        const pr = await fetch(`${API_BASE_URL}hmo_providers.php?id=${p.ProviderID}`, { credentials:'include' }); const pd = await pr.json(); const prov = pd.provider||{};
        container.innerHTML = `
            <div id="view-plan-overlay" class="fixed inset-0 z-60 flex items-center justify-center bg-black/40">
                <div class="bg-white rounded-lg shadow-lg w-full max-w-xl mx-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h5 class="text-lg font-semibold">Plan Details</h5>
                        <button id="view-plan-close" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <div class="p-4">
                        <h4 class="text-lg font-semibold mb-2">${p.PlanName||''}</h4>
                        <div><strong>Provider:</strong> ${prov.ProviderName||''}</div>
                        <div><strong>Coverage:</strong> ${p.Coverage? (Array.isArray(p.Coverage)?p.Coverage.join(', '):JSON.parse(p.Coverage).join(', ')) : ''}</div>
                        <div><strong>Max Limit:</strong> ${p.MaximumBenefitLimit||''}</div>
                        <div><strong>Premium:</strong> ${p.PremiumCost||''}</div>
                        <div class="mt-3"><strong>Provider Contact</strong><div>${prov.ContactPerson||''} ${prov.ContactNumber?('<br/>'+prov.ContactNumber):''} ${prov.Email?('<br/>'+prov.Email):''}</div></div>
                    </div>
                    <div class="flex justify-end p-4 border-t"><button id="view-plan-close-btn" class="px-4 py-2 bg-gray-200 rounded">Close</button></div>
                </div>
            </div>
        `;
        document.getElementById('view-plan-close')?.addEventListener('click', ()=>{ document.getElementById('view-plan-overlay')?.remove(); });
        document.getElementById('view-plan-close-btn')?.addEventListener('click', ()=>{ document.getElementById('view-plan-overlay')?.remove(); });
    }catch(e){console.error(e); alert('Failed to load plan details');}
}

// CSV Export Function
function exportPlansToCSV() {
    const plans = window._hmoPlansCache || [];
    if (plans.length === 0) {
        alert('No plans to export');
        return;
    }

    // Define CSV headers
    const headers = ['Plan ID', 'Plan Name', 'Provider', 'Coverage', 'Max Benefit Limit', 'Premium Cost', 'Eligibility', 'Status'];
    
    // Convert plans data to CSV rows
    const rows = plans.map(p => {
        const isActive = p.IsActive === 1 || p.IsActive === '1' || p.Status === 'Active' ? 'Active' : 'Inactive';
        const coverage = p.Coverage ? (Array.isArray(p.Coverage) ? p.Coverage.join('; ') : JSON.parse(p.Coverage || '[]').join('; ')) : '';
        
        return [
            p.PlanID || '',
            p.PlanName || '',
            p.ProviderName || '',
            coverage,
            p.MaximumBenefitLimit || '',
            p.PremiumCost || p.MonthlyPremium || '',
            p.Eligibility || '',
            isActive
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
    link.setAttribute('download', `hmo_plans_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log(`Exported ${plans.length} plans to CSV`);
}
