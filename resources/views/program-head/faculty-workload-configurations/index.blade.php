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
                                @foreach ($contractTypeOptions as $contractTypeOption)
                                    <option value="{{ $contractTypeOption }}" @selected(request('contract_type') == $contractTypeOption)>
                                        {{ $contractTypeOption }}
                                    </option>
                                @endforeach
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
    @include('program-head.faculty-workload-configurations.modals.view-workload')
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
        // Global toast notification function
        window.showToast = function(type, message) {
            const toastContainer = document.getElementById('globalToastContainer');
            if (!toastContainer) return;

            const iconMap = {
                'success': 'fa-circle-check',
                'error': 'fa-circle-xmark',
                'warning': 'fa-triangle-exclamation'
            };

            const bgMap = {
                'success': 'text-bg-success',
                'error': 'text-bg-danger',
                'warning': 'text-bg-warning'
            };

            const toastHtml = `
                <div class="toast align-items-center ${bgMap[type] || 'text-bg-info'} border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fa-solid ${iconMap[type] || 'fa-info-circle'} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = toastHtml;
            const toastElement = tempDiv.firstElementChild;
            toastContainer.appendChild(toastElement);

            const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        };

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
            function applyFilters(options = {}) {
                const { silent = false } = options;
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);

                spinner.classList.remove('d-none');

                return fetch(`${filterForm.dataset.listUrl}?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => null);

                        if (!response.ok || !payload) {
                            throw new Error('Invalid filter response.');
                        }

                        return payload;
                    })
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

                            return true;
                        }

                        throw new Error('Filter request did not return success.');
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        if (!silent) {
                            showToast('error', 'An error occurred while filtering. Please refresh the page.');
                        }

                        return false;
                    })
                    .finally(() => spinner.classList.add('d-none'));
            }

            // Attach action handlers
            attachActionHandlers();

            // Expose table refresh so modal save/delete can update list without full page reload.
            window.refreshFacultyWorkloadTable = function(options = {}) {
                return applyFilters(options);
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
                showToast('error', 'Failed to load configuration.');
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
                    populateViewModal(config);
                    const modal = new bootstrap.Modal(document.getElementById('viewWorkloadModal'));
                    modal.show();
                } else {
                    showToast('error', 'Failed to load configuration.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while loading the configuration.');
            });
        }

        function populateViewModal(config) {
            const facultyName = [config?.faculty?.first_name, config?.faculty?.last_name].filter(Boolean).join(' ').trim();
            const resolvedFacultyName = facultyName || config?.faculty?.full_name || config?.faculty?.name || '-';
            const resolvedDepartmentName = config?.faculty?.department?.department_name || config?.program?.department?.department_name || '-';

            // Faculty information
            document.getElementById('viewFacultyName').textContent = resolvedFacultyName;
            document.getElementById('viewDepartmentName').textContent = resolvedDepartmentName;

            // Status badge
            const statusBadge = document.getElementById('viewStatus');
            if (config.is_active) {
                statusBadge.className = 'badge bg-success';
                statusBadge.textContent = 'Active';
            } else {
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'Inactive';
            }

            // Teaching load constraints
            document.getElementById('viewMaxLectureHours').textContent = config.max_lecture_hours + ' hrs/week';
            document.getElementById('viewMaxLabHours').textContent = config.max_lab_hours + ' hrs/week';
            document.getElementById('viewMaxHoursPerDay').textContent = config.max_hours_per_day + ' hrs/day';

            // Availability schedule
            const availabilityContainer = document.getElementById('viewAvailabilitySchedule');
            availabilityContainer.innerHTML = '';

            const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const teachingScheme = config.teaching_scheme || {};
            const availableDays = Array.isArray(config.available_days) ? config.available_days : [];

            const formatTime = (timeValue) => {
                if (!timeValue) {
                    return '-';
                }

                const hhmm = String(timeValue).substring(0, 5);
                return new Date(`1970-01-01T${hhmm}:00`).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit'
                });
            };

            daysOfWeek.forEach((day) => {
                const scheme = teachingScheme[day] || teachingScheme[day.toLowerCase()] || {};
                const hasStartEnd = !!(scheme.start && scheme.end);
                const isEnabled = hasStartEnd || availableDays.includes(day);

                const startTime = formatTime(scheme.start);
                const endTime = formatTime(scheme.end);

                const availabilityItem = document.createElement('div');
                availabilityItem.className = `availability-item ${!isEnabled ? 'disabled' : ''}`;
                availabilityItem.innerHTML = `
                    <div class="availability-day">
                        <i class="fa-solid fa-calendar-day me-2"></i>${day}
                    </div>
                    <div class="availability-time ${isEnabled ? '' : 'availability-time-muted'}">${isEnabled ? startTime : '-'}</div>
                    <div class="availability-time ${isEnabled ? '' : 'availability-time-muted'}">${isEnabled ? endTime : '-'}</div>
                    <div class="text-end">
                        ${isEnabled
                            ? '<span class="badge bg-success availability-badge">Available</span>'
                            : '<span class="badge bg-secondary availability-badge">Not Available</span>'
                        }
                    </div>
                `;
                availabilityContainer.appendChild(availabilityItem);
            });
        }

        function deleteConfiguration(configId, facultyName) {
            const confirmModalEl = document.getElementById('confirmActionModal');
            const confirmTitle = document.getElementById('confirmModalTitle');
            const confirmMessage = document.getElementById('confirmModalMessage');
            const confirmButton = document.getElementById('confirmActionButton');
            const confirmForm = document.getElementById('confirmActionForm');

            if (!confirmModalEl || !confirmTitle || !confirmMessage || !confirmButton || !confirmForm) {
                if (confirm(`Are you sure you want to delete the workload configuration for ${facultyName}? This action cannot be undone.`)) {
                    performDelete(configId);
                }
                return;
            }

            const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);

            confirmTitle.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion';
            confirmMessage.textContent = `Are you sure you want to delete the workload configuration for ${facultyName}? This action cannot be undone.`;
            confirmButton.className = 'btn btn-maroon';
            confirmButton.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Delete';

            confirmForm.onsubmit = function(e) {
                e.preventDefault();
                confirmModal.hide();
                performDelete(configId);
            };

            confirmModal.show();
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
                        window.refreshFacultyWorkloadTable({ silent: true })
                            .then((refreshed) => {
                                if (!refreshed) {
                                    console.warn('Table refresh failed after delete. Keeping success state visible.');
                                }
                            });
                    }

                    showToast('success', data.message || 'Faculty workload configuration deleted successfully!');
                } else {
                    showToast('error', data.message || 'Failed to delete configuration.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while deleting.');
            });
        }
    </script>
@endpush
