<?php

namespace epicsnail\LaravelTracing\Logging;

use OpenTelemetry\API\Trace\Span;

final class TraceProcessor
{
    public function __invoke(array $record): array
    {
        $spanContext = Span::getCurrent()->getContext();

        if (!$spanContext->isValid()) {
            return $record;
        }

        $record['extra']['trace_id'] = $spanContext->getTraceId();
        $record['extra']['span_id'] = $spanContext->getSpanId();

        return $record;
    }
}