<?php

use App\Http\Controllers\AiTransparencyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI Transparency Routes (FR-3.5.4)
|--------------------------------------------------------------------------
|
| Routes untuk menampilkan transparansi hasil AI grading dengan
| highlight inline, tooltip, confidence score, dan statistik.
|
*/

// Web routes - Menampilkan halaman transparency
Route::middleware(['web', 'auth'])->group(function () {
    // Writing transparency
    Route::get('/ai-grading/writing/{id}', [AiTransparencyController::class, 'showWriting'])
        ->name('ai-grading.writing.show');
    
    // Speaking transparency
    Route::get('/ai-grading/speaking/{id}', [AiTransparencyController::class, 'showSpeaking'])
        ->name('ai-grading.speaking.show');
});

// API routes - Untuk AJAX/Fetch requests
Route::middleware(['api', 'auth:api'])->group(function () {
    // Initialize transparency component
    Route::post('/ai-transparency/initialize', [AiTransparencyController::class, 'initialize'])
        ->name('api.ai-transparency.initialize');
    
    // Get highlights data
    Route::get('/ai-grading-results/{id}/highlights', [AiTransparencyController::class, 'getHighlights'])
        ->name('api.ai-grading-results.highlights');
});
