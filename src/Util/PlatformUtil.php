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
class PlatformUtil
{
    public static function isWindows(): bool
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }
}
