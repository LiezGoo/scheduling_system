@extends('layouts.app')

@section('page-title', 'Edit Schedule')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1">Edit Schedule</h2>
                <p class="text-muted mb-0">
                    {{ $schedule->program?->program_name ?? 'Program' }} | {{ $schedule->academic_year }} | {{ $schedule->semester }} | {{ $schedule->year_level }}
                    Year | {{ $schedule->block ?? 'N/A' }}
                </p>
            </div>
            <a href="{{ route('department-head.schedules.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Schedules
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm">
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
                                <th>Start</th>
                                <th>End</th>
                                <th>Section</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <form method="POST"
                                        action="{{ route('department-head.schedules.items.update', ['schedule' => $schedule, 'item' => $item]) }}">
                                        @csrf
                                        @method('PUT')

                                        <td>
                                            <div class="fw-semibold">{{ $item->subject?->subject_code ?? 'N/A' }}</div>
                                            <div class="text-muted small">{{ $item->subject?->subject_name ?? 'Unknown Subject' }}</div>
                                        </td>

                                        <td>
                                            <select name="instructor_id" class="form-select form-select-sm">
                                                <option value="">Unassigned</option>
                                                @foreach ($instructors as $instructor)
                                                    <option value="{{ $instructor->id }}"
                                                        {{ (string) old('instructor_id', $item->instructor_id) === (string) $instructor->id ? 'selected' : '' }}>
                                                        {{ $instructor->full_name ?? ($instructor->first_name . ' ' . $instructor->last_name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="room_id" class="form-select form-select-sm">
                                                <option value="">Unassigned</option>
                                                @foreach ($rooms as $room)
                                                    <option value="{{ $room->id }}"
                                                        {{ (string) old('room_id', $item->room_id) === (string) $room->id ? 'selected' : '' }}>
                                                        {{ $room->room_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="day" class="form-select form-select-sm" required>
                                                @php($selectedDay = old('day', $item->day_of_week))
                                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" {{ $selectedDay === $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <input type="time" name="start_time" class="form-control form-control-sm"
                                                value="{{ old('start_time', \Illuminate\Support\Str::of((string) $item->start_time)->substr(0, 5)) }}" required>
                                        </td>

                                        <td>
                                            <input type="time" name="end_time" class="form-control form-control-sm"
                                                value="{{ old('end_time', \Illuminate\Support\Str::of((string) $item->end_time)->substr(0, 5)) }}" required>
                                        </td>

                                        <td>{{ $item->section ?? 'N/A' }}</td>

                                        <td class="text-center">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-save me-1"></i>Save
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No schedule items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
