import { API_BASE_URL, REST_API_URL } from '../config.js';

// Helper: set page title and main container reference
function setPage(title) {
    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    if (pageTitleElement) pageTitleElement.textContent = title;
    return mainContentArea;
}

// Helper: generic fetch wrapper for API v2 router (api/index.php)
async function apiFetch(path, options = {}) {
    const url = `${REST_API_URL}${path}`;
    const resp = await fetch(url, {
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        ...options
    });
    if (!resp.ok) throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
    return resp.json();
}

// Clean, minimal hub screen
export async function displayCompensationPlansSection() {
    const main = setPage('Compensation Planning');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b bg-indigo-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-indigo-900">Compensation Plans</h3>
                    <p class="text-sm text-indigo-700">Define, manage, and simulate compensation strategies across departments. Connected to HR Core and Payroll.</p>
                </div>
                <div class="space-x-2">
                    <button id="btn-create-plan" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">+ Create New Plan</button>
                    <select id="active-plan-select" class="px-3 py-2 border rounded text-sm"></select>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Plans Overview</h4>
                    <div id="plans-list" class="overflow-x-auto"></div>
                </div>
                <div class="border-t pt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Planning Tools <span id="plan-context-label" class="text-sm text-gray-500"></span></h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button id="go-salary-grades" class="px-4 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">Salary Grades</button>
                        <button id="go-pay-bands" class="px-4 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Pay Bands</button>
                        <button id="go-employee-mapping" class="px-4 py-3 bg-emerald-600 text-white rounded hover:bg-emerald-700 text-sm">Employee–Grade Mapping</button>
                        <button id="go-workflows" class="px-4 py-3 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm">Pay Adjustment Workflows</button>
                        <button id="go-simulations" class="px-4 py-3 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm">Simulation Tools</button>
                        <button id="go-salary-adjust" class="px-4 py-3 bg-pink-600 text-white rounded hover:bg-pink-700 text-sm">Quick Salary Adjustments</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('go-salary-grades')?.addEventListener('click', () => displaySalaryGradesSection());
    document.getElementById('go-pay-bands')?.addEventListener('click', () => displayPayBandsSection());
    document.getElementById('go-employee-mapping')?.addEventListener('click', () => displayEmployeeMappingSection());
    document.getElementById('go-workflows')?.addEventListener('click', () => displayWorkflowsSection());
    document.getElementById('go-simulations')?.addEventListener('click', () => displaySimulationToolsSection());
    document.getElementById('go-salary-adjust')?.addEventListener('click', () => displaySalaryAdjustmentsSection());

    document.getElementById('btn-create-plan')?.addEventListener('click', () => openCreatePlanModal());
    await loadPlansIntoUI();
}

// --- Plans UI helpers ---
let activePlanId = null;

async function loadPlansIntoUI() {
    const listContainer = document.getElementById('plans-list');
    const planSelect = document.getElementById('active-plan-select');
    const contextLabel = document.getElementById('plan-context-label');
    if (listContainer) listContainer.innerHTML = '<div class="py-4 text-center text-gray-500">Loading plans...</div>';
    try {
        // Prefer REST api if available; fallback to legacy php endpoints
        let plans = [];
        try {
            const rest = await apiFetch('compensation-planning/plans');
            plans = Array.isArray(rest) ? rest : (rest?.data || []);
        } catch (e) {
            const resp = await fetch(`${LEGACY_API_URL}get_compensation_plans.php`, { credentials:'include' });
            if (resp.ok) plans = await resp.json();
        }

        // Populate dropdown
        if (planSelect) {
            planSelect.innerHTML = '';
            const placeholder = document.createElement('option'); placeholder.value=''; placeholder.textContent='Select Active Plan'; planSelect.appendChild(placeholder);
            plans.forEach(p => {
                const o = document.createElement('option');
                o.value = p.PlanID || p.id; o.textContent = p.PlanName || p.name;
                if (String(o.value) === String(activePlanId)) o.selected = true;
                planSelect.appendChild(o);
            });
            planSelect.addEventListener('change', () => {
                activePlanId = planSelect.value || null;
                if (contextLabel) contextLabel.textContent = activePlanId ? `(Active Plan: ${planSelect.options[planSelect.selectedIndex].text})` : '';
                // Could persist in sessionStorage for continuity
                try { if (activePlanId) sessionStorage.setItem('activeCompPlanId', String(activePlanId)); else sessionStorage.removeItem('activeCompPlanId'); } catch(e){}
            });
            try { const saved = sessionStorage.getItem('activeCompPlanId'); if (saved) { activePlanId = saved; planSelect.value = saved; if (contextLabel) contextLabel.textContent = `(Active Plan: ${planSelect.options[planSelect.selectedIndex].text})`; } } catch(e){}
        }

        // Render plan cards/table
        if (listContainer) {
            if (!plans.length) { listContainer.innerHTML = '<div class="py-6 text-center text-gray-500">No plans yet. Click "+ Create New Plan" to add one.</div>'; return; }
            const table = document.createElement('table');
            table.className = 'min-w-full divide-y divide-gray-200';
            table.innerHTML = `
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Effective</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Projected Cost</th>
                </tr></thead>`;
            const tbody = table.createTBody(); tbody.className = 'bg-white divide-y divide-gray-200';
            plans.forEach(p => {
                const tr = tbody.insertRow();
                const td = (t, cls='') => { const c = tr.insertCell(); c.className = `px-4 py-3 text-sm ${cls}`; c.textContent = t ?? ''; return c; };
                td(p.PlanName || p.name, 'font-medium');
                td(p.EffectiveDateFormatted || p.EffectiveDate || p.effective_date || '');
                td(p.PlanType || p.plan_type || '');
                td(p.Status || p.status || 'Draft');
                td(p.ProjectedCost ? currency(p.ProjectedCost) : '—');
            });
            listContainer.innerHTML = '';
            listContainer.appendChild(table);
        }
    } catch (e) {
        if (listContainer) listContainer.innerHTML = `<div class="py-6 text-center text-red-600">Failed to load plans: ${e.message}</div>`;
    }
}

function openCreatePlanModal() {
    const existing = document.getElementById('plan-modal'); if (existing) existing.remove();
    const wrapper = document.createElement('div'); wrapper.id = 'plan-modal'; wrapper.className='fixed inset-0 z-50';
    wrapper.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-2xl mx-auto mt-16 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h4 class="font-semibold">Create Compensation Plan</h4>
                <button id="plan-close" class="text-gray-500">✕</button>
            </div>
            <div class="p-4 space-y-3">
                <input id="p-name" class="border rounded p-2 w-full" placeholder="Plan Name (e.g., FY2025 Salary Strategy)" />
                <textarea id="p-desc" class="border rounded p-2 w-full" placeholder="Description (optional)"></textarea>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input id="p-effective" type="date" class="border rounded p-2" />
                    <input id="p-end" type="date" class="border rounded p-2" />
                </div>
                <input id="p-type" class="border rounded p-2 w-full" placeholder="Plan Type (e.g., Hospital-wide, Nursing Dept)" />
                <div class="flex justify-end gap-2">
                    <button id="p-save" class="px-3 py-2 bg-indigo-600 text-white rounded text-sm">Save</button>
                    <button id="p-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(wrapper);
    document.getElementById('plan-close')?.addEventListener('click', () => wrapper.remove());
    document.getElementById('p-cancel')?.addEventListener('click', () => wrapper.remove());
    document.getElementById('p-save')?.addEventListener('click', async () => {
        try {
            const payload = {
                plan_name: document.getElementById('p-name')?.value?.trim(),
                description: document.getElementById('p-desc')?.value?.trim() || null,
                effective_date: document.getElementById('p-effective')?.value,
                end_date: document.getElementById('p-end')?.value || null,
                plan_type: document.getElementById('p-type')?.value?.trim() || null
            };
            // Prefer REST; fallback to php endpoint
            try {
                await apiFetch('compensation-planning/plans', { method: 'POST', body: JSON.stringify(payload) });
            } catch (e) {
                const resp = await fetch(`${LEGACY_API_URL}add_compensation_plan.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload), credentials:'include' });
                if (!resp.ok) throw new Error(`Legacy add plan failed (${resp.status})`);
            }
            wrapper.remove();
            await loadPlansIntoUI();
        } catch (e) { alert('Create plan failed: ' + e.message); }
    });
}

// Salary Grades CRUD
export async function displaySalaryGradesSection() {
    const main = setPage('Salary Grades & Steps');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-indigo-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-indigo-900">Salary Grades</h3>
                    <p class="text-sm text-indigo-700">Manage grades, steps, and approvals.</p>
                </div>
                <div class="space-x-2">
                    <button id="btn-new-grade" class="px-3 py-2 bg-indigo-600 text-white rounded text-sm">New Grade</button>
                    <button id="btn-refresh-grades" class="px-3 py-2 border border-indigo-300 text-indigo-700 rounded text-sm bg-white">Refresh</button>
                    <button id="btn-export-grades" class="px-3 py-2 border border-indigo-300 text-indigo-700 rounded text-sm bg-white">Export CSV</button>
                </div>
            </div>
            <div class="p-6">
                <div id="grades-list" class="overflow-x-auto"></div>
            </div>
        </div>
        <div id="grade-modal" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="relative max-w-2xl mx-auto mt-20 bg-white rounded shadow">
                <div class="px-4 py-3 border-b flex justify-between items-center">
                    <h4 class="font-semibold">New / Edit Salary Grade</h4>
                    <button id="grade-modal-close" class="text-gray-500">✕</button>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input id="g-grade-code" class="border rounded p-2" placeholder="Grade Code" />
                        <input id="g-grade-name" class="border rounded p-2" placeholder="Grade Name" />
                        <input id="g-position-category" class="border rounded p-2" placeholder="Position Category" />
                        <input id="g-department-id" class="border rounded p-2" placeholder="Department ID (optional)" />
                        <input id="g-branch-id" class="border rounded p-2" placeholder="Branch ID (optional)" />
                        <input id="g-effective-date" type="date" class="border rounded p-2" />
                        <input id="g-end-date" type="date" class="border rounded p-2" />
                        <select id="g-status" class="border rounded p-2">
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <textarea id="g-description" class="border rounded p-2 w-full" placeholder="Description"></textarea>
                    <div class="flex justify-end gap-2">
                        <button id="g-save" class="px-3 py-2 bg-indigo-600 text-white rounded text-sm">Save</button>
                        <button id="g-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('btn-new-grade')?.addEventListener('click', () => toggleGradeModal(true));
    document.getElementById('btn-refresh-grades')?.addEventListener('click', loadGrades);
    document.getElementById('grade-modal-close')?.addEventListener('click', () => toggleGradeModal(false));
    document.getElementById('g-cancel')?.addEventListener('click', () => toggleGradeModal(false));
    document.getElementById('g-save')?.addEventListener('click', saveGrade);
    document.getElementById('btn-export-grades')?.addEventListener('click', exportGradesCSV);

    await loadGrades();
}

let editingGradeId = null;

async function loadGrades() {
    const container = document.getElementById('grades-list');
    if (!container) return;
    container.innerHTML = `<div class="py-6 text-center text-gray-500">Loading grades...</div>`;
    try {
        const result = await apiFetch('compensation-planning/salary-grades');
        const rows = Array.isArray(result) ? result : result?.data || [];
        if (!rows.length) {
            container.innerHTML = `<div class="py-8 text-center text-gray-500">No salary grades found.</div>`;
            return;
        }
        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `
            <thead class="bg-indigo-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Position Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Effective</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-indigo-700 uppercase">Actions</th>
                </tr>
            </thead>`;
        const tbody = table.createTBody();
        tbody.className = 'bg-white divide-y divide-gray-200';
        rows.forEach(g => {
            const tr = tbody.insertRow();
            const td = (text, cls = '') => { const c = tr.insertCell(); c.className = `px-4 py-3 text-sm ${cls}`; c.textContent = text ?? ''; return c; };
            td(g.GradeCode, 'font-medium');
            td(g.GradeName);
            td(g.DepartmentName || '');
            td(g.PositionCategory || '');
            td(g.EffectiveDate || '');
            td(g.Status || '');
            const actions = tr.insertCell();
            actions.className = 'px-4 py-3 text-sm';
            const btnSteps = document.createElement('button'); btnSteps.className = 'text-indigo-600 hover:underline mr-2'; btnSteps.textContent = 'Steps'; btnSteps.onclick = () => loadStepsModal(g.GradeID, g.GradeCode);
            const btnEdit = document.createElement('button'); btnEdit.className = 'text-gray-700 hover:underline mr-2'; btnEdit.textContent = 'Edit'; btnEdit.onclick = () => openEditGradeModal(g);
            const btnDelete = document.createElement('button'); btnDelete.className = 'text-red-600 hover:underline mr-2'; btnDelete.textContent = 'Delete'; btnDelete.onclick = () => deleteGrade(g.GradeID);
            const btnApprove = document.createElement('button'); btnApprove.className = 'text-green-600 hover:underline'; btnApprove.textContent = 'Approve'; btnApprove.onclick = () => approveGrade(g.GradeID);
            actions.appendChild(btnSteps); actions.appendChild(btnEdit); actions.appendChild(btnDelete); actions.appendChild(btnApprove);
        });
        container.innerHTML = '';
        container.appendChild(table);
    } catch (e) {
        container.innerHTML = `<div class="py-6 text-center text-red-600">Failed to load grades: ${e.message}</div>`;
    }
}

function toggleGradeModal(show) {
    const m = document.getElementById('grade-modal');
    if (!m) return;
    m.classList.toggle('hidden', !show);
}

async function saveGrade() {
    try {
        const payload = {
            grade_code: document.getElementById('g-grade-code')?.value?.trim(),
            grade_name: document.getElementById('g-grade-name')?.value?.trim(),
            description: document.getElementById('g-description')?.value?.trim() || null,
            department_id: parseInt(document.getElementById('g-department-id')?.value) || null,
            position_category: document.getElementById('g-position-category')?.value?.trim() || null,
            branch_id: parseInt(document.getElementById('g-branch-id')?.value) || null,
            effective_date: document.getElementById('g-effective-date')?.value,
            end_date: document.getElementById('g-end-date')?.value || null,
            status: document.getElementById('g-status')?.value || 'Draft',
            created_by: window.currentUser?.employee_id || 1
        };
        if (editingGradeId) {
            await apiFetch('compensation-planning/salary-grades', { method: 'PUT', body: JSON.stringify({ id: editingGradeId, ...payload }) });
        } else {
            const res = await apiFetch('compensation-planning/salary-grades', { method: 'POST', body: JSON.stringify(payload) });
            const newId = res?.id || res?.GradeID || null;
            if (newId && activePlanId) {
                try { await fetch(`${LEGACY_API_URL}link_compensation_plan_item.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ plan_id: Number(activePlanId), item_type:'grade', item_id: Number(newId) }) }); } catch(e){}
            }
        }
        toggleGradeModal(false);
        editingGradeId = null;
        await loadGrades();
    } catch (e) {
        alert('Save failed: ' + e.message);
    }
}

