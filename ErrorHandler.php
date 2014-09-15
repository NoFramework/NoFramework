<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework;

class ErrorHandler
{
    protected $error_types = -1;

    const ERROR = 5;
    const WARNING = 4;
    const NOTICE = 3;
    const STRICT = 2;
    const DEPRECATED = 1;

    public function __construct($error_types = -1)
    {
        $this->error_types = $this->error_types;
    }

    public function __invoke($errno, $errstr, $errfile, $errline)
    {
        if (0 !== error_reporting()) { // respect @
            $severity = [
                E_RECOVERABLE_ERROR => [self::ERROR, 'Error'],
                E_USER_ERROR => [self::ERROR, 'Error'],
                E_WARNING => [self::WARNING, 'Warning'],
                E_USER_WARNING => [self::WARNING, 'Warning'],
                E_NOTICE => [self::NOTICE, 'Notice'],
                E_USER_NOTICE => [self::NOTICE, 'Notice'],
                E_STRICT => [self::STRICT, 'Strict'],
                E_DEPRECATED => [self::DEPRECATED, 'Deprecated'],
                E_USER_DEPRECATED => [self::DEPRECATED, 'Deprecated'],
            ][$errno];

            throw new \ErrorException(
                sprintf('[%s] %s', $severity[1], $errstr),
                $errno,
                $severity[0],
                $errfile,
                $errline
            );
        }

        return true;
    }

    public function register()
    {
        set_error_handler($this, $this->error_types);

        return $this;
    }
}

