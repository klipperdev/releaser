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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Json
{
    /**
     * @throws \JsonException
     *
     * @return null|array|\stdClass
     */
    public static function read(string $file, bool $assoc = false)
    {
        return static::load(static::getContent($file), $assoc);
    }

    /**
     * @throws \JsonException
     *
     * @return null|array|\stdClass
     */
    public static function load(string $json, bool $assoc = false)
    {
        return static::parseContent($json, $assoc);
    }

    /**
     * @return null|array|\stdClass
     */
    public static function readOrNull(string $file, bool $assoc = false)
    {
        try {
            return static::loadOrNull(static::getContent($file), $assoc);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return null|array|\stdClass
     */
    public static function loadOrNull(string $json, bool $assoc = false)
    {
        try {
            return static::parseContent($json, $assoc);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @throws \JsonException
     */
    public static function getContent(string $file): string
    {
        $content = (string) @file_get_contents($file);

        if (!is_file($file) || !is_readable($file)) {
            throw new \JsonException(sprintf('Could not read "%s"', $file));
        }

        return $content;
    }

    /**
     * @throws \JsonException
     *
     * @return null|array|\stdClass
     */
    public static function parseContent(string $json, bool $assoc = false)
    {
        try {
            return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw $e;
        }
    }
}
