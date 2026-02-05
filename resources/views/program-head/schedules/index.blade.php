@extends('layouts.app')

@section('page-title', 'Schedule Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-2">Schedule Management</h2>
                <p class="text-muted mb-0">Create and manage class schedules for your program</p>
            </div>
            <div>
                <a href="{{ route('program-head.schedules.create') }}" class="btn btn-maroon">
                    <i class="fa-solid fa-plus me-2"></i>Create New Schedule
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('program-head.schedules.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label">Status</label>
                        <select class="form-select" id="filterStatus" name="status">
                            <option value="">All Statuses</option>
                            <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                            <option value="PENDING_APPROVAL"
                                {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending
                                Approval
                            </option>
                            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved
                            </option>
                            <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterAcademicYear" class="form-label">Academic Year</label>
                        <input type="text" class="form-control" id="filterAcademicYear" name="academic_year"
                            value="{{ request('academic_year') }}" placeholder="e.g., 2025-2026">
                    </div>
                    <div class="col-md-2">
                        <label for="filterSemester" class="form-label">Semester</label>
                        <select class="form-select" id="filterSemester" name="semester">
                            <option value="">All</option>
                            <option value="1st Semester" {{ request('semester') === '1st Semester' ? 'selected' : '' }}>1st
                                Semester</option>
                            <option value="2nd Semester" {{ request('semester') === '2nd Semester' ? 'selected' : '' }}>2nd
                                Semester</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filterYearLevel" class="form-label">Year Level</label>
                        <select class="form-select" id="filterYearLevel" name="year_level">
                            <option value="">All</option>
                            <option value="1" {{ request('year_level') == '1' ? 'selected' : '' }}>1st Year</option>
                            <option value="2" {{ request('year_level') == '2' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3" {{ request('year_level') == '3' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4" {{ request('year_level') == '4' ? 'selected' : '' }}>4th Year</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedules List -->
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Year Level</th>
                                    <th>Block</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->academic_year }}</td>
                                        <td>{{ $schedule->semester }}</td>
                                        <td>{{ $schedule->year_level }}</td>
                                        <td>{{ $schedule->block ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $schedule->getStatusBadgeClass() }}">
                                                {{ $schedule->status_label }}
                                            </span>
                                            @if ($schedule->isRejected() && $schedule->review_remarks)
                                                <div class="text-danger small mt-1">{{ $schedule->review_remarks }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $schedule->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('program-head.schedules.show', $schedule) }}"
                                                    class="btn btn-outline-primary" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                @if ($schedule->isDraft() || $schedule->isRejected())
                                                    <a href="{{ route('program-head.schedules.edit', $schedule) }}"
                                                        class="btn btn-outline-warning" title="Edit">
                                                        <i class="fa-solid fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success"
                                                        title="Submit for Approval" data-bs-toggle="modal"
                                                        data-bs-target="#submitScheduleModal"
                                                        data-action="{{ route('program-head.schedules.submit', $schedule) }}"
                                                        data-label="{{ $schedule->academic_year }} • {{ $schedule->semester }} • Year {{ $schedule->year_level }}">
                                                        <i class="fa-solid fa-paper-plane"></i>
                                                    </button>
                                                    <form
                                                        action="{{ route('program-head.schedules.destroy', $schedule) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this schedule?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger"
                                                            title="Delete">
                                                            <i class="fa-solid fa-trash"></i>
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
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Showing {{ $schedules->firstItem() }} to {{ $schedules->lastItem() }} of
                            {{ $schedules->total() }} schedules
                        </div>
                        <div>
                            {{ $schedules->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No Schedules Found</h5>
                        <p class="text-muted">Create your first schedule to get started.</p>
                        <a href="{{ route('program-head.schedules.create') }}" class="btn btn-maroon">
                            <i class="fa-solid fa-plus me-2"></i>Create Schedule
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div class="modal fade" id="submitScheduleModal" tabindex="-1" aria-labelledby="submitScheduleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submitScheduleLabel">Submit Schedule for Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">You're about to submit this schedule for department head approval:</p>
                    <div class="fw-semibold text-maroon" id="submitScheduleInfo">Schedule</div>
                    <p class="text-muted small mt-2 mb-0">Editing will be locked while awaiting approval.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="submitScheduleForm" method="POST" action="#" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-maroon">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('submitScheduleModal');
            if (!modal) return;

            const form = document.getElementById('submitScheduleForm');
            const info = document.getElementById('submitScheduleInfo');

            modal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                if (!button) return;
                const action = button.getAttribute('data-action');
                const label = button.getAttribute('data-label');
                if (form) form.action = action;
                if (info) info.textContent = label || 'Schedule';
            });
        })();
    </script>
@endpush
