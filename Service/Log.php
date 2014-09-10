<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service;

class Log
{
    protected $type_filter;
    protected $is_output_date = true;

    protected function out($message)
    {
        echo $message . PHP_EOL;
    }

    protected function dateFormat() {
        return '[' . date('j-M-Y H:i:s') . ']';
    }

    public function write($message, $type = false)
    {
        if (
            !$this->type_filter or
            !$type or
            false !== array_search($type, $this->type_filter)
        ) {
            $this->out(
                ($this->is_output_date ? $this->dateFormat() . ' ' : '') .
                ($type ? '[' . $type . '] ' : '') . trim($message)
            );
        }

        return $this;
    }
}

