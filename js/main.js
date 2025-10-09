/**
 * HR Management System - Main JavaScript Entry Point
 * Centralized app initialization, module loader bindings and sidebar wiring.
 * Session-based initialization; requires valid server-side session.
 */

// Import base configuration
import { API_BASE_URL, BASE_URL } from './config.js';

// Import the module loader from utils
import { loadModule } from './utils.js';

// Initialize all section display functions
function initializeSectionDisplayFunctions() {
    const mainContentArea = document.getElementById('main-content-area');
    const sectionDisplayFunctions = {
        displayAnalyticsDashboardsSection: async () => {
            // Prefer the modern HR analytics dashboard module. If import fails, fall back to legacy analytics module.
            const mainContentArea = document.getElementById('main-content-area');
            try {
                const mod = await import('./analytics/hr_analytics_dashboard.js');
                if (mod && typeof mod.displayHRAnalyticsDashboard === 'function') {
                    await mod.displayHRAnalyticsDashboard();
                    return;
                }
            } catch (err) {
                console.warn('Failed to load hr_analytics_dashboard.js, falling back to legacy analytics:', err);
            }

            // Fallback: load legacy analytics module
            try {
                await loadModule('analytics/analytics.js', mainContentArea, 'Analytics Dashboard');
            } catch (fallbackErr) {
                console.error('Failed to load legacy analytics module as fallback:', fallbackErr);
                if (mainContentArea) mainContentArea.innerHTML = '<p class="text-red-500 p-4">Failed to load Analytics Dashboard. See console for details.</p>';
            }
        },
        displayEmployeeSection: async () => {
            await loadModule('core_hr/employees.js', mainContentArea, 'Employee Management');
        },
        displayDocumentsSection: async () => {
            await loadModule('core_hr/documents.js', mainContentArea, 'Document Management');
        },
        displayOrgStructureSection: async () => {
            await loadModule('core_hr/org_structure.js', mainContentArea, 'Organization Structure');
        },
        displayRoleAccessSection: async () => {
            await loadModule('core_hr/role_access.js', mainContentArea, 'Role & Access');
        },
        displayAttendanceSection: async () => {
            await loadModule('time_attendance/attendance.js', mainContentArea, 'Attendance Records');
        },
        displayTimesheetsSection: async () => {
            await loadModule('time_attendance/timesheets.js', mainContentArea, 'Timesheets');
        },
        displaySchedulesSection: async () => {
            await loadModule('time_attendance/schedules.js', mainContentArea, 'Schedules');
        },
        displayShiftsSection: async () => {
            await loadModule('time_attendance/shifts.js', mainContentArea, 'Shifts');
        },
        displayPayrollRunsSection: async () => {
            await loadModule('payroll/payroll_runs.js', mainContentArea, 'Payroll Runs');
        },
        displaySalariesSection: async () => {
            await loadModule('payroll/salaries.js', mainContentArea, 'Salaries');
        },
        displayBonusesSection: async () => {
            await loadModule('payroll/bonuses.js', mainContentArea, 'Bonuses');
        },
        displayDeductionsSection: async () => {
            await loadModule('payroll/deductions.js', mainContentArea, 'Deductions');
        },
        displayPayslipsSection: async () => {
            await loadModule('payroll/payslips.js', mainContentArea, 'Payslips');
        },
        displaySubmitClaimSection: async () => {
            await loadModule('claims/claims.js', mainContentArea, 'Submit Claim');
        },
        displayMyClaimsSection: async () => {
            await loadModule('claims/claims.js', mainContentArea, 'My Claims');
        },
        displayClaimsApprovalSection: async () => {
            await loadModule('claims/claims.js', mainContentArea, 'Claims Approval');
        },
        displayClaimTypesAdminSection: async () => {
            await loadModule('claims/claims.js', mainContentArea, 'Claim Types');
        },
        displayLeaveRequestsSection: async () => {
            await loadModule('leave/leave.js', mainContentArea, 'Leave Requests');
        },
        displayLeaveBalancesSection: async () => {
            await loadModule('leave/leave.js', mainContentArea, 'Leave Balances');
        },
        displayLeaveTypesAdminSection: async () => {
            await loadModule('leave/leave.js', mainContentArea, 'Leave Types');
        },
        displayCompensationPlansSection: async () => {
            try {
                const mod = await import('./compensation/compensation.js');
                if (mod && typeof mod.displayCompensationPlansSection === 'function') {
                    await mod.displayCompensationPlansSection();
                } else {
                    throw new Error('displayCompensationPlansSection export not found in compensation module');
                }
            } catch (err) {
                console.error('Failed to load/display Compensation Plans module:', err);
                throw err;
            }
        },
        displaySalaryAdjustmentsSection: async () => {
            try {
                const mod = await import('./compensation/compensation.js');
                if (mod && typeof mod.displaySalaryAdjustmentsSection === 'function') {
                    await mod.displaySalaryAdjustmentsSection();
                } else {
                    throw new Error('displaySalaryAdjustmentsSection export not found in compensation module');
                }
            } catch (err) {
                console.error('Failed to load/display Salary Adjustments module:', err);
                throw err;
            }
        },
        displayIncentivesSection: async () => {
            try {
                const mod = await import('./compensation/compensation.js');
                if (mod && typeof mod.displayIncentivesSection === 'function') {
                    await mod.displayIncentivesSection();
                } else {
                    throw new Error('displayIncentivesSection export not found in compensation module');
                }
            } catch (err) {
                console.error('Failed to load/display Incentives module:', err);
                throw err;
            }
        },
        displayAnalyticsDashboardsSection: async () => {
            // Use the integrated analytics dashboard with reports functionality
            await loadModule('analytics/analytics.js', mainContentArea, 'Analytics Dashboard');
        },
        displayAnalyticsReportsSection: async () => {
            // Redirect to the integrated analytics dashboard with reports functionality
            await loadModule('analytics/analytics.js', mainContentArea, 'Analytics Dashboard');
        },
        displayAnalyticsMetricsSection: async () => {
            await loadModule('analytics/analytics.js', mainContentArea, 'Analytics Metrics');
        },
        displayHMOProvidersSection: async () => {
            await loadModule('admin/hmo/providers.js', mainContentArea, 'HMO Providers');
        },
        displayHMOPlansSection: async () => {
            await loadModule('admin/hmo/plans.js', mainContentArea, 'HMO Plans');
        },
        displayHMOEnrollmentsSection: async () => {
            await loadModule('admin/hmo/enrollments.js', mainContentArea, 'HMO Enrollments');
        },
        displayHMOClaimsApprovalSection: async () => {
            await loadModule('admin/hmo/claims.js', mainContentArea, 'HMO Claims');
        },
        displayHMODashboardSection: async () => {
            await loadModule('admin/hmo/dashboard.js', mainContentArea, 'HMO Dashboard');
        },
        displayEmployeeHMOSection: async () => {
            await loadModule('employee/hmo.js', mainContentArea, 'My HMO Benefits');
        },
        displayEmployeeHMOClaimsSection: async () => {
            await loadModule('admin/hmo/claims.js', mainContentArea, 'My HMO Claims');
        },
        displaySubmitHMOClaimSection: async () => {
            await loadModule('employee/hmo.js', mainContentArea, 'Submit HMO Claim');
        }
    };

    // Attach all functions to window
    for (const [funcName, func] of Object.entries(sectionDisplayFunctions)) {
        window[funcName] = func;
    }
}

// Export only what's needed
export { initializeSectionDisplayFunctions };
// Admin
import { displayUserManagementSection } from './admin/user_management.js';
// User Profile
import { displayUserProfileSection } from './profile/profile.js';
// --- Import Notification Functions ---
import { initializeNotificationSystem, stopNotificationFetching, onNotificationDropdownOpen, onNotificationDropdownClose } from './notifications/notifications.js';


// --- Global Variables ---
window.currentUser = null;

