@extends('flow-builder::layouts.app')
@section('title', 'Builder: ' . $flow->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('flow-builder.flows.index') }}" class="text-dark">Flows</a></li>
    <li class="breadcrumb-item active">Builder: {{ $flow->name }}</li>
@endsection
@section('topbar-actions')
    <button class="btn btn-sm btn-fb-dark-outline w-300 fw-normal fs-6" id="btnAutoLayout"><i class="bi bi-grid-3x3-gap"></i> Auto Layout</button>
    <button class="btn btn-sm btn-fb-dark w-300 fw-normal fs-6" id="btnSave"><i class="bi bi-save"></i> Save</button>
@endsection

@push('styles')
<style>
    .builder-toolbar {
        background: #fff; border-bottom: 1px solid #e5e7eb;
        padding: .5rem 1rem; display: flex; gap: .5rem; flex-wrap: wrap;
    }
    .builder-toolbar .btn { font-size: .8rem; }
    .builder-canvas {
        position: relative; width: 100%; height: calc(100vh - 200px);
        background: #f9fafb; overflow: auto; border-radius: .5rem; border: 1px solid #e5e7eb;
        background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px);
        background-size: 20px 20px;
    }
    .builder-canvas svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
    .flow-node {
        position: absolute; width: 200px; background: #fff; border: 2px solid #e5e7eb;
        border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.08); cursor: grab;
        user-select: none; z-index: 10; transition: box-shadow .2s;
    }
    .flow-node:hover { box-shadow: 0 4px 16px rgba(0,0,0,.15); }
    .flow-node.dragging { cursor: grabbing; box-shadow: 0 8px 24px rgba(0,0,0,.2); z-index: 20; }
    .flow-node .node-header {
        padding: .5rem .75rem; border-bottom: 1px solid #f3f4f6;
        border-radius: .75rem .75rem 0 0; font-weight: 600; font-size: .8rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    .flow-node .node-body { padding: .5rem .75rem; font-size: .75rem; color: #6b7280; }
    .flow-node .node-actions { display: flex; gap: 2px; }
    .flow-node .node-actions button { background: none; border: none; cursor: pointer; padding: 2px 4px; font-size: .75rem; color: #9ca3af; }
    .flow-node .node-actions button:hover { color: #111; }
    .node-type-trigger .node-header { background: #dbeafe; color: #1e40af; }
    .node-type-condition .node-header { background: #fef3c7; color: #92400e; }
    .node-type-action .node-header { background: #d1fae5; color: #065f46; }
    .node-type-operation .node-header { background: #ede9fe; color: #5b21b6; }
    .node-type-integration .node-header { background: #fce7f3; color: #9d174d; }
    .node-type-ai_agent .node-header { background: #e0f2fe; color: #0369a1; }
    .port {
        width: 12px; height: 12px; border-radius: 50%; background: #9ca3af;
        border: 2px solid #fff; position: absolute; cursor: crosshair; z-index: 15;
    }
    .port-out { bottom: -6px; left: 50%; transform: translateX(-50%); background: #4f46e5; }
    .port-in { top: -6px; left: 50%; transform: translateX(-50%); background: #059669; }
    .save-toast {
        position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
        padding: .75rem 1.25rem; border-radius: .5rem; color: #fff;
        font-weight: 600; display: none;
    }
</style>
@endpush

@section('content')
<div class="builder-toolbar">
    <button class="btn btn-outline-warning btn-sm" onclick="addNode('condition')"><i class="bi bi-signpost-split"></i> Condition</button>
    <button class="btn btn-outline-success btn-sm" onclick="addNode('action')"><i class="bi bi-gear"></i> Action</button>
    <button class="btn btn-outline-info btn-sm" onclick="addNode('operation')"><i class="bi bi-calculator"></i> Operation</button>
    <button class="btn btn-outline-danger btn-sm" onclick="addNode('integration')"><i class="bi bi-plug"></i> Integration</button>
    <button class="btn btn-outline-primary btn-sm" onclick="addNode('ai_agent')"><i class="bi bi-robot"></i> AI Agent</button>
    <span class="ms-auto small text-muted" id="statusText">Drag nodes to reposition. Click port to connect.</span>
</div>

<div class="builder-canvas" id="canvas">
    <svg id="svgCanvas"></svg>
</div>

{{-- Node Edit Modal --}}
<div class="modal fade" id="nodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Node</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editNodeId">
                <div class="mb-3">
                    <label class="form-label">Node Name</label>
                    <input type="text" class="form-control form-control-solid" id="editNodeName">
                </div>

                {{-- Action Node Config Panel --}}
                <div id="actionConfigPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Action Type</label>
                        <select class="form-select" id="actionType">
                            <option value="">Select action…</option>
                            <optgroup label="Query">
                                <option value="get">Get Records (multiple)</option>
                                <option value="first">Get First Record</option>
                                <option value="find">Find by ID</option>
                            </optgroup>
                            <optgroup label="Write">
                                <option value="create">Create Record</option>
                                <option value="update">Update Record</option>
                                <option value="delete">Delete Record</option>
                            </optgroup>
                            <optgroup label="Numeric">
                                <option value="increment">Increment Field</option>
                                <option value="decrement">Decrement Field</option>
                            </optgroup>
                            <optgroup label="Notify">
                                <option value="send_notification">Send Notification</option>
                                <option value="send_email">Send Email</option>
                                <option value="send_whatsapp">Send WhatsApp</option>
                            </optgroup>
                        </select>
                    </div>

                    {{-- Database action fields --}}
                    <div id="dbActionFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Target Model</label>
                            <select class="form-select" id="actionModel">
                                <option value="">Loading models…</option>
                            </select>
                        </div>

                        <div class="mb-3" id="resultKeyGroup">
                            <label class="form-label">Result Variable Name <span class="text-muted small">(optional — store result for next nodes)</span></label>
                            <input type="text" class="form-control form-control-solid" id="actionResultKey" placeholder="e.g. new_record">
                        </div>

                        {{-- Find By section for update/delete --}}
                        <div id="findBySection" class="d-none mb-3">
                            <label class="form-label">Find Record By</label>
                            <div id="findByRows"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addFindByRow">
                                <i class="bi bi-plus"></i> Add Find Condition
                            </button>
                        </div>

                        {{-- Field Mapping for create/update --}}
                        <div id="fieldMappingSection" class="d-none">
                            <label class="form-label">Field Mapping</label>
                            <div class="small text-muted mb-2">Map model fields to values. Use <code>@{{variable}}</code> to reference previous node outputs.</div>
                            <div id="fieldMappingRows"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addFieldMappingRow">
                                <i class="bi bi-plus"></i> Add Field
                            </button>
                        </div>

                        {{-- Inc/Dec specific --}}
                        <div id="incDecSection" class="d-none mb-3">
                            <div class="mb-3">
                                <label class="form-label">Field</label>
                                <select class="form-select form-select-solid" id="incDecField"><option value="">Select field…</option></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Value</label>
                                <input type="text" class="form-control form-control-solid" id="incDecValue" value="1" placeholder="1 or @{{variable}}">
                            </div>
                        </div>

                        {{-- Query section: where, select columns, order by, limit --}}
                        <div id="querySection" class="d-none">
                            {{-- Find by ID (for find action) --}}
                            <div id="findIdSection" class="d-none mb-3">
                                <label class="form-label">Record ID</label>
                                <div id="findIdContainer"></div>
                            </div>

                            {{-- Where conditions --}}
                            <div id="whereSection" class="d-none mb-3">
                                <label class="form-label">Where Conditions</label>
                                <div class="small text-muted mb-2">Filter records. All conditions are combined with AND.</div>
                                <div id="whereRows"></div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="addWhereRow">
                                    <i class="bi bi-plus"></i> Add Condition
                                </button>
                            </div>

                            {{-- Select columns --}}
                            <div id="selectColumnsSection" class="d-none mb-3">
                                <label class="form-label">Select Columns <span class="text-muted small">(optional — leave empty for all)</span></label>
                                <div id="selectColumnsContainer"></div>
                            </div>

                            {{-- Order By --}}
                            <div id="orderBySection" class="d-none mb-3">
                                <label class="form-label">Order By</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <select class="form-select" id="orderByField"><option value="">No ordering</option></select>
                                    </div>
                                    <div class="col-5">
                                        <select class="form-select" id="orderByDirection">
                                            <option value="asc">Ascending</option>
                                            <option value="desc">Descending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Limit --}}
                            <div id="limitSection" class="d-none mb-3">
                                <label class="form-label">Limit <span class="text-muted small">(optional)</span></label>
                                <input type="number" class="form-control form-control-solid" id="queryLimit" min="1" placeholder="No limit">
                            </div>
                        </div>

                        {{-- Media (Spatie Media Library) --}}
                        <div id="mediaSection" class="d-none mt-3">
                            <hr class="my-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="mediaEnabled">
                                <label class="form-check-label fw-semibold" for="mediaEnabled">
                                    <i class="bi bi-image"></i> Media <span class="text-muted small fw-normal">(Spatie Media Library)</span>
                                </label>
                            </div>
                            <div id="mediaFields" class="d-none ps-3 border-start border-2">
                                {{-- Write: create / update --}}
                                <div id="mediaWriteFields">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label">Collection</label>
                                            <input type="text" class="form-control form-control-solid" id="mediaCollection" placeholder="default">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Action</label>
                                            <select class="form-select" id="mediaAction">
                                                <option value="add">Add to collection</option>
                                                <option value="replace">Replace collection</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Media Source <span class="text-muted small">(URL or file path)</span></label>
                                        <div id="mediaSourceContainer"></div>
                                        <div class="form-text">e.g. <code>https://…</code> or absolute server path. Supports <code>@{{variable}}</code>.</div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Custom File Name <span class="text-muted small">(optional)</span></label>
                                        <div id="mediaFileNameContainer"></div>
                                    </div>
                                </div>
                                {{-- Read: get / first / find --}}
                                <div id="mediaReadFields" class="d-none">
                                    <div class="mb-2">
                                        <label class="form-label">Collections to Load <span class="text-muted small">(comma-separated, blank = all)</span></label>
                                        <input type="text" class="form-control form-control-solid" id="mediaCollections" placeholder="default, images…">
                                        <div class="form-text">A <code>media</code> key with URLs is appended to each record in the result.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Condition Node Config Panel --}}
                <div id="conditionConfigPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Field to Check</label>
                        <div id="condFieldContainer"></div>
                        <div class="form-text">Select a variable or type a dot notation path (e.g. <code>payload.status</code>).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Operator</label>
                        <select class="form-select" id="condOperator">
                            <option value="equals">Equals (==)</option>
                            <option value="not_equals">Not Equals (!=)</option>
                            <option value="greater_than">Greater Than (&gt;)</option>
                            <option value="less_than">Less Than (&lt;)</option>
                            <option value="greater_or_equal">Greater or Equal (&gt;=)</option>
                            <option value="less_or_equal">Less or Equal (&lt;=)</option>
                            <option value="contains">Contains</option>
                            <option value="not_contains">Not Contains</option>
                            <option value="starts_with">Starts With</option>
                            <option value="ends_with">Ends With</option>
                            <option value="in">In (comma-separated list)</option>
                            <option value="exists">Exists (not empty)</option>
                            <option value="not_exists">Not Exists (empty/null)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="condValueGroup">
                        <label class="form-label">Value</label>
                        <div id="condValueContainer"></div>
                        <div class="form-text">Enter a static value or pick a variable from the dropdown.</div>
                    </div>
                </div>

                {{-- Operation Node Config Panel --}}
                <div id="operationConfigPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Operation Type</label>
                        <select class="form-select" id="operationType">
                            <option value="">Select operation…</option>
                            <option value="sum">Sum (add values)</option>
                            <option value="subtract">Subtract</option>
                            <option value="multiply">Multiply</option>
                            <option value="divide">Divide</option>
                            <option value="format_text">Format Text</option>
                            <option value="loop">Loop (iterate items)</option>
                            <option value="delay">Delay (wait)</option>
                        </select>
                    </div>

                    {{-- Math operations: values list --}}
                    <div id="opMathFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Values</label>
                            <div class="small text-muted mb-2">Add values to calculate. Use <code>@{{variable}}</code> to reference previous node outputs.</div>
                            <div id="opValuesRows"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addOpValueRow">
                                <i class="bi bi-plus"></i> Add Value
                            </button>
                        </div>
                    </div>

                    {{-- Format text --}}
                    <div id="opFormatFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Text Template</label>
                            <textarea class="form-control form-control-solid" rows="3" id="opTemplate" placeholder="Hello @{{payload.name}}, your total is @{{_node_1_result}}"></textarea>
                            <div class="form-text">Use <code>@{{variable}}</code> placeholders. They will be replaced with actual values at runtime.</div>
                        </div>
                    </div>

                    {{-- Loop --}}
                    <div id="opLoopFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Items Field</label>
                            <input type="text" class="form-control form-control-solid" id="opLoopField" placeholder="e.g. payload.items or _node_1_result">
                            <div class="form-text">Dot notation path to an array. Downstream nodes will execute for each item.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Loop Variable Name</label>
                            <input type="text" class="form-control form-control-solid" id="opLoopAs" placeholder="item" value="item">
                            <div class="form-text">Access the current item in downstream nodes as <code>@{{item.field}}</code>. Index available as <code>@{{item_index}}</code>.</div>
                        </div>
                    </div>

                    {{-- Delay --}}
                    <div id="opDelayFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Delay Mode</label>
                            <select class="form-select" id="opDelayMode">
                                <option value="static">Static (fixed seconds)</option>
                                <option value="random">Random (between two values)</option>
                            </select>
                        </div>
                        <div id="opDelayStaticFields">
                            <div class="mb-3">
                                <label class="form-label">Seconds</label>
                                <input type="number" class="form-control form-control-solid" id="opDelaySeconds" min="0" placeholder="e.g. 30">
                                <div class="form-text">Flow execution will pause for this many seconds.</div>
                            </div>
                        </div>
                        <div id="opDelayRandomFields" class="d-none">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Min Seconds</label>
                                    <input type="number" class="form-control form-control-solid" id="opDelayMin" min="0" placeholder="e.g. 10">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Max Seconds</label>
                                    <input type="number" class="form-control form-control-solid" id="opDelayMax" min="0" placeholder="e.g. 180">
                                </div>
                            </div>
                            <div class="form-text mt-1">A random delay between min and max seconds will be chosen at runtime.</div>
                        </div>
                    </div>

                    {{-- Result key --}}
                    <div id="opResultKeyGroup" class="mb-3">
                        <label class="form-label">Result Variable Name <span class="text-muted small">(optional)</span></label>
                        <input type="text" class="form-control form-control-solid" id="opResultKey" placeholder="e.g. total">
                    </div>
                </div>

                {{-- Integration Node Config Panel --}}
                <div id="integrationConfigPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Integration Type <span class="text-danger">*</span></label>
                        <select class="form-select form-select-solid" id="integrationType">
                            <option value="">Select type…</option>
                            <option value="webhook">Webhook (HTTP Request)</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="firebase">Firebase Push Notification</option>
                            <option value="google_drive">Google Drive</option>
                            <option value="ai_agent">AI Agent (LLM / Groq / OpenAI)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Saved Integration <span class="text-muted small">(optional)</span></label>
                        <select class="form-select" id="integrationSelect">
                            <option value="">None — use config values</option>
                        </select>
                        <div class="form-text">Pick a saved integration for credentials, or leave empty to use config/env values.</div>
                    </div>

                    {{-- Webhook fields --}}
                    <div id="intWebhookFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">URL <span class="text-danger">*</span></label>
                            <div id="intWebhookUrlContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Method</label>
                            <select class="form-select" id="intWebhookMethod">
                                <option value="post">POST</option>
                                <option value="get">GET</option>
                                <option value="put">PUT</option>
                                <option value="patch">PATCH</option>
                                <option value="delete">DELETE</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Headers <span class="text-muted small">(optional)</span></label>
                            <div id="intHeaderRows"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addIntHeaderRow">
                                <i class="bi bi-plus"></i> Add Header
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body Parameters <span class="text-muted small">(optional)</span></label>
                            <div id="intBodyRows"></div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addIntBodyRow">
                                <i class="bi bi-plus"></i> Add Parameter
                            </button>
                        </div>
                    </div>

                    {{-- WhatsApp fields --}}
                    <div id="intWhatsappFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">To (Phone Number) <span class="text-danger">*</span></label>
                            <div id="intWhatsappToContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <div id="intWhatsappMsgContainer"></div>
                        </div>
                    </div>

                    {{-- Firebase fields --}}
                    <div id="intFirebaseFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Device Token <span class="text-danger">*</span></label>
                            <div id="intFirebaseTokenContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <div id="intFirebaseTitleContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body <span class="text-danger">*</span></label>
                            <div id="intFirebaseBodyContainer"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type <span class="text-muted small">(optional)</span></label>
                            <div id="intFirebaseTypeContainer"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type ID <span class="text-muted small">(optional)</span></label>
                            <div id="intFirebaseTypeIdContainer"></div>
                        </div>
                    </div>

                    {{-- Google Drive fields --}}
                    <div id="intGdriveFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Action</label>
                            <select class="form-select" id="intGdriveAction">
                                <option value="upload">Upload</option>
                            </select>
                        </div>
                        <div class="alert alert-info small mb-0">Google Drive integration requires additional OAuth2 setup.</div>
                    </div>

                    {{-- AI Agent fields --}}
                    <div id="intAiAgentFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">System Prompt <span class="text-muted small">(optional)</span></label>
                            <div id="intAiSystemPromptContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Message <span class="text-danger">*</span></label>
                            <div id="intAiUserMessageContainer"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Max Tokens <span class="text-muted small">(optional)</span></label>
                                <input type="number" class="form-control form-control-solid" id="intAiMaxTokens" min="1" max="32768" placeholder="e.g. 1024">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Temperature <span class="text-muted small">(0–2)</span></label>
                                <input type="number" class="form-control form-control-solid" id="intAiTemperature" min="0" max="2" step="0.1" placeholder="e.g. 0.7">
                            </div>
                        </div>
                        <div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i>Select a saved AI Agent integration above for credentials, or configure <code>FLOW_BUILDER_AI_API_KEY</code> in your <code>.env</code>.</div>
                    </div>

                    {{-- Result key --}}
                    <div class="mb-3">
                        <label class="form-label">Result Variable Name <span class="text-muted small">(optional)</span></label>
                        <input type="text" class="form-control form-control-solid" id="intResultKey" placeholder="e.g. webhook_response">
                    </div>
                </div>

                {{-- Generic JSON fallback for other types --}}
                <div id="genericDataPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Data (JSON)</label>
                        <textarea class="form-control form-control-solid font-monospace" rows="6" id="editNodeData"></textarea>
                    </div>
                </div>

                {{-- Trigger Node Config Panel --}}
                <div id="triggerConfigPanel" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Trigger Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="triggerType">
                            <option value="">Select trigger type…</option>
                            <option value="model_event">Model Event</option>
                            <option value="webhook">Webhook</option>
                            <option value="manual">Manual</option>
                            <option value="schedule">Schedule</option>
                        </select>
                    </div>

                    {{-- Model event fields --}}
                    <div id="triggerModelFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Model Class</label>
                            <select class="form-select" id="triggerModelClass">
                                <option value="">Loading models…</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event</label>
                            <select class="form-select" id="triggerModelEvent">
                                <option value="created">Created</option>
                                <option value="updated">Updated</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                    </div>

                    {{-- Schedule fields --}}
                    <div id="triggerScheduleFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Cron Expression</label>
                            <input type="text" class="form-control form-control-solid" id="triggerCronExpression" placeholder="*/5 * * * *" value="*/5 * * * *">
                        </div>
                    </div>

                    {{-- Webhook info --}}
                    <div id="triggerWebhookFields" class="d-none">
                        <div class="mb-3">
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle me-1"></i>A unique webhook token will be generated automatically when you save.
                            </div>
                        </div>
                    </div>

                    {{-- Manual info --}}
                    <div id="triggerManualFields" class="d-none">
                        <div class="mb-3">
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle me-1"></i>This flow can be triggered manually via code or the API.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-fb-dark w-auto fw-normal fs-6" id="saveNodeBtn">Save Node</button>
            </div>
        </div>
    </div>
</div>

{{-- Connection Modal --}}
<div class="modal fade" id="connModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">New Connection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="connFrom">
                <input type="hidden" id="connTo">
                <div class="mb-3">
                    <label class="form-label">Condition Value</label>
                    <select class="form-select" id="connCondition">
                        <option value="">None (always)</option>
                        <option value="true">True</option>
                        <option value="false">False</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-fb" id="saveConnBtn">Connect</button>
            </div>
        </div>
    </div>
</div>

<div class="save-toast" id="saveToast"></div>
@endsection

@push('scripts')
<script>
const FLOW_ID = {{ $flow->id }};
const SAVE_URL = "{{ route('flow-builder.flows.builder.save', $flow) }}";
const MODELS_URL = "{{ route('flow-builder.api.models') }}";
const MODEL_FIELDS_URL = "{{ route('flow-builder.api.model-fields') }}";
const INTEGRATIONS_URL = "{{ route('flow-builder.api.integrations') }}";
const CSRF = "{{ csrf_token() }}";
const EXISTING_TRIGGER = @json($triggerData);
let nodes = @json($nodes);
let connections = @json($connections);
let tempIdCounter = -1;
let draggingNode = null;
let dragOffset = { x: 0, y: 0 };
let connectingFrom = null;
let cachedModels = null;
let cachedFields = {};

const canvas = document.getElementById('canvas');
const svgCanvas = document.getElementById('svgCanvas');

// ==========================================
// MODEL DISCOVERY
// ==========================================
async function loadModels() {
    if (cachedModels) return cachedModels;
    try {
        const res = await fetch(MODELS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        cachedModels = await res.json();
    } catch (e) { cachedModels = []; }
    return cachedModels;
}

async function loadModelFields(modelClass) {
    if (cachedFields[modelClass]) return cachedFields[modelClass];
    try {
        const res = await fetch(MODEL_FIELDS_URL + '?model=' + encodeURIComponent(modelClass), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        cachedFields[modelClass] = await res.json();
    } catch (e) { cachedFields[modelClass] = { columns: [], fillable: [] }; }
    return cachedFields[modelClass];
}

function populateModelSelect(selectEl, selectedVal) {
    loadModels().then(models => {
        selectEl.innerHTML = '<option value="">Select model…</option>';
        models.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.class;
            opt.textContent = m.name + ' (' + m.class + ')';
            if (m.class === selectedVal) opt.selected = true;
            selectEl.appendChild(opt);
        });
    });
}

function getAvailableVariables(currentNodeId) {
    const vars = [
        { value: '@{{payload}}', label: 'Trigger Payload (full)' },
    ];
    const visited = new Set();
    function findUpstream(nodeId) {
        connections.forEach(c => {
            if (c.to_node_id === nodeId && !visited.has(c.from_node_id)) {
                visited.add(c.from_node_id);
                findUpstream(c.from_node_id);
            }
        });
    }
    findUpstream(currentNodeId);

    nodes.forEach(n => {
        if (n.id === currentNodeId) return;
        const label = n.name || n.type;
        vars.push({ value: '@{{_node_' + n.id + '_result}}', label: label + ' → Full Result' });
        if (n.data && n.data.result_key) {
            vars.push({ value: '@{{' + n.data.result_key + '}}', label: label + ' → ' + n.data.result_key });
        }
        if (n.data && n.data.model && n.data.action === 'create' && cachedFields[n.data.model]) {
            const fields = cachedFields[n.data.model].columns || [];
            fields.forEach(f => {
                const key = n.data.result_key || ('_node_' + n.id + '_result');
                vars.push({ value: '@{{' + key + '.' + f + '}}', label: label + ' → ' + f });
            });
        }
        // Loop variables: expose current item and index
        if (n.type === 'operation' && n.data && n.data.type === 'loop') {
            const loopVar = n.data.as || 'item';
            vars.push({ value: '@{{' + loopVar + '}}', label: label + ' → Current Item (' + loopVar + ')' });
            vars.push({ value: '@{{' + loopVar + '_index}}', label: label + ' → Index (' + loopVar + '_index)' });
            // If the loop iterates over a model result, show sub-fields
            const srcField = n.data.field || '';
            const srcNode = nodes.find(sn => {
                if (!sn.data) return false;
                const rk = sn.data.result_key || ('_node_' + sn.id + '_result');
                return rk === srcField || srcField.startsWith(rk);
            });
            if (srcNode && srcNode.data && srcNode.data.model && cachedFields[srcNode.data.model]) {
                const fields = cachedFields[srcNode.data.model].columns || [];
                fields.forEach(f => {
                    vars.push({ value: '@{{' + loopVar + '.' + f + '}}', label: label + ' → ' + loopVar + '.' + f });
                });
            }
        }
    });
    return vars;
}

function buildValueInput(name, currentValue, currentNodeId) {
    const vars = getAvailableVariables(currentNodeId);
    const wrapper = document.createElement('div');
    wrapper.className = 'input-group';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-solid form-control-sm';
    input.name = name;
    input.value = currentValue || '';
    input.placeholder = 'Static value or @{{variable}}';

    const dropdown = document.createElement('button');
    dropdown.type = 'button';
    dropdown.className = 'btn btn-secondary btn-sm dropdown-toggle';
    dropdown.dataset.bsToggle = 'dropdown';
    dropdown.innerHTML = '<i class="bi bi-link-45deg"></i>';

    const menu = document.createElement('ul');
    menu.className = 'dropdown-menu dropdown-menu-end';
    menu.style.maxHeight = '250px';
    menu.style.overflow = 'auto';

    vars.forEach(v => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item small';
        a.href = '#';
        a.textContent = v.label;
        a.addEventListener('click', e => {
            e.preventDefault();
            input.value = v.value;
            input.dispatchEvent(new Event('change'));
        });
        li.appendChild(a);
        menu.appendChild(li);
    });

    wrapper.appendChild(input);
    wrapper.appendChild(dropdown);
    wrapper.appendChild(menu);
    return wrapper;
}

function buildTextareaValueInput(name, currentValue, currentNodeId) {
    const vars = getAvailableVariables(currentNodeId);
    const wrapper = document.createElement('div');

    const textarea = document.createElement('textarea');
    textarea.className = 'form-control form-control-solid form-control-sm mb-1';
    textarea.name = name;
    textarea.rows = 3;
    textarea.value = currentValue || '';
    textarea.placeholder = 'Static text or @{{variable}}';

    const btnRow = document.createElement('div');
    btnRow.className = 'd-flex';

    const dropdown = document.createElement('button');
    dropdown.type = 'button';
    dropdown.className = 'btn btn-secondary btn-sm dropdown-toggle';
    dropdown.dataset.bsToggle = 'dropdown';
    dropdown.innerHTML = '<i class="bi bi-link-45deg"></i> Insert Variable';

    const menu = document.createElement('ul');
    menu.className = 'dropdown-menu';
    menu.style.maxHeight = '250px';
    menu.style.overflow = 'auto';

    vars.forEach(v => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item small';
        a.href = '#';
        a.textContent = v.label;
        a.addEventListener('click', e => {
            e.preventDefault();
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(end);
            textarea.value = before + v.value + after;
            textarea.selectionStart = textarea.selectionEnd = start + v.value.length;
            textarea.focus();
        });
        li.appendChild(a);
        menu.appendChild(li);
    });

    btnRow.appendChild(dropdown);
    btnRow.appendChild(menu);
    wrapper.appendChild(textarea);
    wrapper.appendChild(btnRow);
    return wrapper;
}

// ==========================================
// FIELD MAPPING ROWS
// ==========================================
function addFieldMappingRow(container, field, value, modelClass, nodeId) {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 field-mapping-row align-items-center';

    const colField = document.createElement('div');
    colField.className = 'col-4';
    const fieldSelect = document.createElement('select');
    fieldSelect.className = 'form-select field-select';
    fieldSelect.innerHTML = '<option value="">Select field…</option>';
    fieldSelect.dataset.selectedValue = field;  // store for async restore
    colField.appendChild(fieldSelect);

    if (modelClass && cachedFields[modelClass]) {
        const cols = cachedFields[modelClass].columns || [];
        cols.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === field) opt.selected = true;
            fieldSelect.appendChild(opt);
        });
    }

    const colValue = document.createElement('div');
    colValue.className = 'col-7';
    colValue.appendChild(buildValueInput('field_value', value, nodeId));

    const colDel = document.createElement('div');
    colDel.className = 'col-1';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.addEventListener('click', () => row.remove());
    colDel.appendChild(delBtn);

    row.appendChild(colField);
    row.appendChild(colValue);
    row.appendChild(colDel);
    container.appendChild(row);
}

function addFindByRow(container, field, value, modelClass, nodeId) {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 findby-row align-items-center';

    const colField = document.createElement('div');
    colField.className = 'col-4';
    const fieldSelect = document.createElement('select');
    fieldSelect.className = 'form-select findby-field';
    fieldSelect.innerHTML = '<option value="">Select field…</option>';
    fieldSelect.dataset.selectedValue = field;  // store for async restore
    colField.appendChild(fieldSelect);

    if (modelClass && cachedFields[modelClass]) {
        const cols = cachedFields[modelClass].columns || [];
        ['id', ...cols].forEach(c => {
            if (fieldSelect.querySelector(`option[value="${c}"]`)) return;
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === field) opt.selected = true;
            fieldSelect.appendChild(opt);
        });
    }

    const colValue = document.createElement('div');
    colValue.className = 'col-7';
    colValue.appendChild(buildValueInput('findby_value', value, nodeId));

    const colDel = document.createElement('div');
    colDel.className = 'col-1';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.addEventListener('click', () => row.remove());
    colDel.appendChild(delBtn);

    row.appendChild(colField);
    row.appendChild(colValue);
    row.appendChild(colDel);
    container.appendChild(row);
}

function addWhereRow(container, field, operator, value, modelClass, nodeId) {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 where-row align-items-center';

    const colField = document.createElement('div');
    colField.className = 'col-4';
    const fieldSelect = document.createElement('select');
    fieldSelect.className = 'form-select where-field';
    fieldSelect.innerHTML = '<option value="">Select field…</option>';
    fieldSelect.dataset.selectedValue = field;  // store for async restore
    colField.appendChild(fieldSelect);

    if (modelClass && cachedFields[modelClass]) {
        const cols = cachedFields[modelClass].columns || [];
        ['id', ...cols].forEach(c => {
            if (fieldSelect.querySelector('option[value="' + c + '"]')) return;
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === field) opt.selected = true;
            fieldSelect.appendChild(opt);
        });
    }

    const colOp = document.createElement('div');
    colOp.className = 'col-2';
    const opSelect = document.createElement('select');
    opSelect.className = 'form-select where-operator';
    const ops = [
        ['=', '='], ['!=', '!='], ['>', '>'], ['<', '<'],
        ['>=', '>='], ['<=', '<='], ['like', 'LIKE'], ['not like', 'NOT LIKE']
    ];
    ops.forEach(([val, label]) => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = label;
        if (val === operator) opt.selected = true;
        opSelect.appendChild(opt);
    });
    colOp.appendChild(opSelect);

    const colValue = document.createElement('div');
    colValue.className = 'col-5';
    colValue.appendChild(buildValueInput('where_value', value, nodeId));

    const colDel = document.createElement('div');
    colDel.className = 'col-1';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.addEventListener('click', () => row.remove());
    colDel.appendChild(delBtn);

    row.appendChild(colField);
    row.appendChild(colOp);
    row.appendChild(colValue);
    row.appendChild(colDel);
    container.appendChild(row);
}

