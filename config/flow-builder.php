<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('FLOW_BUILDER_QUEUE_ENABLED', false),
        'connection' => env('FLOW_BUILDER_QUEUE_CONNECTION', null),
        'queue' => env('FLOW_BUILDER_QUEUE_NAME', 'flows'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Limits
    |--------------------------------------------------------------------------
    */
    'max_node_executions' => 100,

    /*
    |--------------------------------------------------------------------------
    | Delay Operation
    |--------------------------------------------------------------------------
    | Maximum seconds a delay node is allowed to sleep. Prevents runaway
    | flows from blocking queue workers indefinitely.
    */
    'max_delay_seconds' => env('FLOW_BUILDER_MAX_DELAY_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/flow-builder',
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Web Route Configuration
    |--------------------------------------------------------------------------
    */
    'web_prefix' => 'flow-builder',
    'web_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('FLOW_BUILDER_LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Executors
    |--------------------------------------------------------------------------
    | Register custom node executors here.
    | Format: 'node_type' => ExecutorClass::class
    */
    'executors' => [],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'whatsapp' => [
            'api_url' => env('FLOW_BUILDER_WHATSAPP_API_URL'),
            'api_key' => env('FLOW_BUILDER_WHATSAPP_API_KEY'),
        ],
        'firebase' => [
            'server_key' => env('FLOW_BUILDER_FIREBASE_SERVER_KEY'),
        ],
        'google_drive' => [
            'credentials_path' => env('FLOW_BUILDER_GOOGLE_DRIVE_CREDENTIALS'),
        ],
        'ai_agent' => [
            'api_key' => env('FLOW_BUILDER_AI_API_KEY'),
            'model'   => env('FLOW_BUILDER_AI_MODEL', 'llama-3.3-70b-versatile'),
            'url'     => env('FLOW_BUILDER_AI_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        ],
    ],

];
