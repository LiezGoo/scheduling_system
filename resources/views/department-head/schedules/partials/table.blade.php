@if ($schedules->isEmpty())
    <div class="empty-state">
        <i class="fas fa-calendar-times empty-state-icon"></i>
        <h2 class="empty-state-title">No Schedules Found</h2>
        <p class="empty-state-text">No schedules match the selected filters.</p>
    </div>
@else
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
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('department-head.schedules.show', $schedule) }}" class="btn btn-sm btn-outline-secondary" title="View" aria-label="View Schedule">
                                    <i class="fa-regular fa-eye" style="font-size: 14px;"></i>
                                </a>
                                <a href="{{ route('department-head.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-warning" title="Edit" aria-label="Edit Schedule">
                                    <i class="fa-solid fa-pencil" style="font-size: 14px;"></i>
                                </a>
                                <form id="delete-form-{{ $schedule->id }}" method="POST" action="{{ route('department-head.schedules.destroy', $schedule) }}" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger schedule-delete-btn"
                                    title="Delete"
                                    aria-label="Delete Schedule"
                                    data-id="{{ $schedule->id }}"
                                    data-message="Delete this schedule?">
                                    <i class="fa-solid fa-trash" style="font-size: 14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($schedules && $schedules->count() > 0)
        <x-pagination.footer :paginator="$schedules" />
    @endif
@endif
