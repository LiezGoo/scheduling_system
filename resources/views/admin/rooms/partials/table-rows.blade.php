@forelse($rooms as $room)
    <tr>
        <td class="fw-semibold">{{ $room->room_code }}</td>
        <td>{{ $room->room_name }}</td>
        <td>{{ $room->building->building_name ?? 'N/A' }}</td>
        <td>
            <span class="badge bg-secondary">
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
                    data-room-type-id="{{ $room->room_type_id }}" title="Edit" aria-label="Edit Room">
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
    <tr>
        <td colspan="5" class="text-center py-4">
            <i class="fa-solid fa-door-open text-muted fa-3x mb-3"></i>
            <p class="text-muted mb-0">No rooms found</p>
        </td>
    </tr>
@endforelse
