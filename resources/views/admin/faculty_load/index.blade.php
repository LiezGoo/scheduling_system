@extends('layouts.app')

@section('page-title', 'Faculty Load Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-book-open me-2"></i>
                    Manage teaching eligibility, subject assignments, and workload limits for faculty members
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#assignFacultyLoadModal">
                <i class="fa-solid fa-plus me-2"></i>Assign Faculty Load
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.faculty-load.index') }}" id="filterForm" novalidate>
                    <div class="row g-3 align-items-end">
                        <!-- Search Faculty -->
                        <div class="col-md-4">
                            <label for="filterFaculty" class="form-label">Search Faculty</label>
                            <input type="text" class="form-control" id="filterFaculty" name="faculty"
                                placeholder="Name or ID..." value="{{ $currentFilters['faculty'] ?? '' }}">
                        </div>

                        <!-- Program Filter -->
                        <div class="col-md-2">
                            <label for="filterProgram" class="form-label">Program</label>
                            <select class="form-select" id="filterProgram" name="program">
                                <option value="">All Programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}"
                                        {{ request('program') == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Role Filter -->
                        <div class="col-md-2">
                            <label for="filterRole" class="form-label">Role</label>
                            <select class="form-select" id="filterRole" name="role">
                                <option value="">All Roles</option>
                                <option value="instructor" {{ request('role') === 'instructor' ? 'selected' : '' }}>
                                    Instructor</option>
                                <option value="program_head" {{ request('role') === 'program_head' ? 'selected' : '' }}>
                                    Program Head</option>
                                <option value="department_head"
                                    {{ request('role') === 'department_head' ? 'selected' : '' }}>Department Head</option>
                            </select>
                        </div>

                        <!-- Subject Filter -->
                        <div class="col-md-2">
                            <label for="filterSubject" class="form-label">Subject</label>
                            <select class="form-select" id="filterSubject" name="subject">
                                <option value="">All Subjects</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}"
                                        {{ request('subject') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->subject_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Clear Filters -->
                        <div class="col-md-2 d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters"
                                title="Clear Filters">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </button>
                            <div class="spinner-border spinner-border-sm text-maroon d-none" role="status"
                                aria-hidden="true" id="filtersSpinner"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Faculty Load Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($facultyLoads->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="facultyLoadTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Faculty ID</th>
                                    <th>Faculty Name</th>
                                    <th>Role</th>
                                    <th>Program</th>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                    <th>Max Sections</th>
                                    <th>Max Load Units</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="facultyLoadTableBody">
                                @include('admin.faculty_load.partials.table-rows')
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination & Per Page -->
                    <div class="d-flex justify-content-between align-items-center mt-4 gap-3">
                        <div class="text-muted small" id="facultyLoadSummary">
                            @include('admin.faculty_load.partials.summary')
                        </div>
                        <div class="d-flex align-items-center gap-3 ms-auto">
                            <div id="facultyLoadPagination" class="d-flex">
                                @include('admin.faculty_load.partials.pagination')
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="perPageSelect" class="text-muted small mb-0 text-nowrap">Per page:</label>
                                <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                                    <option value="10" {{ request('per_page', '15') == '10' ? 'selected' : '' }}>10
                                    </option>
                                    <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15
                                    </option>
                                    <option value="25" {{ request('per_page', '15') == '25' ? 'selected' : '' }}>25
                                    </option>
                                    <option value="50" {{ request('per_page', '15') == '50' ? 'selected' : '' }}>50
                                    </option>
                                    <option value="100" {{ request('per_page', '15') == '100' ? 'selected' : '' }}>100
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <i class="fa-solid fa-inbox text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">No faculty load assignments found</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Assign Faculty Load Modal -->
    <div class="modal fade" id="assignFacultyLoadModal" tabindex="-1" aria-labelledby="assignFacultyLoadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignFacultyLoadModalLabel">
                        <i class="fa-solid fa-plus me-2"></i>Assign Faculty Load
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="assignFacultyLoadForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <!-- Faculty Selection -->
                        <div class="mb-3">
                            <label for="assignFaculty" class="form-label">Faculty <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignFaculty" name="faculty_id" required>
                                <option value="">Select Faculty Member</option>
                                @foreach ($eligibleFaculty as $faculty)
                                    <option value="{{ $faculty->id }}">
                                        {{ $faculty->full_name }} ({{ $faculty->getRoleLabel() }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Program Selection -->
                        <div class="mb-3">
                            <label for="assignProgram" class="form-label">Program <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignProgram" name="program_id" required>
                                <option value="">Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Subject Selection -->
                        <div class="mb-3">
                            <label for="assignSubject" class="form-label">Subject <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignSubject" name="subject_id" required>
                                <option value="">Select Subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" data-units="{{ $subject->units }}">
                                        {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Max Sections -->
                        <div class="mb-3">
                            <label for="assignMaxSections" class="form-label">Max Sections <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="assignMaxSections" name="max_sections"
                                min="1" max="10" value="3" required>
                            <small class="form-text text-muted">Maximum number of sections this faculty can teach for this
                                subject.</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Max Load Units -->
                        <div class="mb-3">
                            <label for="assignMaxLoadUnits" class="form-label">Max Load Units (Optional)</label>
                            <input type="number" class="form-control" id="assignMaxLoadUnits" name="max_load_units"
                                min="1" max="30" placeholder="Leave empty to use subject units">
                            <small class="form-text text-muted">Override for maximum load units. Leave empty to use subject
                                units.</small>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i>Assign Faculty Load
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Faculty Load Modal -->
    <div class="modal fade" id="editFacultyLoadModal" tabindex="-1" aria-labelledby="editFacultyLoadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFacultyLoadModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit Faculty Load
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editFacultyLoadForm" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editFacultyLoadId" name="faculty_load_id">
                    <div class="modal-body">
                        <!-- Faculty Display (Read-only) -->
                        <div class="mb-3">
                            <label for="editFacultyDisplay" class="form-label">Faculty</label>
                            <input type="text" class="form-control" id="editFacultyDisplay" readonly>
                        </div>

                        <!-- Subject Display (Read-only) -->
                        <div class="mb-3">
                            <label for="editSubjectDisplay" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="editSubjectDisplay" readonly>
                        </div>

                        <!-- Max Sections -->
                        <div class="mb-3">
                            <label for="editMaxSections" class="form-label">Max Sections <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editMaxSections" name="max_sections"
                                min="1" max="10" required>
                            <small class="form-text text-muted">Maximum number of sections this faculty can teach for this
                                subject.</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Max Load Units -->
                        <div class="mb-3">
                            <label for="editMaxLoadUnits" class="form-label">Max Load Units (Optional)</label>
                            <input type="number" class="form-control" id="editMaxLoadUnits" name="max_load_units"
                                min="1" max="30" placeholder="Leave empty to use subject units">
                            <small class="form-text text-muted">Override for maximum load units. Leave empty to use subject
                                units.</small>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i>Update Faculty Load
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Faculty Load Modal -->
    <div class="modal fade" id="viewFacultyLoadModal" tabindex="-1" aria-labelledby="viewFacultyLoadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewFacultyLoadModalLabel">
                        <i class="fa-solid fa-circle-info me-2"></i>Faculty Load Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Faculty Info -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-user me-2"></i>Faculty
                                </div>
                                <div class="fw-semibold" id="viewFacultyName"></div>
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-tag me-2"></i>Role
                                </div>
                                <div>
                                    <span class="badge bg-info" id="viewFacultyRole"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-book me-2"></i>Subject
                                </div>
                                <div>
                                    <div class="fw-semibold" id="viewSubjectName"></div>
                                    <small class="text-muted" id="viewSubjectCode"></small>
                                </div>
                            </div>
                        </div>

                        <!-- Program -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-graduation-cap me-2"></i>Program
                                </div>
                                <div class="fw-semibold" id="viewProgramName"></div>
                            </div>
                        </div>

                        <!-- Units -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-calculator me-2"></i>Units
                                </div>
                                <div class="fw-semibold" id="viewUnits"></div>
                            </div>
                        </div>

                        <!-- Max Sections -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-layer-group me-2"></i>Max Sections
                                </div>
                                <div class="fw-semibold" id="viewMaxSections"></div>
                            </div>
                        </div>

                        <!-- Max Load Units -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-scale-balanced me-2"></i>Max Load Units
                                </div>
                                <div class="fw-semibold" id="viewMaxLoadUnits"></div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-circle-check me-2"></i>Status
                                </div>
                                <div>
                                    <span class="badge bg-success" id="viewStatus"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Date -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-calendar-plus me-2"></i>Assigned
                                </div>
                                <div class="fw-semibold" id="viewAssignedDate"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="viewEditBtn">
                        <i class="fa-solid fa-edit me-2"></i>Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Faculty Load Modal -->
    <div class="modal fade" id="removeFacultyLoadModal" tabindex="-1" aria-labelledby="removeFacultyLoadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeFacultyLoadModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Removal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <strong id="removeFacultyLoadName"></strong> from teaching <strong
                            id="removeFacultyLoadSubject"></strong>?</p>
                    <p class="mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemoveBtn">
                        <i class="fa-solid fa-trash me-2"></i>Remove Assignment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS -->
    <style>
        /* ========================================
                                       GLOBAL MODAL STYLING - SorSU Theme
                                       ======================================== */

        /* Modal Header - Apply maroon background to ALL modals */
        .modal-header {
            background-color: #660000 !important;
            color: #ffffff !important;
            border-bottom: none;
        }

        .modal-header .modal-title {
            font-weight: 600;
            color: #ffffff !important;
        }

        .modal-header .modal-title i {
            color: #ffffff !important;
        }

        /* Close button styling for dark backgrounds */
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        /* Primary action buttons within modals */
        .modal .btn-primary {
            background-color: #660000 !important;
            border-color: #660000 !important;
            color: #ffffff !important;
        }

        .modal .btn-primary:hover,
        .modal .btn-primary:focus,
        .modal .btn-primary:active {
            background-color: #520000 !important;
            border-color: #520000 !important;
            color: #ffffff !important;
        }

        .modal .btn-primary:disabled {
            background-color: #660000 !important;
            border-color: #660000 !important;
            opacity: 0.65;
        }

        /* ========================================
                                       UTILITY CLASSES
                                       ======================================== */

        .text-maroon {
            color: #660000 !important;
        }

        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: white;
        }

        .btn-maroon:hover {
            background-color: #880000;
            border-color: #880000;
            color: white;
        }

        .bg-maroon {
            background-color: #660000 !important;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(102, 0, 0, 0.05);
        }

        .status-badge {
            min-width: 75px;
            display: inline-block;
        }
    </style>

    <!-- JavaScript for Faculty Load Management -->
    <script src="{{ asset('js/faculty-load-management.js') }}"></script>
@endsection
