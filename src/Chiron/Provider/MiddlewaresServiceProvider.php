<?php

/**
 * Chiron (http://www.chironframework.com).
 *
 * @see      https://github.com/ncou/Chiron
 *
 * @license   https://github.com/ncou/Chiron/blob/master/licenses/LICENSE.md (MIT License)
 */

//https://github.com/userfrosting/UserFrosting/blob/master/app/system/ServicesProvider.php
//https://github.com/slimphp/Slim/blob/3.x/Slim/DefaultServicesProvider.php
declare(strict_types=1);

namespace Chiron\Provider;

use Chiron\Http\Middleware\BodyParserMiddleware;
use Chiron\Http\Middleware\CharsetByDefaultMiddleware;
use Chiron\Http\Middleware\CheckMaintenanceMiddleware;
use Chiron\Http\Middleware\ContentLengthMiddleware;
use Chiron\Http\Middleware\ContentTypeByDefaultMiddleware;
use Chiron\Http\Middleware\DispatcherMiddleware;
use Chiron\Http\Middleware\EmitterMiddleware;
use Chiron\Http\Middleware\MethodOverrideMiddleware;
use Chiron\Http\Middleware\OriginalRequestMiddleware;
use Chiron\Http\Middleware\RoutingMiddleware;
use Chiron\KernelInterface;
use Chiron\Pipe\PipelineBuilder;
use Chiron\Routing\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Chiron system services provider.
 *
 * Registers system services for Chiron, such as config manager, middleware router and dispatcher...
 */
class MiddlewaresServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Chiron system services.
     *
     * @param ContainerInterface $container A DI container implementing ArrayAccess and container-interop.
     */
    public function register(KernelInterface $kernel): void
    {
        $kernel->add(RoutingMiddleware::class, function () use ($kernel) {
            return new RoutingMiddleware($kernel[RouterInterface::class], $kernel[ResponseFactoryInterface::class], $kernel[StreamFactoryInterface::class]);
        });

        $kernel->add(DispatcherMiddleware::class, function () use ($kernel) {
            return new DispatcherMiddleware(new PipelineBuilder($kernel));
        });

        $kernel->add(ContentTypeByDefaultMiddleware::class, function () {
            return new ContentTypeByDefaultMiddleware();
        });
        /*
        $kernel->add(OriginalRequestMiddleware::class, function ($c) {
            return new OriginalRequestMiddleware();
        });*/

        $kernel->add(CharsetByDefaultMiddleware::class, function () {
            return new CharsetByDefaultMiddleware();
        });
        /*
        $kernel->add(BodyParserMiddleware::class, function ($c) {
            return new BodyParserMiddleware();
        });*/

        /*
                $kernel[EmitterMiddleware::class] = function ($c) {
                    return new EmitterMiddleware();
                };
        */

        $kernel->add(CheckMaintenanceMiddleware::class, function () {
            return new CheckMaintenanceMiddleware();
        });

        $kernel->add(MethodOverrideMiddleware::class, function () {
            return new MethodOverrideMiddleware();
        });

        $kernel->add(ContentLengthMiddleware::class, function () {
            return new ContentLengthMiddleware();
        });

        /*
           $kernel['controllerResolver'] = function ($container) {
               return new ControllerResolver($container);
           };
        */

        //$this->factory = new MiddlewareFactory($this->container);

        //$this->loadConfig($config_path_or_file_or_array, $config_cache_file);

        // TODO : déplacer ces initialisations dans le constructeur d'une classe CONTAINER externalisée

        // TODO : ajouter l'initialisation d'un logger ?????

        // TODO : vérifier l'utilité de mettre cela dans un container, normalement on va toujours passer par le router, donc le mettre dans un container n'est pas vraiment nécessaire, surtout que dans les controller on ne va pas réutiliser le router, car la méthode redirect ou getPathFor se trouve directement dans $app et pas dans la classe Router.
        // register the router in the pimple container

        /*
            $this['session'] = function ($c) {
                // TODO : déplacer la classe session dans le répertoire "components"
                return new Session();
            };
        */

        /*
            $this['router'] = function ($c) {
                return new Router($c->get('basePath'), $this->container);
            };
        */

        // Create request class closure.
        /*
            $this['request'] = function ($c) {
                return Request::fromGlobals();
            };
        */

        // -----------------------------------------------------------------------------
        // Service providers
        // -----------------------------------------------------------------------------
        // Twig
        // TODO : s'inspirer de ce bout de code pour passer des variables global directement à phpRenderer
        /*
        $view = new \Slim\Views\Twig(
            $app->settings['view']['template_path'],
            $app->settings['view']['twig']
        );
        $view->addExtension(new Twig_Extension_Debug());
        $view->addExtension(new \Slim\Views\TwigExtension($app->router, $app->request->getUri()));
        */
        /* @var \Twig_Environment $env */
        /*
        $env = $view->getEnvironment();
        foreach ($app->settings['view']['globals'] as $global => $value) {
            $env->addGlobal($global, $value);
        }
        $container->register($view);*/

        // LOAD Referral Spammer List
        //$spammerList = config('app.referral_spam_list_location', base_path('vendor/matomo/referrer-spam-blacklist/spammers.txt'));
    }
}
