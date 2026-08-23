<?php

use App\Http\Controllers\Api\V1\AuthTokenApiController;
use App\Http\Controllers\Api\V1\CareerPathApiController;
use App\Http\Controllers\Api\V1\ProgramApiController;
use App\Http\Controllers\Api\V1\RecommendationApiController;
use Illuminate\Support\Facades\Route;

/*
 * API v1 (spec §19, §30): thin JSON controllers over the same services
 * the web app uses. Public reads are anonymous; anything account-bound
 * requires a Sanctum personal access token (Bearer).
 */
Route::prefix('v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenApiController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.token.store');
    Route::delete('auth/token', [AuthTokenApiController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'throttle:10,1'])
        ->name('api.v1.auth.token.destroy');

    Route::get('programs', [ProgramApiController::class, 'index'])->name('api.v1.programs.index');
    Route::get('programs/{program:slug}', [ProgramApiController::class, 'show'])->name('api.v1.programs.show');

    Route::get('career-paths', [CareerPathApiController::class, 'index'])->name('api.v1.career-paths.index');
    Route::get('career-paths/{careerPath:slug}', [CareerPathApiController::class, 'show'])->name('api.v1.career-paths.show');

    Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function (): void {
        Route::get('me/profile', [RecommendationApiController::class, 'showProfile'])->name('api.v1.me.profile.show');
        Route::patch('me/profile', [RecommendationApiController::class, 'updateProfile'])->name('api.v1.me.profile.update');
        Route::post('recommendations', [RecommendationApiController::class, 'store'])->name('api.v1.recommendations.store');
        Route::get('recommendations', [RecommendationApiController::class, 'index'])->name('api.v1.recommendations.index');
    });
});