function openEditGradeModal(grade) {
    editingGradeId = grade.GradeID;
    document.getElementById('g-grade-code').value = grade.GradeCode || '';
    document.getElementById('g-grade-name').value = grade.GradeName || '';
    document.getElementById('g-position-category').value = grade.PositionCategory || '';
    document.getElementById('g-department-id').value = grade.DepartmentID || '';
    document.getElementById('g-branch-id').value = grade.BranchID || '';
    document.getElementById('g-effective-date').value = (grade.EffectiveDate || '').slice(0,10);
    document.getElementById('g-end-date').value = (grade.EndDate || '') ? grade.EndDate.slice(0,10) : '';
    document.getElementById('g-status').value = grade.Status || 'Draft';
    document.getElementById('g-description').value = grade.Description || '';
    toggleGradeModal(true);
}

async function deleteGrade(gradeId) {
    if (!confirm('Delete this salary grade? This cannot be undone.')) return;
    try {
        await apiFetch('compensation-planning/salary-grades?id=' + encodeURIComponent(gradeId), { method: 'DELETE' });
        await loadGrades();
    } catch (e) { alert('Delete failed: ' + e.message); }
}

async function approveGrade(gradeId) {
    try {
        const payload = { id: gradeId, approved_by: window.currentUser?.employee_id || 1 };
        await apiFetch('compensation-planning/salary-grades-approve', { method: 'PUT', body: JSON.stringify(payload) });
        await loadGrades();
    } catch (e) { alert('Approval failed: ' + e.message); }
}

async function loadStepsModal(gradeId, gradeCode) {
    const existing = document.getElementById('steps-modal');
    if (existing) existing.remove();
    const wrapper = document.createElement('div');
    wrapper.id = 'steps-modal';
    wrapper.className = 'fixed inset-0 z-50';
    wrapper.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-3xl mx-auto mt-16 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h4 class="font-semibold">Steps for ${gradeCode}</h4>
                <button id="steps-close" class="text-gray-500">✕</button>
            </div>
            <div class="p-4">
                <div class="flex justify-between mb-3">
                    <button id="btn-new-step" class="px-3 py-2 bg-indigo-600 text-white rounded text-sm">New Step</button>
                    <button id="btn-refresh-steps" class="px-3 py-2 border rounded text-sm">Refresh</button>
                </div>
                <div id="steps-list" class="overflow-x-auto"></div>
            </div>
        </div>`;
    document.body.appendChild(wrapper);
    document.getElementById('steps-close')?.addEventListener('click', () => wrapper.remove());
    document.getElementById('btn-refresh-steps')?.addEventListener('click', () => loadSteps(gradeId));
    document.getElementById('btn-new-step')?.addEventListener('click', () => createStep(gradeId));
    await loadSteps(gradeId);
}

async function loadSteps(gradeId) {
    const list = document.getElementById('steps-list');
    if (!list) return;
    list.innerHTML = `<div class="py-4 text-center text-gray-500">Loading steps...</div>`;
    try {
        const result = await apiFetch(`compensation-planning/salary-grades-steps?grade_id=${encodeURIComponent(gradeId)}`);
        const steps = Array.isArray(result) ? result : result?.data || [];
        if (!steps.length) { list.innerHTML = `<div class="py-4 text-center text-gray-500">No steps found.</div>`; return; }
        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `<thead class="bg-gray-50"><tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Step</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Base</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Min</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Max</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Effective</th>
        </tr></thead>`;
        const tbody = table.createTBody();
        tbody.className = 'bg-white divide-y divide-gray-200';
        steps.forEach(s => {
            const tr = tbody.insertRow();
            const td = (t) => { const c = tr.insertCell(); c.className = 'px-3 py-2 text-sm'; c.textContent = t ?? ''; return c; };
            td(String(s.StepNumber));
            td(currency(s.BaseRate));
            td(currency(s.MinRate));
            td(currency(s.MaxRate));
            const actionsCell = tr.insertCell(); actionsCell.className = 'px-3 py-2 text-sm';
            actionsCell.colSpan = 1; // keep structure simple
            const eff = document.createElement('span'); eff.textContent = s.EffectiveDate || ''; eff.className = 'mr-3'; actionsCell.appendChild(eff);
            const btnEdit = document.createElement('button'); btnEdit.className = 'text-gray-700 hover:underline mr-2'; btnEdit.textContent = 'Edit'; btnEdit.onclick = () => editStep(gradeId, s);
            const btnDel = document.createElement('button'); btnDel.className = 'text-red-600 hover:underline'; btnDel.textContent = 'Delete'; btnDel.onclick = () => deleteStep(s.StepID);
            actionsCell.appendChild(btnEdit); actionsCell.appendChild(btnDel);
        });
        list.innerHTML = '';
        list.appendChild(table);
    } catch (e) {
        list.innerHTML = `<div class="py-4 text-center text-red-600">Failed to load steps: ${e.message}</div>`;
    }
}

async function createStep(gradeId) {
    const stepNumber = prompt('Step Number');
    if (!stepNumber) return;
    const baseRate = prompt('Base Rate (PHP)');
    const minRate = prompt('Min Rate (PHP)');
    const maxRate = prompt('Max Rate (PHP)');
    const effectiveDate = prompt('Effective Date (YYYY-MM-DD)', new Date().toISOString().slice(0,10));
    try {
        const res = await apiFetch('compensation-planning/salary-grades-steps', {
            method: 'POST',
            body: JSON.stringify({ grade_id: gradeId, step_number: Number(stepNumber), base_rate: Number(baseRate||0), min_rate: Number(minRate||0), max_rate: Number(maxRate||0), effective_date: effectiveDate })
        });
        const newId = res?.id || res?.StepID || null;
        if (newId && activePlanId) {
            try { await fetch(`${LEGACY_API_URL}link_compensation_plan_item.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ plan_id: Number(activePlanId), item_type:'step', item_id: Number(newId), metadata:{ grade_id: gradeId } }) }); } catch(e){}
        }
        await loadSteps(gradeId);
    } catch (e) { alert('Create step failed: ' + e.message); }
}

async function editStep(gradeId, step) {
    const stepNumber = prompt('Step Number', step.StepNumber);
    if (!stepNumber && stepNumber !== 0) return;
    const baseRate = prompt('Base Rate (PHP)', step.BaseRate);
    const minRate = prompt('Min Rate (PHP)', step.MinRate);
    const maxRate = prompt('Max Rate (PHP)', step.MaxRate);
    const effectiveDate = prompt('Effective Date (YYYY-MM-DD)', (step.EffectiveDate || '').slice(0,10));
    try {
        await apiFetch('compensation-planning/salary-grades-steps', {
            method: 'PUT',
            body: JSON.stringify({ id: step.StepID, step_number: Number(stepNumber||0), base_rate: Number(baseRate||0), min_rate: Number(minRate||0), max_rate: Number(maxRate||0), effective_date: effectiveDate })
        });
        await loadSteps(gradeId);
    } catch (e) { alert('Update step failed: ' + e.message); }
}

async function deleteStep(stepId) {
    if (!confirm('Delete this step?')) return;
    try {
        await apiFetch('compensation-planning/salary-grades-steps?id=' + encodeURIComponent(stepId), { method: 'DELETE' });
        // reload current list by closing and re-opening handled by caller's refresh
        document.getElementById('btn-refresh-steps')?.click();
    } catch (e) { alert('Delete step failed: ' + e.message); }
}

// Pay Bands CRUD
export async function displayPayBandsSection() {
    const main = setPage('Pay Bands');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-blue-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-blue-900">Pay Bands</h3>
                    <p class="text-sm text-blue-700">Manage salary ranges by department/position.</p>
                </div>
                <div class="space-x-2">
                    <button id="btn-new-band" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">New Band</button>
                    <button id="btn-refresh-bands" class="px-3 py-2 border border-blue-300 text-blue-700 rounded text-sm bg-white">Refresh</button>
                    <button id="btn-export-bands" class="px-3 py-2 border border-blue-300 text-blue-700 rounded text-sm bg-white">Export CSV</button>
                </div>
            </div>
            <div class="p-6">
                <div id="bands-list" class="overflow-x-auto"></div>
            </div>
        </div>
    `;

    document.getElementById('btn-new-band')?.addEventListener('click', async () => {
        const bandName = prompt('Band Name'); if (!bandName) return;
        const minSalary = prompt('Min Salary (PHP)');
        const maxSalary = prompt('Max Salary (PHP)');
        const effectiveDate = prompt('Effective Date (YYYY-MM-DD)', new Date().toISOString().slice(0,10));
        try {
            const res = await apiFetch('compensation-planning/pay-bands', {
                method: 'POST',
                body: JSON.stringify({ band_name: bandName, min_salary: Number(minSalary||0), max_salary: Number(maxSalary||0), effective_date: effectiveDate, status: 'Active' })
            });
            const newId = res?.id || res?.BandID || null;
            if (newId && activePlanId) {
                try { await fetch(`${LEGACY_API_URL}link_compensation_plan_item.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ plan_id: Number(activePlanId), item_type:'band', item_id: Number(newId) }) }); } catch(e){}
            }
            await loadBands();
        } catch (e) { alert('Create band failed: ' + e.message); }
    });
    document.getElementById('btn-refresh-bands')?.addEventListener('click', loadBands);
    document.getElementById('btn-export-bands')?.addEventListener('click', exportBandsCSV);

    await loadBands();
}

async function loadBands() {
    const container = document.getElementById('bands-list');
    if (!container) return;
    container.innerHTML = `<div class="py-6 text-center text-gray-500">Loading pay bands...</div>`;
    try {
        const result = await apiFetch('compensation-planning/pay-bands');
        const rows = Array.isArray(result) ? result : result?.data || [];
        if (!rows.length) { container.innerHTML = `<div class="py-8 text-center text-gray-500">No pay bands found.</div>`; return; }
        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `
            <thead class="bg-blue-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase">Band</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase">Min Salary</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase">Max Salary</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase">Status</th>
                </tr>
            </thead>`;
        const tbody = table.createTBody();
        tbody.className = 'bg-white divide-y divide-gray-200';
        rows.forEach(b => {
            const tr = tbody.insertRow();
            const td = (text, cls='') => { const c = tr.insertCell(); c.className = `px-4 py-3 text-sm ${cls}`; c.textContent = text ?? ''; return c; };
            td(b.BandName, 'font-medium');
            td(currency(b.MinSalary));
            td(currency(b.MaxSalary));
            td(b.DepartmentName || '');
            const actions = tr.insertCell(); actions.className = 'px-4 py-3 text-sm';
            const status = document.createElement('span'); status.textContent = b.Status || ''; status.className = 'mr-3'; actions.appendChild(status);
            const btnEdit = document.createElement('button'); btnEdit.className = 'text-gray-700 hover:underline mr-2'; btnEdit.textContent = 'Edit'; btnEdit.onclick = () => editBand(b);
            const btnDel = document.createElement('button'); btnDel.className = 'text-red-600 hover:underline'; btnDel.textContent = 'Delete'; btnDel.onclick = () => deleteBand(b.BandID);
            actions.appendChild(btnEdit); actions.appendChild(btnDel);
        });
        container.innerHTML = '';
        container.appendChild(table);
    } catch (e) {
        container.innerHTML = `<div class="py-6 text-center text-red-600">Failed to load bands: ${e.message}</div>`;
    }
}

