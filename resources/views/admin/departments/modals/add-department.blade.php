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
                <div id="add-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="add-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

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
                <button type="button" class="btn btn-maroon" id="addDepartmentBtn" onclick="submitAddDepartmentForm()">
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
    function submitAddDepartmentForm() {
        const form = document.getElementById('addDepartmentForm');
        const btn = document.getElementById('addDepartmentBtn');
        const spinner = document.getElementById('addDepartmentSpinner');
        const btnText = document.getElementById('addDepartmentBtnText');
        const alertDiv = document.getElementById('add-alert');

        // Basic validation (block submit when invalid)
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const formData = new FormData(form);

        // Disable button and show spinner
        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Saving...';

        fetch('/admin/departments', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset form and close modal
                    form.reset();
                    form.classList.remove('was-validated');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addDepartmentModal'));
                    modal.hide();

                    // Show success message
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    document.getElementById('add-alert-message').textContent = data.message;
                    alertDiv.style.display = 'block';

                    // Reload departments
                    setTimeout(() => {
                        document.getElementById('search').value = '';
                        document.getElementById('departmentPerPageSelect').value = 15;
                        const event = new Event('change');
                        document.getElementById('departmentPerPageSelect').dispatchEvent(event);
                    }, 1000);
                } else {
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    document.getElementById('add-alert-message').textContent = data.message ||
                        'Failed to create department.';
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                document.getElementById('add-alert-message').textContent = 'An error occurred. Please try again.';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
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
        document.getElementById('add-alert').style.display = 'none';
    });
</script>
