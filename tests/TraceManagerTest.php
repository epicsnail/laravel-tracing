<?php

namespace Example\LaravelTracing\Tests;

use PHPUnit\Framework\TestCase;

class TraceManagerTest extends TestCase
{
    public function testPackageStructureExists(): void
    {
        $this->assertTrue(class_exists(\Example\LaravelTracing\Trace\TraceManager::class));
        $this->assertTrue(class_exists(\Example\LaravelTracing\Trace\TracePropagator::class));
    }
}