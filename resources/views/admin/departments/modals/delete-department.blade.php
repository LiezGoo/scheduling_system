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
                <p class="mb-3">
                    Are you sure you want to delete this department?
                </p>
                <p class="fw-bold mb-3">
                    <span id="delete_department_name"></span>
                </p>
                <p class="text-muted mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.
                </p>

                <input type="hidden" id="delete_department_id">
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="deleteDepartmentBtn"
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
    function showFeedback(type, title, message) {
        if (window.showToast && typeof window.showToast === 'function') {
            window.showToast(type, message);
            return;
        }

        console[type === 'error' ? 'error' : 'log'](message);
    }

    function submitDeleteDepartmentForm() {
        const departmentId = document.getElementById('delete_department_id').value;
        const btn = document.getElementById('deleteDepartmentBtn');
        const spinner = document.getElementById('deleteDepartmentSpinner');
        const btnText = document.getElementById('deleteDepartmentBtnText');

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

                    showFeedback('success', 'Department Deleted', data.message || 'Department deleted successfully.');

                    // Reload departments
                    if (typeof fetchDepartments === 'function') {
                        fetchDepartments();
                    } else {
                        location.reload();
                    }
                } else {
                    showFeedback('error', 'Delete Department Failed', data.message || 'Failed to delete department.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFeedback('error', 'Delete Department Failed', 'An error occurred. Please try again.');
            })
            .finally(() => {
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Delete Department';
            });
    }

</script>
