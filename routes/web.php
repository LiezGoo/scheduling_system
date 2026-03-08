<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AccountDeactivatedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\FacultyLoadController;
use App\Http\Controllers\Admin\ProgramSubjectController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\SemesterController as AdminSemesterController;
use App\Http\Controllers\Api\SemesterController as ApiSemesterController;
use App\Http\Controllers\ProgramHead\CurriculumController as ProgramHeadCurriculumController;
use App\Http\Controllers\ProgramHead\FacultyLoadController as ProgramHeadFacultyLoadController;
use App\Http\Controllers\ProgramHead\FacultyWorkloadConfigurationController as ProgramHeadFacultyWorkloadConfigurationController;
use App\Http\Controllers\ProgramHead\ScheduleController as ProgramHeadScheduleController;
use App\Http\Controllers\DepartmentHead\SubjectController as DepartmentHeadSubjectController;
use App\Http\Controllers\DepartmentHead\ScheduleController as DepartmentHeadScheduleController;
use App\Http\Controllers\DepartmentHead\ScheduleReviewController as DepartmentHeadScheduleReviewController;
use App\Http\Controllers\DepartmentHead\ScheduleAdjustmentController;
use App\Http\Controllers\DepartmentHead\ScheduleConfigurationController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
| These routes are accessible without authentication.
| Account deactivation page is explicitly available to show message
| to deactivated users without middleware interference.
*/

Route::get('/account-deactivated', [AccountDeactivatedController::class, 'show'])->name('account-deactivated');

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
| These routes are accessible only to guests (unauthenticated users).
| Authenticated users will be redirected to their respective dashboards.
*/

Route::middleware(['guest'])->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Registration Routes
    Route::get('/register', [RegistrationController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegistrationController::class, 'register'])->name('register.store');

    // Google OAuth Routes
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

    /**
     * Password Reset Routes
     *
     * These routes allow unauthenticated users to request and complete password resets.
     * Access is restricted to guests only via middleware.
     * Deactivated users are prevented from resetting passwords via controller logic.
     */
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| These routes require authentication and will redirect guests to login.
*/

