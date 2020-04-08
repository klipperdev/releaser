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

use Composer\Semver\Semver;
use Symfony\Component\Process\Process;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class GitUtil
{
    public const REQUIRED_VERSION = '^2.20';

    public static function getVersion(): ?string
    {
        $p = new Process(['git', '--version']);
        $p->run();

        $res = ProcessUtil::runSingleResult(['git', '--version']);
        $parts = explode('.', trim(str_replace('git version', '', $res)));

        if (!empty($parts)) {
            $parts = \array_slice($parts, 0, min(3, \count($parts)));
        }

        return implode('.', $parts) ?: null;
    }

    public static function validateVersion(): void
    {
        if (!Semver::satisfies(static::getVersion(), static::REQUIRED_VERSION)) {
            throw new RuntimeException(sprintf(
                'Git must be installed and this tool requires the "%s" version',
                static::REQUIRED_VERSION
            ));
        }
    }

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
