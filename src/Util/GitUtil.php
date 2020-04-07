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

use Symfony\Component\Process\Process;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class GitUtil
{
    public static function getUniqueKey(?string $remoteName = null): ?string
    {
        $url = static::getRemoteUrl($remoteName);

        return null !== $url
            ? strtolower(str_replace(['@', ':', '/', '.', '#'], '-', $url))
            : null;
    }

    public static function getRemoteUrl(?string $remote = null): ?string
    {
        $remoteUrl = null;

        if (empty($remote)) {
            $process = new Process(['git', 'remote']);
            $process->run();
            $remote = $process->isSuccessful() ? trim($process->getOutput()) : '';
        }

        if (!empty($remote)) {
            $remote = trim(explode(PHP_EOL, $remote)[0]);
            $process = new Process(['git', 'remote', 'get-url', $remote]);
            $process->run();
            $remoteUrl = $process->isSuccessful() ? trim($process->getOutput()) : '';
        }

        return !empty($remoteUrl) ? $remoteUrl : null;
    }
}
