<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;
use Arabiacode\LaravelFlowBuilder\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationExecutor implements NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed
    {
        $data = $node->data ?? [];
        $integrationId = $data['integration_id'] ?? null;

        if ($integrationId) {
            $integration = Integration::findOrFail($integrationId);
            $type = $integration->type;
        } else {
            $type = $data['type'] ?? null;
            $integration = null;
        }

        $params = $state->resolveArray($data['params'] ?? []);

        $result = match ($type) {
            'webhook' => $this->callWebhook($data, $params, $state),
            'whatsapp' => $this->sendWhatsApp($integration, $data, $params, $state),
            'firebase' => $this->sendFirebaseNotification($integration, $data, $params, $state),
            'google_drive' => $this->googleDriveUpload($integration, $data, $params, $state),
            default => throw new \InvalidArgumentException("Unknown integration type: {$type}"),
        };

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $result);
        }

        return $result;
    }

    protected function callWebhook(array $data, array $params, FlowState $state): array
    {
        $url = $state->resolveValue($data['url'] ?? $params['url'] ?? '');
        $method = strtolower($data['method'] ?? 'post');
        $headers = $state->resolveArray($data['headers'] ?? []);
        $body = !empty($params) ? $params : $state->resolveArray($data['body'] ?? []);

        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid webhook URL: {$url}");
        }

        try {
            $response = Http::withoutVerifying()->withHeaders($headers)->$method($url, $body);
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'body' => $e->getMessage(),
                'success' => false,
            ];
        }

        return [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
            'success' => $response->successful(),
        ];
    }

    protected function sendWhatsApp(?Integration $integration, array $data, array $params, FlowState $state): array
    {
        $to = $state->resolveValue($data['to'] ?? $params['to'] ?? '');
        $message = $state->resolveValue($data['message'] ?? $params['message'] ?? '');

        $appkey = $integration
            ? ($integration->credentials['appkey'] ?? config('flow-builder.integrations.whatsapp.api_url'))
            : config('flow-builder.integrations.whatsapp.api_url');

        $authkey = $integration
            ? ($integration->credentials['authkey'] ?? config('flow-builder.integrations.whatsapp.api_key'))
            : config('flow-builder.integrations.whatsapp.api_key');
 
        if (!$appkey) {
            throw new \RuntimeException('WhatsApp API URL is not configured.');
        }

        try {
            $response = Http::withoutVerifying()->post('https://botsmsg.com/api/create-message', [
                'appkey' => $appkey,
                'authkey' => $authkey,
                'to' => $to,
                'message' => $message,
                'sandbox' => 'false'
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp API call failed', ['error' => $e->getMessage()]);
            return [
                'whatsapp_sent' => false,
                'to' => $to,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }

        if (!$response instanceof \Illuminate\Http\Client\Response) {
            Log::warning('WhatsApp API returned unexpected response type', ['type' => gettype($response)]);
            return [
                'whatsapp_sent' => false,
                'to' => $to,
                'status' => 0,
                'response' => $response,
            ];
        }

        Log::info('WhatsApp response', ['status' => $response->status(), 'body' => $response->body()]);

        return [
            'whatsapp_sent' => $response->successful(),
            'to' => $to,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ];
    }

    protected function sendFirebaseNotification(?Integration $integration, array $data, array $params, FlowState $state): array
    {
        $serverKey = $integration
            ? ($integration->credentials['server_key'] ?? config('flow-builder.integrations.firebase.server_key'))
            : config('flow-builder.integrations.firebase.server_key');

        if (!$serverKey) {
            throw new \RuntimeException('Firebase server key is not configured.');
        }

        $to = $state->resolveValue($data['device_token'] ?? $params['device_token'] ?? '');
        $title = $state->resolveValue($data['title'] ?? $params['title'] ?? '');
        $body = $state->resolveValue($data['body'] ?? $params['body'] ?? '');
        $extraData = $state->resolveArray($data['data'] ?? $params['data'] ?? []);

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $to,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $extraData,
            ]);
        } catch (\Exception $e) {
            return [
                'firebase_sent' => false,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'firebase_sent' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json(),
        ];
    }

    protected function googleDriveUpload(?Integration $integration, array $data, array $params, FlowState $state): array
    {
        // Google Drive integration requires OAuth2 — this is a placeholder
        // that can be extended with a proper Google API client.
        $action = $data['action'] ?? $params['action'] ?? 'upload';

        return [
            'google_drive' => true,
            'action' => $action,
            'message' => 'Google Drive integration requires additional setup. Override IntegrationExecutor to implement.',
        ];
    }
}
