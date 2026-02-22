<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ScheduleGenerationController
 * 
 * Handles the Genetic Algorithm-based schedule generation
 * This controller manages UI interactions and backend processing
 */
class ScheduleGenerationController extends Controller
{
    /**
     * Show the schedule generation interface
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check authorization
        $this->authorize('generate-schedule', User::class);

        // Load dropdown data
        $academicYears = $this->getAcademicYears();
        $departments = $this->authorize('viewAnyDepartment') 
            ? \App\Models\Department::all() 
            : Auth::user()->department ? collect([Auth::user()->department]) : collect();
        
        $programs = Program::all();
        $yearLevels = $this->getYearLevels();

        return view('genetic-algorithm.generate', [
            'academicYears' => $academicYears,
            'departments' => $departments,
            'programs' => $programs,
            'yearLevels' => $yearLevels,
        ]);
    }

    /**
     * Validate and initiate schedule generation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSchedule(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'academic_year_id' => 'required|integer|exists:academic_years,id',
                'semester' => 'required|integer|between:1,3',
                'department_id' => 'required|integer|exists:departments,id',
                'program_id' => 'required|integer|exists:programs,id',
                'year_level' => 'required|integer|between:1,4',
                'number_of_blocks' => 'required|integer|between:1,20',
                'population_size' => 'required|integer|between:10,500',
                'generations' => 'required|integer|between:10,1000',
                'mutation_rate' => 'required|numeric|between:1,100',
                'crossover_rate' => 'required|numeric|between:1,100',
                'elite_size' => 'required|integer|between:1,50',
            ]);

            // Check authorization
            $this->authorize('generate-schedule', Schedule::class);

            $academicYear = AcademicYear::find($validated['academic_year_id']);
            $numberOfBlocks = $validated['number_of_blocks'];

            // Generate schedules for each block
            $scheduleIds = [];
            for ($i = 1; $i <= $numberOfBlocks; $i++) {
                $blockName = "Block " . $i;

                // Create schedule record for this block
                $schedule = Schedule::create([
                    'academic_year' => $academicYear?->name,
                    'semester' => $validated['semester'],
                    'department_id' => $validated['department_id'],
                    'program_id' => $validated['program_id'],
                    'year_level' => $validated['year_level'],
                    'block_section' => $blockName,
                    'created_by' => Auth::id(),
                    'status' => 'processing',
                ]);

                // Store GA parameters
                $schedule->ga_parameters = [
                    'population_size' => $validated['population_size'],
                    'generations' => $validated['generations'],
                    'mutation_rate' => $validated['mutation_rate'],
                    'crossover_rate' => $validated['crossover_rate'],
                    'elite_size' => $validated['elite_size'],
                ];
                $schedule->save();

                $scheduleIds[] = $schedule->id;

                // TODO: Dispatch to background job for long-running GA algorithm
                // ProcessGeneticAlgorithm::dispatch($schedule);

                Log::info('Schedule generation initiated for block', [
                    'schedule_id' => $schedule->id,
                    'block' => $blockName,
                    'created_by' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'schedule_ids' => $scheduleIds,
                'count' => count($scheduleIds),
                'message' => "Schedule generation initiated for {$numberOfBlocks} block(s)",
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Schedule generation error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during schedule generation',
            ], 500);
        }
    }

    /**
     * Get real-time progress updates (for polling or WebSocket)
     * 
     * @param int $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        
        // Check authorization
        $this->authorize('view', $schedule);

        return response()->json([
            'schedule_id' => $schedule->id,
            'status' => $schedule->status,
            'progress' => $schedule->ga_progress ?? [
                'current_generation' => 0,
                'total_generations' => $schedule->ga_parameters['generations'] ?? 100,
                'best_fitness' => 0,
                'conflict_count' => 0,
            ],
            'created_at' => $schedule->created_at,
            'updated_at' => $schedule->updated_at,
        ]);
    }

    /**
     * Complete schedule generation and store results
     * 
     * @param int $scheduleId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeGeneration($scheduleId, Request $request)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        
        // Check authorization
        $this->authorize('update', $schedule);

        try {
            $validated = $request->validate([
                'schedule_items' => 'required|array',
                'conflicts' => 'array',
                'fitness_score' => 'required|numeric',
                'penalty_score' => 'numeric',
            ]);

            // Store schedule items
            $schedule->scheduleItems()->delete(); // Clear old items
            $schedule->scheduleItems()->createMany($validated['schedule_items']);

            // Store conflicts and scores
            $schedule->update([
                'status' => 'completed',
                'conflicts' => $validated['conflicts'] ?? [],
                'fitness_score' => $validated['fitness_score'],
                'penalty_score' => $validated['penalty_score'],
                'completed_at' => now(),
            ]);

            Log::info('Schedule generation completed', [
                'schedule_id' => $schedule->id,
                'fitness_score' => $validated['fitness_score'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule generation completed successfully',
                'schedule_id' => $schedule->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule completion error', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error completing schedule generation',
            ], 500);
        }
    }

    /**
     * Approve and submit schedule (Program Head only)
     * 
     * @param int $scheduleId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveSchedule($scheduleId, Request $request)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        
        // Check authorization - only Program Heads can approve
        $this->authorize('approve', $schedule);

        try {
            $schedule->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('notes'),
            ]);

            // Trigger notification to relevant stakeholders
            // event(new ScheduleApproved($schedule));

            Log::info('Schedule approved', [
                'schedule_id' => $schedule->id,
                'approved_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule approved and submitted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule approval error', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error approving schedule',
            ], 500);
        }
    }

    /**
     * Get generation history for logged-in user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistory(Request $request)
    {
        $query = Schedule::query();

        // Filter by current user if not admin
        if (!Auth::user()->hasRole('admin')) {
            $query->where('created_by', Auth::id());
        }

        // Filter by program if specified
        if ($request->has('program_id')) {
            $query->where('program_id', $request->input('program_id'));
        }

        $schedules = $query->latest('created_at')
            ->paginate(15);

        return response()->json([
            'schedules' => $schedules->map(fn($s) => [
                'id' => $s->id,
                'date' => $s->created_at->format('M d, Y H:i'),
                'program' => $s->program->name,
                'fitness_score' => $s->fitness_score,
                'conflicts' => count($s->conflicts ?? []),
                'status' => ucfirst($s->status),
            ]),
            'pagination' => [
                'current_page' => $schedules->currentPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    /**
     * Export schedule to PDF
     * 
     * @param int $scheduleId
     * @return \Illuminate\Http\Response
     */
    public function exportPDF($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->authorize('view', $schedule);

        // TODO: Implement PDF generation using DOMPDF or similar library
        // $pdf = PDF::loadView('schedules.pdf', ['schedule' => $schedule]);
        // return $pdf->download("schedule-{$schedule->id}.pdf");

        // Placeholder
        return response()->json([
            'message' => 'PDF export coming soon',
        ]);
    }

