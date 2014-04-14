<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class Request extends \ArrayObject
{
    protected $url;
    protected $base_url;
    protected $query_string;
    protected $path_split;
    protected $method;

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
        if (is_string($state)) {
            $state = ['url' => $state];
        }

        $is_parse_query = true;

        if (!isset($state['url'])) {
            $state['url'] = sprintf(
                '%s://%s%s',

                (isset($_SERVER['SERVER_PROTOCOL']) and false !== strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https'))
                ? 'https'
                : 'http',

                isset($_SERVER['HTTP_X_FORWARDED_HOST'])
                ? $_SERVER['HTTP_X_FORWARDED_HOST']
                : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),

                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/'
            );

            $is_parse_query = false;
        }

        $state = array_merge(
            [
                'method' =>
                    isset($_SERVER['REQUEST_METHOD'])
                    ? substr($_SERVER['REQUEST_METHOD'], 0, 7)
                    : 'GET'
                ,
                'post' => isset($_POST) ? $_POST : [],
                'cookie' => isset($_COOKIE) ? $_COOKIE : [],
                'files' => isset($_FILES) ? $_FILES : [],
                'referer' =>
                    isset($_SERVER['HTTP_REFERER'])
                    ? $_SERVER['HTTP_REFERER']
                    : false
                ,
                'ip' =>
                    isset($_SERVER['REMOTE_ADDR'])
                    ? $_SERVER['REMOTE_ADDR']
                    : '127.0.0.1'
                ,
                'user_agent' =>
                    isset($_SERVER['HTTP_USER_AGENT'])
                    ? $_SERVER['HTTP_USER_AGENT']
                    : false,
            ],
            array_intersect_key($state, array_flip([
                'url',
                'method',
                'post',
                'cookie',
                'files',
                'referer',
                'ip',
                'user_agent',
            ])),
            parse_url($state['url'])
        );

        foreach ($state as $property => $value) {
            $this->$property = $value;
        }

        $this->query_string = $this->query ?: '';

        if ($is_parse_query and $this->query) {
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

        parent::__construct(array_merge(
            $this->query,
            $this->post
        ));
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;

        } else {
            trigger_error(sprintf('Cannot read property %s::$%s',
                static::class,
                $property
            ), E_USER_ERROR);
        }
    }

    public function __call($property, $parameter) {
        if (in_array($property, ['query', 'post', 'cookie', 'files'])) {
            $property = $this->$property;

            return isset($property[$parameter[0]])
                ? $property[$parameter[0]]
                : (isset($parameter[1]) ? $parameter[1] : null);
        } else {
            trigger_error(
                sprintf('Call to undefined method %s::%s.',
                static::class,
                $property), 
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

    public function any($field, $default = null)
    {
        return isset($this[$field]) ? $this[$field] : $default;
    }

    public function getUrl($query)
    {
        if (is_string($query)) {
            $query = ['path' => $query];
        }

        $path = isset($query['path']) ? $query['path'] : '';
        unset($query['path']);

        if (0 !== strpos($path, '/')) {
            $path = $this->path . $path;
        }

        $is_only = isset($query['is_only']) and $query['is_only'];
        unset($query['is_only']);

        if ($query) {
            $base_query = $is_only ? [] : $this->query;

            foreach ($query as $key => $value) {
                unset($base_query[$key]);

                if (is_string($value) and 0 === strpos($value, '=')) {
                    $query[$key] = $this[substr($value, 1)];
                }

                if (is_null($query[$key])) {
                    unset($query[$key]);
                }
            }

            $query = http_build_query(array_merge($base_query, $query));
        } elseif ($is_only) {
            $query = '';
        } else {
            $query = $this->query_string;
        }

        return $this->base_url . $path . ($query ? '?' . $query : '');
    }

    public function pathSlice($offset = 0, $length = null)
    {
        return implode('/', array_slice($this->path_split, $offset, $length));
    }
}

