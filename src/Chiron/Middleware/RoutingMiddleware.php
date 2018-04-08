<?php
declare(strict_types = 1);

namespace Chiron\Middleware;

// TODO : example : https://github.com/zendframework/zend-expressive-router/blob/master/src/Middleware/RouteMiddleware.php
// TODO : regarder ici https://github.com/zrecore/Spark/blob/master/src/Handler/RouteHandler.php    et https://github.com/equip/framework/blob/master/src/Handler/DispatchHandler.php


//namespace Middlewares;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Chiron\Routing\Router;
use Chiron\Routing\RouteResult;

use Chiron\Exception\MethodNotAllowedHttpException;
use Chiron\Exception\NotFoundHttpException;

class RoutingMiddleware implements MiddlewareInterface
{
    // TODO : ajouter une phpdoc avec le type de cette variable (\RouterInterface)
    private $router;

    // TODO : ajouter un RouterInterface comme type hinting pour la paramétre $router
    // TODO : en fait on lui passe en paramétre toute l'application $app , il faudrait surement cloisonner la partie router et l'application.
    // TODO : lui passer le reouter en paramétre. !!!!!
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // TODO : il faudrait peut etre récupérer la réponse via un $handle->handle() pour récupérer les headers de la réponse + le charset et version 1.1/1.0 pour le passer dans les exceptions (notfound+methodnotallowed) car on va recréer une nouvelle response !!!! donc si ca se trouve les headers custom genre X-Powered ou CORS vont être perdus lorsqu'on va afficher les message custom pour l'exception 404 par exemple !!!!
        
        $result = $this->router->match($request);

        // add some usefull information about the url used for the routing
        // TODO : faire plutot porter ces informations (method et uri utilisé) directement dans l'objet RouteResult !!!!!!
        //$request = $request->withAttribute('routeInfo', [$request->getMethod(), (string) $request->getUri()]);

        // Inject the actual route result
        $request = $request->withAttribute(RouteResult::class, $result);

        //if ($result->isSuccess()) {
            // Inject individual matched parameters.
            foreach ($result->getMatchedParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }
        //} elseif ($result->isMethodFailure()) {
        if ($result->isMethodFailure()) {
            // TODO : utiliser un middleware pour gérer ce cas là : https://github.com/zendframework/zend-expressive-router/blob/master/src/Middleware/MethodNotAllowedMiddleware.php
            throw new MethodNotAllowedHttpException($result->getAllowedMethods());
        } elseif ($result->isFailure()) {
            throw new NotFoundHttpException();
        }

        return $handler->handle($request);
    }
}