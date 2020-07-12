<?php

declare(strict_types=1);

namespace Chiron\Console\Command;

use Chiron\Console\AbstractCommand;
use Chiron\PackageManifest;
use Chiron\Console\ExitCode;

//https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/PackageDiscoverCommand.php

class PackageDiscoverCommand extends AbstractCommand
{
    protected static $defaultName = 'package:discover';

    protected function configure()
    {
        $this->setDescription('Rebuild the cached package manifest.');
    }

    public function perform(PackageManifest $manifest): int
    {
        $manifest->discover();

        // TODO : si on est en mode verbose on pourrait afficher plus d'infos du manifest, au lieu de juste afficher le package on pourrait afficher le détail (cad un bootloader, un provider, etc...)
        // TODO : utiliser un iterator dans la classe PackageManifest ????
        /*
        foreach ($manifest->getPackages() as $package) {
            $this->line("Discovered Package: <info>{$package}</info>");
        }*/

        foreach ($manifest->getManifest() as $package => $extra) {
            $this->line("Discovered Package: <info>{$package}</info>");

            if ($this->isVerbose()) {
                $this->listing($extra['bootloaders'] ?? []);
                $this->listing($extra['providers'] ?? []);

                //$this->listing2($extra['bootloaders'] ?? [], 'fg=yellow');
                //$this->listing2($extra['providers'] ?? [], 'fg=yellow');
            }
        }

        $this->info('Package manifest generated successfully.');

        return ExitCode::OK;
    }
}
