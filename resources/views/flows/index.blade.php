@extends('flow-builder::layouts.app')
@section('title', 'Flows')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item active">Flows</li>
@endsection
@section('topbar-actions')
    <a href="{{ route('flow-builder.flows.create') }}" class="btn btn-sm btn-fb-dark w-300 fw-normal fs-6"><i class="bi bi-plus-lg"></i> New Flow</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-4">
        @if($flows->isEmpty())
            <div class="empty-state">
                <i class="bi bi-diagram-3"></i>
                <h5>No flows yet</h5>
                <p>Create your first workflow to get started.</p>
                <a href="{{ route('flow-builder.flows.create') }}" class="btn btn-fb"><i class="bi bi-plus-lg"></i> Create Flow</a>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="table-light text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                        <th>Name</th>
                        <th>Triggers</th>
                        <th>Nodes</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($flows as $flow)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $flow->name }}</div>
                        @if($flow->description)
                        <div class="small text-muted text-truncate" style="max-width:250px">{{ $flow->description }}</div>
                        @endif
                    </td>
                    <td>
                        @foreach($flow->triggers as $trigger)
                            <span class="badge badge-trigger">{{ $trigger->type }}</span>
                        @endforeach
                        @if($flow->triggers->isEmpty())
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td><span class="badge bg-light text-dark">{{ $flow->nodes_count ?? $flow->nodes->count() }}</span></td>
                    <td>
                        <form action="{{ route('flow-builder.flows.toggle', $flow) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $flow->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                <i class="bi {{ $flow->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                {{ $flow->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('flow-builder.flows.edit', $flow) }}" class="btn btn-primary me-2" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('flow-builder.flows.builder', $flow) }}" class="btn btn-dark me-2" title="Builder"><i class="bi bi-diagram-3"></i></a>
                            <form action="{{ route('flow-builder.flows.destroy', $flow) }}" method="POST" onsubmit="return confirm('Delete this flow?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            @if ($flows->lastPage() > 1)
            <nav aria-label="Page navigation example" class=" mt-30">
                <ul class="pagination">

                    {{-- Previous --}}
                    @if ($flows->onFirstPage())
                        <li class="disabled page-item"><a class="page-link"><span>Previous</span></a></li>
                    @else
                        <li class="page-item text-dark">
                            <a class="page-link" href="{{ $flows->previousPageUrl() }}">&laquo;</a>
                        </li>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = 1; $i <= $flows->lastPage(); $i++)
                        <li class="page-item {{ $flows->currentPage() == $i ? 'active ' : '' }}">
                            <a class="page-link" href="{{ $flows->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor

                    {{-- Next --}}
                    @if ($flows->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $flows->nextPageUrl() }}">&raquo;</a>
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
