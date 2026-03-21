@extends('layouts.app')

@section('page-title', 'Review Schedule')

@push('styles')
<style>
    :root {
        --schedule-border: #e9ecef;
        --lecture-blue: #0d6efd;
        --lecture-blue-dark: #0a58ca;
        --lab-green: #28a745;
        --lab-green-dark: #1e7e34;
    }

    .schedule-grid-container {
        background: #fff;
        overflow: visible;
    }

    .schedule-grid-wrapper {
        position: relative;
    }

    .schedule-grid-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .schedule-grid-table th {
        background: #f8f9fa;
        border: 1px solid var(--schedule-border);
        color: #495057;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 12px 8px;
        text-align: center;
        text-transform: uppercase;
    }

    .schedule-time-column {
        width: 88px;
        background: #f8f9fa;
        border-right: 2px solid var(--schedule-border);
        color: #6c757d;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
    }

    .schedule-slot {
        height: 80px;
        border: 1px solid var(--schedule-border);
        padding: 0;
        vertical-align: top;
    }

    .schedule-overlay {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 2;
    }

    .schedule-card {
        position: absolute;
        border-radius: 6px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        box-sizing: border-box;
        color: #fff;
        display: flex;
        flex-direction: column;
        font-size: 0.65rem;
        overflow: hidden;
        padding: 6px 8px;
        pointer-events: auto;
        z-index: 3;
    }

    .schedule-card.lecture {
        background: linear-gradient(135deg, var(--lecture-blue), var(--lecture-blue-dark));
    }

    .schedule-card.lab {
        background: linear-gradient(135deg, var(--lab-green), var(--lab-green-dark));
    }

    .schedule-table-view {
        overflow-x: auto;
    }

    .schedule-type-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.68rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
    }

    .schedule-type-badge.lecture {
        background: rgba(13, 110, 253, 0.12);
        border: 1px solid rgba(13, 110, 253, 0.2);
        color: var(--lecture-blue-dark);
    }

    .schedule-type-badge.lab {
        background: rgba(40, 167, 69, 0.12);
        border: 1px solid rgba(40, 167, 69, 0.2);
        color: var(--lab-green-dark);
    }
