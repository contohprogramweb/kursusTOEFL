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

    Route::get('/study-plan/create', function() {
        return redirect()->route('student.dashboard')->with('info', 'Fitur study plan akan segera tersedia.');
    })->name('study-plan.create');

    Route::get('/study-plan/{id}', function($id) {
        return redirect()->route('student.dashboard')->with('info', 'Fitur detail study plan akan segera tersedia.');
    })->name('study-plan.show');

    Route::get('/badges', function() {
        return redirect()->route('student.dashboard')->with('info', 'Fitur lencana akan segera tersedia.');
    })->name('badges.index');
});
