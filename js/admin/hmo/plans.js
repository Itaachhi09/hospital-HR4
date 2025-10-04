import { API_BASE_URL } from '../../utils.js';

export async function renderHMOPlans(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    try{
        const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials:'include' });
        const data = await res.json(); const plans = data.plans || [];
        // load providers for filter and selects
        const pres = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials:'include' }); const pdata = await pres.json(); const providers = pdata.providers||[];
        window._hmoPlansCache = plans;
        window._hmoProvidersForPlans = providers;
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">Benefit Plans</h2>
                    <div>
                        <button id="refresh-plans" class="hmo-btn hmo-btn-primary">Refresh</button>
                        <button id="add-plan-btn" class="hmo-btn hmo-btn-success">Add Plan</button>
                    </div>
                </div>
                <div class="flex items-center gap-4 mb-3">
                    <input id="hmo-plan-search" placeholder="Search plans or coverage..." class="form-control" style="max-width:360px;" />
                    <select id="hmo-plan-provider-filter" class="form-control" style="max-width:240px;"><option value="">All providers</option>${providers.map(p=>`<option value="${p.ProviderID}">${p.ProviderName}</option>`).join('')}</select>
                    <select id="hmo-plan-status-filter" class="form-control" style="max-width:160px;"><option value="">All statuses</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-plans-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Plan</th>
                                <th class="p-3">Provider</th>
                                <th class="p-3">Coverage</th>
                                <th class="p-3">Max Limit</th>
                                <th class="p-3">Premium</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hmo-plans-tbody"></tbody>
                    </table>
                </div>
            </div>
        `;
        populatePlansTbody(window._hmoPlansCache || []);
        document.getElementById('refresh-plans')?.addEventListener('click', ()=>renderHMOPlans(containerId));
        document.getElementById('add-plan-btn')?.addEventListener('click', ()=>showAddPlanModal(containerId));
        document.getElementById('empty-add-plan')?.addEventListener('click', ()=>showAddPlanModal(containerId));
        document.getElementById('hmo-plan-search')?.addEventListener('input', ()=>applyPlanFilters());
        document.getElementById('hmo-plan-provider-filter')?.addEventListener('change', ()=>applyPlanFilters());
        document.getElementById('hmo-plan-status-filter')?.addEventListener('change', ()=>applyPlanFilters());
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading plans</div>'}
}

function applyPlanFilters(){
    const q = (document.getElementById('hmo-plan-search')?.value||'').toLowerCase().trim();
    const provider = (document.getElementById('hmo-plan-provider-filter')?.value||'');
    const status = (document.getElementById('hmo-plan-status-filter')?.value||'');
    const all = window._hmoPlansCache || [];
    const filtered = all.filter(p=>{
        if (provider && String(p.ProviderID) !== String(provider)) return false;
        if (status && ((p.Status||'') !== status)) return false;
        if (!q) return true;
        const cov = p.Coverage ? (Array.isArray(p.Coverage)?p.Coverage.join(' '):JSON.parse(p.Coverage||'[]').join(' ')) : '';
        return (p.PlanName||'').toLowerCase().includes(q) || (p.ProviderName||'').toLowerCase().includes(q) || cov.toLowerCase().includes(q);
    });
    populatePlansTbody(filtered);
}

function populatePlansTbody(plans){
    const tbody = document.getElementById('hmo-plans-tbody'); if (!tbody) return;
    if (!plans || plans.length === 0){
    tbody.innerHTML = `<tr><td class="p-6 text-center text-sm text-gray-500" colspan="7">No benefit plans found. Click "Add Plan" to create one or import seed data from <code>database/hmo_schema_and_seed.sql</code>.<div class="mt-2"><button id="empty-add-plan" class="hmo-btn hmo-btn-success">Add Plan</button></div></td></tr>`;
        document.getElementById('empty-add-plan')?.addEventListener('click', ()=>showAddPlanModal());
        return;
    }
    tbody.innerHTML = plans.map(p=>`<tr>
        <td class="p-3">${p.PlanName}</td>
        <td class="p-3">${p.ProviderName||''}</td>
        <td class="p-3">${p.Coverage? (Array.isArray(p.Coverage)?p.Coverage.join(', '):JSON.parse(p.Coverage||'[]').join(', ')) : ''}</td>
        <td class="p-3">${p.MaximumBenefitLimit||''}</td>
        <td class="p-3">${p.PremiumCost||''}</td>
        <td class="p-3">${p.Status||''}</td>
        <td class="p-3">
            <button class="hmo-btn hmo-btn-secondary view-plan" data-id="${p.PlanID}">View</button>
            <button class="hmo-btn hmo-btn-secondary edit-plan" data-id="${p.PlanID}">Edit</button>
            <button class="hmo-btn hmo-btn-danger delete-plan" data-id="${p.PlanID}">Delete</button>
        </td>
    </tr>`).join('');
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
