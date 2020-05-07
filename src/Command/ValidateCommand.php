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

use Klipper\Tool\Releaser\Config\ConfigValidator;
use Klipper\Tool\Releaser\IO\IOInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ValidateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('validate')
            ->setDescription('Validates a releaser config file')
            ->setDefinition([
                new InputOption('strict', null, InputOption::VALUE_NONE, 'Return a non-zero exit code for warnings as well as errors'),
                new InputArgument('file', InputArgument::OPTIONAL, 'The path to the releaser config file'),
            ])
            ->setHelp(
                <<<'EOT'
                    The validate command validates a given releaser config file

                    Exit codes in case of errors are:
                    1 validation warning(s), only when --strict is given
                    2 validation error(s)
                    3 file unreadable or missing
                    EOT
            )
        ;
    }

    /**
     * @throws
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        $io = $this->getIO();
        $isStrict = $input->getOption('strict');

        if (!$file && $configSource = $this->getReleaser()->getConfig()->getConfigSource()) {
            $file = $configSource->getName();
        }

        if (!file_exists($file)) {
            $io->writeError('<error>'.$file.' not found.</error>');

            return 3;
        }

        if (!is_readable($file)) {
            $io->writeError('<error>'.$file.' is not readable.</error>');

            return 3;
        }

        $validator = new ConfigValidator($io);
        [$errors, $warnings] = $validator->validate($file);

        $this->outputResult($io, $file, $errors, $warnings);

        if ($errors) {
            return 2;
        }

        return $isStrict && $warnings ? 1 : 0;
    }

    private function outputResult(IOInterface $io, string $name, $errors, $warnings): void
    {
        if ($errors) {
            $io->writeError('<error>'.$name.' is invalid, the following errors/warnings were found:</error>');
        } elseif ($warnings) {
            $io->writeError('<info>'.$name.' is valid, but with a few warnings</info>');
        } else {
            $io->write('<info>'.$name.' is valid</info>');
        }

        $messages = [
            'error' => $errors,
            'warning' => $warnings,
        ];

        foreach ($messages as $style => $styleMessages) {
            foreach ($styleMessages as $styleMessage) {
                $io->writeError('<error>'.$styleMessage.'</error>');
            }
        }
    }
}