// --- Wait for the DOM to be fully loaded ---
// Initialize the application
// Function to initialize application
export async function initializeApp() {
    console.log("Initializing HR System JS...");

    const runInit = async () => {
    console.log("DOM fully loaded and parsed. Initializing HR System JS (session-based)...");

    // Global handler for unhandled promise rejections to surface module loading/init errors
    window.addEventListener('unhandledrejection', (ev) => {
        console.error('Unhandled promise rejection:', ev.reason);
        console.error('Stack trace:', ev.reason?.stack);
        try {
            const mainContentArea = document.getElementById('main-content-area');
            if (mainContentArea) {
                mainContentArea.innerHTML = `<div class="p-6 text-red-600">An unexpected error occurred: ${String(ev.reason && ev.reason.message ? ev.reason.message : ev.reason)}</div>`;
            }
        } catch (e) { console.error('Failed to render unhandled rejection to UI', e); }
    });

    // Global handler for JavaScript errors
    window.addEventListener('error', (ev) => {
        console.error('JavaScript error:', ev.error);
        console.error('Error message:', ev.message);
        console.error('Error filename:', ev.filename);
        console.error('Error line:', ev.lineno);
        console.error('Error column:', ev.colno);
        console.error('Error stack:', ev.error?.stack);
        try {
            const mainContentArea = document.getElementById('main-content-area');
            if (mainContentArea) {
                mainContentArea.innerHTML = `<div class="p-6 text-red-600">
                    <h3 class="font-bold text-lg mb-2">An unexpected error occurred:</h3>
                    <p class="mb-2"><strong>Message:</strong> ${String(ev.message)}</p>
                    <p class="mb-2"><strong>File:</strong> ${ev.filename}</p>
                    <p class="mb-2"><strong>Line:</strong> ${ev.lineno}</p>
                    <p class="text-sm text-gray-600">Check the browser console for more details.</p>
                </div>`;
            }
        } catch (e) { console.error('Failed to render error to UI', e); }
    });

    // Define fallback functions IMMEDIATELY to prevent ReferenceError
    console.log('Setting up fallback display functions...');
    
    // Core HR modules
    window.displayEmployeeSection = async () => {
        console.log('Loading Employee Directory module...');
        await loadModule('core_hr/employees.js', document.getElementById('main-content-area'), 'Employee Directory');
    };
    window.displayDocumentsSection = async () => {
        console.log('Loading Document Viewer module...');
        await loadModule('core_hr/documents.js', document.getElementById('main-content-area'), 'Document Viewer');
    };
    window.displayOrgStructureSection = async () => {
        console.log('Loading Organizational Structure module...');
        await loadModule('core_hr/org_structure.js', document.getElementById('main-content-area'), 'Organizational Structure');
    };
    window.displayRoleAccessSection = async () => {
        console.log('Loading Role & Access module...');
        await loadModule('core_hr/role_access.js', document.getElementById('main-content-area'), 'Role & Access');
    };
    
    // Time & Attendance modules
    window.displayAttendanceSection = async () => {
        console.log('Loading Attendance module...');
        await loadModule('time_attendance/attendance.js', document.getElementById('main-content-area'), 'Attendance Records');
    };
    window.displayTimesheetsSection = async () => {
        console.log('Loading Timesheets module...');
        await loadModule('time_attendance/timesheets.js', document.getElementById('main-content-area'), 'Timesheets');
    };
    window.displaySchedulesSection = async () => {
        console.log('Loading Schedules module...');
        await loadModule('time_attendance/schedules.js', document.getElementById('main-content-area'), 'Schedules');
    };
    window.displayShiftsSection = async () => {
        console.log('Loading Shifts module...');
        await loadModule('time_attendance/shifts.js', document.getElementById('main-content-area'), 'Shifts');
    };
    
    // Payroll modules
    window.displayPayrollRunsSection = async () => {
        console.log('Loading Payroll Runs module...');
        await loadModule('payroll/payroll_runs.js', document.getElementById('main-content-area'), 'Payroll Runs');
    };
    window.displaySalariesSection = async () => {
        console.log('Loading Salaries module...');
        await loadModule('payroll/salaries.js', document.getElementById('main-content-area'), 'Salaries');
    };
    window.displayBonusesSection = async () => {
        console.log('Loading Bonuses module...');
        await loadModule('payroll/bonuses.js', document.getElementById('main-content-area'), 'Bonuses');
    };
    window.displayDeductionsSection = async () => {
        console.log('Loading Deductions module...');
        await loadModule('payroll/deductions.js', document.getElementById('main-content-area'), 'Deductions');
    };
    window.displayPayslipsSection = async () => {
        console.log('Loading Payslips module...');
        await loadModule('payroll/payslips.js', document.getElementById('main-content-area'), 'Payslips');
    };
    
    // Claims modules
    window.displaySubmitClaimSection = async () => {
        console.log('Loading Submit Claim module...');
        await loadModule('claims/claims.js', document.getElementById('main-content-area'), 'Submit Claim');
    };
    window.displayMyClaimsSection = async () => {
        console.log('Loading My Claims module...');
        await loadModule('claims/claims.js', document.getElementById('main-content-area'), 'My Claims');
    };
    window.displayClaimsApprovalSection = async () => {
        console.log('Loading Claims Approval module...');
        await loadModule('claims/claims.js', document.getElementById('main-content-area'), 'Claims Approval');
    };
    window.displayClaimTypesAdminSection = async () => {
        console.log('Loading Claim Types Admin module...');
        await loadModule('claims/claims.js', document.getElementById('main-content-area'), 'Claim Types');
    };
    
    // Leave modules
    window.displayLeaveRequestsSection = async () => {
        console.log('Loading Leave Requests module...');
        await loadModule('leave/leave.js', document.getElementById('main-content-area'), 'Leave Requests');
    };
    window.displayLeaveBalancesSection = async () => {
        console.log('Loading Leave Balances module...');
        await loadModule('leave/leave.js', document.getElementById('main-content-area'), 'Leave Balances');
    };
    window.displayLeaveTypesAdminSection = async () => {
        console.log('Loading Leave Types Admin module...');
        await loadModule('leave/leave.js', document.getElementById('main-content-area'), 'Leave Types');
    };
    
    // Compensation Planning modules
    window.displayCompensationPlansSection = async () => {
        console.log('Loading Compensation Plans module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displayCompensationPlansSection === 'function') {
                await mod.displayCompensationPlansSection();
            } else {
                throw new Error('displayCompensationPlansSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Compensation Plans module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) {
                console.warn('fetchModuleDebug failed:', dbg);
            }
            // Attempt UMD fallback loader
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displayCompensationPlansSection === 'function') {
                    console.info('Using UMD fallback for Compensation Plans');
                    await window.displayCompensationPlansSection();
                    return;
                }
            } catch (fbErr) {
                console.warn('UMD fallback failed:', fbErr);
            }
            throw err;
        }
    };
    window.displaySalaryAdjustmentsSection = async () => {
        console.log('Loading Salary Adjustments module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displaySalaryAdjustmentsSection === 'function') {
                await mod.displaySalaryAdjustmentsSection();
            } else {
                throw new Error('displaySalaryAdjustmentsSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Salary Adjustments module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displaySalaryAdjustmentsSection === 'function') {
                    console.info('Using UMD fallback for Salary Adjustments');
                    await window.displaySalaryAdjustmentsSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    window.displayIncentivesSection = async () => {
        console.log('Loading Incentives module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displayIncentivesSection === 'function') {
                await mod.displayIncentivesSection();
            } else {
                throw new Error('displayIncentivesSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Incentives module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displayIncentivesSection === 'function') {
                    console.info('Using UMD fallback for Incentives');
                    await window.displayIncentivesSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    
    // New Compensation Planning modules
    window.displaySalaryGradesSection = async () => {
        console.log('Loading Salary Grades module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displaySalaryGradesSection === 'function') {
                await mod.displaySalaryGradesSection();
            } else {
                throw new Error('displaySalaryGradesSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Salary Grades module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displaySalaryGradesSection === 'function') {
                    console.info('Using UMD fallback for Salary Grades');
                    await window.displaySalaryGradesSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    window.displayPayBandsSection = async () => {
        console.log('Loading Pay Bands module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displayPayBandsSection === 'function') {
                await mod.displayPayBandsSection();
            } else {
                throw new Error('displayPayBandsSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Pay Bands module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displayPayBandsSection === 'function') {
                    console.info('Using UMD fallback for Pay Bands');
                    await window.displayPayBandsSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    window.displayEmployeeMappingSection = async () => {
        console.log('Loading Employee Mapping module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displayEmployeeMappingSection === 'function') {
                await mod.displayEmployeeMappingSection();
            } else {
                throw new Error('displayEmployeeMappingSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Employee Mapping module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displayEmployeeMappingSection === 'function') {
                    console.info('Using UMD fallback for Employee Mapping');
                    await window.displayEmployeeMappingSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    window.displayWorkflowsSection = async () => {
        console.log('Loading Workflows module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displayWorkflowsSection === 'function') {
                await mod.displayWorkflowsSection();
            } else {
                throw new Error('displayWorkflowsSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Workflows module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displayWorkflowsSection === 'function') {
                    console.info('Using UMD fallback for Workflows');
                    await window.displayWorkflowsSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    window.displaySimulationToolsSection = async () => {
        console.log('Loading Simulation Tools module...');
        try {
            const mod = await import('./compensation/compensation.js');
            if (mod && typeof mod.displaySimulationToolsSection === 'function') {
                await mod.displaySimulationToolsSection();
            } else {
                throw new Error('displaySimulationToolsSection not exported by compensation module');
            }
        } catch (err) {
            console.error('Error loading Simulation Tools module:', err);
            try {
                await fetchModuleDebug('js/compensation/compensation.js');
            } catch (dbg) { console.warn('fetchModuleDebug failed:', dbg); }
            try {
                await loadUmdFallback('/hospital-HR4/js/compensation/compensation.umd.js');
                if (typeof window.displaySimulationToolsSection === 'function') {
                    console.info('Using UMD fallback for Simulation Tools');
                    await window.displaySimulationToolsSection();
                    return;
                }
            } catch (fbErr) { console.warn('UMD fallback failed:', fbErr); }
            throw err;
        }
    };
    
    // Analytics modules
    window.displayAnalyticsDashboardsSection = async () => {
        console.log('Loading Analytics Dashboards module...');
        // Load new comprehensive HR Analytics Dashboard
        try {
            const hrAnalyticsMod = await import('./analytics/hr_analytics_dashboard.js');
            if (hrAnalyticsMod?.displayHRAnalyticsDashboard) {
                await hrAnalyticsMod.displayHRAnalyticsDashboard();
            } else {
                // Fallback to old analytics
                await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Dashboards');
            }
        } catch (err) {
            console.error('Failed to load HR Analytics Dashboard:', err);
            // Fallback to old analytics
            await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Dashboards');
        }
    };
    window.displayAnalyticsReportsSection = async () => {
        console.log('Loading Analytics Reports module...');
        try {
            const hrReportsMod = await import('./analytics/hr_reports_dashboard.js');
            if (hrReportsMod?.displayHRReportsDashboard) {
                await hrReportsMod.displayHRReportsDashboard();
            } else {
                // Fallback to old analytics
                await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Reports');
            }
        } catch (err) {
            console.error('Failed to load HR Reports Dashboard:', err);
            // Fallback to old analytics
            await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Reports');
        }
    };
    window.displayAnalyticsMetricsSection = async () => {
        console.log('Loading Analytics Metrics module...');
        try {
            const hrMetricsMod = await import('./analytics/hr_analytics_metrics_dashboard.js');
            if (hrMetricsMod?.displayHRAnalyticsMetricsDashboard) {
                await hrMetricsMod.displayHRAnalyticsMetricsDashboard();
            } else {
                // Fallback to old analytics
                await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Metrics');
            }
        } catch (err) {
            console.error('Failed to load HR Metrics Dashboard:', err);
            // Fallback to old analytics
            await loadModule('analytics/analytics.js', document.getElementById('main-content-area'), 'Analytics Metrics');
        }
    };
    
    // HMO modules
    window.displayHMOProvidersSection = async () => {
        console.log('Loading HMO Providers module...');
        await loadModule('admin/hmo/providers.js', document.getElementById('main-content-area'), 'HMO Providers');
    };
    window.displayHMOPlansSection = async () => {
        console.log('Loading HMO Plans module...');
        await loadModule('admin/hmo/plans.js', document.getElementById('main-content-area'), 'HMO Plans');
    };
    window.displayHMOEnrollmentsSection = async () => {
        console.log('Loading HMO Enrollments module...');
        await loadModule('admin/hmo/enrollments.js', document.getElementById('main-content-area'), 'HMO Enrollments');
    };
    window.displayHMOClaimsApprovalSection = async () => {
        console.log('Loading HMO Claims Approval module...');
        await loadModule('admin/hmo/claims.js', document.getElementById('main-content-area'), 'HMO Claims Approval');
    };
    window.displayHMODashboardSection = async () => {
        console.log('Loading HMO Dashboard module...');
        await loadModule('admin/hmo/dashboard.js', document.getElementById('main-content-area'), 'HMO Dashboard');
    };
    window.displayEmployeeHMOSection = async () => {
        console.log('Loading Employee HMO module...');
        await loadModule('employee/hmo.js', document.getElementById('main-content-area'), 'Employee HMO');
    };
    window.displayEmployeeHMOClaimsSection = async () => {
        console.log('Loading Employee HMO Claims module...');
        // Employee claims are shown in the main HMO view
        await loadModule('employee/hmo.js', document.getElementById('main-content-area'), 'My HMO Claims');
    };
    window.displaySubmitHMOClaimSection = async () => {
        console.log('Loading Submit HMO Claim module...');
        // Claim submission is part of the employee HMO module
        await loadModule('employee/hmo.js', document.getElementById('main-content-area'), 'Submit HMO Claim');
    };
    
    // Admin modules
    window.displayUserManagementSection = async () => {
        console.log('Loading User Management module...');
        await loadModule('admin/user_management.js', document.getElementById('main-content-area'), 'User Management');
    };
    window.displayUserProfileSection = async () => {
        console.log('Loading User Profile module...');
        await loadModule('profile/profile.js', document.getElementById('main-content-area'), 'User Profile');
    };
    window.displayNotificationsSection = async () => {
        console.log('Loading Notifications module...');
        await loadModule('notifications/notifications.js', document.getElementById('main-content-area'), 'Notifications');
    };
    
    console.log('Fallback display functions set up successfully');

    // Ensure a modal container exists; some modules expect #modalContainer to be present
    try {
        if (!document.getElementById('modalContainer')) {
            const modalDiv = document.createElement('div');
            modalDiv.id = 'modalContainer';
            // Keep it at the end of body so fixed overlays position correctly
            document.body.appendChild(modalDiv);
            console.log('Created fallback #modalContainer appended to document.body');
        }
    } catch (e) { console.warn('Could not create fallback modalContainer', e); }
    
    // Set initial dashboard content immediately
    const initialPageTitle = document.getElementById('page-title');
    const initialMainContent = document.getElementById('main-content-area');
    if (initialPageTitle) {
        initialPageTitle.textContent = 'Dashboard';
    } else {
        console.log('Page title element not found during initialization');
    }
    // Don't replace the modern dashboard content - it's already there
    // The modern dashboard HTML is already present in admin_landing.php
    console.log('Modern dashboard content preserved');

    // --- DOM Elements ---
    // const loginContainer = document.getElementById('login-container'); // No longer a critical element for this flow
    const appContainer = document.getElementById('app-container');
    const mainContentArea = document.getElementById('main-content-area');
    let pageTitleElement = document.getElementById('page-title');
    if (!pageTitleElement) {
        // Retry after a short delay
        setTimeout(() => {
            pageTitleElement = document.getElementById('page-title');
            if (!pageTitleElement) {
                console.log('Page title element still not found after retry');
            }
        }, 100);
    }
    const timesheetModal = document.getElementById('timesheet-detail-modal');
    const modalOverlayTs = document.getElementById('modal-overlay-ts');
    const modalCloseBtnTs = document.getElementById('modal-close-btn-ts');
    const userDisplayName = document.getElementById('user-display-name');
    const userDisplayRole = document.getElementById('user-display-role');

    const userProfileButton = document.getElementById('user-profile-button');
    const userProfileDropdown = document.getElementById('user-profile-dropdown');
    const userProfileArrow = document.getElementById('user-profile-arrow');
    const viewProfileLink = document.getElementById('view-profile-link');
    const logoutLinkNav = document.getElementById('logout-link-nav'); 
    const notificationBellButton = document.getElementById('notification-bell-button');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationDot = document.getElementById('notification-dot');
    const notificationListElement = document.getElementById('notification-list');

    const sidebarItems = {
        dashboard: document.getElementById('dashboard-link'),
        employees: document.getElementById('employees-link'),
        documents: document.getElementById('documents-link'),
        orgStructure: document.getElementById('org-structure-link'),
        roleAccess: document.getElementById('role-access-link'),
        attendance: document.getElementById('attendance-link'),
        timesheets: document.getElementById('timesheets-link'),
        schedules: document.getElementById('schedules-link'),
        shifts: document.getElementById('shifts-link'),
        payrollRuns: document.getElementById('payroll-runs-link'),
        salaries: document.getElementById('salaries-link'),
        bonuses: document.getElementById('bonuses-link'),
        deductions: document.getElementById('deductions-link'),
        payslips: document.getElementById('payslips-link'),
        submitClaim: document.getElementById('submit-claim-link'),
        myClaims: document.getElementById('my-claims-link'),
        claimsApproval: document.getElementById('claims-approval-link'),
        claimTypesAdmin: document.getElementById('claim-types-admin-link'),
        leaveRequests: document.getElementById('leave-requests-link'),
        leaveBalances: document.getElementById('leave-balances-link'),
        leaveTypes: document.getElementById('leave-types-link'),
    // compensationOverview removed
        compPlans: document.getElementById('comp-plans-link'),
        salaryAdjust: document.getElementById('salary-adjust-link'),
        incentives: document.getElementById('incentives-link'),
        salaryGrades: document.getElementById('salary-grades-link'),
        payBands: document.getElementById('pay-bands-link'),
        employeeMapping: document.getElementById('employee-mapping-link'),
        workflows: document.getElementById('workflows-link'),
        simulationTools: document.getElementById('simulation-tools-link'),
        analyticsDashboards: document.getElementById('analytics-dashboards-link'),
        analyticsReports: document.getElementById('analytics-reports-link'),
        analyticsMetrics: document.getElementById('analytics-metrics-link'),
        hmoProviders: document.getElementById('hmo-providers-link'),
        hmoPlans: document.getElementById('hmo-plans-link'),
        hmoEnrollments: document.getElementById('hmo-enrollments-link'),
        hmoClaimsAdmin: document.getElementById('hmo-claims-admin-link'),
        hmoDashboard: document.getElementById('hmo-dashboard-link'),
        myHmoBenefits: document.getElementById('my-hmo-benefits-link'),
        myHmoClaims: document.getElementById('my-hmo-claims-link'),
        submitHmoClaim: document.getElementById('submit-hmo-claim-link'),
        hmoEnrollment: document.getElementById('hmo-enrollment-link'),
        userManagement: document.getElementById('user-management-link'),
        notifications: document.getElementById('notifications-link')
    };

    // --- Error Handling for Missing Core Elements ---
     if (!mainContentArea || !pageTitleElement || !appContainer) { // Removed loginContainer from this check
        console.error("CRITICAL: Essential App DOM elements (app-container, main-content-area, page-title) not found!");
        document.body.innerHTML = '<p style="color: red; padding: 20px;">Application Error: Core UI elements are missing. Ensure app-container, main-content-area, and page-title IDs exist in your HTML.</p>';
        return;
    }
    if (!userProfileButton || !userProfileDropdown || !viewProfileLink || !logoutLinkNav || !userProfileArrow) {
        console.warn("Navbar profile elements not fully found. Profile dropdown might not work.");
    }
    if (!notificationBellButton || !notificationDropdown || !notificationDot || !notificationListElement) {
        console.warn("Notification elements not fully found. Notifications might not work.");
    }

    // --- Setup Modal Close Listeners ---
    if (timesheetModal && modalOverlayTs && modalCloseBtnTs) {
         if (typeof closeTimesheetModal === 'function') {
             modalCloseBtnTs.addEventListener('click', closeTimesheetModal);
             modalOverlayTs.addEventListener('click', closeTimesheetModal);
             const footerCloseBtn = document.getElementById('modal-close-btn-ts-footer');
             if (footerCloseBtn) footerCloseBtn.addEventListener('click', closeTimesheetModal);
         } else {
             console.warn("closeTimesheetModal function not found/imported from timesheets.js.");
             modalCloseBtnTs.addEventListener('click', () => timesheetModal.classList.add('hidden'));
             modalOverlayTs.addEventListener('click', () => timesheetModal.classList.add('hidden'));
             const footerCloseBtn = document.getElementById('modal-close-btn-ts-footer');
             if (footerCloseBtn) footerCloseBtn.addEventListener('click', () => timesheetModal.classList.add('hidden'));
         }
    }
    const employeeDetailModal = document.getElementById('employee-detail-modal');
    const employeeModalOverlay = document.getElementById('modal-overlay-employee');
    const employeeModalCloseBtnHeader = document.getElementById('modal-close-btn-employee');
    const employeeModalCloseBtnFooter = document.getElementById('modal-close-btn-employee-footer');

    function closeEmployeeDetailModal() {
        if (employeeDetailModal) employeeDetailModal.classList.add('hidden');
    }
    if(employeeDetailModal && employeeModalOverlay && employeeModalCloseBtnHeader && employeeModalCloseBtnFooter) {
        employeeModalOverlay.addEventListener('click', closeEmployeeDetailModal);
        employeeModalCloseBtnHeader.addEventListener('click', closeEmployeeDetailModal);
        employeeModalCloseBtnFooter.addEventListener('click', closeEmployeeDetailModal);
    }

    // --- Event Listeners for Navbar Profile Dropdown ---
    if (userProfileButton && userProfileDropdown && userProfileArrow) {
        userProfileButton.addEventListener('click', (event) => {
            event.stopPropagation();
            userProfileDropdown.classList.toggle('hidden');
            userProfileArrow.classList.toggle('bx-chevron-down');
            userProfileArrow.classList.toggle('bx-chevron-up');
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
                onNotificationDropdownClose(); 
            }
        });
    }

    if (viewProfileLink) {
        viewProfileLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (typeof displayUserProfileSection === 'function') {
                displayUserProfileSection();
                updateActiveSidebarLink(null); 
            } else {
                console.error("displayUserProfileSection function is not defined or imported.");
                 if(mainContentArea) mainContentArea.innerHTML = '<p class="text-red-500 p-4">Error: Profile display function not available.</p>';
            }
            if (userProfileDropdown) userProfileDropdown.classList.add('hidden');
            if (userProfileArrow) {
                userProfileArrow.classList.remove('bx-chevron-up');
                userProfileArrow.classList.add('bx-chevron-down');
            }
        });
    }
    
    // --- Logout Handler ---
    window.handleLogout = async function(event) {
        event.preventDefault();
        console.log("Logout initiated...");

        try {
            console.log("Making logout request to:", `${API_BASE_URL}logout.php`);
            const response = await fetch(`${API_BASE_URL}logout.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include' // Include cookies for session handling
            });

            console.log("Logout response status:", response.status);
            console.log("Logout response headers:", response.headers);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log("Logout response data:", data);

            if (data.message === 'Logout successful.') {
                console.log("Logout successful, redirecting to:", data.redirect_url);
                // Clear local user data
                window.currentUser = null;
                stopNotificationFetching();

                // Hide the main app
                if(appContainer) appContainer.style.display = 'none';

                // Redirect to login page
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    // Fallback to index.php if no redirect_url provided
                    window.location.href = 'index.php';
                }
            } else {
                console.error('Logout failed:', data.error);
                alert('Logout failed: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error during logout:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            alert('An error occurred during logout. Please try again. Error: ' + error.message);
        }
    }

    if (logoutLinkNav) {
        logoutLinkNav.addEventListener('click', handleLogout);
    }

    // --- Event Listeners for Notification Bell ---
    if (notificationBellButton && notificationDropdown) {
        // Helper to open with animation
        function openNotificationDropdown() {
            notificationDropdown.classList.remove('hidden');
            // small delay to allow browser to apply the removal before transitioning
            requestAnimationFrame(() => {
                notificationDropdown.classList.remove('scale-95', 'opacity-0');
                notificationDropdown.classList.add('scale-100', 'opacity-100');
            });
            onNotificationDropdownOpen();
        }

        // Helper to close with animation
        function closeNotificationDropdown() {
            notificationDropdown.classList.remove('scale-100', 'opacity-100');
            notificationDropdown.classList.add('scale-95', 'opacity-0');
            // Wait for transition end to hide element fully
            const onEnd = (e) => {
                if (e.target !== notificationDropdown) return;
                notificationDropdown.classList.add('hidden');
                notificationDropdown.removeEventListener('transitionend', onEnd);
            };
            notificationDropdown.addEventListener('transitionend', onEnd);
            onNotificationDropdownClose();
        }

        notificationBellButton.addEventListener('click', (event) => {
            event.stopPropagation();
            const isHidden = notificationDropdown.classList.contains('hidden');
            if (userProfileDropdown && !userProfileDropdown.classList.contains('hidden')) {
                userProfileDropdown.classList.add('hidden');
                if(userProfileArrow) {
                    userProfileArrow.classList.remove('bx-chevron-up');
                    userProfileArrow.classList.add('bx-chevron-down');
                }
            }
            if (isHidden) {
                openNotificationDropdown();
            } else {
                closeNotificationDropdown();
            }
        });
    }

    // Global click listener to close dropdowns
    document.addEventListener('click', (event) => {
        if (userProfileDropdown && userProfileButton && !userProfileButton.contains(event.target) && !userProfileDropdown.contains(event.target)) {
            if (!userProfileDropdown.classList.contains('hidden')) {
                userProfileDropdown.classList.add('hidden');
                if(userProfileArrow) {
                    userProfileArrow.classList.remove('bx-chevron-up');
                    userProfileArrow.classList.add('bx-chevron-down');
                }
            }
        }
        if (notificationDropdown && notificationBellButton && !notificationBellButton.contains(event.target) && !notificationDropdown.contains(event.target)) {
            if (!notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
                onNotificationDropdownClose(); 
            }
        }
    });

    // --- Sidebar Listener Setup ---
    const addClickListenerOnce = (element, handler) => {
        const targetElement = element; // Assumes the clickable part is an <a> tag within the <li> or the menu-option itself if it's an <a>
        if (targetElement && !targetElement.hasAttribute('data-listener-added')) {
            targetElement.addEventListener('click', (e) => {
                e.preventDefault();
                const sidebar = document.querySelector('.sidebar');
                if(sidebar && sidebar.classList.contains('mobile-active')) { try{ if(typeof closeSidebar === 'function') closeSidebar(); }catch(e){} }
                if (typeof handler === 'function') {
                    // Support async handlers and surface promise rejections
                    Promise.resolve()
                        .then(() => handler())
                        .catch(error => {
                            console.error(`Error executing handler for ${targetElement.id || 'sidebar link'}:`, error);
                            if(mainContentArea) { mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error loading section. Please check the console.</p>`; }
                        });
                } else {
                    console.error("Handler is not a function for element:", targetElement);
                }
                updateActiveSidebarLink(targetElement);
            });
            targetElement.setAttribute('data-listener-added', 'true');
        } else if (!targetElement && handler && typeof handler === 'function') {
             // If the element itself is the <a> tag and was passed directly
             if(element && element.tagName === 'A' && !element.hasAttribute('data-listener-added')) {
                element.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sidebar = document.querySelector('.sidebar');
                    if(sidebar && sidebar.classList.contains('mobile-active')) { try{ if(typeof closeSidebar === 'function') closeSidebar(); }catch(e){} }
                    if (typeof handler === 'function') {
                        Promise.resolve()
                            .then(() => handler())
                            .catch(error => console.error(`Error executing handler:`, error));
                    }
                    updateActiveSidebarLink(element);
                });
                element.setAttribute('data-listener-added', 'true');
             } else {
                console.warn(`Sidebar link element (<a>) not found within provided element for handler: ${handler.name}. Check ID/structure.`);
             }
        }
    };

    function attachSidebarListeners() {
        console.log("Attaching sidebar listeners...");
        if (sidebarItems.dashboard) {
            addClickListenerOnce(sidebarItems.dashboard, displayDashboardSection);
        }
        if (sidebarItems.employees) {
             addClickListenerOnce(sidebarItems.employees, displayEmployeeSection);
        }
        if (sidebarItems.documents) {
             addClickListenerOnce(sidebarItems.documents, displayDocumentsSection);
        }
        if (sidebarItems.orgStructure) {
             addClickListenerOnce(sidebarItems.orgStructure, displayOrgStructureSection);
        }
        if (sidebarItems.attendance) {
             addClickListenerOnce(sidebarItems.attendance, displayAttendanceSection);
        }
        if (sidebarItems.timesheets) {
             addClickListenerOnce(sidebarItems.timesheets, displayTimesheetsSection);
        }
        if (sidebarItems.schedules) {
             addClickListenerOnce(sidebarItems.schedules, displaySchedulesSection);
        }
         if (sidebarItems.shifts) {
             addClickListenerOnce(sidebarItems.shifts, displayShiftsSection);
        }
        if (sidebarItems.payrollRuns) {
             addClickListenerOnce(sidebarItems.payrollRuns, displayPayrollRunsSection);
        }
        if (sidebarItems.salaries) {
             addClickListenerOnce(sidebarItems.salaries, displaySalariesSection);
        }
        if (sidebarItems.bonuses) {
             addClickListenerOnce(sidebarItems.bonuses, displayBonusesSection);
        }
        if (sidebarItems.deductions) {
             addClickListenerOnce(sidebarItems.deductions, displayDeductionsSection);
        }
        if (sidebarItems.payslips) {
            addClickListenerOnce(sidebarItems.payslips, displayPayslipsSection);
        }
        if (sidebarItems.submitClaim) {
             addClickListenerOnce(sidebarItems.submitClaim, displaySubmitClaimSection);
        }
        if (sidebarItems.myClaims) {
             addClickListenerOnce(sidebarItems.myClaims, displayMyClaimsSection);
        }
        if (sidebarItems.claimsApproval) {
             addClickListenerOnce(sidebarItems.claimsApproval, displayClaimsApprovalSection);
        }
        if (sidebarItems.claimTypesAdmin) {
             addClickListenerOnce(sidebarItems.claimTypesAdmin, displayClaimTypesAdminSection);
        }
        if (sidebarItems.leaveRequests) {
             addClickListenerOnce(sidebarItems.leaveRequests, displayLeaveRequestsSection);
        }
        if (sidebarItems.leaveBalances) {
             addClickListenerOnce(sidebarItems.leaveBalances, displayLeaveBalancesSection);
        }
        if (sidebarItems.leaveTypes) {
             addClickListenerOnce(sidebarItems.leaveTypes, displayLeaveTypesAdminSection);
        }
       // compensationOverview listener removed
        if (sidebarItems.compPlans) {
             addClickListenerOnce(sidebarItems.compPlans, displayCompensationPlansSection);
        }
        if (sidebarItems.salaryAdjust) {
             addClickListenerOnce(sidebarItems.salaryAdjust, displaySalaryAdjustmentsSection);
        }
        if (sidebarItems.incentives) {
             addClickListenerOnce(sidebarItems.incentives, displayIncentivesSection);
        }
        if (sidebarItems.salaryGrades) {
             addClickListenerOnce(sidebarItems.salaryGrades, displaySalaryGradesSection);
        }
        if (sidebarItems.payBands) {
             addClickListenerOnce(sidebarItems.payBands, displayPayBandsSection);
        }
        if (sidebarItems.employeeMapping) {
             addClickListenerOnce(sidebarItems.employeeMapping, displayEmployeeMappingSection);
        }
        if (sidebarItems.workflows) {
             addClickListenerOnce(sidebarItems.workflows, displayWorkflowsSection);
        }
        if (sidebarItems.simulationTools) {
             addClickListenerOnce(sidebarItems.simulationTools, displaySimulationToolsSection);
        }
        if (sidebarItems.analyticsDashboards) {
             addClickListenerOnce(sidebarItems.analyticsDashboards, displayAnalyticsDashboardsSection);
        }
        if (sidebarItems.analyticsReports) {
             addClickListenerOnce(sidebarItems.analyticsReports, displayAnalyticsReportsSection);
        }
        if (sidebarItems.analyticsMetrics) {
             addClickListenerOnce(sidebarItems.analyticsMetrics, displayAnalyticsMetricsSection);
        }
        if (sidebarItems.hmoProviders) {
             addClickListenerOnce(sidebarItems.hmoProviders, displayHMOProvidersSection);
        }
       if (sidebarItems.hmoPlans) {
           addClickListenerOnce(sidebarItems.hmoPlans, displayHMOPlansSection);
        }
        if (sidebarItems.hmoEnrollments) {
             addClickListenerOnce(sidebarItems.hmoEnrollments, displayHMOEnrollmentsSection);
        }
        if (sidebarItems.hmoClaimsAdmin) {
             addClickListenerOnce(sidebarItems.hmoClaimsAdmin, displayHMOClaimsApprovalSection);
        }
       if (sidebarItems.hmoDashboard) {
           addClickListenerOnce(sidebarItems.hmoDashboard, displayHMODashboardSection);
        }
        if (sidebarItems.myHmoBenefits) {
             addClickListenerOnce(sidebarItems.myHmoBenefits, displayEmployeeHMOSection);
        }
        if (sidebarItems.myHmoClaims) {
             addClickListenerOnce(sidebarItems.myHmoClaims, displayEmployeeHMOClaimsSection);
        }
        if (sidebarItems.submitHmoClaim) {
             addClickListenerOnce(sidebarItems.submitHmoClaim, displaySubmitHMOClaimSection);
        }
        if (sidebarItems.hmoEnrollment) {
             addClickListenerOnce(sidebarItems.hmoEnrollment, displayEmployeeHMOSection);
        }
        if (sidebarItems.userManagement) {
            addClickListenerOnce(sidebarItems.userManagement, displayUserManagementSection);
        }
        if (sidebarItems.notifications) {
            addClickListenerOnce(sidebarItems.notifications, displayNotificationsSection);
        }
        console.log("Sidebar listeners attached/reattached.");
    }
    
    // --- UI Visibility Functions ---
    function showAppUI() {
        // loginContainer is assumed to be removed from HTML or hidden by default in landing pages
        if(appContainer) appContainer.style.display = 'flex';
    }

    // --- Update User Display in Navbar ---
    function updateUserDisplay(userData) {
        if (userData && userDisplayName && userDisplayRole) {
            userDisplayName.textContent = userData.full_name || 'User';
            userDisplayRole.textContent = userData.role_name || 'Role';
        } else {
             if(userDisplayName) userDisplayName.textContent = window.currentUser?.full_name || 'User';
             if(userDisplayRole) userDisplayRole.textContent = '';
        }
    }

    // --- Role-Based UI Access Control ---
    function updateSidebarAccess(roleName) {
        console.log(`Updating sidebar access for role: ${roleName}`);
        const allMenuItems = document.querySelectorAll('.sidebar .menu-option');
        const allSubMenuItems = document.querySelectorAll('.sidebar .menu-drop li');

        allMenuItems.forEach(item => item?.classList.add('hidden')); // Hide all main categories first
        allSubMenuItems.forEach(item => item?.classList.add('hidden')); // Hide all sub-items first

        const show = (element, elementName = 'Unknown') => {
            if (element) {
                element.classList.remove('hidden');
                element.style.display = ''; // Explicitly set display if it was 'none'
                // If it's a sub-item (li), ensure its parent menu-option (category) is also shown
                const parentMenuOption = element.closest('.menu-option');
                if(parentMenuOption) {
                    parentMenuOption.classList.remove('hidden');
                    parentMenuOption.style.display = '';
                }
            } else {
                 console.warn(`Attempted to show a non-existent sidebar element: ${elementName}`);
            }
        };
         const hide = (element, elementName = 'Unknown') => {
             if (element) {
                element.classList.add('hidden');
                // Note: Hiding a sub-item doesn't automatically hide its parent category
                // if other sub-items in that category are visible.
             }
        };

        // Always show dashboard
        show(sidebarItems.dashboard, 'Dashboard');

        switch (roleName) {
            case 'System Admin':
            case 'HR Admin': 
                console.log(`Executing ${roleName} access rules.`);
                show(sidebarItems.coreHr, 'Core HR');
                show(sidebarItems.employees, 'Employees');
                show(sidebarItems.documents, 'Documents');
                show(sidebarItems.orgStructure, 'Org Structure');
                show(sidebarItems.timeAttendance, 'Time & Attendance');
                show(sidebarItems.attendance, 'Attendance');
                show(sidebarItems.timesheets, 'Timesheets');
                show(sidebarItems.schedules, 'Schedules');
                show(sidebarItems.shifts, 'Shifts');
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payrollRuns, 'Payroll Runs');
                show(sidebarItems.salaries, 'Salaries');
                show(sidebarItems.bonuses, 'Bonuses');
                show(sidebarItems.deductions, 'Deductions');
                show(sidebarItems.payslips, 'Payslips'); // Admins might need to view all
                show(sidebarItems.claims, 'Claims');
                // hide(sidebarItems.submitClaim, 'Submit Claim (Admin)'); // Admins typically don't submit their own via general UI
                // hide(sidebarItems.myClaims, 'My Claims (Admin)');
                show(sidebarItems.claimsApproval, 'Claims Approval');
                show(sidebarItems.claimTypesAdmin, 'Claim Types Admin');
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests'); // Includes approvals
                show(sidebarItems.leaveBalances, 'Leave Balances'); // View all
                show(sidebarItems.leaveTypes, 'Leave Types');
                show(sidebarItems.compensation, 'Compensation');
                show(sidebarItems.compPlans, 'Comp Plans');
                show(sidebarItems.salaryAdjust, 'Salary Adjust');
                show(sidebarItems.incentives, 'Incentives');
                show(sidebarItems.analytics, 'Analytics'); 
                show(sidebarItems.analyticsDashboards, 'Analytics Dashboards');
                show(sidebarItems.analyticsReports, 'Analytics Reports');
                show(sidebarItems.analyticsMetrics, 'Analytics Metrics');
                show(sidebarItems.hmo, 'HMO');
                show(sidebarItems.hmoProviders, 'HMO Providers');
                show(sidebarItems.hmoPlans, 'HMO Plans');
                show(sidebarItems.hmoEnrollments, 'HMO Enrollments');
                show(sidebarItems.hmoClaimsAdmin, 'HMO Claims Admin');
                show(sidebarItems.hmoDashboard, 'HMO Dashboard');
                if (roleName === 'System Admin') {
                    show(sidebarItems.admin, 'Admin');
                    show(sidebarItems.userManagement, 'User Management');
                } else { 
                    hide(sidebarItems.admin, 'Admin');
                }
                break;
            case 'Manager':
                console.log("Executing Manager access rules.");
                show(sidebarItems.claims, 'Claims');
                show(sidebarItems.submitClaim, 'Submit Claim'); 
                show(sidebarItems.myClaims, 'My Claims'); 
                show(sidebarItems.claimsApproval, 'Claims Approval'); 
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests'); 
                show(sidebarItems.leaveBalances, 'Leave Balances'); 
                show(sidebarItems.timeAttendance, 'Time & Attendance');
                show(sidebarItems.attendance, 'Attendance'); 
                show(sidebarItems.timesheets, 'Timesheets'); 
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payslips, 'Payslips'); 
                
                // Explicitly hide sections not for Managers
                hide(sidebarItems.coreHr, 'Core HR (Manager)');
                hide(sidebarItems.payrollRuns, 'Payroll Runs (Manager)');
                hide(sidebarItems.salaries, 'Salaries (Manager)');
                hide(sidebarItems.bonuses, 'Bonuses (Manager)');
                hide(sidebarItems.deductions, 'Deductions (Manager)');
                hide(sidebarItems.claimTypesAdmin, 'Claim Types Admin (Manager)');
                hide(sidebarItems.leaveTypes, 'Leave Types (Manager)');
                hide(sidebarItems.compensation, 'Compensation (Manager)');
                hide(sidebarItems.analytics, 'Analytics (Manager)'); 
                hide(sidebarItems.hmo, 'HMO (Manager)');
                hide(sidebarItems.admin, 'Admin (Manager)');
                break;
            case 'Employee':
                console.log("Executing Employee access rules.");
                show(sidebarItems.claims, 'Claims');
                show(sidebarItems.submitClaim, 'Submit Claim');
                show(sidebarItems.myClaims, 'My Claims');
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests');
                show(sidebarItems.leaveBalances, 'Leave Balances');
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payslips, 'Payslips');
                show(sidebarItems.hmo, 'My HMO & Benefits');
                show(sidebarItems.myHmoBenefits, 'My HMO Benefits');
                show(sidebarItems.myHmoClaims, 'My HMO Claims');
                show(sidebarItems.submitHmoClaim, 'Submit HMO Claim');
                show(sidebarItems.hmoEnrollment, 'HMO Enrollment');

                // Explicitly hide sections not for Employees
                hide(sidebarItems.coreHr, 'Core HR (Employee)');
                hide(sidebarItems.timeAttendance, 'Time & Attendance (Employee)'); 
                hide(sidebarItems.payrollRuns, 'Payroll Runs (Employee)');
                hide(sidebarItems.salaries, 'Salaries (Employee)');
                hide(sidebarItems.bonuses, 'Bonuses (Employee)');
                hide(sidebarItems.deductions, 'Deductions (Employee)');
                hide(sidebarItems.claimsApproval, 'Claims Approval (Employee)');
                hide(sidebarItems.claimTypesAdmin, 'Claim Types Admin (Employee)');
                hide(sidebarItems.leaveTypes, 'Leave Types (Employee)');
                hide(sidebarItems.compensation, 'Compensation (Employee)');
                hide(sidebarItems.analytics, 'Analytics (Employee)');
                hide(sidebarItems.hmoProviders, 'HMO Providers (Employee)');
                hide(sidebarItems.hmoPlans, 'HMO Plans (Employee)');
                hide(sidebarItems.hmoEnrollments, 'HMO Enrollments (Employee)');
                hide(sidebarItems.hmoClaimsAdmin, 'HMO Claims Admin (Employee)');
                hide(sidebarItems.hmoDashboard, 'HMO Dashboard (Employee)');
                hide(sidebarItems.admin, 'Admin (Employee)');
                break;
            default: 
                console.log("Executing Default access rules (no specific role identified).");
                Object.values(sidebarItems).forEach(item => {
                    if (item && item !== sidebarItems.dashboard) hide(item);
                });
                document.querySelectorAll('.menu-drop').forEach(d => d.classList.add('hidden'));
                break;
        }
        console.log("Sidebar access update complete.");
    }

    // --- Display Notifications Section ---
    function displayNotificationsSection() {
        const mainContentArea = document.getElementById('main-content-area');
        const pageTitleElement = document.getElementById('page-title');

        if (!mainContentArea || !pageTitleElement) {
            console.error("Main content area or page title element not found");
            return;
        }

        if (pageTitleElement) pageTitleElement.textContent = 'Notifications';

        // Create a full-page notifications view
        mainContentArea.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">All Notifications</h2>
                    <button id="refresh-notifications-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>

                <div id="notifications-full-list" class="bg-white rounded-lg shadow max-h-[70vh] overflow-y-auto">
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-bell text-4xl mb-4"></i>
                        <p>Loading notifications...</p>
                    </div>
                </div>
            </div>
        `;

        // Get the full list element
        const fullListElement = document.getElementById('notifications-full-list');

        // Fetch and render notifications to the full page list
        if (typeof fetchAndRenderNotifications === 'function') {
            fetchAndRenderNotifications(fullListElement);
        }

        // Add click listener for notification items in full page
        if (fullListElement && typeof handleNotificationItemClick === 'function') {
            fullListElement.addEventListener('click', handleNotificationItemClick);
        }

        // Add refresh button functionality
        const refreshBtn = document.getElementById('refresh-notifications-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                if (typeof fetchAndRenderNotifications === 'function') {
                    fetchAndRenderNotifications(fullListElement);
                }
            });
        }
    }

    // --- Navigation Logic for Notifications & Direct Section Access ---
    window.sectionDisplayFunctions = {
        'dashboard': displayDashboardSection,
        'employees': displayEmployeeSection,
        'documents': displayDocumentsSection,
        'org-structure': displayOrgStructureSection,
        'role-access': displayRoleAccessSection,
        'attendance': displayAttendanceSection,
        'timesheets': displayTimesheetsSection,
        'schedules': displaySchedulesSection,
        'shifts': displayShiftsSection,
        'payroll-runs': displayPayrollRunsSection,
        'salaries': displaySalariesSection,
        'bonuses': displayBonusesSection,
        'deductions': displayDeductionsSection,
        'payslips': displayPayslipsSection,
        'submit-claim': displaySubmitClaimSection,
        'my-claims': displayMyClaimsSection,
        'claims-approval': displayClaimsApprovalSection,
        'claim-types-admin': displayClaimTypesAdminSection,
        'leave-requests': displayLeaveRequestsSection,
        'leave-balances': displayLeaveBalancesSection,
        'leave-types': displayLeaveTypesAdminSection,
    // 'compensation-overview' removed
        'comp-plans': displayCompensationPlansSection,
        'salary-adjust': displaySalaryAdjustmentsSection,
        'incentives': displayIncentivesSection,
        'analytics-dashboards': displayAnalyticsDashboardsSection,
        'analytics-reports': displayAnalyticsReportsSection,
        'analytics-metrics': displayAnalyticsMetricsSection,
    'hmo-providers': displayHMOProvidersSection,
    'hmo-plans': displayHMOPlansSection,
        'hmo-enrollments': displayHMOEnrollmentsSection,
    'hmo-claims-admin': displayHMOClaimsApprovalSection,
    'hmo-dashboard': displayHMODashboardSection,
        'my-hmo-benefits': displayEmployeeHMOSection,
        'my-hmo-claims': displayEmployeeHMOClaimsSection,
        'submit-hmo-claim': displaySubmitHMOClaimSection,
        'hmo-enrollment': displayEmployeeHMOSection, // Use same as benefits for now
        'user-management': displayUserManagementSection,
        'profile': displayUserProfileSection,
        'notifications': displayNotificationsSection
    };

    window.navigateToSectionById = async function(sectionId) {
        console.log(`[Main Navigation] Attempting to navigate to section: ${sectionId}`);
        console.log(`[Main Navigation] Function is defined:`, typeof window.navigateToSectionById);
        
        // Create function name from sectionId
        let functionName;
        if (sectionId === 'dashboard') {
            functionName = 'displayDashboardSection';
        } else {
            functionName = `display${sectionId.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join('')}Section`;
        }
        
        console.log(`[Main Navigation] Looking for function: ${functionName}`);
        console.log(`[Main Navigation] Available functions:`, Object.keys(window).filter(key => key.startsWith('display') && key.endsWith('Section')));
        const displayFunction = window[functionName] || window.sectionDisplayFunctions[sectionId];
        const mainContentArea = document.getElementById('main-content-area'); 

        // Don't show loading message for dashboard if modern dashboard is already loaded
        if (sectionId === 'dashboard' && mainContentArea && mainContentArea.querySelector('#total-employees')) {
            console.log('Modern dashboard already loaded, skipping loading message');
        } else {
        // Immediate visual feedback so clicks are not mistaken for no-ops
        try {
            if (mainContentArea) {
                mainContentArea.innerHTML = `<div class="p-6 text-center text-gray-600">Loading <strong>${sectionId}</strong>...</div>`;
            }
        } catch (e) { console.warn('Failed to set loading placeholder', e); }
        }

        console.log(`[Main Navigation] Display function for ${sectionId}:`, displayFunction);
        console.log(`[Main Navigation] Function type:`, typeof displayFunction);
        console.log(`[Main Navigation] Main content area:`, mainContentArea);
        console.log(`[Main Navigation] Current user:`, window.currentUser);

        if (typeof displayFunction === 'function') {
            try {
                console.log(`[Main Navigation] Calling display function for ${sectionId}`);
                // Support async display functions and catch rejections
                await Promise.resolve().then(() => displayFunction()).catch(err => { throw err; });
                console.log(`[Main Navigation] Display function completed for ${sectionId}`);
                // Try to find the corresponding sidebar link to highlight it
                const sidebarLink = document.getElementById(`${sectionId}-link`);
                if (sidebarLink) {
                    updateActiveSidebarLink(sidebarLink);
                } else {
                     // Fallback for links that might not follow the id convention (e.g., dashboard-link)
                     const directSidebarItem = document.querySelector(`.sidebar a[id="${sectionId}-link"]`) || document.querySelector(`.sidebar a[href="#${sectionId}"]`);
                     if (directSidebarItem) {
                         updateActiveSidebarLink(directSidebarItem);
                     } else {
                        console.warn(`[Main Navigation] Sidebar link for sectionId '${sectionId}' not found for highlighting.`);
                        updateActiveSidebarLink(null); // Clear active link if none found
                     }
                }
            } catch (error) {
                console.error(`Error navigating to section '${sectionId}':`, error);
                // Fallback: try to directly load the module for core sections even if a display function exists but failed
                try {
                    const coreMap = {
                        'org-structure': { path: 'core_hr/org_structure.js', title: 'Organizational Structure' }
                    };
                    const core = coreMap[sectionId];
                    if (core && mainContentArea) {
                        const utils = await import('./utils.js');
                        if (typeof utils.loadModule === 'function') {
                            await utils.loadModule(core.path, mainContentArea, core.title);
                            return; // success, stop here
                        }
                    }
                } catch (fallbackErr) {
                    console.warn('Direct module fallback in catch failed:', fallbackErr);
                }
                if (mainContentArea) {
                    mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error loading section: ${sectionId}. Please check the console.</p>`;
                }
            }
        } else {
            console.warn(`[Main Navigation] No display function found for sectionId: ${sectionId}`);
            // First, try a direct module-path fallback for core sections
            try {
                const coreMap = {
                    'org-structure': { path: 'core_hr/org_structure.js', title: 'Organizational Structure' }
                };
                const core = coreMap[sectionId];
                if (core && mainContentArea) {
                    const utils = await import('./utils.js');
                    if (typeof utils.loadModule === 'function') {
                        await utils.loadModule(core.path, mainContentArea, core.title);
                        return;
                    }
                }
            } catch (err) {
                console.warn('Core section direct-load fallback failed:', err);
            }

            // Fallback: try to dynamically load an HMO admin module if the sectionId matches common HMO patterns
            try {
                const hmoMatch = sectionId && sectionId.startsWith('hmo-');
                if (hmoMatch) {
                    // Map section ids to module filenames
                    const map = {
                        'hmo-providers': 'admin/hmo/providers.js',
                        'hmo-plans': 'admin/hmo/plans.js',
                        'hmo-enrollments': 'admin/hmo/enrollments.js',
                        'hmo-claims-admin': 'admin/hmo/claims.js',
                        'hmo-dashboard': 'admin/hmo/dashboard.js'
                    };
                    const modulePath = map[sectionId];
                    if (modulePath) {
                        // Import utils dynamically (which exports loadModule)
                        const utils = await import('./utils.js');
                        if (typeof utils.loadModule === 'function' && mainContentArea) {
                            try {
                                await utils.loadModule(modulePath, mainContentArea, '');
                                // After loading, see if the display function is now available and call it
                                const retryFnName = Object.keys(sectionDisplayFunctions).find(k => k.toLowerCase().includes(sectionId.replace(/-/g, '').toLowerCase()));
                                if (retryFnName && typeof window[retryFnName] === 'function') {
                                    await Promise.resolve().then(() => window[retryFnName]()).catch(err=>{throw err;});
                                    return;
                                }
                            } catch (err) {
                                console.error('Fallback loadModule failed for', modulePath, err);
                            }
                        }
                    }
                }
            } catch (err) {
                console.warn('Fallback navigation attempt failed:', err);
            }
            if (mainContentArea) {
                 mainContentArea.innerHTML = `<p class="text-orange-500 p-4">Section '${sectionId}' not found or not yet implemented.</p>`;
            }
        }
    };
    
    // --- Function to update active sidebar link styling ---
    function updateActiveSidebarLink(clickedLinkElement) {
        // Clear previous active styles
        document.querySelectorAll('aside a').forEach(el => {
            el.classList.remove('bg-white/20', 'text-[#d4af37]', 'font-semibold', 'active-link-style');
        });

        if (!clickedLinkElement) return; // If null, just clear active styles

        // Add active styling to the clicked link
        clickedLinkElement.classList.add('bg-white/20', 'text-[#d4af37]', 'font-semibold', 'active-link-style');
    }

    // --- SESSION CHECK AND APP INITIALIZATION ---
    // Check if user has an active session
    console.log("Checking session at:", `${API_BASE_URL}check_session.php`);
    fetch(`${API_BASE_URL}check_session.php`, {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => {
            console.log("Session check response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("Session check data:", data);
            if (data && data.logged_in && data.user && data.user.user_id) {
                window.currentUser = {
                    user_id: data.user.user_id,
                    employee_id: data.user.employee_id,
                    username: data.user.username,
                    full_name: data.user.full_name,
                    role_id: data.user.role_id,
                    role_name: data.user.role_name,
                    hmo_enrollment: data.user.hmo_enrollment
                };
                showAppUI();
                updateUserDisplay(window.currentUser);
                updateSidebarAccess(window.currentUser.role_name);
                attachSidebarListeners();
                // Add robust event delegation on sidebar so clicks always navigate even if links are re-rendered
                try {
                    const sidebarRoot = document.querySelector('.sidebar');
                    console.log('[Sidebar] Setting up event delegation on:', sidebarRoot);
                    console.log('[Sidebar] Sidebar root found:', !!sidebarRoot);
                    console.log('[Sidebar] Sidebar has delegation attribute:', sidebarRoot?.hasAttribute('data-delegation-added'));
                    
                    // Test: Add a simple click listener first to see if basic clicks work
                    if (sidebarRoot) {
                        sidebarRoot.addEventListener('click', (ev) => {
                            console.log('[Sidebar] BASIC CLICK TEST - Click detected on:', ev.target);
                        });
                    }
                    
                    if (sidebarRoot && !sidebarRoot.hasAttribute('data-delegation-added')) {
                        console.log('[Sidebar] Adding click event listener');
                        sidebarRoot.addEventListener('click', async (ev) => {
                            console.log('[Sidebar] Click detected on:', ev.target);
                            console.log('[Sidebar] Event delegation working!');
                            const anchor = ev.target.closest && ev.target.closest('a');
                            if (!anchor) {
                                console.log('[Sidebar] Not a link click, ignoring');
                                return; // not a link click
                            }
                            // Derive section id from link id (e.g., 'hmo-providers-link' -> 'hmo-providers')
                            const linkId = anchor.id || '';
                            const sectionId = linkId.replace(/-link$/, '');
                            console.log('[Sidebar] Link clicked - ID:', linkId, 'Section ID:', sectionId);
                            if (!sectionId) {
                                console.log('[Sidebar] No section ID found, ignoring');
                                return; // nothing to navigate to
                            }
                            ev.preventDefault();
                            // Close mobile sidebar if open
                            try { const sidebar = document.querySelector('.sidebar'); if (sidebar && sidebar.classList.contains('mobile-active') && typeof closeSidebar === 'function') try{ closeSidebar(); }catch(e){} } catch(e){}
                            // Call the navigation function and await it (safe-guarded)
                            if (typeof navigateToSectionById === 'function') {
                                try {
                                    await navigateToSectionById(sectionId);
                                } catch (err) {
                                    console.error('Navigation handler rejected for section', sectionId, err);
                                    // Let user know something went wrong in the main content area
                                    const mainContentArea = document.getElementById('main-content-area'); if (mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Navigation failed. See console for details.</p>`;
                                }
                            }
                            // Update active styling
                            updateActiveSidebarLink(anchor);
                        });
                        sidebarRoot.setAttribute('data-delegation-added', 'true');
                    }
                } catch (err) {
                    console.warn('Failed to attach sidebar delegation:', err);
                }
                
                // Fallback: Try again after a delay to ensure Alpine.js has rendered
                setTimeout(() => {
                    try {
                        const sidebarRoot = document.querySelector('.sidebar');
                        if (sidebarRoot && !sidebarRoot.hasAttribute('data-delegation-added')) {
                            console.log('[Sidebar] Fallback: Adding click event listener after delay');
                            sidebarRoot.addEventListener('click', async (ev) => {
                                console.log('[Sidebar] Fallback click detected on:', ev.target);
                                const anchor = ev.target.closest && ev.target.closest('a');
                                if (!anchor) {
                                    console.log('[Sidebar] Fallback: Not a link click, ignoring');
                                    return;
                                }
                                const linkId = anchor.id || '';
                                const sectionId = linkId.replace(/-link$/, '');
                                console.log('[Sidebar] Fallback: Link clicked - ID:', linkId, 'Section ID:', sectionId);
                                if (!sectionId) {
                                    console.log('[Sidebar] Fallback: No section ID found, ignoring');
                                    return;
                                }
                                ev.preventDefault();
                                if (typeof navigateToSectionById === 'function') {
                                    try {
                                        await navigateToSectionById(sectionId);
                                    } catch (err) {
                                        console.error('Fallback navigation handler rejected for section', sectionId, err);
                                        const mainContentArea = document.getElementById('main-content-area');
                                        if (mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Navigation failed. See console for details.</p>`;
                                    }
                                }
                                updateActiveSidebarLink(anchor);
                            });
                            sidebarRoot.setAttribute('data-delegation-added', 'true');
                        }
                    } catch (err) {
                        console.warn('Failed to attach fallback sidebar delegation:', err);
                    }
                }, 500);
                navigateToSectionById('dashboard');
                initializeNotificationSystem();
            } else {
                // Do NOT hard-redirect on refresh if the PHP page already established a session
                // Some environments may fail the ajax session check intermittently. If the
                // server-side rendered page exposed window.currentUser, proceed using it.
                if (window.currentUser && window.currentUser.user_id) {
                    try {
                        showAppUI();
                        updateUserDisplay(window.currentUser);
                        updateSidebarAccess(window.currentUser.role_name);
                        attachSidebarListeners();
                        navigateToSectionById('dashboard');
                        initializeNotificationSystem();
                    } catch (e) {
                        console.warn('Proceeding with server-side session context failed:', e);
                    }
                } else {
                    // As a last resort, show a login prompt rather than forcing navigation
                    const initialMainContent = document.getElementById('main-content-area');
                    if (initialMainContent) {
                        initialMainContent.innerHTML = '<div class="bg-white rounded-lg shadow-sm border p-6"><h3 class="text-lg font-semibold mb-2">Session not detected</h3><p class="text-sm text-gray-600">Please <a class="text-blue-600 underline" href="index.php">log in</a> again.</p></div>';
                    }
                }
            }
        })
        .catch((error) => {
            console.error("Session check error:", error);
            // Instead of redirecting immediately, show an error message
            if (initialMainContent) {
                initialMainContent.innerHTML = '<p class="text-red-600">Error loading session. Please refresh the page.</p>';
            }
        });

    // Dashboard click handler uses imported function (no hardcoded override)
    // The attachSidebarListeners() already handles dashboard-link with displayDashboardSection

    // Add immediate click handlers for all sidebar links
    const sidebarLinks = [
        { id: 'employees-link', title: 'Employees', content: 'employee' },
        { id: 'documents-link', title: 'Documents', content: 'document' },
        { id: 'org-structure-link', title: 'Organization Structure', content: 'org' },
        { id: 'role-access-link', title: 'Role & Access', content: 'role' },
        { id: 'attendance-link', title: 'Attendance Records', content: 'attendance' },
        { id: 'timesheets-link', title: 'Timesheets', content: 'timesheet' },
        { id: 'schedules-link', title: 'Schedules', content: 'schedule' },
        { id: 'shifts-link', title: 'Shifts', content: 'shift' },
        { id: 'payroll-runs-link', title: 'Payroll Runs', content: 'payroll' },
        { id: 'salaries-link', title: 'Salaries', content: 'salary' },
        { id: 'bonuses-link', title: 'Bonuses', content: 'bonus' },
        { id: 'deductions-link', title: 'Deductions', content: 'deduction' },
        { id: 'payslips-link', title: 'Payslips', content: 'payslip' },
        { id: 'submit-claim-link', title: 'Submit Claim', content: 'claim' },
        { id: 'my-claims-link', title: 'My Claims', content: 'myclaim' },
        { id: 'claims-approval-link', title: 'Claims Approval', content: 'approval' },
        { id: 'claim-types-admin-link', title: 'Claim Types', content: 'claimtype' },
        { id: 'leave-requests-link', title: 'Leave Requests', content: 'leave' },
        { id: 'leave-balances-link', title: 'Leave Balances', content: 'balance' },
        { id: 'leave-types-link', title: 'Leave Types', content: 'leavetype' },
    // compensation-overview link removed from sidebarLinks
        { id: 'comp-plans-link', title: 'Compensation Plans', content: 'compplan' },
        { id: 'salary-adjust-link', title: 'Salary Adjustments', content: 'adjustment' },
        { id: 'incentives-link', title: 'Incentives', content: 'incentive' },
        { id: 'salary-grades-link', title: 'Salary Grades', content: 'salarygrades' },
        { id: 'pay-bands-link', title: 'Pay Bands', content: 'paybands' },
        { id: 'employee-mapping-link', title: 'Employee Mapping', content: 'employeemapping' },
        { id: 'workflows-link', title: 'Workflows', content: 'workflows' },
        { id: 'simulation-tools-link', title: 'Simulation Tools', content: 'simulationtools' },
        { id: 'analytics-dashboards-link', title: 'Analytics Dashboards', content: 'analytics' },
        { id: 'analytics-reports-link', title: 'Analytics Reports', content: 'report' },
        { id: 'analytics-metrics-link', title: 'Analytics Metrics', content: 'metric' },
        { id: 'user-management-link', title: 'User Management', content: 'user' }
    ];

    sidebarLinks.forEach(link => {
        const element = document.getElementById(link.id);
        if (element) {
            element.addEventListener('click', async (e) => {
                e.preventDefault();
                console.log(`${link.title} clicked - calling navigation function`);
                
                // Extract section ID from link ID
                const sectionId = link.id.replace(/-link$/, '');
                console.log(`[Individual Listener] Navigating to section: ${sectionId}`);
                
                // Call the navigation function
                if (typeof navigateToSectionById === 'function') {
                    try {
                        await navigateToSectionById(sectionId);
                    } catch (err) {
                        console.error('Navigation failed for', sectionId, err);
                    }
                }
            });
        }
    });


    // Make functions globally available through initializeSectionDisplayFunctions

    // --- Insert dynamic optional module registration before finishing init ---
    // (call helper to load optional modules without overriding the exported initializeSectionDisplayFunctions)
    if (typeof registerOptionalSectionModules === 'function') {
        try {
            await registerOptionalSectionModules();
        } catch (e) {
            console.warn('Optional modules registration failed (non-fatal):', e);
        }
    }

    console.log("HR System JS Initialized (Role-Based Landing).");
    console.log("Functions made globally available:", Object.keys(window).filter(key => key.startsWith('display')));

    }; // end runInit

    // Run the initialization routine
    await runInit();

} // end initializeApp()

// Replaced the duplicated exported initializer with a non-exported helper
async function registerOptionalSectionModules() {
    console.log('[Init] Registering optional section modules...');
    try {
        const [
            employeesMod,
            documentsMod,
            orgMod,
            attendanceMod,
            salariesMod,
            bonusesMod,
            deductionsMod,
            payslipsMod,
            analyticsMod,
            claimsMod,
            compensationMod,
            dashboardMod,
            profileMod
        ] = await Promise.all([
            import('./core_hr/employees.js'),
            import('./core_hr/documents.js'),
            import('./core_hr/org_structure.js'),
            import('./time_attendance/attendance.js'),
            import('./payroll/salaries.js'),
            import('./payroll/bonuses.js'),
            import('./payroll/deductions.js'),
            import('./payroll/payslips.js'),
            import('./analytics/analytics.js'),
            import('./claims/claims.js'),
            import('./compensation/compensation.js'),
            import('./dashboard/dashboard.js'),
            import('./profile/profile.js')
        ].map(p => p.catch(e => { console.warn('Optional module failed to load', e); return null; })));

        // Ensure a place for registered functions
        window.sectionDisplayFunctions = window.sectionDisplayFunctions || {};

        // Map found module exports to both sectionDisplayFunctions and top-level window.display* names
        if (employeesMod?.displayEmployeeSection) {
            window.sectionDisplayFunctions.displayEmployeeSection = employeesMod.displayEmployeeSection;
            window.displayEmployeeSection = employeesMod.displayEmployeeSection;
        }
        if (documentsMod?.displayDocumentsSection) {
            window.sectionDisplayFunctions.displayDocumentsSection = documentsMod.displayDocumentsSection;
            window.displayDocumentsSection = documentsMod.displayDocumentsSection;
        }
        if (orgMod?.displayOrgStructureSection) {
            window.sectionDisplayFunctions.displayOrgStructureSection = orgMod.displayOrgStructureSection;
            window.displayOrgStructureSection = orgMod.displayOrgStructureSection;
        }
        
        // Add Role Access functions to window object
        if (window.sectionDisplayFunctions.displayRoleAccessSection) {
            window.displayRoleAccessSection = window.sectionDisplayFunctions.displayRoleAccessSection;
        }
        if (attendanceMod?.displayAttendanceSection) {
            window.sectionDisplayFunctions.displayAttendanceSection = attendanceMod.displayAttendanceSection;
            window.displayAttendanceSection = attendanceMod.displayAttendanceSection;
        }
        if (salariesMod?.displaySalariesSection) {
            window.sectionDisplayFunctions.displaySalariesSection = salariesMod.displaySalariesSection;
            window.displaySalariesSection = salariesMod.displaySalariesSection;
        }
        if (bonusesMod?.displayBonusesSection) {
            window.sectionDisplayFunctions.displayBonusesSection = bonusesMod.displayBonusesSection;
            window.displayBonusesSection = bonusesMod.displayBonusesSection;
        }
        if (deductionsMod?.displayDeductionsSection) {
            window.sectionDisplayFunctions.displayDeductionsSection = deductionsMod.displayDeductionsSection;
            window.displayDeductionsSection = deductionsMod.displayDeductionsSection;
        }
        if (payslipsMod?.displayPayslipsSection) {
            window.sectionDisplayFunctions.displayPayslipsSection = payslipsMod.displayPayslipsSection;
            window.displayPayslipsSection = payslipsMod.displayPayslipsSection;
        }
        if (analyticsMod?.displayAnalyticsDashboardsSection) {
            window.sectionDisplayFunctions.displayAnalyticsDashboardsSection = analyticsMod.displayAnalyticsDashboardsSection;
            window.displayAnalyticsDashboardsSection = analyticsMod.displayAnalyticsDashboardsSection;
        }
        if (claimsMod?.displaySubmitClaimSection) {
            window.sectionDisplayFunctions.displaySubmitClaimSection = claimsMod.displaySubmitClaimSection;
            window.displaySubmitClaimSection = claimsMod.displaySubmitClaimSection;
            // also map other claims variants if exported
            if (claimsMod.displayMyClaimsSection) window.displayMyClaimsSection = claimsMod.displayMyClaimsSection;
            if (claimsMod.displayClaimsApprovalSection) window.displayClaimsApprovalSection = claimsMod.displayClaimsApprovalSection;
            if (claimsMod.displayClaimTypesAdminSection) window.displayClaimTypesAdminSection = claimsMod.displayClaimTypesAdminSection;
        }
        if (compensationMod?.displaySalaryAdjustmentsSection) {
            window.sectionDisplayFunctions.displaySalaryAdjustmentsSection = compensationMod.displaySalaryAdjustmentsSection;
            window.displaySalaryAdjustmentsSection = compensationMod.displaySalaryAdjustmentsSection;
        }
        if (dashboardMod?.renderDashboardSummary) {
            window.sectionDisplayFunctions.renderDashboardSummary = dashboardMod.renderDashboardSummary;
            if (!window.displayDashboardSection && dashboardMod.renderDashboardSummary) {
                // Keep existing dashboard loader but provide fallback
                window.displayDashboardSection = async () => {
                    const mainContentArea = document.getElementById('main-content-area');
                    await import('./dashboard/dashboard.js').then(mod => mod.renderDashboardSummary && mod.renderDashboardSummary(mainContentArea)).catch(()=>{});
                };
            }
        }
        if (profileMod?.renderUserProfile) {
            window.sectionDisplayFunctions.renderUserProfile = profileMod.renderUserProfile;
            window.displayUserProfileSection = profileMod.renderUserProfile;
        }

        console.log('[Init] Optional section modules registered.');
        return true;
    } catch (err) {
        console.error('[Init] Failed to register optional section modules', err);
        // Non-fatal: let app continue using the already attached window.display* functions
        return false;
    }
}

// Initialize the application when DOM is ready
// Use a small delay to ensure admin_landing.php initialization completes first
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Main] DOM loaded, initializing application...');
    // Small delay to avoid conflicts with admin_landing.php initialization
    setTimeout(async () => {
        try {
            console.log('[Main] Calling initializeApp...');
            await initializeApp();
            console.log('[Main] Application initialized successfully');
        } catch (error) {
            console.error('[Main] Error initializing application:', error);
        }
    }, 100);
});


