<?php

namespace Arabiacode\LaravelFlowBuilder\Listeners;

use Arabiacode\LaravelFlowBuilder\Jobs\ExecuteFlowJob;
use Arabiacode\LaravelFlowBuilder\Models\FlowTrigger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class ModelEventListener
{
    public function register(): void
    {
        $triggers = Cache::remember('flow_builder.model_triggers', 300, function () {
            return FlowTrigger::with('flow')
                ->whereHas('flow', fn ($q) => $q->where('is_active', true))
                ->where('type', 'model')
                ->get()
                ->toArray();
        });

        foreach ($triggers as $trigger) {
            $modelClass = $trigger['model_class'] ?? null;
            $event = $trigger['event'] ?? null;

            if (!$modelClass || !$event || !class_exists($modelClass)) {
                continue;
            }

            $flowId = $trigger['flow_id'];
            $triggerConditions = $trigger['conditions'] ?? [];

            Event::listen("eloquent.{$event}: {$modelClass}", function ($model) use ($flowId, $triggerConditions) {
                if (!$this->matchesConditions($model, $triggerConditions)) {
                    return;
                }

                if (config('flow-builder.queue.enabled', true)) {
                    ExecuteFlowJob::dispatch($flowId, $model->toArray());
                } else {
                    app(\Arabiacode\LaravelFlowBuilder\Engine\FlowEngine::class)
                        ->execute(\Arabiacode\LaravelFlowBuilder\Models\Flow::findOrFail($flowId), $model->toArray());
                }
            });
        }
    }

    protected function matchesConditions($model, ?array $conditions): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $fieldValue = data_get($model, $field);

            $matches = match ($operator) {
                'equals' => $fieldValue == $value,
                'not_equals' => $fieldValue != $value,
                'greater_than' => $fieldValue > $value,
                'less_than' => $fieldValue < $value,
                'contains' => is_string($fieldValue) && str_contains($fieldValue, (string) $value),
                'exists' => !is_null($fieldValue),
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }
}
