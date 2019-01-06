<?php

declare(strict_types=1);

namespace Chiron\Routing\Resolver;

/**
 * Resolve a callable.
 */
interface ControllerResolverInterface
{
    /**
     * Invoke the resolved callable.
     *
     * @param callable|string $toResolve
     *
     * @return callable
     */
    public function resolve($toResolve): callable;
}