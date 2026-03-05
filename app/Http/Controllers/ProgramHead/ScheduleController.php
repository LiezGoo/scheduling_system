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

        // Get semesters - fetch from database
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $semesters = [];
        if ($activeAcademicYear) {
            $semesters = Semester::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Semester::STATUS_ACTIVE)
                ->get()
                ->mapWithKeys(function ($semester) {
                    return [$semester->name => $semester->name];
                })
                ->toArray();
        }

        // Get program for this program head
        $program = \App\Models\Program::with('department')->find($user->program_id);

        // Get year levels from program subjects (curriculum)
        $yearLevels = [];
        if ($program) {
            $yearLevels = DB::table('program_subjects')
                ->where('program_id', $program->id)
                ->distinct()
                ->pluck('year_level')
                ->sort()
                ->values()
                ->toArray();
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

        $schedule->load(['items.subject', 'items.instructor', 'items.room.building', 'program']);

        return view('program-head.schedules.show', compact('schedule'));
    }
}
