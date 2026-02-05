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
        <td>{{ $load->program_name ?? 'N/A' }}</td>
        <td class="font-monospace">{{ $load->subject_code }}</td>
        <td>{{ Str::limit($load->subject_name, 30) }}</td>
        <td class="text-center">
            <span class="badge bg-primary">{{ $load->lecture_hours ?? 0 }}</span>
        </td>
        <td class="text-center">
            <span class="badge bg-success">{{ $load->lab_hours ?? 0 }}</span>
        </td>
        <td class="text-center">
            <span class="badge bg-warning text-dark">{{ number_format($load->computed_units ?? 0, 2) }}</span>
        </td>
        <td class="text-center">
            <div class="btn-group" role="group" aria-label="Faculty Load Actions">
                <button type="button" class="btn btn-sm btn-outline-primary" title="View Details" data-action="view"
                    data-id="{{ $load->id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit" data-action="edit"
                    data-id="{{ $load->id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Remove" data-action="remove"
                    data-id="{{ $load->id }}" data-bs-toggle="tooltip">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    @empty
        <tr>
            <td colspan="10" class="text-center py-4">
                <i class="fa-solid fa-chalkboard-user text-muted fa-3x mb-3"></i>
                <p class="text-muted mb-0">No faculty load assignments found</p>
            </td>
        </tr>
    @endforelse
