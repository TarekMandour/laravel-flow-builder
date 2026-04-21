@extends('flow-builder::layouts.app')
@section('title', 'Integrations')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item active">Integrations</li>
@endsection
@section('topbar-actions')
    <a href="{{ route('flow-builder.integrations.create') }}" class="btn btn-sm btn-fb-dark w-auto fw-normal fs-6"><i class="bi bi-plus-lg"></i> New Integration</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-4">
        @if($integrations->isEmpty())
            <div class="empty-state">
                <i class="bi bi-plug"></i>
                <h5>No integrations yet</h5>
                <p>Add a saved integration to reuse credentials across your flows.</p>
                <a href="{{ route('flow-builder.integrations.create') }}" class="btn btn-fb"><i class="bi bi-plus-lg"></i> Create Integration</a>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="table-light text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($integrations as $integration)
                <tr>
                    <td class="">{{ $integration->name }}</td>
                    <td>
                        @php
                            $typeLabels = [
                                'webhook' => ['Webhook', 'bi-globe', 'badge-info-soft'],
                                'whatsapp' => ['WhatsApp', 'bi-whatsapp', 'badge-success-soft'],
                                'firebase' => ['Firebase', 'bi-bell', 'badge-warning-soft'],
                                'google_drive' => ['Google Drive', 'bi-cloud', 'badge-info-soft'],
                            ];
                            $t = $typeLabels[$integration->type] ?? [$integration->type, 'bi-plug', 'badge-integration'];
                        @endphp
                        <span class="badge {{ $t[2] }}"><i class="bi {{ $t[1] }} me-1"></i>{{ $t[0] }}</span>
                    </td>
                    <td>
                        <form action="{{ route('flow-builder.integrations.toggle', $integration) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $integration->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                <i class="bi {{ $integration->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                {{ $integration->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="small text-muted">{{ $integration->created_at->diffForHumans() }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('flow-builder.integrations.edit', $integration) }}" class="btn btn-primary btn-sm me-2" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('flow-builder.integrations.destroy', $integration) }}" method="POST" onsubmit="return confirm('Delete this integration?')">
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
            @if ($integrations->lastPage() > 1)
            <nav aria-label="Page navigation example" class=" mt-30">
                <ul class="pagination">

                    {{-- Previous --}}
                    @if ($integrations->onFirstPage())
                        <li class="disabled page-item"><a class="page-link"><span>Previous</span></a></li>
                    @else
                        <li class="page-item text-dark">
                            <a class="page-link" href="{{ $integrations->previousPageUrl() }}">&laquo;</a>
                        </li>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = 1; $i <= $integrations->lastPage(); $i++)
                        <li class="page-item {{ $integrations->currentPage() == $i ? 'active ' : '' }}">
                            <a class="page-link" href="{{ $integrations->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor

                    {{-- Next --}}
                    @if ($integrations->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $integrations->nextPageUrl() }}">&raquo;</a>
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
