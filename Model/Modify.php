<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Model;

trait Modify
{
    use \NoFramework\Magic;

    protected function __property__id() {}

    public function modify($command = [])
    {
        if (false === $this->_id) {
            return false;
        }

        $command['query'] = ['_id' => $this->_id];
        $command['upsert'] = false;
        $command['new'] = true;
        $command['orm'] = false;

        $collection = $this->{'$$collection'}->current();

        $result = $collection->findAndModify($command);

        if (!$result->n) {
            return false;
        }

        $out = $result->value;

        $unset = &$command['unset'];
        $rename = &$command['rename'];

        if ($unset or $rename) {
            $restore = array_fill_keys(
                array_keys(array_replace($unset ?: [], $rename ?: [])),
                null
            );

            $out += array_intersect_key(
                (new \ReflectionClass($this))->getDefaultProperties(),
                $restore
            );
            
            $out += $restore;
        }

        $collection->setState($this, $out);

        return true;
    }

    public function __set($name, $value)
    {
        if (
            '_id' === $name or
            !$this->modify(['set' => [$name => $value]])
        ) {
            trigger_error(sprintf('Cannot set property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }

    public function __unset($name)
    {
        if (
            '_id' === $name or
            !$this->modify(['unset' => [$name => 1]])
        ) {
            trigger_error(sprintf('Cannot unset property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }
}

