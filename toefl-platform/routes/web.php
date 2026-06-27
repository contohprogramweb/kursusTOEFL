<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Parent\ParentLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Parent-Student Link Routes
    Route::prefix('parent')->name('parent.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ParentLinkController::class, 'dashboard'])->name('dashboard');
        
        // Generate code (student)
        Route::get('/generate-code', [ParentLinkController::class, 'showGenerateCode'])->name('code.generate.page');
        Route::post('/generate-code', [ParentLinkController::class, 'generateCode'])->name('code.generate');
        
        // Submit code (parent)
        Route::get('/submit-code', [ParentLinkController::class, 'showSubmitCode'])->name('code.submit');
        Route::post('/submit-code', [ParentLinkController::class, 'submitCode'])->name('code.submit.post');
        
        // Pending requests (student)
        Route::get('/pending', [ParentLinkController::class, 'pendingRequests'])->name('pending');
        
        // Approve/Revoke links
        Route::post('/links/{link}/approve', [ParentLinkController::class, 'approveLink'])->name('links.approve');
        Route::post('/links/{link}/revoke', [ParentLinkController::class, 'revokeLink'])->name('links.revoke');
        
        // My children (parent)
        Route::get('/children', [ParentLinkController::class, 'myChildren'])->name('children');
    });
});

require __DIR__.'/auth.php';

// Exercise Routes (Interactive Practice)
Route::middleware(['auth'])->prefix('exercises')->name('exercises.')->group(function () {
    // Main exercise pages
    Route::get('/', [App\Http\Controllers\ExerciseController::class, 'index'])->name('index');
    Route::post('/create', [App\Http\Controllers\ExerciseController::class, 'create'])->name('create');
    Route::get('/{session}', [App\Http\Controllers\ExerciseController::class, 'show'])->name('show');
    Route::get('/history', [App\Http\Controllers\ExerciseController::class, 'history'])->name('history');
    
    // AJAX endpoints for exercise session
    Route::get('/{session}/question', [App\Http\Controllers\ExerciseController::class, 'getCurrentQuestion'])->name('question.current');
    Route::post('/{session}/answer', [App\Http\Controllers\ExerciseController::class, 'saveAnswer'])->name('answer.save');
    Route::post('/{session}/next', [App\Http\Controllers\ExerciseController::class, 'nextQuestion'])->name('question.next');
    Route::post('/{session}/previous', [App\Http\Controllers\ExerciseController::class, 'previousQuestion'])->name('question.previous');
    Route::post('/{session}/submit', [App\Http\Controllers\ExerciseController::class, 'submit'])->name('submit');
    
    // Statistics API
    Route::get('/api/statistics', [App\Http\Controllers\ExerciseController::class, 'statistics'])->name('api.statistics');
});

// Admin Module Management Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Modules CRUD
    Route::get('/modules', [App\Http\Controllers\Admin\ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/create', [App\Http\Controllers\Admin\ModuleController::class, 'create'])->name('modules.create');
    Route::post('/modules', [App\Http\Controllers\Admin\ModuleController::class, 'store'])->name('modules.store');
    Route::get('/modules/{module}', [App\Http\Controllers\Admin\ModuleController::class, 'show'])->name('modules.show');
    Route::get('/modules/{module}/edit', [App\Http\Controllers\Admin\ModuleController::class, 'edit'])->name('modules.edit');
    Route::put('/modules/{module}', [App\Http\Controllers\Admin\ModuleController::class, 'update'])->name('modules.update');
    Route::delete('/modules/{module}', [App\Http\Controllers\Admin\ModuleController::class, 'destroy'])->name('modules.destroy');

    // Module Contents
    Route::post('/modules/{module}/contents', [App\Http\Controllers\Admin\ModuleContentController::class, 'store'])->name('modules.contents.store');
    Route::put('/contents/{content}', [App\Http\Controllers\Admin\ModuleContentController::class, 'update'])->name('contents.update');
    Route::delete('/contents/{content}', [App\Http\Controllers\Admin\ModuleContentController::class, 'destroy'])->name('contents.destroy');
    Route::post('/modules/{module}/reorder', [App\Http\Controllers\Admin\ModuleContentController::class, 'reorder'])->name('modules.contents.reorder');

    // Questions CRUD (Question Bank)
    Route::get('/questions', [App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/create', [App\Http\Controllers\Admin\QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}', [App\Http\Controllers\Admin\QuestionController::class, 'show'])->name('questions.show');
    Route::get('/questions/{question}/edit', [App\Http\Controllers\Admin\QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('questions.destroy');
    
    // Questions API
    Route::get('/questions/api', [App\Http\Controllers\Admin\QuestionController::class, 'apiIndex'])->name('questions.api');
});

// Learning Routes (for students)
Route::middleware(['auth'])->prefix('learning')->name('learning.')->group(function () {
    // Learning Dashboard
    Route::get('/dashboard', [App\Http\Controllers\LearningController::class, 'dashboard'])->name('dashboard');
    
    // Module listing and viewing
    Route::get('/modules', [App\Http\Controllers\LearningController::class, 'index'])->name('modules.index');
    Route::get('/modules/{module}', [App\Http\Controllers\LearningController::class, 'show'])->name('modules.show');
    Route::get('/modules/{module}/start', [App\Http\Controllers\LearningController::class, 'start'])->name('modules.start');
    Route::post('/modules/{module}/clear-resume', [App\Http\Controllers\LearningController::class, 'clearResumePosition'])->name('modules.clear-resume');
    
    // Content viewing and progress
    Route::get('/modules/{module}/contents/{content}', [App\Http\Controllers\LearningController::class, 'showContent'])->name('content.show');
    Route::post('/modules/{module}/contents/{content}/progress', [App\Http\Controllers\LearningController::class, 'updateProgress'])->name('content.progress');
});

// Admin Simulation Template Management Routes
Route::middleware(['auth'])->prefix('admin/simulations')->name('admin.simulations.')->group(function () {
    // CRUD for simulation templates
    Route::get('/', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'show'])->name('show');
    Route::get('/{template}/edit', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'destroy'])->name('destroy');

    // B2B: Assign template to institution
    Route::post('/{template}/assign', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'assignToInstitution'])->name('assign');
    Route::delete('/{template}/institutions/{institutionId}', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'removeFromInstitution'])->name('remove-institution');

    // API: Get available templates
    Route::get('/api/available', [App\Http\Controllers\Admin\SimulationTemplateController::class, 'apiAvailableTemplates'])->name('api.available');
});

