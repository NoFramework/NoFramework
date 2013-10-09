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
    protected $name;
    protected $propagate = ['storage'];
    protected $separator = '.';
    protected $auto = true;

    protected function __property_storage()
    {
        return new \NoFramework\Storage\Mongo;
    }

    protected function __operator_new($state = null, $id = null)
    {
        $object = parent::__operator_new($state, $id);

        if ($object instanceof self) {
            $name = (array)$id;
            $object->name = $this->name ? $this->name . $this->separator : '';
            $object->name .= array_pop($name);
        }

        return $object;
    }

    public function __call($method, $argument)
    {
        return $this->storage->$method(
            $this->name,
            isset($argument[0]) ? $argument[0] : []
        );
    }
}

