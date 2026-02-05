<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms with optional filters.
     */
    public function index(Request $request)
    {
        $query = Room::query();

        // Filter by search (room code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('room_code', 'LIKE', $search)
                    ->orWhere('room_name', 'LIKE', $search);
            });
        }

        // Filter by room type (case-insensitive text search)
        if ($request->filled('room_type')) {
            $roomType = '%' . $request->room_type . '%';
            $query->where('room_type', 'LIKE', $roomType);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered rooms
        $rooms = $query->orderBy('room_code')->paginate($perPage)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.rooms.partials.table-rows', compact('rooms'))->render(),
                'pagination' => $rooms->withQueryString()->links(),
            ]);
        }

        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room)
    {
        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'room_name' => $room->room_name,
                    'room_type' => $room->room_type,
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
            'room_type' => 'required|string|max:50',
        ]);

        // Trim and normalize the room_type
        $validated['room_type'] = trim($validated['room_type']);

        try {
            $room = Room::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully!',
                'room' => $room,
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
            'room_type' => 'required|string|max:50',
        ]);

        // Trim and normalize the room_type
        $validated['room_type'] = trim($validated['room_type']);

        try {
            $room->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully!',
                'room' => $room,
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