// User Simulation Routes
Route::middleware(['auth'])->prefix('simulations')->name('simulations.')->group(function () {
    // List available simulations
    Route::get('/', [App\Http\Controllers\SimulationController::class, 'index'])->name('index');
    
    // Start a new simulation from template
    Route::post('/templates/{template}/start', [App\Http\Controllers\SimulationController::class, 'start'])->name('start');
    
    // Resume an existing simulation
    Route::get('/{simulation}/resume', [App\Http\Controllers\SimulationController::class, 'resume'])->name('resume');
    
    // Run simulation interface
    Route::get('/{simulation}/run', [App\Http\Controllers\SimulationController::class, 'run'])->name('run');
    
    // Simulation state transitions (AJAX)
    Route::post('/{simulation}/next-section', [App\Http\Controllers\SimulationController::class, 'nextSection'])->name('next-section');
    Route::post('/{simulation}/submit', [App\Http\Controllers\SimulationController::class, 'submit'])->name('submit');
    Route::post('/{simulation}/pause', [App\Http\Controllers\SimulationController::class, 'pause'])->name('pause');
    Route::post('/{simulation}/resume-simulation', [App\Http\Controllers\SimulationController::class, 'resumeSimulation'])->name('resume-simulation');
    Route::post('/{simulation}/record-time', [App\Http\Controllers\SimulationController::class, 'recordTime'])->name('record-time');
    
    // Auto-save answers (AJAX - every 30 detik)
    Route::post('/{simulation}/save-answer', [App\Http\Controllers\SimulationController::class, 'saveAnswer'])->name('save-answer');
    Route::post('/{simulation}/bulk-save-answers', [App\Http\Controllers\SimulationController::class, 'bulkSaveAnswers'])->name('bulk-save-answers');

    // Get simulation status (AJAX polling)
    Route::get('/{simulation}/status', [App\Http\Controllers\SimulationController::class, 'getStatus'])->name('status');
    
    // View results
    Route::get('/{simulation}/results', [App\Http\Controllers\SimulationController::class, 'showResults'])->name('results.show');
    Route::get('/{simulation}/results/{section}', [App\Http\Controllers\SimulationController::class, 'showSectionResults'])->name('results.section');
});

// Gamification Routes
Route::middleware(['auth'])->prefix('gamification')->name('gamification.')->group(function () {
    // Dashboard/Stats
    Route::get('/stats', [\App\Http\Controllers\Gamification\GamificationController::class, 'getStats'])->name('stats');
    
    // Badges
    Route::get('/badges', [\App\Http\Controllers\Gamification\GamificationController::class, 'getBadges'])->name('badges.index');
    Route::get('/badges/all', [\App\Http\Controllers\Gamification\GamificationController::class, 'getAllBadges'])->name('badges.all');
    Route::patch('/badges/{badge}/visibility', [\App\Http\Controllers\Gamification\GamificationController::class, 'toggleBadgeVisibility'])->name('badges.visibility');
    
    // Streak Freeze
    Route::post('/streak/freeze', [\App\Http\Controllers\Gamification\GamificationController::class, 'useFreeze'])->name('streak.freeze');
    
    // Leaderboard (optional)
    Route::get('/leaderboard', [\App\Http\Controllers\Gamification\GamificationController::class, 'getLeaderboard'])->name('leaderboard');
});

// Simulation Report Routes
use App\Http\Controllers\SimulationReportController;
Route::middleware(['auth'])->group(function () {
    Route::get('/simulations/{id}/report', [SimulationReportController::class, 'show'])->name('simulations.report.show');
    Route::get('/simulations/{id}/report/export', [SimulationReportController::class, 'exportPdf'])->name('simulations.report.export');
    
    // API Routes for Recommendations
    Route::post('/api/recommendations/generate-latest', [App\Http\Controllers\RecommendationController::class, 'generateFromLatest'])
        ->name('api.recommendations.generate-latest');
    
    Route::post('/api/recommendations/{id}/read', [App\Http\Controllers\RecommendationController::class, 'markAsRead'])
        ->name('api.recommendations.mark-read');
    
    Route::post('/api/recommendations/mark-all-read', [App\Http\Controllers\RecommendationController::class, 'markAllAsRead'])
        ->name('api.recommendations.mark-all-read');
    
    Route::get('/api/recommendations', [App\Http\Controllers\RecommendationController::class, 'apiGet'])
        ->name('api.recommendations.get');
});
