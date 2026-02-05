<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Tests\TestCase;

/**
 * RBAC AUTHORIZATION TESTS
 *
 * Comprehensive test suite for role-based access control.
 *
 * Location: tests/Feature/RBACAuthorizationTest.php
 *
 * Run with:
 *     php artisan test --filter=RBAC
 */
class RBACAuthorizationTest extends TestCase
{
    protected $department;
    protected $departmentHead;
    protected $program;
    protected $programHead;
    protected $instructor;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->department = Department::factory()->create();
        $this->program = Program::factory()->create(['department_id' => $this->department->id]);

        // Create users
        $this->departmentHead = User::factory()
            ->create([
                'role' => User::ROLE_DEPARTMENT_HEAD,
                'department_id' => $this->department->id,
                'program_id' => null,
            ]);

        $this->programHead = User::factory()
            ->create([
                'role' => User::ROLE_PROGRAM_HEAD,
                'department_id' => null,
                'program_id' => $this->program->id,
            ]);

        $this->instructor = User::factory()
            ->create([
                'role' => User::ROLE_INSTRUCTOR,
                'program_id' => $this->program->id,
            ]);

        $this->student = User::factory()
            ->create([
                'role' => User::ROLE_STUDENT,
                'program_id' => $this->program->id,
            ]);
    }

    // ========================================
    // DEPARTMENT HEAD TESTS
    // ========================================

    /**
     * Test that department head can access their own dashboard
     */
    public function test_department_head_can_access_own_dashboard()
    {
        $this->actingAs($this->departmentHead)
            ->get("/admin/department/{$this->department->id}/dashboard")
            ->assertOk();
    }

    /**
     * Test that department head CANNOT access other department's dashboard
     */
    public function test_department_head_cannot_access_other_department()
    {
        $otherDept = Department::factory()->create();

        $this->actingAs($this->departmentHead)
            ->get("/admin/department/{$otherDept->id}/dashboard")
            ->assertForbidden();
    }

    /**
     * Test that department head can manage all programs in their department
     */
    public function test_department_head_can_manage_all_programs_in_department()
    {
        $this->assertTrue(
            $this->departmentHead->canAccessProgram($this->program)
        );

        $this->assertTrue(
            $this->authorize('view', $this->program)
        );
    }

    /**
     * Test that department head can create users
     */
    public function test_department_head_can_create_users()
    {
        $this->actingAs($this->departmentHead)
            ->post('/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_INSTRUCTOR,
                'program_id' => $this->program->id,
            ])
            ->assertCreated();
    }

    /**
     * Test that department head role requires department_id
     */
    public function test_department_head_must_have_department_id()
    {
        $user = User::factory()
            ->create([
                'role' => User::ROLE_DEPARTMENT_HEAD,
                'department_id' => null, // Invalid!
                'program_id' => null,
            ]);

        // Should be logged out by VerifyRoleIntegrity middleware
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    /**
     * Test that department head role CANNOT have program_id
     */
    public function test_department_head_cannot_have_program_id()
    {
        $user = User::factory()
            ->create([
                'role' => User::ROLE_DEPARTMENT_HEAD,
                'department_id' => $this->department->id,
                'program_id' => $this->program->id, // Invalid!
            ]);

        // Should be logged out by VerifyRoleIntegrity middleware
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    // ========================================
    // PROGRAM HEAD TESTS
    // ========================================

    /**
     * Test that program head can access their own program dashboard
     */
    public function test_program_head_can_access_own_program()
    {
        $this->actingAs($this->programHead)
            ->get("/program-head/program/{$this->program->id}/dashboard")
            ->assertOk();
    }

    /**
     * Test that program head CANNOT access sibling programs
     *
     * CRITICAL SECURITY TEST
     * This ensures program heads cannot access other programs in the same department
     */
    public function test_program_head_cannot_access_sibling_program()
    {
        $siblingProgram = Program::factory()
            ->create(['department_id' => $this->department->id]);

        $this->actingAs($this->programHead)
            ->get("/program-head/program/{$siblingProgram->id}/dashboard")
            ->assertForbidden();
    }

    /**
     * Test that program head CANNOT access programs in other departments
     */
    public function test_program_head_cannot_access_other_department_program()
    {
        $otherDept = Department::factory()->create();
        $otherProgram = Program::factory()->create(['department_id' => $otherDept->id]);

        $this->actingAs($this->programHead)
            ->get("/program-head/program/{$otherProgram->id}/dashboard")
            ->assertForbidden();
    }

    /**
     * Test that program head can ONLY access users in their program
     */
    public function test_program_head_can_only_access_users_in_program()
    {
        $userInProgram = User::factory()
            ->create(['program_id' => $this->program->id]);

        $this->assertTrue(
            $this->programHead->canAccessUser($userInProgram)
        );

        $otherProgram = Program::factory()
            ->create(['department_id' => $this->department->id]);
        $userInOtherProgram = User::factory()
            ->create(['program_id' => $otherProgram->id]);

        $this->assertFalse(
            $this->programHead->canAccessUser($userInOtherProgram)
        );
    }

    /**
     * Test that program head role requires program_id
     */
    public function test_program_head_must_have_program_id()
    {
        $user = User::factory()
            ->create([
                'role' => User::ROLE_PROGRAM_HEAD,
                'program_id' => null, // Invalid!
            ]);

        // Should be logged out by VerifyRoleIntegrity middleware
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    // ========================================
    // STUDENT TESTS
    // ========================================

    /**
     * Test that student role requires program_id
     */
    public function test_student_must_have_program_id()
    {
        $user = User::factory()
            ->create([
                'role' => User::ROLE_STUDENT,
                'program_id' => null, // Invalid!
            ]);

        // Should be logged out by VerifyRoleIntegrity middleware
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    // ========================================
    // POLICY AUTHORIZATION TESTS
    // ========================================

    /**
     * Test DepartmentPolicy::view
     */
    public function test_department_policy_view()
    {
        $this->assertTrue($this->departmentHead->can('view', $this->department));
        $this->assertFalse($this->programHead->can('view', $this->department));
        $this->assertFalse($this->instructor->can('view', $this->department));
        $this->assertFalse($this->student->can('view', $this->department));
    }

    /**
     * Test DepartmentPolicy::update
     */
    public function test_department_policy_update()
    {
        $this->assertTrue($this->departmentHead->can('update', $this->department));
        $this->assertFalse($this->programHead->can('update', $this->department));
        $this->assertFalse($this->instructor->can('update', $this->department));
        $this->assertFalse($this->student->can('update', $this->department));
    }

    /**
     * Test ProgramPolicy::view
     */
    public function test_program_policy_view()
    {
        $this->assertTrue($this->departmentHead->can('view', $this->program));
        $this->assertTrue($this->programHead->can('view', $this->program));
        $this->assertTrue($this->instructor->can('view', $this->program));
        $this->assertTrue($this->student->can('view', $this->program));
    }

    /**
     * Test ProgramPolicy::update
     */
    public function test_program_policy_update()
    {
        $this->assertTrue($this->departmentHead->can('update', $this->program));
        $this->assertTrue($this->programHead->can('update', $this->program));
        $this->assertFalse($this->instructor->can('update', $this->program));
        $this->assertFalse($this->student->can('update', $this->program));
    }

    /**
     * Test UserPolicy::view
     */
    public function test_user_policy_view()
    {
        // Everyone can view themselves
        $this->assertTrue($this->departmentHead->can('view', $this->departmentHead));
        $this->assertTrue($this->programHead->can('view', $this->programHead));

        // Department head can view users in their department
        $this->assertTrue($this->departmentHead->can('view', $this->programHead));
        $this->assertTrue($this->departmentHead->can('view', $this->instructor));

        // Program head can only view users in their program
        $this->assertTrue($this->programHead->can('view', $this->instructor));
        $this->assertFalse($this->programHead->can('view', $this->departmentHead));
    }

    /**
     * Test UserPolicy::update
     */
    public function test_user_policy_update()
    {
        // Everyone can update themselves
        $this->assertTrue($this->instructor->can('update', $this->instructor));

        // Department head can update users in their department
        $this->assertTrue($this->departmentHead->can('update', $this->instructor));

        // Program head can update users in their program
        $this->assertTrue($this->programHead->can('update', $this->instructor));

        // Others cannot update
        $this->assertFalse($this->instructor->can('update', $this->departmentHead));
        $this->assertFalse($this->student->can('update', $this->instructor));
    }

    /**
     * Test UserPolicy::assignRole
     */
    public function test_user_policy_assign_role()
    {
        // Only department head can assign roles
        $this->assertTrue(
            $this->departmentHead->can('assignRole', [$this->instructor, User::ROLE_STUDENT])
        );

        $this->assertFalse(
            $this->programHead->can('assignRole', [$this->instructor, User::ROLE_STUDENT])
        );

        $this->assertFalse(
            $this->instructor->can('assignRole', [$this->student, User::ROLE_STUDENT])
        );

        // Cannot promote to department_head
        $this->assertFalse(
            $this->departmentHead->can('assignRole', [$this->instructor, User::ROLE_DEPARTMENT_HEAD])
        );
    }

    // ========================================
    // ACCOUNT ACTIVE TESTS
    // ========================================

    /**
     * Test that deactivated users are logged out
     */
    public function test_deactivated_user_is_logged_out()
    {
        $this->departmentHead->deactivate();

        $this->actingAs($this->departmentHead)
            ->get('/dashboard')
            ->assertRedirect('/login')
            ->with('error');
    }

    // ========================================
    // QUERY SCOPING TESTS
    // ========================================

    /**
     * Test RBACAuthorizationService::scopedProgramsQuery
     */
    public function test_rbac_service_scoped_programs_query()
    {
        $service = new \App\Services\RBACAuthorizationService($this->departmentHead);
        $programs = $service->scopedProgramsQuery()->get();

        $this->assertEquals(1, $programs->count());
        $this->assertTrue($programs->contains($this->program));
    }

    /**
     * Test RBACAuthorizationService::scopedUsersQuery
     */
    public function test_rbac_service_scoped_users_query()
    {
        $service = new \App\Services\RBACAuthorizationService($this->departmentHead);
        $users = $service->scopedUsersQuery()->get();

        $this->assertTrue($users->contains($this->programHead));
        $this->assertTrue($users->contains($this->instructor));
        $this->assertTrue($users->contains($this->student));
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    /**
     * Test StoreUserRequest validates role configuration
     */
    public function test_store_user_request_validates_department_head_config()
    {
        $this->actingAs($this->departmentHead)
            ->post('/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_DEPARTMENT_HEAD,
                'program_id' => $this->program->id, // Invalid for department_head!
            ])
            ->assertInvalid(['program_id']);
    }

    /**
     * Test StoreUserRequest validates program belongs to department
     */
    public function test_store_user_request_validates_program_belongs_to_department()
    {
        $otherDept = Department::factory()->create();
        $otherProgram = Program::factory()->create(['department_id' => $otherDept->id]);

        $this->actingAs($this->departmentHead)
            ->post('/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_PROGRAM_HEAD,
                'department_id' => $this->department->id,
                'program_id' => $otherProgram->id, // Program not in this department!
            ])
            ->assertInvalid(['program_id']);
    }
}
