<?php

/**
 * Schedule Generation Routes
 * 
 * Add these routes to your routes/web.php or routes/api.php
 * 
 * WEB ROUTES (resources/views/web.php)
 * ===================================
 */

use App\Http\Controllers\ScheduleGenerationController;

// Web routes - UI views
Route::middleware(['auth', 'verified'])->group(function () {
    // Schedule Generation UI
    Route::get('/schedule-generation', [ScheduleGenerationController::class, 'index'])
        ->name('schedule-generation.index')
        ->middleware('can:generate-schedule');

    // Schedule Preview/Edit (existing schedules)
    Route::get('/schedules/{schedule}', [ScheduleGenerationController::class, 'show'])
        ->name('schedules.show')
        ->middleware('can:view,schedule');

    // History page
    Route::get('/schedule-generation/history', [ScheduleGenerationController::class, 'history'])
        ->name('schedule-generation.history');
});

/**
 * API ROUTES (routes/api.php)
 * ==========================
 */

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Generate new schedule
    Route::post('/schedule-generation/generate', [ScheduleGenerationController::class, 'generateSchedule'])
        ->name('api.schedule-generation.generate')
        ->middleware('can:generate-schedule');

    // Get generation progress
    Route::get('/schedule-generation/{scheduleId}/progress', [ScheduleGenerationController::class, 'getProgress'])
        ->name('api.schedule-generation.progress');

    // Complete generation (backend callback)
    Route::post('/schedule-generation/{scheduleId}/complete', [ScheduleGenerationController::class, 'completeGeneration'])
        ->name('api.schedule-generation.complete')
        ->middleware('can:update,schedule');

    // Approve schedule (Program Head only)
    Route::post('/schedule-generation/{scheduleId}/approve', [ScheduleGenerationController::class, 'approveSchedule'])
        ->name('api.schedule-generation.approve')
        ->middleware('can:approve,schedule');

    // Get generation history
    Route::get('/schedule-generation/history', [ScheduleGenerationController::class, 'getHistory'])
        ->name('api.schedule-generation.history');

    // Export schedule
    Route::get('/schedule-generation/{scheduleId}/export/pdf', [ScheduleGenerationController::class, 'exportPDF'])
        ->name('api.schedule-generation.export-pdf')
        ->middleware('can:view,schedule');

    Route::get('/schedule-generation/{scheduleId}/export/csv', [ScheduleGenerationController::class, 'exportCSV'])
        ->name('api.schedule-generation.export-csv')
        ->middleware('can:view,schedule');
});

/**
 * WEBSOCKET ROUTES (routes/channels.php) - OPTIONAL
 * =================================================
 * 
 * For real-time progress updates during generation
 */

/*
Broadcast::channel('schedule.{scheduleId}', function ($user, $scheduleId) {
    $schedule = Schedule::findOrFail($scheduleId);
    return $user->can('view', $schedule) ? [] : false;
});

Broadcast::channel('schedule-generation-{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
*/
