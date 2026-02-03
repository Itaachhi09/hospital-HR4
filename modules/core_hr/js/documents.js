/**
 * HR Core - Document Viewer Module (READ-ONLY)
 * v4.0 - Enhanced for HR Core integration with HR1 and HR2 systems
 * v3.0 - Refactored for HR Core integration - Read-only document viewer
 * v2.1 - Integrated SweetAlert for notifications and confirmations.
 * v2.0 - Refined rendering functions for XSS protection.
 * 
 * Purpose: Display integrated documents from HR1 and HR2 systems
 * - No upload/delete operations (read-only)
 * - Document categorization (A, B, C) as per hospital requirements
 * - File preview for PDF, images, and docx files
 * - Hospital-blue theme with clean UI
 */
import { REST_API_URL, LEGACY_API_URL, populateEmployeeDropdown } from '../utils.js'; // Import shared functions/constants

/**
 * Displays the Employee Documents section.
 * Sets up the UI for uploading and viewing documents.
 */
export async function displayDocumentsSection() { 
    console.log("[Display] Displaying Documents Section...");
    const pageTitleElement = document.getElementById('page-title');
    const pageSubtitleElement = document.getElementById('page-subtitle');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !pageSubtitleElement || !mainContentArea) {
        console.error("displayDocumentsSection: Core DOM elements not found.");
        if(mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error initializing document section elements.</p>`;
        return;
    }

    pageTitleElement.textContent = 'HR Core - Document Viewer';
    pageSubtitleElement.textContent = 'Integrated employee documents from HR1 (Recruitment) and HR2 (Training & Performance)';
    mainContentArea.innerHTML = `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Header with Actions -->
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                        <h3 class="text-xl font-semibold text-blue-900">HR Core Document Library</h3>
                        <p class="text-sm text-blue-700">View-only access to employee documents from integrated HR systems</p>
                </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshDocuments()" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-sync mr-2"></i>Refresh
                        </button>
                        <button onclick="exportDocuments()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-download mr-2"></i>Export
                    </button>
                        <button onclick="showIntegrationStatus()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-info-circle mr-2"></i>Integration Status
                    </button>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                        </div>
                            <input id="doc-search-input" type="text" placeholder="Search by employee name or document title..." 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <!-- Filter Dropdowns -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="doc-module-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Modules</option>
                            <option value="HR1">HR1 (Recruitment)</option>
                            <option value="HR2">HR2 (Training & Performance)</option>
                        </select>
                        
                        <select id="doc-category-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <option value="A">Category A - Initial Application</option>
                            <option value="B">Category B - Pre-Employment</option>
                            <option value="C">Category C - Position-Specific</option>
                        </select>
                        
                        <select id="doc-status-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                            <option value="expired">Expired</option>
                        </select>
                        
                        <button onclick="applyDocumentFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        
                        <button onclick="clearDocumentFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="px-6 py-4">
                <div id="documents-list-container" class="overflow-x-auto">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-500 mt-2">Loading HR Core documents...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Preview Modal -->
        <div id="document-preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Document Preview</h3>
                            <button onclick="closeDocumentPreview()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div id="document-preview-content" class="w-full h-96 border border-gray-300 rounded-lg">
                            <!-- Document preview will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    requestAnimationFrame(async () => {
        // Load documents
        await loadDocuments();
    });
}

/**
 * Applies the selected employee filter and reloads the document list.
 */
function applyDocumentFilter() {
    const searchTerm = document.getElementById('doc-search-input')?.value?.trim();
    const module = document.getElementById('doc-module-filter')?.value;
    const category = document.getElementById('doc-category-filter')?.value;
    const status = document.getElementById('doc-status-filter')?.value;
    loadDocuments(null, { search: searchTerm, module_origin: module, category: category, status: status }); 
}

/**
 * Fetches documents from the API based on the optional employee filter.
 */
async function loadDocuments(employeeId = null, extra = {}) {
    console.log(`[Load] Loading HR Core Documents... (Employee ID: ${employeeId || 'All'})`);
    const container = document.getElementById('documents-list-container');
    if (!container) return;
    container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="text-gray-500 mt-2">Loading HR Core documents...</p></div>'; 

    // Use HR Core API endpoint
    console.log('REST_API_URL:', REST_API_URL);
    let url = `${REST_API_URL}hrcore/documents`;
    console.log('Constructed URL:', url);
    
    const params = new URLSearchParams();
    if (employeeId) params.set('emp_id', String(employeeId));
    if (extra && extra.search) params.set('search', extra.search);
    if (extra && extra.category) params.set('category', extra.category);
    if (extra && extra.module_origin) params.set('module_origin', extra.module_origin);
    if (extra && extra.status) params.set('status', extra.status);
    const qs = params.toString();
    if (qs) url += `?${qs}`;
    
    console.log('Full URL with params:', url);

    try {
        const response = await fetch(url, { 
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (result.success && result.data) {
            renderDocumentsTable(result.data); 
        } else {
            console.error("Error fetching HR Core documents:", result.message);
            container.innerHTML = `<p class="text-red-500 text-center py-4">Error: ${result.message || 'Failed to load documents'}</p>`;
        }
    } catch (error) {
        console.error('Error loading HR Core documents:', error);
        container.innerHTML = `<p class="text-red-500 text-center py-4">Could not load HR Core documents. ${error.message}</p>`;
    }
}

/**
 * Renders the list of documents into an HTML table.
 */
function renderDocumentsTable(documents) {
    console.log("[Render] Rendering HR Core Documents Table...");
    const container = document.getElementById('documents-list-container');
    if (!container) return;

    if (!documents || documents.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No HR Core documents found</h3>
                <p class="text-gray-500">No documents match your current filters or no documents have been integrated yet.</p>
            </div>
        `;
        return;
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';
    table.innerHTML = `
        <thead class="bg-blue-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Employee ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Employee Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Document Title</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Category</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Origin Module</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Uploaded By</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Upload Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
    `;

    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200';

    documents.forEach(doc => {
        const row = tbody.insertRow();
        row.id = `doc-row-${doc.doc_id}`;

        const createCell = (text, className = '') => {
            const cell = row.insertCell();
            cell.className = `px-4 py-3 whitespace-nowrap text-sm ${className}`;
            cell.textContent = text ?? ''; 
            return cell;
        };

        // Employee ID
        createCell(doc.emp_id, 'font-mono text-gray-600');

        // Employee Name
        createCell(doc.employee_name, 'font-medium text-gray-900');

        // Document Title
        createCell(doc.title, 'text-gray-700');

        // Category
        const categoryCell = row.insertCell();
        categoryCell.className = 'px-4 py-3 whitespace-nowrap text-xs';
        if (doc.category) {
            const chip = document.createElement('span');
            chip.textContent = `Category ${doc.category}`;
            chip.className = 'inline-block px-2 py-1 rounded-full text-white text-xs ' + getCategoryBadgeClass(doc.category);
            categoryCell.appendChild(chip);
        } else {
            categoryCell.innerHTML = '<span class="text-gray-400 italic">N/A</span>';
        }

        // Origin Module
        const originCell = row.insertCell();
        originCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        if (doc.module_origin) {
            const badge = document.createElement('span');
            badge.textContent = doc.module_origin;
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + getModuleBadgeClass(doc.module_origin);
            originCell.appendChild(badge);
        } else {
            originCell.innerHTML = '<span class="text-gray-400 italic">N/A</span>';
        }

        // Uploaded By
        createCell(doc.uploaded_by, 'text-gray-600');

        // Upload Date
        const uploadDate = doc.upload_date ? new Date(doc.upload_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) : 'N/A';
        createCell(uploadDate, 'text-gray-500');

        // Status
        const statusCell = row.insertCell();
        statusCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        if (doc.status) {
            const statusBadge = document.createElement('span');
            statusBadge.textContent = doc.status.charAt(0).toUpperCase() + doc.status.slice(1);
            statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + getStatusBadgeClass(doc.status);
            statusCell.appendChild(statusBadge);
        } else {
            statusCell.innerHTML = '<span class="text-gray-400 italic">N/A</span>';
        }

        // Actions
        const actionsCell = row.insertCell();
        actionsCell.className = 'px-4 py-3 whitespace-nowrap text-sm font-medium';
        
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'flex items-center space-x-2';
        
        const viewBtn = document.createElement('button');
        viewBtn.innerHTML = '<i class="fas fa-eye mr-1"></i>View';
        viewBtn.className = 'inline-flex items-center px-3 py-1 border border-blue-300 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500';
        viewBtn.onclick = () => previewDocument(doc.doc_id, doc.title, doc.file_type);
        actionsDiv.appendChild(viewBtn);

        const downloadBtn = document.createElement('button');
        downloadBtn.innerHTML = '<i class="fas fa-download mr-1"></i>Download';
        downloadBtn.className = 'inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500';
        downloadBtn.onclick = () => downloadDocument(doc.doc_id, doc.title);
        actionsDiv.appendChild(downloadBtn);
        
        actionsCell.appendChild(actionsDiv);
    });
    container.innerHTML = '';
    container.appendChild(table);
}

// Helper functions for badge styling
function getCategoryBadgeClass(category) {
    switch(category) {
        case 'A': return 'bg-blue-600';
        case 'B': return 'bg-green-600';
        case 'C': return 'bg-purple-600';
        default: return 'bg-gray-600';
    }
}

function getModuleBadgeClass(module) {
    switch(module) {
        case 'HR1': return 'bg-blue-100 text-blue-800';
        case 'HR2': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'archived': return 'bg-gray-100 text-gray-800';
        case 'expired': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function mapCategoryToChip(category){
    const c = (category||'').toLowerCase();
    if (c.includes('license')) return 'bg-purple-600';
    if (c.includes('contract')) return 'bg-blue-600';
    if (c.includes('certificate')) return 'bg-green-600';
    if (c.includes('id')) return 'bg-yellow-600';
    return 'bg-gray-600';
}

/**
 * Attaches click event listeners to all delete document buttons using event delegation.
 */
function attachDeleteListeners() {
    const container = document.querySelector('.document-action-container'); 
    if (container) {
        container.removeEventListener('click', handleDocumentActionClick);
        container.addEventListener('click', handleDocumentActionClick);
    }
}

/**
 * Handles the click event for a delete document button.
 * Prompts for confirmation using SweetAlert.
 */
async function handleDocumentActionClick(event) {
    const downloadBtn = event.target.closest('.download-doc-btn');
    const previewBtn = event.target.closest('.preview-doc-btn');
    const tokenBtn = event.target.closest('.token-doc-btn');
    if (!downloadBtn && !previewBtn && !tokenBtn) return;

    if (previewBtn) {
        const documentId = previewBtn.dataset.docId;
        const name = previewBtn.dataset.docName || '';
        if (!documentId) return;
        previewDocument(documentId, name);
        return;
    }

    if (downloadBtn) {
        const documentId = downloadBtn.dataset.docId;
        if (!documentId) return;
        downloadDocument(documentId);
        return;
    }

    if (tokenBtn) {
        const documentId = tokenBtn.dataset.docId;
        if (!documentId) return;
        issueSecureLink(documentId);
        return;
    }
}

/**
 * Download document function
 */
async function downloadDocument(documentId) {
    try {
        const url = `${REST_API_URL}hrcore/documents/${encodeURIComponent(documentId)}/download`;
        const response = await fetch(url, { 
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const blob = await response.blob();
        const downloadUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = `document_${documentId}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(downloadUrl);
        
        Swal.fire('Success', 'Document downloaded successfully!', 'success');
    } catch (error) {
        console.error('Download failed:', error);
        Swal.fire('Error', 'Failed to download document.', 'error');
    }
}

async function previewDocument(documentId, documentName){
    try{
        const url = `${REST_API_URL}hrcore/documents/${encodeURIComponent(documentId)}/preview`;
        const res = await fetch(url, { 
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const blob = await res.blob();
        const ext = (documentName?.split('.').pop() || '').toLowerCase();
        const objUrl = URL.createObjectURL(blob);
        let html = '';
        if (['png','jpg','jpeg','gif','webp'].includes(ext)){
            html = `<img src="${objUrl}" alt="${documentName}" class="max-h-[70vh] mx-auto rounded border" />`;
        } else if (ext === 'pdf'){
            html = `<iframe src="${objUrl}" class="w-full min-h-[70vh]" style="border:0;"></iframe>`;
        } else {
            html = `<p class="text-gray-700 text-sm">Preview not available for this file type. You can download it instead.</p>`;
        }
        await Swal.fire({
            title: documentName || 'Preview',
            html,
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
        URL.revokeObjectURL(objUrl);
    }catch(err){
        console.error('Preview failed', err);
        Swal.fire('Preview Failed', String(err.message || err), 'error');
    }
}

async function issueSecureLink(documentId){
    try{
        const ttl = 600;
        const res = await fetch(`${REST_API_URL}hrcore/documents/${encodeURIComponent(documentId)}/token`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ ttl }), credentials: 'include' });
        const data = await res.json();
        if (!res.ok || !data || data.success === false) throw new Error(data?.message || `HTTP ${res.status}`);
        const token = data.data?.token || data.token;
        const expiresAt = data.data?.expires_at || data.expires_at;
        const downloadUrl = `${window.location.origin}${REST_API_URL}hrcore/documents/${encodeURIComponent(documentId)}/download?token=${encodeURIComponent(token)}`;
        await Swal.fire({
            icon: 'success',
            title: 'Secure Link Created',
            html: `<div class="text-left text-sm"><div class="mb-2"><strong>Expires:</strong> ${expiresAt}</div><div class="bg-gray-50 border rounded p-2 break-all">${downloadUrl}</div></div>`,
            confirmButtonText: 'Copy',
            showCancelButton: true
        }).then(res=>{
            if (res.isConfirmed) {
                navigator.clipboard?.writeText(downloadUrl);
            }
        });
    }catch(err){
        console.error('Token link failed', err);
        Swal.fire('Failed to create link', String(err.message||err), 'error');
    }
}


/**
 * Export documents function
 */
window.exportDocuments = function() {
    console.log("[Export] Exporting document data...");
    
    const documents = document.querySelectorAll('#documents-list-container tbody tr');
    if (!documents || documents.length === 0) {
        Swal.fire('No Data', 'No documents available to export.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Export Documents',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'CSV',
        cancelButtonText: 'PDF',
        showDenyButton: true,
        denyButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            exportDocumentsToCSV();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportDocumentsToPDF();
        }
    });
};

/**
 * Export documents to CSV
 */
function exportDocumentsToCSV() {
    try {
        const headers = ['Employee', 'Type', 'Category', 'Filename', 'Source System', 'Uploaded', 'Expiry'];
        const rows = Array.from(document.querySelectorAll('#documents-list-container tbody tr')).map(row => {
            const cells = row.querySelectorAll('td');
            return [
                cells[0]?.textContent?.trim() || '',
                cells[1]?.textContent?.trim() || '',
                cells[2]?.textContent?.trim() || '',
                cells[3]?.textContent?.trim() || '',
                cells[4]?.textContent?.trim() || '',
                cells[5]?.textContent?.trim() || '',
                cells[6]?.textContent?.trim() || ''
            ];
        });

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        downloadFile(csvContent, 'documents.csv', 'text/csv');
        Swal.fire('Success', 'Documents exported to CSV successfully!', 'success');
    } catch (error) {
        console.error('CSV export error:', error);
        Swal.fire('Error', 'Failed to export CSV file.', 'error');
    }
}

/**
 * Export documents to PDF (placeholder)
 */
function exportDocumentsToPDF() {
    Swal.fire({
        title: 'PDF Export',
        text: 'PDF export functionality will be implemented in a future update. For now, please use the CSV export option.',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

/**
 * Refresh documents function
 */
window.refreshDocuments = function() {
    console.log("[Refresh] Refreshing documents...");
    loadDocuments();
};

/**
 * Download file utility
 */
function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

/**
 * Apply document filters
 */
window.applyDocumentFilters = function() {
    applyDocumentFilter();
};

/**
 * Clear document filters
 */
window.clearDocumentFilters = function() {
    document.getElementById('doc-module-filter').value = '';
    document.getElementById('doc-category-filter').value = '';
    document.getElementById('doc-status-filter').value = '';
    document.getElementById('doc-search-input').value = '';
    
    // Reload all documents
    loadDocuments();
};

/**
 * Refresh documents
 */
window.refreshDocuments = function() {
    loadDocuments();
};

/**
 * Export documents
 */
window.exportDocuments = function() {
    // Placeholder for export functionality
    console.log('Export documents functionality not yet implemented');
};

/**
 * Preview document in modal
 */
window.previewDocument = function(docId, title, fileType) {
    const modal = document.getElementById('document-preview-modal');
    const content = document.getElementById('document-preview-content');
    const modalTitle = document.getElementById('modal-title');
    
    modalTitle.textContent = `Preview: ${title}`;
    
    // Show loading
    content.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="text-gray-500 mt-2">Loading preview...</p>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    
    // Load preview based on file type
    if (fileType && fileType.includes('pdf')) {
        content.innerHTML = `
            <iframe src="/api/hrcore/documents/${docId}/preview" 
                    class="w-full h-full border-0 rounded-lg"
                    title="Document Preview">
            </iframe>
        `;
    } else if (fileType && fileType.startsWith('image/')) {
        content.innerHTML = `
            <img src="/api/hrcore/documents/${docId}/preview" 
                 class="w-full h-full object-contain"
                 alt="Document Preview">
        `;
    } else {
        content.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <i class="fas fa-file text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Preview not available for this file type</p>
                    <button onclick="downloadDocument(${docId}, '${title}')" 
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>Download to View
                    </button>
                </div>
            </div>
        `;
    }
};

/**
 * Close document preview modal
 */
window.closeDocumentPreview = function() {
    const modal = document.getElementById('document-preview-modal');
    modal.classList.add('hidden');
};

/**
 * Download document
 */
window.downloadDocument = function(docId, title) {
    const link = document.createElement('a');
    link.href = `/api/hrcore/documents/${docId}/download`;
    link.download = title || 'document';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

/**
 * Show integration status
 */
window.showIntegrationStatus = function() {
    // This would show a modal with integration status
    console.log('Integration status functionality not yet implemented');
};
