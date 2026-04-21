<?php

namespace Arabiacode\LaravelFlowBuilder\Http\Controllers;

use Arabiacode\LaravelFlowBuilder\Engine\FlowEngine;
use Arabiacode\LaravelFlowBuilder\Jobs\ExecuteFlowJob;
use Arabiacode\LaravelFlowBuilder\Models\Flow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FlowWebhookController extends Controller
{
    /**
     * Handle incoming webhook trigger.
     */
    public function webhook(Request $request, int $flow): JsonResponse
    {
        $flowModel = Flow::find($flow);

        if (!$flowModel) {
            return response()->json(['error' => 'Flow not found.'], 404);
        }

        if (!$flowModel->is_active) {
            return response()->json(['error' => 'Flow is not active.'], 422);
        }

        // Verify flow has a webhook trigger
        $hasTrigger = $flowModel->triggers()->where('type', 'webhook')->exists();
        if (!$hasTrigger) {
            return response()->json(['error' => 'Flow does not have a webhook trigger.'], 422);
        }

        $payload = $request->all();

        if (config('flow-builder.queue.enabled', true)) {
            ExecuteFlowJob::dispatch($flowModel->id, $payload);
            return response()->json([
                'message' => 'Flow execution queued.',
                'flow_id' => $flowModel->id,
            ], 202);
        }

        $execution = app(FlowEngine::class)->execute($flowModel, $payload);

        return response()->json([
            'execution_id' => $execution->id,
            'status' => $execution->status,
        ]);
    }

    /**
     * Manually execute a flow with provided payload.
     */
    public function execute(Request $request, int $flow): JsonResponse
    {
        $flowModel = Flow::find($flow);

        if (!$flowModel) {
            return response()->json(['error' => 'Flow not found.'], 404);
        }

        if (!$flowModel->is_active) {
            return response()->json(['error' => 'Flow is not active.'], 422);
        }

        $payload = $request->all();

        if (config('flow-builder.queue.enabled', true)) {
            ExecuteFlowJob::dispatch($flowModel->id, $payload);
            return response()->json([
                'message' => 'Flow execution queued.',
                'flow_id' => $flowModel->id,
            ], 202);
        }

        $execution = app(FlowEngine::class)->execute($flowModel, $payload);

        return response()->json([
            'execution_id' => $execution->id,
            'status' => $execution->status,
        ]);
    }
}
