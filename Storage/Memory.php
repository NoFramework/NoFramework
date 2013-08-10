<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Storage;

class Memory implements \NoFramework\Storage
{
    public $data = [];

    public function find($collection, $where = [], $fields = [], $sort = [],
        $skip = 0, $limit = 0, $options = [])
    {
        if (!isset($this->data[$collection])) {
            return;
        }

        $data = ($where or $fields)
            ? $this->filterCollection($collection, $where, $fields)
            : $this->data[$collection];

        if ($sort) {
            if ($data instanceof \Traversable) {
                $data = iterator_to_array($data);
            }

            uasort($data, function ($less, $greater) use ($sort) {
                foreach ($sort as $field => $order) {
                    if (isset($less[$field]) and isset($greater[$field])) {
                        if ($less[$field] === $greater[$field]) {
                            continue;
                        } else {
                            return ($less[$field] < $greater[$field])
                                ? -$order
                                : $order;
                        }
                    } elseif (isset($less[$field])) {
                        return $order;
                    } elseif (isset($greater[$field])) {
                        return -$order;
                    } else {
                        continue;
                    }
                }

                return 0;
            });
        }

        $count = 0;

        foreach ($data as $_id => $item) {
            $count++;

            if ($count > $skip) {
                yield $_id => $item;
            }

            if ($limit and $count >= $skip + $limit) {
                break;
            }
        }
    }

    public function count($collection, $where = [], $options = [])
    {
        if (!isset($this->data[$collection])) {
            return 0;
        }

        $data = $where
            ? $this->filterCollection($collection, $where, false)
            : $this->data[$collection];

        return count($data);
    }

    public function update($collection, $set, $where = [], $options = [])
    {
        if (isset($set['_id'])) {
            throw new \InvalidArgumentException(
                'Redefining _id is not currently available'
            );
        }

        $option = function ($option) use ($options) {
            return isset($options[$option]) ? $options[$option] : null;
        };

        $options = array_merge([
            'multiple' => !$option('is_replace')
        ], $options);

        $n = 0;

        if (isset($this->data[$collection])) {
            $data = $where
                ? $this->filterCollection($collection, $where, false)
                : $this->data[$collection];

            foreach ($data as $_id => $ignored) {
                if ($option('is_replace')) {
                    $this->data[$collection][$_id] = $set;

                } else {
                    foreach ($set as $field => $value) {
                        $this->data[$collection][$_id][$field] = $value;
                    }
                }

                $n++;

                if (!$option('multiple')) {
                    break;
                }
            }
        }

        if (!$n and $option('upsert')) {
            return $this->__named_insert(
                $collection,
                $option('is_replace') ? $set : array_merge($set, $where)
            );
        }
        
        return [
            'n' => $n,
            'updatedExisting' => $n > 0,
        ];
    }

    public function remove($collection, $where = [], $fields = [],
        $options = [])
    {
        if (isset($fields['_id'])) {
            throw new \InvalidArgumentException(
                'Removing _id is not currently available'
            );
        }

        $option = function ($option) use ($options) {
            return isset($options[$option]) ? $options[$option] : null;
        };

        $options = array_merge([
            'multiple' => true
        ], $options);

        $n = 0;

        if (isset($this->data[$collection])) {
            $data = $where
                ? $this->filterCollection($collection, $where, false)
                : $this->data[$collection];

            foreach ($data as $_id => $ignored) {
                if ($fields) {
                    foreach ((array)$fields as $field) {
                        unset($this->data[$collection][$_id][$field]);
                    }
                } else {
                    unset($this->data[$collection][$_id]);
                }

                $n++;

                if (!$option('multiple')) {
                    break;
                }
            }
        }
        
        return $fields
            ? [
                'n' => $n,
                'updatedExisting' => $n > 0
            ]
            : compact('n');
    }

    public function insert($collection, $set, $options = [])
    {
        if (!isset($set['_id'])) {
            $this->data[$collection][] = $set;
            $_id = $this->endKey($this->data[$collection]);

        } elseif (!isset($this->data[$collection][$set['_id']])) {
            $_id = $set['_id'];
            unset($set['_id']);
            $this->data[$collection][$_id] = $set;

        } else {
            throw new \RuntimeException(sprintf(
                'Duplicate key \'%s\' for collection \'%s\'',
                $set['_id'],
                $collection
            ));
        }

        return [
            'n' => 1,
            'updatedExisting' => false,
            'upserted' => $_id
        ];
    }

    public function insertIgnore($collection, $set, $key = [], $options = [])
    {
        $this->splitKey($set, $key, false);

        return array_merge(
            compact('key'),
            $this->findId($collection, $key)
                ? [
                    'n' => 0,
                    'updatedExisting' => false
                ]
                : $this->__named_insert($collection, $set)
        );
    }

    public function insertOrReplace($collection, $set, $key = [],
        $options = [])
    {
        $this->splitKey($set, $key, false);

        $return = $this->__named_update(
            $collection,
            $set,
            $key,
            array_merge([
                'upsert' => true,
                'is_replace' => true,
            ], $options)
        );

        $return['key'] = $key;

        return $return;
    }

    public function replaceExisting($collection, $set, $key = [],
        $options = [])
    {
        $this->splitKey($set, $key, false);

        $return = $this->__named_update(
            $collection,
            $set,
            $key,
            array_merge([
                'is_replace' => true,
            ], $options)
        );

        $return['key'] = $key;

        return $return;
    }

