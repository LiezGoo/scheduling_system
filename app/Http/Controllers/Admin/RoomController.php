<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    private const ROOM_CSV_HEADERS = [
        'room_code',
        'room_name',
        'building',
        'floor',
        'capacity',
        'type',
    ];

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

    /**
     * Download the room CSV template.
     */
    public function downloadTemplate()
    {
        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, self::ROOM_CSV_HEADERS);
            fclose($file);
        };

        return response()->streamDownload($callback, 'room_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Handle strict CSV room upload and return JSON feedback.
     */
    public function uploadCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please upload a valid CSV file.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFile = $request->file('file');

        if (!$uploadedFile || $uploadedFile->getSize() === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'The uploaded file is empty.',
            ], 422);
        }

        $filePath = $uploadedFile->getRealPath();
        if (!$filePath || !is_readable($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to read the uploaded file.',
            ], 422);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to open the uploaded file.',
            ], 422);
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'CSV file is empty or invalid.',
                ], 422);
            }

            if (isset($header[0])) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }

            if ($header !== self::ROOM_CSV_HEADERS) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid CSV format. Please use the template.',
                    'expected' => self::ROOM_CSV_HEADERS,
                    'received' => $header,
                ], 422);
            }

            $lineNumber = 1;
            $totalRows = 0;
            $successfulImports = 0;
            $failedRows = [];
            $uploadedRoomCodes = [];

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                $isBlankRow = count(array_filter($row, static function ($value) {
                    return trim((string) $value) !== '';
                })) === 0;

                if ($isBlankRow) {
                    $failedRows[] = [
                        'line' => $lineNumber,
                        'room_code' => null,
                        'reason' => 'Blank row is not allowed.',
                    ];
                    continue;
                }

                $totalRows++;

                if (count($row) !== count(self::ROOM_CSV_HEADERS)) {
                    $failedRows[] = [
                        'line' => $lineNumber,
                        'room_code' => $row[0] ?? null,
                        'reason' => 'Invalid column count on this row.',
                    ];
                    continue;
                }

                $payload = [
                    'room_code' => trim((string) $row[0]),
                    'room_name' => trim((string) $row[1]),
                    'building' => trim((string) $row[2]),
                    'floor' => trim((string) $row[3]),
                    'capacity' => trim((string) $row[4]),
                    'room_type' => ucfirst(strtolower(trim((string) $row[5]))),
                ];

                if (in_array($payload['room_code'], $uploadedRoomCodes, true)) {
                    $failedRows[] = [
                        'line' => $lineNumber,
                        'room_code' => $payload['room_code'],
                        'reason' => 'Duplicate room_code found within the uploaded file.',
                    ];
                    continue;
                }

                $rowValidator = Validator::make($payload, [
                    'room_code' => 'required|string|max:50|unique:rooms,room_code',
                    'room_name' => 'required|string|max:255',
                    'building' => 'required|string|max:255',
                    'floor' => 'required|integer|min:0',
                    'capacity' => 'required|integer|min:1',
                    'room_type' => ['required', Rule::in(['Lecture', 'Laboratory'])],
                ]);

                if ($rowValidator->fails()) {
                    $failedRows[] = [
                        'line' => $lineNumber,
                        'room_code' => $payload['room_code'] ?: null,
                        'reason' => implode(' ', $rowValidator->errors()->all()),
                    ];
                    continue;
                }

                try {
                    Room::create($payload);
                    $uploadedRoomCodes[] = $payload['room_code'];
                    $successfulImports++;
                } catch (\Throwable $e) {
                    $failedRows[] = [
                        'line' => $lineNumber,
                        'room_code' => $payload['room_code'] ?: null,
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            if ($totalRows === 0 && empty($failedRows)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data rows found. Add at least one room row below the header.',
                ], 422);
            }

            $failedCount = count($failedRows);
            $status = $successfulImports > 0 ? 'success' : 'error';
            $message = $failedCount > 0
                ? 'CSV processed with some failed rows.'
                : 'Rooms uploaded successfully.';

            return response()->json([
                'status' => $status,
                'message' => $message,
                'summary' => [
                    'total_rows' => $totalRows,
                    'successful_imports' => $successfulImports,
                    'failed_rows' => $failedCount,
                ],
                'failed_rows' => $failedRows,
            ], $successfulImports > 0 ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process CSV file: ' . $e->getMessage(),
            ], 500);
        } finally {
            fclose($handle);
        }
    }
}
