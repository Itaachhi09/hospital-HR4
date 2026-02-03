/**
 * Benefits Management Module
 * Handles benefits categories, benefits, and employee benefits
 */

class BenefitsManagement {
    constructor() {
        this.categories = [];
        this.benefits = [];
        this.employeeBenefits = [];
        this.init();
    }

    init() {
        this.loadCategories();
        this.loadBenefits();
        this.loadEmployeeBenefits();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Category management
        $(document).on('click', '.add-category-btn', () => this.showAddCategoryModal());
        $(document).on('click', '.edit-category-btn', (e) => this.editCategory($(e.target).data('id')));
        $(document).on('click', '.delete-category-btn', (e) => this.deleteCategory($(e.target).data('id')));

        // Benefit management
        $(document).on('click', '.add-benefit-btn', () => this.showAddBenefitModal());
        $(document).on('click', '.edit-benefit-btn', (e) => this.editBenefit($(e.target).data('id')));
        $(document).on('click', '.delete-benefit-btn', (e) => this.deleteBenefit($(e.target).data('id')));

        // Employee benefits management
        $(document).on('click', '.assign-benefit-btn', () => this.showAssignBenefitModal());
        $(document).on('click', '.remove-benefit-btn', (e) => this.removeEmployeeBenefit($(e.target).data('id')));
    }

    // Category Management
    loadCategories() {
        $.get('php/api/get_benefits_categories.php')
            .done((response) => {
                if (response.success) {
                    this.categories = response.categories;
                    this.renderCategoriesTable();
                } else {
                    this.showError('Failed to load benefits categories');
                }
            })
            .fail(() => this.showError('Error loading benefits categories'));
    }

