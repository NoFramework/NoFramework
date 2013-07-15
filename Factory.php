<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Factory implements \ArrayAccess
{
    use MagicProperties {
        isMagicProperty as isMagicPropertyCallback;
        getMagicProperty as getMagicPropertyCallback;
    }

    protected $namespace;
    protected $propagate;
    protected $local_reuse;
    protected $auto;

    private $id;
    private static $root = [];

    public function __construct($state = null, $id = null)
    {
        $state = (array)$state;

        foreach ((array)$state as $property => $value) {
            if ('id' === $property) {
                trigger_error('Cannot set id', E_USER_WARNING);

            } elseif (property_exists($this, $property)) {
                if ($this->getOperator($value)) {
                    unset($this->$property);
                } else {
                    $this->$property = $value;
                    unset($state[$property]);
                }
            }
        }

        if ($state) {
            $this->__property['$unresolved'] = $state;
        }

        $this->id = $id ? (array)$id : [spl_object_hash($this)];

        if (!isset(self::$root[$this->id[0]])) {
            self::$root[$this->id[0]] = $this;
        }
    }

    public function newInstance($state, $id = false)
    {
        if (!$id or !isset($this->$id)) {
            $object = $this->__operator_new(
                is_string($state) ? ['class' => $state] : $state,
                $id
            );

            if ($id) {
                $this->$id = $object;
            }
        } else {
            trigger_error(sprintf(
                'Property \'%s\' is already set',
                $id
            ), E_USER_ERROR);
        }
    }

    public function offsetExists($property)
    {
        return isset($this->__property[$property])
            or $this->isMagicProperty($property);
    }

    public function &offsetGet($property)
    {
        if (isset($this->__property[$property])
            or 0 === strpos($property, '$')
        ) {
            $out = &$this->__property[$property];

        } else {
            $out = $this->getMagicProperty($property);
        }

        return $out;
    }

    public function offsetSet($property, $value)
    {
        $this->__property[$property] = $value;
    }

    public function offsetUnset($property)
    {
        unset($this->__property[$property]);
    }

    public function release()
    {
        unset(self::$root[$this->id[0]]);
    }

    public static function __callStatic($id, $parameter)
    {
        if (!isset(self::$root[$id])) {
            new static(
                isset($parameter[0]) ? $parameter[0] : [],
                $id
            );

        } elseif (isset($parameter[0])) {
            trigger_error(sprintf(
                'Factory \'%s\' is already set',
                $id
            ), E_USER_WARNING);
        }

        return self::$root[$id];
    }

    protected function getOperator($value)
    {
        if (is_array($value)) {
            foreach ($value as $operator => $ignored) {
                return 0 === strpos($operator, '$') ? $operator : false;
            }
        }

        return false;
    }

    protected function resolve($value, $id = false)
    {
        while ($operator = $this->getOperator($value)) {
            $value =
                '$' === $operator
                ? $value[$operator](array_merge(
                    array_slice($this->id, 1),
                    $id ? (array)$id : []
                ))
                : $this->{'__operator_' . substr($operator, 1)}
                    ($value[$operator], $id);
        }

        return $value;
    }

    protected function isMagicProperty($property)
    {
        return $this->auto
            or isset($this->__property['$unresolved'][$property])
            or $this->isMagicPropertyCallback($property);
    }

    protected function &getMagicProperty($property)
    {
        if (isset($this->__property['$unresolved'][$property])) {
            $this->__property[$property] =
                $this->__property['$unresolved'][$property];

            if (1 === count($this->__property['$unresolved'])) {
                unset($this->__property['$unresolved']);

            } else {
                unset($this->__property['$unresolved'][$property]);
            }

            $value = &$this->__property[$property];

        } elseif (!$this->auto or $this->isMagicPropertyCallback($property)) {
            $value = $this->getMagicPropertyCallback($property);

        } else {
            $this->__property[$property] = ['$new' =>
                is_array($this->auto)
                ? $auto
                : (
                    is_string($this->auto)
                    ? ['class' => $this->auto]
                    : []
                )
            ];

            $value = &$this->__property[$property];
        }

        $value = $this->resolve($value, $property);

        return $value;
    }

    protected function __operator_reuse($value, $id = false)
    {
        $value = 0 === strpos($value, '.')
            ? substr($value, 1)
            : ($this->local_reuse ? $this->local_reuse . '.' : '') . $value;

        if (isset(self::$root[$this->id[0]])) {
            $out = self::$root[$this->id[0]];

            foreach (explode('.', $value) as $property) {
                $out = $out->$property;
            }

            return $out;

        } else {
            trigger_error(sprintf(
                'Cannot not reuse \'%s\', factory root is released',
                $value
            ), E_USER_NOTICE);
        }
    }

    protected function setClassInstanceProperty($class, $property, $value)
    {
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($class->instance, $value);
        return $this;
    }

    protected function __operator_new($state, $id = false)
    {
        $class = new \ReflectionClass(
            isset($state['class'])
            ? (0 === strpos($state['class'], '\\')
              ? substr($state['class'], 1)
              : ($this->namespace ? $this->namespace . '\\' : '')
                    . $state['class']
            )
            : get_called_class()
        );
        
        $class->instance = $class->newInstanceWithoutConstructor();

        unset($state['class']);

        foreach ((array)$this->propagate as $property) {
            if (!isset($state[$property])) {
                $state[$property] = $this->$property;
            }
        }

        if ($class->instance instanceof self) {
            foreach ([
                'namespace',
                'propagate',
                'local_reuse',
                'auto',
            ] as $property) {
                if (!isset($state[$property]) and !is_null($this->$property)) {
                    $state[$property] = $this->$property;
                }
            }

            $class->instance->__construct($state, array_merge(
                $this->id,
                $id ? (array)$id : []
            ));

        } else {
            if (isset($state['construct'])) {
                if ($class->constructor = $class->getConstructor()) {
                    $class->constructor->parameter = [];

                    foreach ((array)$state['construct'] as $value) {
                        $class->constructor->parameter[] =
                            $this->resolve($value);
                    }
                }

                unset($state['construct']);
            }

            if ($state) {
                $is_injectable = $class->hasProperty('__property');
                $intersect = [];

                foreach ($state as $property => $value) {
                    $is_unresolved = (bool)$this->getOperator($value);

                    if (!$is_injectable and $is_unresolved) {
                        $value = $this->resolve($value, $property);
                    }

                    if ($class->hasProperty($property)) {
                        if ($is_injectable and $is_unresolved) {
                            $intersect[$property] = true;

                        } else {
                            $this->setClassInstanceProperty(
                                $class,
                                $property,
                                $value
                            );
                            unset($state[$property]);
                        }
                    } elseif (!$is_injectable) {
                        $class->instance->$property = $value;
                        unset($state[$property]);
                    }
                }

                if ($is_injectable and $state) {
                    $this->setClassInstanceProperty(
                        $class,
                        '__property',
                        new self($state, array_merge(
                            $this->id,
                            $id ? (array)$id : []
                        ))
                        #$this->{__FUNCTION__}(array_merge(
                        #    ['propagate' => false],
                        #    $state
                        #), $id)
                    );

                    foreach ($intersect as $property => $ignored) {
                        unset($class->instance->$property);
                    }
                }
            }

            if (isset($class->constructor)) {
                $class->constructor->invokeArgs(
                    $class->instance,
                    $class->constructor->parameter
                );
            }
        }

        return $class->instance;
    }
}