async function refreshFieldSelects(modelClass) {
    if (!modelClass) return;
    const info = await loadModelFields(modelClass);
    const cols = info.columns || [];

    document.querySelectorAll('#fieldMappingRows .field-select').forEach(sel => {
        const cur = sel.dataset.selectedValue !== undefined ? sel.dataset.selectedValue : sel.value;
        sel.innerHTML = '<option value="">Select field…</option>';
        cols.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === cur) opt.selected = true;
            sel.appendChild(opt);
        });
        delete sel.dataset.selectedValue;
    });

    document.querySelectorAll('#findByRows .findby-field').forEach(sel => {
        const cur = sel.dataset.selectedValue !== undefined ? sel.dataset.selectedValue : sel.value;
        sel.innerHTML = '<option value="">Select field…</option>';
        ['id', ...cols].forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === cur) opt.selected = true;
            sel.appendChild(opt);
        });
        delete sel.dataset.selectedValue;
    });

    document.querySelectorAll('#whereRows .where-field').forEach(sel => {
        const cur = sel.dataset.selectedValue !== undefined ? sel.dataset.selectedValue : sel.value;
        sel.innerHTML = '<option value="">Select field…</option>';
        ['id', ...cols].forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === cur) opt.selected = true;
            sel.appendChild(opt);
        });
        delete sel.dataset.selectedValue;
    });

    const incDecField = document.getElementById('incDecField');
    if (incDecField) {
        const cur = incDecField.dataset.selectedValue !== undefined ? incDecField.dataset.selectedValue : incDecField.value;
        incDecField.innerHTML = '<option value="">Select field…</option>';
        cols.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === cur) opt.selected = true;
            incDecField.appendChild(opt);
        });
        delete incDecField.dataset.selectedValue;
    }
}

