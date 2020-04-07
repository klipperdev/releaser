<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser;

use Klipper\Tool\Releaser\Config\Config;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Releaser
{
    public const NAME = 'Klipper Releaser';

    public const VERSION = '@package_version@';

    public const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    public const RELEASE_DATE = '@release_date@';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
