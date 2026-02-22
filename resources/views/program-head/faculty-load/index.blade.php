@extends('layouts.app')

@section('page-title', 'Faculty Load Management')

@section('content')
    <div class="container-fluid py-4" data-faculty-load-base="{{ route('program-head.faculty-load.index') }}">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0"><i class="fa-solid fa-book-open"></i> Manage academic departments and
                    subjects
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#assignFacultyLoadModal">
                <i class="fa-solid fa-plus me-2"></i>Assign Faculty Load
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('program-head.faculty-load.index') }}" id="filterForm" novalidate>
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

                        <!-- Academic Year Filter -->
                        <div class="col-md-2">
                            <label for="filterAcademicYear" class="form-label">Academic Year</label>
                            <select class="form-select" id="filterAcademicYear" name="academic_year_id">
                                <option value="">All Academic Years</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}"
                                        {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Semester Filter -->
                        <div class="col-md-2">
                            <label for="filterSemester" class="form-label">Semester</label>
                            <select class="form-select" id="filterSemester" name="semester">
                                <option value="">All Semesters</option>
                                <option value="1st" {{ request('semester') === '1st' ? 'selected' : '' }}>1st</option>
                                <option value="2nd" {{ request('semester') === '2nd' ? 'selected' : '' }}>2nd</option>
                                <option value="summer" {{ request('semester') === 'summer' ? 'selected' : '' }}>Summer</option>
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

        <!-- Summary Statistics Cards -->
        @if ($facultyLoads && $facultyLoads->count() > 0)
            <div class="row g-3 mb-4">
                <!-- Total Instructors Card -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded p-3">
                                        <i class="fa-solid fa-chalkboard-user fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Instructors</h6>
                                    <h3 class="mb-0">{{ $summary['instructors_with_assignments'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Assignments Card -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded p-3">
                                        <i class="fa-solid fa-clipboard-list fa-2x text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Assignments</h6>
                                    <h3 class="mb-0">{{ $summary['total_faculty_assignments'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Eligible Instructors Card -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 rounded p-3">
                                        <i class="fa-solid fa-users fa-2x text-info"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Eligible Faculty</h6>
                                    <h3 class="mb-0">{{ $summary['total_eligible_instructors'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unassigned Instructors Card -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 rounded p-3">
                                        <i class="fa-solid fa-user-slash fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Unassigned</h6>
                                    <h3 class="mb-0">{{ $summary['instructors_without_assignments'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Faculty Load Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="facultyLoadTable">
                        <thead class="table-light">
                            <tr>
                                <th>Faculty ID</th>
                                <th>Faculty Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Program</th>
                                <th>Academic Year</th>
                                <th class="text-center">Semester</th>
                                <th class="text-center">Year Level</th>
                                <th class="text-center">Block/Section</th>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th class="text-center">Lecture Hrs</th>
                                <th class="text-center">Lab Hrs</th>
                                <th class="text-center">Total Hrs</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="facultyLoadTableBody">
                            @include('program-head.faculty-load.partials.table-rows')
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if ($facultyLoads && $facultyLoads->count() > 0)
                    <x-pagination.footer :paginator="$facultyLoads" />
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
                        <div id="assignFacultyLoadMessage" class="mb-3 d-none"></div>
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

                        <!-- Department Selection -->
                        <div class="mb-3">
                            <label for="assignDepartment" class="form-label">Department <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignDepartment" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
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

                        <!-- Academic Year Selection -->
                        <div class="mb-3">
                            <label for="assignAcademicYear" class="form-label">Academic Year <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignAcademicYear" name="academic_year_id" required>
                                <option value="">Select Academic Year</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Semester Selection -->
                        <div class="mb-3">
                            <label for="assignSemester" class="form-label">Semester <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignSemester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Year Level -->
                        <div class="mb-3">
                            <label for="assignYearLevel" class="form-label">Year Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assignYearLevel" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Block/Section -->
                        <div class="mb-3">
                            <label for="assignBlockSection" class="form-label">Block/Section <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="assignBlockSection" name="block_section"
                                placeholder="e.g., 1" required>
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

                        <!-- Lecture Hours -->
                        <div class="mb-3">
                            <label for="assignLectureHours" class="form-label">Lecture Hours per Week <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="assignLectureHours" name="lecture_hours"
                                min="0" max="40" value="0" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Laboratory Hours -->
                        <div class="mb-3">
                            <label for="assignLabHours" class="form-label">Laboratory Hours per Week <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="assignLabHours" name="lab_hours"
                                min="0" max="40" step="3" value="0" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Total Hours Display -->
                        <div class="mb-3">
                            <label class="form-label">Total Hours</label>
                            <div class="alert alert-info mb-0">
                                <strong id="assignComputedUnits">0</strong> hours
                            </div>
                        </div>

                        <input type="hidden" name="force_assign" id="assignForceAssign" value="0">
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

                        <!-- Lecture Hours -->
                        <div class="mb-3">
                            <label for="editLectureHours" class="form-label">Lecture Hours per Week <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editLectureHours" name="lecture_hours"
                                min="0" max="40" required>
                            <small class="form-text text-muted">1 lecture hour = 1 teaching unit</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Laboratory Hours -->
                        <div class="mb-3">
                            <label for="editLabHours" class="form-label">Laboratory Hours per Week <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editLabHours" name="lab_hours"
                                min="0" max="40" step="3" required>
                            <small class="form-text text-muted">3 laboratory hours = 1 teaching unit (must be divisible by
                                3)</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Total Hours Display -->
                        <div class="mb-3">
                            <label class="form-label">Total Hours</label>
                            <div class="alert alert-info mb-0">
                                <strong id="editComputedUnits">0</strong> hours
                            </div>
                            <small class="form-text text-muted">Automatically calculated based on lecture and lab
                                hours</small>
                        </div>

                        <input type="hidden" name="force_assign" id="editForceAssign" value="0">
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

                        <!-- Contract Type -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-file-contract me-2"></i>Contract
                                </div>
                                <div class="fw-semibold" id="viewContractType"></div>
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
                                    <i class="fa-solid fa-layer-group me-2"></i>Program
                                </div>
                                <div class="fw-semibold" id="viewProgramName"></div>
                            </div>
                        </div>

                        <!-- Academic Year -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-calendar me-2"></i>Academic Year
                                </div>
                                <div class="fw-semibold" id="viewAcademicYear"></div>
                            </div>
                        </div>

                        <!-- Semester / Block -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-list me-2"></i>Term
                                </div>
                                <div class="fw-semibold" id="viewTerm"></div>
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-graduation-cap me-2"></i>Department
                                </div>
                                <div class="fw-semibold" id="viewDepartmentName"></div>
                            </div>
                        </div>

                        <!-- Lecture Hours -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-chalkboard-user me-2"></i>Lecture Hours
                                </div>
                                <div class="fw-semibold" id="viewLectureHours"></div>
                            </div>
                        </div>

                        <!-- Laboratory Hours -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-flask me-2"></i>Laboratory Hours
                                </div>
                                <div class="fw-semibold" id="viewLabHours"></div>
                            </div>
                        </div>

                        <!-- Total Hours -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-calculator me-2"></i>Total Hours
                                </div>
                                <div class="fw-semibold text-primary" id="viewComputedUnits"></div>
                            </div>
                        </div>

                        <!-- Load Limits -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-scale-balanced me-2"></i>Load Limits
                                </div>
                                <div class="fw-semibold" id="viewLoadLimits"></div>
                            </div>
                        </div>

                        <!-- Current Load -->
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-gauge-high me-2"></i>Current Load
                                </div>
                                <div class="fw-semibold" id="viewCurrentLoad"></div>
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

    <!-- Overload Warning Modal -->
    <div class="modal fade" id="overloadWarningModal" tabindex="-1" aria-labelledby="overloadWarningModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="overloadWarningModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Load Limit Warning
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="overloadMessage">This assignment exceeds the load limit.</p>
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Current Lecture Hours</span>
                            <span class="fw-semibold" id="overloadCurrentLecture">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Current Lab Hours</span>
                            <span class="fw-semibold" id="overloadCurrentLab">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Max Lecture Hours</span>
                            <span class="fw-semibold" id="overloadMaxLecture">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Max Lab Hours</span>
                            <span class="fw-semibold" id="overloadMaxLab">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Excess Hours</span>
                            <span class="fw-semibold text-danger" id="overloadExcess">0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmForceAssignBtn">
                        <i class="fa-solid fa-check me-2"></i>Force Assign
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
    <script src="{{ asset('js/faculty-load-management.js') }}?v=20260221"></script>
@endsection
