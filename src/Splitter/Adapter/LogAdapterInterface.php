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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface LogAdapterInterface
{
    public function logSplit(string $branch, string $libraryPath, string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;
}
