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
                <div id="add-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="add-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form id="addRoomForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="room_code" class="form-label fw-bold">Room Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room_code" name="room_code"
                            placeholder="e.g., A101" required>
                        <div class="invalid-feedback">
                            Room code is required and must be unique.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_name" class="form-label fw-bold">Room Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room_name" name="room_name"
                            placeholder="e.g., Computer Lab A" required>
                        <div class="invalid-feedback">
                            Room name is required.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="building_id" class="form-label fw-bold">Building <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="building_id" name="building_id" required>
                            <option value="">Select a building...</option>
                            @foreach ($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->building_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Please select a building.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_type_id" class="form-label fw-bold">Room Type <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="room_type_id" name="room_type_id" required>
                            <option value="">Select a room type...</option>
                            @foreach ($roomTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Please select a room type.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="capacity" class="form-label fw-bold">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" placeholder="e.g., 40"
                            min="1">
                        <small class="form-text text-muted">Maximum number of students</small>
                    </div>

                    <div class="mb-3">
                        <label for="floor_level" class="form-label fw-bold">Floor Level</label>
                        <input type="number" class="form-control" id="floor_level" name="floor_level"
                            placeholder="e.g., 1">
                        <small class="form-text text-muted">Which floor is this room located on?</small>
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
                const alertDiv = document.getElementById('add-alert');
                const alertMessage = document.getElementById('add-alert-message');

                if (data.success) {
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertMessage.textContent = data.message;

                    form.reset();
                    form.classList.remove('was-validated');

                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('addRoomModal')).hide();
                        location.reload();
                    }, 1500);
                } else {
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertMessage.textContent = data.message;
                }
                alertDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.getElementById('add-alert');
                const alertMessage = document.getElementById('add-alert-message');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertMessage.textContent = 'An error occurred while creating the room.';
                alertDiv.style.display = 'block';
            })
            .finally(() => {
                addRoomBtn.disabled = false;
                spinner.style.display = 'none';
            });
    }
</script>
