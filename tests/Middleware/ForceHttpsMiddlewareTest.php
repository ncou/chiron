<?php

declare(strict_types=1);

namespace Chiron\Tests\Middleware;

use Chiron\Http\Factory\ServerRequestFactory;
use Chiron\Http\Response;
use Chiron\Http\Uri;
use Chiron\Middleware\ForceHttpsMiddleware;
use Chiron\Tests\Utils\HandlerProxy2;
use PHPUnit\Framework\TestCase;

class ForceHttpsMiddlewareTest extends TestCase
{
    protected $middleware;

    public $request;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new ForceHttpsMiddleware();
        $this->request = (new ServerRequestFactory())->createServerRequestFromArray([
            'REQUEST_URI'            => '/',
            'REQUEST_METHOD'         => 'GET',
        ]);
    }

    public function testIsHttps()
    {
        $request = $this->request->withUri(
            new Uri('https://domain.com')
        );
        $handler = function ($request) {
            return (new Response())->withJson('SUCCESS');
        };
        $middleware = $this->middleware;
        $result = $middleware->process($request, new HandlerProxy2($handler));
        $this->assertEquals(json_encode('SUCCESS'), (string) $result->getBody());
    }

    public function testNotHttps()
    {
        $request = $this->request->withUri(
            new Uri('http://domain.com')
        );
        $handler = function ($request) {
            throw new \Exception('Should not make it here');
        };
        $middleware = $this->middleware;
        $result = $middleware->process($request, new HandlerProxy2($handler));
        $this->assertEquals(301, $result->getStatusCode());
        $this->assertEquals('https://domain.com', $result->getHeaderLine('Location'));
    }

    public function testNotHttpsWithCustomStatusCode()
    {
        $request = $this->request->withUri(
            new Uri('http://domain.com')
        );
        $handler = function ($request) {
            throw new \Exception('Should not make it here');
        };
        $middleware = new ForceHttpsMiddleware(307);
        $result = $middleware->process($request, new HandlerProxy2($handler));
        $this->assertEquals(307, $result->getStatusCode());
        $this->assertEquals('https://domain.com', $result->getHeaderLine('Location'));
    }

    public function testNotHttpsAndExceptURI()
    {
        $request = $this->request->withUri(
            new Uri('http://domain.com')
        );
        $handler = function ($request) {
            return (new Response())->withJson('SUCCESS');
        };
        $middleware = new ForceHttpsMiddleware(301, ['http://domain.com']);
        $result = $middleware->process($request, new HandlerProxy2($handler));
        $this->assertEquals(json_encode('SUCCESS'), (string) $result->getBody());
    }

    public function testNotHttpsAndExceptURIForPattern()
    {
        $request = $this->request->withUri(
            new Uri('http://domain.com/foo/bar')
        );
        $handler = function ($request) {
            return (new Response())->withJson('SUCCESS');
        };
        $middleware = new ForceHttpsMiddleware(301, ['http://domain.com/*']);
        $result = $middleware->process($request, new HandlerProxy2($handler));
        $this->assertEquals(json_encode('SUCCESS'), (string) $result->getBody());
    }
}
