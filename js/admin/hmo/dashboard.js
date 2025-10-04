import { API_BASE_URL } from '../../utils.js';

export async function renderHMODashboard(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    try{
    const res = await fetch(`${API_BASE_URL}hmo_dashboard.php`, { credentials:'include' }); const data = await res.json(); const s = data.summary||{};
        container.innerHTML = `
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-4">HMO Dashboard</h2>
                <div class="grid grid-cols-4 gap-4">
                    <div id="hmo-active-providers-card" class="p-4 bg-white rounded shadow cursor-pointer" title="View Providers">Active Providers<br/><strong>${s.total_active_providers||s.providers||0}</strong></div>
                    <div id="hmo-active-plans-card" class="p-4 bg-white rounded shadow cursor-pointer" title="View Plans">Active Plans<br/><strong>${s.total_active_plans||s.plans||0}</strong></div>
                    <div id="hmo-enrolled-employees-card" class="p-4 bg-white rounded shadow cursor-pointer" title="View Enrollments">Enrolled Employees<br/><strong>${s.total_enrolled_employees||s.active_enrollments||0}</strong></div>
                    <div id="hmo-pending-claims-card" class="p-4 bg-white rounded shadow cursor-pointer" title="View Pending Claims">Pending Claims<br/><strong>${(s.claims&&s.claims.pending)||s.pending_claims||0}</strong></div>
                </div>
                <div class="mt-6 bg-white p-4 rounded shadow"><h3 class="font-semibold">Claims Summary</h3>
                    <div>Approved: ${(s.claims&&s.claims.approved)||s.approved_claims||0} | Pending: ${(s.claims&&s.claims.pending)||s.pending_claims||0} | Denied: ${(s.claims&&s.claims.denied)||s.denied_claims||0}</div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded shadow">
                        <h4 class="font-semibold">Monthly Claims Trend</h4>
                        <canvas id="hmo-monthly-claims-chart" height="160"></canvas>
                    </div>
                    <div class="bg-white p-4 rounded shadow">
                        <h4 class="font-semibold">Top Hospitals</h4>
                        <canvas id="hmo-top-hospitals-chart" height="160"></canvas>
                    </div>
                </div>
                <div class="mt-6 bg-white p-4 rounded shadow">
                    <h4 class="font-semibold">Plan Utilization</h4>
                    <canvas id="hmo-plan-utilization-chart" height="80"></canvas>
                </div>
                ${( (s.total_active_providers||s.providers||0) === 0 ) && ( (s.total_active_plans||s.plans||0) === 0 ) ? `
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">No HMO providers or plans found. Import <code>database/hmo_schema_and_seed.sql</code> or add providers & plans from the HMO module to populate data for the dashboard.</div>
                `: ''}
            </div>
        `;
        // draw charts after DOM updated
        setTimeout(()=>{ if (document.getElementById('hmo-monthly-claims-chart')) drawHMOCharts(); }, 250);
    }catch(e){console.error(e); container.innerHTML='<div class="p-6">Error loading dashboard</div>'}
}
// chart bootstrapper
async function drawHMOCharts(){
    try{
        const base = `${API_BASE_URL}hmo_dashboard.php`;
        const mc = await fetch(base+'?mode=monthly_claims', { credentials:'include' }); const mcj = await mc.json(); const monthly = mcj.monthly_claims||[];
        const th = await fetch(base+'?mode=top_hospitals', { credentials:'include' }); const thj = await th.json(); const top = thj.top_hospitals||[];
        const pu = await fetch(base+'?mode=plan_utilization', { credentials:'include' }); const puj = await pu.json(); const plans = puj.plan_utilization||[];
        // chart options shared
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.formattedValue;
                            return label ? `${label}: ${value}` : `${value}`;
                        }
                    }
                }
            },
            interaction: { mode: 'nearest', intersect: false }
        };

        // Ensure a global container for HMO charts so we can resize/destroy later
        window._hmoCharts = window._hmoCharts || {};

        // prepare monthly chart
        if (window.Chart && monthly.length>0){
            const labels = monthly.map(r=>r.ym);
            const data = monthly.map(r=>Number(r.cnt));
            const canvas = document.getElementById('hmo-monthly-claims-chart');
            const ctx = canvas.getContext('2d');
            // destroy existing
            if (window._hmoCharts.monthly) { try{ window._hmoCharts.monthly.destroy(); }catch(e){} }
            window._hmoCharts.monthly = new Chart(ctx,{
                type:'line',
                data:{ labels, datasets:[{ label:'Claims', data, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.12)', fill: true, tension: 0.2 }]},
                options: Object.assign({}, baseOptions, { scales: { x: { display: true }, y: { display: true, beginAtZero: true } } })
            });
        }

        // top hospitals
        if (window.Chart && top.length>0){
            const labels = top.map(r=>r.hospital||'Unknown');
            const data = top.map(r=>Number(r.cnt));
            const canvas = document.getElementById('hmo-top-hospitals-chart');
            const ctx = canvas.getContext('2d');
            if (window._hmoCharts.topHosp) { try{ window._hmoCharts.topHosp.destroy(); }catch(e){} }
            window._hmoCharts.topHosp = new Chart(ctx,{
                type:'bar',
                data:{ labels, datasets:[{ label:'Claims', data, backgroundColor: labels.map((_,i)=>['#10b981','#34d399','#60a5fa','#f97316','#f59e0b'][i%5]) }]},
                options: Object.assign({}, baseOptions, { scales: { x: { ticks: { autoSkip: false } }, y: { beginAtZero: true } } })
            });
        }

        // plan utilization
        if (window.Chart && plans.length>0){
            const labels = plans.map(r=>r.PlanName);
            const data = plans.map(r=>Number(r.enrolled));
            const canvas = document.getElementById('hmo-plan-utilization-chart');
            const ctx = canvas.getContext('2d');
            if (window._hmoCharts.planUtil) { try{ window._hmoCharts.planUtil.destroy(); }catch(e){} }
            window._hmoCharts.planUtil = new Chart(ctx,{
                type:'bar',
                data:{ labels, datasets:[{ label:'Enrolled', data, backgroundColor:'#f59e0b' }]},
                options: Object.assign({}, baseOptions, { scales: { x: { ticks: { autoSkip: false } }, y: { beginAtZero: true } } })
            });
        }

        // responsive handling: ensure charts resize when their container changes
        try{
            const observeTargets = [
                document.getElementById('hmo-monthly-claims-chart')?.parentElement,
                document.getElementById('hmo-top-hospitals-chart')?.parentElement,
                document.getElementById('hmo-plan-utilization-chart')?.parentElement
            ].filter(Boolean);
            if (observeTargets.length && typeof ResizeObserver !== 'undefined'){
                if (!window._hmoCharts._resizeObserver){
                    window._hmoCharts._resizeObserver = new ResizeObserver(entries=>{
                        // small debounce
                        clearTimeout(window._hmoCharts._resizeTimeout);
                        window._hmoCharts._resizeTimeout = setTimeout(()=>{
                            Object.keys(window._hmoCharts).forEach(k=>{
                                if (k.startsWith('_')) return; // skip internal
                                try{ window._hmoCharts[k].resize(); }catch(e){}
                            });
                        }, 120);
                    });
                }
                observeTargets.forEach(t=>window._hmoCharts._resizeObserver.observe(t));
            }
            // also handle window resize
            window.addEventListener('resize', ()=>{ clearTimeout(window._hmoCharts._resizeTimeout); window._hmoCharts._resizeTimeout = setTimeout(()=>{ Object.keys(window._hmoCharts).forEach(k=>{ if (k.startsWith('_')) return; try{ window._hmoCharts[k].resize(); }catch(e){} }); }, 120); });
        }catch(e){console.warn('Resize handling setup failed', e)}
    }catch(err){console.error('Charts error',err)}
}
// Draw charts after DOM updates
setTimeout(()=>{ if (document.getElementById('hmo-monthly-claims-chart')) drawHMOCharts(); }, 500);

