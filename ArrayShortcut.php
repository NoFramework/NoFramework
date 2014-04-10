<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

trait ArrayShortcut
{
    public function offsetSet($offset, $value) {
        trigger_error(sprintf(
            '%s is not setable',
            static::class
        ));
    }

    public function offsetUnset($offset) {
        trigger_error(sprintf(
            '%s is not unsetable',
            static::class
        ));
    }

    public function offsetExists($offset) {
        return true;
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    abstract public function get($option);
}

