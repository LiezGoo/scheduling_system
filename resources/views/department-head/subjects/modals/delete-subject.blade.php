<form id="deleteSubjectForm" method="POST" class="d-none" aria-hidden="true">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmModalElement = document.getElementById('confirmModal');
            if (!confirmModalElement) {
                return;
            }

            const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalElement);
            const confirmBtn = document.getElementById('confirmBtn');
            const deleteForm = document.getElementById('deleteSubjectForm');

            let pendingDeleteSubject = null;

            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-subject-btn')) {
                    const btn = e.target.closest('.delete-subject-btn');
                    openDeleteSubjectConfirm(btn.dataset.subjectId, btn.dataset.subjectName || 'this subject');
                }
            });

            function openDeleteSubjectConfirm(subjectId, subjectName) {
                pendingDeleteSubject = {
                    id: subjectId,
                    name: subjectName,
                };

                document.getElementById('confirmIcon').className = 'fas fa-trash me-2';
                document.getElementById('confirmTitle').textContent = 'Delete Subject';
                document.getElementById('confirmMessage').textContent = `Are you sure you want to delete "${pendingDeleteSubject.name}"? This action cannot be undone.`;
                document.getElementById('confirmBtn').className = 'btn btn-danger fw-semibold';
                document.getElementById('confirmBtnIcon').className = 'fas fa-trash me-2';
                document.getElementById('confirmBtnText').textContent = 'Delete';

                confirmModal.show();
            }

            confirmBtn.addEventListener('click', function() {
                if (!pendingDeleteSubject || !pendingDeleteSubject.id) {
                    return;
                }

                deleteForm.action = `/department-head/subjects/${pendingDeleteSubject.id}`;
                confirmModal.hide();
                deleteForm.submit();
            });
        });
    </script>
@endpush
