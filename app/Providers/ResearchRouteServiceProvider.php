<?php

namespace App\Providers;

use App\Http\Controllers\Instructor\DiagnosticAssessmentController;
use App\Http\Controllers\Instructor\MasteryProfileController;
use App\Http\Controllers\Student\AdaptiveRecommendationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ResearchRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:instructor'])
            ->prefix('instructor')
            ->name('instructor.')
            ->group(function (): void {
                Route::get('/diagnostics', [DiagnosticAssessmentController::class, 'index'])
                    ->name('diagnostics.index');
                Route::put('/diagnostics/{quiz}/assessment-type', [DiagnosticAssessmentController::class, 'updateType'])
                    ->name('diagnostics.type.update');

                Route::get('/mastery-profiles', [MasteryProfileController::class, 'index'])
                    ->name('mastery.index');
                Route::get('/mastery-profiles/audit', [MasteryProfileController::class, 'audit'])
                    ->name('mastery.audit');
                Route::get('/mastery-profiles/courses/{course}/students/{student}', [MasteryProfileController::class, 'show'])
                    ->name('mastery.show');
            });

        Route::middleware(['web', 'auth', 'role:student'])
            ->prefix('student')
            ->name('student.')
            ->group(function (): void {
                Route::get('/adaptive-recommendations', [AdaptiveRecommendationController::class, 'index'])
                    ->name('adaptive-recommendations.index');
            });
    }
}
