<?php

declare(strict_types=1);

namespace Chiron\ErrorHandler;

use Chiron\Console\Console;
use Chiron\Container\Container;
use Chiron\ErrorHandler\Exception\FatalErrorException;
use Chiron\ErrorHandler\Exception\FatalErrorException2;
use ErrorException;
use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;


//https://github.com/symfony/symfony/blob/c09128cf9f715a2b04e2b1132ee66a7303c18868/src/Symfony/Component/ErrorHandler/Debug.php
//https://github.com/symfony/debug/blob/e3cb605c6d6a6c5757ac2515f560a53b6a8811e7/Debug.php


//https://github.com/yiisoft/yii2-framework/blob/a741165ee91603518286fc28d4ee273ae3a0ef60/console/ErrorHandler.php
//https://github.com/yiisoft/yii2-framework/blob/0c1efae085dbf4f92db3d82bb530ad14cbc5fe83/web/ErrorHandler.php

//https://github.com/hunzhiwange/framework/blob/50c12e1842db49f111dded3ca139386d1639dcfd/src/Leevel/Kernel/ExceptionRuntime.php

//https://github.com/cakephp/cakephp/blob/master/src/Error/BaseErrorHandler.php#L89
//https://github.com/cakephp/cakephp/blob/master/src/Error/ConsoleErrorHandler.php
//https://github.com/cakephp/cakephp/blob/master/src/Error/ErrorHandler.php#L205
//https://github.com/cakephp/cakephp/blob/master/src/Error/Middleware/ErrorHandlerMiddleware.php

//https://github.com/laravel/lumen-framework/blob/38f42c1399650b6c2286de7e9831e7174cfd14e8/src/Concerns/RegistersExceptionHandlers.php
//https://github.com/laravel/lumen-framework/blob/38f42c1399650b6c2286de7e9831e7174cfd14e8/src/Application.php#L103

// TODO : regarder ici comment tester la méthode shutdownHandler !!!!     https://github.com/nette/tracy/blob/02b60e183ad82c26ad8415547ab393941bef7e94/tests/Tracy/Debugger.E_COMPILE_ERROR.console.phpt

//https://github.com/getsentry/sentry-php/blob/master/src/ErrorHandler.php
//https://github.com/yiisoft/yii2/blob/1a8c83ba438f92075fc6e4ab9124b6ae59fdda8f/framework/web/ErrorHandler.php

//https://github.com/yiisoft/yii-web/blob/master/src/ErrorHandler/ErrorHandler.php

//https://github.com/symfony/error-handler/blob/948260b0c4c846138b6b0ad9423291acbbf08fa8/ErrorHandler.php#L713
//https://github.com/symfony/error-handler/blob/948260b0c4c846138b6b0ad9423291acbbf08fa8/ErrorRenderer/HtmlErrorRenderer.php#L77

//https://github.com/laravel/framework/blob/26e5af1baa32adb1c418660e054445a98aeebf7f/src/Illuminate/Foundation/Exceptions/Handler.php#L389

//https://github.com/laravel/lumen-framework/blob/2174737117877c5db8b01c996cad5c0dae3aabed/src/Exceptions/Handler.php#L96

//https://github.com/laravel/framework/blob/0b12ef19623c40e22eff91a4b48cb13b3b415b25/src/Illuminate/Foundation/Bootstrap/HandleExceptions.php#L35


// TODO : classe à renommer en ErrorHandler !!!!
// TODO : il faudrait trouver un mécanisme pour éviter que l'utilisateur appel plusieurs fois la méthode ::register eventuellement mettre un booleen de classe "isRegistered" qui est à vrai aprés le premier appel et on sortira de la méthode register() si on l'a déjà appellé une premiére fois.
// Exemple => https://github.com/getsentry/sentry-php/blob/master/src/ErrorHandler.php#L72
// Exemple => https://github.com/symfony/phpunit-bridge/blob/master/DeprecationErrorHandler.php#L66
// Exemple => https://github.com/zendframework/zend-log/blob/328de94cb3395382d077dbc09200b733e9596a06/src/Logger.php#L661
final class RegisterErrorHandler
{

