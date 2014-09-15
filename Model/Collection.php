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
    protected function __property_db()
    {
        return new Memory;
    }

    protected function __property_memory()
    {
        return new Memory;
    }

    protected function __property_fs()
    {
        return $this->db->getGridFS($this->collection);
    }

    protected function __property_collection()
    {
        return '';
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
        $command['collection'] = $this->collection;

        return $this->db->$name($command);
    }

    public function item($state = [])
    {
        $class = $this->normalizeClass('Item');

        if (!class_exists($class)) {
            $class = $this->use->normalizeClass('Item');
        }

        $state['$collection'] = $this->{'$this'};

        return $this->setState(
            class_exists($class) ? new $class : new Item,
            $state
        );
    }

    public function cursor($data)
    {
        $class = $this->normalizeClass('Cursor');

        if (!class_exists($class)) {
            $class = $this->use->normalizeClass('Cursor');
        }

        return $this->setState(
            class_exists($class) ? new $class : new Cursor,
            [
                'data' => is_array($data) ? new Memory($data) : $data,
                'collection' => $this
            ]
        );
    }

    public function find($command = [])
    {
        $orm = &$command['orm'];
        unset($command['orm']);

        $command['collection'] = $this->collection;

        $out = $this->cursor($this->db->find($command));

        if ($orm) {
            $out->orm();
        }

        return $out;
    }

    public function findOne($command = [])
    {
        $command['limit'] = 1;

        return $this->find($command)->one();
    }

    public function insert($command = [])
    {
        $orm = &$command['orm'];
        unset($command['orm']);

        $command['collection'] = $this->collection;

        $out = $this->db->insert($command);

        return $orm ? $this->item($out) : $out;
    }

    public function findAndModify($command = [])
    {
        $orm = &$command['orm'];
        unset($command['orm']);

        $command['collection'] = $this->collection;

        $out = $this->db->findAndModify($command);

        $value = &$out['value'];
        $value = $value ? ($orm ? $this->item($value) : $value) : false;

        return (object)$out;
    }

    public function patchFields($fields, $patch)
    {
        $patch = is_string($patch) ? [$patch] : $patch;

        if (array_keys($patch) === range(0, count($patch) - 1)) {
            $patch = array_fill_keys($patch, true);
        }

        $include = array_filter($patch);
        $exclude = array_diff_key($patch, $include);

        $fields = $this->memory->normalizeFields($fields);

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
                $collection = $this->collection ? $this->collection . '.' : '';
                $value += ['collection' => $collection . $as];
            }

            $value += ['db' => $this->{'$db'}];
        }

        $value['class'] = '\\' . $class;

        return parent::__resolve_new($value, $as);
    }
}

