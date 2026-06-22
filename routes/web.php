
// Simulation Report Routes
use App\Http\Controllers\SimulationReportController;
Route::middleware(['auth'])->group(function () {
    Route::get('/simulations/{id}/report', [SimulationReportController::class, 'show'])->name('simulations.report.show');
    Route::get('/simulations/{id}/report/export', [SimulationReportController::class, 'exportPdf'])->name('simulations.report.export');
});
