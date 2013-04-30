<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Storage;

class MySql extends \NoFramework\Storage
{
    protected $user;
    protected $password;
    protected $host = 'localhost';
    protected $port;
    protected $database;

    private $links;

    const DEFAULT_LINK_NAME = 'default';

    public function open(&$link) {
        $return_link = &$link;

        if ( ! is_resource($link) ) {
            $link_name = isset($link) ? $link : static::DEFAULT_LINK_NAME;
            if ( ! isset($this->links[$link_name]) )
                $this->links[$link_name] = NULL;
            $link = &$this->links[$link_name];
        }

        if ( is_resource($link) ) {
            if ( mysql_ping($link) ) {
                $return_link = $link;
                if (  null === $this->links )
                    $this->links[static::DEFAULT_LINK_NAME] = &$link;
                return $this;
            }

            $this->close($link);
        }

        if ( ! $link = mysql_connect(
            ($this->host).
            ($this->port ? ':' . $this->port : ''),
            $this->user ?: NULL,
            $this->password ?: NULL,
            true
        ) )
            throw new \RuntimeException(mysql_error(), 0);

        if ( ! mysql_select_db($this->database, $link) )
            throw new \RuntimeException(mysql_error(), 1);

        $this->query('SET NAMES utf8', $link);

        $return_link = $link;
        return $this;
    }

    public function close(&$link) {
        mysql_close($link);
        $link = NULL;

        return $this;
    }

    public function insert($parameters) {
        extract($parameters);

        $this->open($link);

        $table = $this->table($collection, $link);

        /*
        echo 'INSERT ' .
            (isset($update) ? '' : 'IGNORE ') .
            'INTO ' .
            $table .
            ' SET' .
            $this->set($set, $table, $link) .
            (isset($update) ? "\nON DUPLICATE KEY UPDATE" . $this->set($update, $table, $link) : '').
            PHP_EOL;
        #*/

        $this->query('INSERT ' .
            (isset($update) ? '' : 'IGNORE ') .
            'INTO ' .
            $table .
            ' SET' .
            $this->set($set, $table, $link) .
            (isset($update) ? "\nON DUPLICATE KEY UPDATE" . $this->set($update, $table, $link) : '')
        , $link);
        $last_id = mysql_insert_id();

        if ( isset($is_close) )
            $this->close($link);

        return $last_id;
    }

    public function walk($parameters, $closure) {
        extract($parameters);

        $this->open($link);

        $table = $this->table($collection, $link);
        $result = $this->query('SELECT ' .
            (isset($fields) ? $this->fields($fields, $table, $link) : '*') .
            "\nFROM $table" .
            (isset($where) ? $this->where($where, $table, $link) : '') .
            (isset($sort) ? $this->order($sort, $table, $link) : '') .
            $this->limit(isset($skip) ? $skip : NULL, isset($limit) ? $limit : NULL)
        , $link);
        while ($row = mysql_fetch_assoc($result))
            $closure($row);
        mysql_free_result($result);

        if ( isset($is_close) )
            $this->close($link);

        return $this;
    }

    public function find($parameters) {
        $out = [];
        $this->walk($parameters, function ($item) use (&$out) {
            $out[] = $item;
        });

        return $out;
    }

    public function findOne($parameters) {
        $parameters['limit'] = 1;
        $out = $this->find($parameters);

        if ( count($out) == 0 )
            return false;

        if ( count($out[0])==1 )
            return array_pop($out[0]);

        return $out[0];
    }

    public function update($parameters) {
        extract($parameters);

        $this->open($link);

        $table = $this->table($collection, $link);
        $this->query("UPDATE " .
            $table .
            ' SET' .
            $this->set($set, $table, $link) .
            (isset($where) && $where ? $this->where($where, $table, $link) : '')
        , $link);
        $affected_rows = mysql_affected_rows($link);

        if ( isset($is_close) )
            $this->close($link);

        return $affected_rows;
    }

    public function remove($parameters) {
        extract($parameters);

        $this->open($link);

        $table = $this->table($collection, $link);
        $this->query('DELETE FROM ' .
            $table .
            $this->where($where, $table, $link)
        , $link);
        $affected_rows = mysql_affected_rows($link);

        if ( isset($is_close) )
            $this->close($link);

        return $affected_rows;
    }

    public function count($parameters) {
        extract($parameters);

        $this->open($link);

        $table = $this->table($collection, $link);
        $result = $this->query('SELECT COUNT(1) FROM ' .
            $table .
            (isset($where) ? $this->where($where, $table, $link) : '')
        , $link);
        $out = 0;
        if ($row = mysql_fetch_row($result))
        $out = $row[0];
        mysql_free_result($result);

        if ( isset($is_close) )
            $this->close($link);

        return $out;
    }

