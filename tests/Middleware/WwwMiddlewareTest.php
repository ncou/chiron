<?php

declare(strict_types=1);

namespace Chiron\Tests\Middleware;

use Chiron\Http\Factory\ServerRequestFactory;
use Chiron\Http\Response;
use Chiron\Http\Uri;

use Chiron\Middleware\WwwMiddleware;

use Chiron\Tests\Utils\HandlerProxy2;

use PHPUnit\Framework\TestCase;

class WwwMiddlewareTest extends TestCase
{
    public function wwwProvider()
    {
        return [
            [true, 'http://localhost', 'http://localhost'],
            [true, 'http://localhost.com', 'http://www.localhost.com'],
            [true, 'http://example.com', 'http://www.example.com'],
            [true, 'http://example.co.uk', 'http://www.example.co.uk'],
            [true, 'http://www.example.com', 'http://www.example.com'],
            [true, 'http://ww1.example.com', 'http://www.ww1.example.com'],
            [true, 'http://0.0.0.0', 'http://0.0.0.0'],
            [true, '', ''],
            [false, 'http://localhost', 'http://localhost'],
            [false, 'http://www.localhost.com', 'http://localhost.com'],
            [false, 'http://www.example.com', 'http://example.com'],
            [false, 'http://www.example.co.uk', 'http://example.co.uk'],
            [false, 'http://www.example.com', 'http://example.com'],
            [false, 'http://ww1.example.com', 'http://ww1.example.com'],
            [true, 'http://sub.domain.example.com', 'http://www.sub.domain.example.com'],
            [false, '', ''],
        ];
    }
    /**
     * @dataProvider wwwProvider
     */
    public function testAddWww(bool $addWww, string $uri, string $result)
    {
        $request = (new ServerRequestFactory())->createServerRequestFromArray([
            'REQUEST_URI'            => '/',
            'REQUEST_METHOD'         => 'GET'
        ]);

        $request = $request->withUri(
            new Uri($uri)
        );

        $handler = function ($request) {
            return (new Response(200));
        };
        $middleware = new WwwMiddleware($addWww);
        $response = $middleware->process($request, new HandlerProxy2($handler));

        if ($uri === $result) {
            $this->assertEquals(200, $response->getStatusCode());
        } else {
            $this->assertEquals(301, $response->getStatusCode());
            $this->assertEquals($result, $response->getHeaderLine('Location'));
        }
    }

}
