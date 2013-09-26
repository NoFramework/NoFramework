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
                static::class,
                $property
            ), E_USER_ERROR);
        }
    }

    public function __set($property, $value)
    {
        if (!isset($this->__property[$property])
            and !$this->isMagicProperty($property)
            and !property_exists($this, $property)
        ) {
            $this->$property = $value;

        } else {
            trigger_error(sprintf('Cannot write property %s::$%s',
                static::class,
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
                static::class,
                $property
            ), E_USER_ERROR);
        }
    }

    protected function isMagicProperty($property)
    {
        return method_exists($this, '__property_' . $property);
    }

    protected function &getMagicProperty($property)
    {
        if (isset($this->__property['$cache'][$property])
        and $this->__property['$cache'][$property]['expire'] > microtime(true)
        ) {
            $return = &$this->__property['$cache'][$property]['value'];

        } else {
            $ttl = false;
            $value = $this->{'__property_' . $property}($ttl);

            if (false === $ttl) {
                $this->__property[$property] = $value;
                $return = &$this->__property[$property];

            } else {
                $this->__property['$cache'][$property]['value'] = $value;
                $this->__property['$cache'][$property]['expire']
                     = $ttl + microtime(true);
                $return = &$this->__property['$cache'][$property]['value'];
            }
        }

        return $return;
    }
}

