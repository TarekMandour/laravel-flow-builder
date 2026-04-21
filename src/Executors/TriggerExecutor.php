<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;

class TriggerExecutor implements NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed
    {
        // Trigger node is the entry point — it validates trigger data
        // and passes through. The payload is already in the state.
        $data = $node->data ?? [];

        // If trigger has initial variable mappings, apply them
        if (isset($data['variables']) && is_array($data['variables'])) {
            foreach ($data['variables'] as $key => $valuePath) {
                $state->set($key, $state->resolveValue($valuePath));
            }
        }

        return $state->getPayload();
    }
}
