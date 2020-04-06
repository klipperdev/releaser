<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Command;

use Klipper\Tool\Releaser\Console\Application;
use Klipper\Tool\Releaser\IO\IOInterface;
use Klipper\Tool\Releaser\IO\NullIO;
use Symfony\Component\Console\Command\Command;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class BaseCommand extends Command
{
    private ?IOInterface $io = null;

    public function getIO(): IOInterface
    {
        if (null === $this->io) {
            $application = $this->getApplication();
            $this->setIO($application instanceof Application ? $application->getIO() : new NullIO());
        }

        return $this->io;
    }

    public function setIO(IOInterface $io): void
    {
        $this->io = $io;
    }
}
