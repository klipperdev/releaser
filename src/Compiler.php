<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser;

use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Json\Json;
use Klipper\Tool\Releaser\Util\ProcessUtil;
use Seld\PharUtils\Linter;
use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Compiler
{
    private ?string $version = null;

    private string $branchAliasVersion = '';

    private ?\DateTime $versionDate = null;

    /**
     * Compiles releaser into a single phar file.
     *
     * @param string $pharFile The phar file name
     */
    public function compile(string $pharFile = 'releaser.phar'): void
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $this->version = $this->getVersion();
        $this->versionDate = $this->getVersionDate();
        $this->branchAliasVersion = $this->getBranchAliasVersion();

        $phar = new \Phar($pharFile, 0, 'releaser.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finderSort = static function (\SplFileInfo $a, \SplFileInfo $b) {
            return strcmp(str_replace('\\', '/', $a->getRealPath()), str_replace('\\', '/', $b->getRealPath()));
        };

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->in(__DIR__)
            ->sort($finderSort)
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../composer.json'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../bootstrap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../res/releaser-schema.json'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/symfony/console/Resources/bin/hiddeninput.exe'), false);

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('LICENSE')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->exclude('examples')
            ->in(__DIR__.'/../vendor/composer/semver/')
            ->in(__DIR__.'/../vendor/justinrainbow/json-schema/')
            ->in(__DIR__.'/../vendor/psr/')
            ->in(__DIR__.'/../vendor/symfony/console/')
            ->in(__DIR__.'/../vendor/symfony/polyfill-ctype/')
            ->in(__DIR__.'/../vendor/symfony/polyfill-intl-grapheme/')
            ->in(__DIR__.'/../vendor/symfony/polyfill-intl-normalizer/')
            ->in(__DIR__.'/../vendor/symfony/polyfill-mbstring/')
            ->in(__DIR__.'/../vendor/symfony/polyfill-php80')
            ->in(__DIR__.'/../vendor/symfony/process/')
            ->in(__DIR__.'/../vendor/symfony/service-contracts/')
            ->in(__DIR__.'/../vendor/symfony/string/')
            ->sort($finderSort)
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_files.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_real.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/autoload_static.php'));

        if (file_exists(__DIR__.'/../vendor/composer/include_paths.php')) {
            $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/include_paths.php'));
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../vendor/composer/ClassLoader.php'));

        $this->addReleaserBin($phar);

        $phar->setStub($this->getStub());
        $phar->stopBuffering();

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../LICENSE'), false);

        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        $util = new Timestamps($pharFile);
        $util->updateTimestamps($this->versionDate);
        $util->save($pharFile, \Phar::SHA1);

        try {
            Linter::lint($pharFile);
        } catch (\Exception $e) {
            // do nothing
        }
    }

    private function getVersion(): string
    {
        $process = ProcessUtil::create(['git', 'log', '--pretty="%H"', '-n1', 'HEAD'], __DIR__);

        if (0 !== $process->run()) {
            throw new RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone and that git binary is available.');
        }

        return trim($process->getOutput());
    }

    /**
     * @throws
     */
    private function getVersionDate(): \DateTime
    {
        $process = ProcessUtil::create(['git', 'log', '-n1', '--pretty=%ci', 'HEAD'], __DIR__);

        if (0 !== $process->run()) {
            throw new RuntimeException('Can\'t run git log. You must ensure to run compile from releaser git repository clone and that git binary is available.');
        }

        $versionDate = new \DateTime(trim($process->getOutput()));
        $versionDate->setTimezone(new \DateTimeZone('UTC'));

        return $versionDate;
    }

    private function getBranchAliasVersion(): string
    {
        $process = ProcessUtil::create(['git', 'describe', '--tags', '--exact-match', 'HEAD']);
        $branchAliasVersion = '';

        if (0 === $process->run()) {
            $this->version = trim($process->getOutput());
        } else {
            // get branch-alias defined in composer.json for dev-master (if any)
            $localConfig = Json::readOrNull(__DIR__.'/../composer.json', true);

            if (isset($localConfig['extra']['branch-alias']['dev-master'])) {
                $branchAliasVersion = $localConfig['extra']['branch-alias']['dev-master'];
            }
        }

        return $branchAliasVersion;
    }

    private function addFile(\Phar $phar, \SplFileInfo $file, bool $strip = true): void
    {
        $path = $this->getRelativeFilePath($file);
        $content = file_get_contents($file);

        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        if ('src/Releaser.php' === $path) {
            $content = str_replace([
                '@package_version@',
                '@package_branch_alias_version@',
                '@release_date@',
            ], [
                $this->version,
                $this->branchAliasVersion,
                $this->versionDate->format('Y-m-d H:i:s'),
            ], $content);
            $content = preg_replace('{SOURCE_VERSION = \'[^\']+\';}', 'SOURCE_VERSION = \'\';', $content);
        }

        $phar->addFromString($path, $content);
    }

    private function getRelativeFilePath(\SplFileInfo $file): string
    {
        $realPath = $file->getRealPath();
        $pathPrefix = \dirname(__DIR__).\DIRECTORY_SEPARATOR;

        $pos = strpos($realPath, $pathPrefix);
        $relativePath = (false !== $pos) ? substr_replace($realPath, '', $pos, \strlen($pathPrefix)) : $realPath;

        return str_replace('\\', '/', $relativePath);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace(string $source): string
    {
        if (!\function_exists('token_get_all')) {
            return $source;
        }

        $output = '';

        foreach (token_get_all($source) as $token) {
            if (\is_string($token)) {
                $output .= $token;
            } elseif (\in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function addReleaserBin(\Phar $phar): void
    {
        $content = file_get_contents(__DIR__.'/../bin/releaser');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/releaser', $content);
    }

    private function getStub(): string
    {
        $stub = <<<'EOF'
            #!/usr/bin/env php
            <?php
            
            /*
             * This file is part of the Klipper Releaser package.
             *
             * (c) François Pluchino <francois.pluchino@klipper.dev>
             *
             * For the full copyright and license information, please view the LICENSE
             * file that was distributed with this source code.
             */

            if (extension_loaded('apc') && filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN) && filter_var(ini_get('apc.cache_by_default'), FILTER_VALIDATE_BOOLEAN)) {
                if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
                    ini_set('apc.cache_by_default', 0);
                } else {
                    fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running releaser commands.'.PHP_EOL);
                    fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.'.PHP_EOL);
                }
            }

            Phar::mapPhar('releaser.phar');

            EOF;

        // add warning once the phar is older than 60 days
        if (preg_match('{^[a-f0-9]+$}', $this->version)) {
            $warningTime = (int) $this->versionDate->format('U') + 60 * 86400;
            $stub .= "define('COMPOSER_DEV_WARNING_TIME', {$warningTime});\n";
        }

        return $stub.<<<'EOF'
            require 'phar://releaser.phar/bin/releaser';

            __HALT_COMPILER();
            EOF;
    }
}
