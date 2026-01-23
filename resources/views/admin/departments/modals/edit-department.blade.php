<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-edit me-2"></i> Edit Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edit-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="edit-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form id="editDepartmentForm" novalidate>
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="edit_department_id" name="department_id">

                    <div class="mb-3">
                        <label for="edit_department_code" class="form-label fw-bold">Department Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_department_code" name="department_code"
                            required>
                        <div class="invalid-feedback">
                            Department code is required and must be unique.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label fw-bold">Department Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_department_name" name="department_name"
                            required>
                        <div class="invalid-feedback">
                            Department name is required.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="editDepartmentBtn"
                    onclick="submitEditDepartmentForm()">
                    <i class="fas fa-save me-2"></i> <span id="editDepartmentBtnText">Update Department</span>
                    <span id="editDepartmentSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
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
    function submitEditDepartmentForm() {
        const form = document.getElementById('editDepartmentForm');
        const departmentId = document.getElementById('edit_department_id').value;
        const btn = document.getElementById('editDepartmentBtn');
        const spinner = document.getElementById('editDepartmentSpinner');
        const btnText = document.getElementById('editDepartmentBtnText');
        const alertDiv = document.getElementById('edit-alert');

        // Basic validation
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const formData = new FormData(form);

        // Disable button and show spinner
        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Updating...';

        fetch(`/admin/departments/${departmentId}`, {
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
                    form.classList.remove('was-validated');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editDepartmentModal'));
                    modal.hide();

                    // Show success message on main page
                    const mainAlert = document.createElement('div');
                    mainAlert.className = 'alert alert-success alert-dismissible fade show';
                    mainAlert.innerHTML = `<i class="fas fa-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    document.querySelector('.container-fluid').insertBefore(mainAlert, document.querySelector(
                        '.card'));

                    // Reload departments
                    if (typeof fetchDepartments === 'function') {
                        fetchDepartments();
                    } else {
                        location.reload();
                    }
                } else {
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    document.getElementById('edit-alert-message').textContent = data.message ||
                        'Failed to update department.';
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                document.getElementById('edit-alert-message').textContent = 'An error occurred. Please try again.';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Update Department';
            });
    }

    // Form submission
    document.getElementById('editDepartmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitEditDepartmentForm();
    });

    // Clear validation on modal hide
    document.getElementById('editDepartmentModal').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('editDepartmentForm');
        form.classList.remove('was-validated');
        document.getElementById('edit-alert').style.display = 'none';
    });
</script>
