<?php

use Arabiacode\LaravelFlowBuilder\Http\Controllers\FlowWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('flow-builder.route_prefix', 'api/flow-builder'))
    ->middleware(config('flow-builder.route_middleware', ['api']))
    ->group(function () {

        // Webhook trigger endpoint
        Route::post('webhook/{flow}', [FlowWebhookController::class, 'webhook'])
            ->name('flow-builder.webhook');

        // Manual flow execution endpoint
        Route::post('flows/{flow}/execute', [FlowWebhookController::class, 'execute'])
            ->name('flow-builder.execute');
    });
