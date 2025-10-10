/**
 * Shared Modal Utilities for Payroll Modules
 * Provides consistent modal patterns, confirmation dialogs, and UX components
 */

import { REST_API_URL } from '../config.js';

// Global state for modals
let currentModalState = {
    activeModal: null,
    currentPage: 1,
    itemsPerPage: 25,
    totalItems: 0,
    currentData: null
};

/**
 * Enhanced Confirmation Modal with customizable styling
 */
function showEnhancedConfirmationModal(title, message, description, buttonText, buttonColor, onConfirm) {
    // Create enhanced confirmation modal if it doesn't exist
    let modal = document.getElementById('enhanced-confirmation-modal');
    if (!modal) {
        modal = createEnhancedConfirmationModal();
    }
    
    // Update content
    document.getElementById('enhanced-confirmation-title').textContent = title;
    document.getElementById('enhanced-confirmation-message').textContent = message;
    document.getElementById('enhanced-confirmation-description').textContent = description;
    
    const confirmBtn = document.getElementById('enhanced-confirmation-confirm');
    confirmBtn.textContent = buttonText;
    
    // Update button color
    const colorClasses = {
        'blue': 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
        'green': 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
        'red': 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'yellow': 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        'purple': 'bg-purple-600 hover:bg-purple-700 focus:ring-purple-500',
        'indigo': 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'
    };
    
    confirmBtn.className = `w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm ${colorClasses[buttonColor] || colorClasses.blue}`;
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Add new event listener
    newConfirmBtn.addEventListener('click', () => {
        closeEnhancedConfirmationModal();
        onConfirm();
    });
    
    modal.classList.remove('hidden');
}

function createEnhancedConfirmationModal() {
    const modalHTML = `
        <div id="enhanced-confirmation-modal" class="fixed inset-0 z-60 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="enhanced-confirmation-title">Confirm Action</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" id="enhanced-confirmation-message">Are you sure you want to perform this action?</p>
                                    <p class="text-xs text-gray-400 mt-1" id="enhanced-confirmation-description">This action may take some time to complete.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="enhanced-confirmation-confirm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm
                        </button>
                        <button type="button" onclick="closeEnhancedConfirmationModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const wrapper = document.createElement('div');
    wrapper.innerHTML = modalHTML;
    document.body.appendChild(wrapper.firstElementChild);
    
    return document.getElementById('enhanced-confirmation-modal');
}

function closeEnhancedConfirmationModal() {
    const modal = document.getElementById('enhanced-confirmation-modal');
    if (modal) modal.classList.add('hidden');
}

/**
 * Inline Alert System
 */
function showInlineAlert(message, type = 'info') {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('inline-alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'inline-alert-container';
        alertContainer.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(alertContainer);
    }
    
    const alertId = 'alert-' + Date.now();
    const typeClasses = {
        'success': 'bg-green-50 border-green-200 text-green-800',
        'error': 'bg-red-50 border-red-200 text-red-800',
        'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info': 'bg-blue-50 border-blue-200 text-blue-800'
    };
    
    const iconClasses = {
        'success': 'fas fa-check-circle text-green-400',
        'error': 'fas fa-exclamation-circle text-red-400',
        'warning': 'fas fa-exclamation-triangle text-yellow-400',
        'info': 'fas fa-info-circle text-blue-400'
    };
    
    const alertHTML = `
        <div id="${alertId}" class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 ease-in-out translate-x-0 opacity-100">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="${iconClasses[type]}"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button onclick="closeInlineAlert('${alertId}')" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        closeInlineAlert(alertId);
    }, 5000);
}

function closeInlineAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.transform = 'translateX(100%)';
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

/**
 * Enhanced Details Modal with pagination support
 */
