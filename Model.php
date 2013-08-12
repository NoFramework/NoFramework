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
    protected $collection;
    protected $propagate = ['storage'];
    protected $separator = '.';
    protected $key;
    protected $is_replace = false;
    protected $is_upsert = true;
    protected $on_insert = [
        'create_ts' => '$now',
    ];
    protected $on_update = [
        'update_ts' => '$now',
    ];

    protected function __property_storage()
    {
        return new Storage\Memory;
    }

    protected function __property_timezone_offset()
    {
        return (new \DateTime)->getOffset();
    }

    protected function __operator_new($state = null, $id = null)
    {
        $collection = (array)$id;
        $collection =
            ($this->collection ? $this->collection . $this->separator : '') .
            array_pop($collection);

        return parent::__operator_new(array_merge(
            compact('collection'),
            is_string($state) ? ['class' => $state] : (array)$state
        ), $id);
    }

    protected function convertOnRead($item)
    {
        foreach ($item as $field => $value) {
            if ('_ts' === substr($field, -3)) {
                $item[$field] = $this->storage->toUnixTimestamp($value)
                    + $this->timezone_offset;
            }
        }

        return $item;
    }

    protected function convertOnWrite($set, $now)
    {
        foreach ($set as $field => $value) {
            if ('_ts' === substr($field, -3)) {
                if ('$now' === $value) {
                    $value = $now;
                }

                $set[$field] = $this->storage->fromUnixTimestamp(
                    $value - $this->timezone_offset
                );
            }
        }

        return $set;
    }

    public function find($options = [])
    {
        $arguments = [$this->collection];

        foreach ([
            'where' => [],
            'fields' => [],
            'sort' => [],
            'skip' => 0,
            'limit' => 0,
        ] as $parameter => $default) {
            $arguments[] = isset($options[$parameter])
            ? $options[$parameter]
            : $default;

            unset($options[$parameter]);
        }

        if ($options) {
            $arguments[] = $options;
        }

        foreach (call_user_func_array([$this->storage, 'find'], $arguments) as
            $_id => $item) {

            yield $_id => $this->convertOnRead($item);
        }
    }

    public function count($where = [], $options = [])
    {
        return $this->storage->count($this->collection, $where, $options);
    }

    public function insert($set, $now = null)
    {
        if (!$now) {
            $now = time();
        }

        $result = ($this->key or isset($set['_id']))
            ? $this->storage->insertIgnore(
                $this->collection,
                $this->convertOnWrite($set, $now),
                $this->key
            )
            : $this->storage->insert(
                $this->collection,
                $this->convertOnWrite($set, $now)
            );

        return isset($result['upserted']) ? $result['upserted'] : false;
    }

    public function update($set, $where = [], $options = [])
    {
        return $this->storage->update(
            $this->collection,
            $set,
            $where,
            $options
        );
    }

    public function remove($where = [], $fields = [])
    {
        return $this->storage->remove(
            $this->collection,
            $where,
            $fields
        );
    }
    
    public function save($set, $options = [])
    {
        if ($this->is_replace) {
            if ($this->is_upsert) {
                $this->storage->insertOrReplace(
                    $this->collection,
                    $set,
                    $this->key
                );

            } else {
                $this->storage->replaceExisting(
                    $this->collection,
                    $set,
                    $this->key
                );
            }
        } else {
            if ($this->is_upsert) {
                $this->storage->insertOrUpdate(
                    $this->collection,
                    $set,
                    $this->key,
                    array_keys((array)$this->on_insert)
                );
            } else {
                $this->updateExisting(
                    $this->collection,
                    $set,
                    $this->key
                );
            }
        }
    }
}

