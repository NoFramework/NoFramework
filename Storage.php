<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

abstract class Storage
{
    abstract public function insert($parameter);
    abstract public function find($parameter);
    abstract public function walk($parameter, $closure);
    abstract public function findOne($parameter);
    abstract public function update($parameter);
    abstract public function remove($parameter);
    abstract public function count($parameter);
    
    abstract public function listCollections();

    public function fromUnixTimestamp($timestamp) {
        return $timestamp;
    }

    public function toUnixTimestamp($timestamp) {
        return $timestamp;
    }

    public function listFields($collection) {
        return false;
    }

    public function newId($id = null) {
        return $id;
    }
}

