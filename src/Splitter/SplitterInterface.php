<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Splitter;

use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Splitter\Adapter\SplitterAdapterInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface SplitterInterface
{
    public function setAdapter(?string $name): void;

    /**
     * @throws RuntimeException When no adapter is found
     */
    public function getAdapter(): SplitterAdapterInterface;

    public function prepare(string $remote, string $branch): void;

    public function terminate(string $remote, string $branch): void;

    public function split(string $branch, string $libraryPath, string $libraryUrl): bool;
}