    /**
     * Set the level to show all errors + disable internal php error display and register the error/exception/shutdown handlers.
     */
    public static function enable(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'Off');
        self::register();
    }

    /**
     * Register this error handler.
     */
    // TODO : améliorer le disable errors :   https://github.com/nette/tracy/blob/5e900c8c9aee84b3dbe6b5f2650ade578cc2dcfa/src/Tracy/Debugger/Debugger.php#L181
    // https://github.com/nette/tester/blob/bb813b55a9c358ead2897e37d90e29da1644ce41/src/Framework/Environment.php#L100
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     * @since 2.0.32 this will not do anything if the error handler was not registered
     */
    //https://github.com/yiisoft/yii2-framework/blob/master/base/ErrorHandler.php#L85
    /*
    public function unregister(): void
    {
        if ($this->_registered) {
            restore_error_handler();
            restore_exception_handler();
            $this->_registered = false;
        }
    }*/

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    //https://github.com/yiisoft/yii-web/blob/master/src/ErrorHandler/ErrorHandler.php#L114
    /*
    public function unregister(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }*/

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @throws \ErrorException
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * @param Throwable $e
     */
    public static function handleException(Throwable $e): void
    {
        if (php_sapi_name() === 'cli') {
            self::renderForConsole($e);
        } else {
            self::renderHttpResponse($e);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param Throwable $e
     */
    //https://github.com/cakephp/cakephp/blob/master/src/Error/ConsoleErrorHandler.php#L75
    //https://github.com/nette/tester/blob/bb813b55a9c358ead2897e37d90e29da1644ce41/src/Framework/Dumper.php#L252
    //https://github.com/php-toolkit/cli-utils/blob/master/src/App.php#L243
    //https://github.com/spiral/boot/blob/b76445923be4959068be2e77d72c511709c25f99/src/ExceptionHandler.php#L95
    //https://github.com/yiisoft/yii2-framework/blob/a741165ee91603518286fc28d4ee273ae3a0ef60/console/ErrorHandler.php
    //https://github.com/spiral/exceptions/blob/master/src/ConsoleHandler.php#L68
    private static function renderForConsole(Throwable $e): void
    {
        $message = sprintf(
            "<error>%s</error> %s in %s on line %d\n<comment>Stack trace:</comment>\n%s\n",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        // TODO : faire un fwrite(STDERR, $message) ?
        $stderr = new StreamOutput(fopen('php://stderr', 'w'));
        $stderr->write($message);

        exit(1);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param Throwable $e
     */
    // TODO : code à améliorer !!!!!!
    // TODO : regarder ici comment c'est fait (initialiser un SapiEmitter::class) :    https://github.com/cakephp/cakephp/blob/master/src/Error/ErrorHandler.php#L205
    private static function renderHttpResponse(Throwable $e): void
    {
        // TODO : externaliser la création du content dans une méthode séparée du style '$this->handleCaughtThrowable($throwable): string' qui retourne le texte à la méthode echo. Elle pourrait être aussi utilisée dans le middleware de ErroHandlerMiddleware pour créer le contenu de la réponse !!!!
        $content = '';

        try {
            //$this->log($t);
            //return $this->exposeDetails ? $renderer->renderVerbose($t) : $renderer->render($t);

            $formatter = new \Chiron\ErrorHandler\Formatter\WhoopsFormatter();
            $content = $formatter->format2($e);
        } catch (Throwable $t) {
            $content = nl2br($t->getMessage());
        }


/*
        $message = sprintf(
            "<br/><strong>%s</strong> %s in %s on line %d<br/><i>Stack trace:</i><pre>%s</pre>",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        $content = $message;
*/




        // TODO : à virer c'est un test !!!
        //$content = nl2br($e->getTraceAsString());

        // TODO : il faudrait surement envoyer un header contenttype = "html" non ????
        // TODO : il faudrait pas vérifier que les headers ne sont pas déjà envoyés pour faire ce hhttp_response_code() ????
        // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
        http_response_code(500);

        echo $content;

        //exit(1);
    }

    /**
     * Handle php shutdown and search for fatal errors.
     *
     *
     * @throws FatalErrorException
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && self::isFatalError($error['type'])) {
            $exception = new FatalErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            static::handleException($exception);
        }
    }

    /**
     * Determine if the error type is fatal (halts execution).
     *
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     *
     * @param int $type
     *
     * @return bool
     */
    private static function isFatalError(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
    }
}
