@extends('layouts.app')

@section('page-title', 'Block / Section Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-th-large me-2"></i>Manage class blocks or sections for scheduling.
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addBlockModal">
                <i class="fa-solid fa-plus me-2"></i>Add Block / Section
            </button>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.blocks.index') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filterProgram" class="form-label">Program</label>
                            <select class="form-select" id="filterProgram" name="program_id">
                                <option value="">All Programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" {{ $filters['program_id'] == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_code ?: $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filterAcademicYear" class="form-label">Academic Year</label>
                            <select class="form-select" id="filterAcademicYear" name="academic_year_id">
                                <option value="">All Years</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}" {{ $filters['academic_year_id'] == $academicYear->id ? 'selected' : '' }}>
                                        {{ $academicYear->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filterSemester" class="form-label">Semester</label>
                            <select class="form-select" id="filterSemester" name="semester_id">
                                <option value="">All Semesters</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ $filters['semester_id'] == $semester->id ? 'selected' : '' }}>
                                        {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filterYearLevel" class="form-label">Year Level</label>
                            <select class="form-select" id="filterYearLevel" name="year_level_id">
                                <option value="">All Year Levels</option>
                                @foreach ($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel->id }}" {{ $filters['year_level_id'] == $yearLevel->id ? 'selected' : '' }}>
                                        {{ $yearLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filterBlockSearch" class="form-label">Search</label>
                            <input
                                type="search"
                                class="form-control"
                                id="filterBlockSearch"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="Block name">
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('admin.blocks.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Blocks Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="blocksTable">
                        <thead class="table-light">
                            <tr>
                                <th>Program</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Year Level</th>
                                <th>Block / Section</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($blocks as $block)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $block->program->program_code ?: $block->program->program_name }}
                                    </td>
                                    <td>{{ $block->academicYear->name }}</td>
                                    <td>{{ $block->semester->name }}</td>
                                    <td>{{ $block->yearLevel->name }}</td>
                                    <td class="fw-bold text-primary">{{ $block->block_name }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $block->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($block->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary view-block-btn"
                                                data-program="{{ $block->program->program_code ?: $block->program->program_name }}"
                                                data-academic-year="{{ $block->academicYear->name }}"
                                                data-semester="{{ $block->semester->name }}"
                                                data-year-level="{{ $block->yearLevel->name }}"
                                                data-block-name="{{ $block->block_name }}"
                                                data-status="{{ $block->status }}"
                                                title="View">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning edit-block-btn"
                                                data-id="{{ $block->id }}"
                                                data-program-id="{{ $block->program_id }}"
                                                data-academic-year-id="{{ $block->academic_year_id }}"
                                                data-semester-id="{{ $block->semester_id }}"
                                                data-year-level-id="{{ $block->year_level_id }}"
                                                data-block-name="{{ $block->block_name }}"
                                                data-status="{{ $block->status }}"
                                                title="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger delete-block-btn"
                                                data-id="{{ $block->id }}"
                                                data-block-name="{{ $block->block_name }}"
                                                title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-th-large fa-2x mb-2 d-block"></i>
                                        No blocks found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($blocks instanceof \Illuminate\Contracts\Pagination\Paginator && count($blocks->items()) > 0)
                    <x-pagination.footer :paginator="$blocks" />
                @endif
            </div>
        </div>
    </div>

    <!-- Add Block Modal -->
    <div class="modal fade" id="addBlockModal" tabindex="-1" aria-labelledby="addBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="addBlockModalLabel">
                        <i class="fa-solid fa-plus me-2"></i>Add Block / Section
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.blocks.store') }}" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addBlockProgram" class="form-label">Program <span class="text-danger">*</span></label>
                            <select class="form-select" id="addBlockProgram" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->program_code ?: $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addBlockAcademicYear" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="addBlockAcademicYear" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addBlockSemester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="addBlockSemester" name="semester_id" required>
                                <option value="">-- Select Semester --</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addBlockYearLevel" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="addBlockYearLevel" name="year_level_id" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach ($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel->id }}">{{ $yearLevel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addBlockName" class="form-label">Block Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addBlockName" name="block_name" placeholder="e.g., 1, 2, A, B, C" required>
                            <small class="text-muted">Enter a unique block/section identifier</small>
                        </div>
                        <div class="mb-0">
                            <label for="addBlockStatus" class="form-label">Status</label>
                            <select class="form-select" id="addBlockStatus" name="status">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" {{ $statusOption === 'active' ? 'selected' : '' }}>
                                        {{ ucfirst($statusOption) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Save Block
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Block Modal -->
    <div class="modal fade" id="viewBlockModal" tabindex="-1" aria-labelledby="viewBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="viewBlockModalLabel">
                        <i class="fa-regular fa-eye me-2"></i>Block / Section Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Program</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewBlockProgram">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Academic Year</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewBlockAcademicYear">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Semester</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewBlockSemester">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Year Level</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewBlockYearLevel">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Block / Section</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewBlockName">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Status</label>
                            <p class="form-control-plaintext" id="viewBlockStatus">—</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Block Modal -->
    <div class="modal fade" id="editBlockModal" tabindex="-1" aria-labelledby="editBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="editBlockModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit Block / Section
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editBlockForm" method="POST" action="#" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editBlockProgram" class="form-label">Program <span class="text-danger">*</span></label>
                            <select class="form-select" id="editBlockProgram" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->program_code ?: $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editBlockAcademicYear" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="editBlockAcademicYear" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editBlockSemester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="editBlockSemester" name="semester_id" required>
                                <option value="">-- Select Semester --</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editBlockYearLevel" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="editBlockYearLevel" name="year_level_id" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach ($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel->id }}">{{ $yearLevel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editBlockName" class="form-label">Block Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editBlockName" name="block_name" required>
                        </div>
                        <div class="mb-0">
                            <label for="editBlockStatus" class="form-label">Status</label>
                            <select class="form-select" id="editBlockStatus" name="status">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Update Block
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Block Modal -->
    <div class="modal fade" id="deleteBlockModal" tabindex="-1" aria-labelledby="deleteBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="deleteBlockModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete block <strong id="deleteBlockName"></strong>?</p>
                    <p class="text-muted mb-0"><i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.</p>
                </div>
                <form id="deleteBlockForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-trash me-2"></i>Delete Block
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .btn-maroon {
                background-color: #800000;
                border-color: #800000;
                color: #fff;
            }

            .btn-maroon:hover {
                background-color: #660000;
                border-color: #660000;
                color: #fff;
            }

            .bg-maroon {
                background-color: #800000 !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const viewModal = new bootstrap.Modal(document.getElementById('viewBlockModal'));
                const editModal = new bootstrap.Modal(document.getElementById('editBlockModal'));
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteBlockModal'));

                // View Block
                document.querySelectorAll('.view-block-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        document.getElementById('viewBlockProgram').textContent = this.dataset.program || '—';
                        document.getElementById('viewBlockAcademicYear').textContent = this.dataset.academicYear || '—';
                        document.getElementById('viewBlockSemester').textContent = this.dataset.semester || '—';
                        document.getElementById('viewBlockYearLevel').textContent = this.dataset.yearLevel || '—';
                        document.getElementById('viewBlockName').textContent = this.dataset.blockName || '—';
                        document.getElementById('viewBlockStatus').textContent = (this.dataset.status || 'inactive').replace(/^./, c => c.toUpperCase());
                        viewModal.show();
                    });
                });

                // Edit Block
                document.querySelectorAll('.edit-block-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.id;
                        document.getElementById('editBlockForm').action = `/admin/blocks/${id}`;
                        document.getElementById('editBlockProgram').value = this.dataset.programId || '';
                        document.getElementById('editBlockAcademicYear').value = this.dataset.academicYearId || '';
                        document.getElementById('editBlockSemester').value = this.dataset.semesterId || '';
                        document.getElementById('editBlockYearLevel').value = this.dataset.yearLevelId || '';
                        document.getElementById('editBlockName').value = this.dataset.blockName || '';
                        document.getElementById('editBlockStatus').value = this.dataset.status || 'active';
                        editModal.show();
                    });
                });

                // Delete Block
                document.querySelectorAll('.delete-block-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.id;
                        document.getElementById('deleteBlockName').textContent = this.dataset.blockName || '';
                        document.getElementById('deleteBlockForm').action = `/admin/blocks/${id}`;
                        deleteModal.show();
                    });
                });
            });
        </script>
    @endpush
@endsection