async function editBand(band) {
    const bandName = prompt('Band Name', band.BandName);
    if (!bandName) return;
    const minSalary = prompt('Min Salary (PHP)', band.MinSalary);
    const maxSalary = prompt('Max Salary (PHP)', band.MaxSalary);
    const effectiveDate = prompt('Effective Date (YYYY-MM-DD)', (band.EffectiveDate || '').slice(0,10));
    const status = prompt('Status (Active/Inactive)', band.Status || 'Active') || 'Active';
    try {
        await apiFetch('compensation-planning/pay-bands', {
            method: 'PUT',
            body: JSON.stringify({ id: band.BandID, band_name: bandName, min_salary: Number(minSalary||0), max_salary: Number(maxSalary||0), effective_date: effectiveDate, status })
        });
        await loadBands();
    } catch (e) { alert('Update band failed: ' + e.message); }
}

async function deleteBand(bandId) {
    if (!confirm('Delete this pay band?')) return;
    try {
        await apiFetch('compensation-planning/pay-bands?id=' + encodeURIComponent(bandId), { method: 'DELETE' });
        await loadBands();
    } catch (e) { alert('Delete band failed: ' + e.message); }
}

// CSV Exports
function exportGradesCSV() {
    const table = document.querySelector('#grades-list table');
    if (!table) { alert('No grades to export'); return; }
    const headers = ['Grade Code','Grade Name','Department','Position Category','Effective','Status'];
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => {
        const tds = tr.querySelectorAll('td');
        return [tds[0]?.textContent?.trim(), tds[1]?.textContent?.trim(), tds[2]?.textContent?.trim(), tds[3]?.textContent?.trim(), tds[4]?.textContent?.trim(), tds[5]?.textContent?.trim()];
    });
    downloadCsv('salary_grades', headers, rows);
}

function exportBandsCSV() {
    const table = document.querySelector('#bands-list table');
    if (!table) { alert('No bands to export'); return; }
    const headers = ['Band Name','Min Salary','Max Salary','Department','Status'];
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => {
        const tds = tr.querySelectorAll('td');
        return [tds[0]?.textContent?.trim(), tds[1]?.textContent?.trim(), tds[2]?.textContent?.trim(), tds[3]?.textContent?.trim(), (tds[4]?.querySelector('span')?.textContent || tds[4]?.textContent || '').trim()];
    });
    downloadCsv('pay_bands', headers, rows);
}

function exportMappingCSV() {
    const table = document.querySelector('#mapping-list table');
    if (!table) { alert('No mapping to export'); return; }
    const headers = ['Employee','Position','Grade','Step','Current Salary','Band Range','Status'];
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => {
        const tds = tr.querySelectorAll('td');
        return [tds[0]?.textContent?.trim(), tds[1]?.textContent?.trim(), tds[2]?.textContent?.trim(), tds[3]?.textContent?.trim(), tds[4]?.textContent?.trim(), tds[5]?.textContent?.trim(), (tds[6]?.querySelector('span')?.textContent || tds[6]?.textContent || '').trim()];
    });
    downloadCsv('employee_mapping', headers, rows);
}

function downloadCsv(prefix, headers, rows) {
    const csv = [headers, ...rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g,'\"')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `${prefix}_${new Date().toISOString().slice(0,10)}.csv`; a.click();
    URL.revokeObjectURL(url);
}

// Employee–Grade Mapping
export async function displayEmployeeMappingSection() {
    const main = setPage('Employee – Grade Mapping');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-emerald-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-emerald-900">Employee Mapping</h3>
                    <p class="text-sm text-emerald-700">Link employees to grades and validate pay equity.</p>
                </div>
                <div class="flex gap-2 items-center">
                    <button id="map-new" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Map New Employee</button>
                    <button id="map-import" class="px-3 py-2 border rounded text-sm">Import CSV</button>
                    <input id="map-import-file" type="file" accept=".csv" class="hidden" />
                    <button id="map-export" class="px-3 py-2 border rounded text-sm">Export CSV</button>
                </div>
            </div>
            <div class="p-6">
                <div id="map-kpis" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Total Mapped</div><div id="map-kpi-total" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">% Within Range</div><div id="map-kpi-within" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Needs Adjustment</div><div id="map-kpi-needs" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Latest Updates</div><div id="map-kpi-latest" class="text-sm font-medium">—</div></div>
                </div>
                <div class="mb-3 flex flex-wrap gap-2 items-end">
                    <input id="map-search" class="border rounded p-2 flex-1" placeholder="Search employee or position" />
                    <select id="map-filter-dept" class="px-3 py-2 border rounded text-sm"><option value="">All Departments</option></select>
                    <select id="map-filter-grade" class="px-3 py-2 border rounded text-sm"><option value="">All Grades</option></select>
                    <select id="map-filter-status" class="px-3 py-2 border rounded text-sm"><option value="">All Status</option><option>Within Band</option><option>Below Band</option><option>Above Band</option><option>Pending Review</option></select>
                    <button id="map-apply" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
                    <button id="map-refresh" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Refresh</button>
                    <button id="map-export2" class="px-3 py-2 border border-emerald-300 text-emerald-700 rounded text-sm bg-white">Export CSV</button>
                </div>
                <div id="mapping-list" class="overflow-x-auto"></div>
            </div>
        </div>`;
    document.getElementById('map-refresh')?.addEventListener('click', loadEmployeeMapping);
    document.getElementById('map-export')?.addEventListener('click', exportMappingCSV);
    document.getElementById('map-export2')?.addEventListener('click', exportMappingCSV);
    document.getElementById('map-new')?.addEventListener('click', openMapNewEmployeeModal);
    document.getElementById('map-import')?.addEventListener('click', ()=>document.getElementById('map-import-file')?.click());
    document.getElementById('map-import-file')?.addEventListener('change', handleBulkMappingImport);
    await loadMappingKpis();
    await populateDepartments('map-filter-dept');
    await populateGrades('map-filter-grade');
    document.getElementById('map-apply')?.addEventListener('click', loadEmployeeMapping);
    await loadEmployeeMapping();
}

async function loadEmployeeMapping() {
    const container = document.getElementById('mapping-list');
    if (!container) return;
    container.innerHTML = `<div class="py-6 text-center text-gray-500">Loading mapping...</div>`;
    try {
        const params = new URLSearchParams();
        const d = document.getElementById('map-filter-dept')?.value; if (d) params.set('department_id', d);
        const g = document.getElementById('map-filter-grade')?.value; if (g) params.set('grade_id', g);
        const st = document.getElementById('map-filter-status')?.value; if (st) params.set('status', st);
        const result = await apiFetch(`compensation-planning/employee-mappings?${params}`);
        const rows = Array.isArray(result) ? result : result?.data || [];
        if (!rows.length) { container.innerHTML = `<div class="py-8 text-center text-gray-500">No mappings found.</div>`; return; }
        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `
            <thead class="bg-emerald-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Position</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Grade</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Step</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Current Salary</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Band Range</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-emerald-700 uppercase">Status</th>
            </tr></thead>`;
        const tbody = table.createTBody();
        tbody.className = 'bg-white divide-y divide-gray-200';
        const role = (window.currentUser?.role_name || '').toLowerCase();
        const canApprove = ['hr manager','hr chief','admin head','system admin','admin'].includes(role);
        rows.forEach(r => {
            const tr = tbody.insertRow();
            const td = (t, cls='') => { const c = tr.insertCell(); c.className = `px-4 py-3 text-sm ${cls}`; c.textContent = t ?? ''; return c; };
            td(r.EmployeeName || '');
            td(r.JobTitle || '');
            td(`${r.GradeCode || ''} ${r.GradeName || ''}`.trim());
            td(r.StepNumber ? `Step ${r.StepNumber}` : '');
            td(currency(r.CurrentSalary), 'text-blue-700 font-medium');
            td(`${currency(r.GradeMinRate)} - ${currency(r.GradeMaxRate)}`);
            const statusText = r.Status || calculateBandStatus(r.CurrentSalary, r.GradeMinRate, r.GradeMaxRate);
            const statusCell = tr.insertCell(); statusCell.className = 'px-4 py-3 text-sm';
            const statusSpan = document.createElement('span'); statusSpan.textContent = statusText; statusSpan.className = 'mr-3'; statusCell.appendChild(statusSpan);
            const btnReclass = document.createElement('button'); btnReclass.className = 'text-emerald-700 hover:underline mr-2'; btnReclass.textContent = 'Update Mapping'; btnReclass.onclick = () => openUpdateMappingModal(r);
            statusCell.appendChild(btnReclass);
            if (canApprove && (r.Status==='Pending Review' || r.Status==='Below Band' || r.Status==='Above Band')) {
                const approve = document.createElement('button'); approve.className='text-green-700 hover:underline'; approve.textContent='Approve'; approve.onclick=()=>approveMapping(r.MappingID, r.EmployeeID); statusCell.appendChild(approve);
            }
        });
        container.innerHTML = '';
        container.appendChild(table);
    } catch (e) {
        container.innerHTML = `<div class="py-6 text-center text-red-600">Failed to load mapping: ${e.message}</div>`;
    }
}

function calculateBandStatus(current, min, max) {
    const c = Number(current||0), mi = Number(min||0), ma = Number(max||0);
    if (c < mi) return 'Below Band';
    if (c > ma) return 'Above Band';
    return 'Within Band';
}

async function reclassifyEmployee(row) {
    try {
        const gradeId = parseInt(prompt('New Grade ID', row.GradeID || ''));
        if (!gradeId) return;
        const stepId = parseInt(prompt('New Step ID', row.StepID || ''));
        if (!stepId) return;
        const effective = prompt('Effective Date (YYYY-MM-DD)', new Date().toISOString().slice(0,10));
        const payload = {
            employee_id: row.EmployeeID,
            grade_id: gradeId,
            step_id: stepId,
            current_salary: Number(row.CurrentSalary || 0),
            grade_min_rate: Number(row.GradeMinRate || 0),
            grade_max_rate: Number(row.GradeMaxRate || 0),
            status: 'Pending Review',
            effective_date: effective,
            end_date: null,
            notes: 'Grade reclassification proposal',
            end_previous: true,
            created_by: window.currentUser?.employee_id || 1
        };
        await apiFetch('compensation-planning/employee-mappings', { method: 'POST', body: JSON.stringify(payload) });
        alert('Reclassification proposed.');
        await loadEmployeeMapping();
    } catch (e) { alert('Reclassification failed: ' + e.message); }
}

async function loadMappingKpis() {
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/employee-mapping-overview`, { credentials:'include' });
        if (!res.ok) return; const payload = await res.json(); const d = payload?.data || {};
        const set=(id,v)=>{ const el=document.getElementById(id); if (el) el.textContent=v; };
        set('map-kpi-total', d.total_mapped ?? '—');
        set('map-kpi-within', (typeof d.percent_within==='number'? `${d.percent_within}%` : '—'));
        set('map-kpi-needs', d.needs_adjustment ?? '—');
        const latest = Array.isArray(d.recent_updates)? d.recent_updates.map(x=>`${x.EmployeeName||''} → ${x.GradeCode||''}`).slice(0,3).join(' | ') : '—';
        set('map-kpi-latest', latest || '—');
    } catch(e){}
}

