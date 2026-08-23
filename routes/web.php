<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'home')->name('home');

/*
 * Internal administration (spec §22, §33). Never exposed publicly:
 * requires authentication, verified email and the admin gate.
 */
Route::middleware(['auth', 'verified', 'can:access-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('review-queue', [Admin\ReviewQueueController::class, 'index'])->name('review-queue.index');
        Route::patch('source-references/{source_reference}', [Admin\ReviewQueueController::class, 'update'])
            ->name('source-references.update')
            ->middleware('throttle:30,1');

        Route::get('institutions', [Admin\InstitutionsController::class, 'index'])->name('institutions.index');
        Route::get('programs', [Admin\ProgramsController::class, 'index'])->name('programs.index');
        Route::get('sources', [Admin\SourcesController::class, 'index'])->name('sources.index');
    });

require __DIR__.'/settings.php';
