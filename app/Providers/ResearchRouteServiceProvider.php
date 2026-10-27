<?php

namespace App\Providers;

use App\Http\Controllers\Instructor\DiagnosticAssessmentController;
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
            });
    }
}
