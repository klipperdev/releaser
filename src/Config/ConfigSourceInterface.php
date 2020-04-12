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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ConfigSourceInterface
{
    public function getName(): string;

    public function addLibrary(string $path, string $url): void;

    public function removeLibrary(string $path): void;
}