<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Storage;

class Mongo
{
    use \NoFramework\MagicProperties;
    use Command;

    protected $user = false;
    protected $password = false;
    protected $host = \MongoClient::DEFAULT_HOST;
    protected $name = 'test';
    protected $read_preference = \MongoClient::RP_PRIMARY_PREFERRED;
    protected $read_preference_tags = [];
    protected $is_connect = false;
    protected $write_concern = 1;

    /**
     * connectTimeoutMS
     * fsync
     * journal
     * replicaSet
     * socketTimeoutMS
     * ssl
     * wTimeoutMS
     */
    protected $options = [];

    protected function __property_auth_name()
    {
        return $this->name;
    }

    protected function __property_connection()
    {
        $auth = [];

        if (false !== $this->user) {
            $auth['username'] = $this->user;
        }

        if (false !== $this->password) {
            $auth['password'] = $this->password;
        }

        return new \MongoClient(
            'mongodb://' . implode(',', (array)$this->host),
            array_merge(
                $auth,
                [
                    'connect' => $this->is_connect,
                    'w' => $this->write_concern,
                    'db' => $this->auth_name,
                    'readPreference' => $this->read_preference,
                    'readPreferenceTags' => $this->read_preference_tags,
                ],
                array_intersect_key(
                    $this->options,
                    array_flip([
                        'connectTimeoutMS',
                        'fsync',
                        'journal',
                        'replicaSet',
                        'socketTimeoutMS',
                        'ssl',
                        'wTimeoutMS',
                    ])
                )
            )
        );
    }

    protected function __property_db()
    {
        return $this->connection->selectDB($this->name);
    }

    protected function __command($command)
    {
        $return = $this->db->command($command);

        if (isset($return['errmsg'])) {
            throw new \MongoException($return['errmsg']);
        }

        return array_merge(
            isset($return['lastErrorObject'])
            ? $return['lastErrorObject']
            : [],
            array_diff_key(
                $return,
                ['lastErrorObject' => 0]
            )
        );
    }

    protected function __command_collection($collection)
    {
        return $this->db->selectCollection($collection);
    }

