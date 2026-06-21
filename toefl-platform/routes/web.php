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
