<?php

namespace Arabiacode\LaravelFlowBuilder\Traits;

use Arabiacode\LaravelFlowBuilder\Engine\FlowEngine;
use Arabiacode\LaravelFlowBuilder\Jobs\ExecuteFlowJob;
use Arabiacode\LaravelFlowBuilder\Models\FlowTrigger;

trait HasFlowTriggers
{
    /**
     * Manually trigger all matching flows for this model and event.
     */
    public function triggerFlows(string $event = 'manual'): void
    {
        $triggers = FlowTrigger::whereHas('flow', fn ($q) => $q->where('is_active', true))
            ->where('type', 'model')
            ->where('model_class', static::class)
            ->where('event', $event)
            ->get();

        foreach ($triggers as $trigger) {
            if (config('flow-builder.queue.enabled', true)) {
                ExecuteFlowJob::dispatch($trigger->flow_id, $this->toArray());
            } else {
                app(FlowEngine::class)->execute($trigger->flow, $this->toArray());
            }
        }
    }

    /**
     * Get all flow triggers associated with this model class.
     */
    public function getFlowTriggers()
    {
        return FlowTrigger::where('model_class', static::class)->get();
    }
}