    /**
     * Export schedule to CSV
     * 
     * @param int $scheduleId
     * @return \Illuminate\Http\Response
     */
    public function exportCSV($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->authorize('view', $schedule);

        $items = $schedule->scheduleItems;

        // Generate CSV content
        $headers = ['Subject', 'Instructor', 'Room', 'Day', 'Time', 'Type', 'Duration'];
        $rows = $items->map(function ($item) {
            return [
                $item->subject_code,
                $item->instructor_name,
                $item->room_number,
                $item->day_of_week,
                $item->start_time,
                $item->class_type,
                $item->duration,
            ];
        })->toArray();

        $csv = "Subject,Instructor,Room,Day,Time,Type,Duration\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($cell) => "\"$cell\"", $row)) . "\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"schedule-{$scheduleId}.csv\"");
    }

    /**
     * Get academic years for dropdown
     * 
     * @return array
     */
    private function getAcademicYears()
    {
        return AcademicYear::orderBy('start_year', 'desc')->get();
    }

    /**
     * Get year levels
     * 
     * @return array
     */
    private function getYearLevels()
    {
        return [
            ['value' => 1, 'label' => '1st Year'],
            ['value' => 2, 'label' => '2nd Year'],
            ['value' => 3, 'label' => '3rd Year'],
            ['value' => 4, 'label' => '4th Year'],
        ];
    }
}