    /**
     * options:
     *   where
     *   fields
     *   sort
     *   skip
     *   limit
     *   hint
     *   is_tailable
     *   is_immortal
     *   is_await_data
     *   is_partial
     *   is_exhaust
     *   is_snapshot
     *   timeout
     *   batch_size
     *   read_preference
     *   read_preference_tags
     *
     * return:
     *   MongoCursor
     */
    protected function __command_find($collection, $option)
    {
        $cursor = $this->collection($collection)->find(
            $option('where', []),
            $option('fields', [])
        );

        if ($sort = $option('sort')) {
            $cursor->sort($sort);
        }

        if ($skip = $option('skip')) {
            $cursor->skip($skip);
        }

        if ($limit = $option('limit')) {
            $cursor->limit($limit);
        }

        if ($index = $option('hint')) {
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

    /**
     * options:
     *   where
     *   skip
     *   limit
     *
     * return:
     *   int
     */
    protected function __command_count($collection, $option)
    {
        return $this->db->selectCollection($collection)->count(
            $option('where', []),
            $option('limit', 0),
            $option('skip', 0)
        );
    }

    /**
     * options:
     *   field
     *   where
     *
     * return:
     *   array of distinct values
     */
    protected function __command_distinct($collection, $option)
    {
        return $this->__command([
            'distinct' => $collection,
            'key' => $option('field'),
            'query' => $option('where', [])
        ])['values'];
    }

    protected function writeOptions($option)
    {
        return $option([
            'w',
            'fsync',
            'j',
            'wtimeout',
            'timeout',
        ]);
    }

    /**
     * options:
     *   set
     *   continueOnError
     *   writeOptions
     *
     * return:
     *   inserted object(s)
     */
    protected function __command_insert($collection, $option)
    {
        $set = $option('set');

        if (is_array($set) and array_keys($set) === range(0, count($set) - 1)) {
            $this->collection($collection)->batchInsert(
                $set,
                array_merge(
                    ['continueOnError' => $option('continueOnError', true)],
                    $this->writeOptions($option)
                )
            );
        } else {
            $this->collection($collection)->insert(
                $set,
                $this->writeOptions($option)
            );
        }

        return $set;
    }

    /**
     * options:
     *   where
     *   is_multiple
     *   is_isolated
     *   writeOptions
     *
     * return:
     *   removed count
     */
    protected function __command_remove($collection, $option)
    {
        $where = $option('where', []);
        $is_multiple = $option('is_multiple', true);

        if ($option('is_isolated') and $is_multiple) {
            $where['$isolated'] = 1;
        }

        return $this->collection($collection)->remove(
            $where,
            array_merge([
                'justOne' => !$is_multiple
            ], $this->writeOptions($option))
        )['n'];
    }

    /**
     * options:
     *   set
     *   inc
     *   rename
     *   setOnInsert
     *   unset
     *   bit
     *   addToSet
     *   pop
     *   pullAll
     *   pull
     *   pushAll
     *   push
     *   where
     *   is_upsert
     *   is_multiple
     *   is_isolated
     *   writeOptions
     *
     * return:
     *   n
     *   connectionId
     *   err
     *   ok
     *   updatedExisting
     *   upserted
     */
    protected function __command_update($collection, $option)
    {
        $command = [];
        
        foreach ($option([
            'inc',
            'rename',
            'setOnInsert',
            'set',
            'unset',
            'bit',
            'addToSet',
            'pop',
            'pullAll',
            'pull',
            'pushAll',
            'push'
        ]) as $key => $value) {
            $command['$' . $key] = $value;
        }

        $where = $option('where', []);
        $is_multiple = $option('is_multiple', true);

        if ($option('is_isolated') and $is_multiple) {
            $where['$isolated'] = 1;
        }

        return $this->collection($collection)->update(
            $where,
            $command,
            array_merge([
                'upsert' => $option('is_upsert', false),
                'multiple' => $is_multiple,
            ], $this->writeOptions($option))
        );
    }

    /**
     * options:
     *   set
     *   inc
     *   rename
     *   setOnInsert
     *   unset
     *   bit
     *   addToSet
     *   pop
     *   pull
     *   push
     *   where
     *   is_upsert
     *   timeout
     *   is_new
     *   fields
     *   sort
     *
     * return:
     *   n
     *   connectionId
     *   err
     *   ok
     *   updatedExisting
     *   upserted
     *   value
     */
    protected function __command_findAndModify($collection, $option)
    {
        $command = [];
        
        foreach ($option([
            'inc',
            'rename',
            'setOnInsert',
            'set',
            'unset',
            'bit',
            'addToSet',
            'pop',
            'pull',
            'push'
        ]) as $key => $value) {
            $command['$' . $key] = $value;
        }

        return $this->__command(
            array_merge([
                'findAndModify' => $collection,
                'query' => $option('where', []),
                'update' => $command,
                'new' => $option('is_new', true),
                'upsert' => $option('is_upsert')
            ], $option([
                'sort',
                'fields'
            ])),
            $option([
                'timeout'
            ])
        );
    }

    /**
     * options:
     *   set
     *   where
     *   is_upsert
     *   is_multiple
     *   is_isolated
     *   writeOptions
     *
     * return:
     *   n
     *   connectionId
     *   err
     *   ok
     *   updatedExisting
     *   upserted
     */
    protected function __command_replace($collection, $option)
    {
        $set = $option('set', []);

        foreach ($set as $field => $value) {
            if (0 === strpos($field, '$')) {
                throw new \MongoCursorException(
                    'document to replace can\'t have $ fields'
                );
            }
        }

        $where = $option('where', []);
        $is_multiple = $option('is_multiple', true);

        if ($option('is_isolated') and $is_multiple) {
            $where['$isolated'] = 1;
        }

        return $this->collection($collection)->update(
            $where,
            $set,
            array_merge([
                'upsert' => $option('is_upsert', false),
                'multiple' => $is_multiple,
            ], $this->writeOptions($option))
        );
    }

    /**
     * options:
     *   set
     *   where
     *   is_upsert
     *   timeout
     *   is_new
     *   fields
     *   sort
     *
     * return:
     *   n
     *   connectionId
     *   err
     *   ok
     *   updatedExisting
     *   upserted
     *   value
     */
    protected function __command_findAndReplace($collection, $option)
    {
        $set = $option('set', []);

        foreach ($set as $field => $value) {
            if (0 === strpos($field, '$')) {
                throw new \MongoCursorException(
                    'document to replace can\'t have $ fields'
                );
            }
        }

        return $this->__command(
            array_merge([
                'findAndModify' => $collection,
                'query' => $option('where', []),
                'update' => $set,
                'new' => $option('is_new', true),
                'upsert' => $option('is_upsert')
            ], $option([
                'sort',
                'fields'
            ])),
            $option([
                'timeout'
            ])
        );
    }

    /**
     * options:
     *   key
     *   w
     *   unique
     *   dropDups
     *   sparse
     *   expireAfterSeconds
     *   background
     *   name
     *   timeout
     *
     * return:
     *   n
     *   connectionId
     *   err
     *   ok
     */
    protected function __command_ensureIndex($collection, $option)
    {
        return $this->db->selectCollection($collection)->ensureIndex(
            $option('key'),
            $option([
                'w',
                'unique',
                'dropDups',
                'sparse',
                'expireAfterSeconds',
                'background',
                'name',
                'timeout'
            ])
        );
    }

    protected function __command_listCollections()
    {
        return array_map(
            function ($collection) {
                return $collection->getName();
            },
            $this->db->listCollections()
        );
    }
}

