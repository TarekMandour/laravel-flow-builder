<?php

namespace Arabiacode\LaravelFlowBuilder\Console\Commands;

use Arabiacode\LaravelFlowBuilder\Jobs\ExecuteFlowJob;
use Arabiacode\LaravelFlowBuilder\Models\FlowTrigger;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunScheduledFlowsCommand extends Command
{
    protected $signature = 'flow-builder:run-scheduled';

    protected $description = 'Run all scheduled flows that are currently due.';

    public function handle(): int
    {
        $triggers = FlowTrigger::whereHas('flow', fn ($q) => $q->where('is_active', true))
            ->where('type', 'schedule')
            ->get();

        $executed = 0;

        foreach ($triggers as $trigger) {
            $cron = $trigger->conditions['cron'] ?? null;

            if (!$cron) {
                continue;
            }

            try {
                $expression = new CronExpression($cron);

                if ($expression->isDue()) {
                    if (config('flow-builder.queue.enabled', true)) {
                        ExecuteFlowJob::dispatch($trigger->flow_id, [
                            '_trigger' => 'schedule',
                            '_cron' => $cron,
                        ]);
                    } else {
                        app(\Arabiacode\LaravelFlowBuilder\Engine\FlowEngine::class)
                            ->execute($trigger->flow, [
                                '_trigger' => 'schedule',
                                '_cron' => $cron,
                            ]);
                    }

                    $executed++;
                    $this->info("Dispatched flow: {$trigger->flow->name} (ID: {$trigger->flow_id})");
                }
            } catch (\Exception $e) {
                $this->error("Error checking trigger ID {$trigger->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$executed} flow(s) dispatched.");

        return self::SUCCESS;
    }
}
