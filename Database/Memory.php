<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Database;

class Memory implements \IteratorAggregate
{
    protected $data = [];

    protected $fields;
    protected $skip;
    protected $limit;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        foreach (array_slice(
            $this->data,
            $this->skip,
            $this->limit ?: null,
            true
        ) as $_id => $item) {
            yield $_id => $this->applyFields(
                array_replace(['_id' => $_id], $item),
                $this->fields
            );
        }
    }

    /**
     * command:
     *   query
     *   fields
     *   sort
     *   skip
     *   limit
     *
     * return:
     *   $this
     */
    public function find($command = [])
    {
        $query = &$command['query'];

        $out = new static($this->query($query));

        foreach (array_intersect_key($command, array_flip([
            'fields',
            'sort',
            'skip',
            'limit',
        ])) as $key => $value) {
            $out->$key($value);
        }

        return $out;
    }

    public function fields($fields)
    {
        $this->fields = $this->normalizeFields($fields);

        return $this;
    }

    public function sort($sort)
    {
        $pos = 0;

        foreach ($sort as $field => $order) {
            if (in_array($field, ['$natural', '_id'])) {
                ksort($this->data);

                if ($order < 0) {
                    $this->data = array_reverse($this->data, true);
                }

                $sort = array_slice($sort, 0, $pos, true);
                break;
            }

            $pos++;
        }

        if (!$sort) {
            return $this;
        }

        uasort($this->data, function ($less, $greater) use ($sort) {
            foreach ($sort as $field => $order) {
                if (isset($less[$field]) and isset($greater[$field])) {
                    if ($less[$field] !== $greater[$field]) {
                        return
                            $less[$field] < $greater[$field]
                            ? -$order
                            : $order
                        ;
                    }
                } elseif (isset($less[$field])) {
                    return $order;

                } elseif (isset($greater[$field])) {
                    return -$order;
                }
            }

            return 0;
        });

        return $this;
    }

    public function skip($skip)
    {
        $this->skip = $skip;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * command:
     *   key
     *   query
     *
     * return:
     *   array of distinct values
     */
    public function distinct($command = [])
    {
        $key = &$command['key'];
        $query = &$command['query'];

        if (!$key or '_id' === $key) {
            return array_keys($this->query($query));
        }

        $query[$key]['$exists'] = true;

        return array_values(array_unique(array_map(
            function ($item) use ($key) {
                return $item[$key];
            },
            $this->query($query)
        )));
    }

    /**
     * command:
     *   array:
     *     query
     *     skip
     *     limit
     *
     *   or boolean:
     *     foundOnly
     *
     * return:
     *   int
     */
    public function count($command = [])
    {
        if (!$command) {
            return count($this->data);
        }

        if (is_bool($command)) {
            $command = [
                'skip' => $this->skip,
                'limit' => $this->limit,
            ];
        }

        $query = &$command['query'];

        $count = count($this->query($query));

        if ($skip = &$command['skip']) {
            $count -= min($count, $skip);
        }

        if ($limit = &$command['limit']) {
            $count = min($count, $limit);
        }

        return $count;
    }

    /**
     * command:
     *   set
     *
     * return:
     *   inserted object
     */
    public function insert($command = [])
    {
        $set = &$command['set'];

        unset($set['_id']);
        $this->data[] = $set;
        end($this->data);
        $set['_id'] = key($this->data);

        return $set;
    }

    /**
     * command:
     *   set
     *
     * return:
     *   inserted objects
     */
    public function batchInsert($command = [])
    {
        $set = &$command['set'];

        foreach ($set as $key => $item) {
            unset($item['_id']);
            $this->data[] = $item;
            end($this->data);
            $set[$key]['_id'] = key($this->data);
        }

        return $set;
    }

    /**
     * command:
     *   query
     *   justOne
     *
     * return:
     *   removed count
     */
    public function remove($command = [])
    {
        $query = &$command['query'];
        $justOne = &$command['justOne'];

        $count = 0;

        foreach ($this->query($query) as $_id => $ignored) {
            unset($this->data[$_id]);
            $count++;

            if ($justOne) {
                break;
            }
        }

        return $count;
    }

    /**
     * command:
     *   query
     *   upsert
     *   multiple
     *   replace
     *   set
     *   inc
     *   min
     *   max
     *   rename
     *   unset
     *   setOnInsert
     *
     * return:
     *   n
     *   nModified
     *   upserted
     *   updatedExisting
     */
    public function update($command = [])
    {
        $query = &$command['query'];
        $upsert = &$command['upsert'];
        $replace = &$command['replace'];

        $multiple =
            array_replace(['multiple' => !$replace], $command)['multiple'];

        $return = [
            'n' => 0,
            'nModified' => 0,
            'updatedExistsing' => false,
        ];

        foreach ($this->query($query) as $_id => $item) {
            $new_item = $this->modify($item, $command);

            if ($item !== $new_item) {
                $this->data[$_id] = $new_item;
                $return['nModified']++;
            }

            $return['n']++;
            $return['updatedExistsing'] = true;

            if (!$multiple) {
                break;
            }
        }

        if (!$return['n'] and $upsert) {
            $new_item = $this->upsert($command);

            $return['n']++;
            $return['upserted'] = [[
                'index' => 0,
                '_id' => $new_item['_id'],
            ]];
        }

        return $return;
    }

    /**
     * command:
     *   query
     *   sort
     *   new
     *   fields
     *   upsert
     *   replace
     *   set
     *   inc
     *   min
     *   max
     *   rename
     *   unset
     *   setOnInsert
     *
     * return:
     *   n
     *   upserted
     *   updatedExisting
     *   value
     */
    public function findAndModify($command = [])
    {
        $query = &$command['query'];
        $sort = &$command['sort'];
        $new = array_replace(['new' => true], $command)['new'];

        $return = [
            'n' => 0,
            'updatedExistsing' => false,
            'value' => [],
        ];

        $found =
            $sort
            ? $this->find(['query' => $query, 'sort' => $sort])
            : $this->query($query)
        ;

        foreach ($found as $_id => $item) {
            $new_item = $this->modify($item, $command);
            $this->data[$_id] = $new_item;

            $return['value'] = $new ? $new_item : $item;
            $return['value']['_id'] = $_id;

            $return['n']++;
            $return['updatedExistsing'] = true;

            break;
        }

        if (!$return['n'] and $upsert = &$command['upsert']) {
            $new_item = $this->upsert($command);

            if ($new) {
                $return['value'] = $new_item;
            }

            $return['n']++;
            $return['upserted'] = ['_id' => $new_item['_id']];
        }

        if ($return['value'] and $fields = &$command['fields']) {
            $return['value'] = $this->applyFields(
                $return['value'],
                $this->normalizeFields($fields)
            );
        }

        return $return;
    }

    public function applyFields($item, $fields)
    {
        return
            current($fields)
            ? array_intersect_key($item, $fields)
            : array_diff_key($item, $fields)
        ;
    }

    public function normalizeFields($fields)
    {
        if (!$fields) {
            return [];
        }

        $count = count($fields);

        if (array_keys($fields) === range(0, $count - 1)) {
            return array_fill_keys($fields, true) + ['_id' => true];
        }

        if ($count_include = count(array_filter($fields))) {
            $fields += ['_id' => true];

            if (!$fields['_id']) {
                unset($fields['_id']);
                $count--;
            }

            if ($count !== $count_include) {
                throw new \InvalidArgumentException(
                    'Projection cannot have a mix of inclusion and exclusion'
                );
            }
        }

        return $fields;
    }

    protected function query($query = [])
    {
        $data = [];

        foreach ($this->data as $_id => $ignored) {
            $data[$_id] = &$this->data[$_id];
        }

        if (!$query) {
            return $data;
        }

        foreach ($query as $field => $match) {
            if (!$data) {
                return [];
            }

            if (!$this->hasCollation($match)) {
                $match = ['$in' => [$match]];
            }

            foreach ($data as $_id => $item) {
                foreach ($match as $collate => $right) {
                    if ('_id' === $field) {
                        $exists = true;
                        $left = $_id;

                    } else {
                        $exists = array_key_exists($field, $item);
                        $left = &$item[$field];
                    }

                    if (!$this->isMatch($collate, $exists, $left, $right)) {
                        unset($data[$_id]);
                    }
                }
            }
        }

        return $data;
    }

    protected function hasCollation($match) {
        if (is_array($match)) {
            foreach ($match as $collate => $ignored) {
                if (0 === strpos($collate, '$')) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isMatch($collate, $exists, $left, $right)
    {
        switch ($collate) {
            case '$lt': return $exists and $left < $right;
            case '$gt': return $exists and $left > $right;
            case '$lte': return $exists and $left <= $right;
            case '$gte': return $exists and $left >= $right;
            case '$in': return $exists and
                array_intersect((array)$left, (array)$right);
            case '$ne': case '$nin': return !$exists or
                !array_intersect((array)$left, (array)$right);
            case '$exists': return $exists xor !$right;
        }

        throw new \LogicException(sprintf(
            'Unknown collation \'%s\'',
            $collate
        ));
    }

    protected function modify($item, $command)
    {
        if ($replace = &$command['replace']) {
            unset($replace['_id']);
            return $replace;
        }

        if ($set = &$command['set']) {
            $item = $set + $item;
        }

        if ($inc = &$command['inc']) {
            foreach ($inc as $field => $right) {
                $left = &$item[$field];
                $left += $right;
            }
        }

        if ($min = &$command['min']) {
            foreach ($min as $field => $value) {
                if (
                    !array_key_exists($field, $item) or
                    $item[$field] > $value
                ) {
                    $item[$field] = $value;
                }
            }
        }

        if ($max = &$command['max']) {
            foreach ($max as $field => $value) {
                if (
                    !array_key_exists($field, $item) or
                    $item[$field] < $value
                ) {
                    $item[$field] = $value;
                }
            }
        }

        unset($item['_id']);

        if ($rename = &$command['rename']) {
            foreach ($rename as $from => $to) {
                if (array_key_exists($from, $item)) {
                    $item[$to] = $item[$from];
                    unset($item[$from]);
                }
            }
        }

        if ($unset = &$command['unset']) {
            $item = array_diff_key($item, $unset);
        }

        return $item;
    }

    protected function upsert($command)
    {
        $replace = &$command['replace'];
        $query = &$command['query'];
        $setOnInsert = &$command['setOnInsert'];

        return $this->insert([
            'set' => $replace ?: array_replace(
                $query
                ? $this->modify(
                    array_filter($query, function ($value) {
                        return !$this->hasCollation($value);
                    }),
                    $command
                )
                : [],

                $setOnInsert ?: []
            )
        ]);
    }
}

