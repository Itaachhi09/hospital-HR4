/**
 * Dashboard Module
 * Handles display of summary information and key metrics on the dashboard.
 * v1.4 - Added display for Recent Hires, Dept Distribution Chart, and Employee Quick Actions.
 * v1.3 - Added detailed console logging for data fetching.
 * v1.2 - Applied system color theme to dashboard cards and charts.
 * v1.1 - Added role-based dashboard views.
 */
import { LEGACY_API_URL } from '../utils.js';

// --- DOM Element References ---
let pageTitleElement;
let mainContentArea;
let dashboardSummaryContainer;
let dashboardChartsContainer; // Added for chart rendering
let dashboardQuickActionsContainer; // Added for employee quick actions

// Store chart instances to destroy them before re-rendering
let employeeStatusChartInstance = null;
let leaveRequestsChartInstance = null;
let departmentDistributionChartInstance = null; // New chart instance
let myLeaveSummaryChartInstance = null; // For employee

/**
 * Initializes common elements used by the dashboard module.
 */
function initializeDashboardElements() {
    // For modern dashboard, we don't need to find page-title as it's handled differently
    mainContentArea = document.getElementById('main-content-area');
    if (!mainContentArea) {
        console.error("Dashboard Module: main-content-area not found!");
        return false;
    }
    return true;
}

/**
 * Displays the appropriate dashboard based on the user's role.
 */
export async function displayDashboardSection() {
    console.log("[Display] Displaying Dashboard Section...");
    if (!initializeDashboardElements()) return;

    let user = window.currentUser;
    if (!user || !user.role_name) {
        mainContentArea.innerHTML = '<p class="text-red-500 p-4">Error: User role not found. Please login again.</p>';
        console.error("Dashboard Error: window.currentUser or window.currentUser.role_name is not defined.");
        return;
    }
    console.log("[Dashboard] Current user:", user);

    // For modern dashboard, we don't replace the entire content
    // Instead, we just update the data in the existing modern dashboard structure
    console.log("Modern dashboard is already displayed, updating data only...");
    
    // Find the modern dashboard containers
    dashboardSummaryContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4.gap-6');
    dashboardChartsContainer = document.querySelector('.grid.grid-cols-1.lg\\:grid-cols-2.gap-6');
    dashboardQuickActionsContainer = document.querySelector('.space-y-3');


    try {
        const apiUrl = `${LEGACY_API_URL}get_dashboard_summary.php?role=${encodeURIComponent(user.role_name)}`;
        console.log(`[Dashboard] Fetching summary data from: ${apiUrl}`);
        const response = await fetch(apiUrl, { credentials: 'include' });
        console.log(`[Dashboard] Raw response status: ${response.status}`);

        const summaryData = await handleApiResponse(response); 
        console.log("[Dashboard] Parsed summary data:", summaryData);

        if (summaryData.error) { 
            throw new Error(summaryData.error);
        }

        renderDashboardSummary(summaryData, user.role_name);
        if (user.role_name === 'Employee') {
            renderEmployeeQuickActions();
        } else {
            if(dashboardQuickActionsContainer) dashboardQuickActionsContainer.innerHTML = ''; // Clear if not employee
        }
        renderCharts(summaryData.charts || {}, user.role_name);
    } catch (error) {
        console.error('Error loading dashboard summary:', error);
        if (dashboardSummaryContainer) {
            dashboardSummaryContainer.innerHTML = `<p class="text-red-500 p-4 text-center">Could not load dashboard summary. ${error.message}</p>`;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Dashboard Error',
                text: `Failed to load dashboard data: ${error.message}`,
                confirmButtonColor: '#4727ffff'
            });
        }
    }
}

/**
 * Renders the summary cards on the dashboard.
 */
