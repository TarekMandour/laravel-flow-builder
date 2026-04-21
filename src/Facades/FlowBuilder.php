<?php

namespace Arabiacode\LaravelFlowBuilder\Facades;

use Arabiacode\LaravelFlowBuilder\Engine\FlowEngine;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Arabiacode\LaravelFlowBuilder\Models\FlowExecution execute(\Arabiacode\LaravelFlowBuilder\Models\Flow $flow, array $payload = [])
 *
 * @see \Arabiacode\LaravelFlowBuilder\Engine\FlowEngine
 */
class FlowBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlowEngine::class;
    }
}
