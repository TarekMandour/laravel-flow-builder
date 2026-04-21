@extends('flow-builder::layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-color:#6610f2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Flows</div>
                        <div class="fs-3 fw-bold">{{ $totalFlows }}</div>
                    </div>
                    <div class="text-primary opacity-50"><i class="bi bi-diagram-3 fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-color:#059669">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Active Flows</div>
                        <div class="fs-3 fw-bold">{{ $activeFlows }}</div>
                    </div>
                    <div class="text-success opacity-50"><i class="bi bi-check-circle fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-color:#0284c7">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Executions</div>
                        <div class="fs-3 fw-bold">{{ $totalExecutions }}</div>
                    </div>
                    <div class="text-info opacity-50"><i class="bi bi-play-circle fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card" style="border-color:#dc2626">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Failed Executions</div>
                        <div class="fs-3 fw-bold">{{ $failedExecutions }}</div>
                    </div>
                    <div class="text-danger opacity-50"><i class="bi bi-exclamation-triangle fs-1"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Recent Executions</span>
                <a href="{{ route('flow-builder.executions.index') }}" class="btn btn-sm btn-outline-secondary btn-fb-dark-outline">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentExecutions->isEmpty())
                    <div class="empty-state"><i class="bi bi-inbox"></i>No executions yet.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Flow</th><th>Status</th><th>Started</th><th>Duration</th></tr></thead>
                        <tbody>
                        @foreach($recentExecutions as $exec)
                        <tr>
                            <td>{{ $exec->flow->name ?? '—' }}</td>
                            <td>
                                @if($exec->status === 'completed')
                                    <span class="badge badge-success-soft">Completed</span>
                                @elseif($exec->status === 'failed')
                                    <span class="badge badge-danger-soft">Failed</span>
                                @elseif($exec->status === 'running')
                                    <span class="badge badge-info-soft">Running</span>
                                @else
                                    <span class="badge badge-warning-soft">{{ ucfirst($exec->status) }}</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $exec->started_at?->diffForHumans() ?? '—' }}</td>
                            <td class="small text-muted">
                                @if($exec->started_at && $exec->completed_at)
                                    {{ $exec->started_at->diffInSeconds($exec->completed_at) }}s
                                @else — @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-lightning me-2"></i>Active Flows</span>
                <a href="{{ route('flow-builder.flows.create') }}" class="btn btn-sm btn-fb-dark"><i class="bi bi-plus"></i> New Flow</a>
            </div>
            <div class="card-body p-0">
                @if($activeFlowList->isEmpty())
                    <div class="empty-state"><i class="bi bi-diagram-3"></i>No active flows.</div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($activeFlowList as $flow)
                    <a href="{{ route('flow-builder.flows.builder', $flow) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $flow->name }}</div>
                            <div class="small text-muted">{{ $flow->nodes_count ?? 0 }} nodes · {{ $flow->triggers_count ?? 0 }} triggers</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
