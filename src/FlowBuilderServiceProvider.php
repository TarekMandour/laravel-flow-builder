<?php

namespace Arabiacode\LaravelFlowBuilder;

use Arabiacode\LaravelFlowBuilder\Console\Commands\ClearFlowCacheCommand;
use Arabiacode\LaravelFlowBuilder\Console\Commands\RunScheduledFlowsCommand;
use Arabiacode\LaravelFlowBuilder\Engine\FlowEngine;
use Arabiacode\LaravelFlowBuilder\Listeners\ModelEventListener;
use Illuminate\Support\ServiceProvider;

class FlowBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flow-builder.php', 'flow-builder');

        $this->app->singleton(FlowEngine::class, function ($app) {
            return new FlowEngine();
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/flow-builder.php' => config_path('flow-builder.php'),
        ], 'flow-builder-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flow-builder');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/flow-builder'),
        ], 'flow-builder-views');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RunScheduledFlowsCommand::class,
                ClearFlowCacheCommand::class,
            ]);
        }

        // Register model event triggers after app is fully booted
        $this->app->booted(function () {
            try {
                (new ModelEventListener())->register();
            } catch (\Exception $e) {
                // Silently fail — table may not exist yet (pre-migration)
            }
        });
    }
}
