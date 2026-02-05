@extends('layouts.app')

@section('page-title', 'Schedule Approval')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="h4 mb-2">Schedules Pending Approval</h2>
                <p class="text-muted mb-0">Review schedules submitted by programs under your department.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($schedules->isEmpty())
            <div class="text-center py-5">
                <i class="fa-regular fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No schedules awaiting approval</h5>
                <p class="text-muted">Submitted schedules will appear here for review.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach ($schedules as $programId => $programSchedules)
                    @php
                        $program = $programSchedules->first()?->program;
                    @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-folder-open text-warning"></i>
                                    <span class="fw-semibold">{{ $program?->program_name ?? 'Program' }}</span>
                                </div>
                                <span class="badge bg-light text-dark">{{ $programSchedules->count() }} pending</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Submitted</th>
                                                <th>Academic Year</th>
                                                <th>Semester</th>
                                                <th>Year Level</th>
                                                <th>Block</th>
                                                <th>Program Head</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($programSchedules as $schedule)
                                                <tr>
                                                    <td>{{ optional($schedule->submitted_at)->format('M d, Y') ?? '—' }}
                                                    </td>
                                                    <td>{{ $schedule->academic_year }}</td>
                                                    <td>{{ $schedule->semester }}</td>
                                                    <td>{{ $schedule->year_level }}</td>
                                                    <td>{{ $schedule->block ?? 'N/A' }}</td>
                                                    <td>{{ $schedule->creator?->full_name ?? '—' }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('department-head.schedules.show', $schedule) }}"
                                                            class="btn btn-sm btn-outline-primary">
                                                            Review
                                                        </a>
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
        @endif
    </div>
@endsection
