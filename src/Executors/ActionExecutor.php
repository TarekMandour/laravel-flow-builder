<?php

namespace Arabiacode\LaravelFlowBuilder\Executors;

use Arabiacode\LaravelFlowBuilder\Contracts\NodeExecutor;
use Arabiacode\LaravelFlowBuilder\Engine\FlowState;
use Arabiacode\LaravelFlowBuilder\Models\FlowNode;
use Arabiacode\LaravelFlowBuilder\Notifications\FlowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class ActionExecutor implements NodeExecutor
{
    public function execute(FlowNode $node, FlowState $state): mixed
    {
        $data = $node->data ?? [];
        $action = $data['action'] ?? $data['operation'] ?? null;

        return match ($action) {
            'create' => $this->createModel($data, $state),
            'update' => $this->updateModel($data, $state),
            'delete' => $this->deleteModel($data, $state),
            'increment' => $this->incrementField($data, $state),
            'decrement' => $this->decrementField($data, $state),
            'get' => $this->queryGet($data, $state),
            'first' => $this->queryFirst($data, $state),
            'find' => $this->queryFind($data, $state),
            'send_notification' => $this->sendNotification($data, $state),
            'send_email' => $this->sendEmail($data, $state),
            'send_whatsapp' => $this->sendWhatsApp($data, $state),
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };
    }

    protected function createModel(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $attributes = $state->resolveArray($data['attributes'] ?? []);

        $model = $modelClass::create($attributes);

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $model->toArray());
        }

        return $model->toArray();
    }

    protected function updateModel(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $findBy = $state->resolveArray($data['find_by'] ?? []);
        $attributes = $state->resolveArray($data['attributes'] ?? []);

        $model = $this->findModel($modelClass, $findBy);
        $model->update($attributes);

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $model->fresh()->toArray());
        }

        return $model->fresh()->toArray();
    }

    protected function deleteModel(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $findBy = $state->resolveArray($data['find_by'] ?? []);

        $model = $this->findModel($modelClass, $findBy);
        $model->delete();

        return ['deleted' => true];
    }

    protected function incrementField(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $findBy = $state->resolveArray($data['find_by'] ?? []);
        $field = $data['field'];
        $value = (float) $state->resolveValue($data['value'] ?? 1);

        $model = $this->findModel($modelClass, $findBy);
        $model->increment($field, $value);

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $model->fresh()->toArray());
        }

        return $model->fresh()->toArray();
    }

    protected function decrementField(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $findBy = $state->resolveArray($data['find_by'] ?? []);
        $field = $data['field'];
        $value = (float) $state->resolveValue($data['value'] ?? 1);

        $model = $this->findModel($modelClass, $findBy);
        $model->decrement($field, $value);

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $model->fresh()->toArray());
        }

        return $model->fresh()->toArray();
    }

    protected function queryGet(array $data, FlowState $state): mixed
    {
        $query = $this->buildQuery($data, $state);

        $result = $query->get()->toArray();

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $result);
        }

        return $result;
    }

    protected function queryFirst(array $data, FlowState $state): mixed
    {
        $query = $this->buildQuery($data, $state);

        $model = $query->first();
        $result = $model ? $model->toArray() : null;

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $result);
        }

        return $result;
    }

    protected function queryFind(array $data, FlowState $state): mixed
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $id = $state->resolveValue($data['find_id'] ?? '');

        $columns = !empty($data['select_columns']) ? $data['select_columns'] : ['*'];
        $model = $modelClass::find($id, $columns);
        $result = $model ? $model->toArray() : null;

        if (isset($data['result_key'])) {
            $state->set($data['result_key'], $result);
        }

        return $result;
    }

    protected function buildQuery(array $data, FlowState $state)
    {
        $modelClass = $this->resolveModelClass($data['model']);
        $query = $modelClass::query();

        // Select columns
        if (!empty($data['select_columns'])) {
            $query->select($data['select_columns']);
        }

        // Where conditions
        if (!empty($data['where']) && is_array($data['where'])) {
            foreach ($data['where'] as $condition) {
                $field = $condition['field'] ?? '';
                $operator = $condition['operator'] ?? '=';
                $value = $state->resolveValue($condition['value'] ?? '');

                if ($field) {
                    $query->where($field, $operator, $value);
                }
            }
        }

        // Order by
        if (!empty($data['order_by'])) {
            $direction = $data['order_direction'] ?? 'asc';
            $query->orderBy($data['order_by'], $direction);
        }

        // Limit
        if (!empty($data['limit'])) {
            $query->limit((int) $data['limit']);
        }

        return $query;
    }

    protected function sendNotification(array $data, FlowState $state): mixed
    {
        $notifiableClass = $this->resolveModelClass($data['notifiable_model']);
        $notifiableId = $state->resolveValue($data['notifiable_id']);
        $channel = $data['channel'] ?? 'database';
        $title = $state->resolveValue($data['title'] ?? '');
        $message = $state->resolveValue($data['message'] ?? '');

        $notifiable = $notifiableClass::findOrFail($notifiableId);
        $notifiable->notify(new FlowNotification($title, $message, $channel));

        return ['notified' => true, 'channel' => $channel];
    }

    protected function sendEmail(array $data, FlowState $state): mixed
    {
        $to = $state->resolveValue($data['to']);
        $subject = $state->resolveValue($data['subject'] ?? '');
        $body = $state->resolveValue($data['body'] ?? '');

        Mail::send([], [], function ($mail) use ($to, $subject, $body) {
            $mail->to($to)
                ->subject($subject)
                ->html($body);
        });

        return ['email_sent' => true, 'to' => $to];
    }

    protected function sendWhatsApp(array $data, FlowState $state): mixed
    {
        $to = $state->resolveValue($data['to']);
        $message = $state->resolveValue($data['message'] ?? '');
        $apiUrl = config('flow-builder.integrations.whatsapp.api_url');
        $apiKey = config('flow-builder.integrations.whatsapp.api_key');

        if (!$apiUrl) {
            throw new \RuntimeException('WhatsApp API URL is not configured.');
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post($apiUrl, [
                'to' => $to,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return [
                'whatsapp_sent' => false,
                'to' => $to,
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'whatsapp_sent' => $response->successful(),
            'to' => $to,
            'status' => $response->status(),
        ];
    }

    protected function resolveModelClass(string $modelClass): string
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class does not exist: {$modelClass}");
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Class is not an Eloquent model: {$modelClass}");
        }

        return $modelClass;
    }

    protected function findModel(string $modelClass, array $findBy): Model
    {
        $query = $modelClass::query();

        foreach ($findBy as $field => $value) {
            $query->where($field, $value);
        }

        return $query->firstOrFail();
    }
}
