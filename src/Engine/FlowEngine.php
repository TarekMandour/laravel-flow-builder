<?php

namespace Arabiacode\LaravelFlowBuilder\Engine;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Executors\ActionExecutor;
use Arabiacode\LaravelFlowBuilder\Executors\ConditionExecutor;
use Arabiacode\LaravelFlowBuilder\Executors\IntegrationExecutor;
use Arabiacode\LaravelFlowBuilder\Executors\OperationExecutor;
use Arabiacode\LaravelFlowBuilder\Executors\TriggerExecutor;
use Arabiacode\LaravelFlowBuilder\Models\Flow;
use Arabiacode\LaravelFlowBuilder\Models\FlowConnection;
use Arabiacode\LaravelFlowBuilder\Models\FlowExecution;
use Arabiacode\LaravelFlowBuilder\Models\FlowLog;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;
use Illuminate\Support\Facades\Log;

class FlowEngine
{
    protected FlowState $state;
    protected array $nodeExecutionCount = [];
    protected int $maxNodeExecutions;

    public function __construct()
    {
        $this->maxNodeExecutions = config('flow-builder.max_node_executions', 100);
    }

    public function execute(Flow $flow, array $payload = []): FlowExecution
    {
        $this->nodeExecutionCount = [];

        $execution = $flow->executions()->create([
            'status' => 'running',
            'payload' => $payload,
            'started_at' => now(),
        ]);

        $this->state = new FlowState($payload, $execution);

        // Load flow variables into state
        foreach ($flow->variables as $variable) {
            $this->state->set($variable->key, $variable->value);
        }

        $triggerNode = $flow->nodes()->where('type', 'trigger')->first();

        if (!$triggerNode) {
            $execution->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            FlowLog::create([
                'execution_id' => $execution->id,
                'node_id' => null,
                'status' => 'failed',
                'message' => 'No trigger node found in flow.',
                'created_at' => now(),
            ]);

            return $execution->fresh();
        }

        try {
            $this->executeNode($triggerNode);

            $execution->update([
                'status' => 'success',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $execution->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            FlowLog::create([
                'execution_id' => $execution->id,
                'node_id' => null,
                'status' => 'failed',
                'message' => 'Flow execution failed: ' . $e->getMessage(),
                'data' => ['exception' => get_class($e), 'trace' => $e->getTraceAsString()],
                'created_at' => now(),
            ]);

            if (config('flow-builder.logging.enabled', true)) {
                Log::channel(config('flow-builder.logging.channel'))
                    ->error('FlowBuilder execution failed', [
                        'flow_id' => $flow->id,
                        'execution_id' => $execution->id,
                        'error' => $e->getMessage(),
                    ]);
            }
        }

        return $execution->fresh();
    }

    protected function executeNode(FlowNode $node): void
    {
        // Circular reference / infinite loop protection
        $this->nodeExecutionCount[$node->id] = ($this->nodeExecutionCount[$node->id] ?? 0) + 1;

        if ($this->nodeExecutionCount[$node->id] > $this->maxNodeExecutions) {
            $this->log($node, 'skipped', 'Max execution count reached for node (possible infinite loop).');
            return;
        }

        $executor = $this->resolveExecutor($node->type);

        $this->log($node, 'running', "Executing node: {$node->name}");

        try {
            $result = $executor->execute($node, $this->state);

            // Store the result in state
            $this->state->set("_node_{$node->id}_result", $result);

            $this->log($node, 'success', "Node executed successfully.", ['result' => $result]);

            // Handle loop nodes: re-execute downstream nodes for each iteration
            if ($node->type === 'operation' && ($node->data['type'] ?? '') === 'loop') {
                $this->handleLoopTraversal($node, $result);
                return;
            }

            // Standard graph traversal
            $this->traverseFromNode($node, $result);

        } catch (\Throwable $e) {
            $this->log($node, 'failed', $e->getMessage(), ['exception' => get_class($e)]);
            throw $e;
        }
    }

    protected function traverseFromNode(FlowNode $node, mixed $result): void
    {
        $connections = FlowConnection::where('from_node_id', $node->id)->get();

        foreach ($connections as $connection) {
            // For condition nodes, only follow matching branches
            if ($node->type === 'condition' && $connection->condition_value !== null) {
                $boolResult = $result ? 'true' : 'false';
                if ($connection->condition_value !== $boolResult) {
                    continue;
                }
            }

            $nextNode = FlowNode::find($connection->to_node_id);
            if ($nextNode) {
                $this->executeNode($nextNode);
            }
        }
    }

    protected function handleLoopTraversal(FlowNode $node, mixed $items): void
    {
        if (!is_iterable($items)) {
            return;
        }

        $loopVar = $node->data['as'] ?? 'item';
        $connections = FlowConnection::where('from_node_id', $node->id)->get();

        foreach ($items as $index => $item) {
            $this->state->set($loopVar, $item);
            $this->state->set("{$loopVar}_index", $index);

            foreach ($connections as $connection) {
                $nextNode = FlowNode::find($connection->to_node_id);
                if ($nextNode) {
                    $this->executeNode($nextNode);
                }
            }
        }
    }

    protected function resolveExecutor(string $type): NodeExecutor
    {
        // Check custom executors first
        $customExecutors = config('flow-builder.executors', []);

        if (isset($customExecutors[$type])) {
            $executorClass = $customExecutors[$type];
            if (!is_subclass_of($executorClass, NodeExecutor::class)) {
                throw new \InvalidArgumentException("Custom executor [{$executorClass}] must implement NodeExecutor interface.");
            }
            return app($executorClass);
        }

        return match ($type) {
            'trigger' => app(TriggerExecutor::class),
            'condition' => app(ConditionExecutor::class),
            'action' => app(ActionExecutor::class),
            'operation' => app(OperationExecutor::class),
            'integration' => app(IntegrationExecutor::class),
            'ai_agent' => app(IntegrationExecutor::class),
            default => throw new \InvalidArgumentException("Unknown node type: {$type}"),
        };
    }

    protected function log(FlowNode $node, string $status, ?string $message = null, array $data = []): void
    {
        if (!config('flow-builder.logging.enabled', true)) {
            return;
        }

        FlowLog::create([
            'execution_id' => $this->state->getExecution()->id,
            'node_id' => $node->id,
            'status' => $status,
            'message' => $message,
            'data' => !empty($data) ? $data : null,
            'created_at' => now(),
        ]);
    }
}
