import { API_BASE_URL } from '../utils.js';

// Simple HMO Admin UI module
export class HMOManagement {
    constructor() {
        this.providers = [];
        this.plans = [];
        this.enrollments = [];
        this.init();
    }

    init() {
        this.loadProviders();
        this.loadPlans();
        this.loadEnrollments();
    }

    async loadProviders() {
        try {
            const res = await fetch(`${API_BASE_URL}hmo_providers.php`, { credentials: 'include' });
            const data = await res.json();
            this.providers = data.providers || [];
        } catch (e) { console.error('Load providers error', e); }
    }

    async loadPlans() {
        try {
            const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials: 'include' });
            const data = await res.json();
            this.plans = data.plans || [];
        } catch (e) { console.error('Load plans error', e); }
    }

    async loadEnrollments() {
        try {
            const res = await fetch(`${API_BASE_URL}get_employee_enrollments.php`, { credentials: 'include' });
            const data = await res.json();
            if (data.success) this.enrollments = data.enrollments || [];
        } catch (e) { console.error('Load enrollments error', e); }
    }

    // Plans
    async loadPlans() {
        try {
            const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { credentials: 'include' });
            const data = await res.json();
            this.plans = data.plans || [];
        } catch (e) { console.error('Load plans error', e); }
    }

    async renderPlans(containerId = 'main-content-area') {
        await this.loadProviders();
        await this.loadPlans();
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">HMO Plans</h2>
                    <div>
                        <button id="refresh-hmo-plans" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="add-plan-btn" class="btn btn-sm btn-success">Add Plan</button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-plans-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Plan</th>
                                <th class="p-3">Provider</th>
                                <th class="p-3">Monthly Premium</th>
                                <th class="p-3">Annual Limit</th>
                                <th class="p-3">Active</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        ${this.plans.map(p => `
                            <tr>
                                <td class="p-3">${p.PlanName}</td>
                                <td class="p-3">${p.ProviderName || ''}</td>
                                <td class="p-3">${p.MonthlyPremium}</td>
                                <td class="p-3">${p.AnnualLimit || ''}</td>
                                <td class="p-3">${p.IsActive == 1 ? 'Yes' : 'No'}</td>
                                <td class="p-3"><button class="btn btn-sm btn-danger delete-plan-btn" data-id="${p.PlanID}">Delete</button></td>
                            </tr>
                        `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        document.getElementById('refresh-hmo-plans')?.addEventListener('click', () => this.renderPlans(containerId));
        document.getElementById('add-plan-btn')?.addEventListener('click', () => this.showAddPlanModal());
        container.querySelectorAll('.delete-plan-btn').forEach(btn => btn.addEventListener('click', (ev) => this.deletePlan(ev.target.dataset.id)));
    }

    showAddPlanModal() {
        const modalHtml = `
            <div class="modal fade" id="addPlanModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add HMO Plan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                        <form id="addPlanForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Provider</label>
                                    <select name="provider_id" class="form-control" required>
                                        <option value="">Select Provider</option>
                                        ${this.providers.map(p=>`<option value="${p.ProviderID}">${p.ProviderName}</option>`).join('')}
                                    </select>
                                </div>
                                <div class="mb-3"><label>Plan Name</label><input name="plan_name" class="form-control" required/></div>
                                <div class="mb-3"><label>Monthly Premium</label><input type="number" step="0.01" name="monthly_premium" class="form-control"/></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        const container = document.getElementById('modalContainer');
        container.innerHTML = modalHtml;
        $('#addPlanModal').modal('show');
        document.getElementById('addPlanForm').addEventListener('submit', async (e)=>{
            e.preventDefault();
            const fd = new FormData(e.target);
            const payload = {};
            fd.forEach((v,k)=>payload[k]=v);
            try {
                const res = await fetch(`${API_BASE_URL}hmo_plans.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
                const data = await res.json();
                if (data.success) {
                    $('#addPlanModal').modal('hide');
                    this.renderPlans();
                } else alert(data.error||'Failed to add plan');
            } catch(err){console.error(err); alert('Error adding plan');}
        });
    }

    async deletePlan(id) {
        if (!confirm('Delete plan?')) return;
        try {
            const res = await fetch(`${API_BASE_URL}hmo_plans.php?id=${id}`, { method:'DELETE', credentials:'include' });
            const data = await res.json();
            if (data.success) this.renderPlans(); else alert(data.error||'Failed');
        } catch(e){console.error(e); alert('Error deleting');}
    }

    // Render simplified admin enrollments table into a container
    async renderEnrollments(containerId = 'main-content-area') {
        await this.loadEnrollments();
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold">HMO Enrollments</h2>
                    <div>
                        <button id="refresh-hmo-enrollments" class="btn btn-sm btn-primary">Refresh</button>
                        <button id="add-enrollment-btn" class="btn btn-sm btn-success">Add Enrollment</button>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow">
                    <table class="w-full text-left" id="hmo-enrollments-table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-3">Employee</th>
                                <th class="p-3">Plan</th>
                                <th class="p-3">Provider</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Effective</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        ${this.enrollments.map(e => `
                            <tr>
                                <td class="p-3">${e.EmployeeName || e.EmployeeID}</td>
                                <td class="p-3">${e.PlanName || e.PlanID}</td>
                                <td class="p-3">${e.ProviderName || ''}</td>
                                <td class="p-3">${e.Status}</td>
                                <td class="p-3">${e.EffectiveDate || e.EnrollmentDate}</td>
                                <td class="p-3">
                                    <button class="btn btn-sm btn-danger delete-enrollment-btn" data-id="${e.EnrollmentID}">Remove</button>
                                </td>
                            </tr>
                        `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        document.getElementById('refresh-hmo-enrollments')?.addEventListener('click', () => this.renderEnrollments(containerId));
        document.getElementById('add-enrollment-btn')?.addEventListener('click', () => this.showAddEnrollmentModal());
        container.querySelectorAll('.delete-enrollment-btn').forEach(btn => btn.addEventListener('click', (ev) => this.deleteEnrollment(ev.target.dataset.id)));
    }

    showAddEnrollmentModal() {
        const modalHtml = `
            <div class="modal fade" id="addEnrollmentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add Enrollment</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                        <form id="addEnrollmentForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label>Employee ID</label>
                                    <input name="employee_id" class="form-control" required />
                                </div>
                                <div class="mb-3">
                                    <label>Plan</label>
                                    <select name="plan_id" class="form-control" required>
                                        <option value="">Select Plan</option>
                                        ${this.plans.map(p=>`<option value="${p.PlanID}">${p.PlanName} (${p.ProviderName||''})</option>`).join('')}
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Effective Date</label>
                                    <input type="date" name="effective_date" class="form-control" required />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        const container = document.getElementById('modalContainer');
        container.innerHTML = modalHtml;
        $('#addEnrollmentModal').modal('show');
        document.getElementById('addEnrollmentForm').addEventListener('submit', async (e)=>{
            e.preventDefault();
            const fd = new FormData(e.target);
            const payload = {};
            fd.forEach((v,k)=>payload[k]=v);
            try {
                const res = await fetch(`${API_BASE_URL}hmo_enrollments.php`, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
                const data = await res.json();
                if (data.success) {
                    $('#addEnrollmentModal').modal('hide');
                    this.renderEnrollments();
                } else alert(data.error||'Failed to enroll');
            } catch(err){console.error(err); alert('Error enrolling');}
        });
    }

    async deleteEnrollment(id) {
        if (!confirm('Remove enrollment?')) return;
        try {
            const res = await fetch(`${API_BASE_URL}hmo_enrollments.php?id=${id}`, { method:'DELETE', credentials:'include' });
            const data = await res.json();
            if (data.success) {
                this.renderEnrollments();
            } else alert(data.error||'Failed to remove');
        } catch(e){console.error(e); alert('Error removing');}
    }
}

export function displayHMOAdminSection() {
    const main = document.getElementById('main-content-area');
    const pageTitle = document.getElementById('page-title');
    if (pageTitle) pageTitle.textContent = 'HMO Management';
    const mgr = new HMOManagement();
    // Small delay to ensure plans/providers loaded
    setTimeout(()=>mgr.renderEnrollments('main-content-area'),200);
}

export function displayHMOPlansSection() { displayHMOAdminSection(); }
