@extends('layouts.app')

@section('page-title', 'Faculty Workload Configuration')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-hourglass-end me-2"></i>Define faculty teaching limits and availability schemes
                </p>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-building-columns me-2"></i>{{ $program->program_name }}
                </p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#configureWorkloadModal">
                <i class="fa-solid fa-plus me-2"></i>Faculty Workload
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('program-head.faculty-workload-configurations.index') }}" id="filterForm"
                    data-list-url="{{ route('program-head.faculty-workload-configurations.index') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filterSearch" class="form-label">Search Faculty</label>
                            <input type="text" class="form-control" id="filterSearch" name="search"
                                placeholder="Search by name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filterDepartment" class="form-label">Department</label>
                            <select class="form-select" id="filterDepartment" name="department">
                                <option value="">All Departments</option>
                                <option value="{{ $department->id }}" @selected(request('department') == $department->id)>
                                    {{ $department->department_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterContractType" class="form-label">Contract Type</label>
                            <select class="form-select" id="filterContractType" name="contract_type">
                                <option value="">All Types</option>
                                <option value="Full-Time" @selected(request('contract_type') == 'Full-Time')>Full-Time</option>
                                <option value="Part-Time" @selected(request('contract_type') == 'Part-Time')>Part-Time</option>
                                <option value="Contractual" @selected(request('contract_type') == 'Contractual')>Contractual</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters"
                                title="Clear Filters">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </button>
                            <div class="spinner-border spinner-border-sm text-success d-none" role="status"
                                aria-hidden="true" id="filtersSpinner"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Configurations Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="configurationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Faculty Name</th>
                                <th class="text-center">Department</th>
                                <th class="text-center">Contract Type</th>
                                <th class="text-center">Lecture Limit</th>
                                <th class="text-center">Lab Limit</th>
                                <th class="text-center">Max Hours/Day</th>
                                <th class="text-center">Available Days</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="configurationsTableBody">
                            @include('program-head.faculty-workload-configurations.partials.table-rows')
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <nav id="configurationsPagination" class="d-flex justify-content-between align-items-center mt-4 {{ $configurations->hasPages() ? '' : 'd-none' }}">
                    <div class="text-muted small">
                        Showing {{ $configurations->firstItem() ?? 0 }} to {{ $configurations->lastItem() ?? 0 }} of {{ $configurations->total() ?? 0 }} results
                    </div>
                    <div id="configurationsPaginationLinks">
                        {{ $configurations->links('pagination::bootstrap-5', ['class' => 'mb-0']) }}
                    </div>
                </nav>
            </div>
        </div>
    </div>

    @include('program-head.faculty-workload-configurations.modals.configure-workload')
    @include('components.modals.confirm-modal')
    @include('components.modals.success-modal')
    @include('components.modals.error-modal')
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

        .text-success {
            color: #198754;
        }

        .bg-success {
            background-color: #198754;
        }

        .badge-success {
            background-color: #198754;
        }

        .badge-info {
            background-color: #0dcaf0;
        }

        .page-link {
            color: #660000;
        }

        .page-link:hover {
            color: #550000;
            background-color: #e9ecef;
        }

        .page-link.active {
            background-color: #660000;
            border-color: #660000;
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
            const tableBody = document.getElementById('configurationsTableBody');
            const paginationContainer = document.getElementById('configurationsPagination');
            const paginationLinksContainer = document.getElementById('configurationsPaginationLinks');

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
                            if (paginationLinksContainer) {
                                paginationLinksContainer.innerHTML = data.pagination || '';
                            }
                            if (paginationContainer) {
                                const hasPagination = !!(data.pagination && data.pagination.trim() !== '');
                                paginationContainer.classList.toggle('d-none', !hasPagination);
                            }
                            // Re-attach click handlers
                            attachActionHandlers();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        openSystemModal({
                            type: 'error',
                            title: 'Error',
                            message: 'An error occurred while filtering. Please refresh the page.'
                        });
                    })
                    .finally(() => spinner.classList.add('d-none'));
            }

            // Attach action handlers
            attachActionHandlers();

            // Expose table refresh so modal save/delete can update list without full page reload.
            window.refreshFacultyWorkloadTable = function() {
                applyFilters();
            };

            function attachActionHandlers() {
                // Edit button handlers
                document.querySelectorAll('[data-action="edit"]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        editConfiguration(configId);
                    });
                });

                // Delete button handlers
                document.querySelectorAll('[data-action="delete"]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        const facultyName = this.dataset.facultyName;
                        deleteConfiguration(configId, facultyName);
                    });
                });

                // View button handlers
                document.querySelectorAll('[data-action="view"]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        viewConfiguration(configId);
                    });
                });
            }
        });

        function editConfiguration(configId) {
            fetch(`/program-head/faculty-workload-configurations/${configId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateConfigureForm(data.configuration, true);
                    const modal = new bootstrap.Modal(document.getElementById('configureWorkloadModal'));
                    modal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                openSystemModal({
                    type: 'error',
                    title: 'Error',
                    message: 'Failed to load configuration.'
                });
            });
        }

        function viewConfiguration(configId) {
            fetch(`/program-head/faculty-workload-configurations/${configId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const config = data.configuration;
                    alert(`Faculty: ${config.faculty.name}\nDepartment: ${config.program.department.department_name}\nContract Type: ${config.contract_type}\nLecture Limit: ${config.max_lecture_hours}\nLab Limit: ${config.max_lab_hours}\nMax Hours/Day: ${config.max_hours_per_day}\nAvailable Days: ${config.available_days.join(', ')}`);
                }
            });
        }

        function deleteConfiguration(configId, facultyName) {
            openSystemModal({
                type: 'confirm',
                title: 'Confirm Deletion',
                message: `Are you sure you want to delete the workload configuration for ${facultyName}?`,
                confirmText: 'Delete',
                cancelText: 'Cancel',
                onConfirm: function() {
                    performDelete(configId);
                }
            });
        }

        function performDelete(configId) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/program-head/faculty-workload-configurations/${configId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.refreshFacultyWorkloadTable === 'function') {
                        window.refreshFacultyWorkloadTable();
                    }

                    openSystemModal({
                        type: 'success',
                        title: 'Success',
                        message: data.message || 'Configuration deleted successfully.',
                    });
                } else {
                    openSystemModal({
                        type: 'error',
                        title: 'Error',
                        message: data.message || 'Failed to delete configuration.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                openSystemModal({
                    type: 'error',
                    title: 'Error',
                    message: 'An error occurred while deleting.'
                });
            });
        }
    </script>
@endpush
