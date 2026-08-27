<?php

namespace vinter\LaravelTracing;

use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

final class Bootstrap
{
    public static function createTracerProvider(): TracerProviderInterface
    {
        $endpoint = rtrim((string) config('tracing.exporter.endpoint'), '/');
        $endpoint .= '/v1/traces';

        $transport = (new PsrTransportFactory())->create(
            $endpoint,
            'application/x-protobuf',
            [
                'timeout' => (float) config('tracing.exporter.timeout', 5),
            ]
        );

        $exporter = new SpanExporter($transport);
        $processor = new SimpleSpanProcessor($exporter);

        $resource = ResourceInfoFactory::emptyResource()->merge(
            ResourceInfo::create([
                'service.name' => config('tracing.service_name'),
                'service.version' => env('APP_VERSION', 'unknown'),
                'deployment.environment' => config('tracing.attributes.deployment.environment'),
            ])
        );

        $sampler = new TraceIdRatioBasedSampler(
            max(0.0, min(1.0, (float) config('tracing.sampling.rate', 0.1)))
        );

        $provider = new TracerProvider(
            $processor,
            $sampler,
            $resource
        );

        if (class_exists(Sdk::class)) {
            Sdk::builder()
                ->setTracerProvider($provider)
                ->buildAndRegisterGlobal();
        }

        return $provider;
    }
}