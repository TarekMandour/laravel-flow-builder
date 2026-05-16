<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;

class OperationExecutor implements NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed
    {
        $data = $node->data ?? [];
        $type = $data['type'] ?? null;

        return match ($type) {
            'sum' => $this->sum($data, $state),
            'subtract' => $this->subtract($data, $state),
            'multiply' => $this->multiply($data, $state),
            'divide' => $this->divide($data, $state),
            'format_text' => $this->formatText($data, $state),
            'loop' => $this->loop($data, $state),
            'delay' => $this->delay($data, $state),
            default => throw new \InvalidArgumentException("Unknown operation type: {$type}"),
        };
    }

    protected function sum(array $data, FlowState $state): float
    {
        $values = $this->resolveNumericValues($data, $state);
        $result = array_sum($values);

        $this->storeResult($data, $state, $result);

        return $result;
    }

    protected function subtract(array $data, FlowState $state): float
    {
        $values = $this->resolveNumericValues($data, $state);

        if (empty($values)) {
            $this->storeResult($data, $state, 0);
            return 0;
        }

        $result = array_shift($values);
        foreach ($values as $value) {
            $result -= $value;
        }

        $this->storeResult($data, $state, $result);

        return $result;
    }

    protected function multiply(array $data, FlowState $state): float
    {
        $values = $this->resolveNumericValues($data, $state);

        if (empty($values)) {
            $this->storeResult($data, $state, 0);
            return 0;
        }

        $result = array_shift($values);
        foreach ($values as $value) {
            $result *= $value;
        }

        $this->storeResult($data, $state, $result);

        return $result;
    }

    protected function divide(array $data, FlowState $state): float
    {
        $values = $this->resolveNumericValues($data, $state);

        if (empty($values)) {
            $this->storeResult($data, $state, 0);
            return 0;
        }

        $result = array_shift($values);
        foreach ($values as $value) {
            if ($value == 0) {
                $this->storeResult($data, $state, 0);
                return 0;
            }
            $result /= $value;
        }

        $this->storeResult($data, $state, $result);

        return $result;
    }

    protected function formatText(array $data, FlowState $state): string
    {
        $template = $data['template'] ?? '';
        $result = $state->resolveValue($template);

        $this->storeResult($data, $state, $result);

        return (string) $result;
    }

    protected function loop(array $data, FlowState $state): array
    {
        $field = $data['field'] ?? null;

        if (!$field) {
            return [];
        }

        $items = $state->get($field, []);

        if (!is_array($items)) {
            return [];
        }

        // The FlowEngine handles the actual iteration over downstream nodes.
        // This executor just returns the items to iterate over.
        return $items;
    }

    protected function delay(array $data, FlowState $state): int
    {
        $cap = (int) config('flow-builder.max_delay_seconds', 300);
        $mode = $data['delay_mode'] ?? 'static';

        if ($mode === 'random') {
            $min = max(0, (int) ($data['min_seconds'] ?? 0));
            $max = max($min, (int) ($data['max_seconds'] ?? 0));
            $seconds = $min === $max ? $min : rand($min, $max);
        } else {
            $seconds = max(0, (int) ($data['seconds'] ?? 0));
        }

        $seconds = min($seconds, $cap);

        if ($seconds > 0) {
            sleep($seconds);
        }

        return $seconds;
    }

    protected function resolveNumericValues(array $data, FlowState $state): array
    {
        // Support explicit values array
        if (isset($data['values']) && is_array($data['values'])) {
            return array_map(function ($v) use ($state) {
                return (float) $state->resolveValue($v);
            }, $data['values']);
        }

        // Support a field path that resolves to an array
        $field = $data['field'] ?? null;
        if (!$field) {
            return [];
        }

        $value = $state->get($field, []);

        if (is_array($value)) {
            // Array of objects with a sub-field to sum
            if (isset($data['sum_field'])) {
                return array_map(function ($item) use ($data) {
                    return (float) data_get($item, $data['sum_field'], 0);
                }, $value);
            }

            // Array of numeric values
            return array_map('floatval', $value);
        }

        return [(float) $value];
    }

    protected function storeResult(array $data, FlowState $state, mixed $result): void
    {
        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $result);
        }
    }
}