// ==========================================
// RENDER
// ==========================================
function render() {
    canvas.querySelectorAll('.flow-node').forEach(el => el.remove());
    nodes.forEach(n => renderNode(n));
    renderConnections();
}

function getNodeSummary(n) {
    const d = n.data || {};
    if (n.type === 'trigger') {
        if (!d.trigger_type) return 'Click to configure trigger';
        let s = escHtml(d.trigger_type);
        if (d.trigger_type === 'model_event' && d.model_class) {
            const short = d.model_class.split('\\').pop();
            s += ' · ' + escHtml(short) + ' · ' + escHtml(d.model_event || '');
        } else if (d.trigger_type === 'schedule' && d.cron_expression) {
            s += ' · ' + escHtml(d.cron_expression);
        }
        return s;
    }
    if (n.type === 'action' && d.action) {
        let s = escHtml(d.action);
        if (d.model) {
            const short = d.model.split('\\').pop();
            s += ' · ' + escHtml(short);
        }
        if (['get', 'first', 'find'].includes(d.action)) {
            if (d.where && d.where.length) s += ' · ' + d.where.length + ' where';
            if (d.order_by) s += ' · order:' + escHtml(d.order_by);
            if (d.limit) s += ' · limit:' + d.limit;
            if (d.find_id) s += ' · id:' + escHtml(d.find_id);
            return s;
        }
        const attrCount = d.attributes ? Object.keys(d.attributes).length : 0;
        if (attrCount) s += ' · ' + attrCount + ' fields';
        return s;
    }
    if (n.type === 'condition' && d.field) {
        return escHtml(d.field) + ' ' + escHtml(d.operator || '==') + ' ' + escHtml(String(d.value ?? ''));
    }
    if (n.type === 'operation' && d.type) {
        let s = escHtml(d.type);
        if (['sum', 'subtract', 'multiply', 'divide'].includes(d.type) && d.values) {
            s += ' · ' + d.values.length + ' values';
        } else if (d.type === 'format_text') {
            s += ' · template';
        } else if (d.type === 'loop' && d.field) {
            s += ' · ' + escHtml(d.field);
        } else if (d.type === 'delay') {
            if (d.delay_mode === 'random') {
                s += ' · ' + (d.min_seconds || 0) + 's–' + (d.max_seconds || 0) + 's';
            } else {
                s += ' · ' + (d.seconds || 0) + 's';
            }
        }
        if (d.result_key) s += ' → ' + escHtml(d.result_key);
        return s;
    }
    if ((n.type === 'integration' || n.type === 'ai_agent') && d.type) {
        let s = escHtml(d.type);
        if (d.type === 'webhook' && d.method) s += ' ' + d.method.toUpperCase();
        if (d.url) s += ' · ' + escHtml(d.url).substring(0, 30);
        if (d.to) s += ' · to:' + escHtml(d.to);
        if (d.type === 'ai_agent' && d.user_message) s += ': ' + escHtml(d.user_message).substring(0, 28) + '…';
        if (d.integration_name) s += ' · ' + escHtml(d.integration_name);
        if (d.result_key) s += ' → ' + escHtml(d.result_key);
        return s;
    }
    return escHtml(n.type) + (d && Object.keys(d).length ? ' · ' + Object.keys(d).length + ' params' : '');
}

