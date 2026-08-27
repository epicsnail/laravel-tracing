<?php

namespace vinter\LaravelTracing\Middleware;

use Closure;
use vinter\LaravelTracing\Trace\TraceManager;
use vinter\LaravelTracing\Trace\TracePropagator;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\Span;
use Symfony\Component\HttpFoundation\Response;

class TraceMiddleware
{
    public function __construct(
        private readonly TraceManager $traceManager,
        private readonly TracePropagator $propagator
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->traceManager->enabled()) {
            return $next($request);
        }

        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = is_array($values) ? implode(',', $values) : $values;
        }

        $parentContext = $this->propagator->extract($headers);
        $span = $this->traceManager->startServerSpan($request, $parentContext);
        $scope = $span->activate();

        try {
            /** @var Response $response */
            $response = $next($request);
            $this->traceManager->setResponse($span, $response);

            if (config('tracing.middleware.add_response_trace_id_header', true)) {
                $traceId = $this->traceManager->currentTraceId();
                if ($traceId) {
                    $response->headers->set('X-Trace-Id', $traceId);
                }
            }

            return $response;
        } catch (\Throwable $e) {
            $this->traceManager->recordException($span, $e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}