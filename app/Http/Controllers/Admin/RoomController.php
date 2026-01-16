<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms with optional filters.
     */
    public function index(Request $request)
    {
        $query = Room::with(['roomType', 'building']);

        // Filter by search (room code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('room_code', 'LIKE', $search)
                    ->orWhere('room_name', 'LIKE', $search);
            });
        }

        // Filter by room type
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered rooms
        $rooms = $query->orderBy('room_code')->paginate($perPage)->appends($request->query());

        // Get all room types and buildings for filter dropdowns
        $roomTypes = RoomType::orderBy('type_name')->get();
        $buildings = Building::orderBy('building_name')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.rooms.partials.table-rows', compact('rooms'))->render(),
                'pagination' => $rooms->withQueryString()->links()->render(),
            ]);
        }

        return view('admin.rooms.index', compact('rooms', 'roomTypes', 'buildings'));
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room)
    {
        $room->load(['roomType', 'building']);

        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'room_name' => $room->room_name,
                    'building_id' => $room->building_id,
                    'building_name' => $room->building->building_name ?? 'N/A',
                    'room_type_id' => $room->room_type_id,
                    'type_name' => $room->roomType->type_name ?? 'N/A',
                    'capacity' => $room->capacity,
                    'floor_level' => $room->floor_level,
                    'created_at' => $room->created_at,
                    'updated_at' => $room->updated_at,
                ]
            ]);
        }

        return view('admin.rooms.show', compact('room'));
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_code' => 'required|string|max:50|unique:rooms,room_code',
            'room_name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id',
            'room_type_id' => 'required|exists:room_types,id',
            'capacity' => 'nullable|integer|min:1',
            'floor_level' => 'nullable|integer',
        ]);

        try {
            $room = Room::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully!',
                'room' => $room->load(['roomType', 'building']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_code')->ignore($room->id),
            ],
            'room_name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id',
            'room_type_id' => 'required|exists:room_types,id',
            'capacity' => 'nullable|integer|min:1',
            'floor_level' => 'nullable|integer',
        ]);

        try {
            $room->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully!',
                'room' => $room->load(['roomType', 'building']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Room $room)
    {
        try {
            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage(),
            ], 500);
        }
    }
}
