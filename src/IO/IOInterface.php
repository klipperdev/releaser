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
interface IOInterface
{
    /**
     * Is this output verbose?
     */
    public function isVerbose(): bool;

    /**
     * Is the output very verbose?
     */
    public function isVeryVerbose(): bool;

    /**
     * Is the output in debug verbosity?
     */
    public function isDebug(): bool;

    /**
     * Writes a message to the output.
     *
     * @param array|string $messages  The message as an array of lines or a single string
     * @param bool         $newline   Whether to add a newline or not
     * @param int          $verbosity Verbosity level from the VERBOSITY_* constants
     */
    public function write($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;

    /**
     * Writes a message to the error output.
     *
     * @param array|string $messages  The message as an array of lines or a single string
     * @param bool         $newline   Whether to add a newline or not
     * @param int          $verbosity Verbosity level from the VERBOSITY_* constants
     */
    public function writeError($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;

    /**
     * Overwrites a previous message to the output.
     *
     * @param array|string $messages  The message as an array of lines or a single string
     * @param bool         $newline   Whether to add a newline or not
     * @param int          $size      The size of line
     * @param int          $verbosity Verbosity level from the VERBOSITY_* constants
     */
    public function overwrite($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;

    /**
     * Overwrites a previous message to the error output.
     *
     * @param array|string $messages  The message as an array of lines or a single string
     * @param bool         $newline   Whether to add a newline or not
     * @param int          $size      The size of line
     * @param int          $verbosity Verbosity level from the VERBOSITY_* constants
     */
    public function overwriteError($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;
}
