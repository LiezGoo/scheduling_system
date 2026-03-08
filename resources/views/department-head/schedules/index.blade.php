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

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .btn-apply-filters {
            background-color: #8B3A3A;
            color: #fff;
            border: none;
            height: 38px;
            padding: 0 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-apply-filters:hover {
            background-color: #6f2d2d;
            box-shadow: 0 4px 12px rgba(139, 58, 58, 0.3);
            transform: translateY(-1px);
        }

        .btn-reset-filters {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            height: 38px;
            padding: 0 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            text-decoration: none;
        }

        .btn-reset-filters:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #212529;
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
            color: #495057;
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
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

        .btn-group-sm .btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 0.4rem;
            transition: all 0.2s ease;
            border: 1px solid #dee2e6;
        }

        .btn-outline-primary {
            color: #0056b3;
            border-color: #0056b3;
        }

        .btn-outline-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: #fff;
        }

        .btn-outline-warning {
            color: #ff9800;
            border-color: #ff9800;
        }

        .btn-outline-warning:hover {
            background-color: #ff9800;
            border-color: #ff9800;
            color: #fff;
        }

        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .btn-group-sm .btn[title] {
            position: relative;
        }

        .pagination {
            margin: 0;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .pagination .page-link {
            color: #8B3A3A;
            border-color: #dee2e6;
            margin: 0 0.25rem;
            border-radius: 0.4rem;
        }

        .pagination .page-link:hover {
            color: #6f2d2d;
            background-color: #f8f9fa;
            border-color: #8B3A3A;
        }

        .pagination .page-item.active .page-link {
            background-color: #8B3A3A;
            border-color: #8B3A3A;
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

            .filter-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-apply-filters,
            .btn-reset-filters {
                width: 100%;
                justify-content: center;
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
            <form method="GET" action="{{ route('department-head.schedules.index') }}" class="row g-3">
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="program_id" class="form-label">Program</label>
                    <select class="form-select" id="program_id" name="program_id">
                        <option value="">All Programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                {{ $program->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="academic_year_id" class="form-label">Academic Year</label>
                    <select class="form-select" id="academic_year_id" name="academic_year_id">
                        <option value="">All Years</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester">
                        <option value="">All Semesters</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester }}" {{ request('semester') == $semester ? 'selected' : '' }}>
                                {{ $semester }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="year_level" class="form-label">Year Level</label>
                    <select class="form-select" id="year_level" name="year_level">
                        <option value="">All Levels</option>
                        @foreach ($yearLevels as $level)
                            <option value="{{ $level }}" {{ request('year_level') == $level ? 'selected' : '' }}>
                                {{ $level }}{{ $level == 1 ? 'st' : ($level == 2 ? 'nd' : ($level == 3 ? 'rd' : 'th')) }} Year
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="generated" {{ request('status') == 'generated' ? 'selected' : '' }}>Generated</option>
                        <option value="finalized" {{ request('status') == 'finalized' ? 'selected' : '' }}>Finalized</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label class="form-label" style="opacity: 0;">Action</label>
                    <div class="filter-actions">
                        <button type="submit" class="btn-apply-filters">
                            <i class="fas fa-search"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </form>

            <!-- Reset Filters Option -->
            @if (request()->anyFilled(['program_id', 'academic_year_id', 'semester', 'year_level', 'status']))
                <div class="mt-3">
                    <a href="{{ route('department-head.schedules.index') }}" class="btn-reset-filters">
                        <i class="fas fa-times-circle"></i>Clear All Filters
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Content Section -->
    <div class="content-card">
        @if ($schedules->isEmpty())
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-calendar-times empty-state-icon"></i>
                <h2 class="empty-state-title">No Schedules Found</h2>
                <p class="empty-state-text">No schedules match the selected filters.</p>
                <div class="empty-state-actions">
                    <a href="{{ route('department-head.schedules.generate') }}" class="btn btn-generate-schedule">
                        <i class="fas fa-dna"></i>Generate New Schedule
                    </a>
                    @if (request()->anyFilled(['program_id', 'academic_year_id', 'semester', 'year_level', 'status']))
                        <a href="{{ route('department-head.schedules.index') }}" class="btn-reset-filters">
                            <i class="fas fa-times-circle"></i>Clear Filters
                        </a>
                    @endif
                </div>
            </div>
        @else
            <!-- Schedules Table -->
            <div class="table-wrapper">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Year Level</th>
                            <th>Block</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($schedules as $schedule)
                            <tr>
                                <td class="fw-semibold">{{ $schedule->program->program_name ?? 'N/A' }}</td>
                                <td>{{ $schedule->academic_year ?? 'N/A' }}</td>
                                <td>{{ $schedule->semester ?? 'N/A' }}</td>
                                <td>{{ $schedule->year_level ? $schedule->year_level . ' Year' : 'N/A' }}</td>
                                <td>{{ $schedule->block ?? 'N/A' }}</td>
                                <td>
                                    @php($status = strtolower($schedule->status ?? 'draft'))
                                    @if ($status === 'finalized')
                                        <span class="badge bg-success">Finalized</span>
                                    @elseif ($status === 'generated')
                                        <span class="badge bg-info">Generated</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>{{ $schedule->creator->first_name ?? 'N/A' }} {{ $schedule->creator->last_name ?? '' }}</td>
                                <td>
                                    <span title="{{ optional($schedule->created_at)->format('M d, Y h:i A') }}">
                                        {{ optional($schedule->created_at)->format('M d, Y') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('department-head.schedules.show', $schedule) }}" class="btn btn-outline-primary" title="View Schedule">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (in_array($status, ['draft', 'generated']))
                                            <a href="{{ route('department-head.schedules.edit', $schedule) }}" class="btn btn-outline-warning" title="Edit Schedule">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="POST" action="{{ route('department-head.schedules.finalize', $schedule) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to finalize and publish this schedule?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Finalize & Publish">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($schedules->hasPages())
                <div class="pagination">
                    {{ $schedules->withQueryString()->links() }}
                </div>
            @endif
        @endif
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
