<?php

namespace epicsnail\LaravelTracing\Trace;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as PsrRequest;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;

final class TraceableHttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly TraceManager $traceManager,
        private readonly TracePropagator $propagator
    ) {}

    public function request(string $method, string $uri, array $options = [])
    {
        $span = $this->traceManager->startSpan(
            'HTTP ' . strtoupper($method),
            SpanKind::KIND_CLIENT
        );

        $scope = $span->activate();
        $headers = $options['headers'] ?? [];

        try {
            $this->propagator->inject($headers);
            $options['headers'] = $headers;

            $span->setAttribute('http.request.method', strtoupper($method));
            $span->setAttribute('url.full', $uri);

            $response = $this->client->request($method, $uri, $options);

            $span->setAttribute('http.response.status_code', $response->getStatusCode());

            if ($response->getStatusCode() >= 500) {
                $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR);
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