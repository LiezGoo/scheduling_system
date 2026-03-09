@forelse($configurations as $config)
    <tr>
        <td>
            <strong>{{ $config->faculty->full_name }}</strong>
        </td>
        <td class="text-center">
            <span class="badge bg-info">{{ $config->program->department->department_name }}</span>
        </td>
        <td class="text-center">
            {{ $config->max_lecture_hours }} hrs/week
        </td>
        <td class="text-center">
            {{ $config->max_lab_hours }} hrs/week
        </td>
        <td class="text-center">
            {{ $config->max_hours_per_day }} hrs/day
        </td>
        <td class="text-center">
            <small>{{ $config->available_days_string }}</small>
        </td>
        <td class="text-center">
            @if($config->is_active)
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-danger">Inactive</span>
            @endif
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="view" data-id="{{ $config->id }}"
                    title="View" aria-label="View Configuration Details">
                    <i class="fa-regular fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-action="edit" data-id="{{ $config->id }}"
                    title="Edit" aria-label="Edit Configuration">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="{{ $config->id }}"
                    data-faculty-name="{{ $config->faculty->full_name }}" title="Delete" aria-label="Delete Configuration">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center py-5 text-muted">
            <i class="fa-solid fa-inbox fa-2x mb-3"></i>
            <p>No configurations found</p>
        </td>
    </tr>
@endforelse
