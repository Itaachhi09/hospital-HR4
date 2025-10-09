(function () {
    // Minimal UMD-like fallback for the Compensation module.
    // This provides simple versions of the main display functions used by the app
    // so that the UI is usable even if the ES module fails to load as a module.

    function getApiBase() {
        // Attempt to guess the same API_BASE_URL used by modules: <origin>/hospital-HR4/php/api/
        try {
            const basePath = window.location.pathname.replace(/\/[^/]*$/, '/');
            return window.location.origin + basePath + 'php/api/';
        } catch (e) {
            return window.location.origin + '/hospital-HR4/php/api/';
        }
    }

    function setTitleAndContainer(title, html) {
        const pageTitle = document.getElementById('page-title');
        const mainContent = document.getElementById('main-content-area');
        if (pageTitle) pageTitle.textContent = title;
        if (mainContent) mainContent.innerHTML = html;
        return { pageTitle, mainContent };
    }

    async function fetchJson(url) {
        try {
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const text = await resp.text();
            try { return JSON.parse(text); } catch (e) { return text; }
        } catch (e) {
            console.error('fetchJson error', url, e);
            throw e;
        }
    }

    // Simple renderer for Compensation Plans
    async function displayCompensationPlansSection_fallback() {
        const apiBase = getApiBase();
        const { mainContent } = setTitleAndContainer('Compensation Plans (Fallback)', '<div class="bg-white rounded-lg shadow-sm border p-6">\n  <div id="comp-plans-fallback" class="text-center py-8">Loading compensation plans...</div>\n</div>');
        if (!mainContent) return;
        try {
            const data = await fetchJson(apiBase + 'get_compensation_plans.php');
            const plans = Array.isArray(data) ? data : (data && data.data) ? data.data : [];
            const container = mainContent.querySelector('#comp-plans-fallback');
            if (!container) return;
            if (!plans || plans.length === 0) {
                container.innerHTML = '<p class="text-gray-600">No compensation plans found.</p>';
                return;
            }
            let html = '<table class="min-w-full divide-y divide-gray-200 border">';
            html += '<thead><tr><th class="px-4 py-2">ID</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Effective Date</th></tr></thead>';
            html += '<tbody>';
            for (const p of plans) {
                html += `<tr><td class="px-4 py-2">${p.PlanID ?? '-'}</td><td class="px-4 py-2 font-medium">${p.PlanName ?? '-'}</td><td class="px-4 py-2">${p.PlanType ?? '-'}</td><td class="px-4 py-2">${p.EffectiveDateFormatted ?? p.EffectiveDate ?? '-'}</td></tr>`;
            }
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            const container = mainContent.querySelector('#comp-plans-fallback');
            if (container) container.innerHTML = `<div class="text-red-600">Could not load compensation plans: ${String(e)}</div>`;
        }
    }

    // Simple renderer for Salary Adjustments
    async function displaySalaryAdjustmentsSection_fallback() {
        const apiBase = getApiBase();
        const { mainContent } = setTitleAndContainer('Salary Adjustments (Fallback)', '<div class="bg-white rounded-lg shadow-sm border p-6">\n  <div id="salary-adj-fallback" class="text-center py-8">Loading salary adjustments...</div>\n</div>');
        if (!mainContent) return;
        try {
            const data = await fetchJson(apiBase + 'get_salary_adjustments.php');
            const items = Array.isArray(data) ? data : (data && data.data) ? data.data : [];
            const container = mainContent.querySelector('#salary-adj-fallback');
            if (!container) return;
            if (!items || items.length === 0) {
                container.innerHTML = '<p class="text-gray-600">No salary adjustments found.</p>';
                return;
            }
            let html = '<table class="min-w-full divide-y divide-gray-200 border">';
            html += '<thead><tr><th class="px-4 py-2">ID</th><th class="px-4 py-2">Employee</th><th class="px-4 py-2">Effective Date</th><th class="px-4 py-2">New Salary</th></tr></thead>';
            html += '<tbody>';
            for (const a of items) {
                html += `<tr><td class="px-4 py-2">${a.AdjustmentID ?? '-'}</td><td class="px-4 py-2 font-medium">${a.EmployeeName ?? '-'}</td><td class="px-4 py-2">${a.AdjustmentDateFormatted ?? a.EffectiveDate ?? '-'}</td><td class="px-4 py-2">${a.NewBaseSalary ?? '-'}</td></tr>`;
            }
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            const container = mainContent.querySelector('#salary-adj-fallback');
            if (container) container.innerHTML = `<div class="text-red-600">Could not load salary adjustments: ${String(e)}</div>`;
        }
    }

    // Simple renderer for Incentives
    async function displayIncentivesSection_fallback() {
        const apiBase = getApiBase();
        const { mainContent } = setTitleAndContainer('Incentives (Fallback)', '<div class="bg-white rounded-lg shadow-sm border p-6">\n  <div id="incentives-fallback" class="text-center py-8">Loading incentives...</div>\n</div>');
        if (!mainContent) return;
        try {
            const data = await fetchJson(apiBase + 'get_incentives.php');
            const items = Array.isArray(data) ? data : (data && data.data) ? data.data : [];
            const container = mainContent.querySelector('#incentives-fallback');
            if (!container) return;
            if (!items || items.length === 0) {
                container.innerHTML = '<p class="text-gray-600">No incentive records found.</p>';
                return;
            }
            let html = '<table class="min-w-full divide-y divide-gray-200 border">';
            html += '<thead><tr><th class="px-4 py-2">ID</th><th class="px-4 py-2">Employee</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Amount</th></tr></thead>';
            html += '<tbody>';
            for (const it of items) {
                html += `<tr><td class="px-4 py-2">${it.IncentiveID ?? '-'}</td><td class="px-4 py-2 font-medium">${it.EmployeeName ?? '-'}</td><td class="px-4 py-2">${it.IncentiveType ?? '-'}</td><td class="px-4 py-2">${it.AmountFormatted ?? it.Amount ?? '-'}</td></tr>`;
            }
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (e) {
            const container = mainContent.querySelector('#incentives-fallback');
            if (container) container.innerHTML = `<div class="text-red-600">Could not load incentives: ${String(e)}</div>`;
        }
    }

    // Fallback stubs for more complex modules
    async function notAvailableFallback(title) {
        setTitleAndContainer(title + ' (Fallback)', `<div class="bg-white rounded-lg shadow-sm border p-6"><div class="text-gray-600">This module is temporarily unavailable in fallback mode.</div></div>`);
    }

    // Expose functions on window
    window.displayCompensationPlansSection = displayCompensationPlansSection_fallback;
    window.displaySalaryAdjustmentsSection = displaySalaryAdjustmentsSection_fallback;
    window.displayIncentivesSection = displayIncentivesSection_fallback;
    window.displaySalaryGradesSection = function() { return notAvailableFallback('Salary Grades'); };
    window.displayPayBandsSection = function() { return notAvailableFallback('Pay Bands'); };
    window.displayEmployeeMappingSection = function() { return notAvailableFallback('Employee Mapping'); };
    window.displayWorkflowsSection = function() { return notAvailableFallback('Workflows'); };
    window.displaySimulationToolsSection = function() { return notAvailableFallback('Simulation Tools'); };

    // Mark as fallback so main loader can detect it
    Object.defineProperty(window.displayCompensationPlansSection, '__isFallback', { value: true });
    Object.defineProperty(window.displaySalaryAdjustmentsSection, '__isFallback', { value: true });
    Object.defineProperty(window.displayIncentivesSection, '__isFallback', { value: true });

})();
