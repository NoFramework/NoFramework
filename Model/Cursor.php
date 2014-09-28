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
        foreach ($this->data as $_id => $item) {
            $key = $this->key ? $item[$this->key] : $_id;

            if ($this->map) {
                $item = $this->mapper->map($this->map, $item);
            }

            yield $key => $item;
        }
    }

    public function map($map = 'Item')
    {
        $this->map = $map;

        return $this;
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
}

