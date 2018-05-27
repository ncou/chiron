<?php
/**
 * @see       https://github.com/zendframework/zend-stratigility for the canonical source repository
 *
 * @copyright Copyright (c) 2016-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Chiron\Tests\Http\Middleware;

use Chiron\Http\Middleware\ErrorHandlerMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Tests\Utils\HandlerProxy2;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;
use const E_USER_DEPRECATED;
use function error_reporting;
use function trigger_error;

class ErrorHandlerMiddlewareTest extends TestCase
{
    protected function setUp()
    {
        //$this->request = (new ServerRequestFactory())->createServerRequestFromArray($this->getMockServerValues());
        $this->request = $this->prophesize(ServerRequestInterface::class);

        $this->request->withAttribute(Argument::type('string'), Argument::type(Throwable::class))->will([$this->request, 'reveal']);
        $this->request->withAttribute(Argument::type('string'), Argument::type('bool'))->will([$this->request, 'reveal']);

        /*
        $this->request->withAttribute(Argument::type('string'), Argument::type('bool'))->will(function ($args) {
            return $args[1];
        });*/

        $this->errorReporting = error_reporting();
    }

    protected function tearDown()
    {
        error_reporting($this->errorReporting);
    }

    public function createMiddleware($isDevelopmentMode = true)
    {
        $errorHandlerMiddleware = new ErrorHandlerMiddleware($isDevelopmentMode);

        $handler = function ($request) {
            $response = new Response(500);
            $response->getBody()->write('Oops..');

            return $response;
        };

        $errorHandlerMiddleware->bindExceptionHandler(Throwable::class, new HandlerProxy2($handler));

        return $errorHandlerMiddleware;
    }

    public function testReturnsResponseFromHandlerWhenNoProblemsOccur()
    {
        $expectedResponse = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($expectedResponse);

        $middleware = $this->createMiddleware();

        $result = $middleware->process($this->request->reveal(), $handler->reveal());

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRethrowErrorIfHandlerIsNotDefined()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->willThrow(new RuntimeException('Exception raised'));

        $middleware = new ErrorHandlerMiddleware(true);

        $result = $middleware->process($this->request->reveal(), $handler->reveal());
    }

    public function testReturnsErrorResponseIfHandlerDoesNotReturnAResponse()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn(null);

        $middleware = $this->createMiddleware();

        $result = $middleware->process($this->request->reveal(), $handler->reveal());

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Oops..', (string) $result->getBody());
    }

    public function testReturnsErrorResponseIfHandlerRaisesAnErrorInTheErrorMask()
    {
        error_reporting(E_USER_DEPRECATED);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->will(function () {
                trigger_error('Deprecated', E_USER_DEPRECATED);
            });

        $middleware = $this->createMiddleware();

        $result = $middleware->process($this->request->reveal(), $handler->reveal());

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Oops..', (string) $result->getBody());
    }

    public function testReturnsResponseFromHandlerWhenErrorRaisedIsNotInTheErrorMask()
    {
        $originalMask = error_reporting();
        error_reporting($originalMask & ~E_USER_DEPRECATED);
        $expectedResponse = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->will(function () use ($expectedResponse) {
                trigger_error('Deprecated', E_USER_DEPRECATED);

                return $expectedResponse;
            });

        $middleware = $this->createMiddleware();

        $result = $middleware->process($this->request->reveal(), $handler->reveal());

        $this->assertSame($expectedResponse, $result);
    }

    public function testReturnsErrorResponseIfHandlerRaisesAnException()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::type(ServerRequestInterface::class))
            ->willThrow(new RuntimeException('Exception raised'));

        $middleware = $this->createMiddleware();

        $result = $middleware->process($this->request->reveal(), $handler->reveal());

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Oops..', (string) $result->getBody());
    }
}