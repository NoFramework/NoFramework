<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Service;

class ErrorLog extends Log
{
    protected $message_type = 0;
    protected $destination;
    protected $extra_headers;

    protected function out($message)
    {
        return error_log(
            $message,
            $this->message_type,
            $this->destination,
            $this->extra_headers
        );
    }
}

