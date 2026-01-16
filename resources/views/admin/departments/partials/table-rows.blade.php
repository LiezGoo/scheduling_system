@forelse($departments as $department)
    <tr>
        <td class="fw-semibold">{{ $department->department_code }}</td>
        <td>{{ $department->department_name }}</td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-warning edit-department-btn"
                    data-department-id="{{ $department->id }}" data-department-code="{{ $department->department_code }}"
                    data-department-name="{{ $department->department_name }}" title="Edit" aria-label="Edit Department">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-department-btn"
                    data-department-id="{{ $department->id }}" data-department-name="{{ $department->department_name }}"
                    title="Delete" aria-label="Delete Department">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="text-center py-4">
            <i class="fa-solid fa-building text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-3">No departments found</p>
        </td>
    </tr>
@endforelse
