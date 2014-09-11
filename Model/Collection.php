<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Model;

use NoFramework\Database\Memory as Database;

class Collection extends \NoFramework\Factory
{
    protected function __property_db()
    {
        return new Database;
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

    public function normalizeFields($fields)
    {
        if ($fields) {
            if (array_keys($fields) === range(0, count($fields) - 1)) {
                return array_fill_keys($fields, 1);

            } elseif (abs(array_sum($fields)) !== count($fields)) {
                throw new \InvalidArgumentException(
                    'You cannot currently mix including and excluding fields'
                );
            }
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

