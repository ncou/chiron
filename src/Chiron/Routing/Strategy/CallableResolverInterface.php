<?php

declare(strict_types=1);

namespace Chiron\Routing\Strategy;

/**
 * Resolve a callable.
 */
interface CallableResolverInterface
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