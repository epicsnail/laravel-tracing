<?php

namespace epicsnail\LaravelTracing\Tests;

use PHPUnit\Framework\TestCase;

class TraceManagerTest extends TestCase
{
    public function testPackageStructureExists(): void
    {
        $this->assertTrue(class_exists(\epicsnail\LaravelTracing\Trace\TraceManager::class));
        $this->assertTrue(class_exists(\epicsnail\LaravelTracing\Trace\TracePropagator::class));
    }
}