<?php

use App\Http\Controllers\AiFormController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormImportController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('forms.index') : view('welcome');
});

// Public form fill routes
Route::get('/f/{slug}', [FormSubmissionController::class, 'fill'])->name('forms.fill');
Route::post('/f/{slug}', [FormSubmissionController::class, 'submit'])->name('forms.submit')
    ->middleware('throttle:30,1');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('forms.index'))->name('dashboard');

    // Form management
    Route::resource('forms', FormController::class)->except(['show']);
    Route::get('/forms/{form}/builder', [FormController::class, 'builder'])->name('forms.builder');
    Route::put('/forms/{form}/schema', [FormController::class, 'update'])->name('forms.update-schema');

    // Versioning (Part D)
    Route::get('/forms/{form}/versions', [FormController::class, 'versions'])->name('forms.versions');
    Route::post('/forms/{form}/versions/{version}/restore', [FormController::class, 'restoreVersion'])->name('forms.versions.restore');

    // Submissions
    Route::get('/forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions');
    Route::get('/forms/{form}/submissions/export', [FormSubmissionController::class, 'exportCsv'])->name('forms.submissions.export');

    // AI generation (Part B)
    Route::post('/ai/generate', [AiFormController::class, 'createFromPrompt'])->name('ai.generate');
    Route::post('/ai/edit/{form}', [AiFormController::class, 'editWithAi'])->name('ai.edit');
    Route::get('/ai/status/{jobId}', [AiFormController::class, 'jobStatus'])->name('ai.status');
    Route::post('/ai/apply/{form}', [AiFormController::class, 'applyToForm'])->name('ai.apply');

    // Import (Part C)
    Route::get('/imports', [FormImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/upload', [FormImportController::class, 'upload'])->name('imports.upload');
    Route::get('/imports/{importId}/status', [FormImportController::class, 'status'])->name('imports.status');
    Route::get('/imports/{importId}/preview', [FormImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/{importId}/commit', [FormImportController::class, 'commit'])->name('imports.commit');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
