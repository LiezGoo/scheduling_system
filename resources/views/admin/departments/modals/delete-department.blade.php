<!-- Delete Department Confirmation Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-trash me-2"></i> Delete Department
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="delete-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="delete-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <p class="mb-3">
                    Are you sure you want to delete this department?
                </p>
                <p class="fw-bold mb-3">
                    <span id="delete_department_name"></span>
                </p>
                <p class="text-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.
                </p>

                <input type="hidden" id="delete_department_id">
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteDepartmentBtn"
                    onclick="submitDeleteDepartmentForm()">
                    <i class="fas fa-trash me-2"></i> <span id="deleteDepartmentBtnText">Delete Department</span>
                    <span id="deleteDepartmentSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                        aria-hidden="true" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function submitDeleteDepartmentForm() {
        const departmentId = document.getElementById('delete_department_id').value;
        const btn = document.getElementById('deleteDepartmentBtn');
        const spinner = document.getElementById('deleteDepartmentSpinner');
        const btnText = document.getElementById('deleteDepartmentBtnText');
        const alertDiv = document.getElementById('delete-alert');

        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Deleting...';

        fetch(`/admin/departments/${departmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteDepartmentModal'));
                    modal.hide();

                    // Show success message
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    document.getElementById('delete-alert-message').textContent = data.message;
                    alertDiv.style.display = 'block';

                    // Reload departments
                    setTimeout(() => {
                        const searchInput = document.getElementById('search');
                        const event = new Event('input');
                        searchInput.dispatchEvent(event);
                    }, 1000);
                } else {
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    document.getElementById('delete-alert-message').textContent = data.message ||
                        'Failed to delete department.';
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                document.getElementById('delete-alert-message').textContent =
                'An error occurred. Please try again.';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Delete Department';
            });
    }

    // Clear on modal hide
    document.getElementById('deleteDepartmentModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('delete-alert').style.display = 'none';
    });
</script>
