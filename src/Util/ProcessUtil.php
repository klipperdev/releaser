<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Util;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ProcessUtil
{
    public static string $processClass = Process::class;

    public static function create(array $command, ?string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): Process
    {
        $class = static::$processClass;

        return new $class($command, $cwd, $env, $input, $timeout);
    }

    /**
     * @param array|Process $process The process instance or the command
     */
    public static function run($process, bool $thrownException = true): Process
    {
        if (!$process instanceof Process) {
            $process = static::create((array) $process);
            $process->setTimeout(0);
        }

        $process->run();

        if ($thrownException && !$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    public static function runSingleResult(array $command): ?string
    {
        $p = static::create($command);
        $p->run();

        return trim($p->getOutput()) ?: null;
    }

    /**
     * @return string[]
     */
    public static function runArrayResult(array $command): iterable
    {
        $p = static::create($command);
        $p->run();

        return self::explodeResult($p);
    }

    public static function explodeResult(Process $p): iterable
    {
        $res = trim($p->getOutput());

        return !empty($res) ? explode("\n", $res) : [];
    }
}
