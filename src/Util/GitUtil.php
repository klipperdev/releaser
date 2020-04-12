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
use Klipper\Tool\Releaser\Exception\RuntimeException;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class GitUtil
{
    public const REQUIRED_VERSION = '^2.20';

    public static function getVersion(): ?string
    {
        $res = ProcessUtil::runSingleResult(['git', '--version']);
        $parts = explode('.', trim(str_replace('git version', '', $res)));

        if (!empty($parts)) {
            $parts = \array_slice($parts, 0, min(3, \count($parts)));
        }

        return implode('.', $parts) ?: null;
    }

    public static function validateVersion(): void
    {
        $version = static::getVersion();

        if (empty($version) || !Semver::satisfies($version, static::REQUIRED_VERSION)) {
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

    public static function getRemotes(): iterable
    {
        return ProcessUtil::runArrayResult(['git', 'remote']);
    }

    public static function getRemoteUrl(?string $remote = null): ?string
    {
        $remoteUrl = null;

        if (empty($remote)) {
            $remote = ProcessUtil::runSingleResult(['git', 'remote']);
        }

        if (!empty($remote)) {
            $remote = trim(explode("\n", $remote)[0]);
            $remoteUrl = ProcessUtil::runSingleResult(['git', 'remote', 'get-url', $remote]);
        }

        return !empty($remoteUrl) ? $remoteUrl : null;
    }

    public static function getCurrentBranch(): ?string
    {
        return ProcessUtil::runSingleResult(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
    }

    /**
     * @return string[]
     */
    public static function getBranches(): iterable
    {
        return ProcessUtil::runArrayResult(['git', 'branch', '--remotes', '--format', '%(refname:short)']);
    }

    /**
     * @return string[]
     */
    public static function getBranchNames(string $remote): iterable
    {
        $prefix = $remote.'/';
        $branches = [];

        foreach (static::getBranches() as $branch) {
            if (0 === strpos($branch, $prefix)) {
                $branches[] = substr($branch, \strlen($prefix));
            }
        }

        return $branches;
    }

    /**
     * @return string[]
     */
    public static function getModifiedFiles(string $branch, int $depth = 1): iterable
    {
        return ProcessUtil::runArrayResult(['git', 'diff', '--name-only', $branch.'~'.$depth]);
    }

    /**
     * @return string[]
     */
    public static function getLibraries(array $libraries, string $branch, int $depth = 1): iterable
    {
        $files = static::getModifiedFiles($branch, $depth);
        $libraryPaths = array_keys($libraries);
        $paths = [];

        foreach ($files as $file) {
            foreach ($libraryPaths as $libraryPath) {
                if (0 === strpos($file, $libraryPath)) {
                    $paths[] = $libraryPath;

                    break;
                }
            }
        }

        return array_unique($paths);
    }
}