</style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-2">Schedule Review</h2>
                <p class="text-muted mb-0">Review schedule details before approval.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $schedule->getStatusBadgeClass() }}">{{ $schedule->status_label }}</span>
                <a href="{{ route('department-head.schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Program</div>
                        <div class="fw-semibold">{{ $schedule->program?->program_name }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Academic Year</div>
                        <div class="fw-semibold">{{ $schedule->academic_year }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Semester</div>
                        <div class="fw-semibold">{{ $schedule->semester }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Year Level</div>
                        <div class="fw-semibold">{{ $schedule->year_level }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Block</div>
                        <div class="fw-semibold">{{ $schedule->block ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Submitted By</div>
                        <div class="fw-semibold">{{ $schedule->creator?->full_name ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Submitted At</div>
                        <div class="fw-semibold">{{ optional($schedule->submitted_at)->format('M d, Y h:i A') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>Schedule Preview</strong>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-primary active" id="showGridViewBtn">Grid</button>
                    <button type="button" class="btn btn-outline-secondary" id="showTableViewBtn">Table</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="showGridView" class="schedule-grid-container">
                    <div class="schedule-grid-wrapper">
                        <table class="schedule-grid-table" id="showScheduleGridTable">
                            <thead>
                                <tr>
                                    <th class="schedule-time-column">Time</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                    <th>Saturday</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'] as $time)
                                    <tr>
                                        <td class="schedule-time-column">{{ $time }}</td>
                                        @for ($day = 0; $day < 6; $day++)
                                            <td class="schedule-slot"></td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div id="showScheduleOverlay" class="schedule-overlay"></div>
                    </div>
                </div>

                <div id="showTableView" class="schedule-table-view d-none">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Instructor</th>
                                    <th>Room</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Section</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($schedule->items as $item)
                                    @php
                                        $roomType = strtolower((string) ($item->room?->room_type ?? ''));
                                        $hasLecture = (float) ($item->subject?->lecture_hours ?? 0) > 0;
                                        $hasLab = (float) ($item->subject?->lab_hours ?? 0) > 0;
                                        $classType = match (true) {
                                            $hasLab && !$hasLecture => 'Laboratory',
                                            $hasLecture && !$hasLab => 'Lecture',
                                            $hasLecture && $hasLab && str_contains($roomType, 'lab') => 'Laboratory',
                                            default => 'Lecture',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->subject?->subject_code }}</div>
                                            <div class="text-muted small">{{ $item->subject?->subject_name }}</div>
                                        </td>
                                        <td>{{ $item->instructor?->full_name ?? '—' }}</td>
                                        <td>{{ $item->room?->room_code ?? '—' }}</td>
                                        <td>{{ $item->day_of_week }}</td>
                                        <td>{{ $item->start_time }} - {{ $item->end_time }}</td>
                                        <td>
                                            <span class="schedule-type-badge {{ $classType === 'Laboratory' ? 'lab' : 'lecture' }}">
                                                {{ $classType }}
                                            </span>
                                        </td>
                                        <td>{{ $item->section ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No schedule items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="text-muted small">
                        Approve to publish the schedule or reject with remarks for revision.
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('department-head.schedules.approve', $schedule) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-check me-2"></i>Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#rejectScheduleModal">
                            <i class="fa-solid fa-xmark me-2"></i>Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectScheduleModal" tabindex="-1" aria-labelledby="rejectScheduleLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectScheduleLabel">Reject Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('department-head.schedules.reject', $schedule) }}">
                        @csrf
                        <div class="modal-body">
                            <label for="review_remarks" class="form-label">Remarks (required)</label>
                            <textarea class="form-control" id="review_remarks" name="review_remarks" rows="4" required
                                placeholder="Provide review remarks for rejection."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@php
    $scheduleItemsForPreview = $schedule->items
        ->sortBy([
            ['day_of_week', 'asc'],
            ['start_time', 'asc'],
        ])
        ->values()
        ->map(function ($item) {
            $roomType = strtolower((string) ($item->room?->room_type ?? ''));
            $hasLecture = (float) ($item->subject?->lecture_hours ?? 0) > 0;
            $hasLab = (float) ($item->subject?->lab_hours ?? 0) > 0;

            $classType = match (true) {
                $hasLab && !$hasLecture => 'Laboratory',
                $hasLecture && !$hasLab => 'Lecture',
                $hasLecture && $hasLab && str_contains($roomType, 'lab') => 'Laboratory',
                default => 'Lecture',
            };

            return [
                'subject_code' => $item->subject?->subject_code ?? 'N/A',
                'subject_name' => $item->subject?->subject_name ?? 'N/A',
                'instructor_name' => $item->instructor?->full_name ?? '—',
                'room_name' => $item->room?->room_code ?? '—',
                'room_type' => $item->room?->room_type ?? '',
                'day_of_week' => $item->day_of_week ?? 'Monday',
                'start_time' => $item->getRawOriginal('start_time') ?? '08:00',
                'end_time' => $item->getRawOriginal('end_time') ?? '09:00',
                'class_type' => $classType,
            ];
        });
@endphp
<script>
    (() => {
        const items = @json($scheduleItemsForPreview);

        const gridView = document.getElementById('showGridView');
        const tableView = document.getElementById('showTableView');
        const gridBtn = document.getElementById('showGridViewBtn');
        const tableBtn = document.getElementById('showTableViewBtn');
        const overlay = document.getElementById('showScheduleOverlay');
        const table = document.getElementById('showScheduleGridTable');

        const GRID_START_HOUR = 7;
        const ROW_HEIGHT = 80;
        const ITEM_INSET = 3;
        const DAY_MAP = {
            monday: 0,
            tuesday: 1,
            wednesday: 2,
            thursday: 3,
            friday: 4,
            saturday: 5,
        };

        function setView(view) {
            const showGrid = view === 'grid';
            gridView.classList.toggle('d-none', !showGrid);
            tableView.classList.toggle('d-none', showGrid);
            gridBtn.classList.toggle('btn-primary', showGrid);
            gridBtn.classList.toggle('btn-outline-secondary', !showGrid);
            tableBtn.classList.toggle('btn-primary', !showGrid);
            tableBtn.classList.toggle('btn-outline-secondary', showGrid);
            gridBtn.classList.toggle('active', showGrid);
            tableBtn.classList.toggle('active', !showGrid);
        }

        function parseTime(value) {
            const match = String(value || '').match(/(\d{1,2}):(\d{2})/);
            if (!match) return null;
            return { hour: parseInt(match[1], 10), minute: parseInt(match[2], 10) };
        }

        function formatTime(value) {
            const match = String(value || '').match(/(\d{1,2}:\d{2})/);
            return match ? match[1] : value;
        }

        function renderGrid() {
            if (!overlay || !table) return;

            overlay.innerHTML = '';

            const wrapper = table.closest('.schedule-grid-wrapper');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            if (!wrapper || rows.length === 0) return;

            const wrapperRect = wrapper.getBoundingClientRect();

            items.forEach((item) => {
                const dayIndex = DAY_MAP[String(item.day_of_week || '').toLowerCase()];
                const start = parseTime(item.start_time);
                const end = parseTime(item.end_time);

                if (dayIndex === undefined || !start || !end) return;

                const rowIndex = start.hour - GRID_START_HOUR;
                const row = rows[rowIndex];
                const cell = row?.children?.[dayIndex + 1];
                if (!cell) return;

                const cellRect = cell.getBoundingClientRect();
                const rawDurationMinutes = Math.max(30, ((end.hour * 60) + end.minute) - ((start.hour * 60) + start.minute));
                const displayDurationHours = Math.max(1, Math.ceil(rawDurationMinutes / 60));
                const rowHeight = cellRect.height || ROW_HEIGHT;
                const top = (cellRect.top - wrapperRect.top) + ITEM_INSET;
                const height = Math.max(24, (displayDurationHours * rowHeight) - ITEM_INSET);
                const left = (cellRect.left - wrapperRect.left) + ITEM_INSET;
                const width = cellRect.width - (ITEM_INSET * 2);
                const isLab = String(item.class_type || '').toLowerCase() === 'laboratory';

                const card = document.createElement('div');
                card.className = `schedule-card ${isLab ? 'lab' : 'lecture'}`;
                card.style.cssText = `top:${top}px;left:${left}px;width:${width}px;height:${height}px;`;
                card.innerHTML = `
                    <div class="fw-bold" style="font-size:0.72rem; line-height:1.2; margin-bottom:2px;">${item.subject_code} (${item.class_type})</div>
                    <div style="opacity:0.9; font-size:0.62rem; margin-bottom:2px;">${item.instructor_name}</div>
                    <div style="opacity:0.82; font-size:0.58rem;">${item.room_name}</div>
                    <div style="margin-top:auto; padding-top:3px; border-top:1px solid rgba(255,255,255,0.25); font-size:0.52rem; opacity:0.78;">${formatTime(item.start_time)} - ${formatTime(item.end_time)}</div>
                `;
                overlay.appendChild(card);
            });
        }

        gridBtn?.addEventListener('click', () => setView('grid'));
        tableBtn?.addEventListener('click', () => setView('table'));
        window.addEventListener('resize', renderGrid);

        setView('grid');
        renderGrid();
    })();
</script>
@endpush
