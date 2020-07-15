<?php

namespace Chiron\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Application;
use Chiron\Container\FactoryInterface;
use Chiron\PackageManifest;

final class PackageManifestBootloader extends AbstractBootloader
{
    /**
     * Execute the providers & bootloaders classes found in the composer packages manifest.
     *
     * @param PackageManifest $manifest
     * @param Application $application
     * @param FactoryInterface $factory
     */
    public function boot(PackageManifest $manifest, Application $application, FactoryInterface $factory): void
    {
        foreach ($manifest->getProviders() as $provider) {
            $application->addProvider($factory->build($provider));
        }

        foreach ($manifest->getBootloaders() as $bootloader) {
            $application->addBootloader($factory->build($bootloader));
        }
    }
}