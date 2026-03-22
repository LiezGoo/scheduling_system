<?php

namespace Tests\Feature\DepartmentHead;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\GeneticScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class GenerateScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_can_generate_multiple_blocks(): void
    {
        [$departmentHead, $program, $academicYear, $semester] = $this->createGenerationContext();
        $this->seedGenerationPrerequisites($program->id, $academicYear->id, $semester->name, $departmentHead->department_id);

        $mock = Mockery::mock(GeneticScheduler::class);
        $mock->shouldReceive('generate')
            ->twice()
            ->andReturn(
                [
                    'success' => true,
                    'schedule_id' => 101,
                    'fitness_score' => 9250.5,
                    'metrics' => ['hard_conflicts' => 0],
                    'faculty_workloads' => [],
                ],
                [
                    'success' => true,
                    'schedule_id' => 102,
                    'fitness_score' => 9180.25,
                    'metrics' => ['hard_conflicts' => 0],
                    'faculty_workloads' => [
                        [
                            'faculty_id' => 77,
                            'status' => 'Overloaded',
                            'overload_hours' => 2,
                        ],
                    ],
                ]
            );

        $this->app->instance(GeneticScheduler::class, $mock);

        $response = $this->actingAs($departmentHead)->postJson(route('department-head.schedules.executeGeneration'), [
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
            'semester' => $semester->name,
            'year_level' => 1,
            'number_of_blocks' => 2,
            'population_size' => 80,
            'generations' => 120,
            'mutation_rate' => 15,
            'crossover_rate' => 80,
            'elite_size' => 5,
            'stagnation_limit' => 30,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_blocks', 2)
            ->assertJsonCount(2, 'data.generated_schedules');
    }

    public function test_non_department_head_cannot_generate_schedule(): void
    {
        [$department, $program, $academicYear, $semester] = $this->createBaseEntities();

        $user = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('department-head.schedules.executeGeneration'), [
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
            'semester' => $semester->name,
            'year_level' => 1,
            'number_of_blocks' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_schedule_audit_endpoint_returns_summary_for_department_head_schedule(): void
    {
        [$departmentHead, $program, $academicYear] = $this->createGenerationContextWithoutSemester();

        $schedule = Schedule::query()->create([
            'program_id' => $program->id,
            'created_by' => $departmentHead->id,
            'academic_year' => $academicYear->name,
            'semester' => '1st Semester',
            'year_level' => 1,
            'block' => 'Block 1',
            'status' => Schedule::STATUS_DRAFT,
            'fitness_score' => 9000,
        ]);

        $response = $this->actingAs($departmentHead)
            ->getJson(route('department-head.schedules.audit', ['schedule' => $schedule->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.schedule_id', $schedule->id)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hard_conflicts' => ['faculty_conflicts', 'room_conflicts', 'block_conflicts', 'total'],
                    'faculty_workloads',
                ],
            ]);
    }

    private function createGenerationContext(): array
    {
        [$department, $program, $academicYear, $semester] = $this->createBaseEntities();

        $departmentHead = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_HEAD,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        return [$departmentHead, $program, $academicYear, $semester];
    }

    private function createGenerationContextWithoutSemester(): array
    {
        [$department, $program, $academicYear] = $this->createBaseEntities(false);

        $departmentHead = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_HEAD,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        return [$departmentHead, $program, $academicYear];
    }

    private function createBaseEntities(bool $withSemester = true): array
    {
        $departmentId = DB::table('departments')->insertGetId([
            'department_code' => 'CICT',
            'department_name' => 'College of Information and Communication Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $department = Department::query()->findOrFail($departmentId);

        $program = Program::query()->create([
            'program_code' => 'BSIT',
            'program_name' => 'Bachelor of Science in Information Technology',
            'department_id' => $department->id,
        ]);

        $academicYear = AcademicYear::query()->create([
            'name' => '2026-2027',
            'start_year' => 2026,
            'end_year' => 2027,
            'is_active' => true,
        ]);

        if (!$withSemester) {
            return [$department, $program, $academicYear];
        }

        $semester = Semester::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => '1st Semester',
            'is_active' => true,
        ]);

        return [$department, $program, $academicYear, $semester];
    }

    private function seedGenerationPrerequisites(int $programId, int $academicYearId, string $semesterName, int $departmentId): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'department_id' => $departmentId,
        ]);

        $subject = Subject::query()->create([
            'subject_code' => 'IT101',
            'subject_name' => 'Intro to IT',
            'description' => 'Foundational computing concepts',
            'department_id' => $departmentId,
            'created_by' => $instructor->id,
            'units' => 3.0,
            'lecture_hours' => 3.0,
            'lab_hours' => 0.0,
            'is_active' => true,
        ]);

        DB::table('program_subjects')->insert([
            'program_id' => $programId,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => strtolower($semesterName),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('instructor_loads')->insert([
            'instructor_id' => $instructor->id,
            'program_id' => $programId,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYearId,
            'semester' => $semesterName,
            'year_level' => 1,
            'block_section' => 'Block 1',
            'lec_hours' => 3,
            'lab_hours' => 0,
            'total_hours' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rooms')->insert([
            [
                'room_code' => 'RM101',
                'room_name' => 'Lecture Room 101',
                'room_type' => 'Lecture',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_code' => 'LAB201',
                'room_name' => 'Computer Lab 201',
                'room_type' => 'Laboratory',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
