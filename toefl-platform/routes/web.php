
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
