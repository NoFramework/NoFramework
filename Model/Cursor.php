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
    protected $collection;
    protected $orm;

    public function __call($name, $arguments)
    {
        $return = call_user_func_array([$this->data, $name], $arguments);

        return $return === $this->data ? $this : $return;
    }

    protected function each($item)
    {
        return $this->orm ? $this->collection->item($item) : $item;
    }

    public function getIterator()
    {
        foreach ($this->data as $_id => $item) {
            yield $_id => $this->each($item);
        }
    }

    public function orm($orm = true)
    {
        $this->orm = $orm;

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

