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

class Factory
{
    use Magic;

    protected function __property_this()
    {
        yield $this;
    }

    protected function __property_use()
    {
        return $this->{'$this'};
    }

    protected function __property_global()
    {
        return $this->{'$this'};
    }

    protected function __property_namespace()
    {
        return substr(static::class, 0, strrpos(static::class, '\\'));
    }

    public function __construct($state = [])
    {
        unset($state['this']);

        $this->setState(
            $this,
            $this->resolveState(array_replace(
                array_fill_keys(['use', 'global', 'namespace'], null),
                $state
            ))
        );
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, '__resolve_' . $name)) {
            return call_user_func_array(
                [$this, '__resolve_' . $name],
                $arguments
            );
        }

        trigger_error(sprintf(
            'Call to undefined method %s::%s()',
            static::class,
            $name
        ), E_USER_ERROR);
    }

    public function setState($object, $state)
    {
        $accessProperty = $this->accessProperty($object);

        if ($magic = $accessProperty('__property')) {
            $magic = [
                'property' => $magic,
                'value' => $magic->getValue($object),
            ];
        }

        $is_modify_magic = method_exists($object, '__modify');

        foreach ($state as $key => $value) {
            if ($is_modify_magic) {
                $object->__modify($key, $value, $this);
            }

            $default = $accessProperty($key);

            if (
                ($default or !$magic) and
                $value instanceof \Generator
            ){
                $value = $value->current();
            }

            if ($default) {
                $default->setValue($object, $value);

            } elseif ($magic and isset($value)) {
                $magic['value'][$key] = $value;

            } elseif ($magic) {
                unset($magic['value'][$key]);

            } elseif (isset($value)) {
                $object->$key = $value;

            } else {
                unset($object->$key);
            }
        }

        if ($magic) {
            $magic['property']->setValue($object, $magic['value']);
        }

        return $object;
    }

    protected function accessProperty($object)
    {
        $reflection = new \ReflectionClass($object);

        return function ($key) use ($reflection) {
            if ($reflection->hasProperty($key)) {
                $out = $reflection->getProperty($key);

                if (!$out->isStatic()) {
                    $out->setAccessible(true);

                    return $out;
                }
            }

            return false;
        };
    }

    protected function popResolver(&$value)
    {
        if (is_array($value)) {
            $new = reset($value);
            $out = key($value);

            if (0 === strpos($out, '$')) {
                $value = $new;

                return substr($out, 1);
            }
        }

        return false;
    }

    protected function resolveLater($resolver, $value, $as)
    {
        yield $this->{'__resolve_' . $resolver}(
            $value instanceof \Generator ? $value->current() : $value,
            $as
        );
    }

    protected function resolveState($state, $as = '')
    {
        foreach ($state as $key => $value) {
            $first = strtok($key, '.');

            if ($key !== $first) {
                unset($state[$key]);

                if ($last = strtok('')) {
                    $state[$first]['$new'][$last] = $value;

                } else {
                    $state[$first] = $value;
                }
            }
        }

        $as = $as ? rtrim($as, '.') . '.' : '';

        foreach ($state as $key => $value) {
            yield $key =>
                ($resolver = $this->popResolver($value))
                ? $this->resolveLater($resolver, $value, $as . $key)
                : $value
            ;
        }
    }

    protected function normalizeClass($class)
    {
        return
            $class
            ? (
                0 === strpos($class, '\\')
                ? substr($class, 1)
                : ($this->namespace ? $this->namespace . '\\' : '') . $class
            )
            : false
        ;
    }

    protected function popClass(&$state, $is_normalize = true)
    {
        $state = is_string($state) ? ['class' => $state] : $state;

        $class = &$state['class'];
        unset($state['class']);

        return $is_normalize ? $this->normalizeClass($class) : $class;
    }

    protected function camelCase($value)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }

    protected function isStrictName($name)
    {
        return preg_match('~^[a-z_]+[0-9a-z_]+$~', $name);
    }

    protected function autoNamespace($as, $class = false)
    {
        if (!$as or 0 === strpos($as, '.') or !$this->namespace) {
            return false;
        }

        return
            $this->namespace . '\\' .
            implode('\\', array_map([$this, 'camelCase'], explode('.', $as))) .
            ($class ? '\\' . $class : '')
        ;
    }

    protected function __resolve_new($value = null, $as = null)
    {
        $auto = $this->autoNamespace($as);

        $class =
            $this->popClass($value) ?:
            (class_exists($auto) ? $auto : get_class($this->use))
        ;

        if (is_a($class, self::class, true)) {
            $value['global'] = $this->{'$global'};
            $value['use'] = $this->{'$use'};

            return new $class($value + ['namespace' => $auto]);
        }

        return $this->setState(
            new $class,
            $this->resolveState($value, $as ?: '.')
        );
    }

    protected function __resolve_newRoot($value = null)
    {
        $class = $this->popClass($value, false);
        $value['global'] = $this->{'$global'};

        return $class ? new $class($value) : new self($value);
    }

    protected function __resolve_use($value = null)
    {
        $object = $this->use;

        if ($value) {
            foreach (explode('.', $value) as $key) {
                $object = $object->$key;
            }
        }

        return $object;
    }

    protected function __is_property($name)
    {
        return $this->isStrictName($name);
    }

    protected function __property($name)
    {
        return $this->new(null, $name);
    }
}

