<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Console;

use Composer\Semver\Semver;
use Klipper\Tool\Releaser\Command\AboutCommand;
use Klipper\Tool\Releaser\Json\Json;
use Klipper\Tool\Releaser\Releaser;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console application.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Application extends BaseApplication
{
    private static string $logo = "
  ____        _
 |  _ \  ___ | |  ___   __ _  ___   ___  _ __
 | |_) |/ _ \| | / _ \ / _` |/ __| / _ \| '__|
 |  _ <|  __/| ||  __/| (_| |\__ \|  __/| |
 |_| \_\\___||_| \___| \__,_||___/ \___||_|
";

    public function __construct()
    {
        parent::__construct(Releaser::NAME, Releaser::VERSION);
    }

    public function getHelp(): string
    {
        return self::$logo.PHP_EOL.parent::getHelp();
    }

    public function getLongVersion(): string
    {
        if (Releaser::BRANCH_ALIAS_VERSION && Releaser::BRANCH_ALIAS_VERSION !== '@package_branch_alias_version'.'@') {
            return sprintf(
                '<info>%s</info> version <comment>%s (%s)</comment> %s',
                $this->getName(),
                Releaser::BRANCH_ALIAS_VERSION,
                $this->getVersion(),
                Releaser::RELEASE_DATE
            );
        }

        return parent::getLongVersion().' '.Releaser::RELEASE_DATE;
    }

    /**
     * @throws
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (getenv('KLIPPER_RELEASER_NO_INTERACTION')) {
            $input->setInteractive(false);
        }

        if (!$this->validatePhp($output)) {
            return 1;
        }

        return parent::doRun($input, $output);
    }

    protected function getDefaultCommands(): array
    {
        return array_merge(parent::getDefaultCommands(), [
            new AboutCommand(),
        ]);
    }

    protected function validatePhp(OutputInterface $output): bool
    {
        $composer = Json::readOrNull(__DIR__.'/../../composer.json', true);
        $errors = [];

        foreach ($composer['require'] ?? [] as $dependency => $constraint) {
            $isExt = 0 === strpos($dependency, 'ext-');

            if ($isExt || 'php' === strtolower($dependency)) {
                $dependencyName = $isExt ? substr($dependency, 4) : $dependency;
                $depVersion = $isExt ? phpversion($dependencyName) : PHP_VERSION;

                if (false === $depVersion || !Semver::satisfies($depVersion, $constraint)) {
                    $errors[] = sprintf('  - "%s" with the version: "%s"', $dependency, $constraint);
                }
            }
        }

        $res = empty($errors);

        if (!$res) {
            /** @var FormatterHelper $formatter */
            $formatter = $this->getHelperSet()->get('formatter');
            $messages = [
                '',
                $formatter->formatBlock([
                    '',
                    'To run this application, the PHP requirements must be installed and enabled:',
                    ...$errors,
                    '',
                ], 'error'),
            ];

            if ($output instanceof ConsoleOutputInterface) {
                $output->getErrorOutput()->write($messages, true);
            } else {
                $output->write($messages, true);
            }
        }

        return $res;
    }
}
