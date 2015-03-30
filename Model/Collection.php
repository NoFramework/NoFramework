<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Model;

use NoFramework\Database\Memory as Memory;

class Collection extends \NoFramework\Factory
{
    protected function __property_name() {}

    protected function __property_db()
    {
        return new Memory;
    }

    protected function __property_fs()
    {
        return $this->db->getGridFS($this->name);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, '__resolve_' . $name)) {
            return call_user_func_array(
                [$this, '__resolve_' . $name],
                $arguments
            );
        }

        $command = &$arguments[0];
        $command['collection'] = $this->name;

        return $this->db->$name($command);
    }

    public function cursor($data, $mapper = null)
    {
        return new Cursor($data, $mapper ?: $this);
    }

    public function map($map, $value)
    {
        if (is_callable($map)) {
            return call_user_func($map, $value, $this);
        }

        if (is_string($map)) {
            if (0 === strpos($map, 'column:')) {
                $fields = explode('.', substr($map, strlen('column:')));

                foreach ($fields as $field) {
                    if (!isset($value[$field])) {
                        return null;
                    }

                    $value = $value[$field];
                }

                return $value;
            }

            $map = ['class' => $map];
        }

        return $this->item($map + $value);
    }

    public function item($state = [])
    {
        $class = $this->popClass($state) ?: $this->normalizeClass('Item');
        $state['$collection'] = $this->{'$this'};

        return $this->setState(
            class_exists($class) ? new $class : new Item,
            $state
        );
    }

    public function find($command = [])
    {
        $map = &$command['map'];
        unset($command['map']);

        $key = &$command['key'];
        unset($command['key']);

        $data = &$command['data'];
        unset($command['data']);

        $db = $data ? new Memory($data) : $this->db;

        $command['collection'] = $this->name;

        $out = $this->cursor($db->find($command));

        if ($map) {
            $out->map($map);
        }

        if ($key) {
            $out->key($key);
        }

        return $out;
    }

    public function findOne($command = [])
    {
        $command['limit'] = 1;
        unset($command['key']);

        $virtual = &$command['virtual'];
        unset($command['virtual']);

        $out = $this->find($command)->one();

        if (false === $out and $virtual) {
            $query = &$command['query'];

            $out =
                $query
                ? array_filter($query, function ($match) {
                    if (is_array($match)) {
                        foreach ($match as $collate => $ignored) {
                            if (0 === strpos($collate, '$')) {
                                return false;
                            }
                        }
                    }

                    return true;
                })
                : []
            ;

            if ($map = &$command['map']) {
               $out = $this->map($map, $out);
            }
        }

        return $out;
    }

    public function insert($command = [])
    {
        $map = &$command['map'];
        unset($command['map']);

        $command['collection'] = $this->name;

        $out = $this->db->insert($command);

        if ($map) {
            $out = $this->map($map, $out);
        }

        return $out;
    }

    public function findAndModify($command = [])
    {
        $map = &$command['map'];
        unset($command['map']);

        $command['collection'] = $this->name;

        $out = $this->db->findAndModify($command);

        $value = &$out['value'];

        if ($map) {
            $value = $this->map($map, $value);
        }

        return (object)$out;
    }

    public function distinct($command = [])
    {
        $command = is_string($command) ? ['key' => $command] : $command;
        $command['collection'] = $this->name;

        return $this->db->distinct($command);
    }

    public function getIndexes($command = [])
    {
        return iterator_to_array($this->findIndexes($command));
    }

    public function getIndex($command = [])
    {
        $command['limit'] = 1;

        foreach ($this->findIndexes($command) as $index) {
            return $index;
        }

        return false;
    }

    public function normalizeFields($fields)
    {
        return (new Memory)->normalizeFields($fields);
    }

    public function patchFields($fields, $patch)
    {
        $patch = is_string($patch) ? [$patch] : $patch;

        if (array_keys($patch) === range(0, count($patch) - 1)) {
            $patch = array_fill_keys($patch, true);
        }

        $include = array_filter($patch);
        $exclude = array_diff_key($patch, $include);

        $fields = $this->normalizeFields($fields);

        if (current($fields)) {
            $fields = array_diff_key($fields, $exclude) + $include;

            if (!$fields) {
                throw new \InvalidArgumentException(
                    'Projection cannot be patched to empty'
                );
            }

            $fields += ['_id' => false];

            if ($fields['_id'] and count($fields) !== 1) {
                unset($fields['_id']);
            }
        } else {
            $fields = array_diff_key($fields, $include) + $exclude;
        }

        return $fields;
    }

    protected function __resolve_new($value = null, $as = null)
    {
        $auto = $this->autoNamespace($as, 'Collection');

        $class =
            $this->popClass($value) ?:
            (class_exists($auto) ? $auto : get_class($this->use))
        ;

        if (is_a($class, self::class, true)) {
            if ($as and 0 !== strpos($as, '.')) {
                $name = $this->name ? $this->name . '.' : '';
                $value += ['name' => $name . $as];
            }

            $value += ['db' => $this->{'$db'}];
        }

        $value['class'] = '\\' . $class;

        return parent::__resolve_new($value, $as);
    }
}

