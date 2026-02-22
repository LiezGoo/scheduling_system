<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\InstructorLoad;
use App\Models\Program;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Subject;
use App\Models\User;
use App\Services\ScheduleGenerationService;
use Carbon\Carbon;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * ScheduleGenerationTest
 * 
 * Comprehensive test suite for the Genetic Algorithm schedule generation.
 * Tests constraint validation, conflict detection, and performance.
 * 
 * COVERAGE:
 * ✔ Schedule generation success
 * ✔ Room conflict detection
 * ✔ Instructor time conflict detection
 * ✔ Daily scheme compliance
 * ✔ Faculty load limits
 * ✔ Break time enforcement
 * ✔ Section time conflicts
 * ✔ Performance benchmarking
 * ✔ Stress testing with large datasets
 */
class ScheduleGenerationTest extends TestCase
{
    use RefreshDatabase;

    private ScheduleGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generationService = new ScheduleGenerationService();
    }

    /**
     * BASIC TESTS
     */

    /** @test */
    public function test_schedule_generates_successfully()
    {
        $this->seed(TestDataSeeder::class);

        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => $academicYear->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
        ]);

        $this->assertNotEmpty($result['schedule_id']);
        $this->assertIsInteger($result['schedule_id']);

        $schedule = Schedule::find($result['schedule_id']);
        $this->assertNotNull($schedule);
        $this->assertGreater($schedule->items()->count(), 0);
    }

    /** @test */
    public function test_no_room_conflicts()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()->get();

        // Check for room double-booking
        foreach ($items as $item1) {
            foreach ($items as $item2) {
                if ($item1->id === $item2->id) {
                    continue;
                }

                // Same room, same day
                if ($item1->room_id === $item2->room_id && $item1->day_of_week === $item2->day_of_week) {
                    // Times shouldn't overlap
                    $this->assertFalse(
                        $this->timesOverlap(
                            $item1->start_time,
                            $item1->end_time,
                            $item2->start_time,
                            $item2->end_time
                        ),
                        "Room {$item1->room->room_name} has conflicting bookings"
                    );
                }
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['room_conflicts'], 'Room conflicts detected');
    }

    /** @test */
    public function test_no_instructor_time_conflicts()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()->get();

        // Track instructor assignments per day
        $instructorSchedule = [];

        foreach ($items as $item) {
            $key = $item->instructor_id . '_' . $item->day_of_week;

            if (!isset($instructorSchedule[$key])) {
                $instructorSchedule[$key] = [];
            }

            $instructorSchedule[$key][] = [
                'start' => $item->start_time,
                'end' => $item->end_time,
            ];
        }

        // Check for overlaps
        foreach ($instructorSchedule as $dayKey => $times) {
            for ($i = 0; $i < count($times); $i++) {
                for ($j = $i + 1; $j < count($times); $j++) {
                    $this->assertFalse(
                        $this->timesOverlap($times[$i]['start'], $times[$i]['end'], $times[$j]['start'], $times[$j]['end']),
                        "Instructor has overlapping classes on $dayKey"
                    );
                }
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['instructor_conflicts'], 'Instructor conflicts detected');
    }

    /** @test */
    public function test_instructor_within_daily_scheme()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()
            ->with('instructor')
            ->get();

        foreach ($items as $item) {
            $instructor = $item->instructor;

            if ($instructor->daily_scheme_start && $instructor->daily_scheme_end) {
                $schemeStart = Carbon::parse($instructor->daily_scheme_start);
                $schemeEnd = Carbon::parse($instructor->daily_scheme_end);
                $classStart = Carbon::parse($item->start_time);
                $classEnd = Carbon::parse($item->end_time);

                $this->assertTrue(
                    $classStart->greaterThanOrEqualTo($schemeStart) && $classEnd->lessThanOrEqualTo($schemeEnd),
                    "Class for {$instructor->first_name} at " . $item->start_time . " is outside scheme"
                );
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['scheme_violations'], 'Scheme violations detected');
    }

    /** @test */
    public function test_faculty_load_not_exceeded()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()
            ->with(['subject', 'instructor'])
            ->get();

        $facultyLoads = $this->calculateFacultyLoads($items);

        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find($instructorId);
            $this->assertNotNull($instructor);

            if ($instructor->contract_type === User::CONTRACT_PERMANENT) {
                $this->assertLessThanOrEqual(
                    User::MAX_LECTURE_HOURS_PERMANENT,
                    $loads['lecture'],
                    "Instructor {$instructor->first_name} exceeds max lecture hours"
                );
                $this->assertLessThanOrEqual(
                    User::MAX_LAB_HOURS_PERMANENT,
                    $loads['lab'],
                    "Instructor {$instructor->first_name} exceeds max lab hours"
                );
            } elseif ($instructor->employment_type === User::EMPLOYMENT_CONTRACT_27) {
                $total = $loads['lecture'] + $loads['lab'];
                $this->assertLessThanOrEqual(
                    User::MAX_HOURS_CONTRACT_27,
                    $total,
                    "Contract instructor {$instructor->first_name} exceeds 27 hours"
                );
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['overload_violations'], 'Faculty overload violations detected');
    }

    /** @test */
    public function test_break_time_enforced()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()
            ->with('instructor')
            ->get();

        // Group by instructor and day
        foreach ($items->groupBy('instructor_id') as $instructorId => $instructorItems) {
            foreach ($instructorItems->groupBy('day_of_week') as $day => $dayItems) {
                $sorted = $dayItems->sortBy('start_time')->values();

                // Check max consecutive hours
                for ($i = 0; $i < $sorted->count(); $i++) {
                    $firstClass = $sorted[$i];
                    $classStart = Carbon::parse($firstClass->start_time);
                    $classEnd = Carbon::parse($firstClass->end_time);

                    $j = $i + 1;
                    $gapBetweenClasses = true;

                    // Keep extending while there's no 1-hour break
                    while ($j < $sorted->count()) {
                        $nextClass = $sorted[$j];
                        $nextStart = Carbon::parse($nextClass->start_time);
                        $nextEnd = Carbon::parse($nextClass->end_time);

                        $breakMinutes = $nextStart->diffInMinutes($classEnd);

                        if ($breakMinutes < 60) {
                            $classEnd = $nextEnd;
                            $j++;
                        } else {
                            $gapBetweenClasses = true;
                            break;
                        }
                    }

                    $consecutiveHours = $classStart->diffInHours($classEnd, true);
                    $this->assertLessThanOrEqual(
                        4.5, // Allow 4.5 hours to account for rounding
                        $consecutiveHours,
                        "Instructor has $consecutiveHours consecutive hours on $day"
                    );
                }
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['break_violations'], 'Break violations detected');
    }

    /** @test */
    public function test_sections_have_no_time_overlap()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();
        $items = $schedule->items()->get();

        // Group by section and day
        foreach ($items->groupBy('section') as $section => $sectionItems) {
            foreach ($sectionItems->groupBy('day_of_week') as $day => $dayItems) {
                foreach ($dayItems as $item1) {
                    foreach ($dayItems as $item2) {
                        if ($item1->id === $item2->id) {
                            continue;
                        }

                        // Same section, same day - times shouldn't overlap
                        $this->assertFalse(
                            $this->timesOverlap(
                                $item1->start_time,
                                $item1->end_time,
                                $item2->start_time,
                                $item2->end_time
                            ),
                            "Section {$section} has overlapping classes on {$day}"
                        );
                    }
                }
            }
        }

        // Use validation method
        $validation = $this->generationService->validateGeneratedSchedule($schedule);
        $this->assertEqual(0, $validation['section_conflicts'], 'Section conflicts detected');
    }

    /**
     * COMPREHENSIVE VALIDATION TEST
     */

    /** @test */
    public function test_comprehensive_schedule_validation()
    {
        $this->seed(TestDataSeeder::class);

        $schedule = $this->generateTestSchedule();

        $validation = $this->generationService->validateGeneratedSchedule($schedule);

        // All conflict counts should be zero
        $this->assertEqual(0, $validation['room_conflicts']);
        $this->assertEqual(0, $validation['instructor_conflicts']);
        $this->assertEqual(0, $validation['overload_violations']);
        $this->assertEqual(0, $validation['break_violations']);
        $this->assertEqual(0, $validation['scheme_violations']);
        $this->assertEqual(0, $validation['section_conflicts']);
        $this->assertTrue($validation['all_valid']);
    }

    /**
     * PERFORMANCE TESTS (PART 4)
     */

    /** @test */
    public function test_generation_time_under_limit()
    {
        $this->seed(TestDataSeeder::class);

        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $startTime = microtime(true);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => $academicYear->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
            'generations' => 50, // Reasonable number for testing
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(
            15, // 15 seconds threshold
            $executionTime,
            "Schedule generation took {$executionTime} seconds (exceeds 15 second threshold)"
        );

        echo "\n✓ Generation completed in " . round($executionTime, 2) . " seconds";
    }

    /** @test */
    public function test_generation_performance_metrics()
    {
        $this->seed(TestDataSeeder::class);

        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $startTime = microtime(true);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => $academicYear->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
            'population_size' => 30,
            'generations' => 50,
            'mutation_rate' => 15,
            'crossover_rate' => 80,
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $schedule = Schedule::find($result['schedule_id']);
        $itemCount = $schedule->items()->count();

        echo "\n✓ Performance Report:";
        echo "\n  - Execution Time: " . round($executionTime, 2) . "s";
        echo "\n  - Schedule Items: {$itemCount}";
        echo "\n  - Fitness Score: {$schedule->fitness_score}";
        echo "\n  - Items per Second: " . round($itemCount / $executionTime, 2);

        $this->assertNotEmpty($result);
    }

    /**
     * STRESS TESTS (PART 5)
     */

    /** @test */
    public function test_stress_test_large_dataset()
    {
        $this->seed(TestDataSeeder::class);

        // Create additional data to stress the algorithm
        $this->createLargeDataset();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first();

        $startTime = microtime(true);

        try {
            $result = $this->generationService->generateSchedule([
                'academic_year_id' => $academicYear->id,
                'semester' => 1,
                'program_id' => $program->id,
                'year_level' => 1,
                'block_section' => 'Block 1',
                'created_by' => $user->id,
                'population_size' => 40,
                'generations' => 100,
            ]);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Verify generation succeeded
            $this->assertNotEmpty($result['schedule_id']);

            $schedule = Schedule::find($result['schedule_id']);
            $this->assertNotNull($schedule);
            $this->assertGreater($schedule->items()->count(), 0);

            // Verify no infinite loops or crashes
            $this->assertLessThan(60, $executionTime, "Stress test exceeded 60 seconds");

            echo "\n✓ Stress Test Results:";
            echo "\n  - Execution Time: " . round($executionTime, 2) . "s";
            echo "\n  - Items Generated: {$schedule->items()->count()}";
            echo "\n  - Fitness Score: {$schedule->fitness_score}";
        } catch (\Exception $e) {
            $this->fail("Stress test failed: {$e->getMessage()}");
        }
    }

    /** @test */
    public function test_stress_test_no_crashes()
    {
        $this->seed(TestDataSeeder::class);
        $this->createLargeDataset();

        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first();

        try {
            for ($i = 0; $i < 3; $i++) {
                $result = $this->generationService->generateSchedule([
                    'academic_year_id' => $academicYear->id,
                    'semester' => 1,
                    'program_id' => $program->id,
                    'year_level' => 1,
                    'block_section' => 'Block ' . ($i + 1),
                    'created_by' => $user->id,
                ]);

                $this->assertNotEmpty($result['schedule_id']);
            }

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("Algorithm crashed during stress test: {$e->getMessage()}");
        }
    }

    /**
     * HELPER METHODS
     */

    /**
     * Generate a test schedule
     */
    private function generateTestSchedule(): Schedule
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => $academicYear->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
        ]);

        return Schedule::find($result['schedule_id']);
    }

    /**
     * Check if two times overlap
     */
    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $s1 = Carbon::parse($start1);
        $e1 = Carbon::parse($end1);
        $s2 = Carbon::parse($start2);
        $e2 = Carbon::parse($end2);

        return $s1->lessThan($e2) && $s2->lessThan($e1);
    }

    /**
     * Calculate faculty loads from schedule items
     */
    private function calculateFacultyLoads(Collection $items): array
    {
        $loads = [];

        foreach ($items as $item) {
            $instructorId = $item->instructor_id;
            if (!isset($loads[$instructorId])) {
                $loads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }

            $duration = Carbon::parse($item->start_time)->diffInHours(Carbon::parse($item->end_time), true);
            $subject = $item->subject;

            if ($subject->lecture_hours > 0 && $subject->lab_hours > 0) {
                $loads[$instructorId]['lecture'] += $duration / 2;
                $loads[$instructorId]['lab'] += $duration / 2;
            } elseif ($subject->lecture_hours > 0) {
                $loads[$instructorId]['lecture'] += $duration;
            } else {
                $loads[$instructorId]['lab'] += $duration;
            }
        }

        return $loads;
    }

    /**
     * Create large dataset for stress testing
     */
    private function createLargeDataset(): void
    {
        $department = Department::first();

        // Add 20+ more instructors
        for ($i = 0; $i < 20; $i++) {
            User::create([
                'first_name' => "StressInstructor",
                'last_name' => "Test-$i",
                'email' => "stress.instructor.$i@test.local",
                'role' => User::ROLE_INSTRUCTOR,
                'department_id' => $department->id,
                'is_active' => true,
                'contract_type' => User::CONTRACT_PERMANENT,
                'daily_scheme_start' => '07:00',
                'daily_scheme_end' => '19:00',
                'password' => bcrypt('Password123!'),
            ]);
        }

        // Add 50+ more subjects
        for ($i = 0; $i < 50; $i++) {
            Subject::create([
                'subject_code' => "STRESS$i",
                'subject_name' => "Stress Test Subject $i",
                'department_id' => $department->id,
                'lecture_hours' => rand(2, 3),
                'lab_hours' => rand(1, 3),
                'is_active' => true,
            ]);
        }

        // Add more rooms
        for ($i = 0; $i < 10; $i++) {
            Room::create([
                'room_code' => "STRESS-" . sprintf("%03d", $i),
                'room_name' => "Stress Room $i",
                'room_type' => ['Lecture', 'Laboratory'][rand(0, 1)],
            ]);
        }
    }
}
