@forelse($programs ?? [] as $program)
    <tr data-program-id="{{ $program->id }}">
        <td class="fw-semibold">{{ $program->code }}</td>
        <td>{{ $program->name }}</td>
        <td>{{ $program->department->name ?? ($program->department_name ?? '—') }}</td>
        <td>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary view-program-btn"
                    data-program-id="{{ $program->id }}" title="View Details" aria-label="View Program Details">
                    <i class="fa-regular fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary edit-program-btn"
                    data-program-id="{{ $program->id }}" title="Edit Program" aria-label="Edit Program">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-program-btn"
                    data-program-id="{{ $program->id }}" data-program-name="{{ $program->name }}"
                    title="Delete Program" aria-label="Delete Program">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center py-4">
            <i class="fa-solid fa-graduation-cap text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-0">No programs found</p>
        </td>
    </tr>
@endforelse
