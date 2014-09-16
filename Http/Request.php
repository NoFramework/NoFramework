<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Http;

class Request extends \ArrayObject
{
    protected $url = '//localhost/';
    protected $base_url = '//localhost';
    protected $query_string = '';
    protected $path_split = [];
    protected $method = 'GET';

    protected $scheme = '';
    protected $host = 'localhost';
    protected $port = 80;
    protected $path = '/';
    protected $query = [];

    protected $post = [];
    protected $cookie = [];
    protected $files = [];
    protected $referer = false;
    protected $ip = '127.0.0.1';
    protected $user_agent = false;

    /**
     * state (setable):
     *   url
     *   method
     *   post
     *   cookie
     *   files
     *   referer
     *   ip
     *   user_agent
     *
     * calculated from url (not setable):
     *   base_url
     *   scheme
     *   host
     *   port
     *   path
     *   path_split
     *   query
     *   query_string
     */
    public function __construct($state = [])
    {
        $state = is_string($state) ? ['url' => $state] : $state;
        $url = &$state['url'];
        $is_parse_query = (bool)$url;

        $url = $url ?: sprintf(
            '%s://%s%s',
            (
                isset($_SERVER['SERVER_PROTOCOL']) and
                false !== strpos(
                    strtolower($_SERVER['SERVER_PROTOCOL']),
                    'https'
                )
            )
            ? 'https'
            : 'http',

            isset($_SERVER['HTTP_X_FORWARDED_HOST'])
            ? $_SERVER['HTTP_X_FORWARDED_HOST']
            : (
                isset($_SERVER['HTTP_HOST'])
                ? $_SERVER['HTTP_HOST']
                : 'localhost'
            ),

            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/'
        );

        $state += parse_url($url);

        $state += array_intersect_key($state, array_flip([
            'url',
            'method',
            'post',
            'cookie',
            'files',
            'referer',
            'ip',
            'user_agent',
        ]));

        $state += [
            'method' =>
                isset($_SERVER['REQUEST_METHOD'])
                ? substr($_SERVER['REQUEST_METHOD'], 0, 7)
                : 'GET'
            ,
            'post' => &$_POST,
            'cookie' => &$_COOKIE,
            'files' => &$_FILES,
            'referer' => &$_SERVER['HTTP_REFERER'],
            'ip' => &$_SERVER['REMOTE_ADDR'],
            'user_agent' => &$_SERVER['HTTP_USER_AGENT'],
        ];

        $this->query_string = &$state['query'];
        unset($state['query']);

        foreach (array_filter($state) as $key => $value) {
            $this->$key = $value;
        }

        if ($is_parse_query and $this->query_string) {
            parse_str($this->query_string, $this->query);

        } elseif (isset($_GET)) {
            $this->query = $_GET;
        }

        $this->base_url = sprintf(
            '%s//%s%s',
            ($this->scheme ? $this->scheme . ':' : ''),
            $this->host,
            ($this->port and 80 !== $this->port) ? ':' . $this->port : ''
        );

        $this->path_split = explode('/', ltrim($this->path, '/'));

        parent::__construct($this->post + $this->query);
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;

        } else {
            trigger_error(sprintf('Cannot get property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }

    public function __call($name, $arguments) {
        if (in_array($name, ['query', 'post', 'cookie', 'files'])) {
            $key = &$arguments[0];
            $default = &$arguments[1];

            return isset($this->$name[$key]) ? $this->$name[$key] : $default;

        } else {
            trigger_error(
                sprintf('Call to undefined method %s::%s.',
                static::class,
                $name), 
            E_USER_ERROR);
        }
    }

    public function __toString()
    {
        return $this->url;
    }

    public function offsetGet($index)
    {
        return $this->offsetExists($index) ? parent::offsetGet($index) : null;
    }

    public function any($key, $default = null)
    {
        return isset($this[$key]) ? $this[$key] : $default;
    }

    public function url($query, $base_path = false)
    {
        $query = is_string($query) ? ['path' => $query] : $query;

        $path = &$query['path'];
        unset($query['path']);

        $path = strtok($path, '?');

        if ($path_query = strtok('')) {
            parse_str($path_query, $add_query);
            $query += $add_query;
        }

        $is_inherit = array_key_exists('#inherit', $query);
        unset($query['#inherit']);

        if (0 !== strpos($path, '/')) {
            $path = ($base_path ?: $this->path) . $path;
        }

        if ($query) {
            $base_query = $is_inherit ? $this->query : [];

            foreach ($query as $key => $value) {
                unset($base_query[$key]);

                if (is_string($value) and 0 === strpos($value, '=')) {
                    $query[$key] = $this[substr($value, 1)];
                }

                if (!isset($query[$key])) {
                    unset($query[$key]);
                }
            }

            $query = http_build_query($query + $base_query);

        } elseif ($is_inherit) {
            $query = $this->query_string;

        } else {
            $query = '';
        }

        return $this->base_url . $path . ($query ? '?' . $query : '');
    }

    public function pathSlice($offset = 0, $length = null)
    {
        return implode('/', array_slice($this->path_split, $offset, $length));
    }
}

