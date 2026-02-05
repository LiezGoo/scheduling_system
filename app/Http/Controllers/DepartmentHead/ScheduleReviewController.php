<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleReviewController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display schedules pending approval for the department head.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->isDepartmentHead() || !$user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        $schedules = Schedule::with(['program', 'creator'])
            ->whereHas('program', function ($query) use ($user) {
                $query->where('department_id', $user->department_id);
            })
            ->where('status', Schedule::STATUS_PENDING_APPROVAL)
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('program_id');

        return view('department-head.schedules.index', compact('schedules'));
    }

    /**
     * Show schedule details in read-only mode.
     */
    public function show(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('view', $schedule);

        if (!$user->isDepartmentHead()) {
            abort(403, 'Unauthorized access.');
        }

        $schedule->load([
            'program',
            'creator',
            'items.subject',
            'items.instructor',
            'items.room.building',
        ]);

        return view('department-head.schedules.show', compact('schedule'));
    }

    /**
     * Approve a schedule.
     */
    public function approve(Request $request, Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('review', $schedule);

        DB::transaction(function () use ($schedule, $user, $request) {
            $schedule->approveByDepartmentHead($user, $request->string('review_remarks')->toString() ?: null);
        });

        $this->notificationService->sendToUser(
            $schedule->creator,
            'Schedule Approved',
            "Your schedule for {$schedule->academic_year} ({$schedule->semester}) has been approved.",
            'success',
            route('program-head.schedules.index')
        );

        return redirect()->route('department-head.schedules.index')
            ->with('success', 'Schedule approved successfully.');
    }

    /**
     * Reject a schedule with remarks.
     */
    public function reject(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'review_remarks' => ['required', 'string', 'max:2000'],
        ]);

        $this->authorize('review', $schedule);

        DB::transaction(function () use ($schedule, $request) {
            $schedule->rejectByDepartmentHead(Auth::user(), $request->string('review_remarks')->toString());
        });

        $remarks = $validated['review_remarks'];
        $this->notificationService->sendToUser(
            $schedule->creator,
            'Schedule Rejected',
            "Your schedule has been rejected. Remarks: {$remarks}",
            'error',
            route('program-head.schedules.index')
        );

        return redirect()->route('department-head.schedules.index')
            ->with('success', 'Schedule rejected with remarks.');
    }
}
