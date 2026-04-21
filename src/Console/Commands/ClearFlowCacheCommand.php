<?php

namespace Arabiacode\LaravelFlowBuilder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearFlowCacheCommand extends Command
{
    protected $signature = 'flow-builder:clear-cache';

    protected $description = 'Clear the Flow Builder trigger cache.';

    public function handle(): int
    {
        Cache::forget('flow_builder.model_triggers');

        $this->info('Flow Builder cache cleared successfully.');

        return self::SUCCESS;
    }
}
