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
use Klipper\Tool\Releaser\Splitter\SplitterInterface;

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

    private SplitterInterface $splitter;

    public function __construct(Config $config, SplitterInterface $splitter)
    {
        $this->config = $config;
        $this->splitter = $splitter;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getSplitter(): SplitterInterface
    {
        return $this->splitter;
    }
}