function renderDashboardSummary(summaryData, userRole) {
    if (!dashboardSummaryContainer) {
        console.log("Dashboard summary container not found, skipping summary update");
        return;
    }
    if (!summaryData || typeof summaryData !== 'object') {
        console.error("[Render] renderDashboardSummary: summaryData is invalid or null.", summaryData);
        return;
    }

    // Update the existing modern dashboard stat cards with real data
    const totalEmployeesEl = document.getElementById('total-employees');
    const activeEmployeesEl = document.getElementById('active-employees');
    const pendingLeaveEl = document.getElementById('pending-leave');
    const recentHiresEl = document.getElementById('recent-hires');

    if (totalEmployeesEl) totalEmployeesEl.textContent = summaryData.total_employees || '0';
    if (activeEmployeesEl) activeEmployeesEl.textContent = summaryData.active_employees || '0';
    if (pendingLeaveEl) pendingLeaveEl.textContent = summaryData.pending_leave_requests || '0';
    if (recentHiresEl) recentHiresEl.textContent = summaryData.recent_hires_last_30_days || '0';

    console.log("Updated modern dashboard stat cards with real data");
}

/**
 * Renders quick action buttons for the Employee dashboard.
 */
function renderEmployeeQuickActions() {
    if (!dashboardQuickActionsContainer) {
        console.error("Dashboard Quick Actions Container not found for Employee.");
        return;
    }

    dashboardQuickActionsContainer.innerHTML = `
        <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
            <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Quick Actions</h3>
            <div class="flex flex-wrap gap-4">
                <button id="quick-action-view-profile" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-user"></i>
                    <span>View Profile</span>
                </button>
                <button id="quick-action-submit-leave" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Submit Leave</span>
                </button>
                <button id="quick-action-submit-claim" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-receipt"></i>
                    <span>Submit Claim</span>
                </button>
                 <button id="quick-action-view-payslips" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-file-invoice"></i>
                    <span>View Payslips</span>
                </button>
            </div>
        </div>
    `;

    // Add event listeners for the new buttons
    document.getElementById('quick-action-view-profile')?.addEventListener('click', () => {
        const viewProfileLink = document.getElementById('view-profile-link');
        if (viewProfileLink) {
            viewProfileLink.click();
        } else {
            console.warn("Quick Action: View Profile link not found.");
        }
    });

    document.getElementById('quick-action-submit-leave')?.addEventListener('click', () => {
        const leaveLink = document.getElementById('leave-requests-link'); 
        if (leaveLink) {
            leaveLink.click(); 
        } else {
            console.warn("Quick Action: Leave Requests link not found for navigation.");
        }
    });

    document.getElementById('quick-action-submit-claim')?.addEventListener('click', () => {
        const claimLink = document.getElementById('submit-claim-link'); 
        if (claimLink) {
            claimLink.click();
        } else {
            console.warn("Quick Action: Submit Claim link not found for navigation.");
        }
    });
     document.getElementById('quick-action-view-payslips')?.addEventListener('click', () => {
        const payslipsLink = document.getElementById('payslips-link'); 
        if (payslipsLink) {
            payslipsLink.click();
        } else {
            console.warn("Quick Action: View Payslips link not found for navigation.");
        }
    });
}


/**
 * Helper function to create HTML for a summary card.
 */
function createSummaryCard(title, value, iconClass, bgColor, textColor, iconColor, valueColor) {
    return `
        <div class="${bgColor} p-6 rounded-lg shadow-lg border border-[#4727ff] hover:shadow-xl transition-shadow duration-300 ease-in-out">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium ${textColor} uppercase tracking-wider">${title}</p>
                    <p class="text-3xl font-bold ${valueColor}">${value}</p>
                </div>
                <div class="p-3 bg-white bg-opacity-20 rounded-full">
                    <i class="fas ${iconClass} ${iconColor} text-2xl"></i>
                </div>
            </div>
        </div>
    `;
}

/**
 * Renders charts on the dashboard.
 */
