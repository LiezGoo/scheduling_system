<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AccountDeactivatedController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Examples\NotificationExampleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\FacultyLoadController;
use App\Http\Controllers\Admin\ProgramSubjectController;
use App\Http\Controllers\ProgramHead\SubjectController as ProgramHeadSubjectController;
use App\Http\Controllers\ProgramHead\CurriculumController as ProgramHeadCurriculumController;
use App\Http\Controllers\ProgramHead\FacultyLoadController as ProgramHeadFacultyLoadController;
use App\Http\Controllers\ProgramHead\ScheduleController as ProgramHeadScheduleController;
use App\Http\Controllers\DepartmentHead\ScheduleReviewController as DepartmentHeadScheduleReviewController;

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

    // Example Notification Routes (for testing - remove in production)
    Route::prefix('examples/notifications')->name('examples.notifications.')->group(function () {
        Route::post('/test', [NotificationExampleController::class, 'testNotification'])->name('test');
        Route::post('/schedule-created', [NotificationExampleController::class, 'scheduleCreated'])->name('schedule-created');
        Route::post('/request-approved', [NotificationExampleController::class, 'requestApproved'])->name('request-approved');
        Route::post('/notify-instructors', [NotificationExampleController::class, 'notifyAllInstructors'])->name('notify-instructors');
        Route::post('/notify-by-role', [NotificationExampleController::class, 'notifyByRole'])->name('notify-by-role');
        Route::post('/custom', [NotificationExampleController::class, 'sendCustomNotification'])->name('custom');
    });

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

        // Schedule Generation
        Route::get('/schedule-generation', function() {
            return view('admin.schedule-generation.index');
        })->name('schedule-generation.index');
    });

    // Department Head Dashboard
    Route::get('/department-head/dashboard', function() {
        return view('dashboards.department_head');
    })->middleware(['role:department_head'])->name('department-head.dashboard');

    // Department Head Schedule Review
    Route::middleware(['role:department_head'])->prefix('department-head')->name('department-head.')->group(function () {
        Route::get('/schedules', [DepartmentHeadScheduleReviewController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/{schedule}', [DepartmentHeadScheduleReviewController::class, 'show'])->name('schedules.show');
        Route::post('/schedules/{schedule}/approve', [DepartmentHeadScheduleReviewController::class, 'approve'])->name('schedules.approve');
        Route::post('/schedules/{schedule}/reject', [DepartmentHeadScheduleReviewController::class, 'reject'])->name('schedules.reject');
    });

    // Program Head Dashboard
    Route::get('/program-head/dashboard', function() {
        return view('dashboards.program_head');
    })->middleware(['role:program_head'])->name('program-head.dashboard');

    // Program Head Routes - Scoped to their assigned program
    Route::middleware(['role:program_head'])->prefix('program-head')->name('program-head.')->group(function () {

        // Subject Management
        Route::get('/subjects', [ProgramHeadSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [ProgramHeadSubjectController::class, 'store'])->name('subjects.store');
        Route::get('/subjects/{subject}', [ProgramHeadSubjectController::class, 'show'])->name('subjects.show');
        Route::put('/subjects/{subject}', [ProgramHeadSubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [ProgramHeadSubjectController::class, 'destroy'])->name('subjects.destroy');

        // Curriculum Management
        Route::get('/curriculum', [ProgramHeadCurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('/curriculum', [ProgramHeadCurriculumController::class, 'store'])->name('curriculum.store');

        // Faculty Load Management
        Route::get('/faculty-load', [ProgramHeadFacultyLoadController::class, 'index'])->name('faculty-load.index');
        Route::get('/faculty-load/{facultyLoadId}/details', [ProgramHeadFacultyLoadController::class, 'getDetails'])->name('faculty-load.details');
        Route::post('/faculty-load/assign', [ProgramHeadFacultyLoadController::class, 'assignSubject'])->name('faculty-load.assign');
        Route::post('/faculty-load/update-constraints', [ProgramHeadFacultyLoadController::class, 'updateConstraints'])->name('faculty-load.update-constraints');
        Route::post('/faculty-load/remove', [ProgramHeadFacultyLoadController::class, 'removeAssignment'])->name('faculty-load.remove');

        // Schedule Management
        Route::get('/schedules', [ProgramHeadScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/create', [ProgramHeadScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/schedules', [ProgramHeadScheduleController::class, 'store'])->name('schedules.store');
        Route::get('/schedules/{schedule}', [ProgramHeadScheduleController::class, 'show'])->name('schedules.show');
        Route::get('/schedules/{schedule}/edit', [ProgramHeadScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('/schedules/{schedule}', [ProgramHeadScheduleController::class, 'update'])->name('schedules.update');
        Route::post('/schedules/{schedule}/submit', [ProgramHeadScheduleController::class, 'submit'])->name('schedules.submit');
        Route::delete('/schedules/{schedule}', [ProgramHeadScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // Instructor Dashboard
    Route::get('/instructor/dashboard', function() {
        return view('dashboards.instructor');
    })->middleware(['role:instructor'])->name('instructor.dashboard');

    Route::get('/instructor/my-schedule', function () {
    return view('instructor.my-schedule');
    })->name('instructor.my-schedule.index');

    Route::get('/instructor/my-loads', function () {
    return view('instructor.my-loads');
    })->name('instructor.my-loads.index');

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
