<?php

namespace epicsnail\LaravelTracing\Trace;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;

final class TraceManager
{
    public function __construct(private readonly TracerInterface $tracer) {}

    public function enabled(): bool
    {
        return (bool) config('tracing.enabled', true);
    }

    public function startServerSpan(Request $request, $parentContext = null): SpanInterface
    {
        $builder = $this->tracer
            ->spanBuilder($request->method() . ' ' . $request->path())
            ->setSpanKind(SpanKind::KIND_SERVER);

        if ($parentContext !== null) {
            $builder->setParent($parentContext);
        }

        $span = $builder->startSpan();

        $span->setAttribute('http.request.method', $request->method());
        $span->setAttribute('url.path', $request->path());
        $span->setAttribute('server.address', $request->getHost());

        return $span;
    }

    public function startSpan(string $name, SpanKind $kind = SpanKind::KIND_INTERNAL, $parentContext = null): SpanInterface
    {
        $builder = $this->tracer->spanBuilder($name)->setSpanKind($kind);

        if ($parentContext !== null) {
            $builder->setParent($parentContext);
        }

        return $builder->startSpan();
    }

    public function currentTraceId(): ?string
    {
        $context = Span::getCurrent()->getContext();
        return $context->isValid() ? $context->getTraceId() : null;
    }

    public function currentSpanId(): ?string
    {
        $context = Span::getCurrent()->getContext();
        return $context->isValid() ? $context->getSpanId() : null;
    }

    public function recordException(SpanInterface $span, \Throwable $e): void
    {
        $span->recordException($e);
        $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
    }

    public function setResponse(SpanInterface $span, Response $response): void
    {
        $status = $response->getStatusCode();
        $span->setAttribute('http.response.status_code', $status);

        if ($status >= 500) {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }
    }
}