<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Subject;
use App\Services\FacultyLoadService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Faculty Load Management Tests
 *
 * Tests for the Faculty Load Management module functionality.
 */
class FacultyLoadTest extends TestCase
{
    use RefreshDatabase;

    protected FacultyLoadService $service;
    protected User $admin;
    protected User $instructor;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FacultyLoadService::class);

        // Create test users
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $this->instructor = User::factory()->create(['role' => User::ROLE_INSTRUCTOR, 'status' => 'active']);

        // Create test subject
        $this->subject = Subject::factory()->create();
    }

    /** @test */
    public function it_can_assign_subject_to_eligible_instructor()
    {
        $result = $this->service->assignSubjectToInstructor(
            userId: $this->instructor->id,
            subjectId: $this->subject->id,
            maxSections: 3,
            maxLoadUnits: 12
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('faculty_subjects', [
            'user_id' => $this->instructor->id,
            'subject_id' => $this->subject->id,
            'max_sections' => 3,
            'max_load_units' => 12,
        ]);
    }

    /** @test */
    public function it_prevents_assigning_subject_to_admin()
    {
        $result = $this->service->assignSubjectToInstructor(
            userId: $this->admin->id,
            subjectId: $this->subject->id,
            maxSections: 3
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not an eligible instructor', $result['message']);
    }

    /** @test */
    public function it_prevents_duplicate_assignments()
    {
        $this->instructor->facultySubjects()->attach($this->subject->id, [
            'max_sections' => 3,
        ]);

        $result = $this->service->assignSubjectToInstructor(
            userId: $this->instructor->id,
            subjectId: $this->subject->id,
            maxSections: 3
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already assigned', $result['message']);
    }

    /** @test */
    public function it_can_update_load_constraints()
    {
        $this->instructor->facultySubjects()->attach($this->subject->id, [
            'max_sections' => 3,
            'max_load_units' => 12,
        ]);

        $result = $this->service->updateLoadConstraints(
            userId: $this->instructor->id,
            subjectId: $this->subject->id,
            maxSections: 5,
            maxLoadUnits: 15
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('faculty_subjects', [
            'user_id' => $this->instructor->id,
            'subject_id' => $this->subject->id,
            'max_sections' => 5,
            'max_load_units' => 15,
        ]);
    }

    /** @test */
    public function it_can_remove_subject_assignment()
    {
        $this->instructor->facultySubjects()->attach($this->subject->id, [
            'max_sections' => 3,
        ]);

        $result = $this->service->removeSubjectAssignment(
            userId: $this->instructor->id,
            subjectId: $this->subject->id
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('faculty_subjects', [
            'user_id' => $this->instructor->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    /** @test */
    public function it_can_get_instructor_subjects()
    {
        $subject2 = Subject::factory()->create();

        $this->instructor->facultySubjects()->attach([
            $this->subject->id => ['max_sections' => 3],
            $subject2->id => ['max_sections' => 2],
        ]);

        $subjects = $this->service->getInstructorSubjects($this->instructor->id);

        $this->assertCount(2, $subjects);
        $this->assertTrue($subjects->contains($this->subject));
        $this->assertTrue($subjects->contains($subject2));
    }

    /** @test */
    public function it_can_get_subject_instructors()
    {
        $instructor2 = User::factory()->create(['role' => User::ROLE_PROGRAM_HEAD, 'status' => 'active']);

        $this->instructor->facultySubjects()->attach($this->subject->id, ['max_sections' => 3]);
        $instructor2->facultySubjects()->attach($this->subject->id, ['max_sections' => 2]);

        $instructors = $this->service->getSubjectInstructors($this->subject->id);

        $this->assertCount(2, $instructors);
        $this->assertTrue($instructors->contains($this->instructor));
        $this->assertTrue($instructors->contains($instructor2));
    }

    /** @test */
    public function it_can_identify_unassigned_instructors()
    {
        $instructor2 = User::factory()->create(['role' => User::ROLE_DEPARTMENT_HEAD, 'status' => 'active']);

        // Assign first instructor to a subject
        $this->instructor->facultySubjects()->attach($this->subject->id, ['max_sections' => 3]);

        $unassigned = $this->service->getUnassignedInstructors();

        $this->assertCount(1, $unassigned);
        $this->assertTrue($unassigned->contains($instructor2));
        $this->assertFalse($unassigned->contains($this->instructor));
    }

    /** @test */
    public function it_can_get_faculty_load_summary()
    {
        $instructor2 = User::factory()->create(['role' => User::ROLE_PROGRAM_HEAD, 'status' => 'active']);
        $subject2 = Subject::factory()->create();

        // Assign subjects
        $this->instructor->facultySubjects()->attach($this->subject->id, ['max_sections' => 3]);
        $instructor2->facultySubjects()->attach([
            $this->subject->id => ['max_sections' => 2],
            $subject2->id => ['max_sections' => 3],
        ]);

        $summary = $this->service->getFacultyLoadSummary();

        $this->assertEquals(2, $summary['total_eligible_instructors']);
        $this->assertEquals(2, $summary['instructors_with_assignments']);
        $this->assertEquals(0, $summary['instructors_without_assignments']);
        $this->assertEquals(3, $summary['total_faculty_assignments']);
    }

    /** @test */
    public function it_correctly_identifies_eligible_instructors()
    {
        $eligible = User::eligibleInstructors()->get();

        $this->assertTrue($eligible->contains($this->instructor));
        $this->assertFalse($eligible->contains($this->admin));
    }

    /** @test */
    public function it_can_check_instructor_eligibility()
    {
        $this->assertTrue($this->instructor->isEligibleInstructor());
        $this->assertFalse($this->admin->isEligibleInstructor());
    }
}
