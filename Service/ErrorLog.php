<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
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

