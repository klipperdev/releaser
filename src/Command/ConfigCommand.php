<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Command;

use Klipper\Tool\Releaser\Config\Config;
use Klipper\Tool\Releaser\Config\JsonConfigSource;
use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Factory;
use Klipper\Tool\Releaser\Json\JsonFile;
use Klipper\Tool\Releaser\Util\GitUtil;
use Klipper\Tool\Releaser\Util\PlatformUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ConfigCommand extends BaseCommand
{
    protected ?Config $config = null;

    protected ?JsonFile $configFile = null;

    protected ?JsonConfigSource $configSource = null;

    protected function configure(): void
    {
        $this
            ->setName('config')
            ->setDescription('Set the config options')
            ->setDefinition([
                new InputOption('global', 'G', InputOption::VALUE_NONE, 'Apply command to the global config file'),
                new InputOption('global-repo', 'g', InputOption::VALUE_NONE, 'Apply command to the global repository config file'),
                new InputOption('editor', 'e', InputOption::VALUE_NONE, 'Open the config with an editor'),
                new InputOption('unset', null, InputOption::VALUE_NONE, 'Unset the given setting-key'),
                new InputOption('list', 'l', InputOption::VALUE_NONE, 'List configuration settings'),
                new InputArgument('setting-key', null, 'Setting key'),
                new InputArgument('setting-value', InputArgument::IS_ARRAY, 'Setting value'),
            ])
            ->setHelp(
                <<<'EOT'
                    This command allows you to edit the Releaser config settings
                    in either the local config file or the global config file
                    or the global config file for each repository.

                    It is recommended to use the global config only for keys to be
                    used by all repositories such as 'home', 'data-dir' or 'binaries'.

                    For other keys, it is recommended to use the global repository config
                    or the local config.

                    Only the 'binaries' key cannot be added in the global repository config,
                    because to load the global repository config, this tool call the GIT binary
                    to retrieve the GIT url so that generate the unique name.

                    To set a config setting:

                        <comment>%command.full_name% home /path/to/the/custom/home/directory</comment>

                    To read a config setting:

                        <comment>%command.full_name% home</comment>
                        Outputs: <info>/home/user/.klipperReleaser</info>

                    To edit the global config.json file:

                        <comment>%command.full_name% --global</comment>

                    To edit the global repository config.json:

                        <comment>%command.full_name% --global-repo</comment>

                    To add a library (lib is a short alias for libraries):

                        <comment>%command.full_name% libraries src/Library1 git@github.com:username/repository-library1.git</comment>

                    To remove a library (lib is a short alias for libraries):

                        <comment>%command.full_name% --unset libraries src/Library1</comment>

                    To add a custom binary (bin is a short alias for binaries):

                        <comment>%command.full_name% binaries --global git /path/to/custom/git/binary</comment>

                    To remove a custom binary (bin is a short alias for binaries):

                        <comment>%command.full_name% --global --unset binaries git</comment>
                    EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $global = $input->getOption('global');
        $globalRepo = $input->getOption('global-repo');

        if ($global && null !== $input->getOption('config')) {
            throw new RuntimeException('The options "--config" and "--global" cannot be combined');
        }

        if ($globalRepo && null !== $input->getOption('config')) {
            throw new RuntimeException('The options "--config" and "--global-repo" cannot be combined');
        }

        if ($global && $globalRepo) {
            throw new RuntimeException('The options "--global" and "--global-repo" cannot be combined');
        }

        $io = $this->getIO();
        $this->config = Factory::createConfig($io);

        if ($global) {
            $configFile = sprintf('%s/config.json', $this->config->get('home'));
        } elseif ($globalRepo) {
            $uniqueKey = GitUtil::getUniqueKey();

            if (null === $uniqueKey) {
                throw new \RuntimeException('The working directory is not managed by GIT');
            }

            $configFile = sprintf('%s/%s.json', $this->config->get('data-dir'), $uniqueKey);
        } else {
            $configFile = $input->getOption('config') ?: Factory::getReleaserFile();
        }

        $this->configFile = new JsonFile($configFile, $io);
        $this->configSource = new JsonConfigSource($this->configFile);
    }

    /**
     * @throws
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->openEditor($input)) {
            return 0;
        }

        if (!$input->getOption('global') && !$input->getOption('global-repo') && $this->configFile->exists()) {
            $this->config->merge($this->configFile->read());
        }

        $settingKey = $input->getArgument('setting-key');

        if (null === $settingKey) {
            return $this->listConfiguration($this->config->raw());
        }

        $io = $this->getIO();
        $unset = $input->getOption('unset');
        $settingKey = $input->getArgument('setting-key');
        $settingValues = $input->getArgument('setting-value');

        if (!$settingKey || !\is_string($settingKey)) {
            return 0;
        }

        // show the value if no value is provided
        if (empty($settingValues) && !$unset) {
            $value = $this->config->get($settingKey);

            if (\is_array($value)) {
                if (empty($value)) {
                    $io->write('<info>[]</info>', true, OutputInterface::VERBOSITY_QUIET);
                }

                foreach ($value as $key => $val) {
                    $io->write('[<comment>'.$key.'</comment>] <info>'.$this->formatValue($val).'</info>', true, OutputInterface::VERBOSITY_QUIET);
                }
            } else {
                $this->getIO()->write('<info>'.$this->formatValue($value).'</info>', true, OutputInterface::VERBOSITY_QUIET);
            }

            return 0;
        }

        // edit the value
        switch ($settingKey) {
            case 'branch':
            case 'branches':
                foreach ($settingValues as $settingValue) {
                    if ($unset) {
                        $this->configSource->removeBranch($settingValue);
                    } else {
                        $this->configSource->addBranch($settingValue);
                    }
                }

                break;

            case 'lib':
            case 'library':
            case 'libraries':
                if ($unset) {
                    $this->configSource->removeLibrary($settingValues[0]);
                } else {
                    if (2 !== \count($settingValues)) {
                        throw new RuntimeException('Invalid value to add library. You must define the library path and the GIT url like "config library <src-path> <git-url>"');
                    }

                    $this->configSource->addLibrary($settingValues[0], $settingValues[1]);
                }

                break;

            case 'bin':
            case 'binary':
            case 'binaries':
                if ($unset) {
                    $this->configSource->removeBinary($settingValues[0]);
                } else {
                    if (2 !== \count($settingValues)) {
                        throw new RuntimeException('Invalid value to add custom binary. You must define the default binary name and the custom binary path like "config binary git <git-bin-custom-path>"');
                    }

                    $this->configSource->addBinary($settingValues[0], $settingValues[1]);
                }

                break;

            default:
                if (!\array_key_exists($settingKey, Config::$defaultConfig)) {
                    throw new RuntimeException(sprintf('The "%s" setting key does not exist', $settingKey));
                }

                if ($unset) {
                    $this->configSource->unset($settingKey);
                } else {
                    $this->configSource->set($settingKey, $this->getValue($settingValues[0]));
                }

                break;
        }

        return 0;
    }

    /**
     * @throws
     */
    private function openEditor(InputInterface $input): bool
    {
        if ($input->getOption('editor')) {
            $editor = escapeshellcmd(getenv('EDITOR'));

            if (!$editor) {
                if (PlatformUtil::isWindows()) {
                    $editor = 'notepad';
                } else {
                    foreach (['editor', 'vim', 'vi', 'nano', 'pico', 'ed'] as $candidate) {
                        if (exec('which '.$candidate)) {
                            $editor = $candidate;

                            break;
                        }
                    }
                }
            }

            $file = $this->configFile->getPath();

            if (!$this->configFile->exists()) {
                $this->configFile->write([]);
            }

            system($editor.' '.$file.(PlatformUtil::isWindows() ? '' : ' > `tty`'));

            return true;
        }

        return false;
    }

    /**
     * @throws
     */
    private function listConfiguration(array $contents, ?string $k = null): int
    {
        $io = $this->getIO();
        $keys = array_keys($contents);

        if (empty($keys) && null !== $k) {
            $io->write('[<comment>'.implode('</comment>].[<comment>', explode('.', rtrim($k, '.'))).'</comment>] <info>[]</info>', true, OutputInterface::VERBOSITY_QUIET);
        }

        foreach ($keys as $key) {
            $value = $this->config->get($k.$key);

            if (\is_array($value)) {
                $this->listConfiguration($value, $key.'.');
            } else {
                $p = null !== $k ? implode('</comment>].[<comment>', explode('.', $k)) : null;
                $io->write('[<comment>'.$p.$key.'</comment>] <info>'.$this->formatValue($value).'</info>', true, OutputInterface::VERBOSITY_QUIET);
            }
        }

        return 0;
    }

    /**
     * @throws
     */
    private function formatValue(mixed $value): string
    {
        if (\is_bool($value)) {
            $value = var_export($value, true);
        } elseif (\is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR, 512);
        }

        return (string) $value;
    }

    private function getValue($value)
    {
        if (\in_array($value, ['true', 'false', '1', '0'], true)) {
            $value = 'false' !== $value && (bool) $value;
        }

        return $value;
    }
}
