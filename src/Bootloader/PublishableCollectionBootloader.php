<?php

namespace Chiron\Bootloader;

use Chiron\Core\Directories;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\PublishableCollection;

final class PublishableCollectionBootloader extends AbstractBootloader
{
    public function boot(PublishableCollection $publishable, Directories $directories): void
    {
        $configPath = __DIR__ . '/../../config';

        // TODO : faire un tableau avec from et to. et boucler dessus pour faire la copie, cela évitera d'avoir x fois l'instruction $publishable->add() !!!!
        // copy the configuration file template from the package "config" folder to the user "config" folder.
        $publishable->add($configPath . '/app.php.dist', $directories->get('@config/app.php'));
    }
}
