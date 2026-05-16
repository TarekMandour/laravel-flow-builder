<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;
use Arabiacode\LaravelFlowBuilder\Models\Integration;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
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
            'ai_agent' => $this->callAiAgent($integration, $data, $params, $state),
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

        $to[] = $state->resolveValue($data['device_token'] ?? $params['device_token'] ?? '');
        $title = $state->resolveValue($data['title'] ?? $params['title'] ?? '');
        $body = $state->resolveValue($data['body'] ?? $params['body'] ?? '');
        $type = $state->resolveValue($data['type'] ?? $params['type'] ?? '');
        $type_id = $state->resolveValue($data['type_id'] ?? $params['type_id'] ?? '');

        try {

            $firebase = (new Factory)->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));
 
            $messaging = $firebase->createMessaging();
    
            if (isset($to) && $to != null && $to != 0) { 
                $message = CloudMessage::fromArray([
                    'notification' => [
                        'title' => (string) $title,
                        'body' => (string)$body
                    ], // optional
                    'data' => [
                        'title' => (string) $title,
                        'body' => (string) $body,
                        'type' => (string) $type,
                        'type_id' => (string) $type_id
                    ], // optional
                ]);
                
                $messaging->sendMulticast($message, $to);

                return [
                    'firebase_sent' => true,
                    'status' => 1,
                    'error' => '',
                ];
            } else {
                return [
                    'firebase_sent' => false,
                    'status' => 0,
                    'error' => 'No device token provided',
                ];
            }       

        } catch (\Exception $e) {
            return [
                'firebase_sent' => false,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }

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

    protected function callAiAgent(?Integration $integration, array $data, array $params, FlowState $state): array
    {
        $apiKey = $integration
            ? ($integration->credentials['api_key'] ?? config('flow-builder.integrations.ai_agent.api_key'))
            : config('flow-builder.integrations.ai_agent.api_key');

        $model = $integration
            ? ($integration->credentials['model'] ?? config('flow-builder.integrations.ai_agent.model'))
            : config('flow-builder.integrations.ai_agent.model');

        $url = $integration
            ? ($integration->credentials['url'] ?? config('flow-builder.integrations.ai_agent.url'))
            : config('flow-builder.integrations.ai_agent.url');

        if (!$apiKey) {
            throw new \RuntimeException('AI Agent API key is not configured.');
        }

        $systemPrompt = $state->resolveValue($data['system_prompt'] ?? '');
        $userMessage  = $state->resolveValue($data['user_message'] ?? '');

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $body = ['model' => $model, 'messages' => $messages];
        if (!empty($data['max_tokens'])) {
            $body['max_tokens'] = (int) $data['max_tokens'];
        }
        if (isset($data['temperature']) && $data['temperature'] !== '') {
            $body['temperature'] = (float) $data['temperature'];
        }

        try {
            $response = Http::withToken($apiKey)->withOptions([
                'curl' => [
                    CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA,
                ],
            ])->post($url, $body);
            $json = $response->json();

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'text'    => $json['choices'][0]['message']['content'] ?? null,
                'model'   => $json['model'] ?? $model,
                'usage'   => $json['usage'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status'  => 0,
                'text'    => null,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
