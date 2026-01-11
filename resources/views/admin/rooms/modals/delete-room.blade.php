<!-- Delete Room Modal -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom border-danger" style="background-color: #ffe5e5;">
                <h5 class="modal-title" style="color: #dc3545; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle me-2"></i> Delete Room
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>

                <p>Are you sure you want to delete the room <strong id="delete_room_name"></strong>?</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteRoomBtn" onclick="submitDeleteRoomForm()">
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

        fetch(`/admin/rooms/${roomId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
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
