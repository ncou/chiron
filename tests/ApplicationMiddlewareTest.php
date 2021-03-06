<?php

declare(strict_types=1);

namespace Chiron\Tests\Middleware;

use Chiron\Application;
use Chiron\Http\Middleware\DispatcherMiddleware;
use Chiron\Http\Middleware\RoutingMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Http\Psr\ServerRequest;
use Chiron\Http\Psr\Uri;
use Chiron\Kernel;
use Chiron\Pipe\Decorator\CallableMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationMiddlewareTest extends TestCase
{
    /********************************************************************************
     * Middleware - Application
     *******************************************************************************/

    public function testApplicationWithoutMiddleware()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $app = new Application(new Kernel());
        $response = $app->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
    }

    public function testMiddlewareWithMiddlewareInterface()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $callable = function ($request, $handler) {
            return (new Response())->write('MIDDLEWARE');
        };
        $middleware = new CallableMiddleware($callable);

        $app = new Application(new Kernel());
        $app->middleware($middleware);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE', (string) $response->getBody());
    }

    public function testMiddlewareWithCallable()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $callable = function ($request, $handler) {
            return (new Response())->write('MIDDLEWARE');
        };

        $app = new Application(new Kernel());
        $app->middleware($callable);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE', (string) $response->getBody());
    }

    /**
     * @expectedException \Chiron\Container\Exception\EntryNotFoundException
     * @expectedExceptionMessage Identifier "MiddlewareNotPresentInTheContainer" is not defined in the container.
     */
    public function testMiddlewareWithStringNotPresentInContainer()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $app = new Application(new Kernel());
        $app->middleware('MiddlewareNotPresentInTheContainer');

        $response = $app->handle($request);
    }

    public function testMiddlewareWithStringInContainer()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $entry = function ($c) {
            $callable = function ($request, $handler) {
                return (new Response())->write('MIDDLEWARE');
            };

            return new CallableMiddleware($callable);
        };

        $app = new Application(new Kernel());
        $app->kernel->set('MiddlewareCallableInContainer', $entry);

        $app->middleware('MiddlewareCallableInContainer');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Middleware "integer" is neither a string service name, a PHP callable, or a Psr\Http\Server\MiddlewareInterface instance
     */
    public function testMiddlewareWithInvalidMiddleware()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $app = new Application(new Kernel());

        $app->middleware(123456);

        $response = $app->handle($request);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The middleware present in the container should be a PHP callable or a Psr\Http\Server\MiddlewareInterface instance
     */
    public function testMiddlewareWithInvalidMiddlewareInContainer()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $badEntry = function ($c) {
            return 123456;
        };

        $app = new Application(new Kernel());
        $app->kernel->set('BadMiddlewareType', $badEntry);

        $app->middleware('BadMiddlewareType');

        $response = $app->handle($request);
    }

    public function testMiddlewareWithArrayOfMiddlewareInterface()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $callable1 = function ($request, $handler) {
            $response = $handler->handle($request);

            return $response->write('MIDDLEWARE_1');
        };
        $middleware1 = new CallableMiddleware($callable1);
        //---
        $callable2 = function ($request, $handler) {
            $response = new Response();

            return $response->write('MIDDLEWARE_2_');
        };
        $middleware2 = new CallableMiddleware($callable2);

        $app = new Application(new Kernel());
        $app->middleware([$middleware1, $middleware2]);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE_2_MIDDLEWARE_1', (string) $response->getBody());
    }

    public function testMiddlewareWithArrayOfCallable()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $callable1 = function ($request, $handler) {
            $response = $handler->handle($request);

            return $response->write('MIDDLEWARE_1');
        };
        //---
        $callable2 = function ($request, $handler) {
            $response = new Response();

            return $response->write('MIDDLEWARE_2_');
        };

        $app = new Application(new Kernel());
        $app->middleware([$callable1, $callable2]);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE_2_MIDDLEWARE_1', (string) $response->getBody());
    }

    public function testMiddlewareWithArrayOfString()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $entry1 = function ($c) {
            $callable1 = function ($request, $handler) {
                $response = $handler->handle($request);
                $response->write('MIDDLEWARE_1');

                return $response;
            };

            return new CallableMiddleware($callable1);
        };
        //---
        $entry2 = function ($c) {
            $callable2 = function ($request, $handler) {
                $response = new Response();
                $response->write('MIDDLEWARE_2_');

                return $response;
            };

            return new CallableMiddleware($callable2);
        };

        $app = new Application(new Kernel());
        $app->kernel->set('ENTRY_1', $entry1);
        $app->kernel->set('ENTRY_2', $entry2);

        $app->middleware(['ENTRY_1', 'ENTRY_2']);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MIDDLEWARE_2_MIDDLEWARE_1', (string) $response->getBody());
    }

    /********************************************************************************
     * Middleware - Route
     *******************************************************************************/

    public function testRouteWithoutMiddleware()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $routeCallback = function (ServerRequestInterface $request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Application(new Kernel());
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->router->get('/foo', $routeCallback);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS', (string) $response->getBody());
    }

    public function testRouteWithMiddlewareInterface()
    {
        $request = new ServerRequest('GET', new Uri('/foo'));

        $routeCallback = function ($request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Application(new Kernel());
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $route = $app->router->get('/foo', $routeCallback);

        $callable = function ($request, $handler) {
            $response = $handler->handle($request);

            return $response->write('_MIDDLEWARE');
        };
        $middleware = new CallableMiddleware($callable);
        $route->middleware($middleware);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS_MIDDLEWARE', (string) $response->getBody());
    }

    /********************************************************************************
     * Middleware - RouteGroup
     *******************************************************************************/

    public function testRouteGroupWithoutMiddleware()
    {
        $request = new ServerRequest('GET', new Uri('/foo/bar'));

        $routeCallback = function (ServerRequestInterface $request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Application(new Kernel());
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $group = $app->router->group('/foo', function ($group) use ($routeCallback) {
            $group->get('/bar', $routeCallback);
        });

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS', (string) $response->getBody());
    }

    public function testRouteGroupWithMiddlewareInterface()
    {
        $request = new ServerRequest('GET', new Uri('/foo/bar'));

        $routeCallback = function ($request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Application(new Kernel());
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $group = $app->router->group('/foo', function ($group) use ($routeCallback) {
            $group->get('/bar', $routeCallback);
        });

        $callable = function ($request, $handler) {
            $response = $handler->handle($request);

            return $response->write('_MIDDLEWARE-GROUP');
        };
        $middleware = new CallableMiddleware($callable);
        $group->middleware($middleware);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS_MIDDLEWARE-GROUP', (string) $response->getBody());
    }

    public function testRouteGroupAndRouteWithMiddlewareInterface()
    {
        $request = new ServerRequest('GET', new Uri('/foo/bar'));

        $routeCallback = function ($request) {
            $response = new Response();

            return $response->write('SUCCESS');
        };

        $app = new Application(new Kernel());
        $app->middleware([RoutingMiddleware::class, DispatcherMiddleware::class]);
        $group = $app->router->group('/foo', function ($group) use ($routeCallback) {
            $callable1 = function ($request, $handler) {
                $response = $handler->handle($request);

                return $response->write('_MIDDLEWARE-ROUTE');
            };
            $middleware1 = new CallableMiddleware($callable1);

            $group->get('/bar', $routeCallback)->middleware($middleware1);
        });

        $callable2 = function ($request, $handler) {
            $response = $handler->handle($request);

            return $response->write('_MIDDLEWARE-GROUP');
        };
        $middleware2 = new CallableMiddleware($callable2);
        $group->middleware($middleware2);

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SUCCESS_MIDDLEWARE-GROUP_MIDDLEWARE-ROUTE', (string) $response->getBody());
    }
}
