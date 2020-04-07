<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Json;

use JsonSchema\Validator;
use Klipper\Tool\Releaser\Exception\JsonException;
use Klipper\Tool\Releaser\Exception\JsonValidationException;
use Klipper\Tool\Releaser\Exception\RuntimeException;
use Klipper\Tool\Releaser\Exception\UnexpectedValueException;
use Klipper\Tool\Releaser\IO\IOInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class JsonFile
{
    public const LAX_SCHEMA = 1;

    public const STRICT_SCHEMA = 2;

    public const RELEASER_SCHEMA_PATH = '/../../res/releaser-schema.json';

    private string $path;

    private ?IOInterface $io;

    /**
     * Initializes json file reader/parser.
     *
     * @param string      $path path to a lockfile
     * @param IOInterface $io
     */
    public function __construct(string $path, ?IOInterface $io = null)
    {
        $this->path = $path;
        $this->io = $io;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * Reads json file.
     *
     * @throws RuntimeException
     */
    public function read(): ?array
    {
        try {
            if ($this->io && $this->io->isDebug()) {
                $this->io->writeError('Reading '.$this->path);
            }

            $json = Json::getContent($this->path);

            return Json::load($json, true);
        } catch (\Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Writes json file.
     *
     * @throws \Throwable|UnexpectedValueException
     */
    public function write(array $data): void
    {
        $dir = \dirname($this->path);

        if (!is_dir($dir)) {
            if (file_exists($dir)) {
                throw new UnexpectedValueException(sprintf('"%s" exists and is not a directory', $dir));
            }

            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new UnexpectedValueException(sprintf('"%s" does not exist and could not be created', $dir));
            }
        }

        $retries = 3;

        while ($retries--) {
            try {
                Json::writeIfModified($this->path, Json::encode($data)."\n");

                break;
            } catch (\Throwable $e) {
                if ($retries) {
                    usleep(500000);

                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Validates the schema of the current json file according to composer-schema.json rules.
     *
     * @param int         $schema     a JsonFile::*_SCHEMA constant
     * @param null|string $schemaFile a path to the schema file
     *
     * @throws JsonValidationException
     * @throws JsonException
     */
    public function validateSchema($schema = self::STRICT_SCHEMA, ?string $schemaFile = null): void
    {
        $data = Json::read($this->path);
        $schemaFile = $schemaFile ?? __DIR__.self::RELEASER_SCHEMA_PATH;

        // Prepend with file:// only when not using a special schema already (e.g. in the phar)
        if (false === strpos($schemaFile, '://')) {
            $schemaFile = 'file://'.$schemaFile;
        }

        $schemaData = (object) ['$ref' => $schemaFile];

        if (self::LAX_SCHEMA === $schema) {
            $schemaData->additionalProperties = true;
            $schemaData->required = [];
        }

        $validator = new Validator();
        $validator->check($data, $schemaData);

        if (!$validator->isValid()) {
            $errors = [];
            foreach ((array) $validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
            }

            throw new JsonValidationException('"'.$this->path.'" does not match the expected JSON schema', $errors);
        }
    }
}
