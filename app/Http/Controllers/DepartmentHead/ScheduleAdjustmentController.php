<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleAdjustmentRequest;
use App\Models\ScheduleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ConstraintValidator;
use App\Notifications\AdjustmentRequestSubmitted;
use App\Notifications\AdjustmentRequestApproved;
use App\Notifications\AdjustmentRequestRejected;

class ScheduleAdjustmentController extends Controller
{
    protected $constraintValidator;

    public function __construct(ConstraintValidator $constraintValidator)
    {
        $this->constraintValidator = $constraintValidator;
    }

    /**
     * Display all adjustment requests for a schedule
     */
    public function index(Schedule $schedule)
    {
        $this->authorize('view', $schedule);

        $requests = $schedule->adjustmentRequests()
            ->with(['requester', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $pending = $schedule->adjustmentRequests()->where('status', ScheduleAdjustmentRequest::STATUS_PENDING)->count();

        return view('department-head.adjustments.index', [
            'schedule' => $schedule,
            'requests' => $requests,
            'pending' => $pending,
        ]);
    }

    /**
     * Show specific adjustment request
     */
    public function show(Schedule $schedule, ScheduleAdjustmentRequest $request)
    {
        $this->authorize('view', $schedule);

        if ($request->schedule_id !== $schedule->id) {
            abort(404);
        }

        return view('department-head.adjustments.show', [
            'schedule' => $schedule,
            'request' => $request,
        ]);
    }

    /**
     * Submit adjustment request (for Program Head/Instructor)
     */
    public function store(Schedule $schedule, Request $request)
    {
        // Program Heads and Instructors can request adjustments
        $this->authorize('requestAdjustment', $schedule);

        $validated = $request->validate([
            'schedule_item_id' => 'nullable|integer|exists:schedule_items,id',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $adjustmentRequest = ScheduleAdjustmentRequest::create([
                'schedule_id' => $schedule->id,
                'schedule_item_id' => $validated['schedule_item_id'] ?? null,
                'requested_by' => Auth::id(),
                'reason' => $validated['reason'],
                'status' => ScheduleAdjustmentRequest::STATUS_PENDING,
            ]);

            // Notify department head
            $departmentHead = $schedule->program->department->head;
            if ($departmentHead) {
                $departmentHead->user->notify(new AdjustmentRequestSubmitted($adjustmentRequest, Auth::user()));
            }

            DB::commit();

            return redirect()
                ->route('program-head.schedules.show', $schedule)
                ->with('success', 'Adjustment request submitted successfully. Department Head will review it.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Failed to submit adjustment request: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Approve adjustment request
     */
    public function approve(Schedule $schedule, ScheduleAdjustmentRequest $request)
    {
        $this->authorize('approveAdjustment', $schedule);

        if ($request->schedule_id !== $schedule->id) {
            abort(404);
        }

        if (!$request->isPending()) {
            return back()->withErrors(['error' => 'This request has already been reviewed.']);
        }

        $request->approve(Auth::user());

        // Notify requester
        $request->requester->notify(new AdjustmentRequestApproved($request, $schedule));

        return back()->with('success', 'Adjustment request approved. You can now edit the schedule.');
    }

    /**
     * Reject adjustment request
     */
    public function reject(Schedule $schedule, ScheduleAdjustmentRequest $request, Request $request_data)
    {
        $this->authorize('approveAdjustment', $schedule);

        if ($request->schedule_id !== $schedule->id) {
            abort(404);
        }

        if (!$request->isPending()) {
            return back()->withErrors(['error' => 'This request has already been reviewed.']);
        }

        $validated = $request_data->validate([
            'review_remarks' => 'required|string|min:5|max:500',
        ]);

        $request->reject(Auth::user(), $validated['review_remarks']);

        // Notify requester
        $request->requester->notify(new AdjustmentRequestRejected($request, $schedule));

        return back()->with('success', 'Adjustment request rejected.');
    }

    /**
     * Show edit form for schedule adjustment (after approval)
     */
    public function edit(Schedule $schedule)
    {
        $this->authorize('editSchedule', $schedule);

        $scheduleItems = $schedule->items()
            ->with(['subject', 'instructor', 'room'])
            ->get();

        $rooms = \App\Models\Room::orderBy('room_code')->get();
        $instructors = \App\Models\User::where('role', \App\Models\User::ROLE_INSTRUCTOR)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('department-head.schedules.edit', [
            'schedule' => $schedule,
            'items' => $scheduleItems,
            'rooms' => $rooms,
            'instructors' => $instructors,
        ]);
    }

    /**
     * Update schedule item based on approved adjustment
     */
    public function updateItem(Schedule $schedule, ScheduleItem $item, Request $request)
    {
        $this->authorize('editSchedule', $schedule);

        if ($item->schedule_id !== $schedule->id) {
            abort(404);
        }

        $validated = $request->validate([
            'room_id' => 'nullable|integer|exists:rooms,id',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'instructor_id' => 'nullable|integer|exists:users,id',
        ]);

        $updateData = [
            'room_id' => $validated['room_id'],
            'day_of_week' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'instructor_id' => $validated['instructor_id'],
        ];

        // Validate constraints
        $validation_errors = $this->validateScheduleItemConstraints(
            $item,
            $updateData,
            $schedule
        );

        if (!empty($validation_errors)) {
            return response()->json([
                'success' => false,
                'errors' => $validation_errors,
            ], 422);
        }

        DB::beginTransaction();

        try {
            $item->update($updateData);

            // Recalculate fitness score
            // Note: This would call the GeneticAlgorithmEngine to recalculate fitness
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule item updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to update schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate schedule item constraints before updating
     */
    protected function validateScheduleItemConstraints($item, $changes, $schedule)
    {
        $errors = [];

        $roomId = $changes['room_id'] ?? $item->room_id;
        $dayOfWeek = $changes['day_of_week'] ?? $item->day_of_week;
        $startTime = $changes['start_time'] ?? $item->start_time;
        $endTime = $changes['end_time'] ?? $item->end_time;

        // Check for room conflicts
        if ($roomId && ScheduleItem::hasRoomConflict($roomId, $dayOfWeek, $startTime, $endTime, $schedule->id)) {
            $errors['room'] = 'This room is already scheduled at this time.';
        }

        // Check for instructor conflicts
        $instructorId = $changes['instructor_id'] ?? $item->instructor_id;
        if ($instructorId && ScheduleItem::hasInstructorConflict($instructorId, $dayOfWeek, $startTime, $endTime, $schedule->id)) {
            $errors['instructor'] = 'This instructor is already scheduled at this time.';
        }

        if ($instructorId && $dayOfWeek && $startTime && $endTime) {
            $instructor = User::find($instructorId);
            if ($instructor && !$this->constraintValidator->isWithinInstructorScheme($instructor, $startTime, $endTime, $dayOfWeek, $schedule->program_id)) {
                $errors['teaching_scheme'] = 'Class time is outside faculty teaching availability.';
            }
        }

        return $errors;
    }
}
