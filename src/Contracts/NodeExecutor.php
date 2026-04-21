<?php

namespace Arabiacode\LaravelFlowBuilder\Contracts;

use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;

interface NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed;
}
