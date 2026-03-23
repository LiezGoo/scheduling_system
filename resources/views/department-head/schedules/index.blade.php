@extends('layouts.app')

@section('page-title', 'Schedule Management')

@section('content')
<div class="container-fluid py-4">
    <style>
        /* Enhanced UI Styling */
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: #999;
        }

        .breadcrumb-item.active {
            color: #8B3A3A;
            font-weight: 600;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header-title {
            font-size: 2rem;
            font-weight: 700;
            color: #8B3A3A;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header-subtitle {
            font-size: 1rem;
            color: #6c757d;
            margin: 0;
            /* font-weight: 500; */
        }

        .filter-card {
            border: 1px solid #e9ecef;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            background: #fff;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .filter-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .filter-section-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-select {
            height: 38px;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238B3A3A' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            padding-right: 2.25rem;
            transition: all 0.2s ease;
        }

        .form-select:focus {
            border-color: #8B3A3A;
            box-shadow: 0 0 0 0.2rem rgba(139, 58, 58, 0.15);
        }

        .form-select:hover {
            border-color: #8B3A3A;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }

        #schedule-table-container.is-loading {
            opacity: 0.6;
            transition: opacity 0.2s ease;
        }

        .btn-generate-schedule {
            background-color: #8B3A3A;
            color: #fff;
            border: none;
            padding: 0.65rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 12px rgba(139, 58, 58, 0.2);
            white-space: nowrap;
        }

        .btn-generate-schedule:hover {
            background-color: #6f2d2d;
            box-shadow: 0 6px 16px rgba(139, 58, 58, 0.35);
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-generate-schedule:active {
            transform: translateY(0);
        }

        .content-card {
            border: 1px solid #e9ecef;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            background: #fff;
            overflow: hidden;
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(139, 58, 58, 0.03) 0%, rgba(139, 58, 58, 0.02) 100%);
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state-icon {
            font-size: 3.5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
            display: block;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.75rem;
        }

        .empty-state-text {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .empty-state-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Table Styling */
        .table-wrapper {
            position: relative;
            overflow-x: auto;
        }

        .table {
            font-size: 0.95rem;
        }

        .table thead {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table thead th {
            font-weight: 700;
            color: #000000;
            padding: 1rem;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: rgba(139, 58, 58, 0.02);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: #495057;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .badge.bg-success {
            background-color: #28a745 !important;
            color: #fff;
        }

        .badge.bg-info {
            background-color: #17a2b8 !important;
            color: #fff;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
            color: #fff;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
            color: #fff;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 0.75rem;
            animation: slideDown 0.3s ease;
            margin-bottom: 1.5rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Responsive Adjustments */
        @media (max-width: 1200px) {
            .page-header {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .page-header-title {
                font-size: 1.5rem;
            }

            .filter-card .card-body {
                padding: 1.5rem !important;
            }

            .btn-generate-schedule {
                width: 100%;
                justify-content: center;
            }

            .empty-state {
                padding: 2rem 1.5rem;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .btn-group-sm {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        @media (max-width: 576px) {
            .page-header-title {
                font-size: 1.25rem;
                gap: 0.5rem;
            }

            .empty-state-icon {
                font-size: 2.5rem;
            }

            .table-wrapper {
                font-size: 0.8rem;
            }
        }
    </style>

    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-start mb-5">
        <div>
            <p class="page-header-subtitle"><i class="fas fa-calendar-check"></i> Generate, manage, and publish academic schedules.</p>
        </div>
        <a href="{{ route('department-head.schedules.generate') }}" class="btn btn-generate-schedule">
            <i class="fas fa-dna"></i>Generate Schedule
        </a>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('department-head.schedules.index') }}" class="row g-3" id="scheduleFilterForm">
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filter_program" class="form-label">Program</label>
                    <select class="form-select" id="filter_program" name="program_id">
                        <option value="">All Programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                {{ $program->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filter_academic_year" class="form-label">Academic Year</label>
                    <select class="form-select" id="filter_academic_year" name="academic_year_id">
                        <option value="">All Years</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filter_semester" class="form-label">Semester</label>
                    <select class="form-select" id="filter_semester" name="semester">
                        <option value="">All Semesters</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester }}" {{ request('semester') == $semester ? 'selected' : '' }}>
                                {{ $semester }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filter_year_level" class="form-label">Year Level</label>
                    <select class="form-select" id="filter_year_level" name="year_level">
                        <option value="">All Levels</option>
                        @foreach ($yearLevelOptions as $yearLevelOption)
                            <option value="{{ $yearLevelOption['value'] }}" {{ request('year_level') == $yearLevelOption['value'] ? 'selected' : '' }}>
                                {{ $yearLevelOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="status">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="generated" {{ request('status') == 'generated' ? 'selected' : '' }}>Generated</option>
                        <option value="finalized" {{ request('status') == 'finalized' ? 'selected' : '' }}>Finalized</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Content Section -->
    <div class="content-card">
        <div id="schedule-table-container">
            @include('department-head.schedules.partials.table')
        </div>
    </div>
</div>

<script>
    // Smooth animations on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Add tooltip support
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        const filterForm = document.getElementById('scheduleFilterForm');
        const tableContainer = document.getElementById('schedule-table-container');
        const filterIds = [
            'filter_program',
            'filter_academic_year',
            'filter_semester',
            'filter_year_level',
            'filter_status'
        ];
        let debounceTimer;

        const buildFilterUrl = () => {
            const params = new URLSearchParams(new FormData(filterForm));
            params.delete('page');
            const query = params.toString();
            return `${filterForm.action}${query ? '?' + query : ''}`;
        };

        const applyFilters = async (url = null) => {
            const targetUrl = url || buildFilterUrl();
            tableContainer.classList.add('is-loading');

            try {
                const response = await fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                tableContainer.innerHTML = await response.text();
                window.history.replaceState({}, '', targetUrl);
            } catch (error) {
                console.error('Filter error:', error);
            } finally {
                tableContainer.classList.remove('is-loading');
            }
        };

        filterIds.forEach((id) => {
            const element = document.getElementById(id);
            if (!element) return;

            element.addEventListener('change', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => applyFilters(), 300);
            });
        });

        document.addEventListener('click', function (event) {
            const paginationLink = event.target.closest('#schedule-table-container .pagination a.page-link');
            if (!paginationLink) return;

            event.preventDefault();
            applyFilters(paginationLink.href);
        });

        document.addEventListener('change', function (event) {
            const perPageSelect = event.target.closest('#schedule-table-container .pagination-per-page-select');
            if (!perPageSelect) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPageSelect.value);
            url.searchParams.set('page', '1');
            applyFilters(url.toString());
        }, true);

        // Handle schedule delete via global confirmation modal (delegated for AJAX updates)
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.schedule-delete-btn');
            if (!button) return;

            const id = button.getAttribute('data-id');
            const message = button.getAttribute('data-message') || 'Delete this schedule?';

            showConfirmModal(message, function () {
                const deleteForm = document.getElementById('delete-form-' + id);
                if (deleteForm) {
                    deleteForm.submit();
                }
            }, {
                title: 'Confirm Delete',
                btnClass: 'btn-danger',
                btnText: '<i class="fa-solid fa-trash me-1"></i>Delete'
            });
        });

        // Close alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endsection
