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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class LibraryUtil
{
    public static function getBranchName(string $subTreeBranch, string $libraryPath): string
    {
        return sprintf(
            '%s__%s',
            $subTreeBranch,
            strtolower(str_replace(['/', '\\', '_'], '-', $libraryPath))
        );
    }

    public static function getRemoteName(string $libraryPath): string
    {
        return sprintf(
            'target-%s',
            strtolower(str_replace(['/', '\\', '_'], '-', $libraryPath))
        );
    }
}
