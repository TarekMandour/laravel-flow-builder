<?php

namespace Arabiacode\LaravelFlowBuilder\Http\Controllers;

use Arabiacode\LaravelFlowBuilder\Models\Flow;
use Arabiacode\LaravelFlowBuilder\Models\FlowExecution;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;
use Arabiacode\LaravelFlowBuilder\Models\FlowConnection;
use Arabiacode\LaravelFlowBuilder\Models\FlowTrigger;
use Arabiacode\LaravelFlowBuilder\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class FlowController extends Controller
{
    public function dashboard()
    {
        return view('flow-builder::dashboard', [
            'totalFlows' => Flow::count(),
            'activeFlows' => Flow::where('is_active', true)->count(),
            'totalExecutions' => FlowExecution::count(),
            'failedExecutions' => FlowExecution::where('status', 'failed')->count(),
            'recentExecutions' => FlowExecution::with('flow')->latest('started_at')->limit(10)->get(),
            'activeFlowList' => Flow::where('is_active', true)->withCount(['nodes', 'triggers'])->limit(10)->get(),
        ]);
    }

    public function index()
    {
        $flows = Flow::with('triggers')
            ->withCount('nodes')
            ->latest()
            ->paginate(15);

        return view('flow-builder::flows.index', compact('flows'));
    }

    public function create()
    {
        return view('flow-builder::flows.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $flow = Flow::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('flow-builder.flows.builder', $flow)
            ->with('success', 'Flow created! Configure the trigger node and add action nodes.');
    }

    public function edit(Flow $flow)
    {
        $flow->load('triggers');
        return view('flow-builder::flows.edit', compact('flow'));
    }

    public function update(Request $request, Flow $flow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $flow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('flow-builder.flows.index')
            ->with('success', 'Flow updated.');
    }

    public function toggle(Flow $flow)
    {
        $flow->update(['is_active' => !$flow->is_active]);
        return back()->with('success', $flow->is_active ? 'Flow activated.' : 'Flow deactivated.');
    }

    public function destroy(Flow $flow)
    {
        $flow->delete();
        return redirect()->route('flow-builder.flows.index')
            ->with('success', 'Flow deleted.');
    }

    public function duplicate(Flow $flow)
    {
        DB::transaction(function () use ($flow) {
            // Clone the flow
            $newFlow = Flow::create([
                'name'        => 'Copy of ' . $flow->name,
                'description' => $flow->description,
                'is_active'   => false,
                'created_by'  => $flow->created_by,
            ]);

            // Duplicate triggers
            foreach ($flow->triggers as $trigger) {
                $newFlow->triggers()->create([
                    'type'        => $trigger->type,
                    'model_class' => $trigger->model_class,
                    'event'       => $trigger->event,
                    'conditions'  => $trigger->conditions,
                ]);
            }

            // Duplicate nodes and build old→new ID map
            $nodeIdMap = [];
            foreach ($flow->nodes as $node) {
                $newNode = $newFlow->nodes()->create([
                    'type'       => $node->type,
                    'name'       => $node->name,
                    'data'       => $node->data,
                    'sort_order' => $node->sort_order,
                    'position_x' => $node->position_x,
                    'position_y' => $node->position_y,
                ]);
                $nodeIdMap[$node->id] = $newNode->id;
            }

            // Duplicate connections using the node ID map
            foreach ($flow->connections as $connection) {
                $newFlow->connections()->create([
                    'from_node_id'    => $nodeIdMap[$connection->from_node_id] ?? null,
                    'to_node_id'      => $nodeIdMap[$connection->to_node_id] ?? null,
                    'condition_value' => $connection->condition_value,
                ]);
            }

            // Duplicate flow variables
            foreach ($flow->variables as $variable) {
                $newFlow->variables()->create([
                    'key'   => $variable->key,
                    'value' => $variable->value,
                ]);
            }
        });

        return redirect()->route('flow-builder.flows.index')
            ->with('success', 'Flow duplicated successfully.');
    }

    public function builder(Flow $flow)
    {
        $flow->load(['nodes', 'nodes.outgoingConnections', 'triggers']);

        $nodes = $flow->nodes->map(fn ($n) => [
            'id' => $n->id,
            'type' => $n->type,
            'name' => $n->name,
            'data' => $n->data ?? (object) [],
            'sort_order' => $n->sort_order,
            'position_x' => $n->position_x ?? 100,
            'position_y' => $n->position_y ?? 100,
        ])->values();

        $connections = FlowConnection::whereIn('from_node_id', $flow->nodes->pluck('id'))
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'from_node_id' => $c->from_node_id,
                'to_node_id' => $c->to_node_id,
                'condition_value' => $c->condition_value,
            ])->values();

        // Pass existing trigger data (only first — one trigger per flow)
        $trigger = $flow->triggers->first();
        $triggerData = $trigger ? [
            'id' => $trigger->id,
            'type' => $trigger->type,
            'model_class' => $trigger->model_class,
            'event' => $trigger->event,
            'conditions' => $trigger->conditions,
        ] : null;

        return view('flow-builder::flows.builder', compact('flow', 'nodes', 'connections', 'triggerData'));
    }

    public function saveBuilder(Request $request, Flow $flow)
    {
        $request->validate([
            'nodes' => 'required|array',
            'nodes.*.type' => 'required|string',
            'nodes.*.name' => 'required|string|max:255',
            'connections' => 'nullable|array',
        ]);

        $nodeIdMap = [];

        DB::transaction(function () use ($request, $flow, &$nodeIdMap) {
            $incomingNodeIds = collect($request->input('nodes'))
                ->pluck('id')
                ->filter(fn ($id) => $id > 0)
                ->toArray();

            // Delete removed nodes
            $flow->nodes()->whereNotIn('id', $incomingNodeIds)->delete();

            // Upsert nodes
            $triggerNodeData = null;
            foreach ($request->input('nodes') as $i => $nodeData) {
                $attrs = [
                    'type' => $nodeData['type'],
                    'name' => $nodeData['name'],
                    'data' => $nodeData['data'] ?? [],
                    'sort_order' => $nodeData['sort_order'] ?? $i,
                    'position_x' => $nodeData['position_x'] ?? 100,
                    'position_y' => $nodeData['position_y'] ?? 100,
                ];

                if ($nodeData['id'] > 0) {
                    $node = $flow->nodes()->find($nodeData['id']);
                    if ($node) {
                        $node->update($attrs);
                    } else {
                        $node = $flow->nodes()->create($attrs);
                        $nodeIdMap[$nodeData['id']] = $node->id;
                    }
                } else {
                    $node = $flow->nodes()->create($attrs);
                    $nodeIdMap[$nodeData['id']] = $node->id;
                }

                // Capture trigger node data for saving as FlowTrigger
                if ($nodeData['type'] === 'trigger') {
                    $triggerNodeData = $nodeData['data'] ?? [];
                }
            }

            // Upsert trigger (one per flow) from trigger node data
            if ($triggerNodeData && !empty($triggerNodeData['trigger_type'])) {
                $triggerType = $triggerNodeData['trigger_type'];
                // Map 'model_event' to 'model' for ModelEventListener compatibility
                if ($triggerType === 'model_event') {
                    $triggerType = 'model';
                }

                $triggerAttrs = [
                    'type' => $triggerType,
                    'model_class' => null,
                    'event' => null,
                    'conditions' => null,
                ];

                if ($triggerType === 'model') {
                    $triggerAttrs['model_class'] = $triggerNodeData['model_class'] ?? null;
                    $triggerAttrs['event'] = $triggerNodeData['model_event'] ?? null;
                } elseif ($triggerType === 'schedule') {
                    $triggerAttrs['conditions'] = ['cron_expression' => $triggerNodeData['cron_expression'] ?? ''];
                } elseif ($triggerType === 'webhook') {
                    // Keep existing token or generate new one
                    $existing = $flow->triggers()->first();
                    $token = ($existing && $existing->conditions['token'] ?? null) ?: Str::random(32);
                    $triggerAttrs['conditions'] = ['token' => $token];
                }

                // Delete all existing triggers (enforce one per flow) and create new one
                $flow->triggers()->delete();
                $flow->triggers()->create($triggerAttrs);
            } else {
                // No trigger configured — remove any existing trigger
                $flow->triggers()->delete();
            }

            // Delete old connections for this flow's nodes
            $flowNodeIds = $flow->nodes()->pluck('id')->toArray();
            FlowConnection::whereIn('from_node_id', $flowNodeIds)->delete();

            // Create connections
            $connIds = [];
            foreach ($request->input('connections', []) as $conn) {
                $fromId = $conn['from_node_id'];
                $toId = $conn['to_node_id'];

                // Remap temp IDs
                if (isset($nodeIdMap[$fromId])) $fromId = $nodeIdMap[$fromId];
                if (isset($nodeIdMap[$toId])) $toId = $nodeIdMap[$toId];

                $c = FlowConnection::create([
                    'flow_id' => $flow->id,
                    'from_node_id' => $fromId,
                    'to_node_id' => $toId,
                    'condition_value' => $conn['condition_value'] ?? null,
                ]);
                $connIds[] = $c->id;
            }
        });

        // Clear trigger cache so ModelEventListener picks up changes
        cache()->forget('flow_builder.model_triggers');

        return response()->json([
            'message' => 'Flow saved.',
            'node_id_map' => $nodeIdMap,
        ]);
    }

    public function destroyTrigger(FlowTrigger $trigger)
    {
        $trigger->delete();
        return back()->with('success', 'Trigger removed.');
    }

    public function executionIndex(Request $request)
    {
        $query = FlowExecution::with('flow')->latest('started_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('flow-builder::executions.index', [
            'executions' => $query->paginate(20),
        ]);
    }

    public function executionShow(FlowExecution $execution)
    {
        $execution->load(['flow', 'logs.node']);
        return view('flow-builder::executions.show', compact('execution'));
    }

    /**
     * Return all Eloquent models discovered in app/Models.
     */
    public function getModels()
    {
        $models = [];
        $modelsPath = app_path('Models');

        if (File::isDirectory($modelsPath)) {
            foreach (File::allFiles($modelsPath) as $file) {
                $className = 'App\\Models\\' . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $file->getRelativePathname()
                );

                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    $models[] = [
                        'class' => $className,
                        'name' => class_basename($className),
                    ];
                }
            }
        }

        return response()->json($models);
    }

    /**
     * Return fillable fields / DB columns for a given model class.
     */
    public function getModelFields(Request $request)
    {
        $modelClass = $request->input('model');

        if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return response()->json(['error' => 'Invalid model class'], 422);
        }

        $instance = new $modelClass;
        $table = $instance->getTable();
        $columns = Schema::getColumnListing($table);

        // Exclude common auto-managed columns
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $fillableColumns = array_values(array_diff($columns, $exclude));

        // Also get fillable from model if defined
        $fillable = $instance->getFillable();

        return response()->json([
            'model' => $modelClass,
            'table' => $table,
            'columns' => $fillableColumns,
            'fillable' => $fillable,
        ]);
    }

    public function getIntegrations()
    {
        $integrations = Integration::where('is_active', true)
            ->select('id', 'name', 'type')
            ->orderBy('name')
            ->get();

        return response()->json($integrations);
    }
}
