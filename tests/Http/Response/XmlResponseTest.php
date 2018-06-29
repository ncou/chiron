<?php

declare(strict_types=1);

namespace Chiron\Tests\Http\Response;

use Chiron\Http\Factory\ServerRequestFactory;
use Chiron\Http\Middleware\BodyLimitMiddleware;
use Chiron\Http\Psr\Response;
use Chiron\Tests\Utils\HandlerProxy2;
use PHPUnit\Framework\TestCase;
use Chiron\Http\Response\XmlResponse;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

use const PHP_EOL;

class XmlResponseTest extends TestCase
{
    public function testConstructorAcceptsBodyAsString()
    {
        $body = 'Super valid XML';
        $response = new XmlResponse($body);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }
    public function testConstructorAllowsPassingStatus()
    {
        $body = 'More valid XML';
        $status = 404;
        $response = new XmlResponse($body, $status);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }
    public function testConstructorAllowsPassingHeaders()
    {
        $body = '<nearly>Valid XML</nearly>';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];
        $response = new XmlResponse($body, $status, $headers);
        $this->assertSame(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertSame('application/xml; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }
    public function testAllowsStreamsForResponseBody()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $body   = $stream->reveal();
        $response = new XmlResponse($body);
        $this->assertSame($body, $response->getBody());
    }
    public function invalidContent()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['php://temp']],
            'object'     => [(object) ['php://temp']],
        ];
    }
    /**
     * @dataProvider invalidContent
     */
    public function testRaisesExceptionforNonStringNonStreamBodyContent($body)
    {
        $this->expectException(InvalidArgumentException::class);
        new XmlResponse($body);
    }

    public function testConstructorRewindsBodyStream()
    {
        $body = '<?xml version="1.0"?>' . PHP_EOL . '<something>Valid XML</something>';
        $response = new XmlResponse($body);
        $actual = $response->getBody()->getContents();
        $this->assertSame($body, $actual);
    }
}
