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
            <button class="btn btn-sm btn-outline-primary view-room-btn" data-room-id="{{ $room->id }}"
                title="View Details">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning edit-room-btn" data-room-id="{{ $room->id }}"
                data-room-code="{{ $room->room_code }}" data-room-name="{{ $room->room_name }}"
                data-building-id="{{ $room->building_id }}" data-room-type-id="{{ $room->room_type_id }}"
                data-capacity="{{ $room->capacity }}" data-floor-level="{{ $room->floor_level }}" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger delete-room-btn" data-room-id="{{ $room->id }}"
                data-room-name="{{ $room->room_name }}" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
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
