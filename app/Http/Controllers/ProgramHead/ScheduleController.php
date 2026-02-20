<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Subject;
use App\Models\User;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ScheduleController extends Controller
{
    use AuthorizesRequests;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of schedules for program head's program.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $query = Schedule::with(['program', 'creator'])
            ->where('program_id', $user->program_id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by academic year
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('program-head.schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        // Get subjects from department (shared resource)
        $subjects = Subject::forDepartment($department->id)
            ->active()
            ->orderBy('subject_code')
            ->get();

        // Get eligible instructors from department
        $instructors = User::eligibleInstructors()
            ->active()
            ->inDepartment($department->id)
            ->orderBy('first_name')
            ->get();

        // Get available rooms
        $rooms = Room::with('building', 'roomType')
            ->orderBy('room_code')
            ->get();

        return view('program-head.schedules.create', compact('subjects', 'instructors', 'rooms'));
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $validated = $request->validate([
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|in:1st Semester,2nd Semester',
            'year_level' => 'required|integer|in:1,2,3,4',
            'block' => 'nullable|string|max:10',
            'schedule_items' => 'required|array|min:1',
            'schedule_items.*.subject_id' => 'required|integer|exists:subjects,id',
            'schedule_items.*.instructor_id' => 'required|integer|exists:users,id',
            'schedule_items.*.room_id' => 'required|integer|exists:rooms,id',
            'schedule_items.*.day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedule_items.*.start_time' => 'required|date_format:H:i',
            'schedule_items.*.end_time' => 'required|date_format:H:i|after:schedule_items.*.start_time',
            'schedule_items.*.section' => 'nullable|string|max:50',
        ]);

        // Verify all subjects belong to department
        $subjectIds = collect($validated['schedule_items'])->pluck('subject_id')->unique();
        $validSubjects = Subject::whereIn('id', $subjectIds)
            ->forDepartment($department->id)
            ->active()
            ->count();

        if ($validSubjects !== $subjectIds->count()) {
            return back()->withErrors('Some subjects do not belong to your department or are inactive.');
        }

        DB::beginTransaction();

        try {
            // Create schedule
            $schedule = Schedule::create([
                'program_id' => $user->program_id,
                'created_by' => $user->id,
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
                'year_level' => $validated['year_level'],
                'block' => $validated['block'],
                'status' => Schedule::STATUS_DRAFT,
            ]);

            // Group schedule items by instructor to calculate weekly hours
            $instructorHours = [];
            foreach ($validated['schedule_items'] as $item) {
                $instructorId = $item['instructor_id'];
                $subjectId = $item['subject_id'];

                if (!isset($instructorHours[$instructorId])) {
                    $instructorHours[$instructorId] = [
                        'subjects' => [],
                        'total_lecture_hours' => 0,
                        'total_lab_hours' => 0,
                    ];
                }

                // Get subject to determine if it's lecture or lab hours
                $subject = Subject::find($subjectId);
                if ($subject) {
                    // Track unique subjects per instructor
                    if (!in_array($subjectId, $instructorHours[$instructorId]['subjects'])) {
                        $instructorHours[$instructorId]['subjects'][] = $subjectId;
                        $instructorHours[$instructorId]['total_lecture_hours'] += $subject->lecture_hours;
                        $instructorHours[$instructorId]['total_lab_hours'] += $subject->lab_hours;
                    }
                }
            }

            // Validate faculty load for each instructor
            foreach ($instructorHours as $instructorId => $hours) {
                $instructor = User::find($instructorId);
                if ($instructor) {
                    $totalHours = $hours['total_lecture_hours'] + $hours['total_lab_hours'];
                    $maxHours = 40; // Define your maximum faculty load limit

                    if ($totalHours > $maxHours) {
                        DB::rollBack();
                        return back()->withErrors("Instructor {$instructor->first_name} {$instructor->last_name} exceeds maximum faculty load of {$maxHours} hours.")
                            ->withInput();
                    }
                }
            }

            // Create schedule items
            foreach ($validated['schedule_items'] as $item) {
                // Check for conflicts
                $instructorConflict = ScheduleItem::hasInstructorConflict(
                    $item['instructor_id'],
                    $item['day_of_week'],
                    $item['start_time'],
                    $item['end_time']
                );

                if ($instructorConflict) {
                    DB::rollBack();
                    return back()->withErrors("Instructor conflict detected for {$item['day_of_week']} at {$item['start_time']}-{$item['end_time']}")
                        ->withInput();
                }

                $roomConflict = ScheduleItem::hasRoomConflict(
                    $item['room_id'],
                    $item['day_of_week'],
                    $item['start_time'],
                    $item['end_time']
                );

                if ($roomConflict) {
                    DB::rollBack();
                    return back()->withErrors("Room conflict detected for {$item['day_of_week']} at {$item['start_time']}-{$item['end_time']}")
                        ->withInput();
                }

                ScheduleItem::create([
                    'schedule_id' => $schedule->id,
                    'subject_id' => $item['subject_id'],
                    'instructor_id' => $item['instructor_id'],
                    'room_id' => $item['room_id'],
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'section' => $item['section'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('program-head.schedules.show', $schedule)
                ->with('success', 'Schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Failed to create schedule: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('view', $schedule);

        // Ensure schedule belongs to program head's program
        if (!$user->isProgramHead() || $schedule->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $schedule->load(['items.subject', 'items.instructor', 'items.room.building', 'program']);

        return view('program-head.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(Schedule $schedule)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('update', $schedule);

        // Ensure schedule belongs to program head's program
        if (!$user->isProgramHead() || $schedule->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        // Can only edit drafts or rejected schedules
        if (!($schedule->isDraft() || $schedule->isRejected())) {
            return back()->withErrors('Only draft or rejected schedules can be edited.');
        }

        $schedule->load(['items.subject', 'items.instructor', 'items.room']);

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        // Get subjects from department
        $subjects = Subject::forDepartment($department->id)
            ->active()
            ->orderBy('subject_code')
            ->get();

        // Get eligible instructors
        $instructors = User::eligibleInstructors()
            ->active()
            ->inDepartment($department->id)
            ->orderBy('first_name')
            ->get();

        // Get available rooms
        $rooms = Room::with('building', 'roomType')
            ->orderBy('room_code')
            ->get();

        return view('program-head.schedules.edit', compact('schedule', 'subjects', 'instructors', 'rooms'));
    }

    /**
     * Submit schedule for approval to Department Head.
     */
    public function submit(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('submit', $schedule);

        // Ensure schedule belongs to program head's program
        if (!$user->isProgramHead() || $schedule->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!($schedule->isDraft() || $schedule->isRejected())) {
            return back()->withErrors('Only draft or rejected schedules can be submitted.');
        }

        if ($schedule->items->isEmpty()) {
            return back()->withErrors('Cannot submit empty schedule.');
        }

        if ($schedule->submit()) {
            // Notify department head
            $departmentId = $user->program?->department_id;
            $departmentHead = User::where('role', User::ROLE_DEPARTMENT_HEAD)
                ->where('department_id', $departmentId)
                ->first();

            if ($departmentHead) {
                $this->notificationService->sendToUser(
                    $departmentHead,
                    'New Schedule Pending Approval',
                    "A new schedule from {$schedule->program->program_name} requires your approval.",
                    'info',
                    route('department-head.schedules.show', $schedule)
                );
            }

            return redirect()->route('program-head.schedules.index')
                ->with('success', 'Schedule submitted for approval.');
        }

        return back()->withErrors('Failed to submit schedule.');
    }

    /**
     * Delete a schedule (only drafts).
     */
    public function destroy(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('delete', $schedule);

        // Ensure schedule belongs to program head's program
        if (!$user->isProgramHead() || $schedule->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!$schedule->isDraft()) {
            return back()->withErrors('Only draft schedules can be deleted.');
        }

        try {
            $schedule->delete();

            return redirect()->route('program-head.schedules.index')
                ->with('success', 'Schedule deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors('Failed to delete schedule.');
        }
    }
}
