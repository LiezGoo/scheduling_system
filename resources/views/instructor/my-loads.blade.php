@extends('layouts.app')

@section('page-title', 'My Loads')

@section('content')
<div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0"> <i class="fa-solid fa-users-gear me-2"></i>
                    View your assigned courses, teaching units, and overall faculty workload</p>
            </div>
        </div>
        <!-- Filter Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label for="search" class="form-label small fw-bold">Search</label>
                        <div class="position-relative">
                            <input type="text" id="search" class="form-control"
                                placeholder="Subject code or name..." autocomplete="off" value="{{ request('search') }}">
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

        <div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th class="text-center">Units</th>
                        <th class="text-center">Lec</th>
                        <th class="text-center">Lab</th>
                        <th class="text-center">Schedule</th>
                        <th class="text-center">Room</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>IT101</strong></td>
                        <td>Introduction to Computing</td>
                        <td class="text-center">3</td>
                        <td class="text-center">2</td>
                        <td class="text-center">1</td>
                        <td class="text-center">M/W 9:00 AM - 10:30 AM</td>
                        <td class="text-center">ICT Lab 1</td>
                    </tr>
                    <tr>
                        <td><strong>IT102</strong></td>
                        <td>Computer Programming 1</td>
                        <td class="text-center">3</td>
                        <td class="text-center">2</td>
                        <td class="text-center">1</td>
                        <td class="text-center">T/Th 1:00 PM - 2:30 PM</td>
                        <td class="text-center">ICT Lab 2</td>
                    </tr>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="2" class="text-end"><strong>Total Teaching Units:</strong></td>
                        <td class="text-center"><strong>6</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

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
</div>

@endsection