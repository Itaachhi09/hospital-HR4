// Clean HMO module (single coherent ES module)
import { API_BASE_URL } from '../utils.js';

function $id(id) { return document.getElementById(id); }

// UI renderers
export function displayHMOProvidersSection() {
    console.log('[HMO] displayHMOProvidersSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'HMO Providers & Plans';
    main.innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">HMO Providers & Plans</h2>
                <div class="space-x-2">
                    <button id="add-provider-btn" class="px-4 py-2 bg-blue-600 text-white rounded">Add Provider</button>
                    <button id="add-plan-btn" class="px-4 py-2 bg-green-600 text-white rounded">Add Plan</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded p-4"><h3 class="font-semibold">HMO Providers</h3><div id="providers-list">Loading...</div></div>
                <div class="bg-white rounded p-4"><h3 class="font-semibold">Benefit Plans</h3><div id="plans-list">Loading...</div></div>
            </div>
        </div>
    `;

    const addProviderBtn = $id('add-provider-btn');
    const addPlanBtn = $id('add-plan-btn');
    if (addProviderBtn) addProviderBtn.addEventListener('click', showAddProviderModal);
    if (addPlanBtn) addPlanBtn.addEventListener('click', showAddPlanModal);
    loadHMOProviders();
    loadHMOPlans();
}

export function displayHMOEnrollmentsSection() {
    console.log('[HMO] displayHMOEnrollmentsSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'HMO Enrollments';
    main.innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold">HMO Enrollments</h2><button id="add-enrollment-btn" class="px-4 py-2 bg-blue-600 text-white rounded">Add Enrollment</button></div>
            <div id="enrollments-list">Loading enrollments...</div>
        </div>
    `;

    const addEnrollmentBtn = $id('add-enrollment-btn');
    if (addEnrollmentBtn) addEnrollmentBtn.addEventListener('click', showAddEnrollmentModal);
    loadHMOEnrollments();
}

export function displayHMODashboardSection() {
    console.log('[HMO] displayHMODashboardSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'HMO Dashboard';
    main.innerHTML = `
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded p-4"><h4>Active Plans</h4><p id="stat-plans">...</p></div>
                <div class="bg-white rounded p-4"><h4>Enrolled</h4><p id="stat-enrolled">...</p></div>
                <div class="bg-white rounded p-4"><h4>Monthly Premiums</h4><p id="stat-premiums">...</p></div>
            </div>
            <div id="dashboard-extra" class="mt-4"></div>
        </div>
    `;

    loadHMODashboardStats();
}

export function displayHMOClaimsApprovalSection() {
    console.log('[HMO] displayHMOClaimsApprovalSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'HMO Claims Approval';
    main.innerHTML = `<div class="p-6"><div id="claims-list">Loading claims...</div></div>`;
    loadHMOClaimsForApproval();
}

export function displayEmployeeHMOSection() {
    console.log('[HMO] displayEmployeeHMOSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'My HMO Benefits';
    main.innerHTML = `<div class="p-6"><div id="my-hmo">Loading...</div></div>`;
    loadEmployeeHMOBenefits();
}

export function displayEmployeeHMOClaimsSection() {
    console.log('[HMO] displayEmployeeHMOClaimsSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'My HMO Claims';
    main.innerHTML = `<div class="p-6"><div id="my-claims">Loading...</div></div>`;
    loadEmployeeHMOClaims();
}

export function displaySubmitHMOClaimSection() {
    console.log('[HMO] displaySubmitHMOClaimSection called');
    const main = $id('main-content-area');
    const title = $id('page-title');
    if (!main || !title) return;

    title.textContent = 'Submit HMO Claim';
    main.innerHTML = `
        <div class="p-6">
            <form id="submit-hmo-claim-form">
                <div><label>Service Type</label><input name="serviceType" /></div>
                <div><label>Provider</label><input name="providerName" /></div>
                <div><label>Amount</label><input name="amountClaimed" type="number" /></div>
                <div><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Submit</button></div>
            </form>
            <div id="submit-claim-status" class="mt-2"></div>
        </div>
    `;

    const form = $id('submit-hmo-claim-form');
    if (form) form.addEventListener('submit', handleSubmitHMOClaim);
}

// Data loaders
async function loadHMOProviders() {
    console.log('[HMO] loadHMOProviders()');
    const container = $id('providers-list'); if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch(`${API_BASE_URL}get_hmo_providers.php`);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const providers = (data && data.providers) ? data.providers : (Array.isArray(data) ? data : []);
        if (providers.length > 0) {
            container.innerHTML = providers.map(p => `<div class="p-2 border-b"><b>${escapeHtml(p.ProviderName||p.name||p.provider_name||'Unnamed')}</b><div class="text-sm">${escapeHtml(p.ContactEmail||p.contact_email||p.email||'')}</div></div>`).join('');
        } else container.textContent = 'No providers found.';
    } catch (e) { console.error('[HMO] Error loading providers', e); container.textContent = 'Error loading providers.'; }
}

async function loadHMOPlans() {
    console.log('[HMO] loadHMOPlans()');
    const container = $id('plans-list'); if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch(`${API_BASE_URL}get_hmo_plans.php`);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const plans = (data && data.plans) ? data.plans : (Array.isArray(data) ? data : []);
        if (plans.length > 0) {
            container.innerHTML = plans.map(p => `<div class="p-2 border-b"><b>${escapeHtml(p.PlanName||p.name||p.plan_name||'Plan')}</b><div class="text-sm">${escapeHtml(p.ProviderName||p.provider_name||'')} - ₱${Number(p.MonthlyPremium||p.premium_amount||p.monthly_premium||0).toFixed(2)}</div></div>`).join('');
        } else container.textContent = 'No plans found.';
    } catch (e) { console.error(e); container.textContent = 'Error loading plans.'; }
}

async function loadHMOEnrollments() {
    console.log('[HMO] loadHMOEnrollments()');
    const container = $id('enrollments-list'); if (!container) return;
    container.textContent = 'Loading...';
    try {
    const debugParam = (['localhost','127.0.0.1'].includes(window.location.hostname)) ? '?debug=1' : '';
    const res = await fetch(`${API_BASE_URL}get_hmo_enrollments.php${debugParam}`);
        if (!res.ok) {
            const txt = await res.text();
            console.error('[HMO] get_hmo_enrollments.php HTTP error', res.status, txt);
            container.innerHTML = `<div class="p-3 bg-red-50 border border-red-100 text-red-800">Error loading enrollments: HTTP ${res.status}<pre style="white-space:pre-wrap;margin-top:8px;">${escapeHtml(txt)}</pre></div>`;
            return;
        }
        const data = await res.json();
        if (data && data.success && Array.isArray(data.enrollments)) {
            if (data.enrollments.length === 0) {
                container.innerHTML = '<div class="p-3 text-gray-600">No enrollments found.</div>';
                return;
            }
                container.innerHTML = data.enrollments.map(e => `<div class="p-2 border-b">${escapeHtml(e.EmployeeName||e.employee_name||'')} — ${escapeHtml(e.PlanName||e.plan_name||'')} <span class="text-xs">${escapeHtml(e.Status||e.status||'')}</span></div>`).join('');
        } else {
            console.warn('[HMO] get_hmo_enrollments.php returned unexpected shape', data);
            container.innerHTML = `<div class="p-3 bg-yellow-50 border border-yellow-100 text-yellow-800">No enrollments found or invalid response shape.<pre style="white-space:pre-wrap;margin-top:8px;">${escapeHtml(JSON.stringify(data, null, 2))}</pre></div>`;
        }
    } catch (e) { console.error(e); container.textContent = 'Error loading enrollments.'; }
}

// Small helper to avoid injecting raw server responses into the DOM
function escapeHtml(str) {
    if (!str && str !== 0) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadHMOClaimsForApproval() {
    console.log('[HMO] loadHMOClaimsForApproval()');
    const container = $id('claims-list'); if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch(`${API_BASE_URL}get_hmo_claims.php?status=pending`);
        const data = await res.json();
        if (data.success && Array.isArray(data.claims)) {
            container.innerHTML = data.claims.map(c => `<div class="p-2 border-b">${c.employee_name} — ₱${c.amount} <span class="text-xs">${c.status}</span></div>`).join('');
        } else container.textContent = 'No pending claims.';
    } catch (e) { console.error(e); container.textContent = 'Error loading claims.'; }
}

async function loadEmployeeHMOBenefits() {
    console.log('[HMO] loadEmployeeHMOBenefits()');
    const container = $id('my-hmo'); if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch(`${API_BASE_URL}get_employee_hmo_benefits.php?detailed=1`, { credentials: 'include' });
        const data = await res.json();
        if (data.success && Array.isArray(data.benefits) && data.benefits.length>0) {
            const b = data.benefits[0];
            container.innerHTML = `<div><b>${b.PlanName||b.name||'Plan'}</b><div class="text-sm">Provider: ${b.ProviderName||b.provider_name||''}</div></div>`;
        } else container.textContent = 'No active HMO benefits.';
    } catch (e) { console.error(e); container.textContent = 'Error loading benefits.'; }
}

async function loadEmployeeHMOClaims() {
    console.log('[HMO] loadEmployeeHMOClaims()');
    const container = $id('my-claims'); if (!container) return;
    container.textContent = 'Loading...';
    try {
        const res = await fetch(`${API_BASE_URL}get_employee_hmo_claims.php`, { credentials: 'include' });
        const data = await res.json();
        if (data.success && Array.isArray(data.claims)) {
            container.innerHTML = data.claims.map(c => `<div class="p-2 border-b">${c.ClaimType||c.type} — ₱${c.Amount||c.amount} <span class="text-xs">${c.Status||c.status}</span></div>`).join('');
        } else container.textContent = 'No claims found.';
    } catch (e) { console.error(e); container.textContent = 'Error loading claims.'; }
}

async function loadHMODashboardStats() {
    console.log('[HMO] loadHMODashboardStats()');
    try {
        const res = await fetch(`${API_BASE_URL}get_hmo_statistics.php`);
        const data = await res.json();
        if (data.success && data.data) {
            const summary = data.data.summary || {};
            const extra = $id('dashboard-extra');
            $id('stat-plans').textContent = summary.total_plans || '0';
            $id('stat-enrolled').textContent = summary.total_enrolled || '0';
            $id('stat-premiums').textContent = '₱' + (summary.total_monthly_premiums || '0');
            if (extra) extra.textContent = '';
        }
    } catch (e) { console.error('Error loading dashboard', e); }
}

// Handlers
async function handleSubmitHMOClaim(event) {
    event.preventDefault();
    const status = $id('submit-claim-status'); if (status) status.textContent = 'Submitting...';
    const form = event.target; const fd = new FormData(form);
    try {
        const res = await fetch(`${API_BASE_URL}submit_hmo_claim.php`, { method: 'POST', body: fd, credentials: 'include' });
        const data = await res.json();
        if (data.success) {
            if (status) status.textContent = 'Claim submitted.';
            form.reset();
        } else {
            if (status) status.textContent = data.message || 'Failed to submit claim.';
        }
    } catch (e) { console.error(e); if (status) status.textContent = 'Error submitting claim.'; }
}

// Simple placeholders for modals and actions
function showAddProviderModal() { const name = prompt('Provider name:'); if (name) alert('Add provider: '+name); }
function showAddPlanModal() { const name = prompt('Plan name:'); if (name) alert('Add plan: '+name); }
function showAddEnrollmentModal() { alert('Show add enrollment (placeholder)'); }

// Global placeholders used by inline onclick handlers in templates
window.editProvider = id => console.log('editProvider', id);
window.deleteProvider = id => console.log('deleteProvider', id);
window.editPlan = id => console.log('editPlan', id);
window.deletePlan = id => console.log('deletePlan', id);
window.editEnrollment = id => console.log('editEnrollment', id);
window.terminateEnrollment = id => console.log('terminateEnrollment', id);
window.approveClaim = id => console.log('approveClaim', id);
window.rejectClaim = id => console.log('rejectClaim', id);
window.viewClaimDetails = id => console.log('viewClaimDetails', id);
window.viewEmployeeClaimDetails = id => console.log('viewEmployeeClaimDetails', id);
window.refreshDashboard = () => loadHMODashboardStats();
window.downloadHMOCard = () => alert('Download HMO Card (placeholder)');

export default {
    displayHMOProvidersSection,
    displayHMOEnrollmentsSection,
    displayHMODashboardSection,
    displayHMOClaimsApprovalSection,
    displayEmployeeHMOSection,
    displayEmployeeHMOClaimsSection,
    displaySubmitHMOClaimSection
};

