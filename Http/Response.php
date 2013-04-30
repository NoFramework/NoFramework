<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class Response
{
    public function status($status)
    {
        header(' ', true, $status);
        return $this;
    }

    public function redirect($location, $status = 302)
    {
        header('Location: ' . $location, true, $status);
        return $this;
    }

    public function cookie($cookie)
    {
        extract($cookie);

        if ( ! isset($name) ) {
            throw new \InvalidArgumentException('Cookie name is not set');
        }

        $value = isset($value) ? (string)$value : '';
        $expire = isset($expire) ? (int)$expire : 0;
        $path = isset($path) ? (string)$path : '';
        $domain = isset($domain) ? (string)$domain : '';
        $is_secure = isset($is_secure) ? (boolean)$is_secure : false;
        $is_httponly = isset($is_httponly) ? (boolean)$is_httponly : false;

        setcookie($name, $value, $expire, $path, $domain, $is_secure, $is_httponly);

        return $this;
    }

    public function header($name, $value = null)
    {
        if ( is_null($value) ) {
            header_remove($name);
            return $this;
        }

        $is_replace = true;
        foreach ( is_array($value) || $value instanceof Traversable ? $value : [$value] as $value_item ) {
            header($name . ': ' . $value_item, $is_replace);
            $is_replace = false;
        }

        return $this;
    }

    public function payload($payload)
    {
        echo $payload;
        return $this;
    }
}

