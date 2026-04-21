@extends('flow-builder::layouts.app')
@section('title', 'Execution #' . $execution->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark" >Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.executions.index') }}" class="text-dark">Executions</a></li>
    <li class="breadcrumb-item active">#{{ $execution->id }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Execution Info</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">ID</th><td>{{ $execution->id }}</td></tr>
                    <tr><th class="text-muted">Flow</th><td>{{ $execution->flow->name ?? '—' }}</td></tr>
                    <tr>
                        <th class="text-muted">Status</th>
                        <td>
                            @if($execution->status === 'completed')
                                <span class="badge badge-success-soft">Completed</span>
                            @elseif($execution->status === 'failed')
                                <span class="badge badge-danger-soft">Failed</span>
                            @elseif($execution->status === 'running')
                                <span class="badge badge-info-soft">Running</span>
                            @else
                                <span class="badge badge-warning-soft">{{ ucfirst($execution->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Started</th><td>{{ $execution->started_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Completed</th><td>{{ $execution->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
                    <tr>
                        <th class="text-muted">Duration</th>
                        <td>
                            @if($execution->started_at && $execution->completed_at)
                                {{ $execution->started_at->diffInSeconds($execution->completed_at) }}s
                            @else — @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($execution->payload)
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-code-slash me-2"></i>Payload</div>
            <div class="card-body">
                <pre class="mb-0 small bg-light p-3 rounded" style="max-height:300px;overflow:auto"><code>{{ json_encode($execution->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2"></i>Execution Logs</div>
            <div class="card-body p-4">
                @if($execution->logs->isEmpty())
                    <div class="empty-state py-4"><i class="bi bi-journal-text"></i>No logs recorded.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="table-light text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0"><th>Node</th><th>Type</th><th>Status</th><th>Time</th><th></th></tr></thead>
                        <tbody>
                        @foreach($execution->logs as $log)
                        <tr>
                            <td class="">{{ $log->node->name ?? 'Node #' . $log->node_id }}</td>
                            <td><span class="badge bg-light text-dark">{{ $log->node->type ?? '—' }}</span></td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="badge badge-success-soft">Success</span>
                                @elseif($log->status === 'error')
                                    <span class="badge badge-danger-soft">Error</span>
                                @else
                                    <span class="badge badge-warning-soft">{{ ucfirst($log->status) }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->created_at?->format('H:i:s') ?? '—' }}</td>
                            <td>
                                @if($log->message || $log->data)
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#logDetail{{ $log->id }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @if($log->message || $log->data)
                        <tr class="collapse" id="logDetail{{ $log->id }}">
                            <td colspan="5" class="bg-light">
                                @if($log->message)
                                <div class="mb-2"><strong class="small">Message:</strong> <span class="small">{{ $log->message }}</span></div>
                                @endif
                                @if($log->data)
                                <div><strong class="small">Data:</strong>
                                    <pre class="mb-0 small bg-white p-2 rounded mt-1"><code>{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
