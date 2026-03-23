<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Models\Room;
use App\Models\YearLevel;
use App\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Services\ScheduleGenerationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ScheduleController extends Controller
{
    use AuthorizesRequests;

    protected NotificationService $notificationService;
    protected ScheduleGenerationService $scheduleGenerationService;

    public function __construct(NotificationService $notificationService, ScheduleGenerationService $scheduleGenerationService)
    {
        $this->notificationService = $notificationService;
        $this->scheduleGenerationService = $scheduleGenerationService;
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

        // Fetch dynamic data for filters
        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->get();
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();
        }

        // Get semesters for active academic year (fallback: all semesters)
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $semesters = collect();
        if ($activeAcademicYear) {
            $semesters = Semester::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Semester::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($semesters->isEmpty()) {
            $semesters = Semester::query()
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Get program for this program head
        $program = \App\Models\Program::with('department')->find($user->program_id);

        // Get year levels from program subjects (curriculum), mapped to year_levels table IDs.
        $yearLevels = collect();
        if ($program) {
            $yearLevelCodes = DB::table('program_subjects')
                ->where('program_id', $program->id)
                ->distinct()
                ->pluck('year_level')
                ->sort()
                ->values()
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->values();

            if ($yearLevelCodes->isNotEmpty()) {
                $yearLevels = YearLevel::query()
                    ->where('status', YearLevel::STATUS_ACTIVE)
                    ->where(function ($query) use ($yearLevelCodes) {
                        $query->whereIn('id', $yearLevelCodes->all())
                            ->orWhereIn('code', $yearLevelCodes->map(fn ($value) => (string) $value)->all());
                    })
                    ->get(['id', 'name', 'code'])
                    ->unique('id')
                    ->sortBy(fn (YearLevel $level) => (int) ($level->code ?: $level->id))
                    ->values();
            }
        }

        // Get schedules with filters (VIEW ONLY)
        $query = Schedule::with(['program', 'creator'])
            ->where('program_id', $user->program_id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by academic year
        if ($request->filled('academic_year_id')) {
            $academicYear = AcademicYear::find($request->academic_year_id);
            if ($academicYear) {
                $query->where('academic_year', $academicYear->name);
            } else {
                $query->whereRaw('1 = 0');
            }
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

        return view('program-head.schedules.index', compact(
            'schedules',
            'academicYears',
            'semesters',
            'program',
            'yearLevels'
        ));
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

        $schedule->load(['items.subject', 'items.instructor', 'items.room', 'program']);

        // Build schedule grid for timetable visualization
        $scheduleGrid = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        
        foreach ($days as $day) {
            $scheduleGrid[$day] = [];
        }

        foreach ($schedule->items as $item) {
            $day = strtolower($item->day_of_week);
            $start = \Carbon\Carbon::parse($item->start_time)->format('H:i');
            
            if (!isset($scheduleGrid[$day][$start])) {
                $scheduleGrid[$day][$start] = [];
            }
            
            $scheduleGrid[$day][$start][] = $item;
        }

        return view('program-head.schedules.show', compact('schedule', 'scheduleGrid'));
    }

    /**
     * Preview existing schedules for selected filters.
     * Read-only endpoint used by Program Head schedule preview panel.
     */
    public function preview(Request $request)
    {
        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'year_level_id' => 'required|integer|exists:year_levels,id',
            'block_id' => 'nullable|integer|exists:blocks,id',
        ]);

        $academicYearName = AcademicYear::query()
            ->where('id', $validated['academic_year_id'])
            ->value('name');

        $semesterName = Semester::query()
            ->where('id', $validated['semester_id'])
            ->value('name');

        $yearLevel = YearLevel::query()->find($validated['year_level_id']);
        $yearLevelValue = $this->resolveYearLevelValue($yearLevel);

        if (!$academicYearName || !$semesterName || $yearLevelValue === null) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No schedule found.',
                'data' => [],
            ]);
        }

        $selectedBlockName = null;
        if (!empty($validated['block_id'])) {
            $selectedBlockName = Block::query()
                ->where('id', $validated['block_id'])
                ->where('program_id', $user->program_id)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('semester_id', $validated['semester_id'])
                ->where('year_level_id', $validated['year_level_id'])
                ->value('block_name');

            if (!$selectedBlockName) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Selected block does not match the chosen filters.',
                    'data' => [],
                ], 422);
            }
        }

        $query = ScheduleItem::query()
            ->with([
                'subject:id,subject_code,subject_name',
                'instructor:id,first_name,last_name',
                'room:id,room_name',
                'schedule:id,program_id,academic_year,semester,year_level,block',
            ])
            ->whereHas('schedule', function ($scheduleQuery) use ($user, $academicYearName, $semesterName, $yearLevelValue, $selectedBlockName) {
                $scheduleQuery
                    ->where('program_id', $user->program_id)
                    ->where('academic_year', $academicYearName)
                    ->where('semester', $semesterName)
                    ->where('year_level', $yearLevelValue);

                if (!empty($selectedBlockName)) {
                    $scheduleQuery->where('block', trim((string) $selectedBlockName));
                }
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        $scheduleItems = $query->get();

        if ($scheduleItems->isEmpty()) {
            return response()->json([
                'status' => 'empty',
                'message' => 'No schedule found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatSchedule($scheduleItems),
        ]);
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\ScheduleItem> $scheduleItems
     */
    private function formatSchedule($scheduleItems): array
    {
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $scheduleItems
            ->groupBy('day_of_week')
            ->sortBy(fn ($items, $day) => array_search($day, $dayOrder, true) === false ? 99 : array_search($day, $dayOrder, true))
            ->map(function ($daySchedules) {
                return $daySchedules
                    ->sortBy('start_time')
                    ->values()
                    ->map(function (ScheduleItem $item) {
                        return [
                            'subject' => $item->subject?->subject_name ?? 'Unknown Subject',
                            'code' => $item->subject?->subject_code ?? 'N/A',
                            'faculty' => $item->instructor?->full_name ?? 'TBA',
                            'room' => $item->room?->room_name ?? 'TBA',
                            'start_time' => (string) $item->start_time,
                            'end_time' => (string) $item->end_time,
                            'block' => $item->schedule?->block,
                            'day' => (string) $item->day_of_week,
                        ];
                    })
                    ->all();
            })
            ->toArray();
    }

    /**
     * Return blocks for Program Head schedule filters.
     */
    public function getBlocks(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'year_level_id' => 'required|integer|exists:year_levels,id',
        ]);

        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access.',
                'data' => [],
            ], 403);
        }

        $blocks = Block::query()
            ->where('program_id', $user->program_id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->where('year_level_id', $validated['year_level_id'])
            ->where('status', Block::STATUS_ACTIVE)
            ->orderBy('block_name')
            ->get(['id', 'block_name']);

        return response()->json([
            'status' => 'success',
            'data' => $blocks,
        ]);
    }

    private function resolveYearLevelValue(?YearLevel $yearLevel): ?int
    {
        if (!$yearLevel) {
            return null;
        }

        $code = trim((string) ($yearLevel->code ?? ''));
        if ($code !== '' && ctype_digit($code)) {
            return (int) $code;
        }

        return $yearLevel->id ? (int) $yearLevel->id : null;
    }
}