function createEnhancedDetailsModal(modalId, title, content, maxWidth = 'max-w-6xl') {
    const modalHTML = `
        <div id="${modalId}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle ${maxWidth} sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">${title}</h3>
                            <button type="button" onclick="closeModal('${modalId}')" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        ${content}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById(modalId);
    if (existingModal) existingModal.remove();
    
    // Add new modal
    const wrapper = document.createElement('div');
    wrapper.innerHTML = modalHTML;
    document.body.appendChild(wrapper.firstElementChild);
    
    return document.getElementById(modalId);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
}

/**
 * Pagination Component
 */
function renderPagination(containerId, pagination, onPageChange, itemsPerPage = 25) {
    const paginationEl = document.getElementById(containerId);
    if (!paginationEl) return;
    
    const totalPages = pagination.total_pages || 1;
    const currentPage = pagination.current_page || 1;
    const hasNext = pagination.has_next || false;
    const hasPrev = pagination.has_prev || false;
    
    if (totalPages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }
    
    let paginationHTML = `
        <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="changePage(${currentPage - 1})" 
                        ${!hasPrev ? 'disabled' : ''} 
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button onclick="changePage(${currentPage + 1})" 
                        ${!hasNext ? 'disabled' : ''} 
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">${((currentPage - 1) * itemsPerPage) + 1}</span>
                        to <span class="font-medium">${Math.min(currentPage * itemsPerPage, pagination.total || 0)}</span>
                        of <span class="font-medium">${pagination.total || 0}</span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
    `;
    
    // Previous button
    paginationHTML += `
        <button onclick="changePage(${currentPage - 1})" 
                ${!hasPrev ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        paginationHTML += `
            <button onclick="changePage(${i})" 
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${isActive ? 
                        'z-10 bg-blue-50 border-blue-500 text-blue-600' : 
                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                ${i}
            </button>
        `;
    }
    
    // Next button
    paginationHTML += `
        <button onclick="changePage(${currentPage + 1})" 
                ${!hasNext ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    paginationHTML += `
                    </nav>
                </div>
            </div>
        </div>
    `;
    
    paginationEl.innerHTML = paginationHTML;
    
    // Set up page change handler
    window.changePage = onPageChange;
}

/**
 * Status Badge Generator
 */
function getStatusBadgeClass(status, type = 'default') {
    const statusClasses = {
        'default': {
            'Active': 'bg-green-100 text-green-800',
            'Inactive': 'bg-gray-100 text-gray-800',
            'Pending': 'bg-yellow-100 text-yellow-800',
            'Completed': 'bg-green-100 text-green-800',
            'Approved': 'bg-purple-100 text-purple-800',
            'Locked': 'bg-red-100 text-red-800',
            'Draft': 'bg-gray-100 text-gray-800',
            'Processing': 'bg-blue-100 text-blue-800',
            'Generated': 'bg-blue-100 text-blue-800',
            'Error': 'bg-red-100 text-red-800'
        },
        'payroll': {
            'Draft': 'bg-gray-100 text-gray-800',
            'Processing': 'bg-blue-100 text-blue-800',
            'Completed': 'bg-green-100 text-green-800',
            'Approved': 'bg-purple-100 text-purple-800',
            'Locked': 'bg-red-100 text-red-800',
            'Cancelled': 'bg-gray-100 text-gray-800'
        },
        'payslip': {
            'Generated': 'bg-blue-100 text-blue-800',
            'Processed': 'bg-green-100 text-green-800',
            'Approved': 'bg-purple-100 text-purple-800',
            'Locked': 'bg-red-100 text-red-800',
            'Error': 'bg-red-100 text-red-800'
        }
    };
    
    const classes = statusClasses[type] || statusClasses.default;
    return classes[status] || 'bg-gray-100 text-gray-800';
}

/**
 * Loading State Component
 */
function showLoadingState(containerId, message = 'Loading...') {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="text-gray-500 mt-2">${message}</p>
            </div>
        `;
    }
}

/**
 * Empty State Component
 */
function showEmptyState(containerId, icon = 'fas fa-inbox', title = 'No data found', message = 'There are no items to display.') {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="${icon} text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">${title}</h3>
                <p class="text-gray-500">${message}</p>
            </div>
        `;
    }
}

/**
 * Error State Component
 */
function showErrorState(containerId, errorMessage) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-red-300 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Data</h3>
                <p class="text-red-500">${errorMessage}</p>
            </div>
        `;
    }
}

/**
 * Load payroll runs for filter dropdowns
 */
async function loadPayrollRunsForFilter(selectId) {
    try {
        const response = await fetch(`${REST_API_URL}payroll-v2/runs`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            const select = document.getElementById(selectId);
            if (select) {
                if (data.success && data.data && Array.isArray(data.data.items)) {
                    // Keep the first option
                    const firstOption = select.querySelector('option');
                    select.innerHTML = '';
                    select.appendChild(firstOption);
                    
                    data.data.items.forEach(run => {
                        const option = document.createElement('option');
                        option.value = run.PayrollRunID;
                        option.textContent = `Run #${run.PayrollRunID} - ${run.PayPeriodStart} to ${run.PayPeriodEnd}`;
                        select.appendChild(option);
                    });
                } else {
                    console.warn('Invalid payroll runs data format:', data);
                }
            }
        } else {
            console.error('Failed to load payroll runs:', response.status);
        }
    } catch (error) {
        console.error('Error loading payroll runs for filter:', error);
    }
}

// Global functions for modal management
window.closeModal = closeModal;
window.closeInlineAlert = closeInlineAlert;
window.closeEnhancedConfirmationModal = closeEnhancedConfirmationModal;
window.showEnhancedConfirmationModal = showEnhancedConfirmationModal;
window.showInlineAlert = showInlineAlert;
window.getStatusBadgeClass = getStatusBadgeClass;
window.loadPayrollRunsForFilter = loadPayrollRunsForFilter;
