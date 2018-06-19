<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Exception;

use Chiron\Http\Exception\ExpectationFailedHttpException;

class ExpectationFailedHttpExceptionTest extends HttpExceptionTest
{
    protected function createException()
    {
        return new ExpectationFailedHttpException();
    }
}
