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

use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Util\BranchUtil;
use Klipper\Tool\Releaser\Util\GitUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SplitCommand extends BaseCommand
{
    private int $depth = 1;

    private ?string $remote;

    private array $branches = [];

    private array $libraries = [];

    protected function configure(): void
    {
        $this
            ->setName('split')
            ->setDescription('Split the main repository into many library repositories')
            ->addOption('depth', '-D', InputOption::VALUE_REQUIRED, 'Depth history of Git to check the modified files', 1)
            ->addOption('remote', '-R', InputOption::VALUE_REQUIRED, 'Remote name of GIT repository, by default, the first remote is selected')
            ->addOption('all', '-A', InputOption::VALUE_NONE, 'Check if all branches must be splitted')
            ->addOption('branch', '-b', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'List of the Git branch names to be splitted, all branches if no branch is specified')
            ->addArgument('library', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'List of the library paths to be splitted, all configured paths if any path is specified')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        GitUtil::validateVersion();
        $this->initDepth($input);
        $this->initRemote($input);
        $this->initBranches($input);
        $this->initLibraries($input);

        $splitter = $this->getReleaser()->getSplitter();
        $splitter->setAdapter($this->getReleaser()->getConfig()->get('adapter'));
        $this->getIO()->write(
            sprintf('Splitter adapter used: <info>%s</info>', $splitter->getAdapter()->getName()),
            true,
            OutputInterface::VERBOSITY_VERBOSE
        );
    }

    protected function initDepth(InputInterface $input): void
    {
        if (!is_numeric($input->getOption('depth'))) {
            throw new RuntimeException('Git history depth option must be an integer');
        }

        $this->depth = (int) $input->getOption('depth');

        if ($this->depth < 1) {
            throw new RuntimeException('Git history depth must be greater than or equal that 1');
        }
    }

    protected function initRemote(InputInterface $input): void
    {
        $remotes = GitUtil::getRemotes();

        if (empty($remotes)) {
            throw new RuntimeException('No Git remote is available in the repository');
        }

        $this->remote = $input->getOption('remote') ?: $remotes[0];

        if (!\in_array($this->remote, (array) $remotes, true)) {
            throw new RuntimeException(sprintf(
                'Git remote "%s" does not exist, available remotes: %s',
                $this->remote,
                implode(', ', (array) $remotes)
            ));
        }
    }

    protected function initBranches(InputInterface $input): void
    {
        $this->branches = (array) $input->getOption('branch');
        $branchNames = GitUtil::getBranchNames($this->remote);
        $branchPattern = $this->getReleaser()->getConfig()->get('branch-pattern') ?: null;

        if (empty($this->branches) && null !== $currentBranch = GitUtil::getCurrentBranch()) {
            $this->branches[] = $currentBranch;
        }

        if (empty($branchNames)) {
            throw new RuntimeException('No Git branch is available in the repository');
        }

        if ($input->getOption('all')) {
            foreach ($branchNames as $branchName) {
                if (BranchUtil::isSplittable($branchName, $branchPattern)) {
                    $this->branches[] = $branchName;
                }
            }

            $this->branches = array_unique($this->branches);
        }

        foreach ($this->branches as $branch) {
            if (!\in_array($branch, $branchNames, true)) {
                throw new RuntimeException(sprintf('The "%s" branch does not exist in Git repository', $branch));
            }
        }
    }

    protected function initLibraries(InputInterface $input): void
    {
        $config = $this->getReleaser()->getConfig();
        $baseDir = $config->getBaseDir();
        $configLibraries = $config->get('libraries');
        $libraryNames = $input->getArgument('library');
        $this->libraries = $configLibraries;

        foreach ($libraryNames as $libraryName) {
            if (!is_dir($baseDir.'/'.$libraryName)) {
                throw new RuntimeException(sprintf('The library path "%s" does not exist in the current project', $libraryName));
            }

            if (!isset($configLibraries[$libraryName])) {
                throw new RuntimeException(sprintf('The library path "%s" is not configured for the splitting', $libraryName));
            }
        }

        // Remove the non-existing libraries in project or the unconfigured libraries for splitting
        foreach (array_keys($this->libraries) as $libraryName) {
            if (!isset($configLibraries[$libraryName]) || !is_dir($baseDir.'/'.$libraryName)) {
                unset($this->libraries[$libraryName]);
            }
        }
    }

    /**
     * @throws
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $success = true;
        $spitter = $this->getReleaser()->getSplitter();

        foreach ($this->branches as $branch) {
            $libraryPaths = GitUtil::getLibraries($this->libraries, $branch, $this->depth);

            if (empty($libraryPaths)) {
                $io->write(sprintf('[<info>%s</info>] <info>No library to split</info>', $branch));
            } else {
                $spitter->prepare($this->remote, $branch);
            }

            foreach ($libraryPaths as $libraryPath) {
                $success = $spitter->split($branch, $libraryPath, $this->libraries[$libraryPath])
                    && $success;
            }

            if (!empty($libraryPaths)) {
                $spitter->terminate($this->remote, $branch);
            }
        }

        return $success ? 0 : 1;
    }
}
