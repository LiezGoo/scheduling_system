@forelse ($subjects as $subject)
    <tr>
        <td><strong>{{ $subject->subject_code }}</strong></td>
        <td>{{ $subject->subject_name }}</td>
        <td>{{ $subject->department->department_name ?? 'N/A' }}</td>
        <td class="text-center">{{ $subject->units }}</td>
        <td class="text-center">{{ $subject->lecture_hours }}</td>
        <td class="text-center">{{ $subject->lab_hours }}</td>
        <td>{{ $subject->year_level_label }}</td>
        <td>{{ $subject->semester_label }}</td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('admin.subjects.show', $subject) }}" class="btn btn-sm btn-outline-secondary"
                    title="View" aria-label="View Subject Details">
                    <i class="fa-regular fa-eye"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-warning edit-subject-btn"
                    data-subject-id="{{ $subject->id }}" data-subject-code="{{ $subject->subject_code }}"
                    data-subject-name="{{ $subject->subject_name }}"
                    data-department-id="{{ $subject->department_id }}" data-units="{{ $subject->units }}"
                    data-lecture-hours="{{ $subject->lecture_hours }}" data-lab-hours="{{ $subject->lab_hours }}"
                    data-year-level="{{ $subject->year_level }}" data-semester="{{ $subject->semester }}"
                    title="Edit" aria-label="Edit Subject">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-subject-btn"
                    data-subject-id="{{ $subject->id }}" data-subject-name="{{ $subject->subject_name }}"
                    title="Delete" aria-label="Delete Subject">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center py-4">
            <i class="fa-solid fa-book text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-0">No subjects found</p>
        </td>
    </tr>
@endforelse