function renderCharts(chartData, userRole) {
    if (!dashboardChartsContainer) { // Check if the container exists
        console.log("Dashboard Charts Container not found, skipping chart updates");
        return;
    }

    // For modern dashboard, we don't replace the entire charts container
    // Instead, we update the existing charts with real data
    console.log("Updating modern dashboard charts with real data");

    // Destroy previous chart instances if they exist
    if (employeeStatusChartInstance) employeeStatusChartInstance.destroy();
    if (leaveRequestsChartInstance) leaveRequestsChartInstance.destroy();
    if (departmentDistributionChartInstance) departmentDistributionChartInstance.destroy();
    if (myLeaveSummaryChartInstance) myLeaveSummaryChartInstance.destroy();
    employeeStatusChartInstance = null;
    leaveRequestsChartInstance = null;
    departmentDistributionChartInstance = null;
    myLeaveSummaryChartInstance = null;


    const primaryChartColor = '#4727ff'; // blue accent from UI
    const secondaryChartColor = '#594423'; // dark brown
    const tertiaryChartColor = '#F7E6CA'; // beige
    const borderColor = '#4E3B2A'; // dark brown border
    const altColor1 = '#C7955C'; // medium brown
    const altColor2 = '#9C6644'; // lighter brown


    if (!chartData || typeof chartData !== 'object') {
        console.warn("[Render] renderCharts: chartData is invalid or null.", chartData);
        dashboardChartsContainer.innerHTML = '<p class="col-span-full text-center text-gray-500 py-4">Chart data is unavailable.</p>';
        return;
    }

    if (userRole === 'System Admin' || userRole === 'HR Admin') {
        // Update the existing modern dashboard charts with real data
        const departmentChart = document.getElementById('departmentChart');
        const hiresTrendChart = document.getElementById('hiresTrendChart');
        
        // Update Department Distribution Chart if it exists
        if (departmentChart && chartData.employee_status_distribution && chartData.employee_status_distribution.data) {
            // Update the existing chart with real data
            if (window.dashboardCharts && window.dashboardCharts.department) {
                window.dashboardCharts.department.data.datasets[0].data = chartData.employee_status_distribution.data;
                window.dashboardCharts.department.data.labels = chartData.employee_status_distribution.labels || ['Active', 'Inactive'];
                window.dashboardCharts.department.update();
            }
        }
        
        // Update Hires Trend Chart if it exists
        if (hiresTrendChart && chartData.hires_trend && chartData.hires_trend.data) {
            // Update the existing chart with real data
            if (window.dashboardCharts && window.dashboardCharts.hiresTrend) {
                window.dashboardCharts.hiresTrend.data.datasets[0].data = chartData.hires_trend.data;
                window.dashboardCharts.hiresTrend.data.labels = chartData.hires_trend.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                window.dashboardCharts.hiresTrend.update();
            }
        }

        console.log("Updated modern dashboard charts with real data");
    } else if (userRole === 'Employee') {
        // For employee role, we can add specific chart updates here if needed
        console.log("Employee role - no specific chart updates needed for modern dashboard");
    }
}

/**
 * Handles API response, checking status and parsing JSON.
 */
async function handleApiResponse(response) {
    const contentType = response.headers.get("content-type");
    let data;

    const rawText = await response.text().catch(e => {
        console.error("[HandleAPIResponse] Error reading response text:", e);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}. Failed to read response body.`);
        }
        return "[[Failed to read response body]]"; 
    });
    console.log(`[HandleAPIResponse] Raw response text (Status ${response.status}):`, rawText.substring(0, 500) + (rawText.length > 500 ? "..." : ""));

    if (!response.ok) {
        let errorPayload = { error: `HTTP error! Status: ${response.status}` };
        if (contentType && contentType.includes("application/json")) {
            try {
                data = JSON.parse(rawText); 
                errorPayload.error = data.error || errorPayload.error;
                errorPayload.details = data.details; 
            } catch (jsonError) {
                console.error("[HandleAPIResponse] Failed to parse JSON error response:", jsonError);
                errorPayload.error += ` (Non-JSON error response received, see raw text log)`;
            }
        } else {
             errorPayload.error = `Server returned non-JSON error (Status: ${response.status}). See raw text log.`;
        }
        const error = new Error(errorPayload.error);
        error.details = errorPayload.details;
        throw error;
    }

    try {
        if (response.status === 204) { 
             console.log("[HandleAPIResponse] Received 204 No Content.");
             return { message: "Operation completed successfully (No Content)." };
        }
        if (!rawText || !rawText.trim()) {
             console.warn("[HandleAPIResponse] Received successful status, but response body was empty or whitespace.");
             return {}; 
        }
        try {
            data = JSON.parse(rawText);
            console.log("[HandleAPIResponse] Successfully parsed JSON data:", data);
            return data;
        } catch (jsonError) {
            console.error("[HandleAPIResponse] Failed to parse successful response as JSON:", jsonError);
            throw new Error("Received successful status, but failed to parse response as JSON. See raw text log.");
        }
    } catch (e) { 
        console.error("[HandleAPIResponse] Error processing successful response body:", e);
        throw e; 
    }
}
