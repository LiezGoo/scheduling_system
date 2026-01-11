@forelse($users as $user)
    <tr class="{{ $user->status === 'inactive' ? 'table-muted' : '' }}" data-user-id="{{ $user->id }}">
        <td>
            <div class="d-flex align-items-center">
                <div class="avatar-circle me-2">
                    {{ strtoupper(substr($user->first_name, 0, 1)) }}
                </div>
                <span class="fw-semibold">{{ $user->full_name }}</span>
            </div>
        </td>
        <td>{{ $user->email }}</td>
        <td>
            <span class="badge bg-info">
                {{ $user->getRoleLabel() }}
            </span>
        </td>
        <td>
            <span class="badge status-badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                {{ ucfirst($user->status) }}
            </span>
        </td>
        <td>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary view-user-btn"
                    data-user-id="{{ $user->id }}" title="View" aria-label="View User Details">
                    <i class="fa-regular fa-eye"></i>
                </button>

                <button type="button" class="btn btn-sm btn-outline-warning edit-user-btn"
                    data-user-id="{{ $user->id }}" title="Edit" aria-label="Edit User">
                    <i class="fa-solid fa-pencil"></i>
                </button>

                <button type="button" class="btn btn-sm btn-outline-info toggle-status-btn"
                    data-user-id="{{ $user->id }}" data-current-status="{{ $user->status }}" title="Toggle Status"
                    aria-label="Toggle User Status" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <i class="fa-solid fa-toggle-{{ $user->status === 'active' ? 'on' : 'off' }}"></i>
                </button>

                <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn"
                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->full_name }}" title="Delete"
                    aria-label="Delete User" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center py-4">
            <i class="fa-solid fa-users text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-0">No users found</p>
        </td>
    </tr>
@endforelse
