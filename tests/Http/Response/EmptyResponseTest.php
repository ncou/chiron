<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Response;

use Chiron\Http\Factory\ServerRequestFactory;
use Chiron\Http\Middleware\BodyLimitMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Tests\Utils\HandlerProxy2;
use PHPUnit\Framework\TestCase;
use Chiron\Http\Response\EmptyResponse;

class EmptyResponseTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $response = new EmptyResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(204, $response->getStatusCode());
        // TODO : vérifier que le header est un tableau vide
    }

    public function testHeadersConstructor()
    {
        $response = new EmptyResponse(['x-empty' => 'true']);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('true', $response->getHeaderLine('x-empty'));
    }

}
