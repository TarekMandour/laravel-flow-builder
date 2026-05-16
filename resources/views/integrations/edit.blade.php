@extends('flow-builder::layouts.app')
@section('title', 'Edit Integration')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.integrations.index') }}" class="text-dark">Integrations</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Integration — {{ $integration->name }}</div>
            <div class="card-body">
                <form action="{{ route('flow-builder.integrations.update', $integration) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $integration->name) }}" class="form-control form-control-solid @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="intType" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            <option value="webhook" {{ old('type', $integration->type) === 'webhook' ? 'selected' : '' }}>Webhook (HTTP Request)</option>
                            <option value="whatsapp" {{ old('type', $integration->type) === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="firebase" {{ old('type', $integration->type) === 'firebase' ? 'selected' : '' }}>Firebase Push Notification</option>
                            <option value="google_drive" {{ old('type', $integration->type) === 'google_drive' ? 'selected' : '' }}>Google Drive</option>
                            <option value="ai_agent" {{ old('type', $integration->type) === 'ai_agent' ? 'selected' : '' }}>AI Agent (LLM / Groq / OpenAI)</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ old('is_active', $integration->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-semibold mb-3"><i class="bi bi-key me-1"></i> Credentials</h6>
                    <div class="small text-muted mb-3">Credentials are encrypted at rest. Leave value blank to keep existing. Remove a row to delete that credential.</div>

                    <div id="credentialRows">
                        @php $creds = $integration->credentials ?? []; @endphp
                        @foreach($creds as $key => $value)
                        <div class="row g-2 mb-2 cred-row align-items-center">
                            <div class="col-4">
                                <input type="text" name="credential_keys[]" value="{{ $key }}" class="form-control form-control-solid form-control-sm" placeholder="Key">
                            </div>
                            <div class="col-7">
                                <div class="input-group input-group-sm">
                                    <input type="password" name="credential_values[]" value="" class="form-control form-control-solid form-control-sm cred-value-input" placeholder="Leave blank to keep existing">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCredVisibility(this)" title="Show/Hide">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text text-success small"><i class="bi bi-check-circle"></i> Saved</div>
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.cred-row').remove()"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="addCredRow">
                        <i class="bi bi-plus"></i> Add Credential
                    </button>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-fb-dark w-auto fw-normal fs-6"><i class="bi bi-check-lg"></i> Update Integration</button>
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
            <input type="text" name="credential_keys[]" value="${key}" class="form-control form-control-solid form-control-sm" placeholder="Key (e.g. api_key)">
        </div>
        <div class="col-7">
            <input type="text" name="credential_values[]" value="${value}" class="form-control form-control-solid form-control-sm" placeholder="Value">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.cred-row').remove()"><i class="bi bi-x"></i></button>
        </div>
    `;
    container.appendChild(row);
}
function toggleCredVisibility(btn) {
    const input = btn.closest('.input-group').querySelector('.cred-value-input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
document.getElementById('addCredRow').addEventListener('click', () => addCredRow());
</script>
@endpush
