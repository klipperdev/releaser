<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Config;

use Klipper\Tool\Releaser\Exception\JsonValidationException;
use Klipper\Tool\Releaser\IO\IOInterface;
use Klipper\Tool\Releaser\Json\JsonFile;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ConfigValidator
{
    private IOInterface $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function validate(string $file): array
    {
        $config = null;
        $errors = [];
        $warnings = [];

        try {
            $json = new JsonFile($file, $this->io);
            $config = $json->read();

            $json->validateSchema(JsonFile::LAX_SCHEMA);
            $json->validateSchema();
        } catch (JsonValidationException $e) {
            foreach ($e->getErrors() as $message) {
                $errors[] = $message;
            }
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();

            return [$errors, $warnings];
        }

        if (isset($config['sub-libraries']) && !empty($config['sub-libraries'])) {
            foreach ($config['sub-libraries'] as $path => $gitUrl) {
                $pattern = '/^([A-Za-z0-9]+@|http(|s)\:\/\/)([A-Za-z0-9.]+(:\d+)?)(?::|\/)([\/\w.-]+?)(\.git)?$/i';
                preg_match($pattern, $gitUrl, $matches);

                if (empty($matches)) {
                    $warnings[] = sprintf('The "sub-libraries[%s]" value must contain a valid URL of GIT repository', $path);
                }
            }
        }

        return [$errors, $warnings];
    }
}
