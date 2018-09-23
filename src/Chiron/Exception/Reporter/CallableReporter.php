<?php

declare(strict_types=1);

namespace Chiron\Exception\Reporter;

use Exception;
use Throwable;

class CallableReporter implements ReporterInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * Create a new exception handler instance.
     *
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Report or log an exception.
     *
     * @param \Throwable                               $e
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    // TODO : créer une classe "LogReporter" et ReporterInterface pour externaliser le code et permettre de mettre plusieurs reporters
    public function report(Throwable $e): void
    {
        return call_user_func($this->callable, $e);
    }

    /**
     * Can we report the exception?
     *
     * @param \Throwable $e
     *
     * @return bool
     */
    public function canReport(Throwable $e): bool
    {
        return true;
    }
}