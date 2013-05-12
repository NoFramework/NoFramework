<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

trait MagicProperties
{
    protected $__property = [];

    public function __isset($property)
    {
        return isset($this->__property[$property]);
    }

    public function __get($property)
    {
        if (isset($this->__property[$property])) {
            return $this->__property[$property];

        } elseif ($this->isMagicProperty($property)) {
            return $this->getMagicProperty($property);

        } else {
            trigger_error(sprintf('Cannot read property %s::$%s',
                get_called_class(),
                $property
            ), E_USER_ERROR);
        }
    }

    public function __set($property, $value)
    {
        if (!isset($this->__property[$property])
            and !$this->isMagicProperty($property)
            and !array_key_exists($property, get_object_vars($this))
        ) {
            $this->$property = $value;

        } else {
            trigger_error(sprintf('Cannot write property %s::$%s',
                get_called_class(),
                $property
            ), E_USER_ERROR);
        }
    }

    public function __unset($property)
    {
        if (isset($this->__property[$property])) {
            unset($this->$property);

        } else {
            trigger_error(sprintf('Cannot remove property %s::$%s',
                get_called_class(),
                $property
            ), E_USER_ERROR);
        }
    }

    protected function isMagicProperty($property)
    {
        return method_exists($this, '__property_' . $property);
    }

    protected function getMagicProperty($property)
    {
        return $this->__property[$property]
            = $this->{'__property_' . $property}();
    }
}

