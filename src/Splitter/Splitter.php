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
use Klipper\Tool\Releaser\Splitter\Adapter\SplitterAdapterInterface;
use Klipper\Tool\Releaser\Util\BranchUtil;
use Klipper\Tool\Releaser\Util\LibraryUtil;
use Klipper\Tool\Releaser\Util\ProcessUtil;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Splitter implements SplitterInterface
{
    /**
     * @var SplitterAdapterInterface[]
     */
    private array $adapters = [];

    private ?IOInterface $io;

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
        if (null !== $name && !isset($this->adapter[$name])) {
            throw new RuntimeException(sprintf('The "%s" splitter adapter does not exist', $name));
        }

        $this->adapter = $name;
    }

    public function prepare(string $remote, string $branch): void
    {
        $remoteBranch = $remote.'/'.$branch;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);

        $this->io->write(sprintf('[<info>%s</info>] Fetch from <comment>%s</comment>', $branch, $remoteBranch));
        ProcessUtil::run(['git', 'fetch', 'origin', $branch]);
        $this->io->write(sprintf('[<info>%s</info>] Create subtree working branch <comment>%s</comment>', $branch, $subTreeBranch));
        ProcessUtil::run(['git', 'checkout', '-B', $subTreeBranch, $remoteBranch]);
    }

    public function terminate(string $remote, string $branch): void
    {
        $remoteBranch = $remote.'/'.$branch;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);

        $this->io->write(sprintf('[<info>%s</info>] Clean subtree working branch <comment>%s</comment>', $branch, $subTreeBranch));
        ProcessUtil::run(['git', 'checkout', '-B', $branch, $remoteBranch], false);
        ProcessUtil::run(['git', 'branch', '-D', $subTreeBranch], false);
    }

    public function split(string $branch, string $libraryPath, string $libraryRemote): bool
    {
        $success = true;
        $subTreeBranch = BranchUtil::getSubTreeBranchName($branch);

        $this->io->write(
            sprintf('[<info>%s</info>][<info>%s</info>] Library splitting in progress...', $branch, $libraryPath),
            $this->io->isVerbose()
        );

        try {
            $this->splitLibrary($branch, $subTreeBranch, $libraryPath, $libraryRemote);
            $this->io->overwrite(
                sprintf('[<info>%s</info>][<info>%s</info>] Library splitting success', $branch, $libraryPath)
            );
        } catch (\Throwable $e) {
            $success = false;
            $this->io->overwriteError(
                sprintf('[<info>%s</info>][<info>%s</info>] <error>Library splitting error: %s</error>', $branch, $libraryPath, $e->getMessage())
            );
        }

        // Clean the working branches
        $this->io->overwrite(
            sprintf('[<info>%s</info>][<info>%s</info>] Clean library subtree working branch and remote', $branch, $libraryPath),
            true,
            OutputInterface::VERBOSITY_VERBOSE
        );
        ProcessUtil::run(['git', 'branch', '-D', LibraryUtil::getBranchName($subTreeBranch, $libraryPath)], false);
        ProcessUtil::run(['git', 'remote', 'rm', LibraryUtil::getRemoteName($libraryPath)], false);

        return $success;
    }

    protected function splitLibrary(string $branch, string $subTreeBranch, string $libraryPath, string $libraryRemoteUrl): void
    {
        $libraryBranch = LibraryUtil::getBranchName($subTreeBranch, $libraryPath);
        $libraryRemote = LibraryUtil::getRemoteName($libraryPath);

        // Add remote identified of library
        ProcessUtil::run(['git', 'remote', 'add', $libraryRemote, $libraryRemoteUrl], false);

        // Split the library
        $this->getAdapter()->split($branch, $subTreeBranch, $libraryPath, $libraryBranch);

        // Push to the Git repository of library
        ProcessUtil::run(['git', 'push', '--follow-tags', '--tags', $libraryRemote, sprintf('%s:%s', $subTreeBranch, $branch)]);
    }

    private function getAdapter(): SplitterAdapterInterface
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
}
