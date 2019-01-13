<?php

declare(strict_types=1);

namespace Chiron\Tests\Routing;

use Chiron\Routing\Route;
use Chiron\Routing\RouteGroup;
use Chiron\Routing\Router;
use Chiron\Routing\Strategy\StrategyInterface;
//use Psr\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chiron\Routing\Route
 */
class RouteGroupTest extends TestCase
{
    public function testRouteGroup()
    {
        $router = new Router();

        $res = $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            });
        });

        $this->assertInstanceOf(RouteGroup::class, $res);
        //$this->assertEquals('/prefix', $res->getPrefix());

        $this->assertEquals('test_1', ($router->getRoutes()[0])->getName());
        $this->assertEquals('/prefix', ($router->getRoutes()[0])->getPath());

        $this->assertEquals('test_2', ($router->getRoutes()[1])->getName());
        $this->assertEquals('/prefix/group/foo', ($router->getRoutes()[1])->getPath());

        $this->assertEquals('test_3', ($router->getRoutes()[2])->getName());
        $this->assertEquals('/prefix/group/bar', ($router->getRoutes()[2])->getPath());
    }

    public function testRouteGroupWithOverrideHostPortScheme()
    {
        $router = new Router();

        $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1')->host('baz');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            })->port(8080);
        })->scheme('https');

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals('baz', $route_1->getHost());
        $this->assertEquals(null, $route_1->getPort());
        $this->assertEquals('https', $route_1->getScheme());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals(null, $route_2->getHost());
        $this->assertEquals(8080, $route_2->getPort());
        $this->assertEquals('https', $route_2->getScheme());

        $route_3 = $router->getNamedRoute('test_3');
        $this->assertEquals(null, $route_3->getHost());
        $this->assertEquals(8080, $route_3->getPort());
        $this->assertEquals('https', $route_3->getScheme());
    }

    public function testRouteGroupWithOverrideMiddleware()
    {
        $router = new Router();

        $router->group('/prefix', function ($group) {
            $group->get('/', function () {
                return 'ROUTE_1';
            })->name('test_1')->middleware('MIDDLEWARE_1');

            $group->group('/group/', function ($group) {
                $group->get('/foo', function () {
                    return 'ROUTE_2';
                })->name('test_2')->middleware('MIDDLEWARE_2');
                $group->get('/bar', function () {
                    return 'ROUTE_3';
                })->name('test_3');
            })->middleware('MIDDLEWARE_3');
        })->middleware('MIDDLEWARE_4');

        $route_1 = $router->getNamedRoute('test_1');
        $this->assertEquals(['MIDDLEWARE_1', 'MIDDLEWARE_4'], $route_1->gatherMiddlewareStack());

        $route_2 = $router->getNamedRoute('test_2');
        $this->assertEquals(['MIDDLEWARE_2', 'MIDDLEWARE_4', 'MIDDLEWARE_3'], $route_2->gatherMiddlewareStack());

        $route_3 = $router->getNamedRoute('test_3');
        $this->assertEquals(['MIDDLEWARE_4', 'MIDDLEWARE_3'], $route_3->gatherMiddlewareStack());
    }

    public function testRouteMiddlewareTrait()
    {
        $router = new Router();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals([], $group->getMiddlewareStack());

        $group->middleware('baz');

        $this->assertEquals('baz', $group->getMiddlewareStack()[0]);

        $group->prependMiddleware('qux');

        $this->assertEquals('qux', $group->getMiddlewareStack()[0]);
    }

    public function testRouteConditionTrait()
    {
        $router = new Router();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals(null, $group->getHost());
        $this->assertEquals(null, $group->getScheme());
        $this->assertEquals(null, $group->getPort());

        $group->setHost('host_1');
        $this->assertEquals('host_1', $group->getHost());

        $group->host('host_2');
        $this->assertEquals('host_2', $group->getHost());

        $group->setScheme('http');
        $this->assertEquals('http', $group->getScheme());

        $group->scheme('https');
        $this->assertEquals('https', $group->getScheme());

        $group->requireHttp();
        $this->assertEquals('http', $group->getScheme());

        $group->requireHttps();
        $this->assertEquals('https', $group->getScheme());

        $group->setPort(8080);
        $this->assertEquals(8080, $group->getPort());

        $group->port(8181);
        $this->assertEquals(8181, $group->getPort());
    }

    public function testRouteStrategyTrait()
    {
        $router = new Router();
        $group = $router->group('/prefix', function () {
        });

        $this->assertEquals(null, $group->getStrategy());

        $strategyMock = $this->createMock(StrategyInterface::class);
        $group->setStrategy($strategyMock);

        $this->assertEquals($strategyMock, $group->getStrategy());
    }

    /**
     * Asserts that the collection can map and return a route object.
     */
    public function testRouteCollectionTraitHttpMethods()
    {
        $router = new Router();
        $group = $router->group('/prefix', function ($group) {
            $group->get('/get/', 'foobar');
            $group->post('/post/', 'foobar');
            $group->put('/put/', 'foobar');
            $group->patch('/patch/', 'foobar');
            $group->delete('/delete/', 'foobar');
            $group->head('/head/', 'foobar');
            $group->options('/options/', 'foobar');
            $group->trace('/trace/', 'foobar');
        });

        $routes = $router->getRoutes();

        $this->assertSame(8, count($routes));

        $route_1 = $router->getRoutes()[0];
        $this->assertSame([strtoupper('get')], $route_1->getAllowedMethods());

        $route_2 = $router->getRoutes()[1];
        $this->assertSame([strtoupper('post')], $route_2->getAllowedMethods());

        $route_3 = $router->getRoutes()[2];
        $this->assertSame([strtoupper('put')], $route_3->getAllowedMethods());

        $route_4 = $router->getRoutes()[3];
        $this->assertSame([strtoupper('patch')], $route_4->getAllowedMethods());

        $route_5 = $router->getRoutes()[4];
        $this->assertSame([strtoupper('delete')], $route_5->getAllowedMethods());

        $route_6 = $router->getRoutes()[5];
        $this->assertSame([strtoupper('head')], $route_6->getAllowedMethods());

        $route_7 = $router->getRoutes()[6];
        $this->assertSame([strtoupper('options')], $route_7->getAllowedMethods());

        $route_8 = $router->getRoutes()[7];
        $this->assertSame([strtoupper('trace')], $route_8->getAllowedMethods());
    }

    public function testRouteCollectionTraitMap()
    {
        $router = new Router();
        $group = $router->group('/prefix', function ($group) {
            $group->map('/', 'foobar');
        });

        $routes = $router->getRoutes();

        $this->assertSame(1, count($routes));

        $route = $router->getRoutes()[0];

        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'], $route->getAllowedMethods());
    }
}
