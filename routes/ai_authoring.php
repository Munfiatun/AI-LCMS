<?php

use App\Http\Controllers\Instructor\AIMaterialAuthoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:instructor'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function (): void {
        Route::get('/sections/{section}/materials/ai-authoring', [AIMaterialAuthoringController::class, 'create'])
            ->name('materials.ai-authoring.create');
        Route::post('/sections/{section}/materials/ai-authoring/generate', [AIMaterialAuthoringController::class, 'generate'])
            ->name('materials.ai-authoring.generate')
            ->middleware('throttle:5,1');
        Route::get('/sections/{section}/materials/ai-authoring/review', [AIMaterialAuthoringController::class, 'review'])
            ->name('materials.ai-authoring.review');
        Route::post('/sections/{section}/materials/ai-authoring/store', [AIMaterialAuthoringController::class, 'store'])
            ->name('materials.ai-authoring.store');
        Route::delete('/sections/{section}/materials/ai-authoring', [AIMaterialAuthoringController::class, 'destroy'])
            ->name('materials.ai-authoring.destroy');
    });
