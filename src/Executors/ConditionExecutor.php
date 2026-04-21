<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;

class ConditionExecutor implements NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed
    {
        $data = $node->data ?? [];

        $field = $data['field'] ?? null;
        $operator = $data['operator'] ?? 'equals';
        $value = isset($data['value']) ? $state->resolveValue($data['value']) : null;

        if ($field === null) {
            return false;
        }

        // Resolve the field — supports both dot-path and {{variable}} syntax
        $resolvedField = $state->resolveValue($field);
        // If resolveValue returned the raw string (no mustache), treat as dot-path
        $fieldValue = ($resolvedField === $field) ? $state->get($field) : $resolvedField;

        return $this->evaluate($fieldValue, $operator, $value);
    }

    protected function evaluate(mixed $fieldValue, string $operator, mixed $value): bool
    {
        return match ($operator) {
            'equals' => $fieldValue == $value,
            'not_equals' => $fieldValue != $value,
            'greater_than' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue > $value,
            'less_than' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue < $value,
            'greater_or_equal' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue >= $value,
            'less_or_equal' => is_numeric($fieldValue) && is_numeric($value) && $fieldValue <= $value,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, (string) $value),
            'not_contains' => is_string($fieldValue) && !str_contains($fieldValue, (string) $value),
            'starts_with' => is_string($fieldValue) && str_starts_with($fieldValue, (string) $value),
            'ends_with' => is_string($fieldValue) && str_ends_with($fieldValue, (string) $value),
            'in' => in_array($fieldValue, array_map('trim', explode(',', (string) $value))),
            'exists' => !is_null($fieldValue) && $fieldValue !== '',
            'not_exists' => is_null($fieldValue) || $fieldValue === '',
            default => false,
        };
    }
}
