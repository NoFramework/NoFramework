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
    abstract public function insert($parameters);
    abstract public function find($parameters);
    abstract public function walk($parameters, $closure);
    abstract public function findOne($parameters);
    abstract public function update($parameters);
    abstract public function remove($parameters);
    abstract public function count($parameters);
    
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

