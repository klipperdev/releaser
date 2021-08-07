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
class SplitshLiteAdapter implements SplitterAdapterInterface
{
    public function getName(): string
    {
        return 'splitsh-lite';
    }

    public function isAvailable(): bool
    {
        return !empty(ProcessUtil::runSingleResult(['splitsh-lite', '--version']));
    }

    public function split(LogAdapterInterface $log, string $branch, string $subTreeBranch, string $libraryPath, string $libraryRemote, bool $allowScratch = true): bool
    {
        $libraryBranch = LibraryUtil::getBranchName($subTreeBranch, $libraryPath);

        // Split the library
        $log->logSplit($branch, $libraryPath, 'Splitting local branch...');
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
        $opt = $scratch ? ['--scratch'] : [];
        ProcessUtil::run(['splitsh-lite', '--prefix='.$libraryPath, '--target=heads/'.$libraryBranch, '--quiet', ...$opt]);
        ProcessUtil::run(['git', 'checkout', 'heads/'.$libraryBranch]);
        ProcessUtil::run(['git', 'switch', '-c', $libraryBranch]);
    }
}
