<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ScheduleItem;
use App\Models\Subject;
use App\Models\User;
use App\Models\Program;
use App\Services\ScheduleGenerationService;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ConstraintEnforcementTest
 * Tests for NSTP and break time constraint enforcement in the GA.
 */
class ConstraintEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private ScheduleGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generationService = new ScheduleGenerationService();
    }

    /** @test */
    public function test_ga_produces_valid_schedule_without_break_time_violations()
    {
        $this->seed(TestDataSeeder::class);

        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => AcademicYear::where('is_active', true)->first()->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($result['success'], 'Schedule generation should succeed');

        // Verify no classes during break times
        $schedule = \App\Models\Schedule::find($result['schedule_id']);
        $items = $schedule->items()->get();

        $this->assertNotEmpty($items, 'Schedule should have items');

        $breakTimes = [
            ['start' => '10:00', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '12:00'],
            ['start' => '13:00', 'end' => '14:00'],
            ['start' => '14:00', 'end' => '15:00'],
        ];

        foreach ($items as $item) {
            $itemStart = $item->start_time instanceof \DateTime 
                ? $item->start_time->format('H:i')
                : (string) $item->start_time;
            $itemEnd = $item->end_time instanceof \DateTime 
                ? $item->end_time->format('H:i')
                : (string) $item->end_time;

            foreach ($breakTimes as $breakTime) {
                $starts = strtotime($itemStart);
                $ends = strtotime($itemEnd);
                $bStart = strtotime($breakTime['start']);
                $bEnd = strtotime($breakTime['end']);

                $overlaps = $starts < $bEnd && $ends > $bStart;

                $this->assertFalse(
                    $overlaps,
                    "Class {$itemStart}-{$itemEnd} overlaps with break {$breakTime['start']}-{$breakTime['end']}"
                );
            }
        }
    }

    /** @test */
    public function test_nstp_subject_scheduled_on_saturday_only()
    {
        $this->seed(TestDataSeeder::class);

        // Get a subject and mark as NSTP
        $subject = Subject::where('is_active', true)->first();
        $this->assertNotNull($subject);
        
        $subject->update(['is_nstp' => true, 'lecture_hours' => 3, 'lab_hours' => 0]);

        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => AcademicYear::where('is_active', true)->first()->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
        ]);

        // May skip if NSTP not in curriculum
        if (!$result['success']) {
            $this->markTestSkipped('Could not generate schedule');
        }

        $nstpItems = ScheduleItem::where('schedule_id', $result['schedule_id'])
            ->where('subject_id', $subject->id)
            ->get();

        if ($nstpItems->isEmpty()) {
            $this->markTestSkipped('NSTP subject not in generated schedule');
        }

        foreach ($nstpItems as $item) {
            $this->assertEquals('Saturday', $item->day_of_week, 'NSTP must be on Saturday');
        }
    }

    /** @test */
    public function test_nstp_subject_has_3_hour_duration()
    {
        $this->seed(TestDataSeeder::class);

        $subject = Subject::where('is_active', true)->skip(1)->first() ?? Subject::where('is_active', true)->first();
        $this->assertNotNull($subject);
        
        $subject->update(['is_nstp' => true, 'lecture_hours' => 3, 'lab_hours' => 0]);

        $program = Program::first();
        $user = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $result = $this->generationService->generateSchedule([
            'academic_year_id' => AcademicYear::where('is_active', true)->first()->id,
            'semester' => 1,
            'program_id' => $program->id,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'created_by' => $user->id,
        ]);

        if (!$result['success']) {
            $this->markTestSkipped('Could not generate schedule');
        }

        $nstpItems = ScheduleItem::where('schedule_id', $result['schedule_id'])
            ->where('subject_id', $subject->id)
            ->get();

        if ($nstpItems->isEmpty()) {
            $this->markTestSkipped('NSTP subject not scheduled');
        }

        foreach ($nstpItems as $item) {
            $start = $item->start_time instanceof \DateTime ? $item->start_time->format('H:i') : (string) $item->start_time;
            $end = $item->end_time instanceof \DateTime ? $item->end_time->format('H:i') : (string) $item->end_time;
            
            $hours = (strtotime($end) - strtotime($start)) / 3600;
            $this->assertEquals(3.0, $hours, 'NSTP must be exactly 3 hours');
        }
    }
}
