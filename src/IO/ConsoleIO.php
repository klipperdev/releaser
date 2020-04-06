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

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ConsoleIO extends BaseIO
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected HelperSet $helperSet;

    protected ?string $lastMessage = null;

    protected ?string $lastMessageErr = null;

    private ?float $startTime = null;

    /**
     * Constructor.
     *
     * @param InputInterface  $input     The input instance
     * @param OutputInterface $output    The output instance
     * @param HelperSet       $helperSet The helperSet instance
     */
    public function __construct(InputInterface $input, OutputInterface $output, HelperSet $helperSet)
    {
        $this->input = $input;
        $this->output = $output;
        $this->helperSet = $helperSet;
    }

    public function enableDebugging(float $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getHelperSet(): HelperSet
    {
        return $this->helperSet;
    }

    public function isVerbose(): bool
    {
        return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;
    }

    public function isDebug(): bool
    {
        return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG;
    }

    public function write($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->doWrite($messages, $newline, false, $verbosity);
    }

    public function writeError($messages, bool $newline = true, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->doWrite($messages, $newline, true, $verbosity);
    }

    public function overwrite($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->doOverwrite($messages, $newline, $size, false, $verbosity);
    }

    public function overwriteError($messages, bool $newline = true, ?int $size = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->doOverwrite($messages, $newline, $size, true, $verbosity);
    }

    /**
     * @param string|string[] $messages
     */
    private function doWrite($messages, bool $newline, bool $stderr, int $verbosity, bool $raw = false): void
    {
        if ($verbosity > $this->output->getVerbosity()) {
            return;
        }

        if ($raw) {
            if (OutputInterface::OUTPUT_NORMAL === $verbosity) {
                $verbosity = OutputInterface::OUTPUT_RAW;
            } else {
                $verbosity |= OutputInterface::OUTPUT_RAW;
            }
        }

        if (null !== $this->startTime) {
            $memoryUsage = memory_get_usage() / 1024 / 1024;
            $timeSpent = microtime(true) - $this->startTime;
            $messages = array_map(static function ($message) use ($memoryUsage, $timeSpent) {
                return sprintf('[%.1fMiB/%.2fs] %s', $memoryUsage, $timeSpent, $message);
            }, (array) $messages);
        }

        if (true === $stderr && $this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->write($messages, $newline, $verbosity);
            $this->lastMessageErr = implode($newline ? "\n" : '', (array) $messages);

            return;
        }

        $this->output->write($messages, $newline, $verbosity);
        $this->lastMessage = implode($newline ? "\n" : '', (array) $messages);
    }

    /**
     * @param string|string[] $messages
     */
    private function doOverwrite($messages, bool $newline, ?int $size, bool $stderr, int $verbosity): void
    {
        $messages = implode($newline ? "\n" : '', (array) $messages);

        if (!isset($size)) {
            $size = \strlen(strip_tags($stderr ? $this->lastMessageErr : $this->lastMessage));
        }

        // clean the line
        $this->doWrite(str_repeat("\x08", $size), false, $stderr, $verbosity);

        // write the new message
        $this->doWrite($messages, false, $stderr, $verbosity);

        // In cmd.exe on Win8.1 (possibly 10?), the line can not be cleared, so we need to
        // track the length of previous output and fill it with spaces to make sure the line is cleared.
        $fill = $size - \strlen(strip_tags($messages));

        if ($fill > 0) {
            // whitespace whatever has left
            $this->doWrite(str_repeat(' ', $fill), false, $stderr, $verbosity);
            // move the cursor back
            $this->doWrite(str_repeat("\x08", $fill), false, $stderr, $verbosity);
        }

        if ($newline) {
            $this->doWrite('', true, $stderr, $verbosity);
        }

        if ($stderr) {
            $this->lastMessageErr = $messages;
        } else {
            $this->lastMessage = $messages;
        }
    }
}
