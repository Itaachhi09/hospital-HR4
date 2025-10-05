/**
 * Core HR - Documents Module
 * v2.1 - Integrated SweetAlert for notifications and confirmations.
 * v2.0 - Refined rendering functions for XSS protection.
 */
import { API_BASE_URL, populateEmployeeDropdown, isReadOnlyMode } from '../utils.js'; // Import shared functions/constants

/**
 * Displays the Employee Documents section.
 * Sets up the UI for uploading and viewing documents.
 */
export async function displayDocumentsSection() { 
    console.log("[Display] Displaying Documents Section...");
    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');

    if (!pageTitleElement || !mainContentArea) {
        console.error("displayDocumentsSection: Core DOM elements not found.");
        if(mainContentArea) mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error initializing document section elements.</p>`;
        return;
    }

    pageTitleElement.textContent = 'Employee Documents';
    mainContentArea.innerHTML = `
        <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA] space-y-6">
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-lg font-semibold text-[#4E3B2A] mb-3 font-header">Upload New Document</h3>
                <form id="upload-document-form" class="space-y-4" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="doc-employee-select" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                            <select id="doc-employee-select" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                <option value="">Loading employees...</option>
                            </select>
                        </div>
                        <div>
                            <label for="doc-type" class="block text-sm font-medium text-gray-700 mb-1">Document Type:</label>
                            <input type="text" id="doc-type" name="document_type" required placeholder="e.g., Contract, ID, Certificate" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="doc-category" class="block text-sm font-medium text-gray-700 mb-1">Category/Tag:</label>
                            <input type="text" id="doc-category" name="category" placeholder="Contract, Certificate, License, ID" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="doc-expiry" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date (optional):</label>
                            <input type="date" id="doc-expiry" name="expires_on" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="doc-file" class="block text-sm font-medium text-gray-700 mb-1">File:</label>
                            <input type="file" id="doc-file" name="document_file" required class="w-full p-1.5 border border-gray-300 rounded-md shadow-sm text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-[#F7E6CA] file:text-[#4E3B2A] hover:file:bg-[#EADDCB]">
                            <p class="mt-1 text-xs text-gray-500">Allowed: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</p>
                            <div id="doc-dropzone" class="mt-2 border-2 border-dashed border-gray-300 rounded-md p-4 text-center text-sm text-gray-600 hover:border-[#4E3B2A] cursor-pointer">
                                Drag & drop file here or click above
                            </div>
                            <div id="doc-upload-preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="pt-2">
                            <button type="submit" class="px-4 py-2 bg-[#4727ff] text-white rounded-md hover:bg-[#3a1fcc] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#4727ff] transition duration-150 ease-in-out">
                                Upload Document
                            </button>
                        </div>
                </form>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-[#4E3B2A] mb-3 font-header">Existing Documents</h3>
                <div class="flex flex-wrap gap-4 mb-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search:</label>
                        <input id="doc-search" type="text" placeholder="Search name or doc" class="w-full sm:w-64 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                     <div>
                       <label for="filter-doc-employee" class="block text-sm font-medium text-gray-700 mb-1">Filter by Employee:</label>
                       <select id="filter-doc-employee" class="w-full sm:w-auto p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                           <option value="">All Employees</option>
                           </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
                        <input id="filter-doc-category" type="text" placeholder="Contract, License, ..." class="w-full sm:w-48 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiring Within (days):</label>
                        <input id="filter-doc-expiring" type="number" min="1" class="w-24 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]" placeholder="30">
                    </div>
                    <div>
                       <button id="filter-doc-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                           Filter
                       </button>
                    </div>
                </div>
                <div id="documents-list-container" class="overflow-x-auto">
                    <p class="text-center py-4">Loading documents...</p> 
                </div>
            </div>
        </div>`;

    requestAnimationFrame(async () => {
        // Hide upload form and destructive actions in read-only mode
        try {
            const ro = await isReadOnlyMode();
            if (ro) {
                const uploadBlock = document.getElementById('upload-document-form');
                if (uploadBlock) uploadBlock.closest('.border-b')?.classList.add('hidden');
            }
        } catch (e) { /* ignore */ }
        await populateEmployeeDropdown('doc-employee-select'); 
        await populateEmployeeDropdown('filter-doc-employee', true); 

        const uploadForm = document.getElementById('upload-document-form');
        if (uploadForm) {
            if (!uploadForm.hasAttribute('data-listener-attached')) {
                uploadForm.addEventListener('submit', handleUploadDocument);
                uploadForm.setAttribute('data-listener-attached', 'true');
            }
            setupDragAndDrop();
            const fileInput = document.getElementById('doc-file');
            fileInput?.addEventListener('change', (e)=>{
                const f = e.target.files && e.target.files[0];
                if (f) renderUploadPreview(f);
            });
        } else {
            console.error("Upload Document form not found after injecting HTML.");
        }

        const filterBtn = document.getElementById('filter-doc-btn');
        if (filterBtn) {
            if (!filterBtn.hasAttribute('data-listener-attached')) {
                filterBtn.addEventListener('click', applyDocumentFilter);
                filterBtn.setAttribute('data-listener-attached', 'true');
            }
        } else {
            console.error("Filter Document button not found after injecting HTML.");
        }
        await loadDocuments();
    });
}

/**
 * Applies the selected employee filter and reloads the document list.
 */
function applyDocumentFilter() {
    const employeeId = document.getElementById('filter-doc-employee')?.value;
    const q = document.getElementById('doc-search')?.value?.trim();
    const cat = document.getElementById('filter-doc-category')?.value?.trim();
    const days = document.getElementById('filter-doc-expiring')?.value?.trim();
    loadDocuments(employeeId, { search: q, category: cat, expiring_within_days: days }); 
}

/**
 * Fetches documents from the API based on the optional employee filter.
 */
async function loadDocuments(employeeId = null, extra = {}) {
    console.log(`[Load] Loading Documents... (Employee ID: ${employeeId || 'All'})`);
    const container = document.getElementById('documents-list-container');
    if (!container) return;
    container.innerHTML = '<p class="text-center py-4">Loading documents...</p>'; 

    // Use read-only aggregator endpoint; employees see only their own via server-side auth
    let url = `${API_BASE_URL.replace(/php\/api\/$/, 'api/') }hr-core/documents`;
    const params = new URLSearchParams();
    if (employeeId) params.set('employee_id', String(employeeId));
    if (extra && extra.search) params.set('search', extra.search);
    if (extra && extra.category) params.set('category', extra.category);
    if (extra && extra.expiring_within_days) params.set('expiring_within_days', extra.expiring_within_days);
    const qs = params.toString();
    if (qs) url += `?${qs}`;

    try {
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();
        const documents = Array.isArray(result) ? result
            : (Array.isArray(result?.data?.items) ? result.data.items
            : (Array.isArray(result?.data) ? result.data : []));
        renderDocumentsTable(documents); 
    } catch (error) {
        console.error('Error loading documents:', error);
        container.innerHTML = `<p class="text-red-500 text-center py-4">Could not load documents. ${error.message}</p>`;
    }
}

/**
 * Renders the list of documents into an HTML table.
 */
function renderDocumentsTable(documents) {
    console.log("[Render] Rendering Documents Table...");
    const container = document.getElementById('documents-list-container');
    if (!container) return;
    container.innerHTML = '';

    if (!documents || documents.length === 0) {
        const noDataMessage = document.createElement('p');
        noDataMessage.className = 'text-center py-4 text-gray-500';
        noDataMessage.textContent = 'No documents found for the selected criteria.';
        container.appendChild(noDataMessage);
        return;
    }

    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200 border border-gray-300';

    const thead = table.createTHead();
    thead.className = 'bg-gray-50';
    const headerRow = thead.insertRow();
    const headers = ['Employee', 'Type', 'Category', 'Filename', 'Uploaded', 'Expiry', 'Actions'];
    headers.forEach(text => {
        const th = document.createElement('th');
        th.scope = 'col';
        th.className = 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = text; 
        headerRow.appendChild(th);
    });

    const tbody = table.createTBody();
    tbody.className = 'bg-white divide-y divide-gray-200 document-action-container';

    documents.forEach(doc => {
        const row = tbody.insertRow();
        row.id = `doc-row-${doc.DocumentID}`;

        const createCell = (text) => {
            const cell = row.insertCell();
            cell.className = 'px-4 py-3 whitespace-nowrap text-sm';
            cell.textContent = text ?? ''; 
            return cell;
        };

        createCell(doc.EmployeeName).classList.add('font-medium', 'text-gray-900');

        const typeCell = createCell(doc.DocumentType);
        if (!doc.DocumentType) {
            typeCell.innerHTML = '<span class="text-gray-400 italic">N/A</span>'; 
        } else {
            typeCell.classList.add('text-gray-700');
        }

        const categoryCell = row.insertCell();
        categoryCell.className = 'px-4 py-3 whitespace-nowrap text-xs';
        if (doc.Category) {
            const chip = document.createElement('span');
            chip.textContent = doc.Category;
            chip.className = 'inline-block px-2 py-1 rounded-full text-white text-xs ' + mapCategoryToChip(doc.Category);
            categoryCell.appendChild(chip);
        } else {
            categoryCell.innerHTML = '<span class="text-gray-400 italic">N/A</span>';
        }

        const filenameCell = row.insertCell();
        filenameCell.className = 'px-4 py-3 whitespace-nowrap text-sm text-gray-700';
        // Use secure download endpoint within app (session-based)
        const filePath = `${API_BASE_URL.replace(/php\/api\/$/, 'api/')}documents/${encodeURIComponent(doc.DocumentID)}/download`;
        const link = document.createElement('a');
        link.href = filePath;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'text-blue-600 hover:underline';
        link.title = 'View Document';
        link.textContent = doc.DocumentName || ''; 
        if (!doc.DocumentName) {
            link.innerHTML = '<span class="text-gray-400 italic">N/A</span>'; 
        }
        filenameCell.appendChild(link);

        const uploadDate = doc.UploadedAt ? new Date(doc.UploadedAt).toLocaleDateString() : (doc.UploadDate ? new Date(doc.UploadDate).toLocaleDateString() : 'N/A');
        createCell(uploadDate).classList.add('text-gray-500');

        const expiryCell = row.insertCell();
        expiryCell.className = 'px-4 py-3 whitespace-nowrap text-sm';
        if (doc.ExpiresOn) {
            const dt = new Date(doc.ExpiresOn);
            const daysLeft = Math.ceil((dt - new Date()) / (1000*60*60*24));
            const badge = document.createElement('span');
            badge.textContent = dt.toLocaleDateString();
            badge.className = 'inline-block px-2 py-1 rounded text-xs ' + (daysLeft <= 30 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700');
            expiryCell.appendChild(badge);
        } else {
            expiryCell.innerHTML = '<span class="text-gray-400 italic">—</span>';
        }

        const actionsCell = row.insertCell();
        actionsCell.className = 'px-4 py-3 whitespace-nowrap text-sm font-medium space-x-3';
        const previewBtn = document.createElement('button');
        previewBtn.className = 'text-blue-600 hover:text-blue-800 preview-doc-btn';
        previewBtn.dataset.docId = doc.DocumentID;
        previewBtn.dataset.docName = doc.DocumentName || '';
        previewBtn.title = 'Preview';
        previewBtn.innerHTML = '<i class="fas fa-eye"></i> Preview';
        actionsCell.appendChild(previewBtn);

        (async ()=>{
            const ro = await isReadOnlyMode();
            if (!ro) {
                const tokenBtn = document.createElement('button');
                tokenBtn.className = 'text-green-600 hover:text-green-800 token-doc-btn';
                tokenBtn.dataset.docId = doc.DocumentID;
                tokenBtn.title = 'Create secure link';
                tokenBtn.innerHTML = '<i class="fas fa-link"></i> Link';
                actionsCell.appendChild(tokenBtn);
            }
        })();

        (async ()=>{
            const ro = await isReadOnlyMode();
            if (!ro) {
                const deleteButton = document.createElement('button');
                deleteButton.className = 'text-red-600 hover:text-red-800 delete-doc-btn';
                deleteButton.dataset.docId = doc.DocumentID;
                deleteButton.title = 'Delete Document';
                deleteButton.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                actionsCell.appendChild(deleteButton);
            }
        })();
    });
    container.appendChild(table);
    attachDeleteListeners();
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
    const delBtn = event.target.closest('.delete-doc-btn');
    const previewBtn = event.target.closest('.preview-doc-btn');
    const tokenBtn = event.target.closest('.token-doc-btn');
    if (!delBtn && !previewBtn && !tokenBtn) return;

    if (previewBtn) {
        const documentId = previewBtn.dataset.docId;
        const name = previewBtn.dataset.docName || '';
        if (!documentId) return;
        previewDocument(documentId, name);
        return;
    }

    if (tokenBtn) {
        const documentId = tokenBtn.dataset.docId;
        if (!documentId) return;
        issueSecureLink(documentId);
        return;
    }

    if (delBtn) {
        const documentId = delBtn.dataset.docId;
        if (!documentId) {
            console.error("Could not find document ID on delete button.");
            Swal.fire('Error', 'Could not identify the document to delete.', 'error');
            return;
        }
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete document ID ${documentId}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        });
        if (result.isConfirmed) deleteDocument(documentId);
    }
}

function setupDragAndDrop(){
    const dz = document.getElementById('doc-dropzone');
    const fileInput = document.getElementById('doc-file');
    if (!dz || !fileInput) return;
    const highlight = (on)=>{ dz.classList.toggle('border-[#4E3B2A]', on); dz.classList.toggle('bg-gray-50', on); };
    dz.addEventListener('dragover', (e)=>{ e.preventDefault(); highlight(true); });
    dz.addEventListener('dragleave', (e)=>{ e.preventDefault(); highlight(false); });
    dz.addEventListener('drop', (e)=>{
        e.preventDefault(); highlight(false);
        const files = e.dataTransfer?.files;
        if (files && files.length){
            fileInput.files = files;
            renderUploadPreview(files[0]);
        }
    });
    dz.addEventListener('click', ()=> fileInput.click());
}

function renderUploadPreview(file){
    const cont = document.getElementById('doc-upload-preview');
    if (!cont) return;
    cont.innerHTML = '';
    const info = document.createElement('div');
    info.className = 'text-xs text-gray-600 mb-2';
    info.textContent = `${file.name} (${Math.round(file.size/1024)} KB)`;
    cont.appendChild(info);
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (['png','jpg','jpeg'].includes(ext)){
        const img = document.createElement('img');
        img.className = 'h-24 rounded border';
        img.src = URL.createObjectURL(file);
        cont.appendChild(img);
    } else if (ext === 'pdf') {
        const badge = document.createElement('span');
        badge.className = 'inline-block px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs';
        badge.textContent = 'PDF selected';
        cont.appendChild(badge);
    }
}

async function previewDocument(documentId, documentName){
    try{
        const url = `${API_BASE_URL.replace(/php\/api\/$/, 'api/')}documents/${encodeURIComponent(documentId)}/download`;
        const res = await fetch(url, { credentials: 'include' });
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
        const res = await fetch(`${API_BASE_URL.replace(/php\/api\/$/, 'api/')}documents/${encodeURIComponent(documentId)}/token`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ ttl }), credentials: 'include' });
        const data = await res.json();
        if (!res.ok || !data || data.success === false) throw new Error(data?.message || `HTTP ${res.status}`);
        const token = data.data?.token || data.token;
        const expiresAt = data.data?.expires_at || data.expires_at;
        const downloadUrl = `${window.location.origin}${API_BASE_URL.replace(/php\/api\/$/, 'api/')}documents/${encodeURIComponent(documentId)}/download?token=${encodeURIComponent(token)}`;
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
 * Sends a request to the API to delete a specific document.
 * Uses SweetAlert for feedback.
 */
async function deleteDocument(documentId) {
    console.log(`[Delete] Attempting to delete document ID: ${documentId}`);
    
    Swal.fire({
        title: 'Deleting...',
        text: `Deleting document ${documentId}, please wait.`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${API_BASE_URL.replace(/php\/api\/$/, 'api/')}documents/${encodeURIComponent(documentId)}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `HTTP error! status: ${response.status}`);
        }

        Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: result.message || 'Document deleted successfully!',
            timer: 2000,
            confirmButtonColor: '#4E3B2A'
        });
        await loadDocuments(document.getElementById('filter-doc-employee')?.value);

    } catch (error) {
        console.error('Error deleting document:', error);
        Swal.fire({
            icon: 'error',
            title: 'Deletion Failed',
            text: `Error deleting document: ${error.message}`,
            confirmButtonColor: '#4E3B2A'
        });
    }
}


/**
 * Handles the submission of the document upload form.
 * Uses SweetAlert for feedback.
 */
async function handleUploadDocument(event) {
    event.preventDefault(); 
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const fileInput = document.getElementById('doc-file');

    if (!form || !submitButton || !fileInput) {
         console.error("Upload Document form elements missing.");
         return;
    }

    if (!fileInput.files || fileInput.files.length === 0) {
        Swal.fire('Validation Error', 'Please select a file to upload.', 'warning');
        return;
    }
    const file = fileInput.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        Swal.fire('File Too Large', 'File size exceeds the 5MB limit.', 'warning');
        return;
    }
    
    const formData = new FormData(form);

    Swal.fire({
        title: 'Uploading...',
        text: 'Uploading document, please wait.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    submitButton.disabled = true;

    try {
        const empId = form.elements['employee_id']?.value;
        const response = await fetch(`${API_BASE_URL.replace(/php\/api\/$/, 'api/')}employees/${encodeURIComponent(empId)}/documents`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const result = await response.json();

        if (!response.ok) {
            if (response.status === 400 && result.details) {
                 const errorMessages = Object.values(result.details).join(' ');
                 throw new Error(errorMessages || result.error || `HTTP error! status: ${response.status}`);
            }
             if ((response.status === 400 || response.status === 500) && result.error && (result.error.includes('upload') || result.error.includes('save'))) {
                 throw new Error(result.error);
             }
            throw new Error(result.error || `HTTP error! status: ${response.status}`);
        }

        Swal.fire({
            icon: 'success',
            title: 'Uploaded!',
            text: result.message || 'Document uploaded successfully!',
            timer: 2000,
            confirmButtonColor: '#4E3B2A'
        });
        form.reset(); 
        await loadDocuments(document.getElementById('filter-doc-employee')?.value);

    } catch (error) {
        console.error('Error uploading document:', error);
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: `Upload Error: ${error.message}`,
            confirmButtonColor: '#4E3B2A'
        });
    } finally {
        submitButton.disabled = false; 
        if (Swal.isLoading()) {
            Swal.close();
        }
    }
}
