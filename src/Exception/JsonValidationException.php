<?php

/*
 * This file is part of the Klipper Releaser package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Tool\Releaser\Exception;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class JsonValidationException extends JsonException
{
    /**
     * @var string[]
     */
    protected array $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(string $message, array $errors = [], ?\Exception $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
