<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Middleware;

use Chiron\Http\Middleware\ContentLengthMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Tests\Utils\RequestHandlerCallable;
use PHPUnit\Framework\TestCase;

class ContentLengthMiddlewareTest extends TestCase
{
    protected $middleware;

    public $request;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new ContentLengthMiddleware();
        $this->request = new ServerRequest('GET', new Uri('/'));
    }

    public function testEmptyContentLenght()
    {
        $handler = function ($request) {
            return new Response(); // it's a response with an empty body (so the size = 0)
        };
        $middleware = $this->middleware;
        $result = $middleware->process($this->request, new RequestHandlerCallable($handler));
        //$this->assertNull($result->getHeaderLine('Content-Length'));
        //$this->assertFalse($result->hasHeader('Content-Length'));
        $this->assertEquals(0, $result->getHeaderLine('Content-Length'));
    }

    public function testNoContentLenghtBecauseBodyIsNull()
    {
        $handler = function ($request) {
            $response = new Response();
            // destroy the body ressource so the size can't be calculated !
            $response->getBody()->detach();

            return $response;
        };
        $middleware = $this->middleware;
        $result = $middleware->process($this->request, new RequestHandlerCallable($handler));
        //$this->assertNull($result->getHeaderLine('Content-Length'));
        $this->assertFalse($result->hasHeader('Content-Length'));
        //$this->assertEquals(0, $result->getHeaderLine('Content-Length'));
    }

    public function testWithTransfertEncoding()
    {
        $handler = function ($request) {
            $response = new Response();
            $response->getBody()->write('Body');

            $response = $response->withHeader('Transfer-Encoding', 'chunked');

            return $response;
        };
        $middleware = $this->middleware;
        $result = $middleware->process($this->request, new RequestHandlerCallable($handler));
        $this->assertFalse($result->hasHeader('Content-Length'));
    }

    public function testWithTransfertEncodingAndRemoveContentLength()
    {
        $handler = function ($request) {
            $response = new Response();
            $response->getBody()->write('Body');

            $response = $response->withHeader('Content-Length', '4');
            $response = $response->withHeader('Transfer-Encoding', 'chunked');

            return $response;
        };
        $middleware = $this->middleware;
        $result = $middleware->process($this->request, new RequestHandlerCallable($handler));
        $this->assertFalse($result->hasHeader('Content-Length'));
    }

    public function testAddsContentLenght()
    {
        $handler = function ($request) {
            $response = new Response();
            $response->getBody()->write('Body');

            return $response;
        };
        $middleware = $this->middleware;
        $result = $middleware->process($this->request, new RequestHandlerCallable($handler));
        $this->assertEquals(4, $result->getHeaderLine('Content-Length'));
    }
}
