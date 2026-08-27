<?php

namespace epicsnail\LaravelTracing\Trace;

use OpenTelemetry\API\Context\Context;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

final class TracePropagator
{
    private TraceContextPropagator $propagator;

    public function __construct()
    {
        $this->propagator = TraceContextPropagator::getInstance();
    }

    public function inject(array &$carrier): void
    {
        $this->propagator->inject($carrier);
    }

    public function extract(array $carrier): Context
    {
        return $this->propagator->extract($carrier);
    }
}