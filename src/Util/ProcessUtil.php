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
    public static function runSingleResult(array $command): ?string
    {
        $p = new Process($command);
        $p->run();

        return trim($p->getOutput()) ?: null;
    }

    /**
     * @return string[]
     */
    public static function runArrayResult(array $command): iterable
    {
        $p = new Process($command);
        $p->run();

        return self::explodeResult($p);
    }

    public static function explodeResult(Process $p): iterable
    {
        $res = trim($p->getOutput());

        return !empty($res) ? explode("\n", $res) : [];
    }
}
