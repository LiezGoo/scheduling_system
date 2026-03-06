@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-subject-btn')) {
                    const btn = e.target.closest('.delete-subject-btn');

                    window.openSystemModal({
                        type: 'confirm',
                        title: 'Confirm Deletion',
                        message: 'Are you sure you want to delete this record? This action cannot be undone.',
                        confirmText: 'Delete',
                        cancelText: 'Cancel',
                        onConfirm: function() {
                            executeDeleteSubject(btn.dataset.subjectId);
                        }
                    });
                }
            });

            function executeDeleteSubject(subjectId) {
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
                            window.openSystemModal({
                                type: 'success',
                                title: 'Deleted Successfully',
                                message: 'The record has been successfully removed from the system.',
                                confirmText: 'OK',
                                onConfirm: function() {
                                    location.reload();
                                }
                            });
                        } else {
                            window.openSystemModal({
                                type: 'error',
                                title: 'Action Failed',
                                message: 'An error occurred while processing your request. Please try again.',
                                confirmText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        window.openSystemModal({
                            type: 'error',
                            title: 'Action Failed',
                            message: 'An error occurred while processing your request. Please try again.',
                            confirmText: 'OK'
                        });
                        console.error('Error:', error);
                    });
            }
        });
    </script>
@endpush
