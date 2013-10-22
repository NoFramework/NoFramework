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
        foreach ((array)$state as $property => $value) {
            if ('id' !== $property and property_exists($this, $property)) {
                if ($this->getOperator($value)) {
                    unset($this->$property);
                } else {
                    $this->$property = $value;
                    unset($state[$property]);
                }
            }
        }

        if ($state) {
            $this['$unresolved'] = $state;
        }

        $this->id = $id ? (array)$id : [spl_object_hash($this)];

        if (!isset(self::$root[$this->id[0]])) {
            self::$root[$this->id[0]] = $this;
        }
    }

    public function with($closure)
    {
        $closure($this);
        return $this;
    }

    public function newInstance($state = null, $id = null)
    {
        if (!$id) {
            $object = $this->__operator_new($state);

        } elseif (!isset($this->$id)) {
            $this['$unresolved'][$id] = ['$new' => $state];
            $object = $this->$id;

        } else {
            trigger_error(sprintf(
                'Property \'%s\' is already set',
                $id
            ), E_USER_ERROR);
        }

        return $object;
    }

    public function offsetExists($property)
    {
        return isset($this->__property[$property])
            or $this->isMagicProperty($property);
    }

    public function &offsetGet($property)
    {
        if (isset($this->__property[$property]) or '$' === $property[0]) {
            $return = &$this->__property[$property];

        } else {
            $return = $this->getMagicProperty($property);
        }

        return $return;
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
            new static(isset($parameter[0]) ? $parameter[0] : [], $id);

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
            or isset($this['$unresolved'][$property])
            or $this->isMagicPropertyCallback($property);
    }

    protected function &getMagicProperty($property)
    {
        if (isset($this['$unresolved'][$property])) {
            $this->__property[$property] = $this['$unresolved'][$property];

            if (1 === count($this['$unresolved'])) {
                unset($this['$unresolved']);

            } else {
                unset($this['$unresolved'][$property]);
            }

            $value = &$this->__property[$property];

        } elseif (!$this->auto or $this->isMagicPropertyCallback($property)) {
            $value = $this->getMagicPropertyCallback($property);

        } else {
            $this->__property[$property] = ['$new' =>
                true === $this->auto ? [] : $this->auto
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
            $return = self::$root[$this->id[0]];

            foreach (explode('.', $value) as $property) {
                $return = $return->$property;
            }

            return $return;

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

    protected function __operator_new($state = null, $id = null)
    {
        $state = $state
            ? is_string($state) ? ['class' => $state] : (array)$state
            : [];

        $class = new \ReflectionClass(
            isset($state['class'])
            ? ('\\' === $state['class'][0]
              ? substr($state['class'], 1)
              : ($this->namespace ? $this->namespace . '\\' : '')
                    . $state['class']
            )
            : static::class
        );

        $class->instance = $class->newInstanceWithoutConstructor();

        unset($state['class']);

        $last_id = (array)$id;
        $last_id = array_pop($last_id);

        foreach ((array)$this->propagate as $property) {
            if (!isset($state[$property]) and $property !== $last_id) {
                $state[$property] = $this->$property;
            }
        }
        
        $id = $id ? array_merge($this->id, (array)$id) : null;

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

            $class->instance->__construct($state, $id);

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
                        new self(array_merge([
                            'namespace' => $this->namespace,
                            'local_reuse' => $this->local_reuse
                        ], $state), $id)
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

