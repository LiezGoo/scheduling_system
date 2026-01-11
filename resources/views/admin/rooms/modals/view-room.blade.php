<!-- View Room Modal -->
<div class="modal fade" id="viewRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i class="fas fa-door-open me-2"></i> Room Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="view-alert" class="alert alert-dismissible fade hide" role="alert" style="display: none;">
                    <span id="view-alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div id="room-details-content">
                    <div class="text-center">
                        <div class="spinner-border" style="color: #660000;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentViewRoomId = null;

    function loadRoomDetails(roomId) {
        currentViewRoomId = roomId;
        const contentDiv = document.getElementById('room-details-content');

        fetch(`/admin/rooms/${roomId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const room = data.room;
                    contentDiv.innerHTML = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: #660000; text-transform: uppercase; font-size: 0.85rem;">
                                Room Code
                            </h6>
                            <p class="mb-0" style="font-size: 1.1rem;">${room.room_code}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: #660000; text-transform: uppercase; font-size: 0.85rem;">
                                Room Name
                            </h6>
                            <p class="mb-0" style="font-size: 1.1rem;">${room.room_name}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: #660000; text-transform: uppercase; font-size: 0.85rem;">
                                Building
                            </h6>
                            <p class="mb-0" style="font-size: 1.1rem;">
                                ${room.building_name}
                                ${room.building_code ? `<br><small class="text-muted">${room.building_code}</small>` : ''}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: #660000; text-transform: uppercase; font-size: 0.85rem;">
                                Room Type
                            </h6>
                            <p class="mb-0">
                                <span class="badge" style="background-color: #660000; font-size: 0.95rem;">
                                    ${room.type_name}
                                </span>
                            </p>
                        </div>
                    </div>
                `;
                } else {
                    const alertDiv = document.getElementById('view-alert');
                    const alertMessage = document.getElementById('view-alert-message');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertMessage.textContent = data.message || 'Failed to load room details.';
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.getElementById('view-alert');
                const alertMessage = document.getElementById('view-alert-message');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertMessage.textContent = 'An error occurred while loading room details.';
                alertDiv.style.display = 'block';
            });
    }

    // Edit button in view modal
    document.getElementById('viewEditBtn').addEventListener('click', function() {
        if (currentViewRoomId) {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewRoomModal'));
            viewModal.hide();

            // Fetch room details and populate edit form
            fetch(`/admin/rooms/${currentViewRoomId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const room = data.room;
                        document.getElementById('edit_room_id').value = room.id;
                        document.getElementById('edit_room_code').value = room.room_code;
                        document.getElementById('edit_room_name').value = room.room_name;
                        document.getElementById('edit_building_id').value = room.building_id;
                        document.getElementById('edit_room_type_id').value = room.room_type_id;

                        const editForm = document.getElementById('editRoomForm');
                        editForm.classList.remove('was-validated');

                        const editModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
                        editModal.show();
                    }
                });
        }
    });
</script>

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
