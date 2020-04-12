<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Splitter\Adapter;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface SplitterAdapterInterface
{
    public function getName(): string;

    public function isAvailable(): bool;

    /**
     * @throws \Throwable
     */
    public function split(LogAdapterInterface $log, string $branch, string $subTreeBranch, string $libraryPath, string $libraryRemote, bool $allowScratch = true): bool;
}
