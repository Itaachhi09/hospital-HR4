import { API_BASE_URL } from '../../utils.js';

export async function renderHMODashboard(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    try{
    const res = await fetch(`${API_BASE_URL}hmo_dashboard.php`, { credentials:'include' }); const data = await res.json(); const s = data.summary||{};
        container.innerHTML = `
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-4">HMO Dashboard</h2>
                <div class="grid grid-cols-4 gap-4">
                    <div class="p-4 bg-white rounded shadow">Active Providers<br/><strong>${s.total_active_providers||s.providers||0}</strong></div>
                    <div class="p-4 bg-white rounded shadow">Active Plans<br/><strong>${s.total_active_plans||s.plans||0}</strong></div>
                    <div class="p-4 bg-white rounded shadow">Enrolled Employees<br/><strong>${s.total_enrolled_employees||s.active_enrollments||0}</strong></div>
                    <div class="p-4 bg-white rounded shadow">Pending Claims<br/><strong>${(s.claims&&s.claims.pending)||s.pending_claims||0}</strong></div>
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
