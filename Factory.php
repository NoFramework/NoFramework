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

    private $root_id;
    private static $root = [];

    public function __construct($state = null)
    {
        if (isset($state['namespace'])) {
            $this->namespace = $state['namespace'];
            unset($state['namespace']);
        }

        if ($state) {
            $this->__property['$unresolved'] = $state;

            if (is_null($this->root_id)) {
                self::$root[$this->root_id = spl_object_hash($this)] = $this;
            }
        }
    }

    public function newInstance($state)
    {
        return $this->__operator_new($state);
    }

    public function offsetExists($property)
    {
        return isset($this->__property[$property])
            or $this->isMagicProperty($property);
    }

    public function offsetGet($property)
    {
        return isset($this->__property[$property])
            ? $this->__property[$property]
            : $this->getMagicProperty($property);
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
        if (isset(self::$root[$this->root_id])) {
            unset(self::$root[$this->root_id]);
        }
    }

    public static function walk($closure)
    {
        array_walk(self::$root, $closure);
    }

    public static function single($state = null)
    {
        if (!$root = reset(self::$root)) {
            return new static($state);

        } elseif (!$state) {
            return $root;

        } else {
            trigger_error(sprintf(
                '%s is already set',
                __METHOD__
            ), E_USER_WARNING);
        }
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

    protected function resolve($value)
    {
        while ($operator = $this->getOperator($value)) {
            $value =
                '$' === $operator
                ? $value[$operator]()
                : $this->{'__operator_' . substr($operator, 1)}
                    ($value[$operator]);
        }

        return $value;
    }

    protected function isMagicProperty($property)
    {
        return isset($this->__property['$unresolved'][$property])
            or $this->isMagicPropertyCallback($property);
    }

    protected function getMagicProperty($property)
    {
        if (isset($this->__property['$unresolved'][$property])) {
            $value = $this->__property['$unresolved'][$property];

            if (1 === count($this->__property['$unresolved'])) {
                unset($this->__property['$unresolved']);

            } else {
                unset($this->__property['$unresolved'][$property]);
            }
        } else {
            $value = $this->getMagicPropertyCallback($property);
        }

        return $this->__property[$property] = $this->resolve($value);
    }

    protected function __operator_reuse($value)
    {
        if (isset(self::$root[$this->root_id])) {
            $out = self::$root[$this->root_id];

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

    protected function __operator_new($state)
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

        if ($class->instance instanceof self) {
            $class->instance->root_id = $this->root_id;
            $class->instance->__construct(array_merge([
                'namespace' => $this->namespace,
            ], $state));

        } else {
            if (isset($state['construct'])) {
                if ($class->constructor = $class->getConstructor()) {
                    $class->constructor->arguments = [];

                    foreach ((array)$state['construct'] as $value) {
                        $class->constructor->arguments[] =
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
                        $value = $this->resolve($value);
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
                        $this->{__FUNCTION__}(array_merge(
                            ['namespace' => $this->namespace], $state
                        ))
                    );

                    foreach ($intersect as $property => $ignored) {
                        unset($class->instance->$property);
                    }
                }
            }

            if (isset($class->constructor)) {
                $class->constructor->invokeArgs(
                    $class->instance,
                    $class->constructor->arguments
                );
            }
        }

        return $class->instance;
    }
}

