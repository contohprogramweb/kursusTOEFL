<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes - Student Dashboard (FR-3.6.1)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Dasbor Siswa
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');

    // Refresh dashboard cache
    Route::post('/student/dashboard/refresh', [StudentDashboardController::class, 'refresh'])
        ->name('student.dashboard.refresh');

    // Placeholder routes untuk quick actions (akan diimplementasikan nanti)
    Route::get('/practice/start', function() {
        return redirect()->route('student.dashboard')->with('info', 'Fitur latihan akan segera tersedia.');
    })->name('practice.start');

    Route::get('/simulation/start', function() {
        return redirect()->route('student.dashboard')->with('info', 'Fitur simulasi akan segera tersedia.');
    })->name('simulation.start');

    Route::get('/module/resume/{id}', function($id) {
        return redirect()->route('student.dashboard')->with('info', 'Fitur modul akan segera tersedia.');
    })->name('module.resume');

    // Study Plan routes - FR-3.2.4, FR-3.6.3
    Route::get('/study-plan/create', [App\Http\Controllers\StudyPlanController::class, 'create'])
        ->name('study-plan.create');

    Route::post('/study-plan', [App\Http\Controllers\StudyPlanController::class, 'store'])
        ->name('study-plan.store');

    Route::get('/study-plan/{studyPlan}', [App\Http\Controllers\StudyPlanController::class, 'show'])
        ->name('study-plan.show');

    Route::post('/study-plan/{studyPlan}/regenerate', [App\Http\Controllers\StudyPlanController::class, 'regenerate'])
        ->name('study-plan.regenerate');

    Route::get('/study-plan/{studyPlan}/calendar', [App\Http\Controllers\StudyPlanController::class, 'calendarData'])
        ->name('study-plan.calendar');

    Route::post('/study-plan/{studyPlan}/reminder', [App\Http\Controllers\StudyPlanController::class, 'sendReminder'])
        ->name('study-plan.reminder');

    // Task management
    Route::post('/study-plan/task/{task}/complete', [App\Http\Controllers\StudyPlanController::class, 'completeTask'])
        ->name('study-plan.task.complete');

    Route::post('/study-plan/task/{task}/uncomplete', [App\Http\Controllers\StudyPlanController::class, 'uncompleteTask'])
        ->name('study-plan.task.uncomplete');

    Route::post('/study-plan/task/{task}/adjust', [App\Http\Controllers\StudyPlanController::class, 'adjustTask'])
        ->name('study-plan.task.adjust');

    // Recommendations routes - FR-3.2.4, FR-3.6.3
    Route::get('/recommendations', [App\Http\Controllers\RecommendationController::class, 'index'])
        ->name('recommendations.index');

    Route::get('/badges', function() {
        return redirect()->route('student.dashboard')->with('info', 'Fitur lencana akan segera tersedia.');
    })->name('badges.index');
});
