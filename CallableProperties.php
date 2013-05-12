<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

trait CallableProperties
{
    public function __call($property, $arguments)
    {
        if (is_callable($this->$property)) {
            return call_user_func_array($this->$property, $arguments);

        } else {
            trigger_error(sprintf('Call to undefined method %s::$%s()',
                get_called_class(),
                $property
            ), E_USER_ERROR);
        }
    }
}