    public function listCollections() {
        $this->open($link);

        $result = $this->query('SHOW TABLES FROM ' .
            $this->escapeDatabase($link)
        , $link);
        $out = [];
        while ($row = mysql_fetch_row($result))
            $out[] = $row[0];
        mysql_free_result($result);

        $this->close($link);

        return $out;
    }

    public function listFields($collection) {
        $this->open($link);

        $result = $this->query('SHOW COLUMNS FROM ' .
            $this->table($collection, $link)
        , $link);
        $out = [];
        while ($row = mysql_fetch_assoc($result))
            $out[] = [
                'field' => $row['Field'],
                'default' => $row['Default'],
                'is_null' => $row['Null'] == 'YES',
            ];
        mysql_free_result($result);

        $this->close($link);

        return $out;
    }

    public function fromUnixTimestamp($timestamp) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function toUnixTimestamp($timestamp) {
        return strtotime($timestamp);
    }

    public function query($query, $link) {
        if ( false === ($result = mysql_query($query, $link)) )
            throw new \RuntimeException($query . "\n" . mysql_error());

        return $result;
    }

    private function escapeDatabase($link) {
        return '`' . mysql_real_escape_string($this->database, $link) . '`';
    }

    private function table($collection, $link) {
        return $this->escapeDatabase($link) . '.`' . mysql_real_escape_string($collection, $link) . '`';
    }

    private function field($field, $table, $link) {
        return $table . '.`' . mysql_real_escape_string($field, $link) . '`';
    }

    private function value($value, $link) {
        if ( ! isset($value) )
            return 'NULL';

        if ( is_array($value) )
            $value = implode(':', $value);

        return "'" . mysql_real_escape_string($value, $link) . "'";
    }

    private function fields($fields, $table, $link) {
        if ( count($fields) == 0 )
            return ' *';

        $out = [];
        foreach ($fields as $field)
            $out[] = $this->field($field, $table, $link);

        return "\n\t" . implode(",\n\t", $out);
    }

    private function set($set, $table, $link) {
        $out = [];

        foreach($set as $field => $value) {

            if ( ! is_string($field) ) {
                $out[] = $value;
                continue;
            }

            $field = $this->field($field, $table, $link); 

            $out[] = $field . ' = ' . $this->value($value, $link) ;
        }

        return "\n\t" . implode(",\n\t", $out);
    }

    private function where($where, $table, $link) {
        $out = [];

        foreach($where as $field => $value) {
            if ( ! is_string($field) ) {
                $out[] = $value;
                continue;
            }

            $field = $this->field($field, $table, $link); 

            if ( is_array($value) ) {
                $collation = key($value);
                $value = $value[$collation];
            } else {
                $collation = '=';
            }

            if ( $collation === '$in' ) {
                if ( !is_array($value) )
                    $collation = '=';

                elseif ( empty($value) )
                    return "\nWHERE false";
            }

            switch ( $collation ) {
                case '$in': 
                    foreach($value as $k => $v)
                        $value[$k] = $this->value($v, $link);
                    $out[] = $field . ' IN (' . implode(',', $value) . ')';
                break;

                case '$like':
                    if ( !isset($value) || is_array($value) )
                    break;
                    $out[] = $field . " LIKE '%" . mysql_real_escape_string($value, $link) . "%'";
                break;

                case '<':
                    $out[] = $field . ' < ' . $this->value($value, $link);
                break;

                case '>':
                    $out[] = $field . ' > ' . $this->value($value, $link);
                break;

                case '<>':
                    $out[] = $field . (isset($value) ? ' <> ' . $this->value($value, $link) : ' IS NOT NULL');
                break;

                case '=':
                    $out[] = $field . (isset($value) ? ' = ' . $this->value($value, $link) : ' IS NULL');
                break;
            }
        }

        return "\nWHERE\n\t(" . implode(") AND\n\t(", $out) . ")";
    }

    private function order($sort, $table, $link) {
        $out = [];

        foreach($sort as $field => $direction)
            $out[] = $this->field($field, $table, $link) . ($direction == -1 ? ' DESC' : '');

        return "\nORDER BY\n\t" . implode(",\n\t", $out);
    }

    private function limit($skip, $limit) {
        if ( ! intval($limit) )
            return '';

        return "\nLIMIT " . (intval($skip) ? intval($skip) . ',' : '') . intval($limit);
    }
}
