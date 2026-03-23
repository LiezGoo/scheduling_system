@extends('layouts.app')

@section('page-title', 'View Schedule')

@push('styles')
    <style>
        .review-timetable-wrapper {
            overflow-x: auto;
        }

        .review-timetable {
            width: 100%;
            min-width: 980px;
            table-layout: fixed;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .review-timetable th,
        .review-timetable td {
            vertical-align: middle;
            border: 1px solid #d8dce0;
            padding: 10px 8px;
        }

        .review-timetable thead th {
            background: #edf1f5;
            color: #1f2937;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
            text-align: center;
        }

        .review-time-col {
            width: 130px;
            background: #f8f9fa;
            color: #4b5563;
            font-size: 0.74rem;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .review-slot-empty {
            background: #fcfcfd;
        }

        .review-slot-empty .placeholder {
            color: #c0c7cf;
            font-size: 0.68rem;
            letter-spacing: 0.3px;
        }

        .schedule-card {
            color: #fff;
            border-radius: 8px;
            padding: 8px;
            font-size: 12px;
            margin: 4px 0;
            text-align: left;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .schedule-card-lecture {
            background: #0d6efd;
        }

        .schedule-card-lab {
            background: #198754;
        }

        .schedule-card-nstp {
            background: #fd7e14;
        }

        .schedule-card .subject-code {
            font-weight: 700;
            font-size: 0.78rem;
            line-height: 1.2;
        }

        .schedule-card .subject-name,
        .schedule-card .meta {
            display: block;
            line-height: 1.25;
            opacity: 0.95;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="text-muted mb-0">View schedule details and submit adjustment requests if needed.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $schedule->getStatusBadgeClass() }}">{{ $schedule->status_label }}</span>
                <a href="{{ route('program-head.schedules.index') }}" class="btn btn-outline-secondary">
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

        <!-- Schedule Details Card -->
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
                        <div class="fw-semibold">{{ optional($schedule->submitted_at)->format('M d, Y h:i A') ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Fitness Score</div>
                        <div class="fw-semibold">
                            @if ($schedule->fitness_score)
                                {{ number_format($schedule->fitness_score, 2) }}
                            @else
                                No data
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $timeSlots = [
                '07:00',
                '08:00',
                '09:00',
                '10:00',
                '11:00',
                '12:00',
                '13:00',
                '14:00',
                '15:00',
                '16:00',
                '17:00',
            ];

            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        @endphp

        <!-- Timetable Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <strong>Weekly Timetable</strong>
            </div>
            <div class="card-body p-0">
                @if ($schedule->items->isEmpty())
                    <div class="text-center text-muted py-4">No schedule items found.</div>
                @else
                    <div class="review-timetable-wrapper">
                        <table class="review-timetable table text-center align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    @foreach ($days as $day)
                                        <th>{{ strtoupper(substr($day, 0, 3)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timeSlots as $time)
                                    <tr>
                                        <td class="review-time-col">
                                            <strong>
                                                {{ $time }} - {{ \Carbon\Carbon::createFromFormat('H:i', $time)->addHour()->format('H:i') }}
                                            </strong>
                                        </td>

                                        @foreach ($days as $day)
                                            <td class="review-slot-empty">
                                                @if (isset($scheduleGrid[$day][$time]))
                                                    @foreach ($scheduleGrid[$day][$time] as $item)
                                                        @php
                                                            $subjectName = strtolower((string) ($item->subject?->subject_name ?? ''));
                                                            $subjectCode = strtolower((string) ($item->subject?->subject_code ?? ''));
                                                            $isNstp = str_contains($subjectName, 'nstp') || str_contains($subjectCode, 'nstp');
                                                            $isLab = str_contains($subjectName, 'lab')
                                                                || str_contains(strtolower((string) ($item->room?->room_name ?? '')), 'lab')
                                                                || str_contains(strtolower((string) ($item->room?->room_code ?? '')), 'lab');

                                                            $cardClass = $isNstp ? 'schedule-card-nstp' : ($isLab ? 'schedule-card-lab' : 'schedule-card-lecture');
                                                        @endphp

                                                        <div class="schedule-card {{ $cardClass }}"
                                                            title="{{ $item->subject?->subject_code ?? 'N/A' }} | {{ $item->subject?->subject_name ?? 'Unknown Subject' }} | {{ optional($item->start_time)->format('H:i') ?? substr((string) $item->start_time, 0, 5) }}-{{ optional($item->end_time)->format('H:i') ?? substr((string) $item->end_time, 0, 5) }} | {{ $item->room?->room_code ?? 'TBA' }} | {{ $item->instructor?->full_name ?? 'TBA' }}">
                                                            <span class="subject-code">{{ $item->subject?->subject_code ?? 'N/A' }}</span>
                                                            <span class="subject-name">{{ $item->subject?->subject_name ?? 'Unknown Subject' }}</span>
                                                            <span class="meta">{{ $item->instructor?->full_name ?? 'TBA' }}</span>
                                                            <span class="meta">{{ $item->room?->room_code ?? 'TBA' }}</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <span class="placeholder">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Adjustment Request Section -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-2">Request Schedule Adjustment</h6>
                        <p class="text-muted small mb-0">
                            Submit an adjustment request if you would like any changes to this schedule. The department head will review your request.
                        </p>
                    </div>
                    @if ($schedule->status === \App\Models\Schedule::STATUS_FINALIZED)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustmentRequestModal">
                            <i class="fa-solid fa-plus me-2"></i>Submit Request
                        </button>
                    @else
                        <span class="badge bg-secondary">
                            Schedule not available for adjustments (Status: {{ $schedule->status_label }})
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Adjustment Request Modal -->
        @if ($schedule->status === \App\Models\Schedule::STATUS_FINALIZED)
            <div class="modal fade" id="adjustmentRequestModal" tabindex="-1" aria-labelledby="adjustmentRequestLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="adjustmentRequestLabel">Submit Schedule Adjustment Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('program-head.schedules.adjustments.store', $schedule) }}">
                            @csrf
                            <div class="modal-body">
                                <label for="adjustment_reason" class="form-label">Reason for Adjustment (required)</label>
                                <textarea class="form-control @error('adjustment_reason') is-invalid @enderror" 
                                    id="adjustment_reason" name="adjustment_reason" rows="4" required
                                    placeholder="Describe the changes you would like to make to this schedule."></textarea>
                                @error('adjustment_reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror

                                <label for="adjustment_details" class="form-label mt-3">Additional Details (optional)</label>
                                <textarea class="form-control @error('adjustment_details') is-invalid @enderror" 
                                    id="adjustment_details" name="adjustment_details" rows="3"
                                    placeholder="Provide any additional context or specific items affected."></textarea>
                                @error('adjustment_details')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
