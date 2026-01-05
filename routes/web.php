<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;

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
