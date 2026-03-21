<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-plus me-2"></i> Add New Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addDepartmentForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="department_code" class="form-label fw-bold">Department Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="department_code" name="department_code"
                            placeholder="e.g., CICT" required>
                        <div class="invalid-feedback">
                            Department code is required and must be unique.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="department_name" class="form-label fw-bold">Department Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="department_name" name="department_name"
                            placeholder="e.g., College of Information and Communications Technology" required>
                        <div class="invalid-feedback">
                            Department name is required.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-maroon" id="addDepartmentBtn" form="addDepartmentForm">
                    <i class="fas fa-save me-2"></i> <span id="addDepartmentBtnText">Save Department</span>
                    <span id="addDepartmentSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                        aria-hidden="true" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-maroon {
        background-color: #660000;
        border-color: #660000;
        color: white;
    }

    .btn-maroon:hover {
        background-color: #550000;
        border-color: #550000;
        color: white;
    }

    .btn-maroon:focus {
        background-color: #550000;
        border-color: #550000;
        box-shadow: 0 0 0 0.2rem rgba(102, 0, 0, 0.25);
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addDepartmentModalEl = document.getElementById('addDepartmentModal');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let isSubmitting = false;

        function showFeedback(type, title, message, list = []) {
            if (window.showToast && typeof window.showToast === 'function') {
                const fullMessage = Array.isArray(list) && list.length > 0
                    ? `${message} ${list.join(' ')}`
                    : message;
                window.showToast(type, fullMessage);
                return;
            }

            console[type === 'error' ? 'error' : 'log'](message);
        }

        function submitAddDepartmentForm() {
            if (isSubmitting) {
                return;
            }

            const form = document.getElementById('addDepartmentForm');
            const btn = document.getElementById('addDepartmentBtn');
            const spinner = document.getElementById('addDepartmentSpinner');
            const btnText = document.getElementById('addDepartmentBtnText');
            const codeInput = document.getElementById('department_code');
            const nameInput = document.getElementById('department_name');

        function clearFieldErrors() {
            [codeInput, nameInput].forEach(input => {
                input.classList.remove('is-invalid');
            });
        }

        function applyFieldErrors(errors) {
            if (errors.department_code && codeInput) {
                codeInput.classList.add('is-invalid');
                codeInput.nextElementSibling.textContent = errors.department_code[0];
            }

            if (errors.department_name && nameInput) {
                nameInput.classList.add('is-invalid');
                nameInput.nextElementSibling.textContent = errors.department_name[0];
            }
        }

        function refreshDepartmentsTable() {
            if (typeof fetchDepartments === 'function') {
                // Silent refresh: creation toast has already been shown.
                fetchDepartments(true, true);
                return;
            }

            const searchInput = document.getElementById('search');
            const perPageSelect = document.getElementById('departmentPerPageSelect');
            const tableBody = document.getElementById('departments-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const search = searchInput ? searchInput.value : '';
            const perPage = perPageSelect ? perPageSelect.value : 15;

            fetch(`/admin/departments?search=${encodeURIComponent(search)}&per_page=${perPage}&page=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (tableBody) {
                            tableBody.innerHTML = data.html;
                        }
                        if (paginationContainer) {
                            paginationContainer.innerHTML = data.pagination;
                        }
                    }
                });
        }

        // Basic validation (block submit when invalid)
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        clearFieldErrors();

        const formData = new FormData(form);
        isSubmitting = true;

        // Disable button and show spinner
        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Saving...';

            fetch('/admin/departments', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: formData
                })
            .then(async response => {
                const payload = await response.json();
                if (!response.ok) {
                    throw { status: response.status, payload };
                }
                return payload;
            })
            .then(data => {
                if (data.success) {
                    // Reset form and close modal
                    form.reset();
                    form.classList.remove('was-validated');
                    const modal = bootstrap.Modal.getInstance(addDepartmentModalEl);
                    modal?.hide();

                    showFeedback(
                        'success',
                        'Department Added Successfully',
                        data.message || 'Department added successfully.'
                    );

                    // Reload departments - reset to page 1 to show the new department
                    refreshDepartmentsTable();
                } else {
                    showFeedback('error', 'Create Department Failed', data.message || 'Failed to create department.');
                }
            })
            .catch(error => {
                console.error('Error:', error);

                if (error && error.status === 422 && error.payload && error.payload.errors) {
                    applyFieldErrors(error.payload.errors);
                    const validationMessages = Object.values(error.payload.errors).flat();
                    showFeedback(
                        'error',
                        'Validation Error',
                        'Please fix the highlighted fields and try again.',
                        validationMessages
                    );
                    return;
                }

                const fallbackMessage = (error && error.payload && error.payload.message) ?
                    error.payload.message :
                    'An error occurred. Please try again.';

                showFeedback('error', 'Create Department Failed', fallbackMessage);
            })
            .finally(() => {
                isSubmitting = false;
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Save Department';
            });
    }

    // Form validation on input
        document.getElementById('addDepartmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAddDepartmentForm();
        });

        // Clear validation on modal hide
        document.getElementById('addDepartmentModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addDepartmentForm');
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('department_code').classList.remove('is-invalid');
            document.getElementById('department_name').classList.remove('is-invalid');
        });
    });
</script>
