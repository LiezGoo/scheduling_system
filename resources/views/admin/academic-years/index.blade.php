@extends('layouts.app')

@section('page-title', 'Academic Years Management')

@section('content')
<div class="container-fluid py-4 ps-3 pe-3">
    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3 rounded-3 border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check me-2 flex-shrink-0"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3 rounded-3 border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-exclamation me-2 flex-shrink-0"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0"><i class="fas fa-calendar-alt me-2"></i>Manage academic years and semesters</p>
        </div>
        <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addAcademicYearModal">
            <i class="fas fa-plus me-2"></i>Add Academic Year
        </button>
    </div>

    <!-- Academic Years Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="academicYearsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Academic Year</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="academic-years-table-body">
                        @forelse ($academicYears as $academicYear)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $academicYear->name }}</div>
                                    <small class="text-muted">{{ $academicYear->start_year }}–{{ $academicYear->end_year }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if (!$academicYear->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="activateAcademicYear({{ $academicYear->id }})"
                                                    title="Activate" aria-label="Activate Academic Year">
                                                <i class="fa-solid fa-toggle-on"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                onclick="editAcademicYear({{ $academicYear->id }}, '{{ $academicYear->start_year }}', '{{ $academicYear->end_year }}')"
                                                title="Edit" aria-label="Edit Academic Year">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteAcademicYear({{ $academicYear->id }})"
                                                title="Delete" aria-label="Delete Academic Year">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-4">
                                    <i class="fa-solid fa-calendar-times text-muted fa-3x mb-3"></i>
                                    <p class="text-muted mb-0">No academic years found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if (collect($academicYears)->count() > 0)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <div class="text-muted small">
                        Showing {{ collect($academicYears)->count() }} of
                        {{ collect($academicYears)->count() }} academic years
                    </div>
                </div>

                <!-- Active Year Info -->
                @php
                    $activeAcademicYear = collect($academicYears)->where('is_active', true)->first();
                @endphp
                @if ($activeAcademicYear)
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success"></i>
                            <small class="text-muted">
                                <span class="fw-semibold text-dark">System Active:</span>
                                <span class="badge bg-success ms-2">{{ $activeAcademicYear->name }}</span>
                            </small>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Add Academic Year Modal -->
<div class="modal fade" id="addAcademicYearModal" tabindex="-1" aria-labelledby="addAcademicYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="addAcademicYearModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add Academic Year
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAcademicYearForm" method="POST" action="{{ route('admin.academic-years.store') }}">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label for="start_year" class="form-label fw-semibold small text-dark">
                            Start Year <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control rounded-2" id="start_year" name="start_year" 
                               min="2000" max="2100" placeholder="e.g., 2025" required>
                    </div>
                    <div class="mb-4">
                        <label for="end_year" class="form-label fw-semibold small text-dark">
                            End Year <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control rounded-2" id="end_year" name="end_year" 
                               min="2000" max="2100" placeholder="e.g., 2026" required>
                        <small class="text-muted d-block mt-2">Must be greater than start year</small>
                    </div>
                    <div class="alert alert-info border-0 rounded-2 mb-0" role="alert">
                        <small class="text-info">
                            <i class="fas fa-lightbulb me-1"></i>
                            The academic year name will be auto-generated as "2025–2026"
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold">
                        <i class="fas fa-save me-2"></i>Create Year
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Academic Year Modal -->
<div class="modal fade" id="editAcademicYearModal" tabindex="-1" aria-labelledby="editAcademicYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="editAcademicYearModalLabel">
                    <i class="fas fa-pencil me-2"></i>Edit Academic Year
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAcademicYearForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label for="edit_start_year" class="form-label fw-semibold small text-dark">
                            Start Year <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control rounded-2" id="edit_start_year" name="start_year" 
                               min="2000" max="2100" required>
                    </div>
                    <div class="mb-0">
                        <label for="edit_end_year" class="form-label fw-semibold small text-dark">
                            End Year <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control rounded-2" id="edit_end_year" name="end_year" 
                               min="2000" max="2100" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold">
                        <i class="fas fa-save me-2"></i>Update Year
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- Modal Components -->
@include('components.modals.confirm-modal')
@include('components.modals.success-modal')
@include('components.modals.error-modal')

@push('styles')
<style>
    :root {
        --maroon: #7B0000;
        --maroon-dark: #660000;
        --maroon-light: rgba(123, 0, 0, 0.1);
    }

    /* Colors & Utilities */
    .bg-maroon {
        background-color: var(--maroon) !important;
    }

    .text-maroon {
        color: var(--maroon) !important;
    }

    .btn-maroon {
        background-color: var(--maroon);
        border-color: var(--maroon);
        color: white;
        transition: all 0.2s ease;
    }

    .btn-maroon:hover,
    .btn-maroon:focus {
        background-color: var(--maroon-dark);
        border-color: var(--maroon-dark);
        color: white;
        box-shadow: 0 2px 8px rgba(123, 0, 0, 0.25);
        transform: translateY(-1px);
    }

    .btn-maroon:active {
        background-color: var(--maroon-dark);
        border-color: var(--maroon-dark);
        transform: translateY(0);
    }

    /* Table Styling */
    .table {
        font-size: 0.95rem;
    }

    .table thead th {
        background-color: #f3f4f6;
        border-bottom: 2px solid #e5e7eb;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .hover-row {
        transition: background-color 0.15s ease;
    }

    .hover-row:hover {
        background-color: rgba(123, 0, 0, 0.02);
    }

    .table tbody tr {
        border-color: #f0f0f0;
    }

    /* Action Buttons */
    .btn-sm {
        transition: all 0.2s ease;
    }

    .btn-outline-info:hover,
    .btn-outline-warning:hover,
    .btn-outline-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-outline-info:active,
    .btn-outline-warning:active,
    .btn-outline-danger:active {
        transform: translateY(0);
    }

    /* Badges */
    .badge {
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Badges specific sizing */
    .badge.bg-success,
    .badge.bg-secondary {
        font-size: 0.8rem !important;
    }

    /* Card Design */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
        border: none;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .rounded-3 {
        border-radius: 0.75rem;
    }

    .rounded-2 {
        border-radius: 0.5rem;
    }

    /* Focus State for Forms */
    .form-control:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.2rem rgba(123, 0, 0, 0.15);
    }

    /* Form Select Focus */
    .form-select:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.2rem rgba(123, 0, 0, 0.15);
    }

    /* Modal Styling */
    .modal-header {
        border-bottom: 1px solid #e9ecef;
    }

    .modal-footer {
        background-color: #f8f9fa;
    }

    /* Alert Improvements */
    .alert {
        border-radius: 0.75rem;
        border: none;
        padding: 1rem 1.25rem;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .alert-danger {
        background-color: #fee2e2;
        color: #7f1d1d;
    }

    /* Transitions */
    .btn,
    .form-control,
    .form-select {
        transition: all 0.2s ease;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .card-header {
            padding: 0.875rem 1rem !important;
        }

        .table:not(.table-sm) > :not(caption) > * > * {
            padding: 0.75rem 0.5rem;
        }

        .px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .table:not(.table-sm) > :not(caption) > * > * {
            padding: 0.6rem 0.4rem;
        }

        .btn-sm {
            padding: 0.35rem 0.55rem;
            font-size: 0.8rem;
        }

        .alert {
            padding: 0.875rem 1rem;
            font-size: 0.9rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Modal instances
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));

    // Store pending action data
    let pendingAction = {
        type: null,
        id: null,
        data: {}
    };

    // Edit Academic Year
    function editAcademicYear(id, startYear, endYear) {
        const form = document.getElementById('editAcademicYearForm');
        form.action = `/admin/academic-years/${id}`;
        document.getElementById('edit_start_year').value = startYear;
        document.getElementById('edit_end_year').value = endYear;
        
        const modal = new bootstrap.Modal(document.getElementById('editAcademicYearModal'));
        modal.show();
    }

    // Delete Academic Year - Show Confirmation Modal
    function deleteAcademicYear(id) {
        pendingAction = { type: 'delete', id: id };
        
        document.getElementById('confirmIcon').className = 'fas fa-trash me-2';
        document.getElementById('confirmTitle').textContent = 'Delete Academic Year';
        document.getElementById('confirmMessage').textContent = 'Are you sure you want to delete this academic year? This action cannot be undone.';
        document.getElementById('confirmBtn').className = 'btn btn-danger fw-semibold';
        document.getElementById('confirmBtnIcon').className = 'fas fa-trash me-2';
        document.getElementById('confirmBtnText').textContent = 'Delete';
        
        confirmModal.show();
    }

    // Activate Academic Year - Show Confirmation Modal
    function activateAcademicYear(id) {
        pendingAction = { type: 'activate', id: id };
        
        document.getElementById('confirmIcon').className = 'fas fa-toggle-on me-2';
        document.getElementById('confirmTitle').textContent = 'Activate Academic Year';
        document.getElementById('confirmMessage').textContent = 'Activating this academic year will deactivate the current active year. Continue?';
        document.getElementById('confirmBtn').className = 'btn btn-primary fw-semibold';
        document.getElementById('confirmBtn').style.backgroundColor = 'var(--maroon)';
        document.getElementById('confirmBtn').style.borderColor = 'var(--maroon)';
        document.getElementById('confirmBtnIcon').className = 'fas fa-toggle-on me-2';
        document.getElementById('confirmBtnText').textContent = 'Activate';
        
        confirmModal.show();
    }

    // Execute confirmed action
    document.getElementById('confirmBtn').addEventListener('click', function() {
        if (pendingAction.type === 'delete') {
            executeDelete(pendingAction.id);
        } else if (pendingAction.type === 'activate') {
            executeActivate(pendingAction.id);
        }
    });

    // Execute Delete
    function executeDelete(id) {
        confirmModal.hide();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/academic-years/${id}`;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        
        // Show success modal and reload after a short delay
        setTimeout(() => {
            document.getElementById('successTitle').textContent = 'Deleted Successfully';
            document.getElementById('successMessage').textContent = 'Academic year has been deleted.';
            successModal.show();
            
            // Reload page after modal is shown
            document.getElementById('successBtn').addEventListener('click', function() {
                location.reload();
            }, { once: true });
        }, 300);
        
        form.submit();
    }

    // Execute Activate
    function executeActivate(id) {
        confirmModal.hide();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/academic-years/${id}/activate`;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        
        // Show success modal and reload after a short delay
        setTimeout(() => {
            document.getElementById('successTitle').textContent = 'Activated Successfully';
            document.getElementById('successMessage').textContent = 'Academic year has been activated.';
            successModal.show();
            
            // Reload page after modal is shown
            document.getElementById('successBtn').addEventListener('click', function() {
                location.reload();
            }, { once: true });
        }, 300);
        
        form.submit();
    }

    // Auto-focus confirm button when modal is shown
    document.getElementById('confirmModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('confirmBtn').focus();
    });

    // Auto-focus success button when modal is shown
    document.getElementById('successModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('successBtn').focus();
    });
</script>
@endpush
