<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Database;

class Mongo
{
    use \NoFramework\Magic;

    protected $host = \MongoClient::DEFAULT_HOST;
    protected $username = false;
    protected $password = false;
    protected $connect = false;
    protected $readPreference = \MongoClient::RP_PRIMARY_PREFERRED;
    protected $readPreferenceTags = [];

    protected $options = [];

    protected function __property_db()
    {
        return 'test';
    }

    protected function __property_auth_db()
    {
        return $this->db;
    }

    protected function __property_connection()
    {
        $options = $this->options;

        if (false !== $this->username) {
            $options['username'] = $this->username;
        }

        if (false !== $this->password) {
            $options['password'] = $this->password;
        }

        $options['db'] = $this->auth_db;

        foreach ([
            'connect',
            'readPreference',
            'readPreferenceTags',
        ] as $property) {
            $options[$property] = $this->$property;
        }

        return new \MongoClient(
            'mongodb://' . implode(',', array_map(function ($host) {
                return trim($host);
            }, (array)$this->host)),
            $options
        );
    }

    protected function __property_dbo()
    {
        return $this->connection->selectDB($this->db);
    }

    public function __call($name, $arguments)
    {
        $command = &$arguments[0];

        return $this->command(array_replace(
            [$name => $this->popCollection($command, false)],
            $command
        ));
    }

    public function getGridFS($prefix = 'fs')
    {
        return $this->dbo->getGridFS($prefix ?: 'fs');
    }

    public function command($command)
    {
        $timeout = &$command['timeout'];
        unset($command['timeout']);

        $return = $this->dbo->command(
            $command,
            $timeout ? ['timeout' => $timeout] : []
        );

        if (isset($return['errmsg'])) {
            throw new \MongoException($return['errmsg']);
        }

        $lastErrorObject = &$return['lastErrorObject'];
        unset($return['lastErrorObject']);

        return $return + ($lastErrorObject ?: []);
    }

    /**
     * command:
     *   collection
     *   query
     *   fields
     *   readPreference
     *   readPreferenceTags
     *   sort
     *   skip
     *   limit
     *   hint
     *   tailable
     *   immortal
     *   awaitData
     *   partial
     *   snapshot
     *   timeout
     *   batchSize
     *
     * return:
     *   MongoCursor
     */
    public function find($command = [])
    {
        $collection = $this->popCollection($command);

        $query = &$command['query'];
        $fields = &$command['fields'];
        $readPreference = &$command['readPreference'];
        $readPreferenceTags = &$command['readPreferenceTags'];

        unset(
            $command['query'],
            $command['fields'],
            $command['readPreference'],
            $command['readPreferenceTags']
        );

        $cursor = $collection->find($query ?: [], $fields ?: []);

        if ($readPreference or $readPreferenceTags) {
            $cursor->setReadPreference(
                $readPreference ?: $this->readPreference,
                $readPreferenceTags ?: $this->readPreferenceTags
            );
        }

        foreach (array_filter($command) as $key => $value) {
            $cursor->$key($value);
        }

        return $cursor;
    }

    /**
     * command:
     *   collection
     *   query
     *   skip
     *   limit
     *
     * return:
     *   int
     */
    public function count($command = [])
    {
        $collection = $this->popCollection($command);

        $query = &$command['query'];
        $skip = &$command['skip'];
        $limit = &$command['limit'];

        return $collection->count($query ?: [], (int)$limit, (int)$skip);
    }

    /**
     * command:
     *   collection
     *   key
     *   query
     *   timeout
     *
     * return:
     *   array of distinct values
     */
    public function distinct($command = [])
    {
        return $this->command(array_replace(
            [
                'distinct' => $this->popCollection($command, false),
                'key' => '_id',
            ],
            $command
        ))['values'];
    }

    /**
     * command:
     *   collection
     *   set
     *   w
     *   fsync
     *   j
     *   wtimeout
     *   timeout
     *
     * return:
     *   inserted object
     */
    public function insert($command = [])
    {
        $collection = $this->popCollection($command);

        $set = &$command['set'];
        unset($command['set']);

        $collection->insert($set ?: [], $command);

        return $set;
    }

