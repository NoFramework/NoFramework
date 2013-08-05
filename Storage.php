<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

abstract class Storage
{
    use NamedParameters;

    abstract protected function __named_find($collection, $where = [],
        $fields = [], $sort = [], $skip = 0, $limit = 0, $options = []);

    abstract protected function __named_count($collection, $where = [],
        $options = []);

    abstract protected function __named_insert($collection, $set,
        $options = []);

    abstract protected function __named_update($collection, $set, $where = [],
        $options = []);

    abstract protected function __named_remove($collection, $where = [],
        $fields = [], $options = []);

    abstract protected function __named_insertIgnore($collection, $set,
        $key = [], $options = []);

    abstract protected function __named_replaceExisting($collection, $set,
        $key = [], $options = []);

    abstract protected function __named_insertOrReplace($collection, $set,
        $key = [], $options = []);

    abstract protected function __named_updateExisting($collection, $set,
        $key = [], $options = []);

    abstract protected function __named_insertOrUpdate($collection, $set,
        $key = [], $insert = [], $options = []);

    abstract protected function __named_drop($collection, $options = []);

    abstract public function fromUnixTimestamp($timestamp);

    abstract public function toUnixTimestamp($timestamp);

    protected function __named_run($method, $parameter = [])
    {
        return $this->$method($parameter);
    }

    public function runEach($commands, $closure = null, $is_try_catch = false)
    {
        foreach ($commands as $command) {
            if ($is_try_catch) {
                try {
                    $result = $this->run($command);
                } catch (\Exception $e) {
                    $result = $e;
                }
            } else {
                $result = $this->run($command);
            }

            if ($closure) {
                $closure($result);
            }
        }

        return $this;
    }

    protected function isNumericArray($object)
    {
        return (is_array($object)
            and array_keys($object) === range(0, count($object) - 1));
    }
}

