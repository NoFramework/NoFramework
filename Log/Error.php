<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Log;

class Error extends \NoFramework\Log
{
    protected /*int*/ $message_type = 0;
    protected /*string*/ $destination;
    protected /*string*/ $extra_headers;
    protected /*boolean*/ $is_output_date = true;

    protected function onWrite($message, $type)
    {
        return error_log(
            ($this->is_output_date ? $this->dateFormat() . ' ' : '') .
            ($type ? '[' . $type . '] ' : '') . $message,
            $this->message_type,
            $this->destination,
            $this->extra_headers
        );
    }
}

