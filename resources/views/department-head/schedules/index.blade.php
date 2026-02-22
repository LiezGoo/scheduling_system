@extends('layouts.app')

@section('page-title', 'Schedule Approval')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="mb-4">
            <p class="text-muted mb-0">
                <i class="fa-solid fa-clipboard-check me-2"></i>Review and approve schedules submitted by program heads.
            </p>
        </div>

        <!-- Success Alert -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <a href="{{ route('department-head.schedules.index', array_merge(request()->except('page'), ['status' => 'PENDING_APPROVAL'])) }}"
                    class="card shadow-sm border-0 h-100 summary-card text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="summary-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-hourglass-end"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Pending</p>
                            <div class="fs-4 fw-bold text-warning">{{ $pendingCount }}</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="{{ route('department-head.schedules.index', array_merge(request()->except('page'), ['status' => 'APPROVED'])) }}"
                    class="card shadow-sm border-0 h-100 summary-card text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="summary-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Approved</p>
                            <div class="fs-4 fw-bold text-success">{{ $approvedCount }}</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="{{ route('department-head.schedules.index', array_merge(request()->except('page'), ['status' => 'REJECTED'])) }}"
                    class="card shadow-sm border-0 h-100 summary-card text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="summary-icon bg-danger-subtle text-danger">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Rejected</p>
                            <div class="fs-4 fw-bold text-danger">{{ $rejectedCount }}</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form id="scheduleFilterForm" method="GET" action="{{ route('department-head.schedules.index') }}"
                    class="m-0" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3">
                            <label for="filterProgram" class="form-label small fw-semibold">Program</label>
                            <select id="filterProgram" name="program" class="form-select">
                                <option value="">All Programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}"
                                        {{ request('program') == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label for="filterAcademicYear" class="form-label small fw-semibold">Academic Year</label>
                            <select id="filterAcademicYear" name="academic_year_id" class="form-select">
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="filterSemester" class="form-label small fw-semibold">Semester</label>
                            <select id="filterSemester" name="semester" class="form-select">
                                <option value="">All Semesters</option>
                                <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>1st Semester
                                </option>
                                <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>2nd Semester
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="filterStatus" class="form-label small fw-semibold">Status</label>
                            <select id="filterStatus" name="status" class="form-select">
                                <option value="PENDING_APPROVAL" {{ request('status', 'PENDING_APPROVAL') == 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending</option>
                                <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>
                                    Approved</option>
                                <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>
                                    Rejected</option>
                                <option value="">All Statuses</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="filterSearch" class="form-label small fw-semibold">Search</label>
                            <input type="text" id="filterSearch" name="search" class="form-control"
                                placeholder="Program or block..." value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-12 d-flex align-items-center justify-content-end gap-2">
                            <button type="submit" class="btn btn-maroon">
                                <i class="fa-solid fa-search me-1"></i>Search
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearScheduleFilters"
                                title="Clear Filters">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedules by Academic Year / Semester / Program -->
        @if ($schedules->isEmpty())
            <!-- Empty State -->
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center">
                    <div class="empty-state-icon">
                        <i class="fa-regular fa-inbox text-muted"></i>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: #660000;">No schedules submitted yet</h5>
                    <p class="text-muted mb-0">Schedules from Program Heads will appear here for review.</p>
                    @if (request()->filled('search') || request()->filled('program') || request()->filled('academic_year_id') || request()->filled('semester') || (request()->filled('status') && request('status') !== 'PENDING_APPROVAL'))
                        <p class="text-muted small mt-2"><a href="{{ route('department-head.schedules.index') }}">Clear filters</a> to see all schedules.</p>
                    @endif
                </div>
            </div>
        @else
            @php
                $schedulesByYear = $schedules->groupBy('academic_year');
            @endphp
            <div class="accordion" id="scheduleAccordion">
                @foreach ($schedulesByYear as $academicYear => $yearSchedules)
                    @php
                        $yearId = 'year-' . $loop->index;
                        $schedulesBySemester = $yearSchedules->groupBy('semester');
                    @endphp
                    <div class="accordion-item shadow-sm mb-3 border-0 rounded-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed folder-header" type="button"
                                data-bs-toggle="collapse" data-bs-target="#{{ $yearId }}"
                                aria-expanded="false" aria-controls="{{ $yearId }}">
                                <i class="fa-solid fa-folder me-2 text-maroon"></i>
                                <span class="fw-semibold">{{ $academicYear }}</span>
                                <span class="badge bg-maroon ms-2">{{ $yearSchedules->count() }}</span>
                            </button>
                        </h2>
                        <div id="{{ $yearId }}" class="accordion-collapse collapse" data-bs-parent="#scheduleAccordion">
                            <div class="accordion-body p-0">
                                <div class="accordion" id="{{ $yearId }}-semesters">
                                    @foreach ($schedulesBySemester as $semester => $semesterSchedules)
                                        @php
                                            $semesterId = $yearId . '-semester-' . $loop->index;
                                            $schedulesByProgram = $semesterSchedules->groupBy(fn ($item) => $item->program?->program_name ?? 'Program');
                                        @endphp
                                        <div class="accordion-item border-0 rounded-0">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed folder-header level-two" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#{{ $semesterId }}"
                                                    aria-expanded="false" aria-controls="{{ $semesterId }}">
                                                    <i class="fa-solid fa-folder me-2 text-maroon"></i>
                                                    <span class="fw-semibold">Semester {{ $semester }}</span>
                                                    <span class="badge bg-secondary ms-2">{{ $semesterSchedules->count() }}</span>
                                                </button>
                                            </h2>
                                            <div id="{{ $semesterId }}" class="accordion-collapse collapse">
                                                <div class="accordion-body p-0">
                                                    <div class="accordion" id="{{ $semesterId }}-programs">
                                                        @foreach ($schedulesByProgram as $programName => $programSchedules)
                                                            @php
                                                                $programId = $semesterId . '-program-' . $loop->index;
                                                            @endphp
                                                            <div class="accordion-item border-0">
                                                                <h2 class="accordion-header">
                                                                    <button class="accordion-button collapsed folder-header level-three" type="button"
                                                                        data-bs-toggle="collapse" data-bs-target="#{{ $programId }}"
                                                                        aria-expanded="false" aria-controls="{{ $programId }}">
                                                                        <i class="fa-solid fa-folder-open me-2 text-maroon"></i>
                                                                        <span class="fw-semibold">{{ $programName }}</span>
                                                                        <span class="badge bg-light text-dark ms-2">{{ $programSchedules->count() }}</span>
                                                                    </button>
                                                                </h2>
                                                                <div id="{{ $programId }}" class="accordion-collapse collapse">
                                                                    <div class="accordion-body p-3">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-hover align-middle mb-0">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>Block / Section</th>
                                                                                        <th>Submitted By</th>
                                                                                        <th>Date Submitted</th>
                                                                                        <th>Status</th>
                                                                                        <th class="text-center">Actions</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach ($programSchedules as $schedule)
                                                                                        <tr>
                                                                                            <td class="fw-semibold">{{ $schedule->block ?? 'N/A' }}</td>
                                                                                            <td>{{ $schedule->creator?->full_name ?? '—' }}</td>
                                                                                            <td>
                                                                                                <small class="text-muted">
                                                                                                    {{ optional($schedule->submitted_at)->format('M d, Y g:i A') ?? '—' }}
                                                                                                </small>
                                                                                            </td>
                                                                                            <td>
                                                                                                @if ($schedule->status === \App\Models\Schedule::STATUS_PENDING_APPROVAL)
                                                                                                    <span class="badge bg-warning">Pending</span>
                                                                                                @elseif ($schedule->status === \App\Models\Schedule::STATUS_APPROVED)
                                                                                                    <span class="badge bg-success">Approved</span>
                                                                                                @elseif ($schedule->status === \App\Models\Schedule::STATUS_REJECTED)
                                                                                                    <span class="badge bg-danger">Rejected</span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                <div class="btn-group btn-group-sm" role="group">
                                                                                                    <a href="{{ route('department-head.schedules.show', $schedule) }}"
                                                                                                        class="btn btn-outline-primary" title="View details">
                                                                                                        <i class="fa-solid fa-eye"></i>
                                                                                                    </a>
                                                                                                    @if ($schedule->status === \App\Models\Schedule::STATUS_PENDING_APPROVAL)
                                                                                                        <button type="button" class="btn btn-outline-success approve-schedule-btn"
                                                                                                            data-schedule-id="{{ $schedule->id }}"
                                                                                                            title="Approve schedule">
                                                                                                            <i class="fa-solid fa-check"></i>
                                                                                                        </button>
                                                                                                        <button type="button" class="btn btn-outline-danger reject-schedule-btn"
                                                                                                            data-schedule-id="{{ $schedule->id }}"
                                                                                                            title="Reject schedule">
                                                                                                            <i class="fa-solid fa-times"></i>
                                                                                                        </button>
                                                                                                    @endif
                                                                                                </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Approve Schedule Modal -->
    <div class="modal fade" id="approveScheduleModal" tabindex="-1" aria-labelledby="approveScheduleLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="approveScheduleLabel">
                        <i class="fa-solid fa-circle-check me-2"></i>Approve Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="approveScheduleForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-3">Are you sure you want to approve this schedule?</p>
                        <div class="alert alert-info small mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i>The program head will be notified of the approval.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-check me-2"></i>Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Schedule Modal -->
    <div class="modal fade" id="rejectScheduleModal" tabindex="-1" aria-labelledby="rejectScheduleLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="rejectScheduleLabel">
                        <i class="fa-solid fa-circle-xmark me-2"></i>Reject Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="rejectScheduleForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-3">Please provide your reason for rejecting this schedule.</p>
                        <div class="mb-3">
                            <label for="rejectRemarks" class="form-label">Reason for Rejection</label>
                            <textarea id="rejectRemarks" name="review_remarks" class="form-control" rows="4"
                                placeholder="Enter your remarks..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-times me-2"></i>Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-maroon {
            background-color: #660000 !important;
        }

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

        .badge.bg-maroon {
            background-color: #660000 !important;
        }

        .summary-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .summary-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .folder-header {
            background-color: #f3f4f6;
            color: #333;
            border-radius: 0.75rem;
        }

        .folder-header.level-two {
            background-color: #f7f7f7;
        }

        .folder-header.level-three {
            background-color: #fafafa;
        }

        .accordion-item {
            overflow: hidden;
        }

        .empty-state-icon {
            background: rgba(102, 0, 0, 0.05);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state-icon i {
            font-size: 3rem;
        }

        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23660000' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23660000' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        @media (max-width: 768px) {
            .btn-group-sm {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-group-sm>.btn {
                width: 100%;
            }

            .folder-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentScheduleId = null;

            // Clear filters
            document.getElementById('clearScheduleFilters')?.addEventListener('click', function () {
                document.querySelectorAll('#scheduleFilterForm input, #scheduleFilterForm select').forEach(el => {
                    if (el.name !== 'status') el.value = '';
                    if (el.name === 'status') el.value = 'PENDING_APPROVAL';
                });
                document.getElementById('scheduleFilterForm').submit();
            });

            // Approve button click handlers
            document.querySelectorAll('.approve-schedule-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentScheduleId = this.dataset.scheduleId;
                    const form = document.getElementById('approveScheduleForm');
                    form.action = `/department-head/schedules/${currentScheduleId}/approve`;
                    const modal = new bootstrap.Modal(document.getElementById('approveScheduleModal'));
                    modal.show();
                });
            });

            // Reject button click handlers
            document.querySelectorAll('.reject-schedule-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentScheduleId = this.dataset.scheduleId;
                    const form = document.getElementById('rejectScheduleForm');
                    form.action = `/department-head/schedules/${currentScheduleId}/reject`;
                    document.getElementById('rejectRemarks').value = '';
                    const modal = new bootstrap.Modal(document.getElementById('rejectScheduleModal'));
                    modal.show();
                });
            });
        });
    </script>
@endsection
