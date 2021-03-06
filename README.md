[![Build Status](https://travis-ci.org/ncou/Chiron.svg?branch=master)](https://travis-ci.org/ncou/Chiron)
[![Coverage Status](https://coveralls.io/repos/github/ncou/Chiron/badge.svg?branch=master)](https://coveralls.io/github/ncou/Chiron?branch=master)
[![CodeCov](https://codecov.io/gh/ncou/Chiron/branch/master/graph/badge.svg)](https://codecov.io/gh/ncou/Chiron)

[![Latest Stable Version](https://poser.pugx.org/chiron/chiron/v/stable.png)](https://packagist.org/packages/chiron/chiron)
[![Total Downloads](https://img.shields.io/packagist/dt/chiron/chiron.svg?style=flat-square)](https://packagist.org/packages/chiron/chiron/stats)
[![Monthly Downloads](https://img.shields.io/packagist/dm/chiron/chiron.svg?style=flat-square)](https://packagist.org/packages/chiron/chiron/stats)

[![StyleCI](https://styleci.io/repos/125737330/shield?style=flat)](https://styleci.io/repos/125737330)
[![PHP-Eye](https://php-eye.com/badge/chiron/chiron/tested.svg?style=flat)](https://php-eye.com/package/chiron/chiron)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)





Micro-Framework
---------------

Chiron is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs.
All that is needed to get access to the Framework is to include the autoloader.

    <?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    
    require_once __DIR__.'/../vendor/autoload.php';
    
    $app = new Chiron\Application();
    $app->get('/hello/[:name]', function (Request $request, Response $response, string $name) {
        $response->getBody()->write('Hello ' . $name);
        return $response;
    });
    $app->run();

Next we define a route to /hello/[:name] that matches for GET http requests. When the route matches, the function is executed and the return value is sent back to the client as an http response.

Installation
------------

If you want to get started fast, use the Chiron Skeleton as a base by running this bash command :

    $ composer create-project chiron/chiron-skeleton [my-app-name]

Replace [my-app-name] with the desired directory name for your new application.

You can then run it with PHP's built-in webserver:

    $ cd [my-app-name]; php -S localhost:8080 -t public public/index.php

>Or using the Composer shortcut :
>
>$ composer start

If you want more flexibility, and install only the framework, use this Composer command instead:

    $ composer require chiron/chiron

## Description

## About Chiron

## Features
- PSR-{2,3,4,6,7,11,12,15,16,17} compliant
- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).

## Motivation
Chiron was built with the purpose of understanding how major PHP frameworks operate under the hood. Most frameworks like Laravel implement techniques that can seem like "magic" unless you actually implement them yourself, an example being utilizing reflection API to plug in dependencies. Chiron has helped me so much with familarizing myself with quite a few advanced concepts in the PHP & OOP world.

## Skeletons
| App Type | Current Status | Install       
| ---       | --- | ---
chiron/app | [![Latest Stable Version](https://poser.pugx.org/chiron/app/version)](https://packagist.org/packages/chiron/app) | https://github.com/chironphp/app
chiron/app-cli | [![Latest Stable Version](https://poser.pugx.org/chiron/app-cli/version)](https://packagist.org/packages/chiron/app-cli) | https://github.com/chironphp/app-cli

License:
--------
MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