    /**
     * command:
     *   collection
     *   set
     *   continueOnError
     *   w
     *   fsync
     *   j
     *   wtimeout
     *   timeout
     *
     * return:
     *   inserted objects
     */
    public function batchInsert($command = [])
    {
        $collection = $this->popCollection($command);

        $set = &$command['set'];
        unset($command['set']);

        $collection->batchInsert($set, $command);

        return $set;
    }

    /**
     * command:
     *   collection
     *   query
     *   justOne
     *   w
     *   fsync
     *   j
     *   wtimeout
     *   timeout
     *
     * return:
     *   removed count
     */
    public function remove($command = [])
    {
        $collection = $this->popCollection($command);

        $query = &$command['query'];
        unset($query);

        return $collection->remove($query ?: [], $command)['n'];
    }

    /**
     * command:
     *   collection
     *   query
     *   upsert
     *   multiple
     *   replace
     *   w
     *   fsync
     *   j
     *   wtimeout
     *   timeout
     *   set
     *   inc
     *   mul
     *   rename
     *   unset
     *   setOnInsert
     *   min
     *   max
     *   currentDate
     *   bit
     *   addToSet
     *   pop
     *   pullAll
     *   pull
     *   pushAll
     *   push
     *
     * return:
     *   n
     *   nModified
     *   upserted
     *   updatedExisting
     */
    public function update($command = [])
    {
        $collection = $this->popCollection($command);

        $query = &$command['query'];
        $update = &$command['replace'];

        unset(
            $command['query'],
            $command['replace']
        );

        $options = array_intersect_key($command, array_flip([
            'multiple',
            'upsert',
            'w',
            'fsync',
            'j',
            'wtimeout',
            'timeout',
        ]));

        $options += [
            'multiple' => !$update,
            'upsert' => false,
        ];

        if ($update) {
            foreach ($update as $key => $value) {
                if (0 === strpos($key, '$')) {
                    throw new \MongoException(
                        'document to replace can\'t have $ fields'
                    );
                }
            }
        } else {
            foreach (array_diff_key($command, $options) as $key => $value) {
                $update['$' . $key] = $value;
            }
        }

        return $collection->update($query ?: [], $update, $options);
    }

    /**
     * command:
     *   collection
     *   query
     *   sort
     *   new
     *   fields
     *   upsert
     *   replace
     *   remove
     *   timeout
     *   set
     *   inc
     *   mul
     *   rename
     *   unset
     *   setOnInsert
     *   min
     *   max
     *   currentDate
     *   bit
     *   addToSet
     *   pop
     *   pullAll
     *   pull
     *   pushAll
     *   push
     *
     * return:
     *   n
     *   upserted
     *   updatedExisting
     *   value
     */
    public function findAndModify($command = [])
    {
        $collection = $this->popCollection($command, false);

        $out = array_intersect_key($command, array_flip([
            'query',
            'fields',
            'sort',
            'upsert',
            'new',
            'remove',
            'timeout',
        ]));

        $out += [
            'query' => [],
            'new' => true,
            'upsert' => false,
        ];

        $out['update'] = &$command['replace'];
        unset($command['replace']);

        if ($out['update']) {
            foreach ($out['update'] as $key => $value) {
                if (0 === strpos($key, '$')) {
                    throw new \MongoException(
                        'document to replace can\'t have $ fields'
                    );
                }
            }
        } else {
            foreach (array_diff_key($command, $out) as $key => $value) {
                $out['update']['$' . $key] = $value;
            }
        }
        
        return $this->command(array_replace(
            ['findAndModify' => $collection],
            $out
        ));
    }

    /**
     * command:
     *   collection
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
     *   createdCollectionAutomatically
     *   numIndexesBefore
     *   numIndexesAfter
     */
    public function ensureIndex($command = [])
    {
        $collection = $this->popCollection($command);

        $key = &$command['key'];
        unset($command['key']);

        return $collection->ensureIndex($key, $command);
    }

    public function listCollections()
    {
        return array_map(
            function ($collection) {
                return $collection->getName();
            },
            $this->dbo->listCollections()
        );
    }

    protected function popCollection(&$command, $is_object = true)
    {
        $out = &$command['collection'];
        unset($command['collection']);

        $out = $out ?: 'collection';

        return
            $is_object
            ? $this->dbo->selectCollection($out)
            : $out
        ;
    }
}

