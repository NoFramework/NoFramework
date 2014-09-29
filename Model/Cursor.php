<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Model;

class Cursor implements \IteratorAggregate
{
    protected $data;
    protected $mapper;
    protected $map;
    protected $key;
    protected $skip;
    protected $limit;

    public function __construct($data, $mapper = false)
    {
        $this->data = $data;
        $this->mapper = $mapper;
    }

    public function __call($name, $arguments)
    {
        $return = call_user_func_array([$this->data, $name], $arguments);

        return $return === $this->data ? $this : $return;
    }

    public function getIterator()
    {
        if (0 === $this->limit) {
            return;
        }

        $count = 0;

        foreach ($this->data as $_id => $item) {
            if ($count < $this->skip) {
                $count++;
                continue;
            }

            $key = $this->key ? $item[$this->key] : $_id;

            if ($this->map) {
                $item = $this->mapper->map($this->map, $item);
            }

            yield $key => $item;

            $count++;

            if ($this->limit and $count >= $this->skip + $this->limit) {
                break;
            }
        }
    }

    public function slice($skip = null, $limit = null)
    {
        if ($skip < 0 or $limit < 0) {
            $count = $this->count(true);

            if ($skip < 0) {
                $skip += $count;
            }

            $skip = $skip < 0 ? 0 : $skip;

            if ($limit < 0) {
                $limit += $count - $skip;
            }

            $limit = $limit < 0 ? 0 : $limit;
        }

        $this->skip = $skip;
        $this->limit = $limit;

        return $this;
    }

    public function map($map = 'Item')
    {
        $this->map = $map;

        return $this;
    }

    public function column($column)
    {
        return $this->map('column:' . $column);
    }

    public function key($key)
    {
        $this->key = $key;

        return $this;
    }

    public function one()
    {
        foreach ($this as $item) {
            return $item;
        }

        return false;
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }

    public function reduce($callable, $initial = null)
    {
        $result = $initial;

        foreach ($this as $item) {
            $result = $callable($result, $item);
        }

        return $result;
    }

    public function countDistinct($fields)
    {
        $out = [];

        $register = function ($field, $value) use (&$out) {
            $key = is_array($value) ? serialize($value) : (string)$value;
            $distinct = &$out[$field][$key];
            $distinct['value'] = $value;
            $count = &$distinct['count'];
            $count++;
        };

        foreach ($this->data as $item) {
            foreach ((array)$fields as $field) {
                $value = $this->mapper->map('column:' . $field, $item);

                if (
                    is_array($value) and
                    array_keys($value) === range(0, count($value) - 1)
                ) {
                    foreach ($value as $value_item) {
                        $register($field, $value_item);
                    }
                } else {
                    $register($field, $value);
                }
            }
        }

        return $out;
    }

    public function print_r($return = false)
    {
        $out = '';

        foreach ($this as $key => $item) {
            $out .= print_r(['key' => $key, 'item' => $item], $return);
        }

        return $return ? $out : true;
    }
}

