<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing\Resolver\Fixtures;

/**
 * Mock object for ControllerResolverTest.
 */
class CallableTest
{
    public static $CalledCount = 0;

    public function toCall()
    {
        static::$CalledCount++;
    }
}