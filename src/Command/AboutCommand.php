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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AboutCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('about')
            ->setDescription('Shows the short information about Klipper Releaser')
            ->setHelp(
                <<<'EOT'
                    <info>php releaser about</info>
                    EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(
            <<<'EOT'
                <info>Releaser is a tool to split and release the main repository into many library repositories.</info>
                EOT
        );

        return 0;
    }
}
