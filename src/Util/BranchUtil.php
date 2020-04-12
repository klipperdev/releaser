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
class BranchUtil
{
    public const SPLITTABLE_PATTERN = '/^master|(([0-9xX]+\.?)+)$/i';

    public static function isSplittable(string $branch, ?string $pattern = null): bool
    {
        $pattern = $pattern ?: static::SPLITTABLE_PATTERN;

        return (bool) preg_match($pattern, $branch);
    }

    public static function getSubTreeBranchName(string $branch): string
    {
        return 'subtree__'.$branch;
    }
}
