@extends('layouts.app')

@section('page-title', 'Program Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0"><i class="fa-solid fa-diagram-project"></i> Manage academic programs and
                    departments
                </p>
            </div>
            <button type="button" class="btn btn-primary-theme d-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#addProgramModal">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Program</span>
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="programFilterForm" class="m-0" method="GET" action="{{ url('/admin/programs') }}"
                    data-list-url="{{ url('/admin/programs') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="filterQuery" class="form-label">Search</label>
                            <input type="search" class="form-control" id="filterQuery" name="q"
                                placeholder="Search by program name or code" value="{{ request('q') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="filterDepartment" class="form-label">Department</label>
                            <select id="filterDepartment" name="department" class="form-select">
                                <option value="">All Departments</option>
                                @isset($departments)
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept['id'] ?? $dept->id }}"
                                            {{ request('department') == ($dept['id'] ?? $dept->id) ? 'selected' : '' }}>
                                            {{ $dept['name'] ?? $dept->name }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearProgramFilters"
                                title="Clear Filters">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </button>
                            <div class="spinner-border spinner-border-sm text-maroon d-none" role="status"
                                aria-hidden="true" id="programsFiltersSpinner"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Programs Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="programsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Program Code</th>
                                <th>Program Name</th>
                                <th>Department</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="programsTableBody">
                            @include('admin.programs.partials.table-rows')
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted" id="programsSummary">
                        @include('admin.programs.partials.summary')
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div id="programsPagination">
                            @include('admin.programs.partials.pagination')
                        </div>
                        <label for="programsPerPageSelect" class="text-muted small mb-0">Per page:</label>
                        <select id="programsPerPageSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal fade" id="addProgramModal" tabindex="-1" aria-labelledby="addProgramModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="addProgramModalLabel">
                        <i class="fa-solid fa-graduation-cap me-2"></i>Add New Program
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addProgramForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addProgramCode" class="form-label">Program Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addProgramCode" name="code" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addProgramName" class="form-label">Program Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addProgramName" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addProgramDepartment" class="form-label">Department</label>
                            <select class="form-select" id="addProgramDepartment" name="department_id">
                                <option value="">Select Department</option>
                                @isset($departments)
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept['id'] ?? $dept->id }}">{{ $dept['name'] ?? $dept->name }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Save Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Program Modal -->
    <div class="modal fade" id="editProgramModal" tabindex="-1" aria-labelledby="editProgramModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="editProgramModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit Program
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editProgramForm" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editProgramId" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editProgramCode" class="form-label">Program Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editProgramCode" name="code" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editProgramName" class="form-label">Program Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editProgramName" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editProgramDepartment" class="form-label">Department</label>
                            <select class="form-select" id="editProgramDepartment" name="department_id">
                                <option value="">Select Department</option>
                                @isset($departments)
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept['id'] ?? $dept->id }}">{{ $dept['name'] ?? $dept->name }}
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Update Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProgramModal" tabindex="-1" aria-labelledby="deleteProgramModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteProgramModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete program <strong id="deleteProgramName"></strong>?</p>
                    <p class="text-danger mb-0"><i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteProgramBtn">
                        <i class="fa-solid fa-trash me-2"></i>Delete Program
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/program-management.js') }}"></script>
    @endpush
@endsection
