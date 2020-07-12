<?php

declare(strict_types=1);

namespace Chiron;

use Chiron\Bootload\BootloaderInterface;
use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Dispatcher\DispatcherInterface;
use Chiron\ErrorHandler\RegisterErrorHandler;
use Chiron\Exception\ApplicationException;
use Chiron\Container\Container;

use Chiron\Bootloader\CoreBootloader;
use Chiron\Bootloader\EnvironmentBootloader;
use Chiron\Bootloader\ConfigureBootloader;
use Chiron\Bootloader\DirectoriesBootloader;
use Chiron\Bootloader\PackageManifestBootloader;
use Chiron\Bootloader\MutationsBootloader;
use Chiron\Bootloader\PublishableCollectionBootloader;
use Chiron\Config\InjectableConfigInterface;
use Chiron\Config\InjectableConfigMutation;
use Chiron\Provider\ConfigureServiceProvider;
use Chiron\Provider\ErrorHandlerServiceProvider;
use Chiron\Provider\HttpFactoriesServiceProvider;
use Chiron\Provider\LoggerServiceProvider;
use Chiron\Provider\MiddlewaresServiceProvider;
use Chiron\Provider\RoadRunnerServiceProvider;
use Chiron\Provider\ServerRequestCreatorServiceProvider;

//https://github.com/swoft-cloud/swoft-framework/blob/0702d93baf8ee92bc4d1651fe0cda2a022197e98/src/SwoftApplication.php

//https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpKernel/Kernel.php
//https://github.com/drupal/core/blob/4576cfa33ea2d49e6b956795d474ee89972b1d59/lib/Drupal/Component/DependencyInjection/Container.php

// RESET SERVICE : http://apigen.juzna.cz/doc/redaxo/redaxo/class-Symfony.Contracts.Service.ResetInterface.html

/**
 * This constant defines the framework installation directory.
 */
//defined('CHIRON_PATH') or define('CHIRON_PATH', __DIR__);

/**
 * The application framework core.
 */
// TODO : il faudrait pas une méthode setDispatchers($array) / getDispatchers(): array ????
// TODO : ajouter des méthodes set/getName() pour le nom de l'application idem pour get/setVersion()
// TODO : créer une classe d'exception dédiée à l'pplication ???? ApplicationException qui étendrait de RuntimeException. Elle serai levée si le rootPath est manquant ou si aucun dispatcher n'est trouvé.
// TODO : passer la classe en "final" ??? ou alors permettre de faire un extends de cette classe ???? bien réfléchir !!!!

// TODO : ajouter une méthode pour trouver les commandes ajoutées à l'application, un "findCommand($name)".
// Exemple : https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Console/Application.php#L112
// Exemple : https://github.com/symfony/console/blob/master/Application.php#L595



class Application
{
    /**
     * Indicates if the botloaders stack has been "booted".
     *
     * @var bool
     */
    // TODO : renommer cette variable en $booted
    private $isBooted = false;

    /** @var Container */
    private $container;

    /** @var BootloaderInterface[] */
    private $bootloaders = [];

    /** @var DispatcherInterface[] */
    private $dispatchers = [];

    /**
     * Private constructor. Use the method 'create()' or 'init()' to construct the application.
     *
     * @param Container $container
     */
    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    // TODO : il faudrait pas initialiser le container avant de le retourner ??? ou alors cela risque de poser problémes ?????
    // TODO : méthode pas vraiment utile à la limite retourner un ContainerInterface plutot qu'un container, et donc éviter que l'utilisateur ne puisse accéder aux fonction bind/singleton/inflect...etc de l'object Container. L'utilisateur aura seulement accés aux méthodes get/has de base.
    // TODO : renommer cette méthode en "container()" ? ca serai plus simple pour chainer les instructions : $app->container()->make(xxxx);
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Add new dispatcher. This method must only be called before method `start` will be invoked.
     *
     * @param DispatcherInterface $dispatcher
     */
    // TODO : il faudrait gérer le cas ou l'on souhaite ajouter un dispatcher au dessus de la stack. Ajouter un paramétre 'bool $onTop = false' à cette méthode ????
    // TODO : permettre de gérer les dispatchers dans les fichiers composer.json (partie "extra") et les charger via le packagemanifest ????
    public function addDispatcher(DispatcherInterface $dispatcher): void
    {
        $this->dispatchers[] = $dispatcher;
    }

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProviderInterface $provider
     */
    // TODO : améliorer le code : https://github.com/laravel/framework/blob/5.8/src/Illuminate/Foundation/Application.php#L594
    //public function register($provider)
    public function addProvider(ServiceProviderInterface $provider): void
    {
        $provider->register($this->container);
    }

