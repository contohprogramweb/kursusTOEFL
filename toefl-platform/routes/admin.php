<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // User Management
    Route::resource('users', UserController::class);
    
    // User Actions
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('users.unsuspend');
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
    
    // User Import/Export
    Route::get('users/download-template', [UserController::class, 'downloadTemplate'])->name('users.download-template');
    Route::get('users/import', [UserController::class, 'showImportForm'])->name('users.import');
    Route::post('users/preview-import', [UserController::class, 'previewImport'])->name('users.preview-import');
    Route::post('users/commit-import', [UserController::class, 'commitImport'])->name('users.commit-import');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    
    // Audit Logs
    Route::get('users/audit-logs', [UserController::class, 'auditLogs'])->name('users.audit-logs');
});
