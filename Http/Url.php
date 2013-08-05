<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;
use \NoFramework\File\Path;

class Url
{
    use \NoFramework\MagicProperties;

    protected function __property_base_url()
    {
        $split = $this->splitUrl($this->url);
        return $split['base_url'];
    }

    protected function __property_scheme()
    {
        $split = $this->splitUrl($this->base_url);
        return $split['scheme'];
    }

    protected function __property_host_port()
    {
        $split = $this->splitUrl($this->base_url);
        return $split['host_port'];
    }

    protected function __property_host()
    {
        $split = $this->splitHostPort($this->host_port);
        return $split['host'];
    }

    protected function __property_port()
    {
        $split = $this->splitHostPort($this->host_port);
        return $split['port'];
    }

    protected function __property_request_uri()
    {
        $split = $this->splitUrl($this->url);
        return $split['request_uri'];
    }

    protected function __property_path_string()
    {
        if (isset($this->path)) {
            return (string)$this->path;
        }

        $split = $this->splitRequestUri($this->request_uri);
        return $split['path_string'];
    }

    protected function __property_path()
    {
        return new Path([
            'separator' => '/',
            'path' => urldecode($this->path_string) ?: '/'
        ]);
    }

    protected function __property_query_string()
    {
        if (isset($this->query)) {
            return http_build_query($this->query);
        }

        $split = $this->splitRequestUri($this->request_uri);
        return $split['query_string'];
    }

    protected function __property_query()
    {
        $query = [];

        foreach (explode('&', $this->query_string) as $key_value) {
            $key = urldecode(strtok($key_value, '='));
            $value = strtok('=');
            $value = false === $value ? null : urldecode($value);

            if (!isset($query[$key])) {
                $query[$key] = $value;

            } elseif (!is_array($query[$key])) {
                $query[$key] = [$query[$key], $value];

            } else {
                $query[$key][] = $value;
            }
        }

        return $query;
    }

    protected function __property_url()
    {
        return $this->joinBaseUrl($this->scheme, $this->host, $this->port) .
        $this->path_string .
        ($this->query_string ? '?' . $this->query_string : '');
    }

    public function __toString()
    {
        return $this->url;
    }

    protected function splitUrl($url)
    {
        $parts = [
            'scheme' => '2',
            'host' => '3',
            'port' => '5',
            'request_uri' => '6'
        ];

        $is_matched = preg_match(
            '/^((\w*):|)\/\/([\w\.-]*)(:(\d*)|)(\/.*)$/i',
            $url,
            $matches
        );

        foreach ($parts as $part => $match ) {
            $$part = $is_matched ? $matches[$match] : '';
        }

        $host_port = $this->joinHostPort($host, $port);
        $base_url = $this->joinBaseUrl($scheme, $host, $port);
        
        return compact(
            'scheme',
            'host_port',
            'base_url',
            'request_uri'
        );
    }

    protected function splitHostPort($host_port)
    {
        return [
            'host' => strtok($host_port, ':'),
            'port' => intval(strtok(':')) ?: ''
        ];
    }

    protected function splitRequestUri($request_uri)
    {
        $path_string = strtok($request_uri, '?');

        return [
            'path_string' => $path_string ?: '/',
            'query_string' => substr($request_uri, strlen($path_string) + 1)
        ];
    }

    protected function joinHostPort($host, $port)
    {
        return $host . (($port and 80 !== $port) ? ':' . $port : '');
    }

    protected function joinBaseUrl($scheme, $host, $port)
    {
        return ($scheme ? $scheme . ':' : '') .
            '//' . $this->joinHostPort($host, $port);
    }
}

