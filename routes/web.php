<?php

use Arabiacode\LaravelFlowBuilder\Http\Controllers\FlowController;
use Arabiacode\LaravelFlowBuilder\Http\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('flow-builder.web_middleware', ['web']))
    ->prefix(config('flow-builder.web_prefix', 'flow-builder'))
    ->name('flow-builder.')
    ->group(function () {

        Route::get('/', [FlowController::class, 'dashboard'])->name('dashboard');
        Route::get('/guide', fn () => view('flow-builder::package'))->name('guide');

        Route::get('/flows', [FlowController::class, 'index'])->name('flows.index');
        Route::get('/flows/create', [FlowController::class, 'create'])->name('flows.create');
        Route::post('/flows', [FlowController::class, 'store'])->name('flows.store');
        Route::get('/flows/{flow}/edit', [FlowController::class, 'edit'])->name('flows.edit');
        Route::put('/flows/{flow}', [FlowController::class, 'update'])->name('flows.update');
        Route::patch('/flows/{flow}/toggle', [FlowController::class, 'toggle'])->name('flows.toggle');
        Route::delete('/flows/{flow}', [FlowController::class, 'destroy'])->name('flows.destroy');

        Route::get('/flows/{flow}/builder', [FlowController::class, 'builder'])->name('flows.builder');
        Route::post('/flows/{flow}/builder', [FlowController::class, 'saveBuilder'])->name('flows.builder.save');

        Route::delete('/triggers/{trigger}', [FlowController::class, 'destroyTrigger'])->name('triggers.destroy');

        Route::get('/executions', [FlowController::class, 'executionIndex'])->name('executions.index');
        Route::get('/executions/{execution}', [FlowController::class, 'executionShow'])->name('executions.show');

        // Integrations CRUD
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::get('/integrations/create', [IntegrationController::class, 'create'])->name('integrations.create');
        Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
        Route::get('/integrations/{integration}/edit', [IntegrationController::class, 'edit'])->name('integrations.edit');
        Route::put('/integrations/{integration}', [IntegrationController::class, 'update'])->name('integrations.update');
        Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
        Route::patch('/integrations/{integration}/toggle', [IntegrationController::class, 'toggle'])->name('integrations.toggle');

        // Model discovery API (for builder UI)
        Route::get('/api/models', [FlowController::class, 'getModels'])->name('api.models');
        Route::get('/api/model-fields', [FlowController::class, 'getModelFields'])->name('api.model-fields');
        Route::get('/api/integrations', [FlowController::class, 'getIntegrations'])->name('api.integrations');
    });
