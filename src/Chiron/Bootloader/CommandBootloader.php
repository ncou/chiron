<?php

declare(strict_types=1);

namespace Chiron\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Console\Config\ConsoleConfig;
use Chiron\Console\Console;

use Chiron\Container\Container;
use Chiron\Console\CommandLoader\CommandLoader;


use Chiron\Console\Command\Hello;
use Chiron\Console\Command\VersionCommand;
use Chiron\Console\Command\AboutCommand;
use Chiron\Console\Command\RuntimeDirCommand;
use Chiron\Console\Command\PackageDiscoverCommand;
use Chiron\Console\Command\EncryptKeyCommand;
use Chiron\Console\Command\PublishCommand;
use Chiron\Console\Command\RouteListCommand;
use Chiron\Console\Command\ServeCommand;
use Chiron\Console\Command\TwigClearCommand;
use Chiron\Console\Command\TwigCompileCommand;
use Chiron\Console\Command\CacheClearCommand;


final class CommandBootloader extends AbstractBootloader
{
    public function boot(Console $console): void
    {


        // TODO : attention si il y a des bootloaders chargés via le packagemanifest qui ajoutent une commande dans la console, si cette commande utilise le même nom que les commandes par défaut  définies ci dessous, elles vont être écrasées !!!! faut il faire un test dans cette classe si la command est déjà définie dans la console on ne l'ajoute pas (éventuellement on léve une ApplicationException si la commande est déjà définie en indiquant qu'on ne peux pas l'écraser !!!!) ????? EXEMPLE ci dessous :
/*
        if (! $console->has(xxxxx::getDefaultName())) {
            $console->addCommand(xxxxx::getDefaultName(), xxxxx::class);
        }

        OU

        if ($console->has(xxxxx::getDefaultName())) {
            Throw new ApplicationException('Internal command "XXXX" can't be overriden');
        }
*/


        // TODO : code à améliorer !!!!
        // TODO : charger certaines commandes que dans le cas ou on est en mode http ou en mode console !!!! (exemple pour la commande Serve et RouteList qui ne servent pas en mode application 100% console) !!!!
        //$console->addCommand(Hello::getDefaultName(), Hello::class);
        //$console->addCommand(VersionCommand::getDefaultName(), VersionCommand::class);
        //$console->addCommand(RuntimeDirCommand::getDefaultName(), RuntimeDirCommand::class);

        // TODO : à déplacer dans un package d'encodage dédié ?
        $console->addCommand(EncryptKeyCommand::getDefaultName(), EncryptKeyCommand::class);

        $console->addCommand(AboutCommand::getDefaultName(), AboutCommand::class);
        $console->addCommand(PackageDiscoverCommand::getDefaultName(), PackageDiscoverCommand::class);
        $console->addCommand(PublishCommand::getDefaultName(), PublishCommand::class);

        // TODO : charger ces commandes uniquement si il y a un RouterInterface de présent (cad un class_exist === true) ???? ou alors tester avec un $container->has('RouterInterface')
        $console->addCommand(RouteListCommand::getDefaultName(), RouteListCommand::class);
        $console->addCommand(ServeCommand::getDefaultName(), ServeCommand::class);

        $console->addCommand(CacheClearCommand::getDefaultName(), CacheClearCommand::class);

        $console->addCommand(TwigClearCommand::getDefaultName(), TwigClearCommand::class);
        $console->addCommand(TwigCompileCommand::getDefaultName(), TwigCompileCommand::class);
    }
}

