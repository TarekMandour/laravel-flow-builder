@extends('flow-builder::layouts.app')
@section('title', 'Edit Flow')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.flows.index') }}" class="text-dark">Flows</a></li>
    <li class="breadcrumb-item active">Edit: {{ $flow->name }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Flow</div>
            <div class="card-body">
                <form action="{{ route('flow-builder.flows.update', $flow) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Flow Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $flow->name) }}" class="form-control form-control-solid @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-solid">{{ old('description', $flow->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ old('is_active', $flow->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-fb-dark w-auto fw-normal fs-6"><i class="bi bi-check-lg"></i> Update Flow</button>
                        <a href="{{ route('flow-builder.flows.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Triggers --}}
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-lightning me-2"></i>Trigger</div>
            <div class="card-body">
                @if($flow->triggers->isEmpty())
                    <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>No trigger configured yet. Open the visual builder to set up the trigger node.</div>
                @else
                    @php $trigger = $flow->triggers->first(); @endphp
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-trigger">{{ $trigger->type }}</span>
                        <span class="small text-muted">
                            @if($trigger->type === 'model')
                                {{ $trigger->model_class ?? '' }} &rarr; {{ $trigger->event ?? '' }}
                            @elseif($trigger->type === 'schedule')
                                {{ $trigger->conditions['cron_expression'] ?? '' }}
                            @elseif($trigger->type === 'webhook')
                                Token: {{ Str::limit($trigger->conditions['token'] ?? '', 20) }}
                            @else
                                Manual trigger
                            @endif
                        </span>
                    </div>
                    <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Edit the trigger in the visual builder.</div>
                @endif
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('flow-builder.flows.builder', $flow) }}" class="btn btn-fb btn-lg">
                <i class="bi bi-diagram-3 me-2"></i>Open Visual Builder
            </a>
        </div>
    </div>
</div>
@endsection
