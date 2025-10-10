// Enhanced employee-facing HMO view with detailed plan information
import { REST_API_URL } from '../utils.js';

export async function renderEmployeeHMO(containerId='main-content-area'){
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = `<div class="p-4">Loading your HMO information...</div>`;
    try{
        const res = await fetch(`${LEGACY_API_URL}get_employee_enrollments.php`, { credentials: 'include' });
        const data = await res.json();
        const enrollments = data.enrollments || [];
        if (!enrollments.length){
            container.innerHTML = `<div class="p-6 text-center text-muted">You have no HMO enrollments. Contact HR or check back later.</div>`;
            return;
        }
        const rows = await Promise.all(enrollments.map(async e=>{
            const plan = e.PlanName || '';
            const provider = e.ProviderName || '';
            const status = e.Status || '';
            const start = e.StartDate || e.EnrollmentDate || '';
            const end = e.EndDate || '';
            // Fetch detailed plan info
            let planDetails = {};
            try {
                const planRes = await fetch(`${REST_API_URL}hmo/plans?id=${e.PlanID}`, { credentials: 'include' });
                const planData = await planRes.json();
                planDetails = planData.plan || {};
            } catch (err) {
                console.error('Failed to fetch plan details', err);
            }
            const coverage = Array.isArray(planDetails.Coverage) ? planDetails.Coverage : (planDetails.Coverage ? JSON.parse(planDetails.Coverage) : []);
            const accreditedHospitals = Array.isArray(planDetails.AccreditedHospitals) ? planDetails.AccreditedHospitals : (planDetails.AccreditedHospitals ? JSON.parse(planDetails.AccreditedHospitals) : []);
            const eligibility = planDetails.Eligibility || 'Individual';
            const maxLimit = planDetails.MaximumBenefitLimit || 'N/A';
            const premium = planDetails.PremiumCost || 'N/A';
            return `<div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">${provider} — ${plan} <span class="badge ${status === 'Active' ? 'bg-success' : 'bg-secondary'}">${status}</span></h5>
                    <p class="mb-1"><strong>Effective:</strong> ${start}${end?(' — '+end):''}</p>
                    <p class="mb-1"><strong>Eligibility:</strong> ${eligibility}</p>
                    <p class="mb-1"><strong>Coverage:</strong> ${coverage.join(', ') || 'N/A'}</p>
                    <p class="mb-1"><strong>Maximum Benefit Limit:</strong> ${maxLimit !== 'N/A' ? '₱' + parseFloat(maxLimit).toLocaleString() : 'N/A'}</p>
                    <p class="mb-1"><strong>Premium Cost:</strong> ${premium !== 'N/A' ? '₱' + parseFloat(premium).toLocaleString() + ' per month' : 'N/A'}</p>
                    <div class="mt-3">
                        <strong>Accredited Hospitals & Clinics:</strong>
                        <ul class="mb-0">${accreditedHospitals.slice(0,10).map(h=>`<li>${h}</li>`).join('')}${accreditedHospitals.length > 10 ? '<li>...and more</li>' : ''}</ul>
                    </div>
                </div>
            </div>`;
        }));
        container.innerHTML = `<div class="p-4"><h3>Your HMO Benefits</h3><p class="text-muted">View your current HMO provider, plan details, and accredited facilities.</p>${rows.join('')}</div>`;
    }catch(e){
        console.error('Failed to load enrollments', e);
        container.innerHTML = `<div class="p-4 text-danger">Unable to load HMO enrollments. Try again later.</div>`;
    }
}

export function showEmployeeHMOPlaceholder(){
    // small helper for inline fallback calls
    renderEmployeeHMO('main-content-area');
}