    public function addBootloader(BootloaderInterface $bootloader): void
    {
        // if you add a bootloader after the application run(), we execute the bootloader, else we add it to the stack for an execution later.
        if ($this->isBooted) {
            $bootloader->bootload($this->container);
        } else {
            $this->bootloaders[] = $bootloader;
        }
    }

    /**
     * Start application and process user requests using selected dispatcher or throw an exception.
     *
     * @throws RuntimeException
     *
     * @return mixed Could be an 'int' for command-line dispatcher or 'void' for web dispatcher.
     */
    // TODO : il faudrait pas faire une vérification sur un booléen type isRunning pour éviter d'appeller plusieurs fois cette méthode (notamment depuis un Bootloader qui récupére l'application et qui essayerai d'appeller cette méthode run() !!!!)
    public function run()
    {
        $this->boot();

        // TODO : mettre ce code dans une méthode private "dispatch()" ????
        foreach ($this->dispatchers as $dispatcher) {
            if ($dispatcher->canDispatch()) {
                return $dispatcher->dispatch();
            }
        }

        // TODO : configurer le message dans le cas ou le tableau de dispatcher est vide c'est que l'application n'a pas été correctement initialisée ????
        // TODO : créer une exception DispatcherNotFoundException qui héritera de ApplicationException.
        throw new ApplicationException('Unable to locate active dispatcher.');
    }

    /**
     * Boot the application's service bootloaders.
     */
    // TODO : gérer le cas ou l'utilisateur appel dans cette ordre les méthodes => create() / boot() / init() dans ce cas dans la méthode init() il faudra lever une ApplicationException si le booléen $isBooted est à true. Réfléchir aussi au cas ou  l'utilisateur fait un init()/boot()/create c'est la même problématique.
    // TODO : pourquoi ne pas mettre cette méthode en private ????
    public function boot(): void
    {
        if (! $this->isBooted) {
            $this->isBooted = true;

            foreach ($this->bootloaders as $bootloader) {
                $bootloader->bootload($this->container);
            }
        }
    }

    // TODO : il faudrait surement récupérer l'instance déjà créée précédemment pour limiter la création de nouvelles instance à chaque appel de cette méthode. Eventuellement passer un booleen "$forceNew = false" en paramétre pour soit créer une nouvelle instance soit recuperer l'ancienne instance (via une propriété de classe public et statique $instance). Attention si on récupére l'instance il faudra faire un reset sur la valeur du boolen $this->isBooted car si l'utilisateur a fait un appel dans cette ordre : create/boot/init on va avoir un probléme lorsqu'on va vouloir faire le run()
    // TODO : à renommer en getInstance() ????
    public static function create(): self
    {
        $container = new Container();
        $container->setAsGlobal();

        $app = new self($container);
        $container->singleton(self::class, $app);

        return $app;
    }

    // TODO : ajouter un paramétre boolen $debug pour savoir si on active ou non le error handler->register()
    // TODO : tester le cas ou on appel plusieurs fois cette méthode. Il faudra surement éviter de réinsérer plusieurs fois les bootloaders et autres service provider
    // TODO : passer en paramétre un tableau de "environment" values qui permettra d'initialiser le bootloader DotEnvBootloader::class
    public static function init(array $paths, array $values = [], bool $handleErrors = true): self
    {
        // TODO : attention il faudrait pouvoir faire un register une seule fois pour les error handlers !!!!
        // used to handle errors in the bootloaders processing.
        if ($handleErrors) {
            RegisterErrorHandler::enable();
        }

        // TODO : il faudrait gérer le cas ou l'application est déjà "create" et qu'on récupére cette instance plutot que de rappeller la méthode. (c'est dans le cas ou l'utilisateur fait un App::create qu'il ajoute des providers ou autre et qu'ensuite il fasse un App::init pour finaliser l'initialisation !!!) Je suppose qu'il faudra garder un constante public de classe static avec l'instance (comme pour Container::$instance). Cela permettra aussi de créer une fonction globale "app()" qui retournera l'instance de la classe Application::class. Cela permettra en plus de la facade Facade\Application de passer par cette méthode pour injecter des bootloader par exemple.
        $app = self::create();

        $app->addBootloader(new DirectoriesBootloader($paths));
        $app->addBootloader(new EnvironmentBootloader($values));

        self::configure($app);

        return $app;
    }

    // TODO : il y a surement des services à ne pas charger si on est en mode console !!! et inversement il y en a surement à charger uniquement en mode console !!!
    private static function configure(Application $app): void
    {
        // TODO : changer le nom de la classe MutationsBootloader en CoreMutationsBootloader car sinon le nom est trop générique !!!!
        $app->addBootloader(new MutationsBootloader());
        $app->addBootloader(new ConfigureBootloader());
        $app->addBootloader(new CoreBootloader());
        // TODO : ajouter un appel à ApplicationBootLoader() ici juste aprés le CoreBootLoader ????
    }
}
