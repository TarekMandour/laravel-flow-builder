@extends('flow-builder::layouts.app')
@section('title', 'Executions')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item active">Executions</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class=""><i class="bi bi-play-circle me-2 "></i>Execution History</span>
        <form class="d-flex gap-2" method="GET">
            <select name="status" class="form-select form-control-solid form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </form>
    </div>
    <div class="card-body p-4">
        @if($executions->isEmpty())
            <div class="empty-state"><i class="bi bi-inbox"></i><h5>No executions found</h5></div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="table-light text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0"><th>#</th><th>Flow</th><th>Status</th><th>Started</th><th>Completed</th><th>Duration</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody class="table-group-divider">
                @foreach($executions as $exec)
                <tr>
                    <td class="text-muted small">{{ $exec->id }}</td>
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
                    <td class="small text-muted">{{ $exec->started_at?->format('M d, H:i:s') ?? '—' }}</td>
                    <td class="small text-muted">{{ $exec->completed_at?->format('M d, H:i:s') ?? '—' }}</td>
                    <td class="small text-muted">
                        @if($exec->started_at && $exec->completed_at)
                            {{ $exec->started_at->diffInSeconds($exec->completed_at) }}s
                        @else — @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('flow-builder.executions.show', $exec) }}" class="btn btn-sm btn-warning me-2 fw-bold"> <i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            @if ($executions->withQueryString()->lastPage() > 1)
            <nav aria-label="Page navigation example" class=" mt-30">
                <ul class="pagination">

                    {{-- Previous --}}
                    @if ($executions->withQueryString()->onFirstPage())
                        <li class="disabled page-item"><a class="page-link"><span>Previous</span></a></li>
                    @else
                        <li class="page-item text-dark">
                            <a class="page-link" href="{{ $executions->withQueryString()->previousPageUrl() }}">&laquo;</a>
                        </li>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = 1; $i <= $executions->withQueryString()->lastPage(); $i++)
                        <li class="page-item {{ $executions->withQueryString()->currentPage() == $i ? 'active ' : '' }}">
                            <a class="page-link" href="{{ $executions->withQueryString()->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor

                    {{-- Next --}}
                    @if ($executions->withQueryString()->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $executions->withQueryString()->nextPageUrl() }}">&raquo;</a>
                        </li>
                    @else
                        <li class="disabled page-item"><a class="page-link"><span>Next</span></a></li>
                    @endif

                </ul>
            </nav>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
