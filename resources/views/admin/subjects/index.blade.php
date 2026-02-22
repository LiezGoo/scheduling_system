@extends('layouts.app')

@section('page-title', 'Subject Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-book me-2"></i>Manage academic subjects
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="fa-solid fa-plus me-2"></i>Add New Subject
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.subjects.index') }}" id="filterForm"
                    data-list-url="{{ route('admin.subjects.index') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filterSearch" class="form-label">Search</label>
                            <input type="text" class="form-control" id="filterSearch" name="search"
                                placeholder="Search by code or name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filterDepartment" class="form-label">Department</label>
                            <select class="form-select" id="filterDepartment" name="department_id">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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

        <!-- Subjects Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="subjectsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Department</th>
                                <th class="text-center">Units</th>
                                <th class="text-center">Lecture Hrs</th>
                                <th class="text-center">Lab Hrs</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subjectsTableBody">
                            @include('admin.subjects.partials.table-rows')
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if ($subjects && $subjects->count() > 0)
                    <x-pagination.footer :paginator="$subjects" />
                @endif
            </div>
        </div>
    </div>

    @include('admin.subjects.modals.add-subject')
    @include('admin.subjects.modals.edit-subject')
    @include('admin.subjects.modals.delete-subject')
@endsection

@push('styles')
    <style>
        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: #fff;
        }

        .btn-maroon:hover {
            background-color: #550000;
            border-color: #550000;
            color: #fff;
        }

        .text-maroon {
            color: #660000;
        }

        .bg-maroon {
            background-color: #660000;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const filterInputs = filterForm.querySelectorAll('input, select');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const spinner = document.getElementById('filtersSpinner');
            const tableBody = document.getElementById('subjectsTableBody');
            const pagination = document.getElementById('subjectsPagination');

            // Auto-submit on filter change
            filterInputs.forEach(input => {
                input.addEventListener('change', () => applyFilters());
            });

            // Search with debounce
            const searchInput = document.getElementById('filterSearch');
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => applyFilters(), 500);
            });

            // Clear filters
            clearFiltersBtn.addEventListener('click', () => {
                filterForm.reset();
                applyFilters();
            });

            // Apply filters via AJAX
            function applyFilters() {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                // Include per_page if present in URL
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('per_page')) {
                    params.set('per_page', urlParams.get('per_page'));
                }

                spinner.classList.remove('d-none');

                fetch(`${filterForm.dataset.listUrl}?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            tableBody.innerHTML = data.html;
                            pagination.innerHTML = data.pagination;
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => spinner.classList.add('d-none'));
            }
        });
    </script>
@endpush
