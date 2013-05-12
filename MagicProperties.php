<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

/**
 * Implemention of php magic methods for accessing inexistent properties.
 */
trait MagicProperties
{
    /**
     * Here is the registry of values for magic properties.
     * This thing is not just an array, it can be an object
     * implementing ArrayAccess interface, and can be injected from
     * outside on construction. 
     */
    protected $__property = [];

    /**
     * Whether the value for this property exists in registry;
     */
    public function __isset($property)
    {
        return isset($this->__property[$property]);
    }

    /**
     * Try to get the value for this property from the registry;
     * else try to evaluate it. 
     */
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

    /**
     * All magic properties are read only.
     * This can be adjusted by reimplementing __set, or you may
     * create setter, or just declare property as public.
     */
    public function __set($property, $value)
    {
        if (!isset($this->__property[$property])
            and !$this->isMagicProperty($property)
            and !property_exists($this, $property)
        ) {
            $this->$property = $value;

        } else {
            trigger_error(sprintf('Cannot write property %s::$%s',
                get_called_class(),
                $property
            ), E_USER_ERROR);
        }
    }

    /**
     * Hack of my dream. If there is a declared protected property and thus in __property,
     * then there will be inconsistency between calling that property from outside and from inside.
     * Magic is prefferable - so unset real property, but there is no way to kill it from outside,
     * even via reflection.
     * By implementing __unset this way we may use unset($object->$property) to remove confilcts.
     */
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

    /**
     * Is this a magic property (in common case: is there a method
     * prefixed by '__property_')
     */
    protected function isMagicProperty($property)
    {
        return method_exists($this, '__property_' . $property);
    }

    /**
     * Get magic property (call magic method)
     */
    protected function getMagicProperty($property)
    {
        return $this->__property[$property]
            = $this->{'__property_' . $property}();
    }
}

