@extends('layouts.app')

@section('page-title', 'Review Schedule')

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
            <div class="card-header bg-light">
                <strong>Schedule Items</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Instructor</th>
                                <th>Room</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedule->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->subject?->subject_code }}</div>
                                        <div class="text-muted small">{{ $item->subject?->subject_name }}</div>
                                    </td>
                                    <td>{{ $item->instructor?->full_name ?? '—' }}</td>
                                    <td>{{ $item->room?->room_code ?? '—' }}</td>
                                    <td>{{ $item->day_of_week }}</td>
                                    <td>{{ $item->start_time }} - {{ $item->end_time }}</td>
                                    <td>{{ $item->section ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No schedule items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
