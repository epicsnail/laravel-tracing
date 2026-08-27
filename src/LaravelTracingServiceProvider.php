<?php

namespace vinter\LaravelTracing;

use vinter\LaravelTracing\Logging\TraceProcessor;
use vinter\LaravelTracing\Middleware\TraceMiddleware;
use vinter\LaravelTracing\Queue\TraceQueueMiddleware;
use vinter\LaravelTracing\Trace\TraceManager;
use vinter\LaravelTracing\Trace\TracePropagator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use OpenTelemetry\API\Trace\TracerProviderInterface;

class LaravelTracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tracing.php', 'tracing');

        $this->app->singleton(TracerProviderInterface::class, function () {
            return Bootstrap::createTracerProvider();
        });

        $this->app->singleton(TracePropagator::class, fn () => new TracePropagator());
        $this->app->singleton(TraceManager::class, fn ($app) => new TraceManager(
            $app->make(TracerProviderInterface::class)->getTracer('laravel-tracing')
        ));

        $this->app->singleton(TraceQueueMiddleware::class, fn ($app) =>
            new TraceQueueMiddleware($app->make(TraceManager::class), $app->make(TracePropagator::class))
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tracing.php' => config_path('tracing.php'),
        ], 'tracing-config');

        if (config('tracing.middleware.enabled', true)) {
            $this->app['router']->pushMiddlewareToGroup('web', TraceMiddleware::class);
            $this->app['router']->pushMiddlewareToGroup('api', TraceMiddleware::class);
        }

        if (config('tracing.logging.enabled', true)) {
            $this->registerLogProcessor();
        }
    }

    private function registerLogProcessor(): void
    {
        try {
            $logger = Log::getLogger();
            if ($logger instanceof Logger) {
                $logger->pushProcessor(new TraceProcessor());
            }
        } catch (\Throwable) {
            // Logging configuration may be initialized later by Laravel.
        }
    }
}