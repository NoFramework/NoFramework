<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Model extends Factory
{
    protected $storage;
    protected $collection;
    protected $propagate = 'storage';
    protected $separator = '.';

    protected function __operator_new($state = null, $id = null)
    {
        $collection = (array)$id;
        $collection =
            ($this->collection ? $this->collection . $this->separator : '') .
            array_pop($collection);

        return parent::__operator_new(array_merge(
            compact('collection'),
            $state
        ), $id);
    }

    public function __call($method, $argument)
    {
        return $this->storage->__call($method, array_merge(
            [['collection' => $this->collection]],
            $argument
        ));
    }
}

