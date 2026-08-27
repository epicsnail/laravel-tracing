<?php

namespace Example\LaravelTracing\Queue;

use vinter\LaravelTracing\Trace\TraceManager;
use vinter\LaravelTracing\Trace\TracePropagator;
use OpenTelemetry\API\Trace\SpanKind;

final class TraceQueueMiddleware
{
    public function __construct(
        private readonly TraceManager $traceManager,
        private readonly TracePropagator $propagator
    ) {}

    public function handle($job, $next)
    {
        if (!config('tracing.queue.enabled', true)) {
            return $next($job);
        }

        $payload = method_exists($job, 'payload') ? (array) $job->payload() : [];
        $headers = $payload['trace_headers'] ?? [];

        $parentContext = $this->propagator->extract($headers);

        $name = 'queue.' . (method_exists($job, 'resolveName')
            ? $job->resolveName()
            : get_class($job));

        $span = $this->traceManager->startSpan(
            $name,
            SpanKind::KIND_CONSUMER,
            $parentContext
        );

        $scope = $span->activate();

        try {
            return $next($job);
        } catch (\Throwable $e) {
            $this->traceManager->recordException($span, $e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}