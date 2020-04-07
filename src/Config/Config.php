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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Config
{
    public static array $defaultConfig = [
        'data-dir' => '{home}/.klipperReleaser',
        'home' => '.',
        'sub-libraries' => [],
    ];

    private array $config;

    private ?string $baseDir;

    private ?ConfigSourceInterface $configSource = null;

    public function __construct(string $baseDir)
    {
        $this->config = static::$defaultConfig;
        $this->baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function setConfigSource(?ConfigSourceInterface $source): void
    {
        $this->configSource = $source;
    }

    public function getConfigSource(): ?ConfigSourceInterface
    {
        return $this->configSource;
    }

    /**
     * Merges new config values with the existing ones (overriding).
     */
    public function merge(array $config): void
    {
        $this->config = static::mergeValues($this->config, $config);
    }

    /**
     * @param null|mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $val = $this->config;

        foreach ($keys as $subKey) {
            $val = $this->getValue($val, $subKey);

            if (!\is_array($val)) {
                break;
            }
        }

        return $val ?? $default;
    }

    public function raw(): array
    {
        return $this->config;
    }

    public static function mergeValues(array $initialConfig, array $config): array
    {
        foreach ($config as $key => $value) {
            if (isset($initialConfig[$key]) && \is_array($initialConfig[$key])) {
                if (\is_array($value)) {
                    $initialConfig[$key] = static::mergeValues($initialConfig[$key], $value);
                } else {
                    $initialConfig[$key][] = $value;
                }
            } else {
                $initialConfig[$key] = $value;
            }
        }

        return $initialConfig;
    }

    /**
     * @param null|mixed $config
     *
     * @return null|mixed
     */
    private function getValue($config, string $key)
    {
        $nKey = ctype_digit($key) ? (int) $key : $key;

        if (\is_array($config) && \array_key_exists($nKey, $config)) {
            return $this->process($config[$nKey]);
        }

        return null;
    }

    /**
     * Replaces {$refs} inside a config string.
     *
     * @param null|int|string $value   The config string that can contain {$refs-to-other-config}
     * @param mixed           $default The default value
     *
     * @return null|mixed
     */
    private function process($value, $default = null)
    {
        $config = $this;

        if (!\is_string($value)) {
            return $value;
        }

        return preg_replace_callback('#{(.+)}#', static function ($match) use ($config, $default) {
            return $config->get($match[1], $default);
        }, $value);
    }
}
