<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Config;

use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Json\JsonFile;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class JsonConfigSource implements ConfigSourceInterface
{
    private JsonFile $file;

    public function __construct(JsonFile $file)
    {
        $this->file = $file;
    }

    public function getName(): string
    {
        return $this->file->getPath();
    }

    /**
     * @throws \Throwable
     */
    public function addLibrary(string $path, string $url): void
    {
        $config = $this->readJson();
        $config['sub-library'][$path] = $url;
        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function removeLibrary(string $path): void
    {
        $config = $this->readJson();
        unset($config['sub-library'][$path]);
        $this->saveJson($config);
    }

    protected function readJson(): array
    {
        if ($this->file->exists()) {
            if (!is_readable($this->file->getPath())) {
                throw new RuntimeException(sprintf('The file "%s" is not readable', $this->file->getPath()));
            }

            return $this->file->read() ?? [];
        }

        return [];
    }

    /**
     * @throws RuntimeException
     * @throws \Throwable
     */
    protected function saveJson(array $config): void
    {
        if ($this->file->exists() && !is_writable($this->file->getPath())) {
            throw new RuntimeException(sprintf('The file "%s" is not writable', $this->file->getPath()));
        }

        $this->file->write($config);
    }
}
