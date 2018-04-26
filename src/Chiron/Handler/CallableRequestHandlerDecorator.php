<?php
/**
 * @see       https://github.com/zendframework/zend-stratigility for the canonical source repository
 *
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Chiron\Stack\Utils;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

//use Zend\Stratigility\Exception;

/**
 * Decorate callable middleware as PSR-15 middleware.
 *
 * Decorates middleware with the following signature:
 *
 * <code>
 * function (
 *     ServerRequestInterface $request,
 *     RequestHandlerInterface $handler
 * ) : ResponseInterface
 * </code>
 *
 * such that it will operate as PSR-15 middleware.
 *
 * Neither the arguments nor the return value need be typehinted; however, if
 * the signature is incompatible, a PHP Error will likely be thrown.
 */
final class CallableRequestHandlerDecorator implements RequestHandlerInterface
{
    /**
     * @var callable
     */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\MissingResponseException if the decorated middleware
     *                                            fails to produce a response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        //return ($this->middleware)($request, $handler);
        return call_user_func($this->handler, $request);
    }
}