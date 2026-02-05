@extends('layouts.app')

@section('page-title', 'Room Management')

@section('content')
    <div class="container-fluid pt-4 pb-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="col-md-6">
                <p class="text-muted mb-0"><i class="fas fa-door-open me-2"></i>Manage campus rooms</p>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-2"></i> Add Room
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label small fw-bold">Search</label>
                        <input type="text" id="search" class="form-control" placeholder="Room code or name..."
                            autocomplete="off" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-5">
                        <label for="room_type_filter" class="form-label small fw-bold">Room Type</label>
                        <input type="text" id="room_type_filter" class="form-control"
                            placeholder="Filter by room type..." autocomplete="off" value="{{ request('room_type') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters"
                            title="Clear Filters">
                            <i class="fa-solid fa-rotate-left me-1"></i>Clear
                        </button>
                        <div class="spinner-border spinner-border-sm text-maroon d-none" role="status" aria-hidden="true"
                            id="filtersSpinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rooms Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="roomsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Room Code</th>
                                <th>Room Name</th>
                                <th>Room Type</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rooms-table-body">
                            @include('admin.rooms.partials.table-rows', ['rooms' => $rooms])
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($rooms && $rooms->count() > 0)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <div class="text-muted small">
                            Showing {{ $rooms->firstItem() ?? 0 }} to {{ $rooms->lastItem() ?? 0 }} of
                            {{ $rooms->total() }} rooms
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div id="pagination-container">
                                {{ $rooms->appends(request()->query())->links() }}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="roomsPerPageSelect" class="text-muted small mb-0">Per page:</label>
                                <select id="roomsPerPageSelect" class="form-select form-select-sm" style="width: auto;">
                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15
                                    </option>
                                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="filter-spinner" class="text-center mt-3" style="display: none;">
            <div class="spinner-border" style="color: #660000;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    @include('admin.rooms.modals.add-room')
    @include('admin.rooms.modals.edit-room')
    @include('admin.rooms.modals.delete-room')

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
        }

        .table tbody tr:hover {
            background-color: rgba(102, 0, 0, 0.05);
        }
    </style>

    <script>
        let filterTimeout;

        // Search input with debounce
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });

        // Room Type filter with debounce
        document.getElementById('room_type_filter').addEventListener('input', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });

        // Clear filters button
        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('search').value = '';
            document.getElementById('room_type_filter').value = '';
            applyFilters();
        });

        // Per-page selector
        const perPageSelect = document.getElementById('roomsPerPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                const search = document.getElementById('search').value;
                const room_type = document.getElementById('room_type_filter').value;
                const per_page = this.value;

                window.location.href =
                    `{{ route('admin.rooms.index') }}?search=${search}&room_type=${room_type}&per_page=${per_page}`;
            });
        }

        // Pagination click handler
        document.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('#pagination-container a');
            if (paginationLink) {
                e.preventDefault();
                const url = new URL(paginationLink.href);
                const page = url.searchParams.get('page') || 1;
                applyFilters(page);
            }
        });

        function applyFilters(page = 1) {
            const search = document.getElementById('search').value;
            const room_type = document.getElementById('room_type_filter').value;
            const per_page = document.getElementById('roomsPerPageSelect').value;

            // Show loading spinner
            document.getElementById('filter-spinner').style.display = 'block';

            let url =
                `{{ route('admin.rooms.index') }}?search=${search}&room_type=${room_type}&page=${page}`;
            if (per_page) {
                url += `&per_page=${per_page}`;
            }

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('rooms-table-body').innerHTML = data.html;
                        document.getElementById('pagination-container').innerHTML = data.pagination;
                        const emptyState = document.getElementById('empty-state');
                        if (emptyState) {
                            emptyState.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    document.getElementById('filter-spinner').style.display = 'none';
                });
        }

        // Edit button event listener
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-room-btn')) {
                const btn = e.target.closest('.edit-room-btn');
                const roomId = btn.getAttribute('data-room-id');
                const roomCode = btn.getAttribute('data-room-code');
                const roomName = btn.getAttribute('data-room-name');
                const roomType = btn.getAttribute('data-room-type');

                document.getElementById('edit_room_id').value = roomId;
                document.getElementById('edit_room_code').value = roomCode;
                document.getElementById('edit_room_name').value = roomName;
                document.getElementById('edit_room_type').value = roomType;

                // Clear previous validation
                const editForm = document.getElementById('editRoomForm');
                editForm.classList.remove('was-validated');

                const editModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
                editModal.show();
            }
        });

        // Delete button event listener
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-room-btn')) {
                const btn = e.target.closest('.delete-room-btn');
                const roomId = btn.getAttribute('data-room-id');
                const roomName = btn.getAttribute('data-room-name');

                document.getElementById('delete_room_id').value = roomId;
                document.getElementById('delete_room_name').textContent = roomName;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteRoomModal'));
                deleteModal.show();
            }
        });
    </script>
@endsection
