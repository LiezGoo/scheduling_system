@forelse ($facultyLoads as $load)
    <tr>
        <td>
            <span class="badge bg-light text-dark font-monospace">{{ $load->school_id }}</span>
        </td>
        <td class="fw-semibold">{{ $load->full_name }}</td>
        <td>
            <span class="badge bg-info">
                @switch($load->role)
                    @case('admin')
                        Administrator
                    @break

                    @case('instructor')
                        Instructor
                    @break

                    @case('program_head')
                        Program Head
                    @break

                    @case('department_head')
                        Department Head
                    @break

                    @case('student')
                        Student
                    @break

                    @default
                        {{ ucfirst(str_replace('_', ' ', $load->role)) }}
                @endswitch
            </span>
        </td>
        <td>{{ $load->department_name ?? 'N/A' }}</td>
        <td>{{ $load->program_name ?? 'N/A' }}</td>
        <td>{{ $load->academic_year_name ?? 'N/A' }}</td>
        <td class="text-center">{{ $load->semester ?? '—' }}</td>
        <td class="text-center">{{ $load->year_level ?? '—' }}</td>
        <td class="text-center">
            <span class="badge bg-light text-dark">{{ $load->total_subjects ?? 0 }}</span>
        </td>
        <td>
            <div class="subjects-list" title="{{ $load->subject_names ?? '' }}" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                @php
                    // Split by comma with optional space (handles both SQLite and MySQL formats)
                    $subjects = collect(preg_split('/,\s*/', $load->subject_names ?? ''))
                        ->filter(fn($s) => !empty($s))
                        ->map(fn($s) => trim($s))
                        ->values()
                        ->toArray();
                @endphp
                @if (count($subjects) > 0)
                    <span class="text-muted small">
                        {{ implode(', ', array_slice($subjects, 0, 2)) }}@if (count($subjects) > 2) +{{ count($subjects) - 2 }}@endif
                    </span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-primary">{{ $load->total_lec_hours ?? 0 }}</span>
        </td>
        <td class="text-center">
            <span class="badge bg-success">{{ $load->total_lab_hours ?? 0 }}</span>
        </td>
        <td class="text-center">
            <span class="badge bg-warning text-dark">{{ $load->total_teaching_hours ?? 0 }}</span>
        </td>
        <td class="text-center">
            <div class="btn-group" role="group" aria-label="Faculty Load Actions">
                <button type="button" class="btn btn-sm btn-outline-primary" title="View Details" data-action="view"
                    data-id="{{ $load->load_id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit" data-action="edit"
                    data-id="{{ $load->load_id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Remove" data-action="remove"
                    data-id="{{ $load->load_id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="16" class="text-center py-4">
            <i class="fa-solid fa-chalkboard-user text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-0">No faculty load assignments found</p>
        </td>
    </tr>
@endforelse
