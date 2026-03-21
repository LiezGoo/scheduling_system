@extends('layouts.app')

@section('page-title', 'Semester Management')

@section('content')
<style>
    /* Page Content Styling - Does NOT override .page-header from app-layout.css */
    .breadcrumb-nav {
        background-color: transparent;
        padding: 0;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }

    .breadcrumb {
        margin-bottom: 0;
        background: none;
        padding: 0;
    }

    .breadcrumb-item + .breadcrumb-item::before {
        color: #999;
    }

    .breadcrumb-item.active {
        color: #7B0000;
        font-weight: 600;
    }

    /* Alerts Container */
    .alerts-container {
        margin-bottom: 1.5rem;
    }

    .alert {
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Filter Card */
    .filter-card {
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        background: #fff;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .filter-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .filter-section-label {
        font-size: 1.1rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-select {
        height: 38px;
        font-size: 0.95rem;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        transition: all 0.2s ease;
    }

    .form-select:focus {
        border-color: #7B0000;
        box-shadow: 0 0 0 0.2rem rgba(123, 0, 0, 0.15);
    }

    .form-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .btn-reset-filters {
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
        height: 38px;
        padding: 0 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        width: 100%;
        justify-content: center;
    }

    .btn-reset-filters:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
        color: #212529;
    }

    /* Content Card */
    .content-card {
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        background: #fff;
        overflow: hidden;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, rgba(123, 0, 0, 0.03) 0%, rgba(123, 0, 0, 0.02) 100%);
    }

    .empty-state-icon {
        font-size: 3.5rem;
        color: #dee2e6;
        margin-bottom: 1.5rem;
        display: block;
    }

    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 0.75rem;
    }

    .empty-state-text {
        font-size: 1rem;
        color: #6c757d;
        margin-bottom: 2rem;
    }

    .btn-add-semester {
        background-color: #7B0000;
        color: #fff;
        border: none;
        padding: 0.65rem 1.5rem;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 4px 12px rgba(123, 0, 0, 0.2);
        cursor: pointer;
    }

    .btn-add-semester:hover {
        background-color: #660000;
        box-shadow: 0 6px 16px rgba(123, 0, 0, 0.35);
        transform: translateY(-2px);
        color: #fff;
    }

    /* Table */
    .table-wrapper {
        position: relative;
        overflow-x: auto;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-card .card-body {
            padding: 1.5rem !important;
        }

        .btn-reset-filters {
            width: 100%;
        }

        .empty-state {
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            font-size: 2.5rem;
        }
    }
</style>


  <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0"><i class="fas fa-calendar-alt me-2"></i>Manage semesters</p>
        </div>
        <button type="button" class="btn btn-add-semester" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
            <i class="fas fa-plus me-2"></i>Add Semester
        </button>
    </div>

<!-- Filter Card
<div class="filter-card">
    <div class="card-body p-4">
        <div class="row align-items-end g-3">
            <div class="col-lg-4 col-md-6">
                <label for="filterAcademicYear" class="form-label">Academic Year</label>
                <select id="filterAcademicYear" class="form-select" onchange="filterSemesters()">
                    <option value="">All Academic Years</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-6">
                <label for="filterStatus" class="form-label">Status</label>
                <select id="filterStatus" class="form-select" onchange="filterSemesters()">
                    <option value="">All Statuses</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-6">
                <button type="button" class="btn-reset-filters" onclick="resetFilters()">
                    <i class="fas fa-times-circle"></i>Clear Filters
                </button>
            </div>
        </div>
    </div>
