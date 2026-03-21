@extends('layouts.app')

@section('page-title', 'Year Level Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-layer-group me-2"></i>Manage available academic year levels for all programs.
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addYearLevelModal">
                <i class="fa-solid fa-plus me-2"></i>Add Year Level
            </button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.year-levels.index') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <label for="filterYearLevelSearch" class="form-label">Search</label>
                            <input
                                type="search"
                                class="form-control"
                                id="filterYearLevelSearch"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="Search by year level name">
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.year-levels.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="yearLevelsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Year Level Name</th>
                                <th>Year Level Code</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($yearLevels as $yearLevel)
                                <tr>
                                    <td class="fw-semibold">{{ $yearLevel->name }}</td>
                                    <td>{{ $yearLevel->code ?: '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $yearLevel->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($yearLevel->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary view-year-level-btn"
                                                data-name="{{ $yearLevel->name }}"
                                                data-code="{{ $yearLevel->code }}"
                                                data-status="{{ $yearLevel->status }}"
                                                title="View">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning edit-year-level-btn"
                                                data-id="{{ $yearLevel->id }}"
                                                data-name="{{ $yearLevel->name }}"
                                                data-code="{{ $yearLevel->code }}"
                                                data-status="{{ $yearLevel->status }}"
                                                title="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger delete-year-level-btn"
                                                data-id="{{ $yearLevel->id }}"
                                                data-name="{{ $yearLevel->name }}"
                                                title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-layer-group fa-2x mb-2 d-block"></i>
                                        No year levels found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($yearLevels instanceof \Illuminate\Contracts\Pagination\Paginator && $yearLevels->count() > 0)
                    <x-pagination.footer :paginator="$yearLevels" />
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="addYearLevelModal" tabindex="-1" aria-labelledby="addYearLevelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="addYearLevelModalLabel">
                        <i class="fa-solid fa-plus me-2"></i>Add Year Level
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.year-levels.store') }}" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addYearLevelName" class="form-label">Year Level Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addYearLevelName" name="name" placeholder="e.g., 1st Year" required>
                        </div>
                        <div class="mb-3">
                            <label for="addYearLevelCode" class="form-label">Year Level Code</label>
                            <input type="text" class="form-control" id="addYearLevelCode" name="code" placeholder="e.g., 1">
                        </div>
                        <div class="mb-0">
                            <label for="addYearLevelStatus" class="form-label">Status</label>
                            <select class="form-select" id="addYearLevelStatus" name="status">
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
                            <i class="fa-solid fa-save me-2"></i>Save Year Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewYearLevelModal" tabindex="-1" aria-labelledby="viewYearLevelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="viewYearLevelModalLabel">
                        <i class="fa-regular fa-eye me-2"></i>Year Level Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Year Level Name</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewYearLevelName">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Year Level Code</label>
                            <p class="form-control-plaintext border-bottom pb-2" id="viewYearLevelCode">—</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Status</label>
                            <p class="form-control-plaintext" id="viewYearLevelStatus">—</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editYearLevelModal" tabindex="-1" aria-labelledby="editYearLevelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="editYearLevelModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit Year Level
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editYearLevelForm" method="POST" action="#" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editYearLevelName" class="form-label">Year Level Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editYearLevelName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editYearLevelCode" class="form-label">Year Level Code</label>
                            <input type="text" class="form-control" id="editYearLevelCode" name="code">
                        </div>
                        <div class="mb-0">
                            <label for="editYearLevelStatus" class="form-label">Status</label>
                            <select class="form-select" id="editYearLevelStatus" name="status">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Update Year Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteYearLevelModal" tabindex="-1" aria-labelledby="deleteYearLevelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="deleteYearLevelModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete year level <strong id="deleteYearLevelName"></strong>?</p>
                    <p class="text-muted mb-0"><i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.</p>
                </div>
                <form id="deleteYearLevelForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-trash me-2"></i>Delete Year Level
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
                const viewModal = new bootstrap.Modal(document.getElementById('viewYearLevelModal'));
                const editModal = new bootstrap.Modal(document.getElementById('editYearLevelModal'));
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteYearLevelModal'));

                document.querySelectorAll('.view-year-level-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        document.getElementById('viewYearLevelName').textContent = this.dataset.name || '—';
                        document.getElementById('viewYearLevelCode').textContent = this.dataset.code || '—';
                        document.getElementById('viewYearLevelStatus').textContent = (this.dataset.status || 'inactive').replace(/^./, c => c.toUpperCase());
                        viewModal.show();
                    });
                });

                document.querySelectorAll('.edit-year-level-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.id;
                        document.getElementById('editYearLevelForm').action = `/admin/year-levels/${id}`;
                        document.getElementById('editYearLevelName').value = this.dataset.name || '';
                        document.getElementById('editYearLevelCode').value = this.dataset.code || '';
                        document.getElementById('editYearLevelStatus').value = this.dataset.status || 'inactive';
                        editModal.show();
                    });
                });

                document.querySelectorAll('.delete-year-level-btn').forEach((button) => {
                    button.addEventListener('click', function () {
                        const id = this.dataset.id;
                        document.getElementById('deleteYearLevelName').textContent = this.dataset.name || '';
                        document.getElementById('deleteYearLevelForm').action = `/admin/year-levels/${id}`;
                        deleteModal.show();
                    });
                });
            });
        </script>
    @endpush
@endsection
