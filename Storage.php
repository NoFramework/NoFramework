<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

interface Storage
{
    public function find($collection, $where = [], $fields = [], $sort = [],
        $skip = 0, $limit = 0, $options = []);

    public function count($collection, $where = [], $options = []);

    public function update($collection, $set, $where = [], $options = []);

    public function remove($collection, $where = [], $fields = [],
        $options = []);

    public function insert($collection, $set, $options = []);

    public function insertIgnore($collection, $set, $key = [], $options = []);

    public function insertOrReplace($collection, $set, $key = [],
        $options = []);

    public function replaceExisting($collection, $set, $key = [],
        $options = []);

    public function insertOrUpdate($collection, $set, $key = [],
        $insert_only = [], $options = []);

    public function updateExisting($collection, $set, $key = [], $options = []);

    public function drop($collection, $options = []);

    public function fromUnixTimestamp($timestamp);

    public function toUnixTimestamp($timestamp);
}

