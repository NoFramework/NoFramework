<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
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

    public function getIterator()
    {
        foreach ($this->data as $_id => $item) {
            yield $_id =>
                $this->orm
                ? $this->collection->item($item)
                : $item
            ;
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

