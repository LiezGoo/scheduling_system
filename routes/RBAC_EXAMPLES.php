<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProgramHeadDashboardController;
use Illuminate\Support\Facades\Route;

/**
 * RBAC ROUTING EXAMPLES
 *
 * These routes demonstrate how to implement proper authorization and scoping
 * in your routing structure.
 *
 * Add these routes to routes/web.php or create routes/rbac.php and include it.
 *
 * MIDDLEWARE STACK (on all protected routes):
 * - auth: Ensure user is authenticated
 * - verified.role: Validate role configuration
 * - account.active: Ensure account is active
 */

// ========================================
// PUBLIC ROUTES (no auth)
// ========================================

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', 'LoginController@showLoginForm')->name('login');
    Route::post('/login', 'LoginController@login')->name('login.store');
});

// ========================================
// PROTECTED ROUTES (auth + role validation + account active)
// ========================================

Route::middleware(['auth', 'verified.role', 'account.active'])->group(function () {

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        return redirect('/login');
    })->name('logout');

    // ========================================
    // DEPARTMENT HEAD (ADMIN) ROUTES
    // ========================================

    Route::middleware('can:is-department-head')->group(function () {

        Route::prefix('admin/department/{department}')->name('admin.')->group(function () {

            // Main admin dashboard (single, scoped to department)
            Route::get('/dashboard', [AdminDashboardController::class, 'show'])
                ->name('dashboard');

            // Manage departments (in their scope)
            Route::get('/departments', [AdminDashboardController::class, 'departmentList'])
                ->name('departments.index');

            // Manage programs
            Route::get('/programs', [AdminDashboardController::class, 'programList'])
                ->name('programs.index');
            Route::get('/programs/{program}', 'ProgramController@show')
                ->name('programs.show');
            Route::get('/programs/{program}/edit', 'ProgramController@edit')
                ->name('programs.edit');
            Route::put('/programs/{program}', 'ProgramController@update')
                ->name('programs.update');
            Route::post('/programs', 'ProgramController@store')
                ->name('programs.store');
            Route::get('/programs/create', 'ProgramController@create')
                ->name('programs.create');

            // Manage users
            Route::get('/users', [AdminDashboardController::class, 'userList'])
                ->name('users.index');
            Route::get('/users/{user}', 'UserController@show')
                ->name('users.show');
            Route::get('/users/{user}/edit', 'UserController@edit')
                ->name('users.edit');
            Route::put('/users/{user}', 'UserController@update')
                ->name('users.update');
            Route::delete('/users/{user}', 'UserController@destroy')
                ->name('users.destroy');
            Route::post('/users', 'UserController@store')
                ->name('users.store');
            Route::get('/users/create', 'UserController@create')
                ->name('users.create');

            // Activate/Deactivate users
            Route::post('/users/{user}/activate', 'UserController@activate')
                ->name('users.activate');
            Route::post('/users/{user}/deactivate', 'UserController@deactivate')
                ->name('users.deactivate');

        });

    });

    // ========================================
    // PROGRAM HEAD ROUTES
    // ========================================

    Route::middleware('can:is-program-head')->group(function () {

        Route::prefix('program-head/program/{program}')->name('program-head.')->group(function () {

            // Program head dashboard (scoped to their program)
            Route::get('/dashboard', [ProgramHeadDashboardController::class, 'show'])
                ->name('dashboard');

            // Manage users in program
            Route::get('/users', [ProgramHeadDashboardController::class, 'userList'])
                ->name('users.index');
            Route::get('/users/{user}', 'UserController@show')
                ->name('users.show');

        });

    });

    // ========================================
    // INSTRUCTOR ROUTES
    // ========================================

    Route::middleware('can:is-instructor')->group(function () {

        Route::prefix('instructor')->name('instructor.')->group(function () {

            // Instructor dashboard
            Route::get('/dashboard', 'InstructorDashboardController@show')
                ->name('dashboard');

            // View teaching load
            Route::get('/teaching-load', 'InstructorDashboardController@teachingLoad')
                ->name('teaching-load');

            // View schedule
            Route::get('/schedule', 'InstructorDashboardController@schedule')
                ->name('schedule');

        });

    });

    // ========================================
    // STUDENT ROUTES
    // ========================================

    Route::middleware('can:is-student')->group(function () {

        Route::prefix('student')->name('student.')->group(function () {

            // Student schedule (scoped to their program)
            Route::get('/schedule', 'StudentDashboardController@schedule')
                ->name('schedule');

            // View curriculum
            Route::get('/curriculum', 'StudentDashboardController@curriculum')
                ->name('curriculum');

        });

    });

    // ========================================
    // SHARED ROUTES (all authenticated users)
    // ========================================

    // User profile
    Route::get('/profile', 'ProfileController@show')->name('profile.show');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');

    // Change password
    Route::get('/password/change', 'PasswordController@showChangeForm')->name('password.change');
    Route::post('/password/change', 'PasswordController@update')->name('password.update');

});

/**
 * SECURITY NOTES FOR ROUTING:
 *
 * 1. PARAMETER VALIDATION
 *    Always verify route parameters against authenticated user:
 *
 *    Route::get('/admin/department/{department}', ...)
 *
 *    In controller:
 *    if (auth()->user()->department_id !== $department) {
 *        abort(403);
 *    }
 *
 * 2. AUTHORIZATION CHECKS
 *    Use policies or gates:
 *
 *    $this->authorize('view', $department);
 *    or
 *    Gate::authorize('manage-department', $department);
 *
 * 3. QUERY SCOPING
 *    Always scope queries to authenticated user's permissions:
 *
 *    $programs = auth()->user()->getAccessiblePrograms();
 *    or
 *    $authService = new RBACAuthorizationService(auth()->user());
 *    $programs = $authService->scopedProgramsQuery()->get();
 *
 * 4. MIDDLEWARE ORDER
 *    1. auth - Ensure user is authenticated
 *    2. verified.role - Validate role configuration
 *    3. account.active - Ensure account is not deactivated
 *    4. can:gate - Check specific authorization
 *
 * 5. IMPLICIT MODEL BINDING
 *    When using Route::model('program', Program::class):
 *
 *    Verify the binding in the controller:
 *    if (!auth()->user()->canAccessProgram($program)) {
 *        abort(403);
 *    }
 *
 *    OR use a custom route binding:
 *
 *    Route::model('program', Program::class, function (Model $value) {
 *        return auth()->user()->canAccessProgram($value)
 *            ? $value
 *            : abort(403);
 *    });
 */