</div> -->

    <!-- Content Card -->
    <div class="content-card">
        @if ($semesters->isEmpty())
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-calendar-times empty-state-icon"></i>
                <h2 class="empty-state-title">No Semesters Found</h2>
                <p class="empty-state-text">There are no semesters matching the selected filters.</p>
                <button type="button" class="btn-add-semester" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                    <i class="fas fa-plus"></i>Add New Semester
                </button>
            </div>
        @else
            <!-- Semesters Table -->
            <div class="table-wrapper">
                <table class="table table-hover align-middle mb-0" id="semestersTable">
                    <thead class="table-light">
                        <tr>
                            <th>Semester Name</th>
                            <th>Academic Year</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="semesters-table-body">
                        @foreach ($semesters as $semester)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $semester->name }}</div>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $semester->academicYear->name }}</small>
                                </td>
                                <td>
                                    <small>{{ $semester->start_date ? $semester->start_date->format('M d, Y') : 'N/A' }}</small>
                                </td>
                                <td>
                                    <small>{{ $semester->end_date ? $semester->end_date->format('M d, Y') : 'N/A' }}</small>
                                </td>
                                <td class="text-center">
                                    @if ($semester->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if ($semester->status !== 'active')
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="activateSemester({{ $semester->id }})"
                                                    title="Activate" aria-label="Activate Semester">
                                                <i class="fa-solid fa-toggle-on"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="deactivateSemester({{ $semester->id }})"
                                                    title="Deactivate" aria-label="Deactivate Semester">
                                                <i class="fa-solid fa-toggle-off"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                onclick="editSemester({{ $semester->id }}, '{{ $semester->name }}', {{ $semester->academic_year_id }}, '{{ $semester->start_date }}', '{{ $semester->end_date }}', '{{ $semester->status }}')"
                                                title="Edit" aria-label="Edit Semester">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteSemester({{ $semester->id }}, '{{ $semester->name }}')"
                                                title="Delete" aria-label="Delete Semester">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($semesters->hasPages() || $semesters->count() > 0)
                <div style="border-top: 1px solid #e9ecef; padding: 1rem; font-size: 0.9rem;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted">
                            Showing {{ $semesters->count() }} of {{ $semesters->total() }} semesters
                        </div>
                        @if ($semesters->hasPages())
                            <div>
                                {{ $semesters->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

<!-- Add Semester Modal -->
<div class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="addSemesterModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add Semester
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSemesterForm" method="POST" action="{{ route('admin.semesters.store') }}">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label for="add_academic_year_id" class="form-label fw-semibold small text-dark">
                            Academic Year <span class="text-danger">*</span>
                        </label>
                        <select class="form-select rounded-2" id="add_academic_year_id" name="academic_year_id" required>
                            <option value="">-- Select Academic Year --</option>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_name" class="form-label fw-semibold small text-dark">
                            Semester Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control rounded-2" id="add_name" name="name" 
                               placeholder="e.g., 1st Semester" required maxlength="100">
                        <small class="text-muted d-block mt-2">e.g., 1st Semester, 2nd Semester, Summer</small>
                    </div>
                    <div class="mb-3">
                        <label for="add_start_date" class="form-label fw-semibold small text-dark">
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control rounded-2" id="add_start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_end_date" class="form-label fw-semibold small text-dark">
                            End Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control rounded-2" id="add_end_date" name="end_date" required>
                    </div>
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="add_active" name="status" value="active">
                            <label class="form-check-label fw-semibold small text-dark" for="add_active">
                                Set as Active Semester
                            </label>
                            <small class="text-muted d-block mt-2">If checked, other semesters in this academic year will be deactivated</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold">
                        <i class="fas fa-save me-2"></i>Create Semester
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Semester Modal -->
<div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="editSemesterModalLabel">
                    <i class="fas fa-pencil me-2"></i>Edit Semester
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSemesterForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label for="edit_academic_year_id" class="form-label fw-semibold small text-dark">
                            Academic Year <span class="text-danger">*</span>
                        </label>
                        <select class="form-select rounded-2" id="edit_academic_year_id" name="academic_year_id" required>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-semibold small text-dark">
                            Semester Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control rounded-2" id="edit_name" name="name" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="edit_start_date" class="form-label fw-semibold small text-dark">
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control rounded-2" id="edit_start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_end_date" class="form-label fw-semibold small text-dark">
                            End Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control rounded-2" id="edit_end_date" name="end_date" required>
                    </div>
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_active" name="status" value="active">
                            <label class="form-check-label fw-semibold small text-dark" for="edit_active">
                                Set as Active Semester
                            </label>
                            <small class="text-muted d-block mt-2">If checked, other semesters in this academic year will be deactivated</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold">
                        <i class="fas fa-save me-2"></i>Update Semester
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Components -->
@include('components.modals.confirm-modal')

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

    .table tbody tr {
        border-color: #f0f0f0;
        transition: background-color 0.15s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(123, 0, 0, 0.02);
    }

    /* Action Buttons */
    .btn-sm {
        transition: all 0.2s ease;
    }

    .btn-outline-info:hover,
    .btn-outline-warning:hover,
    .btn-outline-danger:hover,
    .btn-outline-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-outline-info:active,
    .btn-outline-warning:active,
    .btn-outline-danger:active,
    .btn-outline-secondary:active {
        transform: translateY(0);
    }

    /* Badges */
    .badge {
        font-weight: 500;
        letter-spacing: 0.3px;
    }

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
    .form-control:focus,
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

    // Store pending action data
    let pendingAction = {
        type: null,
        id: null,
        data: {}
    };

    // Edit Semester
    function editSemester(id, name, academicYearId, startDate, endDate, status) {
        const form = document.getElementById('editSemesterForm');
        form.action = `/admin/semesters/${id}`;
        
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_academic_year_id').value = academicYearId;
        document.getElementById('edit_start_date').value = startDate.split(' ')[0]; // Extract date part
        document.getElementById('edit_end_date').value = endDate.split(' ')[0]; // Extract date part
        document.getElementById('edit_active').checked = status === 'active';
        
        const modal = new bootstrap.Modal(document.getElementById('editSemesterModal'));
        modal.show();
    }

    // Delete Semester - Show Confirmation Modal
    function deleteSemester(id, name) {
        pendingAction = { type: 'delete', id: id };
        
        document.getElementById('confirmIcon').className = 'fas fa-trash me-2';
        document.getElementById('confirmTitle').textContent = 'Delete Semester';
        document.getElementById('confirmMessage').textContent = `Are you sure you want to delete "${name}"? This action cannot be undone if the semester is referenced in schedules or faculty loads.`;
        document.getElementById('confirmBtn').className = 'btn btn-danger fw-semibold';
        document.getElementById('confirmBtnIcon').className = 'fas fa-trash me-2';
        document.getElementById('confirmBtnText').textContent = 'Delete';
        
        confirmModal.show();
    }

    // Activate Semester - Show Confirmation Modal
    function activateSemester(id) {
        pendingAction = { type: 'activate', id: id };
        
        document.getElementById('confirmIcon').className = 'fas fa-toggle-on me-2';
        document.getElementById('confirmTitle').textContent = 'Activate Semester';
        document.getElementById('confirmMessage').textContent = 'Activating this semester will deactivate other semesters in the same academic year. Continue?';
        document.getElementById('confirmBtn').className = 'btn btn-primary fw-semibold';
        document.getElementById('confirmBtn').style.backgroundColor = 'var(--maroon)';
        document.getElementById('confirmBtn').style.borderColor = 'var(--maroon)';
        document.getElementById('confirmBtnIcon').className = 'fas fa-toggle-on me-2';
        document.getElementById('confirmBtnText').textContent = 'Activate';
        
        confirmModal.show();
    }

    // Deactivate Semester - Show Confirmation Modal
    function deactivateSemester(id) {
        pendingAction = { type: 'deactivate', id: id };
        
        document.getElementById('confirmIcon').className = 'fas fa-toggle-off me-2';
        document.getElementById('confirmTitle').textContent = 'Deactivate Semester';
        document.getElementById('confirmMessage').textContent = 'Are you sure you want to deactivate this semester?';
        document.getElementById('confirmBtn').className = 'btn btn-warning fw-semibold';
        document.getElementById('confirmBtnIcon').className = 'fas fa-toggle-off me-2';
        document.getElementById('confirmBtnText').textContent = 'Deactivate';
        
        confirmModal.show();
    }

    // Execute confirmed action
    document.getElementById('confirmBtn').addEventListener('click', function() {
        if (pendingAction.type === 'delete') {
            executeDelete(pendingAction.id);
        } else if (pendingAction.type === 'activate') {
            executeToggleStatus(pendingAction.id);
        } else if (pendingAction.type === 'deactivate') {
            executeToggleStatus(pendingAction.id);
        }
    });

    // Execute Delete
    function executeDelete(id) {
        confirmModal.hide();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/semesters/${id}`;
        
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
        
        if (window.showToast) {
            window.showToast('success', 'Semester has been deleted.');
        }
        
        form.submit();
    }

    // Execute Toggle Status
    function executeToggleStatus(id) {
        confirmModal.hide();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/semesters/${id}/toggle-status`;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        
        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        
        if (window.showToast) {
            window.showToast('success', 'Semester status has been updated.');
        }
        
        form.submit();
    }

    // Filter Semesters (placeholder for now - could be implemented with AJAX)
    function filterSemesters() {
        const academicYearId = document.getElementById('filterAcademicYear').value;
        const status = document.getElementById('filterStatus').value;
        
        // Build query string
        let url = new URL(window.location);
        if (academicYearId) {
            url.searchParams.set('academic_year_id', academicYearId);
        } else {
            url.searchParams.delete('academic_year_id');
        }
        
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        window.location = url.toString();
    }

    // Reset Filters
    function resetFilters() {
        document.getElementById('filterAcademicYear').value = '';
        document.getElementById('filterStatus').value = '';
        window.location = window.location.pathname;
    }

    // Auto-focus confirm button when modal is shown
    document.getElementById('confirmModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('confirmBtn').focus();
    });

    // Format add modal status checkbox handling
    document.getElementById('add_active').addEventListener('change', function() {
        if (this.checked) {
            this.value = 'active';
        } else {
            this.value = 'inactive';
        }
    });

    // Format edit modal status checkbox handling
    document.getElementById('edit_active').addEventListener('change', function() {
        if (this.checked) {
            this.value = 'active';
        } else {
            this.value = 'inactive';
        }
    });
</script>
@endpush