    renderCategoriesTable() {
        const tbody = $('#benefits-categories-table tbody');
        tbody.empty();

        this.categories.forEach(category => {
            tbody.append(`
                <tr>
                    <td>${category.CategoryName}</td>
                    <td>${category.Description || 'N/A'}</td>
                    <td>${new Date(category.CreatedAt).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-category-btn" data-id="${category.CategoryID}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-category-btn" data-id="${category.CategoryID}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    showAddCategoryModal() {
        const modal = `
            <div class="modal fade" id="addCategoryModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Benefits Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="addCategoryForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" name="categoryName" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('#modalContainer').html(modal);
        $('#addCategoryModal').modal('show');

        $('#addCategoryForm').on('submit', (e) => {
            e.preventDefault();
            this.addCategory(new FormData(e.target));
        });
    }

    addCategory(formData) {
        $.ajax({
            url: 'php/api/add_benefits_category.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            if (response.success) {
                this.showSuccess('Benefits Category added successfully');
                $('#addCategoryModal').modal('hide');
                this.loadCategories();
            } else {
                this.showError(response.error || 'Failed to add category');
            }
        })
        .fail(() => this.showError('Error adding category'));
    }

    // Benefits Management
    loadBenefits() {
        $.get('php/api/get_benefits.php')
            .done((response) => {
                if (response.success) {
                    this.benefits = response.benefits;
                    this.renderBenefitsTable();
                } else {
                    this.showError('Failed to load benefits');
                }
            })
            .fail(() => this.showError('Error loading benefits'));
    }

    renderBenefitsTable() {
        const tbody = $('#benefits-table tbody');
        tbody.empty();

        this.benefits.forEach(benefit => {
            const valueDisplay = this.formatBenefitValue(benefit);
            tbody.append(`
                <tr>
                    <td>${benefit.BenefitName}</td>
                    <td>${benefit.CategoryName}</td>
                    <td>${benefit.BenefitType}</td>
                    <td>${valueDisplay}</td>
                    <td>
                        <span class="badge ${benefit.IsTaxable ? 'badge-warning' : 'badge-info'}">
                            ${benefit.IsTaxable ? 'Taxable' : 'Non-taxable'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${benefit.IsActive ? 'badge-success' : 'badge-danger'}">
                            ${benefit.IsActive ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-benefit-btn" data-id="${benefit.BenefitID}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-benefit-btn" data-id="${benefit.BenefitID}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    formatBenefitValue(benefit) {
        if (benefit.BenefitType === 'Monetary') {
            return `₱${parseFloat(benefit.Value).toLocaleString()} ${benefit.Currency}`;
        } else if (benefit.BenefitType === 'Percentage') {
            return `${benefit.Value}%`;
        } else {
            return benefit.Value;
        }
    }

    showAddBenefitModal() {
        // Load categories for dropdown
        const categoryOptions = this.categories.map(c =>
            `<option value="${c.CategoryID}">${c.CategoryName}</option>`
        ).join('');

        const modal = `
            <div class="modal fade" id="addBenefitModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Benefit</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="addBenefitForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-control" name="categoryId" required>
                                        <option value="">Select Category</option>
                                        ${categoryOptions}
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Benefit Name *</label>
                                    <input type="text" class="form-control" name="benefitName" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Benefit Type *</label>
                                    <select class="form-control" name="benefitType" required>
                                        <option value="Monetary">Monetary</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Allowance">Allowance</option>
                                        <option value="Reimbursement">Reimbursement</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Value *</label>
                                    <input type="number" class="form-control" name="value" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Currency</label>
                                    <select class="form-control" name="currency">
                                        <option value="PHP">PHP</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="isTaxable" id="isTaxable">
                                        <label class="form-check-label" for="isTaxable">
                                            Is Taxable
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Effective Date *</label>
                                    <input type="date" class="form-control" name="effectiveDate" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Benefit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('#modalContainer').html(modal);
        $('#addBenefitModal').modal('show');

        $('#addBenefitForm').on('submit', (e) => {
            e.preventDefault();
            this.addBenefit(new FormData(e.target));
        });
    }

    addBenefit(formData) {
        $.ajax({
            url: 'php/api/add_benefit.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        })
        .done((response) => {
            if (response.success) {
                this.showSuccess('Benefit added successfully');
                $('#addBenefitModal').modal('hide');
                this.loadBenefits();
            } else {
                this.showError(response.error || 'Failed to add benefit');
            }
        })
        .fail(() => this.showError('Error adding benefit'));
    }

    // Employee Benefits Management
    loadEmployeeBenefits() {
        // Load from API - list all recent employee benefits for admin
        const self = this;
        // If there's a selected employee filter on the page, use it
        const employeeSelect = document.querySelector('[data-employee-filter]');
        const empId = employeeSelect ? employeeSelect.value : null;
        const url = empId ? `php/api/get_employee_benefits.php?employee_id=${empId}` : `php/api/get_employee_benefits.php?employee_id=${empId || 0}`;
        $.get(url)
            .done((response) => {
                // Response from legacy wrapper may already be the structured API response
                try {
                    const parsed = typeof response === 'string' ? JSON.parse(response) : response;
                    if (parsed.error || parsed.success===false) {
                        this.showError(parsed.error || 'Failed to load employee benefits');
                        return;
                    }
                    // The REST route returns { success:true, data: [...] } or direct array (depending on controller)
                    let list = [];
                    if (Array.isArray(parsed)) list = parsed;
                    else if (Array.isArray(parsed.data)) list = parsed.data;
                    else if (Array.isArray(parsed)) list = parsed;
                    else if (Array.isArray(parsed.benefits)) list = parsed.benefits;
                    else if (Array.isArray(parsed)) list = parsed;
                    // Fallback: if response has 'success' and the data was printed directly
                    if (parsed.success && Array.isArray(parsed)) list = parsed;

                    this.employeeBenefits = list;
                    this.renderEmployeeBenefitsTable();
                } catch (err) {
                    // If parsing failed, assume response is already array
                    this.employeeBenefits = response || [];
                    this.renderEmployeeBenefitsTable();
                }
            })
            .fail(() => this.showError('Error loading employee benefits'));
    }

    renderEmployeeBenefitsTable() {
        const tbody = $('#employee-benefits-table tbody');
        tbody.empty();
        if (!this.employeeBenefits || this.employeeBenefits.length===0) {
            tbody.append(`<tr><td colspan="5" class="text-center">No employee benefits assigned.</td></tr>`);
            return;
        }

        this.employeeBenefits.forEach(item => {
            const start = item.StartDate ? new Date(item.StartDate).toLocaleDateString() : '—';
            const end = item.EndDate ? new Date(item.EndDate).toLocaleDateString() : '—';
            const amount = item.BenefitAmount ? `₱${parseFloat(item.BenefitAmount).toLocaleString()}` : '-';
            const employeeName = item.FirstName ? `${item.FirstName} ${item.LastName}` : (item.EmployeeName || 'Employee');
            tbody.append(`
                <tr>
                    <td>${employeeName}</td>
                    <td>${item.BenefitName || item.CategoryName || 'Benefit'}</td>
                    <td>${amount}</td>
                    <td>${item.Status || 'Active'}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-benefit-details" data-id="${item.BenefitID}">Details</button>
                        <button class="btn btn-sm btn-warning remove-benefit-btn" data-id="${item.BenefitID}">Remove</button>
                    </td>
                </tr>
            `);
        });
    }

    showAssignBenefitModal() {
        // Build assign modal with employee and benefit dropdowns
        const employeeSelectId = 'assignBenefitEmployee';
        const benefitSelectId = 'assignBenefitSelect';
        const modal = `
            <div class="modal fade" id="assignBenefitModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Benefit to Employee</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="assignBenefitForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Employee *</label>
                                    <select id="${employeeSelectId}" class="form-control" name="employee_id" required></select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Benefit *</label>
                                    <select id="${benefitSelectId}" class="form-control" name="benefit_id" required></select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="benefit_amount">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Assign Benefit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('#modalContainer').html(modal);
        $('#assignBenefitModal').modal('show');

        // Populate employee dropdown and benefit dropdown
        populateEmployeeDropdown(employeeSelectId, false).then(() => {});
        // Load benefits
        $.get('php/api/get_benefits.php').done((resp) => {
            const parsed = typeof resp === 'string' ? JSON.parse(resp) : resp;
            const list = parsed && parsed.benefits ? parsed.benefits : (Array.isArray(parsed) ? parsed : []);
            const sel = document.getElementById(benefitSelectId);
            if (sel) {
                sel.innerHTML = '<option value="">-- Select Benefit --</option>' + list.map(b => `<option value="${b.BenefitID}">${b.BenefitName}</option>`).join('');
            }
        });

        $('#assignBenefitForm').on('submit', (e) => {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            // Basic validation
            if (!fd.get('employee_id') || !fd.get('benefit_id')) { this.showError('Employee and Benefit are required'); return; }
            // Submit via legacy wrapper
            $.ajax({
                url: 'php/api/assign_employee_benefit.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done((res) => {
                const parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.error || parsed.success===false) {
                    this.showError(parsed.error || 'Failed to assign benefit');
                } else {
                    this.showSuccess('Benefit assigned successfully');
                    $('#assignBenefitModal').modal('hide');
                    this.loadEmployeeBenefits();
                }
            }).fail(() => this.showError('Error assigning benefit'));
        });
    }

    // Utility Methods
    showSuccess(message) {
        // Show success toast or alert
        alert('Success: ' + message);
    }

    showError(message) {
        // Show error toast or alert
        alert('Error: ' + message);
    }

    editCategory(id) {
        const category = this.categories.find(c => c.CategoryID == id);
        if (category) {
            this.showSuccess(`Edit category: ${category.CategoryName}`);
            // Implement edit modal
        }
    }

    deleteCategory(id) {
        if (confirm('Are you sure you want to delete this benefits category?')) {
            // Implement delete API call
            this.showSuccess('Category deleted successfully');
            this.loadCategories();
        }
    }

    editBenefit(id) {
        const benefit = this.benefits.find(b => b.BenefitID == id);
        if (benefit) {
            this.showSuccess(`Edit benefit: ${benefit.BenefitName}`);
            // Implement edit modal
        }
    }

    deleteBenefit(id) {
        if (confirm('Are you sure you want to delete this benefit?')) {
            // Implement delete API call
            this.showSuccess('Benefit deleted successfully');
            this.loadBenefits();
        }
    }

    removeEmployeeBenefit(id) {
        if (!confirm('Are you sure you want to remove this benefit from the employee?')) return;
        $.ajax({ url: `php/api/delete_employee_benefit.php?id=${encodeURIComponent(id)}`, method: 'DELETE' })
            .done((res) => {
                const parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.error || parsed.success===false) {
                    this.showError(parsed.error || 'Failed to remove benefit');
                } else {
                    this.showSuccess('Benefit removed successfully');
                    this.loadEmployeeBenefits();
                }
            }).fail(() => this.showError('Error removing benefit'));
    }
}

// Initialize Benefits Management when document is ready
$(document).ready(() => {
    if ($('#benefits-management-section').length) {
        new BenefitsManagement();
    }
});
