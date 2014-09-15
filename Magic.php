<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework;

trait Magic
{
    protected $__property = [];

    public function __isset($name)
    {
        $name = 0 !== strpos($name, '$') ? $name : substr($name, 1);

        return !property_exists($this, $name) and (
            isset($this->__property[$name]) or
            method_exists($this, '__property_' . $name) or
            $this->__is_property($name)
        );
    }

    public function __get($name)
    {
        $is_resolve = 0 !== strpos($name, '$');
        $property = $is_resolve ? $name : substr($name, 1);

        if (!property_exists($this, $property)) {
            $value = &$this->__property[$property];

            if (!isset($value)) {
                if (method_exists($this, '__property_' . $property)) {
                    $value = $this->{'__property_' . $property}();
                    $value = isset($value) ? $value : false;

                } else {
                    $value = $this->__property($property);
                }
            }

            if ($is_resolve and $value instanceof \Generator) {
                $resolved = $value->current();

                if (isset($resolved)) {
                    return $resolved;

                } else {
                    trigger_error(sprintf('Property %s::$%s resolved to null',
                        static::class,
                        $name
                    ), E_USER_NOTICE); // respect @
                }
            } elseif (isset($value)) {
                return $value;

            } else {
                trigger_error(sprintf('Undefined property %s::$%s',
                    static::class,
                    $name
                ), E_USER_NOTICE); // respect @
            }
        } else {
            trigger_error(sprintf('Cannot get property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }

    public function __set($name, $value)
    {
        trigger_error(sprintf('Cannot set property %s::$%s',
            static::class,
            $name
        ), E_USER_ERROR);
    }

    public function __unset($name)
    {
        trigger_error(sprintf('Cannot unset property %s::$%s',
            static::class,
            $name
        ), E_USER_ERROR);
    }

    protected function __is_property($name) {}
    protected function __property($name) {}
}

