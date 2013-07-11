<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

abstract class Log
{
    protected $type_filter = false;

    abstract protected function onWrite($message, $type);

    protected function dateFormat() {
        return '[' . date('j-M-Y H:i:s') . ']';
    }

    public function write($message, $type = false)
    {
        return
            (false === $this->type_filter
            or false === $type
            or false !== array_search($type, $this->type_filter)
            )
            ? $this->onWrite($message, $type)
            : false;
    }
}

