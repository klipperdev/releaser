<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser;

use Klipper\Tool\Releaser\Config\Config;
use Klipper\Tool\Releaser\Config\JsonConfigSource;
use Klipper\Tool\Releaser\Exception\JsonException;
use Klipper\Tool\Releaser\Exception\JsonValidationException;
use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\IO\IOInterface;
use Klipper\Tool\Releaser\Json\JsonFile;
use Klipper\Tool\Releaser\Splitter\Splitter;
use Klipper\Tool\Releaser\Util\GitUtil;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Factory
{
    /**
     * @throws RuntimeException
     */
    public static function getHomeDir(): string
    {
        $home = getenv('KLIPPER_RELEASER_HOME');

        if (!$home) {
            $home = static::getUserDir();

            if ($home) {
                return $home.'/.klipperReleaser';
            }
        }

        throw new RuntimeException('The HOME or KLIPPER_RELEASER_HOME environment variable must be set for releaser to run correctly');
    }

    /**
     * @throws RuntimeException
     */
    public static function getUserDir(): string
    {
        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    /**
     * @param null|array|string $localConfig
     */
    public static function createConfig(IOInterface $io, ?string $cwd = null): Config
    {
        $cwd = $cwd ?: (string) getcwd();
        $config = new Config($cwd);

        $config->merge([
            'home' => static::getHomeDir(),
        ]);

        // load global config
        $file = new JsonFile(sprintf('%s/%s.json', $config->get('data-dir'), GitUtil::getUniqueKey()));

        if ($file->exists()) {
            if (null !== $io && $io->isDebug()) {
                $io->write(sprintf('Loading config file "%s"', $file->getPath()));
            }

            try {
                $config->merge($file->read());
            } catch (\Throwable $e) {
                $io->writeError(sprintf('<error>Error to load config file "%s": %s', $file->getPath(), $e->getMessage()));
            }
        }

        $config->setConfigSource(new JsonConfigSource($file));

        return $config;
    }

    /**
     * @param null|array|string $localConfig
     *
     * @throws JsonValidationException
     * @throws JsonException
     */
    public function createReleaser(IOInterface $io, $localConfig = null, ?string $cwd = null): Releaser
    {
        $cwd = $cwd ?: (string) getcwd();
        $releaserFile = null;

        if (\is_string($localConfig)) {
            $releaserFile = $localConfig;
            $file = new JsonFile($localConfig, $io);

            if (!$file->exists()) {
                if ($localConfig === static::getReleaserFile()) {
                    $message = sprintf('Releaser could not find a %s file in %s', $localConfig, $cwd);
                } else {
                    $message = sprintf('Releaser could not find the config file in %s', $localConfig);
                }

                throw new RuntimeException($message);
            }

            $file->validateSchema(JsonFile::LAX_SCHEMA);
            $localConfig = $file->read();
        }

        $config = static::createConfig($io, $cwd);
        $config->merge($localConfig ?? []);

        if ($releaserFile) {
            $io->write(sprintf('Loading config file "%s"', $releaserFile), true, OutputInterface::VERBOSITY_DEBUG);
            $config->setConfigSource(new JsonConfigSource(new JsonFile(realpath($releaserFile), $io)));
        }

        return new Releaser(
            $config,
            new Splitter(
                [
                ],
                $io
            )
        );
    }

    /**
     * @throws JsonValidationException
     * @throws JsonException
     */
    public static function create(IOInterface $io, ?string $configFile = null, ?string $cwd = null): Releaser
    {
        $factory = new static();

        return $factory->createReleaser($io, $configFile, $cwd);
    }

    public static function getReleaserFile(): string
    {
        return trim(getenv('KLIPPER_RELEASER')) ?: './.klipperReleaser.json';
    }
}
