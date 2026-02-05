@forelse($rooms as $room)
    <tr>
        <td><strong>{{ $room->room_code }}</strong></td>
        <td>{{ $room->room_name }}</td>
        <td>
            <span class="badge bg-info">
                {{ $room->room_type ?? 'N/A' }}
            </span>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-warning edit-room-btn"
                    data-room-id="{{ $room->id }}" data-room-code="{{ $room->room_code }}"
                    data-room-name="{{ $room->room_name }}" data-room-type="{{ $room->room_type }}" title="Edit"
                    aria-label="Edit Room">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-room-btn"
                    data-room-id="{{ $room->id }}" data-room-name="{{ $room->room_name }}" title="Delete"
                    aria-label="Delete Room">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr id="empty-state">
        <td colspan="4" class="text-center text-muted py-4">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p class="mb-0">No rooms found</p>
        </td>
    </tr>
@endforelse
