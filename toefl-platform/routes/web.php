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
