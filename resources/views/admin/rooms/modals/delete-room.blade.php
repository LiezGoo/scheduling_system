<!-- Delete Room Modal -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-trash me-2"></i> Delete Room
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the room <strong id="delete_room_name"></strong>?</p>
                <p class="text-muted mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="deleteRoomBtn" onclick="submitDeleteRoomForm()">
                    <i class="fas fa-trash me-2"></i> <span id="deleteRoomBtnText">Delete Room</span>
                    <span id="deleteRoomSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                        aria-hidden="true" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="delete_room_id">

<script>
    function submitDeleteRoomForm() {
        const roomId = document.getElementById('delete_room_id').value;
        const deleteRoomBtn = document.getElementById('deleteRoomBtn');
        const spinner = document.getElementById('deleteRoomSpinner');

        deleteRoomBtn.disabled = true;
        spinner.style.display = 'inline-block';

        const formData = new FormData();
        formData.append('_method', 'DELETE');

        fetch(`/admin/rooms/${roomId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                const data = isJson ? await response.json() : {
                    success: false,
                    message: 'Failed to delete room. Please try again.'
                };

                if (!response.ok) {
                    data.success = false;
                    data.message = data.message || 'Failed to delete room. Please try again.';
                }

                return data;
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteRoomModal')).hide();

                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector(
                        '.row'));

                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the room.');
            })
            .finally(() => {
                deleteRoomBtn.disabled = false;
                spinner.style.display = 'none';
            });
    }
</script>
