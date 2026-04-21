@extends('flow-builder::layouts.app')

@section('title', 'Package Guide')

@section('breadcrumb')
    <li class="breadcrumb-item active">Package Guide</li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-box-seam me-2"></i>Laravel Flow Builder</span>
                <span class="badge text-bg-light border">v1.0.0</span>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    A production-ready workflow automation package for Laravel. Create trigger-based flows,
                    connect nodes visually, and execute business logic using actions, conditions, operations,
                    and integrations.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-light border">Laravel 10/11/12</span>
                    <span class="badge rounded-pill text-bg-light border">PHP 8.1+</span>
                    <span class="badge rounded-pill text-bg-light border">API + Web UI</span>
                    <span class="badge rounded-pill text-bg-light border">Queue Ready</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-download me-2"></i>Download</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Install from Packagist:</p>
                <pre class="bg-light p-3 rounded border small mb-3">composer require arabiacode/laravel-flow-builder</pre>
                <p class="small text-muted mb-2">Run migrations:</p>
                <pre class="bg-light p-3 rounded border small mb-3">php artisan migrate</pre>
                <p class="small text-muted mb-2">Access the flow builder UI:</p>
                <pre class="bg-light p-3 rounded border small mb-0">http://127.0.0.1:8000/flow-builder</pre>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-stars me-2"></i>Key Features</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Visual flow builder with nodes and connections</li>
                    <li>Trigger types: model events, webhooks, scheduled flows</li>
                    <li>Node types: trigger, condition, action, operation, integration</li>
                    <li>Template variables with dot notation, for example "'{{'order.total'}}'"</li>
                    <li>Execution logs and history for each flow run</li>
                    <li>Custom node executors through package configuration</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-sliders me-2"></i>Important Settings</div>
            <div class="card-body">
                <p class="mb-2 small text-muted">Publish config (optional):</p>
                <pre class="bg-light p-3 rounded border small mb-3">php artisan vendor:publish --tag=flow-builder-config</pre>
                <p class="mb-2 small text-muted">Main options in config/flow-builder.php:</p>
                <ul class="mb-0">
                    <li>queue.enabled, queue.connection, queue.queue</li>
                    <li>retry.max_attempts and retry.delay</li>
                    <li>route_prefix and route_middleware</li>
                    <li>web_prefix and web_middleware</li>
                    <li>logging.enabled and logging.channel</li>
                    <li>executors (custom node handlers)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-rocket-takeoff me-2"></i>How To Use</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="fw-semibold mb-1">1. Create Flow</div>
                    <div class="small text-muted">Open Flows and create a new flow with a clear business objective.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="fw-semibold mb-1">2. Add Trigger</div>
                    <div class="small text-muted">Choose model event, webhook, or schedule as the entry point.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="fw-semibold mb-1">3. Build Nodes</div>
                    <div class="small text-muted">Use condition, action, operation, and integration nodes to define logic.</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light-subtle">
                    <div class="fw-semibold mb-1">4. Execute & Monitor</div>
                    <div class="small text-muted">Run manually via API or trigger automatically and monitor executions.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-link-45deg me-2"></i>HTTP Endpoints</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Default API prefix: <strong>/api/flow-builder</strong></p>
                <pre class="bg-light p-3 rounded border small mb-0">POST /webhook/{flow}
POST /flows/{flow}/execute</pre>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-terminal me-2"></i>Artisan Commands</div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded border small mb-0">php artisan flow-builder:run-scheduled
php artisan flow-builder:clear-cache</pre>
                <p class="small text-muted mt-2 mb-0">For scheduled triggers, run the first command every minute in your scheduler.</p>
            </div>
        </div>
    </div>
</div>
@endsection
