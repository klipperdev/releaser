<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Splitter\Adapter;

use Klipper\Tool\Releaser\Util\LibraryUtil;
use Klipper\Tool\Releaser\Util\ProcessUtil;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class GitSubtreeAdapter implements SplitterAdapterInterface
{
    public function getName(): string
    {
        return 'git-subtree';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function split(LogAdapterInterface $log, string $branch, string $subTreeBranch, string $libraryPath, string $libraryRemote, bool $allowScratch = true): bool
    {
        $libraryBranch = LibraryUtil::getBranchName($subTreeBranch, $libraryPath);

        // Fetch the remote library
        $log->logSplit($branch, $libraryPath, 'Fetching remote repository...');
        ProcessUtil::run(['git', 'fetch', $libraryRemote, '--depth=1'], false);

        // Create the local branch for the remote library
        $log->logSplit($branch, $libraryPath, 'Creating the local branch...');
        ProcessUtil::run(['git', 'branch', $libraryBranch, $libraryRemote.'/'.$branch], false);

        // Split the library
        $log->logSplit($branch, $libraryPath, 'Splitting the local branch...');
        $scratched = false;

        try {
            $this->subtree($libraryPath, $libraryBranch);
        } catch (\Throwable $e) {
            if (!$allowScratch) {
                throw $e;
            }

            $scratched = true;
            $this->subtree($libraryPath, $libraryBranch, $scratched);
        }

        return $scratched;
    }

    private function subtree(string $libraryPath, string $libraryBranch, bool $scratch = false): void
    {
        if ($scratch) {
            ProcessUtil::run(['git', 'branch', '-D', $libraryBranch], false);
        }

        ProcessUtil::run(['git', 'subtree', 'split', '-P', $libraryPath, '-b', $libraryBranch]);
    }
}
