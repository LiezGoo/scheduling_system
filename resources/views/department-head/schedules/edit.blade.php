@extends('layouts.app')

@section('page-title', 'Edit Schedule')

@push('styles')
<style>
    .schedule-item-card {
        transition: all 0.2s ease;
        border-left: 4px solid #660000;
    }
    .schedule-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .badge-time {
        background: #f8f9fa;
        color: #333;
        border: 1px solid #dee2e6;
    }
    .edit-modal-header {
        background: #660000;
        color: white;
    }
    .filter-btn.active {
        background-color: #660000;
        color: white;
        border-color: #660000;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">Manual Schedule Adjustment</h2>
            <p class="text-muted mb-0">Modifying schedule for {{ $schedule->program?->program_name }} - {{ $schedule->block }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('department-head.schedules.show', $schedule) }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Review
            </a>
            <button class="btn btn-success" id="saveAllBtn" onclick="location.reload()">
                <i class="fa-solid fa-check me-2"></i>Done Adjusting
            </button>
        </div>
    </div>

    <!-- Stats & Filters -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light-subtle">
                <div class="card-body p-3">
                    <div class="text-muted small">Total Subjects</div>
                    <div class="h5 mb-0 fw-bold">{{ $items->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-9 text-end d-flex align-items-end justify-content-end gap-2">
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-outline-secondary filter-btn active" data-filter="all">All Days</button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="Monday">Mon</button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="Tuesday">Tue</button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="Wednesday">Wed</button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="Thursday">Thu</button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="Friday">Fri</button>
            </div>
        </div>
    </div>

    <!-- Schedule Items List -->
    <div class="row g-3" id="scheduleItemsContainer">
        @foreach($items as $item)
        <div class="col-12 col-xl-6 schedule-item-row" data-day="{{ $item->day }}">
            <div class="card shadow-sm schedule-item-card border-0 mb-2">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold fs-5">{{ $item->subject?->subject_code }}</div>
                            <div class="text-muted small">{{ $item->subject?->subject_name }}</div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="openEditModal({{ json_encode($item) }})">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                        </button>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-calendar-day text-muted small" style="width: 14px;"></i>
                                <span class="small fw-semibold">{{ $item->day }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-clock text-muted small" style="width: 14px;"></i>
                                <span class="badge badge-time small">{{ \Carbon\Carbon::parse($item->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('h:i A') }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 mb-1 text-truncate">
                                <i class="fa-solid fa-user-tie text-muted small" style="width: 14px;"></i>
                                <span class="small">{{ $item->instructor?->full_name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <i class="fa-solid fa-door-open text-muted small" style="width: 14px;"></i>
                                <span class="small">{{ $item->room?->room_code ?? 'Unassigned Room' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header edit-modal-header shadow-sm">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Class Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editItemForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div id="modalAlert" class="alert alert-danger d-none"></div>
                    <input type="hidden" id="item_id" name="item_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Subject</label>
                        <div id="modalSubjectName" class="p-2 border rounded bg-light fw-semibold"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="day" class="form-label small fw-bold text-muted text-uppercase">Day</label>
                            <select class="form-select" id="day" name="day" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="room_id" class="form-label small fw-bold text-muted text-uppercase">Room</label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->room_code }} ({{ $room->room_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="start_time" class="form-label small fw-bold text-muted text-uppercase">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_time" class="form-label small fw-bold text-muted text-uppercase">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                        <div class="col-12">
                            <label for="instructor_id" class="form-label small fw-bold text-muted text-uppercase">Instructor</label>
                            <select class="form-select" id="instructor_id" name="instructor_id" required>
                                @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="updateBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="updateSpinner"></span>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    const editForm = document.getElementById('editItemForm');
    const updateBtn = document.getElementById('updateBtn');
    const updateSpinner = document.getElementById('updateSpinner');
    const modalAlert = document.getElementById('modalAlert');

    // Filtering logic
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const day = this.dataset.filter;
            document.querySelectorAll('.schedule-item-row').forEach(row => {
                if (day === 'all' || row.dataset.day === day) {
                    row.classList.remove('d-none');
                } else {
                    row.classList.add('d-none');
                }
            });
        });
    });

    function openEditModal(item) {
        modalAlert.classList.add('d-none');
        document.getElementById('item_id').value = item.id;
        document.getElementById('modalSubjectName').textContent = `${item.subject?.subject_code} - ${item.subject?.subject_name}`;
        
        // Populate fields
        document.getElementById('day').value = item.day || item.day_of_week;
        document.getElementById('room_id').value = item.room_id;
        document.getElementById('start_time').value = item.start_time.substring(0, 5);
        document.getElementById('end_time').value = item.end_time.substring(0, 5); // Assuming format is HH:MM:SS
        document.getElementById('instructor_id').value = item.instructor_id;
        
        editModal.show();
    }

    editForm.onsubmit = async (e) => {
        e.preventDefault();
        
        const itemId = document.getElementById('item_id').value;
        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());
        
        // Show loading
        updateBtn.disabled = true;
        updateSpinner.classList.remove('d-none');
        modalAlert.classList.add('d-none');

        try {
            const response = await fetch(`{{ url("department-head/schedules/{$schedule->id}/items") }}/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                editModal.hide();
                showToast(result.message, 'success');
                // Reload after short delay to show changes
                setTimeout(() => location.reload(), 1000);
            } else {
                // Handle validation errors
                let errorHtml = '<ul class="mb-0">';
                if (result.errors) {
                    Object.values(result.errors).forEach(err => {
                        errorHtml += `<li>${err}</li>`;
                    });
                } else if (result.error) {
                    errorHtml += `<li>${result.error}</li>`;
                }
                errorHtml += '</ul>';
                modalAlert.innerHTML = errorHtml;
                modalAlert.classList.remove('d-none');
            }
        } catch (error) {
            modalAlert.textContent = 'An error occurred while updating the schedule item.';
            modalAlert.classList.remove('d-none');
        } finally {
            updateBtn.disabled = false;
            updateSpinner.classList.add('d-none');
        }
    };
</script>
@endpush