function openMapNewEmployeeModal() {
    const existing = document.getElementById('map-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='map-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center"><h4 class="font-semibold">Map New Employee</h4><button id="map-close" class="text-gray-500">✕</button></div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div><label class="text-sm block mb-1">Employee</label><select id="map-emp" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Current Salary</label><input id="map-salary" class="border rounded p-2 w-full" disabled /></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div><label class="text-sm block mb-1">Grade</label><select id="map-grade" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Step</label><select id="map-step" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Effective Date</label><input id="map-eff" type="date" class="border rounded p-2 w-full" /></div>
                </div>
                <div class="flex justify-end gap-2"><button id="map-save" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Submit for Review</button><button id="map-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button></div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('map-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('map-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('map-save')?.addEventListener('click', submitMapNewEmployee);
    populateEmployeesForAdjustment('map-emp');
    populateGrades('map-grade');
    document.getElementById('map-grade')?.addEventListener('change', async (ev)=>{ await populateSteps('map-step', ev.target.value); });
    document.getElementById('map-emp')?.addEventListener('change', ev=>{
        const opt = ev.target.selectedOptions?.[0]; if (!opt) return; const data = JSON.parse(opt.getAttribute('data-json')||'{}');
        const oldSalary = Number(data.BaseSalary || data.base_salary || 0);
        const oldEl = document.getElementById('map-salary'); if (oldEl) oldEl.value = currency(oldSalary);
    });
}

async function submitMapNewEmployee() {
    try {
        const empSel = document.getElementById('map-emp'); const opt = empSel?.selectedOptions?.[0]; if (!opt) { alert('Select employee'); return; }
        const empJson = JSON.parse(opt.getAttribute('data-json')||'{}');
        // Auto-validate against grade range (best-effort): fetch steps/grade or assume step base if selected
        let warn='';
        try {
            const gid = document.getElementById('map-grade')?.value;
            if (gid) {
                const res = await fetch(`${REST_API_URL}compensation-planning/salary-grades-steps?grade_id=${encodeURIComponent(gid)}`, { credentials:'include' });
                if (res.ok) {
                    const list = await res.json(); const steps = Array.isArray(list)? list:(list?.data||[]);
                    const min = Math.min(...steps.map(s=>Number(s.MinRate||s.BaseRate||Infinity)));
                    const max = Math.max(...steps.map(s=>Number(s.MaxRate||s.BaseRate||0)));
                    const sal = parseFloat((document.getElementById('map-salary')?.value||'').replace(/[^0-9.]/g,''))||0;
                    if (sal && (sal<min || sal>max)) { warn = `Warning: Current salary ${currency(sal)} is outside grade range (${currency(min)} - ${currency(max)}). Submit anyway?`; }
                }
            }
        } catch(e){}
        if (warn) { const ok = confirm(warn); if (!ok) return; }

        const payload = {
            employee_id: Number(empSel.value),
            grade_id: document.getElementById('map-grade')?.value || null,
            step_id: document.getElementById('map-step')?.value || null,
            current_salary: parseFloat((document.getElementById('map-salary')?.value||'').replace(/[^0-9.]/g,''))||0,
            grade_min_rate: 0,
            grade_max_rate: 0,
            status: 'Pending Review',
            effective_date: document.getElementById('map-eff')?.value || new Date().toISOString().slice(0,10),
            end_date: null,
            notes: 'New mapping submission',
            created_by: window.currentUser?.employee_id || null,
            end_previous: true
        };
        const res = await fetch(`${REST_API_URL}compensation-planning/employee-mappings`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
        if (!res.ok) throw new Error('Failed to submit mapping');
        document.getElementById('map-modal')?.remove();
        await loadEmployeeMapping();
        alert('Mapping submitted for review');
    } catch(e) { alert('Save failed: ' + e.message); }
}

function openUpdateMappingModal(row) {
    const existing = document.getElementById('upd-map-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='upd-map-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center"><h4 class="font-semibold">Update Mapping</h4><button id="upd-map-close" class="text-gray-500">✕</button></div>
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div><label class="text-sm block mb-1">Employee</label><input class="border rounded p-2 w-full" value="${row.EmployeeName||''}" disabled /></div>
                    <div><label class="text-sm block mb-1">Current Salary</label><input id="upd-map-salary" class="border rounded p-2 w-full" value="${currency(row.CurrentSalary)}" disabled /></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div><label class="text-sm block mb-1">Grade</label><select id="upd-map-grade" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Step</label><select id="upd-map-step" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Effective Date</label><input id="upd-map-eff" type="date" class="border rounded p-2 w-full" value="${(row.EffectiveDate||'').slice(0,10)}" /></div>
                </div>
                <div class="flex justify-end gap-2"><button id="upd-map-save" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Submit for Review</button><button id="upd-map-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button></div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('upd-map-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('upd-map-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('upd-map-save')?.addEventListener('click', async ()=>{
        try {
            // Auto-validate range
            let warn='';
            try {
                const gid = document.getElementById('upd-map-grade')?.value;
                if (gid) {
                    const res = await fetch(`${REST_API_URL}compensation-planning/salary-grades-steps?grade_id=${encodeURIComponent(gid)}`, { credentials:'include' });
                    if (res.ok) {
                        const list = await res.json(); const steps = Array.isArray(list)? list:(list?.data||[]);
                        const min = Math.min(...steps.map(s=>Number(s.MinRate||s.BaseRate||Infinity)));
                        const max = Math.max(...steps.map(s=>Number(s.MaxRate||s.BaseRate||0)));
                        const sal = Number(row.CurrentSalary||0);
                        if (sal && (sal<min || sal>max)) { warn = `Warning: Current salary ${currency(sal)} is outside grade range (${currency(min)} - ${currency(max)}). Submit anyway?`; }
                    }
                }
            } catch(e){}
            if (warn) { const ok = confirm(warn); if (!ok) return; }
            const payload = {
                employee_id: Number(row.EmployeeID),
                grade_id: document.getElementById('upd-map-grade')?.value || null,
                step_id: document.getElementById('upd-map-step')?.value || null,
                current_salary: Number(row.CurrentSalary||0),
                grade_min_rate: Number(row.GradeMinRate||0),
                grade_max_rate: Number(row.GradeMaxRate||0),
                status: 'Pending Review',
                effective_date: document.getElementById('upd-map-eff')?.value || new Date().toISOString().slice(0,10),
                end_date: null,
                notes: 'Mapping update',
                created_by: window.currentUser?.employee_id || null,
                end_previous: true
            };
            const res = await fetch(`${REST_API_URL}compensation-planning/employee-mappings`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
            if (!res.ok) throw new Error('Failed to submit mapping update');
            document.getElementById('upd-map-modal')?.remove();
            await loadEmployeeMapping();
            alert('Mapping update submitted for review');
        } catch(e) { alert('Save failed: ' + e.message); }
    });
    populateGrades('upd-map-grade');
    document.getElementById('upd-map-grade')?.addEventListener('change', async (ev)=>{ await populateSteps('upd-map-step', ev.target.value); });
}

async function approveMapping(mappingId, employeeId) {
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/employee-mappings-approve`, { method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ id: mappingId, user_id: window.currentUser?.employee_id || null }) });
        if (!res.ok) throw new Error('Approve failed');
        // HR Core sync best-effort
        try { await fetch(`${REST_API_URL}integrations/hrcore/sync`, { method:'POST', credentials:'include', body: new URLSearchParams({ employee_id: employeeId }) }); } catch(e){}
        // Analytics refresh best-effort
        try { await apiFetch('integrations/analytics/compensation'); } catch(e){}
        await loadEmployeeMapping();
        alert('Mapping approved');
    } catch(e) { alert('Approve failed: ' + e.message); }
}

async function handleBulkMappingImport(ev) {
    try {
        const file = ev.target.files?.[0]; if (!file) return;
        const text = await file.text();
        // Expected headers: employee_id,grade_id,step_id,effective_date,current_salary(optional)
        const rows = text.split(/\r?\n/).filter(Boolean);
        const header = rows.shift().split(',').map(h=>h.trim().toLowerCase());
        const idx = (name)=> header.indexOf(name);
        const required = ['employee_id','grade_id','step_id','effective_date'];
        for (const r of required) { if (idx(r)===-1) { alert(`CSV missing column: ${r}`); return; } }
        let ok=0, fail=0;
        for (const line of rows) {
            const parts = line.split(',');
            const payload = {
                employee_id: Number(parts[idx('employee_id')]),
                grade_id: Number(parts[idx('grade_id')]),
                step_id: Number(parts[idx('step_id')]),
                current_salary: idx('current_salary')>-1 ? Number(parts[idx('current_salary')]) : 0,
                grade_min_rate: 0,
                grade_max_rate: 0,
                status: 'Pending Review',
                effective_date: parts[idx('effective_date')],
                end_date: null,
                notes: 'Bulk mapping import',
                created_by: window.currentUser?.employee_id || null,
                end_previous: true
            };
            try {
                const res = await fetch(`${REST_API_URL}compensation-planning/employee-mappings`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
                if (!res.ok) throw new Error('HTTP '+res.status);
                ok++;
            } catch(e) { fail++; }
        }
        await loadEmployeeMapping();
        alert(`Import completed. Success: ${ok}, Failed: ${fail}`);
        ev.target.value = '';
    } catch(e) { alert('Import failed: ' + e.message); }
}

// Pay Adjustment Workflows
export async function displayWorkflowsSection() {
    const main = setPage('Pay Adjustment Workflows');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-purple-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-purple-900">Workflows</h3>
                    <p class="text-sm text-purple-700">Draft → Review → Approve → Implement</p>
                </div>
                <div class="space-x-2">
                    <button id="wf-new" class="px-3 py-2 bg-purple-600 text-white rounded text-sm">New Workflow</button>
                    <button id="wf-refresh" class="px-3 py-2 border border-purple-300 text-purple-700 rounded text-sm bg-white">Refresh</button>
                </div>
            </div>
            <div class="p-6">
                <div id="wf-list" class="overflow-x-auto"></div>
            </div>
        </div>`;

    document.getElementById('wf-new')?.addEventListener('click', createWorkflowPrompt);
    document.getElementById('wf-refresh')?.addEventListener('click', loadWorkflows);
    await loadWorkflows();
}

async function loadWorkflows() {
    const container = document.getElementById('wf-list');
    if (!container) return;
    container.innerHTML = `<div class="py-6 text-center text-gray-500">Loading workflows...</div>`;
    try {
        const result = await apiFetch('compensation-planning/workflows');
        const rows = Array.isArray(result) ? result : result?.data || [];
        if (!rows.length) { container.innerHTML = `<div class="py-8 text-center text-gray-500">No workflows found.</div>`; return; }
        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `<thead class="bg-purple-50"><tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Name</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Value</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Effective</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Impact</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-purple-700 uppercase">Actions</th>
        </tr></thead>`;
        const tbody = table.createTBody();
        tbody.className = 'bg-white divide-y divide-gray-200';
        rows.forEach(w => {
            const tr = tbody.insertRow();
            const td = (t, cls='') => { const c = tr.insertCell(); c.className = `px-4 py-3 text-sm ${cls}`; c.textContent = t ?? ''; return c; };
            td(w.WorkflowName || '');
            td(w.AdjustmentType || '');
            td(String(w.AdjustmentValue ?? ''));
            td(w.EffectiveDate || '');
            td(w.Status || '');
            td(w.TotalImpact != null ? currency(w.TotalImpact) : '—');
            const actions = tr.insertCell();
            actions.className = 'px-4 py-3 text-sm';
            const btnDetails = document.createElement('button'); btnDetails.className = 'text-purple-700 hover:underline mr-2'; btnDetails.textContent = 'Generate Details'; btnDetails.onclick = () => generateWorkflowDetails(w.WorkflowID);
            const btnPreview = document.createElement('button'); btnPreview.className = 'text-purple-700 hover:underline mr-2'; btnPreview.textContent = 'Preview Impact'; btnPreview.onclick = () => previewWorkflowImpact(w.WorkflowID);
            const btnApprove = document.createElement('button'); btnApprove.className = 'text-green-700 hover:underline mr-2'; btnApprove.textContent = 'Approve'; btnApprove.onclick = () => approveWorkflow(w.WorkflowID);
            const btnImplement = document.createElement('button'); btnImplement.className = 'text-emerald-700 hover:underline'; btnImplement.textContent = 'Implement'; btnImplement.onclick = () => implementWorkflow(w.WorkflowID);
            actions.appendChild(btnDetails); actions.appendChild(btnPreview); actions.appendChild(btnApprove); actions.appendChild(btnImplement);
        });
        container.innerHTML = '';
        container.appendChild(table);
    } catch (e) {
        container.innerHTML = `<div class="py-6 text-center text-red-600">Failed to load workflows: ${e.message}</div>`;
    }
}

async function createWorkflowPrompt() {
    const name = prompt('Workflow Name'); if (!name) return;
    const type = prompt('Adjustment Type (Percentage | Fixed Amount)', 'Percentage') || 'Percentage';
    const value = prompt('Adjustment Value (number)'); if (value == null) return;
    const effective = prompt('Effective Date (YYYY-MM-DD)', new Date().toISOString().slice(0,10));
    try {
        const res = await apiFetch('compensation-planning/workflows', {
            method: 'POST',
            body: JSON.stringify({ workflow_name: name, adjustment_type: type, adjustment_value: Number(value||0), effective_date: effective, status: 'Draft', created_by: window.currentUser?.employee_id || 1 })
        });
        const newId = res?.id || res?.WorkflowID || null;
        if (newId && activePlanId) {
            try { await fetch(`${LEGACY_API_URL}link_compensation_plan_item.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ plan_id: Number(activePlanId), item_type:'workflow', item_id: Number(newId) }) }); } catch(e){}
        }
        await loadWorkflows();
    } catch (e) { alert('Create workflow failed: ' + e.message); }
}

async function previewWorkflowImpact(workflowId) {
    try {
        const result = await apiFetch('compensation-planning/workflows-calculate-impact', { method: 'POST', body: JSON.stringify({ workflow_id: workflowId }) });
        alert(`Estimated total payroll impact: ${currency(result.total_impact || 0)}\nAffected employees: ${result.affected_employees || 0}`);
    } catch (e) { alert('Preview failed: ' + e.message); }
}

async function approveWorkflow(workflowId) {
    try {
        await apiFetch('compensation-planning/workflows-approve', { method: 'PUT', body: JSON.stringify({ id: workflowId, approved_by: window.currentUser?.employee_id || 1 }) });
        await loadWorkflows();
    } catch (e) { alert('Approve failed: ' + e.message); }
}

async function implementWorkflow(workflowId) {
    try {
        // Implement in compensation module
        await apiFetch('compensation-planning/workflows-implement', { method: 'PUT', body: JSON.stringify({ id: workflowId, implemented_by: window.currentUser?.employee_id || 1 }) });
        // Ensure details exist; if not, generate based on current workflow settings
        let details = await apiFetch('compensation-planning/workflow-details?workflow_id=' + encodeURIComponent(workflowId));
        if (!Array.isArray(details) || details.length === 0) {
            await generateWorkflowDetails(workflowId);
            details = await apiFetch('compensation-planning/workflow-details?workflow_id=' + encodeURIComponent(workflowId));
        }
        const changes = (details || []).map(d => ({ employee_id: d.EmployeeID, new_salary: d.NewSalary, adjustment_amount: d.AdjustmentAmount }));
        await apiFetch('integrations/payroll/update', { method: 'POST', body: JSON.stringify({ changes, effective_date: new Date().toISOString().slice(0,10), reason: `Workflow ${workflowId} Implementation` }) });
        // Notify analytics to refresh salary insights (best-effort)
        try { await apiFetch('integrations/analytics/compensation'); } catch(e) {}
        alert('Workflow implemented and pushed to Payroll.');
        await loadWorkflows();
    } catch (e) { alert('Implement failed: ' + e.message); }
}

async function generateWorkflowDetails(workflowId) {
    try {
        // find workflow data
        const list = await apiFetch('compensation-planning/workflows');
        const workflows = Array.isArray(list) ? list : list?.data || [];
        const wf = workflows.find(w => String(w.WorkflowID) === String(workflowId));
        if (!wf) throw new Error('Workflow not found');
        // compute impacted employees
        const impact = await apiFetch('compensation-planning/workflows-calculate-impact', { method: 'POST', body: JSON.stringify({ workflow_id: workflowId }) });
        const employees = impact?.employees || [];
        // create details with chosen parameters
        await apiFetch('compensation-planning/workflows-create-details', {
            method: 'POST',
            body: JSON.stringify({ workflow_id: workflowId, employees, adjustment_value: Number(wf.AdjustmentValue||0), adjustment_type: wf.AdjustmentType || 'Percentage' })
        });
        alert('Workflow details generated.');
    } catch (e) { alert('Generate details failed: ' + e.message); }
}

// Simulations
export async function displaySimulationToolsSection() {
    const main = setPage('Compensation Simulation Tools');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-orange-50">
                <h3 class="text-xl font-semibold text-orange-900">Simulation Tools</h3>
                <p class="text-sm text-orange-700">Forecast changes and export results.</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input id="sim-grade-ids" class="border rounded p-2" placeholder="Grade IDs (comma-separated)" />
                    <select id="sim-type" class="border rounded p-2">
                        <option>Grade Adjustment</option>
                        <option>Department Adjustment</option>
                        <option>Position Adjustment</option>
                    </select>
                    <input id="sim-value" class="border rounded p-2" placeholder="Adjustment Value (e.g., 5 for 5%)" />
                </div>
                <div class="flex gap-2">
                    <button id="sim-run" class="px-3 py-2 bg-orange-600 text-white rounded text-sm">Run Simulation</button>
                    <button id="sim-export" class="px-3 py-2 border rounded text-sm">Export CSV</button>
                    <button id="sim-finance" class="px-3 py-2 border rounded text-sm">Send to Finance</button>
                </div>
                <div id="sim-output" class="overflow-x-auto"></div>
            </div>
        </div>`;

    document.getElementById('sim-run')?.addEventListener('click', runSimulation);
    document.getElementById('sim-export')?.addEventListener('click', exportSimulation);
    document.getElementById('sim-finance')?.addEventListener('click', sendSimulationToFinance);
}

let lastSimulationResult = null;
async function runSimulation() {
    const type = document.getElementById('sim-type')?.value || 'Grade Adjustment';
    const value = Number(document.getElementById('sim-value')?.value || 0);
    const grades = (document.getElementById('sim-grade-ids')?.value || '').split(',').map(s => parseInt(s.trim())).filter(Boolean);
    const params = { simulation_type: type, parameters: { grades, adjustment_value: value } };
    const out = document.getElementById('sim-output'); if (out) out.innerHTML = '<div class="py-4 text-center text-gray-500">Running simulation...</div>';
    try {
        const result = await apiFetch('compensation-planning/simulations-run', { method: 'POST', body: JSON.stringify(params) });
        lastSimulationResult = result;
        // Best-effort link simulation run to active plan for reporting context
        try {
            if (activePlanId) {
                await fetch(`${LEGACY_API_URL}link_compensation_plan_item.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ plan_id: Number(activePlanId), item_type:'simulation', item_id: Number(Date.now()%2147483647), metadata: { parameters: params, totals: { total_impact: result?.total_impact, affected: result?.affected_employees } } }) });
            }
        } catch(e){}
        renderSimulation(result);
    } catch (e) { if (out) out.innerHTML = `<div class="py-4 text-center text-red-600">Simulation failed: ${e.message}</div>`; }
}

function renderSimulation(result) {
    const out = document.getElementById('sim-output');
    if (!out) return;
    const employees = result?.employees || [];
    const totalImpact = result?.total_impact || 0;
    const affected = result?.affected_employees || employees.length;
    out.innerHTML = `
        <div class="mb-3 text-sm text-gray-700">Estimated total payroll impact: <strong>${currency(totalImpact)}</strong> • Affected employees: <strong>${affected}</strong></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Employee</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Current Salary</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">New Salary</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Impact</th>
                </tr></thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${employees.map(e => `
                        <tr>
                            <td class="px-3 py-2 text-sm">${(e.FirstName||'') + ' ' + (e.LastName||'')}</td>
                            <td class="px-3 py-2 text-sm">${currency(e.BaseSalary)}</td>
                            <td class="px-3 py-2 text-sm">${currency(e.NewSalary || 0)}</td>
                            <td class="px-3 py-2 text-sm">${currency((e.NewSalary||0) - (e.BaseSalary||0))}</td>
                        </tr>`).join('')}
                </tbody>
            </table>
        </div>`;
}

function exportSimulation() {
    const result = lastSimulationResult;
    if (!result) { alert('Run a simulation first.'); return; }
    const employees = result.employees || [];
    const headers = ['Employee','Current Salary','New Salary','Impact'];
    const rows = employees.map(e => [
        `${(e.FirstName||'')} ${(e.LastName||'')}`.trim(),
        Number(e.BaseSalary||0),
        Number(e.NewSalary||0),
        Number((e.NewSalary||0) - (e.BaseSalary||0))
    ]);
    const csv = [headers, ...rows].map(r => r.map(v => typeof v==='number'? v: `"${String(v).replace(/"/g,'\"')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `simulation_${new Date().toISOString().slice(0,10)}.csv`; a.click();
    URL.revokeObjectURL(url);
}

async function sendSimulationToFinance() {
    if (!lastSimulationResult) { alert('Run a simulation first.'); return; }
    try {
        const tag = prompt('Version tag for Finance (e.g., FY2025 Adjustment Set 1)', `FY${new Date().getFullYear()} Adjustment`);
        const payload = { impact_data: lastSimulationResult, report_type: 'salary_adjustment', version_tag: tag };
        const resp = await apiFetch('integrations/finance/budget-impact', { method: 'POST', body: JSON.stringify(payload) });
        alert(resp?.message || 'Sent to Finance');
    } catch (e) { alert('Finance sync failed: ' + e.message); }
}

// Quick Salary Adjustments (entry point kept for backward compatibility)
export async function displaySalaryAdjustmentsSection() {
    const main = setPage('Salary Adjustments');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-pink-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-pink-900">Salary Adjustment Dashboard</h3>
                    <p class="text-sm text-pink-700">Manage, review, approve, and implement salary changes with full Payroll integration.</p>
                </div>
                <div class="flex gap-2">
                    <button id="sa-new" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">+ New Adjustment</button>
                    <button id="sa-export" class="px-3 py-2 border rounded text-sm">Export CSV</button>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div id="sa-kpis" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Pending Adjustments</div><div id="kpi-pending" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Approved (This Month)</div><div id="kpi-approved-month" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Total Increase Amount</div><div id="kpi-total-increase" class="text-2xl font-semibold">—</div></div>
                    <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Avg % Raise / Dept</div><div id="kpi-avg-raise" class="text-lg font-semibold">—</div></div>
                </div>
                <div class="flex flex-wrap gap-3 items-end">
                    <select id="sa-filter-dept" class="px-3 py-2 border rounded text-sm"><option value="">All Departments</option></select>
                    <select id="sa-filter-reason" class="px-3 py-2 border rounded text-sm"><option value="">All Reasons</option></select>
                    <select id="sa-filter-status" class="px-3 py-2 border rounded text-sm"><option value="">All Status</option><option>Pending Review</option><option>Approved</option><option>Implemented</option><option>Rejected</option></select>
                    <input id="sa-filter-start" type="date" class="px-3 py-2 border rounded text-sm" />
                    <input id="sa-filter-end" type="date" class="px-3 py-2 border rounded text-sm" />
                    <button id="sa-apply" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
                </div>
                <div id="sa-list" class="overflow-x-auto"></div>
            </div>
        </div>`;

    document.getElementById('sa-new')?.addEventListener('click', openSalaryAdjustmentModal);
    document.getElementById('sa-export')?.addEventListener('click', exportAdjustmentsCSV);
    document.getElementById('sa-apply')?.addEventListener('click', loadSalaryAdjustments);
    await populateDepartments('sa-filter-dept');
    await populateReasons('sa-filter-reason');
    await loadSalaryAdjustments();
}

async function populateDepartments(selectId) {
    try {
        const res = await fetch(`${REST_API_URL}departments`, { credentials:'include' });
        if (!res.ok) return;
        const payload = await res.json();
        const sel = document.getElementById(selectId);
        if (sel && payload?.success && Array.isArray(payload.data)) {
            payload.data.forEach(d=>{ const o=document.createElement('option'); o.value=d.DepartmentID; o.textContent=d.DepartmentName; sel.appendChild(o); });
        }
    } catch(e){}
}

async function populateReasons(selectId) {
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/adjustment-reasons`, { credentials:'include' });
        if (!res.ok) return;
        const payload = await res.json();
        const sel = document.getElementById(selectId);
        if (sel && payload?.success && Array.isArray(payload.data)) {
            payload.data.forEach(r=>{ const o=document.createElement('option'); o.value=r.ReasonID; o.textContent=r.ReasonLabel; sel.appendChild(o); });
        }
    } catch(e){}
}

async function loadSalaryAdjustments() {
    const container = document.getElementById('sa-list'); if (!container) return;
    container.innerHTML = '<div class="py-6 text-center text-gray-500">Loading adjustments...</div>';
    const params = new URLSearchParams();
    const d = document.getElementById('sa-filter-dept')?.value; if (d) params.set('department_id', d);
    const r = document.getElementById('sa-filter-reason')?.value; if (r) params.set('reason_id', r);
    const s = document.getElementById('sa-filter-status')?.value; if (s) params.set('status', s);
    const sd = document.getElementById('sa-filter-start')?.value; if (sd) params.set('start_date', sd);
    const ed = document.getElementById('sa-filter-end')?.value; if (ed) params.set('end_date', ed);
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/salary-adjustments?${params}`, { credentials:'include' });
        if (!res.ok) throw new Error('Failed to load adjustments');
        const payload = await res.json();
        const list = payload?.data || payload || [];
        renderSalaryAdjustments(list);
        updateSalaryAdjustmentKPIs(list);
    } catch(e) { container.innerHTML = `<div class="py-6 text-center text-red-600">${e.message}</div>`; }
}

function updateSalaryAdjustmentKPIs(list) {
    const set = (id,val) => { const el=document.getElementById(id); if (el) el.textContent=val; };
    const pending = list.filter(x=>x.Status==='Pending Review').length;
    const now = new Date(); const ym = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
    const approvedThisMonth = list.filter(x=>x.Status==='Approved' || x.Status==='Implemented').filter(x=>String(x.EffectiveDate||'').startsWith(ym)).length;
    const totalIncrease = list.reduce((sum,x)=> sum + Math.max(0, (parseFloat(x.NewSalary||0) - parseFloat(x.OldSalary||0))), 0);
    const deptMap = new Map();
    list.filter(x=>x.Status==='Approved' || x.Status==='Implemented').forEach(x=>{
        const oldS = parseFloat(x.OldSalary||0); const diff = parseFloat(x.NewSalary||0) - oldS; const pct = oldS>0? (diff/oldS*100):0;
        const key = x.DepartmentName || 'Dept';
        if (!deptMap.has(key)) deptMap.set(key, []);
        deptMap.get(key).push(pct);
    });
    const avgPerDept = Array.from(deptMap.entries()).map(([k,arr])=> ({k,avg: (arr.reduce((a,b)=>a+b,0)/(arr.length||1))}));
    const avgText = avgPerDept.length? avgPerDept.map(d=>`${d.k}: ${d.avg.toFixed(1)}%`).slice(0,3).join(' | ') : '—';
    set('kpi-pending', pending);
    set('kpi-approved-month', approvedThisMonth);
    set('kpi-total-increase', currency(totalIncrease));
    set('kpi-avg-raise', avgText);
}

function renderSalaryAdjustments(list) {
    const container = document.getElementById('sa-list'); if (!container) return;
    if (!Array.isArray(list) || !list.length) { container.innerHTML = '<div class="py-8 text-center text-gray-500">No salary adjustments found.</div>'; return; }
    const table = document.createElement('table'); table.className='min-w-full divide-y divide-gray-200';
    table.innerHTML = `
        <thead class="bg-gray-50"><tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Employee</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Department</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Old Salary</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">New Salary</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Change (%)</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Effective Date</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
        </tr></thead>`;
    const tbody = table.createTBody(); tbody.className='bg-white divide-y divide-gray-200';
    const role = (window.currentUser?.role_name || '').toLowerCase();
    const canInitiate = ['admin','hr_chief','department head'].includes(role);
    const canReviewApprove = ['hr manager'].includes(role);
    const canApproveImplement = ['hr chief','admin head','system admin','admin'].includes(role);
    list.forEach(a=>{
        const tr = tbody.insertRow();
        const td = (t, cls='')=>{ const c=tr.insertCell(); c.className=`px-4 py-3 text-sm ${cls}`; c.textContent=t??''; return c; };
        td(a.EmployeeName || '');
        td(a.DepartmentName || '');
        td(currency(a.OldSalary));
        td(currency(a.NewSalary));
        const oldS=parseFloat(a.OldSalary||0), diff=parseFloat(a.NewSalary||0)-oldS, pct= oldS>0? (diff/oldS*100):0;
        td(`${pct.toFixed(1)}%`, pct>=0?'text-green-700':'text-red-700');
        td(a.Status || '');
        td(a.EffectiveDate || '');
        const actions = tr.insertCell(); actions.className='px-4 py-3 text-sm';
        // Action buttons based on status
        if (a.Status==='Pending Review' && canReviewApprove) {
            const review = document.createElement('button'); review.className='text-gray-700 hover:underline mr-2'; review.textContent='Review'; review.onclick=()=>setAdjustmentStatus(a.AdjustmentID,'Pending Review'); actions.appendChild(review);
            const approve = document.createElement('button'); approve.className='text-green-700 hover:underline'; approve.textContent='Approve'; approve.onclick=()=>setAdjustmentStatus(a.AdjustmentID,'Approved'); actions.appendChild(approve);
        } else if (a.Status==='Approved' && canApproveImplement) {
            const implement = document.createElement('button'); implement.className='text-emerald-700 hover:underline'; implement.textContent='Implement'; implement.onclick=()=>implementAdjustment(a); actions.appendChild(implement);
        }
    });
    container.innerHTML=''; container.appendChild(table);
}

async function setAdjustmentStatus(id, status) {
    try {
        await fetch(`${REST_API_URL}compensation-planning/salary-adjustments-status`, { method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ id, status, user_id: window.currentUser?.employee_id||null }) });
        await loadSalaryAdjustments();
    } catch(e) { alert('Status update failed: '+e.message); }
}

async function implementAdjustment(adj) {
    try {
        // Baseline verification
        let baselineOk = true; let retroTag = '';
        try {
            const res = await fetch(`${REST_API_URL}salaries/${adj.EmployeeID}/summary`, { credentials:'include' });
            if (res.ok) {
                const payload = await res.json(); const cur = payload?.data?.BaseSalary || payload?.data?.base_salary || null;
                if (cur !== null && Math.abs(Number(cur) - Number(adj.OldSalary)) > 0.009) {
                    baselineOk = confirm(`Warning: Current payroll base (${currency(cur)}) differs from recorded Old Salary (${currency(adj.OldSalary)}). Proceed?`);
                }
            }
        } catch(e){}
        if (!baselineOk) return;
        // 1) Set status Implemented
        await setAdjustmentStatus(adj.AdjustmentID, 'Implemented');
        // 2) Push to Payroll
        const todayStr = new Date().toISOString().slice(0,10);
        if (adj.EffectiveDate && adj.EffectiveDate < todayStr) retroTag = ' [Retroactive]';
        const changes = [{ employee_id: adj.EmployeeID, new_salary: adj.NewSalary }];
        await apiFetch('integrations/payroll/update', { method:'POST', body: JSON.stringify({ changes, effective_date: adj.EffectiveDate, reason: `Salary Adjustment (${adj.ReasonLabel||'Reason'})${retroTag}` }) });
        // 3) HR Core sync (best-effort)
        try { await fetch(`${REST_API_URL}integrations/hrcore/sync`, { method:'POST', credentials:'include', body: new URLSearchParams({ employee_id: adj.EmployeeID }) }); } catch(e){}
        // 4) Analytics refresh (best-effort)
        try { await apiFetch('integrations/analytics/compensation'); } catch(e){}
        // 5) Finance optional (best-effort)
        try { await apiFetch('integrations/finance/budget-impact', { method:'POST', body: JSON.stringify({ impact_data: { employee_id: adj.EmployeeID, old: adj.OldSalary, new: adj.NewSalary }, report_type: 'salary_adjustment' }) }); } catch(e){}
        await loadSalaryAdjustments();
        alert('Adjustment implemented and pushed to Payroll.');
    } catch(e) { alert('Implement failed: '+e.message); }
}

function exportAdjustmentsCSV() {
    const table = document.querySelector('#sa-list table'); if (!table) { alert('No data to export'); return; }
    const headers = ['Employee','Department','Old Salary','New Salary','Change %','Status','Effective Date'];
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr=>{
        const t = tr.querySelectorAll('td');
        return [t[0]?.textContent?.trim(), t[1]?.textContent?.trim(), t[2]?.textContent?.trim(), t[3]?.textContent?.trim(), t[4]?.textContent?.trim(), t[5]?.textContent?.trim(), t[6]?.textContent?.trim()];
    });
    downloadCsv('salary_adjustments', headers, rows);
}

async function openSalaryAdjustmentModal() {
    const existing = document.getElementById('sa-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='sa-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center"><h4 class="font-semibold">New Salary Adjustment</h4><button id="sa-close" class="text-gray-500">✕</button></div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm block mb-1">Employee</label>
                        <select id="sa-employee" class="border rounded p-2 w-full"></select>
                    </div>
                    <div>
                        <label class="text-sm block mb-1">Reason</label>
                        <select id="sa-reason" class="border rounded p-2 w-full"></select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div><label class="text-sm block mb-1">Old Salary</label><input id="sa-old" class="border rounded p-2 w-full" disabled /></div>
                    <div><label class="text-sm block mb-1">New Salary</label><input id="sa-new" class="border rounded p-2 w-full" /></div>
                    <div><label class="text-sm block mb-1">Change</label><input id="sa-change" class="border rounded p-2 w-full" disabled /></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div><label class="text-sm block mb-1">Grade (optional)</label><select id="sa-grade" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Step (optional)</label><select id="sa-step" class="border rounded p-2 w-full"></select></div>
                    <div><label class="text-sm block mb-1">Effective Date</label><input id="sa-eff" type="date" class="border rounded p-2 w-full" /></div>
                </div>
                <div>
                    <label class="text-sm block mb-1">Attachment (optional)</label>
                    <div class="flex gap-2">
                        <input id="sa-attach" class="border rounded p-2 flex-1" placeholder="https://..." />
                        <input id="sa-file" type="file" class="border rounded p-2 text-sm" />
                        <button id="sa-upload" class="px-3 py-2 border rounded text-sm">Upload</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Provide a URL or upload a file; the URL will be stored with the adjustment.</p>
                </div>
                <div>
                    <label class="text-sm block mb-1">Justification</label>
                    <textarea id="sa-just" class="border rounded p-2 w-full" placeholder="Provide supporting details..."></textarea>
                </div>
                <div class="flex justify-end gap-2"><button id="sa-save" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">Submit for Review</button><button id="sa-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button></div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('sa-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('sa-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('sa-save')?.addEventListener('click', submitSalaryAdjustment);
    await populateEmployeesForAdjustment('sa-employee');
    await populateReasons('sa-reason');
    await populateGrades('sa-grade');
    document.getElementById('sa-grade')?.addEventListener('change', async (ev)=>{ await populateSteps('sa-step', ev.target.value); });
    document.getElementById('sa-employee')?.addEventListener('change', onAdjustmentEmployeeChange);
    document.getElementById('sa-new')?.addEventListener('input', updateChangePreview);
    document.getElementById('sa-upload')?.addEventListener('click', uploadAdjustmentFile);
}

async function populateEmployeesForAdjustment(selectId) {
    try {
        const res = await fetch(`${REST_API_URL}integrations/hrcore`, { credentials:'include' });
        if (!res.ok) return; const payload = await res.json(); const list = payload?.data || payload || [];
        const sel = document.getElementById(selectId); if (!sel) return; sel.innerHTML='';
        list.forEach(e=>{ const o=document.createElement('option'); o.value=e.EmployeeID; o.textContent=`${e.employee_name || (e.FirstName? (e.FirstName+' '+(e.LastName||'')) : '')} (${e.EmployeeNumber||''})`; o.setAttribute('data-json', JSON.stringify(e)); sel.appendChild(o); });
    } catch(e){}
}

async function populateGrades(selectId) {
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/salary-grades`, { credentials:'include' });
        if (!res.ok) return; const list = await res.json();
        const sel = document.getElementById(selectId); if (!sel) return; sel.innerHTML = '<option value="">—</option>';
        (Array.isArray(list)? list : (list?.data||[])).forEach(g=>{ const o=document.createElement('option'); o.value=g.GradeID; o.textContent=`${g.GradeCode} - ${g.GradeName}`; sel.appendChild(o); });
    } catch(e){}
}

async function populateSteps(selectId, gradeId) {
    try { const sel=document.getElementById(selectId); if (!sel) return; sel.innerHTML='<option value="">—</option>'; if (!gradeId) return; const res=await fetch(`${REST_API_URL}compensation-planning/salary-grades-steps?grade_id=${encodeURIComponent(gradeId)}`, { credentials:'include' }); if (!res.ok) return; const list=await res.json(); (Array.isArray(list)? list:(list?.data||[])).forEach(s=>{ const o=document.createElement('option'); o.value=s.StepID; o.textContent=`Step ${s.StepNumber} (${currency(s.BaseRate)})`; sel.appendChild(o); }); } catch(e){}
}

function onAdjustmentEmployeeChange(ev) {
    try {
        const opt = ev.target.selectedOptions?.[0]; if (!opt) return; const data = JSON.parse(opt.getAttribute('data-json')||'{}');
        const oldSalary = Number(data.BaseSalary || data.base_salary || 0);
        const oldEl = document.getElementById('sa-old'); if (oldEl) oldEl.value = currency(oldSalary);
        updateChangePreview();
    } catch(e){}
}

function updateChangePreview() {
    try {
        const oldS = parseFloat((document.getElementById('sa-old')?.value||'').replace(/[^0-9.]/g,''))||0;
        const newS = parseFloat(document.getElementById('sa-new')?.value||'')||0;
        const diff = newS - oldS; const pct = oldS>0? (diff/oldS*100):0;
        const el = document.getElementById('sa-change'); if (el) el.value = `${currency(diff)} (${pct.toFixed(1)}%)`;
    } catch(e){}
}

async function submitSalaryAdjustment() {
    try {
        const empSel = document.getElementById('sa-employee'); const emp = empSel?.selectedOptions?.[0]; if (!emp) { alert('Select employee'); return; }
        const empJson = JSON.parse(emp.getAttribute('data-json')||'{}');
        const payload = {
            employee_id: Number(emp.value),
            department_id: empJson.DepartmentID || null,
            old_salary: parseFloat((document.getElementById('sa-old')?.value||'').replace(/[^0-9.]/g,''))||0,
            new_salary: parseFloat(document.getElementById('sa-new')?.value||'')||0,
            grade_id: document.getElementById('sa-grade')?.value || null,
            step_id: document.getElementById('sa-step')?.value || null,
            reason_id: document.getElementById('sa-reason')?.value || null,
            justification: document.getElementById('sa-just')?.value?.trim() || null,
            attachment_url: document.getElementById('sa-attach')?.value?.trim() || null,
            effective_date: document.getElementById('sa-eff')?.value || new Date().toISOString().slice(0,10),
            initiated_by: window.currentUser?.employee_id || null
        };
        const res = await fetch(`${REST_API_URL}compensation-planning/salary-adjustments`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
        if (!res.ok) throw new Error('Failed to create adjustment');
        document.getElementById('sa-modal')?.remove();
        await loadSalaryAdjustments();
        // Notification hook (best-effort)
        try { if (window.initializeNotificationSystem) window.initializeNotificationSystem(); } catch(e){}
        alert('Adjustment submitted for review');
    } catch(e) { alert('Save failed: '+e.message); }
}

async function uploadAdjustmentFile(ev) {
    try {
        ev.preventDefault();
        const input = document.getElementById('sa-file'); const file = input?.files?.[0]; if (!file) { alert('Choose a file first'); return; }
        const fd = new FormData(); fd.append('file', file);
        const res = await fetch(`${LEGACY_API_URL}upload_attachment.php`, { method:'POST', body: fd });
        const payload = await res.json(); if (!res.ok || !payload?.success) throw new Error(payload?.error || 'Upload failed');
        const urlInput = document.getElementById('sa-attach'); if (urlInput) urlInput.value = payload.url;
        alert('Uploaded: ' + payload.filename);
    } catch(e) { alert('Upload failed: ' + e.message); }
}

// Incentives placeholder (kept for main.js compatibility)
export async function displayIncentivesSection() {
    const main = setPage('Incentives');
    if (!main) return;
    main.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-pink-50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-pink-900">Incentives Dashboard</h3>
                    <p class="text-sm text-pink-700">Overview and management of incentive programs.</p>
                </div>
                <div class="flex gap-2">
                    <button id="btn-add-incentive-type" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">+ Add Incentive</button>
                    <button id="btn-export-incentives" class="px-3 py-2 border rounded text-sm">Export CSV</button>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div id="incentives-cards" class="grid grid-cols-1 md:grid-cols-4 gap-4"></div>
                <div class="flex flex-wrap gap-3 items-end">
                    <select id="inc-filter-dept" class="px-3 py-2 border rounded text-sm"><option value="">All Departments</option></select>
                    <select id="inc-filter-category" class="px-3 py-2 border rounded text-sm">
                        <option value="">All Categories</option>
                        <option>Cash</option><option>Non-Cash</option><option>Recognition</option>
                        <option>Professional Development</option><option>Health & Wellness</option>
                        <option>Time-Based</option><option>Loyalty</option>
                    </select>
                    <input id="inc-filter-start" type="date" class="px-3 py-2 border rounded text-sm" />
                    <input id="inc-filter-end" type="date" class="px-3 py-2 border rounded text-sm" />
                    <button id="inc-apply" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
                </div>
                <div id="incentives-types-list" class="overflow-x-auto"></div>
            </div>
        </div>`;

    document.getElementById('btn-add-incentive-type')?.addEventListener('click', openAddIncentiveTypeModal);
    document.getElementById('btn-export-incentives')?.addEventListener('click', exportIncentivesCSV);
    document.getElementById('inc-apply')?.addEventListener('click', loadIncentiveTypes);
    // Add Grant Incentive button near Add Incentive Type
    const headerBtns = document.querySelector('.px-6.py-4.border-b.bg-pink-50.flex.items-center.justify-between .flex.gap-2');
    if (headerBtns && !document.getElementById('btn-grant-incentive')) {
        const grantBtn = document.createElement('button'); grantBtn.id='btn-grant-incentive'; grantBtn.className='px-3 py-2 border rounded text-sm'; grantBtn.textContent='Grant Incentive'; grantBtn.addEventListener('click', openGrantIncentiveModal); headerBtns.appendChild(grantBtn);
    }
    await loadIncentiveCards();
    await loadIncentiveTypes();
}

async function loadIncentiveCards() {
    const wrap = document.getElementById('incentives-cards'); if (!wrap) return;
    wrap.innerHTML = `
        <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Total Granted (This Month)</div><div id="inc-card-total" class="text-2xl font-semibold">—</div></div>
        <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Top Performing Dept</div><div id="inc-card-top-dept" class="text-lg font-semibold">—</div></div>
        <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Total Cash Cost</div><div id="inc-card-cash" class="text-2xl font-semibold">—</div></div>
        <div class="p-4 bg-white border rounded shadow-sm"><div class="text-xs text-gray-500">Most Common Non-Cash</div><div id="inc-card-common" class="text-lg font-semibold">—</div></div>`;
    try {
        // If analytics endpoint exists, use it. Otherwise leave placeholders.
        const analytics = await fetch(`${REST_API_URL}integrations/analytics?type=incentives`).then(r=>r.ok?r.json():null).catch(()=>null);
        if (analytics?.data) {
            const d = analytics.data;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('inc-card-total', d.total_granted_this_month ?? '—');
            set('inc-card-top-dept', d.top_department ?? '—');
            set('inc-card-cash', d.total_cash_cost ? currency(d.total_cash_cost) : '—');
            set('inc-card-common', d.most_common_non_cash ?? '—');
        }
    } catch(e){}
}

async function loadIncentiveTypes() {
    const container = document.getElementById('incentives-types-list'); if (!container) return;
    container.innerHTML = '<div class="py-6 text-center text-gray-500">Loading incentives...</div>';
    try {
        const params = new URLSearchParams();
        const cat = document.getElementById('inc-filter-category')?.value; if (cat) params.set('category', cat);
        const dept = document.getElementById('inc-filter-dept')?.value; if (dept) params.set('department_id', dept);
        const res = await fetch(`${REST_API_URL}compensation-planning/incentive-types?${params}`, { credentials:'include' });
        if (!res.ok) throw new Error('Failed to load incentive types');
        const payload = await res.json();
        const list = payload?.data || payload || [];
        if (!Array.isArray(list) || !list.length) { container.innerHTML = '<div class="py-8 text-center text-gray-500">No incentives defined.</div>'; return; }
        const table = document.createElement('table'); table.className='min-w-full divide-y divide-gray-200';
        table.innerHTML = `
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Category</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Frequency</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
            </tr></thead>`;
        const tbody = table.createTBody(); tbody.className='bg-white divide-y divide-gray-200';
        list.forEach(i => {
            const tr = tbody.insertRow();
            const td = (t,cls='') => { const c = tr.insertCell(); c.className=`px-4 py-3 text-sm ${cls}`; c.textContent=t??''; return c; };
            td(i.Name || i.name, 'font-medium');
            td(i.Category || i.category);
            td((i.ValueType==='Amount' || i.value_type==='Amount') ? currency(i.ValueAmount ?? i.value_amount ?? 0) : (i.ValueAmount ?? i.value_amount ?? '—'));
            td(i.Frequency || i.frequency || 'One-time');
            td(i.Status || i.status || 'Active');
            const actions = tr.insertCell(); actions.className='px-4 py-3 text-sm';
            const edit = document.createElement('button'); edit.className='text-gray-700 hover:underline mr-2'; edit.textContent='Edit'; edit.onclick=()=>openEditIncentiveTypeModal(i);
            const del = document.createElement('button'); del.className='text-red-600 hover:underline'; del.textContent='Delete'; del.onclick=()=>deleteIncentiveType(i.IncentiveTypeID || i.id);
            actions.appendChild(edit); actions.appendChild(del);
        });
        container.innerHTML=''; container.appendChild(table);
    } catch (e) { container.innerHTML = `<div class="py-6 text-center text-red-600">${e.message}</div>`; }
}

function openAddIncentiveTypeModal() {
    const existing = document.getElementById('inc-type-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='inc-type-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-2xl mx-auto mt-16 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center"><h4 class="font-semibold">Add Incentive Type</h4><button id="inc-type-close" class="text-gray-500">✕</button></div>
            <div class="p-4 space-y-3">
                <input id="it-name" class="border rounded p-2 w-full" placeholder="Incentive Name" />
                <select id="it-category" class="border rounded p-2 w-full">
                    <option>Cash</option><option>Non-Cash</option><option>Recognition</option><option>Professional Development</option><option>Health & Wellness</option><option>Time-Based</option><option>Loyalty</option>
                </select>
                <textarea id="it-desc" class="border rounded p-2 w-full" placeholder="Description"></textarea>
                <textarea id="it-elig" class="border rounded p-2 w-full" placeholder='Eligibility JSON (e.g., {"performanceRatingGte":90,"yearsOfServiceGte":5})'></textarea>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <select id="it-value-type" class="border rounded p-2"><option>Amount</option><option>Equivalent</option></select>
                    <input id="it-value" class="border rounded p-2" placeholder="Value/Amount" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select id="it-frequency" class="border rounded p-2"><option>One-time</option><option>Monthly</option><option>Quarterly</option><option>Annual</option></select>
                    <input id="it-dept" class="border rounded p-2" placeholder="Department ID (optional)" />
                    <input id="it-poscat" class="border rounded p-2" placeholder="Position Category (optional)" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm"><input id="it-taxable" type="checkbox" class="mr-2">Taxable</label>
                    <select id="it-status" class="border rounded p-2 text-sm"><option>Active</option><option>Inactive</option></select>
                </div>
                <div class="flex justify-end gap-2"><button id="it-save" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">Save</button><button id="it-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button></div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('inc-type-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('it-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('it-save')?.addEventListener('click', async ()=>{
        try {
            const payload = {
                name: document.getElementById('it-name')?.value?.trim(),
                category: document.getElementById('it-category')?.value,
                description: document.getElementById('it-desc')?.value?.trim() || null,
                eligibility: safeParseJSON(document.getElementById('it-elig')?.value),
                value_type: document.getElementById('it-value-type')?.value || 'Amount',
                value_amount: Number(document.getElementById('it-value')?.value || 0),
                frequency: document.getElementById('it-frequency')?.value || 'One-time',
                department_id: parseInt(document.getElementById('it-dept')?.value) || null,
                position_category: document.getElementById('it-poscat')?.value?.trim() || null,
                taxable: document.getElementById('it-taxable')?.checked ? 1 : 0,
                status: document.getElementById('it-status')?.value || 'Active'
            };
            const res = await fetch(`${REST_API_URL}compensation-planning/incentive-types`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
            if (!res.ok) throw new Error('Failed to create incentive type');
            w.remove();
            await loadIncentiveTypes();
        } catch(e) { alert('Save failed: ' + e.message); }
    });
}

function openEditIncentiveTypeModal(item) {
    const existing = document.getElementById('inc-type-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='inc-type-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-2xl mx-auto mt-16 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center"><h4 class="font-semibold">Edit Incentive Type</h4><button id="inc-type-close" class="text-gray-500">✕</button></div>
            <div class="p-4 space-y-3">
                <input id="it-name" class="border rounded p-2 w-full" placeholder="Incentive Name" value="${item.Name || item.name || ''}" />
                <select id="it-category" class="border rounded p-2 w-full">
                    ${['Cash','Non-Cash','Recognition','Professional Development','Health & Wellness','Time-Based','Loyalty'].map(c=>`<option ${((item.Category||item.category)==c?'selected':'')}>${c}</option>`).join('')}
                </select>
                <textarea id="it-desc" class="border rounded p-2 w-full" placeholder="Description">${item.Description || item.description || ''}</textarea>
                <textarea id="it-elig" class="border rounded p-2 w-full" placeholder='Eligibility JSON'>${item.EligibilityJSON || item.eligibility || ''}</textarea>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <select id="it-value-type" class="border rounded p-2"><option ${((item.ValueType||item.value_type)==='Amount'?'selected':'')}>Amount</option><option ${((item.ValueType||item.value_type)!=='Amount'?'selected':'')}>Equivalent</option></select>
                    <input id="it-value" class="border rounded p-2" placeholder="Value/Amount" value="${item.ValueAmount ?? item.value_amount ?? ''}" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select id="it-frequency" class="border rounded p-2"><option ${((item.Frequency||item.frequency)==='One-time'?'selected':'')}>One-time</option><option ${((item.Frequency||item.frequency)==='Monthly'?'selected':'')}>Monthly</option><option ${((item.Frequency||item.frequency)==='Quarterly'?'selected':'')}>Quarterly</option><option ${((item.Frequency||item.frequency)==='Annual'?'selected':'')}>Annual</option></select>
                    <input id="it-dept" class="border rounded p-2" placeholder="Department ID (optional)" value="${item.DepartmentID ?? ''}" />
                    <input id="it-poscat" class="border rounded p-2" placeholder="Position Category (optional)" value="${item.PositionCategory || ''}" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm"><input id="it-taxable" type="checkbox" class="mr-2" ${(item.Taxable||item.taxable)?'checked':''}>Taxable</label>
                    <select id="it-status" class="border rounded p-2 text-sm"><option ${(item.Status||item.status)==='Active'?'selected':''}>Active</option><option ${(item.Status||item.status)==='Inactive'?'selected':''}>Inactive</option></select>
                </div>
                <div class="flex justify-end gap-2"><button id="it-save" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">Save</button><button id="it-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button></div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('inc-type-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('it-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('it-save')?.addEventListener('click', async ()=>{
        try {
            const payload = {
                id: item.IncentiveTypeID || item.id,
                name: document.getElementById('it-name')?.value?.trim(),
                category: document.getElementById('it-category')?.value,
                description: document.getElementById('it-desc')?.value?.trim() || null,
                eligibility: safeParseJSON(document.getElementById('it-elig')?.value),
                value_type: document.getElementById('it-value-type')?.value || 'Amount',
                value_amount: Number(document.getElementById('it-value')?.value || 0),
                frequency: document.getElementById('it-frequency')?.value || 'One-time',
                department_id: parseInt(document.getElementById('it-dept')?.value) || null,
                position_category: document.getElementById('it-poscat')?.value?.trim() || null,
                taxable: document.getElementById('it-taxable')?.checked ? 1 : 0,
                status: document.getElementById('it-status')?.value || 'Active'
            };
            const res = await fetch(`${REST_API_URL}compensation-planning/incentive-types`, { method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
            if (!res.ok) throw new Error('Failed to update incentive type');
            w.remove();
            await loadIncentiveTypes();
        } catch(e) { alert('Save failed: ' + e.message); }
    });
}

async function deleteIncentiveType(id) {
    if (!confirm('Delete this incentive type?')) return;
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/incentive-types?id=${encodeURIComponent(id)}`, { method:'DELETE', credentials:'include' });
        if (!res.ok) throw new Error('Failed to delete incentive type');
        await loadIncentiveTypes();
    } catch(e) { alert('Delete failed: ' + e.message); }
}

function exportIncentivesCSV() {
    const table = document.querySelector('#incentives-types-list table'); if (!table) { alert('No data to export'); return; }
    const headers = ['Name','Category','Value','Frequency','Status'];
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr=>{
        const t = tr.querySelectorAll('td');
        return [t[0]?.textContent?.trim(), t[1]?.textContent?.trim(), t[2]?.textContent?.trim(), t[3]?.textContent?.trim(), t[4]?.textContent?.trim()];
    });
    downloadCsv('incentive_types', headers, rows);
}

function safeParseJSON(s) { try { return s ? JSON.parse(s) : null; } catch(e){ return null; } }

// --- Grant Incentive Modal & Flow ---
async function openGrantIncentiveModal() {
    const existing = document.getElementById('grant-incentive-modal'); if (existing) existing.remove();
    const w = document.createElement('div'); w.id='grant-incentive-modal'; w.className='fixed inset-0 z-50';
    w.innerHTML = `
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-5xl mx-auto mt-10 bg-white rounded shadow">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h4 class="font-semibold">Grant Incentive</h4>
                <button id="gi-close" class="text-gray-500">✕</button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm block mb-1">Incentive Type</label>
                        <select id="gi-type" class="border rounded p-2 w-full"></select>
                    </div>
                    <div>
                        <label class="text-sm block mb-1">Amount Override (optional)</label>
                        <input id="gi-amount" class="border rounded p-2 w-full" placeholder="e.g., 2000" />
                        <p class="text-xs text-gray-500 mt-1">Leave blank to use type default. For non-cash, leave blank.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input id="gi-dept" class="border rounded p-2" placeholder="Department ID (filter)" />
                    <input id="gi-yos" class="border rounded p-2" placeholder="Years of Service ≥" />
                    <input id="gi-rating" class="border rounded p-2" placeholder="Performance Rating ≥" />
                    <input id="gi-award-date" type="date" class="border rounded p-2" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input id="gi-payout-date" type="date" class="border rounded p-2" />
                    <input id="gi-search" class="border rounded p-2 md:col-span-3" placeholder="Search employees by name or number" />
                </div>
                <div class="flex gap-2 items-center">
                    <button id="gi-load" class="px-3 py-2 border rounded text-sm">Load Employees</button>
                    <span class="text-xs text-gray-500">Select employees to grant the incentive.</span>
                </div>
                <div id="gi-employees" class="max-h-[50vh] overflow-auto border rounded"></div>
                <div class="flex justify-end gap-2">
                    <button id="gi-approve" class="px-3 py-2 bg-pink-600 text-white rounded text-sm">Approve & Push to Payroll</button>
                    <button id="gi-cancel" class="px-3 py-2 border rounded text-sm">Cancel</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(w);
    document.getElementById('gi-close')?.addEventListener('click', ()=>w.remove());
    document.getElementById('gi-cancel')?.addEventListener('click', ()=>w.remove());
    document.getElementById('gi-load')?.addEventListener('click', loadGrantIncentiveEmployees);
    document.getElementById('gi-approve')?.addEventListener('click', approveGrantIncentive);
    await populateIncentiveTypeSelect('gi-type');
}

async function populateIncentiveTypeSelect(selectId) {
    try {
        const res = await fetch(`${REST_API_URL}compensation-planning/incentive-types`, { credentials:'include' });
        if (!res.ok) throw new Error('Failed to load incentive types');
        const payload = await res.json();
        const list = payload?.data || payload || [];
        const sel = document.getElementById(selectId);
        if (sel) { sel.innerHTML=''; list.forEach(i=>{ const o=document.createElement('option'); o.value = i.IncentiveTypeID || i.id; o.textContent = `${i.Name || i.name} (${i.Category || i.category})`; o.setAttribute('data-json', JSON.stringify(i)); sel.appendChild(o); }); }
    } catch(e) { /* ignore */ }
}

async function loadGrantIncentiveEmployees() {
    const container = document.getElementById('gi-employees'); if (!container) return;
    container.innerHTML = '<div class="p-4 text-center text-gray-500">Loading employees...</div>';
    const dept = document.getElementById('gi-dept')?.value?.trim();
    const yos = parseFloat(document.getElementById('gi-yos')?.value || '');
    const rating = parseFloat(document.getElementById('gi-rating')?.value || '');
    const search = document.getElementById('gi-search')?.value?.trim()?.toLowerCase();
    try {
        const params = new URLSearchParams(); if (dept) params.set('department_id', dept);
        const res = await fetch(`${REST_API_URL}integrations/hrcore?${params}`, { credentials:'include' });
        if (!res.ok) throw new Error('Failed to load HR Core employees');
        let list = await res.json(); list = list?.data || list || [];
        // Client-side filters if fields exist
        list = list.filter(e => {
            let ok = true;
            if (yos && typeof e.years_of_service !== 'undefined') ok = ok && (parseFloat(e.years_of_service)||0) >= yos;
            if (rating && typeof e.performance_rating !== 'undefined') ok = ok && (parseFloat(e.performance_rating)||0) >= rating;
            if (search) ok = ok && ((e.employee_name||'').toLowerCase().includes(search) || String(e.EmployeeNumber||'').toLowerCase().includes(search));
            return ok;
        });
        // Render checkboxes
        const table = document.createElement('table'); table.className='min-w-full divide-y divide-gray-200';
        table.innerHTML = `<thead class="bg-gray-50"><tr>
            <th class="px-3 py-2"><input id="gi-check-all" type="checkbox" /></th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Employee</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Department</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Position</th>
        </tr></thead>`;
        const tbody = table.createTBody(); tbody.className='bg-white divide-y divide-gray-200';
        list.forEach(e => {
            const tr = tbody.insertRow();
            const cbCell = tr.insertCell(); cbCell.className='px-3 py-2'; const cb = document.createElement('input'); cb.type='checkbox'; cb.value = e.EmployeeID; cb.setAttribute('data-emp-json', JSON.stringify(e)); cbCell.appendChild(cb);
            const name = tr.insertCell(); name.className='px-3 py-2 text-sm'; name.textContent = `${e.employee_name || (e.FirstName? (e.FirstName+' '+(e.LastName||'')) : '')}`;
            const deptCell = tr.insertCell(); deptCell.className='px-3 py-2 text-sm'; deptCell.textContent = e.DepartmentName || e.Department || '';
            const posCell = tr.insertCell(); posCell.className='px-3 py-2 text-sm'; posCell.textContent = e.PositionName || e.JobTitle || '';
        });
        container.innerHTML=''; container.appendChild(table);
        const checkAll = document.getElementById('gi-check-all'); if (checkAll) checkAll.addEventListener('change', (ev)=>{ const on = ev.target.checked; container.querySelectorAll('tbody input[type="checkbox"]').forEach(cb=>{ cb.checked = on; }); });
    } catch(e) { container.innerHTML = `<div class=\"p-4 text-center text-red-600\">${e.message}</div>`; }
}

async function approveGrantIncentive() {
    try {
        const typeSel = document.getElementById('gi-type');
        const typeJson = typeSel?.selectedOptions?.[0]?.getAttribute('data-json');
        if (!typeJson) { alert('Select an incentive type'); return; }
        const type = JSON.parse(typeJson);
        const overrideAmount = document.getElementById('gi-amount')?.value?.trim();
        const amount = overrideAmount ? Number(overrideAmount) : (type.ValueType === 'Amount' ? Number(type.ValueAmount||0) : 0);
        const awardDate = document.getElementById('gi-award-date')?.value || new Date().toISOString().slice(0,10);
        const payoutDate = document.getElementById('gi-payout-date')?.value || null;
        const checks = Array.from(document.querySelectorAll('#gi-employees tbody input[type="checkbox"]:checked'));
        if (!checks.length) { alert('Select at least one employee'); return; }

        let success=0, failed=0, payrollOk=0, payrollFail=0;
        for (const cb of checks) {
            const emp = JSON.parse(cb.getAttribute('data-emp-json')||'{}');
            // 1) Save incentive record
            try {
                const resp = await fetch(`${LEGACY_API_URL}add_incentive.php`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({
                    employee_id: emp.EmployeeID,
                    plan_id: activePlanId ? Number(activePlanId) : null,
                    incentive_type: type.Name || type.name,
                    amount: amount,
                    award_date: awardDate,
                    payout_date: payoutDate,
                    payroll_id: null
                }) });
                if (!resp.ok) throw new Error('add_incentive failed');
                success++;
            } catch(e) { failed++; continue; }
            // 2) Push to Payroll
            try {
                let pushed=false;
                // Prefer bonuses route if available
                try {
                    const bres = await fetch(`${REST_API_URL}bonuses`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ employee_id: emp.EmployeeID, bonus_type: `Incentive: ${(type.Name||type.name)}`, amount: amount, taxable: !!(type.Taxable||type.taxable), award_date: awardDate }) });
                    if (bres.ok) { pushed=true; }
                } catch(e){}
                if (!pushed) {
                    // Fallback to payroll integration update with reason tag
                    const changes = [{ employee_id: emp.EmployeeID, new_salary: emp.BaseSalary||0, reason: `Incentive: ${(type.Name||type.name)}, taxable=${(type.Taxable||type.taxable)?1:0}, amount=${amount}` }];
                    const presp = await fetch(`${REST_API_URL}integrations/payroll/update`, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({ changes, effective_date: awardDate, reason: `Incentive grant ${(type.Name||type.name)}` }) });
                    if (!presp.ok) throw new Error('payroll update failed');
                }
                payrollOk++;
            } catch(e) { payrollFail++; }
        }
        alert(`Incentives saved: ${success}, failed: ${failed}. Payroll push ok: ${payrollOk}, failed: ${payrollFail}.`);
        document.getElementById('grant-incentive-modal')?.remove();
    } catch(e) { alert('Grant failed: ' + e.message); }
}

// Utilities
function currency(v) { const n = Number(v||0); return n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }); }

export default function initialize() {
    // no-op; functions are exported and called by main.js navigation
}