    public function insertOrUpdate($collection, $set, $key = [],
        $insert_only = [], $options = [])
    {
        $document = $set;

        $this->splitKey($set, $key);

        foreach ($insert as $field) {
            unset($set[$field]);
        }

        if ($set) {
            $return = $this->__named_update(
                $collection,
                $set,
                $key,
                array_merge([
                    'multiple' => false
                ], $options)
            );

            if (!$return['updatedExisting']) {
                $return = $this->__named_insert($collection, $document);
            }
        } else {
            $return = $this->findId($collection, $key)
                ? [
                    'n' => 0,
                    'updatedExisting' => false
                ]
                : $this->__named_insert($collection, $document);
        }

        $return['key'] = $key;

        return $return;
    }

    public function updateExisting($collection, $set, $key = [], $options = [])
    {
        $this->splitKey($set, $key);

        if (!$set) {
            throw new \InvalidArgumentException('Nothing to set');
        }

        $return = $this->__named_update(
            $collection,
            $set,
            $key,
            array_merge([
                'multiple' => false
            ], $options)
        );

        $return['key'] = $key;

        return $return;
    }

    public function drop($collection, $options = [])
    {
        unset($this->data[$collection]);
        return [
            'ns' => $collection
        ];
    }

    public function fromUnixTimestamp($timestamp)
    {
        return $timestamp;
    }

    public function toUnixTimestamp($timestamp)
    {
        return $timestamp;
    }

    protected function endKey($array) {
        end($array);
        return key($array);
    }

    protected function splitKey(&$set, &$key, $is_unset = true)
    {
        $fields = (array)$key ?: ['_id'];
        $key = isset($set['_id'])
            ? ['_id' => $set['_id']]
            : [];

        foreach ($fields as $field) {
            if (!isset($set[$field])) {
                throw new \InvalidArgumentException(sprintf(
                    'Field \'%s\' is not set for set %s',
                    $field,
                    print_r($set, true)
                ));
            }

            $key[$field] = $set[$field];

            if ($is_unset) {
                unset($set[$field]);
            }
        }
    }

    protected function findId($collection, $where)
    {
        foreach ($this->filterCollection(
            $collection,
            $where,
            false
        ) as $_id => $ignored) {
            return $_id;
        }

        return false;
    }

    protected function isMatched($collation, $left, $right, $exists)
    {
        switch ($collation) {
            case '=': return $exists and $left === $right;
            case '<>': return !$exists or $left !== $right;
            case '<': return $exists and $left < $right;
            case '>': return $exists and $left > $right;
            case '<=': return $exists and $left <= $right;
            case '>=': return $exists and $left >= $right;
            case '$in': return $exists and in_array($left,
                (array)$right, true);
            case '$nin': return !$exists or !in_array($left,
                (array)$right, true);
            case '$exists': return $exists xor !$right;
        }

        throw new \RuntimeException(sprintf(
            'Unknown collation \'%s\'',
            $collation
        ), E_USER_WARNING);
    }

    protected function filterCollectionById($collection, $ids)
    {
        foreach ($ids as $_id) {
            if (isset($this->data[$collection][$_id])) {
                yield $_id => $this->data[$collection][$_id];
            }
        }
    }

    protected function filterCollection($collection, $where, $fields = [])
    {
        if (!isset($this->data[$collection])) {
            return;
        }

        $data = $this->data[$collection];

        if (isset($where['_id'])) {
            $_id = [];

            if (!is_array($where['_id'])) {
                $where['_id'] = ['=' => $where['_id']];
            }

            foreach ($where['_id'] as $collation => $collation_value) {
                switch ($collation) {
                    case '=':
                        $_id[] = $collation_value;
                        unset($where['_id'][$collation]);
                        break;
                    case '$in':
                        $_id = array_merge($_id, (array)$collation_value);
                        unset($where['_id'][$collation]);
                        break;
                    case '$exists':
                        unset($where['_id'][$collation]);
                        break;
                }
            }

            if ($_id) {
                $data = $this->filterCollectionById($collection, $_id);
            }

            if (!$where['_id']) {
                unset($where['_id']);
            }
        }

        $where_fields = array_keys($where);

        foreach ($data as $_id => $item) {
            $item_values = $this->getItemValues($item, $where_fields);

            if (isset($where['_id'])) {
                $item_values['_id'] = $_id;
            }

            $is_matched = true;

            foreach ($where as $field => $value) {
                $exists = array_key_exists($field, $item_values);

                if (!is_array($value)) {
                    $value = ['=' => $value];
                }

                foreach ($value as $collation => $collation_value) {
                    $is_matched1 = $this->isMatched(
                        $collation,
                        $exists ? $item_values[$field] : null,
                        $collation_value,
                        $exists
                    );

                    $is_matched &= $is_matched1;
                }
            }

            if ($is_matched) {
                if ($fields) {
                    $item = $this->getItemValues($item, $fields);

                    if (in_array('_id', $fields, true)) {
                        $item['_id'] = $_id;
                    }
                } elseif (false === $fields) {
                    $item = false;
                }

                yield $_id => $item;
            }
        }
    }

    protected function getItemValues($item, $fields)
    {
        $return = [];

        foreach ($fields as $field) {
            if (isset($item[$field])) {
                $return[$field] = $item[$field];
            }
        }

        return $return;
    }
}

