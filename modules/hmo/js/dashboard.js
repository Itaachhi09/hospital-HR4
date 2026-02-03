import { REST_API_URL } from '../../utils.js';

export async function renderHMODashboard(containerId='main-content-area'){
    const container = document.getElementById(containerId); if (!container) return;
    
    // Show loading state
    container.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="text-gray-500 mt-4">Loading HMO dashboard...</p>
            </div>
        </div>
    `;
    
    try{
        const res = await fetch(`${REST_API_URL}hmo/dashboard`, { credentials:'include' }); 
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const response = await res.json(); 
        const s = response.data || response.summary || {};
        
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Enhanced Header -->
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-4 rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold mb-1">HMO Dashboard</h2>
                            <p class="text-sm text-purple-100">Overview of health insurance benefits and claims</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="refresh-dashboard" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition duration-150 ease-in-out flex items-center space-x-2">
                                <i class="fas fa-sync-alt"></i>
                                <span>Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div class="px-6 py-6 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div id="hmo-active-providers-card" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition duration-150 ease-in-out" title="View Providers">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Active Providers</p>
                                    <p class="text-2xl font-bold text-gray-900">${s.total_providers||0}</p>
                                </div>
                                <div class="bg-purple-100 rounded-full p-3">
                                    <i class="fas fa-hospital text-purple-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div id="hmo-active-plans-card" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition duration-150 ease-in-out" title="View Plans">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Active Plans</p>
                                    <p class="text-2xl font-bold text-gray-900">${s.total_plans||0}</p>
                                </div>
                                <div class="bg-blue-100 rounded-full p-3">
                                    <i class="fas fa-file-medical text-blue-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div id="hmo-enrolled-employees-card" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition duration-150 ease-in-out" title="View Enrollments">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Enrolled Employees</p>
                                    <p class="text-2xl font-bold text-gray-900">${s.total_enrollments||0}</p>
                                </div>
                                <div class="bg-green-100 rounded-full p-3">
                                    <i class="fas fa-users text-green-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div id="hmo-pending-claims-card" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition duration-150 ease-in-out" title="View Pending Claims">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Pending Claims</p>
                                    <p class="text-2xl font-bold text-yellow-600">${s.pending_claims||0}</p>
                                </div>
                                <div class="bg-yellow-100 rounded-full p-3">
                                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Claims Summary -->
                <div class="px-6 py-4 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Claims Summary</h3>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-600">Approved:</span>
                            <span class="font-semibold text-green-600">${s.approved_claims||0}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-yellow-600"></i>
                            <span class="text-sm text-gray-600">Pending:</span>
                            <span class="font-semibold text-yellow-600">${s.pending_claims||0}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-times-circle text-red-600"></i>
                            <span class="text-sm text-gray-600">Denied:</span>
                            <span class="font-semibold text-red-600">${s.denied_claims||0}</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Monthly Claims Trend</h4>
                            <div style="height: 220px;">
                                <canvas id="hmo-monthly-claims-chart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Top Hospitals</h4>
                            <div style="height: 220px;">
                                <canvas id="hmo-top-hospitals-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Plan Utilization</h4>
                        <div style="height: 180px;">
                            <canvas id="hmo-plan-utilization-chart"></canvas>
                        </div>
                    </div>
                </div>

                ${( (s.total_providers||0) === 0 ) && ( (s.total_plans||0) === 0 ) ? `
                    <div class="mx-6 mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-info-circle text-yellow-600 text-xl"></i>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">No HMO Data Found</p>
                                <p class="text-xs text-yellow-700 mt-1">Import <code class="bg-yellow-100 px-1 rounded">database/hmo_schema_and_seed.sql</code> or add providers & plans from the HMO module.</p>
                            </div>
                        </div>
                    </div>
                `: ''}
            </div>
        `;
        // Wire refresh button
        document.getElementById('refresh-dashboard')?.addEventListener('click', () => renderHMODashboard(containerId));
        
        // draw charts after DOM updated
        setTimeout(()=>{ if (document.getElementById('hmo-monthly-claims-chart')) drawHMOCharts(); }, 250);

        // Attach click handlers to dashboard cards so they always work after render
        try {
            const attachCardHandlers = () => {
                try {
                    const providersCard = document.getElementById('hmo-active-providers-card');
                    if (providersCard) {
                        providersCard.replaceWith(providersCard.cloneNode(true));
                    }
                    const pCard = document.getElementById('hmo-active-providers-card');
                    if (pCard) pCard.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (typeof window.displayHMOProvidersSection === 'function') { window.displayHMOProvidersSection(); return; }
                        if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-providers'); return; }
                    });

                    const plansCard = document.getElementById('hmo-active-plans-card');
                    if (plansCard) {
                        plansCard.replaceWith(plansCard.cloneNode(true));
                    }
                    const plCard = document.getElementById('hmo-active-plans-card');
                    if (plCard) plCard.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (typeof window.displayHMOPlansSection === 'function') { window.displayHMOPlansSection(); return; }
                        if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-plans'); return; }
                    });

                    const enrollCard = document.getElementById('hmo-enrolled-employees-card');
                    if (enrollCard) {
                        enrollCard.replaceWith(enrollCard.cloneNode(true));
                    }
                    const enCard = document.getElementById('hmo-enrolled-employees-card');
                    if (enCard) enCard.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (typeof window.displayHMOEnrollmentsSection === 'function') { window.displayHMOEnrollmentsSection(); return; }
                        if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-enrollments'); return; }
                    });

                    const pendingCard = document.getElementById('hmo-pending-claims-card');
                    if (pendingCard) {
                        pendingCard.replaceWith(pendingCard.cloneNode(true));
                    }
                    const pdCard = document.getElementById('hmo-pending-claims-card');
                    if (pdCard) pdCard.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (typeof window.displayHMOClaimsApprovalSection === 'function') { window.displayHMOClaimsApprovalSection(); return; }
                        if (typeof window.navigateToSectionById === 'function') { window.navigateToSectionById('hmo-claims-admin'); return; }
                    });
                } catch (err) {
                    console.warn('Failed to attach HMO dashboard card handlers:', err);
                }
            };
            // Run attachment shortly after render to ensure DOM is ready
            setTimeout(attachCardHandlers, 300);
        } catch (e) {
            console.warn('HMO dashboard handler attachment failed:', e);
        }
    }catch(e){
        console.error(e); 
        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <div class="flex items-center space-x-3 text-red-600">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Error Loading Dashboard</h3>
                        <p class="text-sm text-red-500 mt-1">${e.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}
// chart bootstrapper
async function drawHMOCharts(){
    try{
        const base = `${REST_API_URL}hmo/dashboard`;
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
