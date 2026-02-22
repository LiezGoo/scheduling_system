<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="deleteSubjectModalLabel">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the subject <strong id="delete_subject_name_display"></strong>?</p>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    This action cannot be undone.
                </p>
                <input type="hidden" id="delete_subject_id">

                <div id="deleteSubjectAlert" class="alert d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="confirmDeleteSubjectBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <span class="btn-text"><i class="fas fa-trash me-2"></i>Delete Subject</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteSubjectModal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
            const deleteSubjectBtn = document.getElementById('confirmDeleteSubjectBtn');
            const deleteSubjectAlert = document.getElementById('deleteSubjectAlert');

            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-subject-btn')) {
                    const btn = e.target.closest('.delete-subject-btn');

                    document.getElementById('delete_subject_id').value = btn.dataset.subjectId;
                    document.getElementById('delete_subject_name_display').textContent = btn.dataset.subjectName;

                    deleteSubjectModal.show();
                }
            });

            deleteSubjectBtn.addEventListener('click', function() {
                const subjectId = document.getElementById('delete_subject_id').value;
                const spinner = deleteSubjectBtn.querySelector('.spinner-border');
                const btnText = deleteSubjectBtn.querySelector('.btn-text');

                deleteSubjectBtn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Deleting...';

                fetch(`/department-head/subjects/${subjectId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            deleteSubjectAlert.className = 'alert alert-success';
                            deleteSubjectAlert.textContent = data.message;
                            deleteSubjectAlert.classList.remove('d-none');

                            setTimeout(() => {
                                deleteSubjectModal.hide();
                                location.reload();
                            }, 1500);
                        } else {
                            deleteSubjectAlert.className = 'alert alert-danger';
                            deleteSubjectAlert.textContent = data.message || 'Failed to delete subject.';
                            deleteSubjectAlert.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        deleteSubjectAlert.className = 'alert alert-danger';
                        deleteSubjectAlert.textContent = 'An error occurred. Please try again.';
                        deleteSubjectAlert.classList.remove('d-none');
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        deleteSubjectBtn.disabled = false;
                        spinner.classList.add('d-none');
                        btnText.textContent = 'Delete';
                    });
            });

            document.getElementById('deleteSubjectModal').addEventListener('hidden.bs.modal', function() {
                deleteSubjectAlert.classList.add('d-none');
            });
        });
    </script>
@endpush
