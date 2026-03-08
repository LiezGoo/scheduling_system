<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-plus me-2"></i> Add New Room
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="room_code" class="form-label fw-bold">Room Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room_code" name="room_code"
                            placeholder="e.g., CCB-RM-1, CCB-LAB-A" required>
                        <div class="invalid-feedback">
                            Room code is required and must be unique.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_name" class="form-label fw-bold">Room Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room_name" name="room_name"
                            placeholder="e.g., CCB Room 1" required>
                        <div class="invalid-feedback">
                            Room name is required.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_type" class="form-label fw-bold">Room Type <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room_type" name="room_type"
                            placeholder="e.g., Lecture, Laboratory" maxlength="50" required>
                        <div class="invalid-feedback">
                            Room type is required (max 50 characters).
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" id="addRoomBtn" onclick="submitAddRoomForm()">
                    <i class="fas fa-save me-2"></i> <span id="addRoomBtnText">Save Room</span>
                    <span id="addRoomSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
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
    function submitAddRoomForm() {
        const form = document.getElementById('addRoomForm');

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const addRoomBtn = document.getElementById('addRoomBtn');
        const spinner = document.getElementById('addRoomSpinner');
        const btnText = document.getElementById('addRoomBtnText');

        addRoomBtn.disabled = true;
        spinner.style.display = 'inline-block';

        const formData = new FormData(form);

        fetch('{{ route('admin.rooms.store') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast(data.message || 'Room added successfully', 'success');
                    form.reset();
                    form.classList.remove('was-validated');
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('addRoomModal')).hide();
                        location.reload();
                    }, 500);
                } else {
                    window.showToast(data.message || 'Failed to create room.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.showToast('An error occurred while creating the room.', 'error');
            })
            .finally(() => {
                addRoomBtn.disabled = false;
                spinner.style.display = 'none';
            });
    }
</script>
