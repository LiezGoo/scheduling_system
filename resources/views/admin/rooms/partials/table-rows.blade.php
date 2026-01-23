@forelse($rooms as $room)
    <tr>
        <td><strong>{{ $room->room_code }}</strong></td>
        <td>{{ $room->room_name }}</td>
        <td>
            <span class="badge bg-secondary">
                {{ $room->building->building_name ?? 'N/A' }}
            </span>
        </td>
        <td>
            <span class="badge bg-info">
                {{ $room->roomType->type_name ?? 'N/A' }}
            </span>
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary view-room-btn"
                    data-room-id="{{ $room->id }}" title="View" aria-label="View Room Details">
                    <i class="fa-regular fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning edit-room-btn"
                    data-room-id="{{ $room->id }}" data-room-code="{{ $room->room_code }}"
                    data-room-name="{{ $room->room_name }}" data-building-id="{{ $room->building_id }}"
                    data-room-type-id="{{ $room->room_type_id }}" data-capacity="{{ $room->capacity }}"
                    data-floor-level="{{ $room->floor_level }}" title="Edit" aria-label="Edit Room">
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
        <td colspan="5" class="text-center text-muted py-4">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p class="mb-0">No rooms found</p>
        </td>
    </tr>
@endforelse
