<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Exception;

use Chiron\Http\Exception\UnavailableForLegalReasonsHttpException;

class UnavailableForLegalReasonsHttpExceptionTest extends HttpExceptionTest
{
    protected function createException()
    {
        return new UnavailableForLegalReasonsHttpException();
    }
}
