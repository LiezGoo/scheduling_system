@extends('layouts.app')

@section('page-title', 'Department Management')

@section('content')
    <div class="container-fluid pt-4 pb-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="col-md-6">
                <p class="text-muted mb-0"><i class="fas fa-building me-2"></i>Manage academic departments</p>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="fas fa-plus me-2"></i> Add Department
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($message = Session::get('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filter Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label for="search" class="form-label small fw-bold">Search</label>
                        <div class="position-relative">
                            <input type="text" id="search" class="form-control"
                                placeholder="Department code or name..." autocomplete="off" value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFilterBtn"
                            title="Clear Filters">
                            <i class="fa-solid fa-rotate-left me-1"></i>Clear
                        </button>
                        <div class="spinner-border spinner-border-sm text-maroon d-none" role="status" aria-hidden="true"
                            id="filter-spinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="departmentsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Department Code</th>
                                <th>Department Name</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="departments-table-body">
                            @include('admin.departments.partials.table-rows', [
                                'departments' => $departments,
                            ])
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($departments && $departments->count() > 0)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <div class="text-muted small">
                            Showing {{ $departments->firstItem() ?? 0 }} to {{ $departments->lastItem() ?? 0 }} of
                            {{ $departments->total() }} departments
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <label for="departmentPerPageSelect" class="text-muted small mb-0">Per page:</label>
                                <select id="departmentPerPageSelect" class="form-select form-select-sm"
                                    style="width: auto;">
                                    <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15"
                                        {{ request('per_page') == 15 || !request('per_page') ? 'selected' : '' }}>15
                                    </option>
                                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                </select>
                            </div>
                            <div id="pagination-container">
                                {{ $departments->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('admin.departments.modals.add-department')
    @include('admin.departments.modals.edit-department')
    @include('admin.departments.modals.delete-department')

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
            color: white;
        }
    </style>

    <script>
        const searchInput = document.getElementById('search');
        const perPageSelect = document.getElementById('departmentPerPageSelect');
        const tableBody = document.getElementById('departments-table-body');
        const paginationContainer = document.getElementById('pagination-container');
        const spinner = document.getElementById('filter-spinner');

        // Debounce function for search
        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // Fetch filtered departments
        function fetchDepartments(resetToFirstPage = false) {
            const search = searchInput.value;
            const perPage = perPageSelect.value;
            const page = resetToFirstPage ? 1 : (new URLSearchParams(window.location.search).get('page') || 1);

            spinner.style.display = 'block';

            fetch(`/admin/departments?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = data.html;
                        paginationContainer.innerHTML = data.pagination;
                        window.history.pushState({}, '',
                            `/admin/departments?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`
                        );
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load departments');
                })
                .finally(() => {
                    spinner.style.display = 'none';
                    attachTableRowListeners();
                });
        }

        // Search input listener
        searchInput.addEventListener('input', debounce(fetchDepartments, 500));

        // Per page select listener
        perPageSelect.addEventListener('change', fetchDepartments);

        // Clear filter button listener
        document.getElementById('clearFilterBtn').addEventListener('click', function() {
            searchInput.value = '';
            fetchDepartments(true);
        });

        // Attach listeners to table rows
        function attachTableRowListeners() {
            // Edit button listeners
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-department-btn')) {
                    const btn = e.target.closest('.edit-department-btn');
                    const deptId = btn.getAttribute('data-department-id');
                    const deptCode = btn.getAttribute('data-department-code');
                    const deptName = btn.getAttribute('data-department-name');

                    document.getElementById('edit_department_id').value = deptId;
                    document.getElementById('edit_department_code').value = deptCode;
                    document.getElementById('edit_department_name').value = deptName;

                    document.getElementById('editDepartmentForm').classList.remove('was-validated');

                    const editModal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
                    editModal.show();
                }

                // Delete button listeners
                if (e.target.closest('.delete-department-btn')) {
                    const btn = e.target.closest('.delete-department-btn');
                    const deptId = btn.getAttribute('data-department-id');
                    const deptName = btn.getAttribute('data-department-name');

                    document.getElementById('delete_department_id').value = deptId;
                    document.getElementById('delete_department_name').textContent = deptName;

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteDepartmentModal'));
                    deleteModal.show();
                }
            });
        }

        // Pagination link handling
        document.addEventListener('click', function(e) {
            if (e.target.closest('.pagination a')) {
                e.preventDefault();
                const url = e.target.closest('a').getAttribute('href');
                const page = new URLSearchParams(new URL(url, window.location.origin).search).get('page');
                const search = searchInput.value;
                const perPage = perPageSelect.value;

                spinner.style.display = 'block';

                fetch(`/admin/departments?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            tableBody.innerHTML = data.html;
                            paginationContainer.innerHTML = data.pagination;
                            window.history.pushState({}, '',
                                `/admin/departments?search=${encodeURIComponent(search)}&per_page=${perPage}&page=${page}`
                            );
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        spinner.style.display = 'none';
                        attachTableRowListeners();
                    });
            }
        });

        attachTableRowListeners();
    </script>
@endsection
