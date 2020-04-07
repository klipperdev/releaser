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
use Klipper\Tool\Releaser\Command\ValidateCommand;
use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Factory;
use Klipper\Tool\Releaser\IO\ConsoleIO;
use Klipper\Tool\Releaser\IO\IOInterface;
use Klipper\Tool\Releaser\IO\NullIO;
use Klipper\Tool\Releaser\Json\Json;
use Klipper\Tool\Releaser\Releaser;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console application.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Application extends BaseApplication
{
    protected IOInterface $io;

    protected ?Releaser $releaser = null;

    protected ?string $configFile = null;

    private static string $logo = "
  ____        _
 |  _ \  ___ | |  ___   __ _  ___   ___  _ __
 | |_) |/ _ \| | / _ \ / _` |/ __| / _ \| '__|
 |  _ <|  __/| ||  __/| (_| |\__ \|  __/| |
 |_| \_\\___||_| \___| \__,_||___/ \___||_|
";

    /**
     * Store the initial working directory at startup time.
     */
    private string $initialWorkingDirectory;

    public function __construct()
    {
        $this->initialWorkingDirectory = getcwd();
        $this->io = new NullIO();

        parent::__construct(Releaser::NAME, Releaser::VERSION);
    }

    public function getIO(): IOInterface
    {
        return $this->io;
    }

    /**
     * @throws
     */
    public function getReleaser(): Releaser
    {
        if (null === $this->releaser) {
            try {
                $this->releaser = Factory::create($this->io, $this->configFile, $this->initialWorkingDirectory);
            } catch (\InvalidArgumentException $e) {
                $this->io->writeError($e->getMessage());
                exit(1);
            }
        }

        return $this->releaser;
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

        $io = $this->io = new ConsoleIO($input, $output, new HelperSet([
            new QuestionHelper(),
        ]));

        if (!$this->validatePhp($output)) {
            return 1;
        }

        // switch working dir
        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
            $this->initialWorkingDirectory = $newWorkDir;
            $io->write('Changed CWD to '.getcwd(), true, OutputInterface::VERBOSITY_DEBUG);
        }

        // switch configuration file
        if (($newConfigFile = $input->getParameterOption(['--config', '-c'])) && is_file($newConfigFile)) {
            $this->configFile = $newConfigFile;
        }

        if ($input->hasParameterOption('--profile')) {
            $startTime = microtime(true);
            $this->io->enableDebugging($startTime);
        }

        $result = parent::doRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        if (isset($startTime)) {
            $io->writeError('<info>Time: '.round(microtime(true) - $startTime, 2).'s');
        }

        return $result;
    }

    protected function getDefaultCommands(): array
    {
        return array_merge(parent::getDefaultCommands(), [
            new AboutCommand(),
            new ValidateCommand(),
        ]);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('--profile', null, InputOption::VALUE_NONE, 'Display timing information'));
        $definition->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory'));
        $definition->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'If specified, use the given JSON config file'));

        return $definition;
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

    /**
     * @throws RuntimeException
     */
    private function getNewWorkingDir(InputInterface $input): string
    {
        $workingDir = $input->getParameterOption(['--working-dir', '-d']);

        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new RuntimeException(sprintf('Invalid working directory specified, "%s" does not exist', $workingDir));
        }

        return $workingDir;
    }
}
