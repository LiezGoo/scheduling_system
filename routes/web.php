<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Examples\NotificationExampleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProgramController;

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
| These routes are accessible only to guests (unauthenticated users).
| Authenticated users will be redirected to their respective dashboards.
*/

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
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

        // Program Management
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
    });

    // Department Head Dashboard
    Route::get('/department-head/dashboard', function() {
        return view('dashboards.department_head');
    })->middleware(['role:department_head'])->name('department-head.dashboard');

    // Program Head Dashboard
    Route::get('/program-head/dashboard', function() {
        return view('dashboards.program_head');
    })->middleware(['role:program_head'])->name('program-head.dashboard');

    // Instructor Dashboard
    Route::get('/instructor/dashboard', function() {
        return view('dashboards.instructor');
    })->middleware(['role:instructor'])->name('instructor.dashboard');

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
