@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-subject-btn')) {
                    const btn = e.target.closest('.delete-subject-btn');
                    
                    if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                        executeDeleteSubject(btn.dataset.subjectId);
                    }
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
                            window.showToast('Subject deleted successfully!', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            window.showToast(data.message || 'An error occurred while processing your request. Please try again.', 'error');
                        }
                    })
                    .catch(error => {
                        window.showToast('An error occurred while processing your request. Please try again.', 'error');
                        console.error('Error:', error);
                    });
            }
        });
    </script>
@endpush
