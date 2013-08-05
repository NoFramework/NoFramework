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

    protected function __operator_new($state = null, $id = null)
    {
        $collection = (array)$id;
        $collection =
            ($this->collection ? $this->collection . '.' : '') .
            array_pop($collection);

        return parent::__operator_new(array_merge([
            'storage' => $this->storage,
            'collection' => $collection
        ], $state), $id);
    }

    public function __call($method, $argument)
    {
        return $this->storage->__call($method, array_merge(
            [['collection' => $this->collection]],
            $argument
        ));
    }

    public function runEach($commands, $closure = null, $is_try_catch = false)
    {
        foreach ($commands as &$command) {
            if (!isset($command['collection'])) {
                $command['collection'] = $this->collection;
            }
        }

        $this->storage->runEach($commands, $closure, $is_try_catch);

        return $this;
    }
}

