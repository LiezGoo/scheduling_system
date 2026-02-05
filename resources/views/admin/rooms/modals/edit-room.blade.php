<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-edit me-2"></i> Edit Room
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edit-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="edit-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form id="editRoomForm" novalidate>
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="edit_room_id" name="room_id">

                    <div class="mb-3">
                        <label for="edit_room_code" class="form-label fw-bold">Room Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_room_code" name="room_code" required>
                        <div class="invalid-feedback">
                            Room code is required and must be unique.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_room_name" class="form-label fw-bold">Room Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_room_name" name="room_name" required>
                        <div class="invalid-feedback">
                            Room name is required.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_room_type" class="form-label fw-bold">Room Type <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_room_type" name="room_type"
                            placeholder="e.g., Lecture, Laboratory, Computer Lab, Physics Lab" maxlength="50" required>
                        <div class="invalid-feedback">
                            Room type is required (max 50 characters).
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="editRoomBtn" onclick="submitEditRoomForm()">
                    <i class="fas fa-save me-2"></i> <span id="editRoomBtnText">Update Room</span>
                    <span id="editRoomSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
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
    function submitEditRoomForm() {
        const form = document.getElementById('editRoomForm');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const roomId = document.getElementById('edit_room_id').value;
        if (!roomId) {
            const alertDiv = document.getElementById('edit-alert');
            const alertMessage = document.getElementById('edit-alert-message');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertMessage.textContent = 'Missing room ID. Please refresh the page and try again.';
            alertDiv.style.display = 'block';
            return;
        }
        const editRoomBtn = document.getElementById('editRoomBtn');
        const spinner = document.getElementById('editRoomSpinner');

        editRoomBtn.disabled = true;
        spinner.style.display = 'inline-block';

        const formData = new FormData(form);
        if (!formData.has('_method')) {
            formData.append('_method', 'PUT');
        }

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
                    message: 'Failed to update room. Please try again.'
                };

                if (!response.ok) {
                    data.success = false;
                    data.message = data.message || 'Failed to update room. Please try again.';
                }

                return data;
            })
            .then(data => {
                const alertDiv = document.getElementById('edit-alert');
                const alertMessage = document.getElementById('edit-alert-message');

                if (data.success) {
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertMessage.textContent = data.message;

                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('editRoomModal')).hide();
                        location.reload();
                    }, 1500);
                } else {
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    if (data.errors) {
                        const firstError = Object.values(data.errors).flat()[0];
                        alertMessage.textContent = firstError || data.message;
                    } else {
                        alertMessage.textContent = data.message;
                    }
                }
                alertDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.getElementById('edit-alert');
                const alertMessage = document.getElementById('edit-alert-message');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertMessage.textContent = 'An error occurred while updating the room.';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
                editRoomBtn.disabled = false;
                spinner.style.display = 'none';
            });
</script>
