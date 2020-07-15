<?php

declare(strict_types=1);

namespace Chiron\Console\Command;

use Chiron\Filesystem\Filesystem;
use Chiron\Console\AbstractCommand;
use Chiron\Console\ExitCode;
use Chiron\Encrypter\Config\EncrypterConfig;
use Chiron\Support\Security;
use Symfony\Component\Console\Input\InputOption;

// TODO : passer les méthodes "perform" en protected pour chaque classe de type "Command"
final class EncryptKeyCommand extends AbstractCommand
{
    protected static $defaultName = 'encrypt:key';

    protected function configure()
    {
        $this
            ->setDescription('Generate new encryption key.')
            ->addOption('mount', 'm', InputOption::VALUE_OPTIONAL, 'Mount encrypter key into given file');
    }

    public function perform(Filesystem $filesystem, EncrypterConfig $config): int
    {
        $key = Security::generateKey();

        $this->sprintf("<info>New encryption key:</info> <fg=cyan>%s</fg=cyan>\n", $key);

        $filepath = $this->option('mount');
        if ($filepath === null) {
            // Only show the generated key, if the optional "mount" file path is not defined.
            return ExitCode::OK;
        }

        if ($filesystem->missing($filepath)) {
            $this->sprintf('<error>Unable to find `%s`</error>', $filepath);

            return ExitCode::NOINPUT;
        }

        $content = $filesystem->read($filepath);
        $content = str_replace($config->getKey(), $key, $content);

        $filesystem->write($filepath, $content);

        $this->writeln('<comment>Encryption key has been updated.</comment>');

        return ExitCode::OK;
    }
}