function renderNode(n) {
    const div = document.createElement('div');
    div.className = `flow-node node-type-${n.type}`;
    div.dataset.id = n.id;
    div.style.left = (n.position_x || 100) + 'px';
    div.style.top = (n.position_y || 100) + 'px';
    div.innerHTML = `
        <div class="port port-in" data-id="${n.id}" data-dir="in"></div>
        <div class="node-header">
            <span>${escHtml(n.name || n.type)}</span>
            <div class="node-actions">
                <button onclick="editNode(${n.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                <button onclick="deleteNode(${n.id})" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="node-body">${getNodeSummary(n)}</div>
        <div class="port port-out" data-id="${n.id}" data-dir="out"></div>
    `;
    div.addEventListener('mousedown', startDrag);
    div.querySelectorAll('.port').forEach(p => {
        p.addEventListener('mousedown', e => { e.stopPropagation(); startConnect(p); });
        p.addEventListener('mouseup', e => { e.stopPropagation(); endConnect(p); });
    });
    canvas.appendChild(div);
}

function renderConnections() {
    svgCanvas.innerHTML = '';
    connections.forEach(c => {
        const fromNode = nodes.find(n => n.id === c.from_node_id);
        const toNode = nodes.find(n => n.id === c.to_node_id);
        if (!fromNode || !toNode) return;
        const x1 = (fromNode.position_x || 100) + 100;
        const y1 = (fromNode.position_y || 100) + getNodeHeight(fromNode);
        const x2 = (toNode.position_x || 100) + 100;
        const y2 = (toNode.position_y || 100);
        const color = c.condition_value === 'true' ? '#059669' : c.condition_value === 'false' ? '#dc2626' : '#6366f1';
        const cy1 = y1 + 50, cy2 = y2 - 50;
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M${x1},${y1} C${x1},${cy1} ${x2},${cy2} ${x2},${y2}`);
        path.setAttribute('stroke', color);
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-linecap', 'round');
        const hitPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        hitPath.setAttribute('d', path.getAttribute('d'));
        hitPath.setAttribute('stroke', 'transparent');
        hitPath.setAttribute('stroke-width', '12');
        hitPath.setAttribute('fill', 'none');
        hitPath.style.cursor = 'pointer';
        hitPath.style.pointerEvents = 'stroke';
        hitPath.addEventListener('dblclick', () => {
            if (confirm('Delete this connection?')) {
                connections = connections.filter(cc => cc !== c);
                renderConnections();
            }
        });
        svgCanvas.appendChild(path);
        svgCanvas.appendChild(hitPath);
        if (c.condition_value) {
            const mx = (x1 + x2) / 2, my = (y1 + y2) / 2;
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', mx);
            text.setAttribute('y', my - 5);
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('fill', color);
            text.setAttribute('font-size', '11');
            text.setAttribute('font-weight', '600');
            text.textContent = c.condition_value;
            svgCanvas.appendChild(text);
        }
    });
}

function getNodeHeight() { return 80; }

// ==========================================
// DRAG
// ==========================================
function startDrag(e) {
    if (e.target.classList.contains('port')) return;
    const el = e.currentTarget;
    draggingNode = el;
    el.classList.add('dragging');
    const rect = el.getBoundingClientRect();
    dragOffset.x = e.clientX - rect.left;
    dragOffset.y = e.clientY - rect.top;
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    e.preventDefault();
}
function onDrag(e) {
    if (!draggingNode) return;
    const canvasRect = canvas.getBoundingClientRect();
    let x = e.clientX - canvasRect.left - dragOffset.x + canvas.scrollLeft;
    let y = e.clientY - canvasRect.top - dragOffset.y + canvas.scrollTop;
    x = Math.max(0, x); y = Math.max(0, y);
    draggingNode.style.left = x + 'px';
    draggingNode.style.top = y + 'px';
    const id = Number(draggingNode.dataset.id);
    const node = nodes.find(n => n.id === id);
    if (node) { node.position_x = x; node.position_y = y; }
    renderConnections();
}
function stopDrag() {
    if (draggingNode) draggingNode.classList.remove('dragging');
    draggingNode = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
}

// ==========================================
// CONNECT
// ==========================================
function startConnect(port) {
    if (port.dataset.dir === 'out') {
        connectingFrom = Number(port.dataset.id);
        document.getElementById('statusText').textContent = 'Click on target node\'s input port to connect…';
    }
}
function endConnect(port) {
    if (connectingFrom !== null && port.dataset.dir === 'in') {
        const toId = Number(port.dataset.id);
        if (connectingFrom !== toId) {
            document.getElementById('connFrom').value = connectingFrom;
            document.getElementById('connTo').value = toId;
            document.getElementById('connCondition').value = '';
            new bootstrap.Modal(document.getElementById('connModal')).show();
        }
    }
    connectingFrom = null;
    document.getElementById('statusText').textContent = 'Drag nodes to reposition. Click port to connect.';
}

// ==========================================
// ADD NODE
// ==========================================
function addNode(type) {
    if (type === 'trigger') {
        const existing = nodes.find(n => n.type === 'trigger');
        if (existing) {
            alert('Only one trigger node is allowed per flow.');
            return;
        }
    }
    const id = tempIdCounter--;
    const scroll = { x: canvas.scrollLeft, y: canvas.scrollTop };
    nodes.push({
        id, type, name: type.charAt(0).toUpperCase() + type.slice(1) + ' Node',
        data: {}, sort_order: nodes.length,
        position_x: 50 + scroll.x + Math.random() * 200,
        position_y: 50 + scroll.y + Math.random() * 200,
    });
    render();
}

// ==========================================
// EDIT NODE — Smart panels
// ==========================================
function editNode(id) {
    const node = nodes.find(n => n.id === id);
    if (!node) return;

    document.getElementById('editNodeId').value = id;
    document.getElementById('editNodeName').value = node.name || '';

    document.getElementById('actionConfigPanel').classList.add('d-none');
    document.getElementById('conditionConfigPanel').classList.add('d-none');
    document.getElementById('genericDataPanel').classList.add('d-none');
    document.getElementById('triggerConfigPanel').classList.add('d-none');
    document.getElementById('operationConfigPanel').classList.add('d-none');
    document.getElementById('integrationConfigPanel').classList.add('d-none');

    if (node.type === 'trigger') {
        showTriggerPanel(node);
    } else if (node.type === 'action') {
        showActionPanel(node);
    } else if (node.type === 'condition') {
        showConditionPanel(node);
    } else if (node.type === 'operation') {
        showOperationPanel(node);
    } else if (node.type === 'integration' || node.type === 'ai_agent') {
        showIntegrationPanel(node);
    } else {
        showGenericPanel(node);
    }

    new bootstrap.Modal(document.getElementById('nodeModal')).show();
}

function showOperationPanel(node) {
    const panel = document.getElementById('operationConfigPanel');
    panel.classList.remove('d-none');

    const d = node.data || {};
    const opType = document.getElementById('operationType');
    opType.value = d.type || '';

    document.getElementById('opResultKey').value = d.result_key || '';
    document.getElementById('opTemplate').value = d.template || '';
    document.getElementById('opLoopField').value = d.field || '';
    document.getElementById('opLoopAs').value = d.as || 'item';

    toggleOperationSubSections(d.type || '', d, node.id);

    opType.onchange = () => toggleOperationSubSections(opType.value, {}, node.id);
}

function toggleOperationSubSections(type, data, nodeId) {
    const isMath = ['sum', 'subtract', 'multiply', 'divide'].includes(type);

    document.getElementById('opMathFields').classList.toggle('d-none', !isMath);
    document.getElementById('opFormatFields').classList.toggle('d-none', type !== 'format_text');
    document.getElementById('opLoopFields').classList.toggle('d-none', type !== 'loop');
    document.getElementById('opDelayFields').classList.toggle('d-none', type !== 'delay');
    document.getElementById('opResultKeyGroup').classList.toggle('d-none', type === 'loop' || type === 'delay' || !type);

    if (type === 'delay') {
        const mode = (data && data.delay_mode) || 'static';
        document.getElementById('opDelayMode').value = mode;
        document.getElementById('opDelaySeconds').value = (data && data.seconds) || '';
        document.getElementById('opDelayMin').value = (data && data.min_seconds) || '';
        document.getElementById('opDelayMax').value = (data && data.max_seconds) || '';
        document.getElementById('opDelayStaticFields').classList.toggle('d-none', mode !== 'static');
        document.getElementById('opDelayRandomFields').classList.toggle('d-none', mode !== 'random');
        document.getElementById('opDelayMode').onchange = function () {
            document.getElementById('opDelayStaticFields').classList.toggle('d-none', this.value !== 'static');
            document.getElementById('opDelayRandomFields').classList.toggle('d-none', this.value !== 'random');
        };
    }

    if (isMath) {
        const container = document.getElementById('opValuesRows');
        container.innerHTML = '';
        const values = (data && data.values) ? data.values : [];
        if (values.length) {
            values.forEach(v => addOpValueRow(container, v, nodeId));
        } else {
            addOpValueRow(container, '', nodeId);
            addOpValueRow(container, '', nodeId);
        }
    }
}

function addOpValueRow(container, value, nodeId) {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 op-value-row align-items-center';

    const colValue = document.createElement('div');
    colValue.className = 'col-10';
    colValue.appendChild(buildValueInput('op_value', value, nodeId));

    const colDel = document.createElement('div');
    colDel.className = 'col-2';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.addEventListener('click', () => row.remove());
    colDel.appendChild(delBtn);

    row.appendChild(colValue);
    row.appendChild(colDel);
    container.appendChild(row);
}

function collectOperationData() {
    const data = {};
    data.type = document.getElementById('operationType').value;

    if (['sum', 'subtract', 'multiply', 'divide'].includes(data.type)) {
        data.values = [];
        document.querySelectorAll('#opValuesRows .op-value-row').forEach(row => {
            const val = row.querySelector('input[name="op_value"]').value;
            if (val !== '') data.values.push(val);
        });
    } else if (data.type === 'format_text') {
        data.template = document.getElementById('opTemplate').value;
    } else if (data.type === 'loop') {
        data.field = document.getElementById('opLoopField').value;
        data.as = document.getElementById('opLoopAs').value || 'item';
    } else if (data.type === 'delay') {
        data.delay_mode = document.getElementById('opDelayMode').value;
        if (data.delay_mode === 'static') {
            data.seconds = Number(document.getElementById('opDelaySeconds').value) || 0;
        } else {
            data.min_seconds = Number(document.getElementById('opDelayMin').value) || 0;
            data.max_seconds = Number(document.getElementById('opDelayMax').value) || 0;
        }
    }

    if (data.type !== 'loop' && data.type !== 'delay') {
        const rk = document.getElementById('opResultKey').value;
        if (rk) data.result_key = rk;
    }

    return data;
}

document.getElementById('addOpValueRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    addOpValueRow(document.getElementById('opValuesRows'), '', nodeId);
});

function showTriggerPanel(node) {
    const panel = document.getElementById('triggerConfigPanel');
    panel.classList.remove('d-none');

    const d = node.data || {};
    const triggerType = document.getElementById('triggerType');
    triggerType.value = d.trigger_type || '';

    // Populate model select
    populateModelSelect(document.getElementById('triggerModelClass'), d.model_class || '');

    document.getElementById('triggerModelEvent').value = d.model_event || 'created';
    document.getElementById('triggerCronExpression').value = d.cron_expression || '*/5 * * * *';

    toggleTriggerSubSections(d.trigger_type || '');

    triggerType.onchange = () => toggleTriggerSubSections(triggerType.value);
}

function toggleTriggerSubSections(type) {
    document.getElementById('triggerModelFields').classList.toggle('d-none', type !== 'model_event');
    document.getElementById('triggerScheduleFields').classList.toggle('d-none', type !== 'schedule');
    document.getElementById('triggerWebhookFields').classList.toggle('d-none', type !== 'webhook');
    document.getElementById('triggerManualFields').classList.toggle('d-none', type !== 'manual');
}

function collectTriggerData() {
    const data = {};
    data.trigger_type = document.getElementById('triggerType').value;

    if (data.trigger_type === 'model_event') {
        data.model_class = document.getElementById('triggerModelClass').value;
        data.model_event = document.getElementById('triggerModelEvent').value;
    } else if (data.trigger_type === 'schedule') {
        data.cron_expression = document.getElementById('triggerCronExpression').value;
    }

    return data;
}

function showActionPanel(node) {
    const panel = document.getElementById('actionConfigPanel');
    panel.classList.remove('d-none');

    const d = node.data || {};
    const actionType = document.getElementById('actionType');
    actionType.value = d.action || '';

    const modelSelect = document.getElementById('actionModel');
    populateModelSelect(modelSelect, d.model || '');

    document.getElementById('actionResultKey').value = d.result_key || '';

    toggleActionSubSections(d.action || '', d, node.id);

    actionType.onchange = () => toggleActionSubSections(actionType.value, {}, node.id);
    modelSelect.onchange = async () => {
        const mc = modelSelect.value;
        if (!mc) return;
        await refreshFieldSelects(mc);
        const action = document.getElementById('actionType').value;
        if (['get', 'first', 'find'].includes(action)) {
            populateSelectColumns(mc, [], node.id);
        }
        if (['get', 'first'].includes(action)) {
            populateOrderBy(mc, '', 'asc');
        }
    };
}

function populateSelectColumns(modelClass, selected, nodeId) {
    const selectContainer = document.getElementById('selectColumnsContainer');
    if (!selectContainer) return;
    if (!modelClass) { selectContainer.innerHTML = ''; return; }
    loadModelFields(modelClass).then(info => {
        const cols = info.columns || [];
        selectContainer.innerHTML = '';
        cols.forEach(c => {
            const div = document.createElement('div');
            div.className = 'form-check form-check-inline';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.value = c;
            cb.id = 'selcol_' + c;
            if (selected.includes(c)) cb.checked = true;
            const lbl = document.createElement('label');
            lbl.className = 'form-check-label small';
            lbl.htmlFor = 'selcol_' + c;
            lbl.textContent = c;
            div.appendChild(cb);
            div.appendChild(lbl);
            selectContainer.appendChild(div);
        });
    });
}

function populateOrderBy(modelClass, selectedField, selectedDir) {
    const orderByField = document.getElementById('orderByField');
    const orderByDir = document.getElementById('orderByDirection');
    if (!orderByField) return;
    if (!modelClass) {
        orderByField.innerHTML = '<option value="">No ordering</option>';
        return;
    }
    loadModelFields(modelClass).then(info => {
        const cols = info.columns || [];
        orderByField.innerHTML = '<option value="">No ordering</option>';
        ['id', ...cols].forEach(c => {
            if (orderByField.querySelector('option[value="' + c + '"]')) return;
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === selectedField) opt.selected = true;
            orderByField.appendChild(opt);
        });
        orderByDir.value = selectedDir || 'asc';
    });
}

function toggleActionSubSections(action, data, nodeId) {
    const dbActions = ['create', 'update', 'delete', 'increment', 'decrement', 'get', 'first', 'find'];
    const queryActions = ['get', 'first', 'find'];
    const isDb = dbActions.includes(action);
    const isQuery = queryActions.includes(action);

    document.getElementById('dbActionFields').classList.toggle('d-none', !isDb);
    document.getElementById('findBySection').classList.toggle('d-none', !['update', 'delete', 'increment', 'decrement'].includes(action));
    document.getElementById('fieldMappingSection').classList.toggle('d-none', !['create', 'update'].includes(action));
    document.getElementById('incDecSection').classList.toggle('d-none', !['increment', 'decrement'].includes(action));
    document.getElementById('resultKeyGroup').classList.toggle('d-none', action === 'delete');

    // Query sections
    document.getElementById('querySection').classList.toggle('d-none', !isQuery);
    document.getElementById('findIdSection').classList.toggle('d-none', action !== 'find');
    document.getElementById('whereSection').classList.toggle('d-none', !['get', 'first'].includes(action));
    document.getElementById('selectColumnsSection').classList.toggle('d-none', !isQuery);
    document.getElementById('orderBySection').classList.toggle('d-none', !['get', 'first'].includes(action));
    document.getElementById('limitSection').classList.toggle('d-none', action !== 'get');

    // Media (Spatie) section
    const isMediaAction = ['create', 'update', 'get', 'first', 'find'].includes(action);
    document.getElementById('mediaSection').classList.toggle('d-none', !isMediaAction);
    if (isMediaAction) {
        const isWriteMedia = ['create', 'update'].includes(action);
        document.getElementById('mediaWriteFields').classList.toggle('d-none', !isWriteMedia);
        document.getElementById('mediaReadFields').classList.toggle('d-none', isWriteMedia);

        const media = (data && data.media) || null;
        const mediaOn = !!(media && media.enabled);
        document.getElementById('mediaEnabled').checked = mediaOn;
        document.getElementById('mediaFields').classList.toggle('d-none', !mediaOn);

        if (isWriteMedia) {
            document.getElementById('mediaCollection').value = (media && media.collection) || '';
            document.getElementById('mediaAction').value = (media && media.action) || 'add';
            const srcContainer = document.getElementById('mediaSourceContainer');
            srcContainer.innerHTML = '';
            srcContainer.appendChild(buildValueInput('media_source', (media && media.source) || '', nodeId));
            const fnContainer = document.getElementById('mediaFileNameContainer');
            fnContainer.innerHTML = '';
            fnContainer.appendChild(buildValueInput('media_file_name', (media && media.file_name) || '', nodeId));
        } else {
            document.getElementById('mediaCollections').value = (media && media.collections) || '';
        }
    }

    if (isDb) {
        const modelClass = data.model || document.getElementById('actionModel').value || '';

        const findByContainer = document.getElementById('findByRows');
        findByContainer.innerHTML = '';
        if (data.find_by) {
            Object.entries(data.find_by).forEach(([field, value]) => {
                addFindByRow(findByContainer, field, value, modelClass, nodeId);
            });
        }

        const mappingContainer = document.getElementById('fieldMappingRows');
        mappingContainer.innerHTML = '';
        if (data.attributes) {
            Object.entries(data.attributes).forEach(([field, value]) => {
                addFieldMappingRow(mappingContainer, field, value, modelClass, nodeId);
            });
        }

        if (['increment', 'decrement'].includes(action)) {
            const incDecEl = document.getElementById('incDecField');
            incDecEl.dataset.selectedValue = data.field || '';  // store for async restore
            incDecEl.value = data.field || '';
            document.getElementById('incDecValue').value = data.value ?? 1;
        }

        // Query-specific population
        if (isQuery) {
            // Find by ID
            if (action === 'find') {
                const findIdContainer = document.getElementById('findIdContainer');
                findIdContainer.innerHTML = '';
                findIdContainer.appendChild(buildValueInput('find_id', data.find_id || '', nodeId));
            }

            // Where conditions
            if (['get', 'first'].includes(action)) {
                const whereContainer = document.getElementById('whereRows');
                whereContainer.innerHTML = '';
                if (data.where && Array.isArray(data.where)) {
                    data.where.forEach(w => addWhereRow(whereContainer, w.field || '', w.operator || 'equals', w.value || '', modelClass, nodeId));
                }
            }

            // Select columns
            populateSelectColumns(modelClass, data.select_columns || [], nodeId);

            // Order By
            if (['get', 'first'].includes(action)) {
                populateOrderBy(modelClass, data.order_by || '', data.order_direction || 'asc');
            }

            // Limit
            if (action === 'get') {
                document.getElementById('queryLimit').value = data.limit || '';
            }
        }

        if (modelClass) {
            loadModelFields(modelClass).then(() => refreshFieldSelects(modelClass));
        }
    }
}

function showConditionPanel(node) {
    const panel = document.getElementById('conditionConfigPanel');
    panel.classList.remove('d-none');

    const d = node.data || {};

    // Build field input with variable dropdown
    const fieldContainer = document.getElementById('condFieldContainer');
    fieldContainer.innerHTML = '';
    fieldContainer.appendChild(buildValueInput('cond_field', d.field || '', node.id));

    document.getElementById('condOperator').value = d.operator || 'equals';

    // Build value input with variable dropdown
    const valueContainer = document.getElementById('condValueContainer');
    valueContainer.innerHTML = '';
    valueContainer.appendChild(buildValueInput('cond_value', d.value ?? '', node.id));

    const hideValueOps = ['exists', 'not_exists'];
    document.getElementById('condOperator').onchange = () => {
        document.getElementById('condValueGroup').classList.toggle('d-none',
            hideValueOps.includes(document.getElementById('condOperator').value));
    };
    document.getElementById('condOperator').dispatchEvent(new Event('change'));
}

// ==========================================
// INTEGRATION PANEL
// ==========================================
let cachedIntegrations = null;

async function loadIntegrations() {
    if (cachedIntegrations) return cachedIntegrations;
    try {
        const resp = await fetch(INTEGRATIONS_URL);
        cachedIntegrations = await resp.json();
    } catch (e) {
        cachedIntegrations = [];
    }
    return cachedIntegrations;
}

async function showIntegrationPanel(node) {
    const panel = document.getElementById('integrationConfigPanel');
    panel.classList.remove('d-none');

    const d = node.data || {};
    const typeSelect = document.getElementById('integrationType');
    typeSelect.value = d.type || (node.type === 'ai_agent' ? 'ai_agent' : '');

    document.getElementById('intResultKey').value = d.result_key || '';

    // Populate sub-sections FIRST (synchronously) so modal opens with correct data
    toggleIntegrationSubSections(typeSelect.value, d, node.id);

    // Load saved integrations dropdown (async — populates after modal opens)
    const intSelect = document.getElementById('integrationSelect');
    intSelect.innerHTML = '<option value="">None — use config values</option>';
    const integrations = await loadIntegrations();
    integrations.forEach(i => {
        const opt = document.createElement('option');
        opt.value = i.id;
        opt.textContent = i.name + ' (' + i.type + ')';
        if (String(i.id) === String(d.integration_id || '')) opt.selected = true;
        intSelect.appendChild(opt);
    });

    typeSelect.onchange = () => {
        toggleIntegrationSubSections(typeSelect.value, {}, node.id);
        // Auto-filter saved integrations by type
        const selectedType = typeSelect.value;
        intSelect.querySelectorAll('option').forEach(opt => {
            if (!opt.value) return; // keep "None" option
            const match = integrations.find(i => String(i.id) === opt.value);
            opt.classList.toggle('d-none', match && match.type !== selectedType);
        });
    };
}

function toggleIntegrationSubSections(type, data, nodeId) {
    document.getElementById('intWebhookFields').classList.toggle('d-none', type !== 'webhook');
    document.getElementById('intWhatsappFields').classList.toggle('d-none', type !== 'whatsapp');
    document.getElementById('intFirebaseFields').classList.toggle('d-none', type !== 'firebase');
    document.getElementById('intGdriveFields').classList.toggle('d-none', type !== 'google_drive');
    document.getElementById('intAiAgentFields').classList.toggle('d-none', type !== 'ai_agent');

    if (type === 'webhook') {
        const urlContainer = document.getElementById('intWebhookUrlContainer');
        urlContainer.innerHTML = '';
        urlContainer.appendChild(buildValueInput('int_webhook_url', data.url || '', nodeId));

        document.getElementById('intWebhookMethod').value = data.method || 'post';

        // Headers
        const headerContainer = document.getElementById('intHeaderRows');
        headerContainer.innerHTML = '';
        if (data.headers && typeof data.headers === 'object') {
            Object.entries(data.headers).forEach(([key, val]) => {
                addIntKVRow(headerContainer, key, val, 'int_header', nodeId);
            });
        }

        // Body
        const bodyContainer = document.getElementById('intBodyRows');
        bodyContainer.innerHTML = '';
        if (data.body && typeof data.body === 'object') {
            Object.entries(data.body).forEach(([key, val]) => {
                addIntKVRow(bodyContainer, key, val, 'int_body', nodeId);
            });
        }
    }

    if (type === 'whatsapp') {
        const toContainer = document.getElementById('intWhatsappToContainer');
        toContainer.innerHTML = '';
        toContainer.appendChild(buildValueInput('int_wa_to', data.to || '', nodeId));

        const msgContainer = document.getElementById('intWhatsappMsgContainer');
        msgContainer.innerHTML = '';
        msgContainer.appendChild(buildValueInput('int_wa_message', data.message || '', nodeId));
    }

    if (type === 'firebase') {
        const tokenContainer = document.getElementById('intFirebaseTokenContainer');
        tokenContainer.innerHTML = '';
        tokenContainer.appendChild(buildValueInput('int_fb_token', data.device_token || '', nodeId));

        const titleContainer = document.getElementById('intFirebaseTitleContainer');
        titleContainer.innerHTML = '';
        titleContainer.appendChild(buildValueInput('int_fb_title', data.title || '', nodeId));

        const bodyContainer = document.getElementById('intFirebaseBodyContainer');
        bodyContainer.innerHTML = '';
        bodyContainer.appendChild(buildValueInput('int_fb_body', data.body || '', nodeId));

        const typeContainer = document.getElementById('intFirebaseTypeContainer');
        typeContainer.innerHTML = '';
        typeContainer.appendChild(buildValueInput('int_fb_type', data.firebase_type || '', nodeId));

        const typeIdContainer = document.getElementById('intFirebaseTypeIdContainer');
        typeIdContainer.innerHTML = '';
        typeIdContainer.appendChild(buildValueInput('int_fb_type_id', data.type_id || '', nodeId));
    }

    if (type === 'google_drive') {
        document.getElementById('intGdriveAction').value = data.action || 'upload';
    }

    if (type === 'ai_agent') {
        const sysContainer = document.getElementById('intAiSystemPromptContainer');
        sysContainer.innerHTML = '';
        sysContainer.appendChild(buildTextareaValueInput('int_ai_system_prompt', data.system_prompt || '', nodeId));

        const msgContainer = document.getElementById('intAiUserMessageContainer');
        msgContainer.innerHTML = '';
        msgContainer.appendChild(buildTextareaValueInput('int_ai_user_message', data.user_message || '', nodeId));

        document.getElementById('intAiMaxTokens').value = data.max_tokens || '';
        document.getElementById('intAiTemperature').value = data.temperature !== undefined ? data.temperature : '';
    }
}

function addIntKVRow(container, key, value, prefix, nodeId) {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 int-kv-row align-items-center';

    const colKey = document.createElement('div');
    colKey.className = 'col-4';
    const keyInput = document.createElement('input');
    keyInput.type = 'text';
    keyInput.className = 'form-control form-control-solid form-control-sm int-kv-key';
    keyInput.placeholder = 'Key';
    keyInput.value = key || '';
    colKey.appendChild(keyInput);

    const colVal = document.createElement('div');
    colVal.className = 'col-7';
    colVal.appendChild(buildValueInput(prefix + '_value', value || '', nodeId));

    const colDel = document.createElement('div');
    colDel.className = 'col-1';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.addEventListener('click', () => row.remove());
    colDel.appendChild(delBtn);

    row.appendChild(colKey);
    row.appendChild(colVal);
    row.appendChild(colDel);
    container.appendChild(row);
}

function collectIntKVRows(containerId, prefix) {
    const obj = {};
    document.querySelectorAll('#' + containerId + ' .int-kv-row').forEach(row => {
        const key = row.querySelector('.int-kv-key').value.trim();
        const val = row.querySelector('input[name="' + prefix + '_value"]').value;
        if (key) obj[key] = val;
    });
    return obj;
}

function collectIntegrationData() {
    const data = {};
    data.type = document.getElementById('integrationType').value;

    const intId = document.getElementById('integrationSelect').value;
    if (intId) {
        data.integration_id = intId;
        // Store name for summary display
        const opt = document.getElementById('integrationSelect').selectedOptions[0];
        if (opt) data.integration_name = opt.textContent;
    }

    data.result_key = document.getElementById('intResultKey').value || undefined;

    if (data.type === 'webhook') {
        const urlInput = document.querySelector('#intWebhookUrlContainer input[name="int_webhook_url"]');
        data.url = urlInput ? urlInput.value : '';
        data.method = document.getElementById('intWebhookMethod').value;

        const headers = collectIntKVRows('intHeaderRows', 'int_header');
        if (Object.keys(headers).length) data.headers = headers;

        const body = collectIntKVRows('intBodyRows', 'int_body');
        if (Object.keys(body).length) data.body = body;
    }

    if (data.type === 'whatsapp') {
        const toInput = document.querySelector('#intWhatsappToContainer input[name="int_wa_to"]');
        data.to = toInput ? toInput.value : '';
        const msgInput = document.querySelector('#intWhatsappMsgContainer input[name="int_wa_message"]');
        data.message = msgInput ? msgInput.value : '';
    }

    if (data.type === 'firebase') {
        const tokenInput = document.querySelector('#intFirebaseTokenContainer input[name="int_fb_token"]');
        data.device_token = tokenInput ? tokenInput.value : '';
        const titleInput = document.querySelector('#intFirebaseTitleContainer input[name="int_fb_title"]');
        data.title = titleInput ? titleInput.value : '';
        const bodyInput = document.querySelector('#intFirebaseBodyContainer input[name="int_fb_body"]');
        data.body = bodyInput ? bodyInput.value : '';
        const typeInput = document.querySelector('#intFirebaseTypeContainer input[name="int_fb_type"]');
        data.firebase_type = typeInput ? typeInput.value : '';  // use firebase_type key to avoid overwriting data.type
        const typeIdInput = document.querySelector('#intFirebaseTypeIdContainer input[name="int_fb_type_id"]');
        data.type_id = typeIdInput ? typeIdInput.value : '';
    }

    if (data.type === 'google_drive') {
        data.action = document.getElementById('intGdriveAction').value;
    }

    if (data.type === 'ai_agent') {
        const sysTextarea = document.querySelector('#intAiSystemPromptContainer textarea[name="int_ai_system_prompt"]');
        data.system_prompt = sysTextarea ? sysTextarea.value : '';
        const msgTextarea = document.querySelector('#intAiUserMessageContainer textarea[name="int_ai_user_message"]');
        data.user_message = msgTextarea ? msgTextarea.value : '';
        const maxTokens = document.getElementById('intAiMaxTokens').value;
        if (maxTokens) data.max_tokens = parseInt(maxTokens, 10);
        const temp = document.getElementById('intAiTemperature').value;
        if (temp !== '') data.temperature = parseFloat(temp);
    }

    if (!data.result_key) delete data.result_key;
    return data;
}

function showGenericPanel(node) {
    document.getElementById('genericDataPanel').classList.remove('d-none');
    document.getElementById('editNodeData').value = JSON.stringify(node.data || {}, null, 2);
}

// ==========================================
// SAVE NODE
// ==========================================
document.getElementById('saveNodeBtn').addEventListener('click', () => {
    const id = Number(document.getElementById('editNodeId').value);
    const node = nodes.find(n => n.id === id);
    if (!node) return;

    node.name = document.getElementById('editNodeName').value;

    if (node.type === 'trigger') {
        node.data = collectTriggerData();
    } else if (node.type === 'action') {
        node.data = collectActionData();
    } else if (node.type === 'condition') {
        node.data = collectConditionData();
    } else if (node.type === 'operation') {
        node.data = collectOperationData();
    } else if (node.type === 'integration' || node.type === 'ai_agent') {
        node.data = collectIntegrationData();
    } else {
        try {
            node.data = JSON.parse(document.getElementById('editNodeData').value);
        } catch (e) {
            alert('Invalid JSON');
            return;
        }
    }

    bootstrap.Modal.getInstance(document.getElementById('nodeModal')).hide();
    render();
});

function collectActionData() {
    const data = {};
    data.action = document.getElementById('actionType').value;
    data.model = document.getElementById('actionModel').value;
    data.result_key = document.getElementById('actionResultKey').value || undefined;

    const findByRows = document.querySelectorAll('#findByRows .findby-row');
    if (findByRows.length) {
        data.find_by = {};
        findByRows.forEach(row => {
            const field = row.querySelector('.findby-field').value;
            const value = row.querySelector('input[name="findby_value"]').value;
            if (field) data.find_by[field] = value;
        });
    }

    const mappingRows = document.querySelectorAll('#fieldMappingRows .field-mapping-row');
    if (mappingRows.length) {
        data.attributes = {};
        mappingRows.forEach(row => {
            const field = row.querySelector('.field-select').value;
            const value = row.querySelector('input[name="field_value"]').value;
            if (field) data.attributes[field] = value;
        });
    }

    if (['increment', 'decrement'].includes(data.action)) {
        data.field = document.getElementById('incDecField').value;
        data.value = document.getElementById('incDecValue').value;
    }

    // Query-specific data
    if (['get', 'first', 'find'].includes(data.action)) {
        if (data.action === 'find') {
            const findIdInput = document.querySelector('#findIdContainer input[name="find_id"]');
            data.find_id = findIdInput ? findIdInput.value : '';
        }

        if (['get', 'first'].includes(data.action)) {
            const whereRows = document.querySelectorAll('#whereRows .where-row');
            if (whereRows.length) {
                data.where = [];
                whereRows.forEach(row => {
                    const field = row.querySelector('.where-field').value;
                    const operator = row.querySelector('.where-operator').value;
                    const value = row.querySelector('input[name="where_value"]').value;
                    if (field) data.where.push({ field, operator, value });
                });
            }
        }

        const checkedCols = document.querySelectorAll('#selectColumnsContainer input[type="checkbox"]:checked');
        if (checkedCols.length) {
            data.select_columns = Array.from(checkedCols).map(cb => cb.value);
        }

        if (['get', 'first'].includes(data.action)) {
            const orderByField = document.getElementById('orderByField').value;
            if (orderByField) {
                data.order_by = orderByField;
                data.order_direction = document.getElementById('orderByDirection').value || 'asc';
            }
        }

        if (data.action === 'get') {
            const limit = document.getElementById('queryLimit').value;
            if (limit) data.limit = parseInt(limit, 10);
        }
    }

    if (!data.result_key) delete data.result_key;
    if (!data.model) delete data.model;

    // Media (Spatie)
    if (document.getElementById('mediaEnabled').checked && ['create', 'update', 'get', 'first', 'find'].includes(data.action)) {
        data.media = { enabled: true };
        if (['create', 'update'].includes(data.action)) {
            data.media.collection = document.getElementById('mediaCollection').value || 'default';
            data.media.action = document.getElementById('mediaAction').value || 'add';
            const srcInput = document.querySelector('#mediaSourceContainer input[name="media_source"]');
            data.media.source = srcInput ? srcInput.value : '';
            const fnInput = document.querySelector('#mediaFileNameContainer input[name="media_file_name"]');
            if (fnInput && fnInput.value) data.media.file_name = fnInput.value;
        } else {
            data.media.collections = document.getElementById('mediaCollections').value || '';
        }
    }

    return data;
}

function collectConditionData() {
    const fieldInput = document.querySelector('#condFieldContainer input[name="cond_field"]');
    const valueInput = document.querySelector('#condValueContainer input[name="cond_value"]');
    return {
        field: fieldInput ? fieldInput.value : '',
        operator: document.getElementById('condOperator').value,
        value: valueInput ? valueInput.value : '',
    };
}

document.getElementById('addFieldMappingRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    const modelClass = document.getElementById('actionModel').value;
    addFieldMappingRow(document.getElementById('fieldMappingRows'), '', '', modelClass, nodeId);
});

document.getElementById('addFindByRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    const modelClass = document.getElementById('actionModel').value;
    addFindByRow(document.getElementById('findByRows'), '', '', modelClass, nodeId);
});

document.getElementById('mediaEnabled').addEventListener('change', function () {
    document.getElementById('mediaFields').classList.toggle('d-none', !this.checked);
    if (this.checked) {
        const nodeId = Number(document.getElementById('editNodeId').value);
        const action = document.getElementById('actionType').value;
        if (['create', 'update'].includes(action)) {
            const srcContainer = document.getElementById('mediaSourceContainer');
            if (!srcContainer.hasChildNodes()) {
                srcContainer.appendChild(buildValueInput('media_source', '', nodeId));
            }
            const fnContainer = document.getElementById('mediaFileNameContainer');
            if (!fnContainer.hasChildNodes()) {
                fnContainer.appendChild(buildValueInput('media_file_name', '', nodeId));
            }
        }
    }
});

document.getElementById('addWhereRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    const modelClass = document.getElementById('actionModel').value;
    addWhereRow(document.getElementById('whereRows'), '', '=', '', modelClass, nodeId);
});

document.getElementById('addIntHeaderRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    addIntKVRow(document.getElementById('intHeaderRows'), '', '', 'int_header', nodeId);
});

document.getElementById('addIntBodyRow').addEventListener('click', () => {
    const nodeId = Number(document.getElementById('editNodeId').value);
    addIntKVRow(document.getElementById('intBodyRows'), '', '', 'int_body', nodeId);
});

// ==========================================
// DELETE NODE
// ==========================================
function deleteNode(id) {
    const node = nodes.find(n => n.id === id);
    if (node && node.type === 'trigger') {
        alert('The trigger node cannot be deleted. Each flow must have exactly one trigger.');
        return;
    }
    if (!confirm('Delete this node?')) return;
    nodes = nodes.filter(n => n.id !== id);
    connections = connections.filter(c => c.from_node_id !== id && c.to_node_id !== id);
    render();
}

// ==========================================
// SAVE CONNECTION
// ==========================================
document.getElementById('saveConnBtn').addEventListener('click', () => {
    const from = Number(document.getElementById('connFrom').value);
    const to = Number(document.getElementById('connTo').value);
    const cond = document.getElementById('connCondition').value || null;
    connections.push({ from_node_id: from, to_node_id: to, condition_value: cond });
    bootstrap.Modal.getInstance(document.getElementById('connModal')).hide();
    renderConnections();
});

// ==========================================
// SAVE ALL
// ==========================================
document.getElementById('btnSave').addEventListener('click', save);
async function save() {
    document.getElementById('btnSave').disabled = true;
    document.getElementById('btnSave').innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    try {
        const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ nodes, connections }),
        });
        const data = await res.json();
        if (res.ok) {
            if (data.node_id_map) {
                for (const [oldId, newId] of Object.entries(data.node_id_map)) {
                    const oid = Number(oldId);
                    const nid = Number(newId);
                    const node = nodes.find(n => n.id === oid);
                    if (node) node.id = nid;
                    connections.forEach(c => {
                        if (c.from_node_id === oid) c.from_node_id = nid;
                        if (c.to_node_id === oid) c.to_node_id = nid;
                    });
                }
            }
            showToast('Saved successfully!', 'success');
        } else {
            showToast(data.message || 'Save failed', 'danger');
        }
    } catch (err) {
        showToast('Network error: ' + err.message, 'danger');
    }
    document.getElementById('btnSave').disabled = false;
    document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Save';
}

// ==========================================
// AUTO LAYOUT
// ==========================================
document.getElementById('btnAutoLayout').addEventListener('click', () => {
    const byType = { trigger: [], condition: [], action: [], operation: [], integration: [] };
    nodes.forEach(n => { (byType[n.type] || (byType[n.type] = [])).push(n); });
    let y = 30;
    for (const type of ['trigger', 'condition', 'action', 'operation', 'integration']) {
        const group = byType[type] || [];
        group.forEach((n, i) => { n.position_x = 50 + i * 240; n.position_y = y; });
        if (group.length) y += 140;
    }
    render();
});

// ==========================================
// HELPERS
// ==========================================
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function showToast(msg, type) {
    const t = document.getElementById('saveToast');
    t.textContent = msg;
    t.style.background = type === 'success' ? '#059669' : '#dc2626';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

// ==========================================
// INIT
// ==========================================

// Auto-create trigger node if none exists
if (!nodes.find(n => n.type === 'trigger')) {
    const triggerNode = {
        id: tempIdCounter--,
        type: 'trigger',
        name: 'Trigger',
        data: {},
        sort_order: 0,
        position_x: 100,
        position_y: 50,
    };

    // Pre-populate from existing trigger data if available
    if (EXISTING_TRIGGER) {
        const t = EXISTING_TRIGGER;
        triggerNode.data.trigger_type = t.type === 'model' ? 'model_event' : t.type;
        if (t.model_class) triggerNode.data.model_class = t.model_class;
        if (t.event) triggerNode.data.model_event = t.event;
        if (t.conditions && t.conditions.cron_expression) triggerNode.data.cron_expression = t.conditions.cron_expression;
    }

    nodes.unshift(triggerNode);
} else if (EXISTING_TRIGGER) {
    // Sync existing trigger data into the trigger node
    const triggerNode = nodes.find(n => n.type === 'trigger');
    if (triggerNode && (!triggerNode.data || !triggerNode.data.trigger_type)) {
        const t = EXISTING_TRIGGER;
        triggerNode.data = triggerNode.data || {};
        triggerNode.data.trigger_type = t.type === 'model' ? 'model_event' : t.type;
        if (t.model_class) triggerNode.data.model_class = t.model_class;
        if (t.event) triggerNode.data.model_event = t.event;
        if (t.conditions && t.conditions.cron_expression) triggerNode.data.cron_expression = t.conditions.cron_expression;
    }
}

loadModels();
render();
</script>
@endpush
