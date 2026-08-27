<?php

return [
    'enabled' => (bool) env('TRACING_ENABLED', true),

    'service_name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'laravel-service')),

    'exporter' => [
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4318'),
        'timeout' => (float) env('OTEL_EXPORTER_OTLP_TIMEOUT', 5),
    ],

    'sampling' => [
        'rate' => (float) env('OTEL_TRACE_SAMPLE_RATE', 0.1),
    ],

    'middleware' => [
        'enabled' => true,
        'add_response_trace_id_header' => (bool) env('TRACING_RESPONSE_TRACE_ID', true),
    ],

    'queue' => [
        'enabled' => true,
    ],

    'logging' => [
        'enabled' => true,
        'trace_id' => true,
        'span_id' => true,
    ],

    'attributes' => [
        'deployment.environment' => env('APP_ENV', 'production'),
    ],

    'redact' => [
        'access_token',
        'authorization',
        'app_key',
        'sign',
        'password',
        'passwd',
        'secret',
        'token',
    ],
];