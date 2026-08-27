# Laravel Tracing

Laravel + OpenTelemetry + OTLP + Zipkin tracing package.

## Requirements

- PHP >= 8.1
- Laravel 9/10/11/12
- OpenTelemetry compatible Collector

## Install

```bash
composer require example/laravel-tracing
```

Publish config:

```bash
php artisan vendor:publish --tag=tracing-config
```

Environment:

```env
TRACING_ENABLED=true
OTEL_SERVICE_NAME=product-service
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_EXPORTER_OTLP_TIMEOUT=5
OTEL_TRACE_SAMPLE_RATE=0.1
TRACING_RESPONSE_TRACE_ID=true
```

## HTTP tracing

The package registers `TraceMiddleware` for Laravel `web` and `api` middleware groups.

It extracts W3C `traceparent`, creates a SERVER span, records HTTP status/errors and returns `X-Trace-Id`.

## Business span

```php
use Example\LaravelTracing\Trace\TraceManager;

public function create(TraceManager $tracing)
{
    $span = $tracing->startSpan('product.create');
    $scope = $span->activate();

    try {
        // business code
    } catch (\Throwable $e) {
        $tracing->recordException($span, $e);
        throw $e;
    } finally {
        $scope->detach();
        $span->end();
    }
}
```

## Queue

Attach `Example\LaravelTracing\Queue\TraceQueueMiddleware` to jobs:

```php
public function middleware(): array
{
    return [app(\Example\LaravelTracing\Queue\TraceQueueMiddleware::class)];
}
```

The producer must inject `traceparent` into the message/job metadata. For Laravel Queue, keep the tracing metadata in the job payload or a transport-specific header.

## Logging

The package adds:

```text
extra.trace_id
extra.span_id
```

to Monolog records when a valid current span exists.

## Architecture

```text
Laravel
  |
  +-- TraceMiddleware
  |
  +-- TraceManager
  |
  +-- Queue Middleware
  |
  +-- HTTP propagation
  |
  v
OpenTelemetry
  |
  | OTLP
  v
OpenTelemetry Collector
  |
  | Zipkin
  v
Zipkin
```

## Important

Do not put passwords, access tokens, signatures or full request bodies into span attributes. Use the `redact` configuration for application-level filtering.
