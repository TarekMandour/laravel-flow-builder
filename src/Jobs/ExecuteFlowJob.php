<?php

namespace Arabiacode\LaravelFlowBuilder\Jobs;

use Arabiacode\LaravelFlowBuilder\Engine\FlowEngine;
use Arabiacode\LaravelFlowBuilder\Models\Flow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(
        protected int $flowId,
        protected array $payload = []
    ) {
        $this->tries = config('flow-builder.retry.max_attempts', 3);
        $this->onQueue(config('flow-builder.queue.queue', 'flows'));

        $connection = config('flow-builder.queue.connection');
        if ($connection) {
            $this->onConnection($connection);
        }
    }

    public function handle(FlowEngine $engine): void
    {
        $flow = Flow::find($this->flowId);

        if (!$flow || !$flow->is_active) {
            return;
        }

        $engine->execute($flow, $this->payload);
    }

    public function retryAfter(): int
    {
        return config('flow-builder.retry.delay', 60);
    }
}
