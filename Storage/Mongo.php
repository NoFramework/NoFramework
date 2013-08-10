<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Storage;

class Mongo implements \NoFramework\Storage
{
    use \NoFramework\MagicProperties;

    protected $username = false;
    protected $password = false;
    protected $host = \MongoClient::DEFAULT_HOST;
    protected $database = 'test';
    protected $read_preference = \MongoClient::RP_PRIMARY_PREFERRED;
    protected $read_preference_tags = [];
    protected $write_concern = 1;
    protected $options = [];

    protected function __property_connection()
    {
        $auth = [];

        if (false !== $this->username) {
            $auth['username'] = $this->username;
        }

        if (false !== $this->password) {
            $auth['password'] = $this->password;
        }

        return new \MongoClient(
            'mongodb://' . implode(',', (array)$this->host),
            array_merge([
                'connect' => true,
                'db' => $this->database,
                'readPreference' => $this->read_preference,
                'readPreferenceTags' => $this->read_preference_tags,
                'w' => $this->write_concern,
            ], $auth, $this->options)
        );
    }

    protected function __property_db()
    {
        return $this->connection->selectDB($this->database);
    }

    public function find($collection, $where = [], $fields = [], $sort = [],
        $skip = 0, $limit = 0, $options = [])
    {
        $option = function ($option) use ($options) {
            return isset($options[$option]) ? $options[$option] : null;
        };

        $cursor = $this->db->selectCollection($collection)->find(
            $this->normalizeWhere($where),
            $fields
        );

        if ($sort) {
            $cursor->sort($sort);
        }

        if ($skip) {
            $cursor->skip($skip);
        }

        if ($limit) {
            $cursor->limit($limit);
        }

        if ($index = $option('index')) {
            $cursor->hint($index);
        }

        if ($option('is_tailable')) {
            $cursor->tailable();
        }

        if ($option('is_immortal')) {
            $cursor->immortal();
        }

        if ($option('is_await_data')) {
            $cursor->awaitData();
        }

        if ($option('is_partial')) {
            $cursor->partial();
        }

        #if ($option('is_exhaust')) {
        #    @$cursor->setFlag(6);
        #}

        if ($option('is_snapshot')) {
            $cursor->snapshot();
        }

        if ($timeout = $option('timeout')) {
            $cursor->timeout($timeout);
        }

        if ($batch_size = $option('batch_size')) {
            $cursor->batchSize($batch_size);
        }

        $read_preference = $option('read_preference');
        $read_preference_tags = $option('read_preference_tags');

        if ($read_preference or $read_preference_tags) {
            $cursor->setReadPreference(
                $read_preference ?: $this->read_preference,
                $read_preference_tags ?: $this->read_preference_tags
            );
        }

        return $cursor;
    }

    public function count($collection, $where = [], $options = [])
    {
        $option = function ($option) use ($options) {
            return isset($options[$option]) ? $options[$option] : null;
        };

        $return = $this->db->selectCollection($collection)->count(
            $this->normalizeWhere($where),
            $option('limit'),
            $option('skip')
        );

        return $return;
    }

    public function update($collection, $set, $where = [], $options = [])
    {
        $option = function ($option) use ($options) {
            return isset($options[$option]) ? $options[$option] : null;
        };

        $is_replace = true;

        foreach ($set as $operation => $ignored) {
            if (0 === strpos($operation, '$')) {
                $is_replace = false;
                break;
            }
        }

        if ($is_replace and !$option('is_replace')) {
            $set = ['$set' => $set];
            $is_replace = false;
        }

        unset($options['is_replace']);

        return $this->db->selectCollection($collection)->update(
            $this->normalizeWhere($where),
            $set,
            array_merge([
                'w' => $this->write_concern,
                'multiple' => !$is_replace,
            ], $options)
        );
    }

    public function remove($collection, $where = [], $fields = [],
        $options = [])
    {
        $multiple = isset($options['multiple']) ? $options['multiple'] : true;
        unset($options['multiple']);

        if ($fields) {
            return $this->__named_update(
                $collection,
                ['$unset' => array_fill_keys((array)$fields, true)],
                $where,
                array_merge(compact('multiple'), $options)
            );
        } else {
            return $this->db->selectCollection($collection)->remove(
                $this->normalizeWhere($where),
                array_merge([
                    'w' => $this->write_concern,
                    'justOne' => !$multiple
                ], $options)
            );
        }
    }

    public function insert($collection, $set, $options = [])
    {
		$return = $this->db->selectCollection($collection)->insert(
            $set,
            array_merge([
                'w' => $this->write_concern,
            ], $options)
        );

        $return['updatedExisting'] = false;
        $return['n'] = 1;

        if (isset($set['_id'])) {
            $return['upserted'] = $set['_id'];
        }

        return $return;
    }

    public function insertIgnore($collection, $set, $key = [], $options = [])
    {
        $this->splitKey($set, $key);

        $return = $this->__named_update(
            $collection,
            ['$setOnInsert' => $set ?: $key],
            $key,
            array_merge([
                'upsert' => true,
                'multiple' => false
            ], $options)
        );

        $return['key'] = $key;
        $return['updatedExisting'] = false;

        if (!isset($return['upserted'])) {
            $return['n'] = 0;
        }

        return $return;
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
        $this->splitKey($set, $key);

        $setOnInsert = [];
        foreach ($insert as $field) {
            if (isset($set[$field])) {
                $setOnInsert[$field] = $set[$field];
                unset($set[$field]);
            }
        }

        $operation = [];

        if ($set) {
            $operation['$set'] = $set;
        }

        if ($setOnInsert) {
            $operation['$setOnInsert'] = $setOnInsert;
        }

        if (!$operation) {
            $operation['$setOnInsert'] = $key;
        }

        $return = $this->__named_update(
            $collection,
            $operation,
            $key,
            array_merge([
                'upsert' => true,
                'multiple' => false
            ], $options)
        );

        $return['key'] = $key;

        if (!isset($operation['$set'])) {
            $return['updatedExisting'] = false;

            if (!isset($return['upserted'])) {
                $return['n'] = 0;
            }
        }

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
        return $this->db->dropCollection($collection);
    }

    public function fromUnixTimestamp($timestamp)
    {
        return new \MongoDate($timestamp);
    }

    public function toUnixTimestamp($timestamp)
    {
        return $timestamp->sec;
    }

    public function ensureIndex($collection, $key, $options = [])
    {
        return $this->db->selectCollection($collection)->ensureIndex(
            $key,
            array_merge([
                'w' => $this->write_concern,
            ], $options)
        );
    }

    protected function normalizeWhere($where)
    {
        $return = [];

        foreach ((array)$where as $field => $value) {
            if (is_array($value)
                and array_keys($value) === range(0, count($value) - 1)) {
                $value = ['$in' => $value];

            } elseif (is_array($value)) {
                foreach ($value as $collation => $collation_value) {
                    switch ($collation) {
                        case '=':
                            $value = $collation_value;
                            break 2;
                        case '<':
                            unset($value[$collation]);
                            $value['$lt'] = $collation_value;
                            break;
                        case '>':
                            unset($value[$collation]);
                            $value['$gt'] = $collation_value;
                            break;
                        case '<=':
                            unset($value[$collation]);
                            $value['$lte'] = $collation_value;
                            break;
                        case '>=':
                            unset($value[$collation]);
                            $value['$gte'] = $collation_value;
                            break;
                        case '<>':
                            unset($value[$collation]);
                            $value['$ne'] = $collation_value;
                            break;
                    }
                }
            }

            $return[$field] = $value;
        }

        return $return;
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
}

