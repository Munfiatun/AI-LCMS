<?php

namespace App\Providers;

use App\Services\Learning\Adaptive\AdaptiveEngine;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AdaptiveEngine::class, RuleBasedAdaptiveEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }
}