Route::middleware(['auth'])->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Dynamic semester API for dropdowns
    Route::get('/api/semesters', [ApiSemesterController::class, 'index'])->name('api.semesters.index');

    // Generic dashboard - redirects to role-based dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        $redirectPath = match($user->role ?? 'student') {
            'admin' => '/admin/dashboard',
            'department_head' => '/department-head/dashboard',
            'program_head' => '/program-head/dashboard',
            'instructor' => '/instructor/dashboard',
            default => '/student/dashboard',
        };
        return redirect($redirectPath);
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Role-Based Dashboard Routes
    |--------------------------------------------------------------------------
    | Each role has a specific dashboard with appropriate middleware.
    */

    // Admin Dashboard
    Route::get('/admin/dashboard', function() {
        return view('dashboards.admin');
    })->middleware(['role:admin'])->name('admin.dashboard');

    // Admin User Management & Program Management Routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        
        // User Approval Routes (MUST be before /users/{user} to avoid route parameter conflict)
        Route::get('/users/approvals', [UserApprovalController::class, 'index'])->name('users.approvals');
        Route::get('/users/approval/pending-count', [UserApprovalController::class, 'getPendingCount'])->name('users.approval.count');
        Route::post('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
        
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Department Management
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // Program Management
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::get('/programs/{program}', [ProgramController::class, 'show'])->name('programs.show');
        Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');

        // Curriculum Management
        Route::get('/curriculum', [ProgramSubjectController::class, 'index'])->name('curriculum.index');
        Route::post('/curriculum', [ProgramSubjectController::class, 'store'])->name('curriculum.store');

        // Subject Management
        Route::get('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'store'])->name('subjects.store');
        Route::get('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'show'])->name('subjects.show');
        Route::put('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [\App\Http\Controllers\Admin\SubjectController::class, 'destroy'])->name('subjects.destroy');

        // Room Management
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        // Academic Year & Semester Management
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::get('/academic-years/{academicYear}', [AcademicYearController::class, 'show'])->name('academic-years.show');
        Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('academic-years.update');
        Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('academic-years.destroy');
        Route::post('/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');

        // Semester Management
        Route::get('/semesters', [AdminSemesterController::class, 'index'])->name('semesters.index');
        Route::post('/semesters', [AdminSemesterController::class, 'store'])->name('semesters.store');
        Route::put('/semesters/{semester}', [AdminSemesterController::class, 'update'])->name('semesters.update');
        Route::patch('/semesters/{semester}/toggle-status', [AdminSemesterController::class, 'toggleStatus'])->name('semesters.toggle-status');
        Route::delete('/semesters/{semester}', [AdminSemesterController::class, 'destroy'])->name('semesters.destroy');
        
        // Legacy semester endpoints (kept for backward compatibility)
        Route::post('/academic-years/{academicYear}/semesters', [AdminSemesterController::class, 'store'])->name('academic-years.semesters.store');
        Route::post('/semesters/{semester}/activate', [AdminSemesterController::class, 'toggleStatus'])->name('semesters.activate');

        // Faculty Load Management
        Route::get('/faculty-load', [FacultyLoadController::class, 'index'])->name('faculty-load.index');
        Route::get('/faculty-load/{facultyLoadId}/details', [FacultyLoadController::class, 'getDetails'])->name('faculty-load.details');
        Route::get('/faculty-load/{user}', [FacultyLoadController::class, 'show'])->name('faculty-load.show');
        Route::post('/faculty-load/assign', [FacultyLoadController::class, 'assignSubject'])->name('faculty-load.assign');
        Route::post('/faculty-load/update-constraints', [FacultyLoadController::class, 'updateConstraints'])->name('faculty-load.update-constraints');
        Route::post('/faculty-load/remove', [FacultyLoadController::class, 'removeAssignment'])->name('faculty-load.remove');
        Route::get('/faculty-load/api/unassigned', [FacultyLoadController::class, 'getUnassignedInstructors'])->name('faculty-load.api.unassigned');
        Route::get('/faculty-load/api/subject/{subject}/instructors', [FacultyLoadController::class, 'getSubjectInstructors'])->name('faculty-load.api.subject-instructors');
        Route::get('/faculty-load/api/summary', [FacultyLoadController::class, 'getSummary'])->name('faculty-load.api.summary');
        Route::get('/faculty-load/api/assignment-context', [FacultyLoadController::class, 'getAssignmentContext'])->name('faculty-load.api.assignment-context');
    Route::get('/faculty-load/api/workload-configuration', [FacultyLoadController::class, 'getFacultyWorkloadConfiguration'])->name('faculty-load.api.workload-configuration');

        // Schedule Generation
        Route::get('/schedule-generation', function() {
            return view('admin.schedule-generation.index');
        })->name('schedule-generation.index');
    });

    // Department Head Dashboard
    Route::get('/department-head/dashboard', function() {
        return view('dashboards.department_head');
    })->middleware(['role:department_head'])->name('department-head.dashboard');

    // Department Head Routes
    Route::middleware(['role:department_head'])->prefix('department-head')->name('department-head.')->group(function () {
        // Schedule Configurations
        Route::post('/schedule-configurations', [ScheduleConfigurationController::class, 'store'])->name('schedule-configurations.store');
        Route::get('/schedule-configurations', [ScheduleConfigurationController::class, 'index'])->name('schedule-configurations.index');

        // Subject Management
        Route::get('/subjects', [DepartmentHeadSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [DepartmentHeadSubjectController::class, 'store'])->name('subjects.store');
        Route::get('/subjects/{subject}', [DepartmentHeadSubjectController::class, 'show'])->name('subjects.show');
        Route::put('/subjects/{subject}', [DepartmentHeadSubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [DepartmentHeadSubjectController::class, 'destroy'])->name('subjects.destroy');

        // Schedule Generation & Management
        Route::get('/schedules', [DepartmentHeadScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/generate', [DepartmentHeadScheduleController::class, 'generate'])->name('schedules.generate');
        Route::post('/schedules/generate', [DepartmentHeadScheduleController::class, 'executeGeneration'])->name('schedules.executeGeneration');
        Route::get('/schedules/{schedule}', [DepartmentHeadScheduleController::class, 'show'])->name('schedules.show');
        Route::get('/schedules/{schedule}/edit', [ScheduleAdjustmentController::class, 'edit'])->name('schedules.edit');
        Route::put('/schedules/{schedule}/items/{item}', [ScheduleAdjustmentController::class, 'updateItem'])->name('schedules.items.update');
        Route::post('/schedules/{schedule}/finalize', [DepartmentHeadScheduleController::class, 'finalize'])->name('schedules.finalize');
        Route::delete('/schedules/{schedule}', [DepartmentHeadScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Adjustment Requests Management
        Route::get('/schedules/{schedule}/adjustments', [ScheduleAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('/schedules/{schedule}/adjustments/{request}', [ScheduleAdjustmentController::class, 'show'])->name('adjustments.show');
        Route::post('/schedules/{schedule}/adjustments/{request}/approve', [ScheduleAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('/schedules/{schedule}/adjustments/{request}/reject', [ScheduleAdjustmentController::class, 'reject'])->name('adjustments.reject');
    });

    // Program Head Dashboard
    Route::get('/program-head/dashboard', function() {
        return view('dashboards.program_head');
    })->middleware(['role:program_head'])->name('program-head.dashboard');

    // Program Head Routes - Scoped to their assigned program
    Route::middleware(['role:program_head'])->prefix('program-head')->name('program-head.')->group(function () {

        // Curriculum Management
        Route::get('/curriculum', [ProgramHeadCurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('/curriculum', [ProgramHeadCurriculumController::class, 'store'])->name('curriculum.store');

        // Faculty Load Management
        Route::get('/faculty-load', [ProgramHeadFacultyLoadController::class, 'index'])->name('faculty-load.index');
        Route::get('/faculty-load/{facultyLoadId}/details', [ProgramHeadFacultyLoadController::class, 'getDetails'])->name('faculty-load.details');
        Route::get('/faculty-load/api/assignable-subjects', [ProgramHeadFacultyLoadController::class, 'getAssignableSubjects'])->name('faculty-load.api.assignable-subjects');
        Route::post('/faculty-load/assign', [ProgramHeadFacultyLoadController::class, 'assignSubject'])->name('faculty-load.assign');
        Route::post('/faculty-load/update-constraints', [ProgramHeadFacultyLoadController::class, 'updateConstraints'])->name('faculty-load.update-constraints');
        Route::post('/faculty-load/remove', [ProgramHeadFacultyLoadController::class, 'removeAssignment'])->name('faculty-load.remove');

        // Faculty Workload Configuration
        Route::resource('faculty-workload-configurations', ProgramHeadFacultyWorkloadConfigurationController::class);
        Route::get('/faculty-workload-configurations/{faculty_workload_configuration}/edit', [ProgramHeadFacultyWorkloadConfigurationController::class, 'edit'])->name('faculty-workload-configurations.edit');
        Route::post('/faculty-workload-configurations/get-faculty-department', [ProgramHeadFacultyWorkloadConfigurationController::class, 'getFacultyDepartment'])->name('faculty-workload-configurations.get-faculty-department');

        // Schedule Management (View-Only with Adjustment Requests)
        Route::get('/schedules', [ProgramHeadScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/{schedule}', [ProgramHeadScheduleController::class, 'show'])->name('schedules.show');
        Route::post('/schedules/{schedule}/adjustments', [ScheduleAdjustmentController::class, 'store'])->name('schedules.adjustments.store');
    });

    // Instructor Dashboard
    Route::get('/instructor/dashboard', function() {
        return view('dashboards.instructor');
    })->middleware(['role:instructor'])->name('instructor.dashboard');

    // Instructor Pages
    Route::get('/instructor/my-loads', function() {
        return view('instructor.my-loads');
    })->middleware(['role:instructor'])->name('instructor.my-loads');

    Route::get('/instructor/my-schedule', function() {
        return view('instructor.my-schedule');
    })->middleware(['role:instructor'])->name('instructor.my-schedule');

    // Student Dashboard
    Route::get('/student/dashboard', function() {
        return view('dashboards.student');
    })->middleware(['role:student'])->name('student.dashboard');
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
| Handle 404 errors for undefined routes.
*/

Route::fallback(function () {
    return ('Page is not found. Please try again.');
});
