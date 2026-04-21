@extends('flow-builder::layouts.app')
@section('title', 'Create Integration')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.integrations.index') }}" class="text-dark">Integrations</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Create Integration</div>
            <div class="card-body">
                <form action="{{ route('flow-builder.integrations.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid @error('name') is-invalid @enderror" placeholder="e.g. Production WhatsApp" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="intType" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            <option value="webhook" {{ old('type') === 'webhook' ? 'selected' : '' }}>Webhook (HTTP Request)</option>
                            <option value="whatsapp" {{ old('type') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="firebase" {{ old('type') === 'firebase' ? 'selected' : '' }}>Firebase Push Notification</option>
                            <option value="google_drive" {{ old('type') === 'google_drive' ? 'selected' : '' }}>Google Drive</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-semibold mb-3"><i class="bi bi-key me-1"></i> Credentials</h6>
                    <div class="small text-muted mb-3">Credentials are encrypted at rest. Add key-value pairs for API keys, tokens, URLs, etc.</div>

                    <div id="credentialRows">
                        @if(old('credential_keys'))
                            @foreach(old('credential_keys') as $i => $key)
                            <div class="row g-2 mb-2 cred-row align-items-center">
                                <div class="col-4">
                                    <input type="text" name="credential_keys[]" value="{{ $key }}" class="form-control form-control-sm" placeholder="Key (e.g. api_key)">
                                </div>
                                <div class="col-7">
                                    <input type="text" name="credential_values[]" value="{{ old('credential_values')[$i] ?? '' }}" class="form-control form-control-sm" placeholder="Value">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.cred-row').remove()"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="addCredRow">
                        <i class="bi bi-plus"></i> Add Credential
                    </button>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-fb-dark w-auto fw-normal fs-6"><i class="bi bi-check-lg"></i> Create Integration</button>
                        <a href="{{ route('flow-builder.integrations.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function addCredRow(key = '', value = '') {
    const container = document.getElementById('credentialRows');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 cred-row align-items-center';
    row.innerHTML = `
        <div class="col-4">
            <input type="text" name="credential_keys[]" value="${key}" class="form-control form-control-sm" placeholder="Key (e.g. api_key)">
        </div>
        <div class="col-7">
            <input type="text" name="credential_values[]" value="${value}" class="form-control form-control-sm" placeholder="Value">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.cred-row').remove()"><i class="bi bi-x"></i></button>
        </div>
    `;
    container.appendChild(row);
}
document.getElementById('addCredRow').addEventListener('click', () => addCredRow());
</script>
@endpush
