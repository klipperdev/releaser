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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class BaseIO implements IOInterface, LoggerInterface
{
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        if (\in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR], true)) {
            $this->writeError('<error>'.$message.'</error>', true, OutputInterface::VERBOSITY_NORMAL);
        } elseif (LogLevel::WARNING === $level) {
            $this->writeError('<warning>'.$message.'</warning>', true, OutputInterface::VERBOSITY_NORMAL);
        } elseif (LogLevel::NOTICE === $level) {
            $this->writeError('<info>'.$message.'</info>', true, OutputInterface::VERBOSITY_VERBOSE);
        } elseif (LogLevel::INFO === $level) {
            $this->writeError('<info>'.$message.'</info>', true, OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $this->writeError($message, true, OutputInterface::VERBOSITY_DEBUG);
        }
    }
}
