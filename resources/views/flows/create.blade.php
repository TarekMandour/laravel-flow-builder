@extends('flow-builder::layouts.app')
@section('title', 'Create Flow')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.flows.index') }}" class="text-dark">Flows</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Create New Flow</div>
            <div class="card-body">
                <form action="{{ route('flow-builder.flows.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Flow Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-solid @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-fb-dark w-auto fw-normal fs-6"><i class="bi bi-check-lg"></i> Create Flow</button>
                        <a href="{{ route('flow-builder.flows.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <div class="mt-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Configure the trigger in the visual builder after creating the flow.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
