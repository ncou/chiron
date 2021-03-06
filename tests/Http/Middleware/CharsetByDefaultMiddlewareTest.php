<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Middleware;

use Chiron\Http\Middleware\CharsetByDefaultMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Tests\Utils\RequestHandlerCallable;
use PHPUnit\Framework\TestCase;

class CharsetByDefaultMiddlewareTest extends TestCase
{
    public function testContentTypeIsNotAdded()
    {
        $request = new ServerRequest('GET', new Uri('/'));

        $handler = function ($request) {
            return new Response();
        };
        $middleware = new CharsetByDefaultMiddleware();
        $response = $middleware->process($request, new RequestHandlerCallable($handler));

        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function testWithTextualContentType()
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $handler = function ($request) {
            $response = new Response();
            $response = $response->withHeader('Content-Type', 'text/plain; boundary=something');

            return $response;
        };
        $middleware = new CharsetByDefaultMiddleware('iso-8859-1');
        $response = $middleware->process($request, new RequestHandlerCallable($handler));

        $this->assertEquals('text/plain; boundary=something; charset=iso-8859-1', $response->getHeaderLine('Content-Type'));
    }

    public function testWithNonTextualContentType()
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $handler = function ($request) {
            $response = new Response();
            $response = $response->withHeader('Content-Type', 'application/pdf');

            return $response;
        };
        $middleware = new CharsetByDefaultMiddleware('iso-8859-1');
        $response = $middleware->process($request, new RequestHandlerCallable($handler));

        $this->assertEquals('application/pdf', $response->getHeaderLine('Content-Type'));
    }

    public function testWithNonTextualContentTypeButWhitlisted()
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $handler = function ($request) {
            $response = new Response();
            $response = $response->withHeader('Content-Type', 'application/json');

            return $response;
        };
        $middleware = new CharsetByDefaultMiddleware('iso-8859-1');
        $response = $middleware->process($request, new RequestHandlerCallable($handler));

        $this->assertEquals('application/json; charset=iso-8859-1', $response->getHeaderLine('Content-Type'));
    }

    public function testWithNonTextualContentTypeButWhitlistedAndWithParamInContentTypeHeader()
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $handler = function ($request) {
            $response = new Response();
            $response = $response->withHeader('Content-Type', 'application/json; boundary=something');

            return $response;
        };
        $middleware = new CharsetByDefaultMiddleware('iso-8859-1');
        $response = $middleware->process($request, new RequestHandlerCallable($handler));

        $this->assertEquals('application/json; boundary=something; charset=iso-8859-1', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testReturnsErrorResponseIfHandlerRaisesAnException_StartWithNumeric()
    {
        $middleware = new CharsetByDefaultMiddleware('123456-UTF');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testReturnsErrorResponseIfHandlerRaisesAnException_StringToShort()
    {
        $middleware = new CharsetByDefaultMiddleware('utf');
    }
}
