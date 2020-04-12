<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Splitter;

use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\IO\IOInterface;
use Klipper\Tool\Releaser\IO\NullIO;
use Klipper\Tool\Releaser\Splitter\Adapter\LogAdapterInterface;
use Klipper\Tool\Releaser\Splitter\Adapter\SplitterAdapterInterface;
use Klipper\Tool\Releaser\Util\BranchUtil;
use Klipper\Tool\Releaser\Util\LibraryUtil;
use Klipper\Tool\Releaser\Util\ProcessUtil;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Splitter implements SplitterInterface, LogAdapterInterface
{
    /**
     * @var SplitterAdapterInterface[]
     */
    private array $adapters = [];

    private IOInterface $io;

    private ?string $adapter = null;

    /**
     * @param SplitterAdapterInterface[] $adapters
     */
    public function __construct(array $adapters, ?IOInterface $io = null)
    {
        $this->io = $io ?? new NullIO();

        foreach ($adapters as $adapter) {
            $this->adapters[$adapter->getName()] = $adapter;
        }
    }

    public function setAdapter(?string $name): void
    {
        $name = 'auto' !== $name ? $name : null;

        if (null !== $name && !isset($this->adapter[$name])) {
            throw new RuntimeException(sprintf('The "%s" splitter adapter does not exist', $name));
        }

        $this->adapter = $name;
    }

    public function getAdapter(): SplitterAdapterInterface
    {
        if (isset($this->adapters[$this->adapter])) {
            return $this->adapters[$this->adapter];
        }

        foreach ($this->adapters as $adapter) {
            if ($adapter->isAvailable()) {
                $this->adapter = $adapter->getName();

                return $adapter;
            }
        }

        throw new RuntimeException('No adapter for splitter is found');
    }

    public function prepare(string $remote, string $branch): void
    {
        $remoteBranch = $remote.'/'.$branch;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);

        $this->io->write(sprintf('[<info>%s</info>] Fetch from <comment>%s</comment>', $branch, $remoteBranch));
        ProcessUtil::run(['git', 'fetch', 'origin', $branch]);
        $this->io->write(sprintf('[<info>%s</info>] Create subtree working branch <comment>%s</comment>', $branch, $subTreeBranch), true, OutputInterface::VERBOSITY_VERBOSE);
        ProcessUtil::run(['git', 'checkout', '-B', $subTreeBranch, $remoteBranch]);
    }

    public function terminate(string $remote, string $branch): void
    {
        $remoteBranch = $remote.'/'.$branch;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);

        $this->io->write(sprintf('[<info>%s</info>] Clean subtree working branch <comment>%s</comment>', $branch, $subTreeBranch), true, OutputInterface::VERBOSITY_VERBOSE);
        ProcessUtil::run(['git', 'checkout', '-B', $branch, $remoteBranch], false);
        ProcessUtil::run(['git', 'branch', '-D', $subTreeBranch], false);
    }

    public function split(string $branch, string $libraryPath, string $libraryUrl, bool $allowScratch = true): bool
    {
        $success = true;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);
        $libraryBranch = LibraryUtil::getBranchName($subTreeBranch, $libraryPath);
        $libraryRemote = LibraryUtil::getRemoteName($libraryPath);

        $this->io->write(
            sprintf('[<info>%s</info>][<info>%s</info>] Library splitting in progress...', $branch, $libraryPath),
            $this->io->isVeryVerbose()
        );

        try {
            // Add remote identified of library
            $this->logSplit($branch, $libraryPath, 'Adding the remote repository of the library...');
            ProcessUtil::run(['git', 'remote', 'add', $libraryRemote, $libraryUrl], false);

            // Split the library
            $scratched = $this->getAdapter()->split($this, $branch, $subTreeBranch, $libraryPath, $libraryRemote, $allowScratch);

            // Push to the Git repository of library
            $this->logSplit($branch, $libraryPath, 'Pushing to the remote repository...');

            try {
                $this->pushLibraryRepository($branch, $libraryBranch, $libraryRemote, $scratched);
            } catch (\Throwable $e) {
                if (!$allowScratch || $scratched) {
                    throw $e;
                }

                $this->pushLibraryRepository($branch, $libraryBranch, $libraryRemote, true);
            }

            // Clean library working directory
            $this->cleanLibraryWorkingBranch($branch, $libraryPath, $libraryBranch, $libraryRemote);

            $this->io->overwrite(
                sprintf('[<info>%s</info>][<info>%s</info>] Library splitting success', $branch, $libraryPath)
            );
        } catch (\Throwable $e) {
            $success = false;
            $this->cleanLibraryWorkingBranch($branch, $libraryPath, $libraryBranch, $libraryRemote);
            $this->io->overwriteError(
                sprintf('[<info>%s</info>][<info>%s</info>] <error>Library splitting error: %s</error>', $branch, $libraryPath, $e->getMessage())
            );
        }

        return $success;
    }

    public function logSplit(string $branch, string $libraryPath, string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->io->overwrite(
            sprintf('[<info>%s</info>][<info>%s</info>] Library splitting in progress: %s', $branch, $libraryPath, $message),
            $this->io->isVeryVerbose(),
            null,
            $verbosity
        );
    }

    private function pushLibraryRepository(string $branch, string $libraryBranch, string $libraryRemote, bool $force = false): void
    {
        $pushOptions = $force ? ['--force'] : [];
        ProcessUtil::run(['git', 'push', '--follow-tags', '--tags', $libraryRemote, $libraryBranch.':'.$branch, ...$pushOptions]);
    }

    private function cleanLibraryWorkingBranch(string $branch, string $libraryPath, string $libraryBranch, string $libraryRemote): void
    {
        $this->logSplit($branch, $libraryPath, 'Clean working branch and remote...');
        ProcessUtil::run(['git', 'branch', '-D', $libraryBranch], false);
        ProcessUtil::run(['git', 'remote', 'rm', $libraryRemote], false);
    }
}