// Provide a generic initializer the dynamic loader can call
export async function initialize(containerId = 'main-content-area') {
    return renderHMODashboard(containerId);
}

// Make the providers card clickable after render
setTimeout(() => {
    try {
        const card = document.getElementById('hmo-active-providers-card');
        if (card) {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                // Prefer the global display function if available
                if (typeof window.displayHMOProvidersSection === 'function') {
                    window.displayHMOProvidersSection();
                    return;
                }
                // Fallback to navigateToSectionById
                if (typeof window.navigateToSectionById === 'function') {
                    window.navigateToSectionById('hmo-providers');
                    return;
                }
                // Last resort: dynamic import directly
                import('../../utils.js').then(mod => {
                    const { loadModule } = mod;
                    const main = document.getElementById('main-content-area');
                    if (typeof loadModule === 'function' && main) loadModule('admin/hmo/providers.js', main, 'HMO Providers');
                }).catch(err => console.error('Failed to load providers via fallback:', err));
            });
        }
    } catch (err) {
        console.warn('Could not attach click handler to HMO providers card:', err);
    }
}, 300);

// Attach handlers for other dashboard cards
setTimeout(() => {
    try {
        const plansCard = document.getElementById('hmo-active-plans-card');
        if (plansCard) {
            plansCard.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof window.displayHMOPlansSection === 'function') { window.displayHMOPlansSection(); return; }
                if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-plans'); return; }
                import('../../utils.js').then(mod => { const { loadModule } = mod; const main = document.getElementById('main-content-area'); if (typeof loadModule === 'function' && main) loadModule('admin/hmo/plans.js', main, 'HMO Plans'); }).catch(err => console.error('Failed to load plans via fallback:', err));
            });
        }

        const enrollCard = document.getElementById('hmo-enrolled-employees-card');
        if (enrollCard) {
            enrollCard.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof window.displayHMOEnrollmentsSection === 'function') { window.displayHMOEnrollmentsSection(); return; }
                if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-enrollments'); return; }
                import('../../utils.js').then(mod => { const { loadModule } = mod; const main = document.getElementById('main-content-area'); if (typeof loadModule === 'function' && main) loadModule('admin/hmo/enrollments.js', main, 'HMO Enrollments'); }).catch(err => console.error('Failed to load enrollments via fallback:', err));
            });
        }

        const pendingCard = document.getElementById('hmo-pending-claims-card');
        if (pendingCard) {
            pendingCard.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof window.displayHMOClaimsApprovalSection === 'function') { window.displayHMOClaimsApprovalSection(); return; }
                if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-claims-admin'); return; }
                import('../../utils.js').then(mod => { const { loadModule } = mod; const main = document.getElementById('main-content-area'); if (typeof loadModule === 'function' && main) loadModule('admin/hmo/claims.js', main, 'HMO Claims'); }).catch(err => console.error('Failed to load claims via fallback:', err));
            });
        }
    } catch (err) {
        console.warn('Could not attach click handlers to HMO dashboard cards:', err);
    }
}, 360);