// Debug helper: fetch a module URL as text and log headers + a preview to help identify why import() failed
async function fetchModuleDebug(relativePath) {
    try {
        // Build absolute URL relative to current document
        const base = window.location.pathname.replace(/\/[^/]*$/, '/');
        const url = new URL(relativePath, window.location.origin + base).href;
        console.groupCollapsed(`fetchModuleDebug: fetching ${url}`);
        const response = await fetch(url, { cache: 'no-store' });
        console.log('Response status:', response.status, response.statusText);
        try { console.log('Content-Type:', response.headers.get('Content-Type')); } catch (e) { console.warn('Could not read headers', e); }
        const text = await response.text();
        const preview = text.length > 3000 ? text.slice(0, 3000) + '\n\n...TRUNCATED...' : text;
        console.log('Response preview:\n', preview);
        console.groupEnd();
        return { status: response.status, contentType: response.headers.get('Content-Type'), textPreview: preview };
    } catch (e) {
        console.error('fetchModuleDebug error fetching module:', e);
        throw e;
    }
}

// Injects a UMD fallback script and resolves when loaded
async function loadUmdFallback(absoluteUrl) {
    return new Promise((resolve, reject) => {
        try {
            // Don't load twice
            if (document.querySelector(`script[data-umd-fallback][src="${absoluteUrl}"]`)) {
                return resolve(true);
            }
            const s = document.createElement('script');
            s.src = absoluteUrl;
            s.setAttribute('data-umd-fallback', '1');
            s.onload = () => resolve(true);
            s.onerror = (e) => reject(new Error('Failed to load UMD fallback script: ' + absoluteUrl));
            document.head.appendChild(s);
        } catch (e) { reject(e); }
    });
}

