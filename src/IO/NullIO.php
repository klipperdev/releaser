<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\IO;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class NullIO extends BaseIO
{
    public function isVerbose(): bool
    {
        return false;
    }

    public function isVeryVerbose(): bool
    {
        return false;
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function write($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
    }

    public function writeError($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
    }

    public function overwrite($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
    }

    public function overwriteError($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
    }
}
