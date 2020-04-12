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
     * @param mixed $value
     *
     * @throws \Throwable
     */
    public function set(string $key, $value): void
    {
        $config = $this->readJson();
        $config[$key] = $value;
        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function unset(string $key): void
    {
        $config = $this->readJson();
        unset($config[$key]);
        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function addBranch(string $branch): void
    {
        $config = $this->readJson();

        if (!\in_array($branch, $config['branches'] ?? [], true)) {
            $config['branches'][] = $branch;
        }

        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function removeBranch(string $branch): void
    {
        $config = $this->readJson();
        unset($config['branches'][array_search($branch, $config['branches'] ?? [], true)]);
        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function addLibrary(string $path, string $url): void
    {
        $config = $this->readJson();
        $config['libraries'][$path] = $url;
        $this->saveJson($config);
    }

    /**
     * @throws \Throwable
     */
    public function removeLibrary(string $path): void
    {
        $config = $this->readJson();
        unset($config['libraries'][$path]);
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

        if (empty($config) && !$this->file->exists()) {
            return;
        }

        $this->file->write($config);
    }
}
