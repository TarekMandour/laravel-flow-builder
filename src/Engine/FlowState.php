<?php

namespace Arabiacode\LaravelFlowBuilder\Engine;

use Arabiacode\LaravelFlowBuilder\Models\FlowExecution;

class FlowState
{
    protected array $data;
    protected array $variables = [];
    protected FlowExecution $execution;

    public function __construct(array $payload, FlowExecution $execution)
    {
        $this->data = $payload;
        $this->execution = $execution;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->variables, $key,
            data_get($this->data, $key, $default)
        );
    }

    public function set(string $key, mixed $value): void
    {
        data_set($this->variables, $key, $value);
    }

    public function has(string $key): bool
    {
        return data_get($this->variables, $key) !== null
            || data_get($this->data, $key) !== null;
    }

    public function resolveValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // If the entire value is a single template variable, return the raw value
        if (preg_match('/^\{\{(.+?)\}\}$/', $value, $matches)) {
            return $this->get(trim($matches[1]));
        }

        // Otherwise, do string interpolation
        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) {
            $resolved = $this->get(trim($matches[1]), $matches[0]);
            return is_scalar($resolved) ? (string) $resolved : json_encode($resolved);
        }, $value);
    }

    public function resolveArray(array $data): array
    {
        $resolved = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $resolved[$key] = $this->resolveArray($value);
            } else {
                $resolved[$key] = $this->resolveValue($value);
            }
        }
        return $resolved;
    }

    public function getPayload(): array
    {
        return $this->data;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getExecution(): FlowExecution
    {
        return $this->execution;
    }

    public function all(): array
    {
        return array_merge($this->data, $this->variables);
    }
}
