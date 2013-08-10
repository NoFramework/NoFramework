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
        if (!isset($cookie['name'])) {
            throw new \InvalidArgumentException('Cookie name is not set');
        }

        $get = function ($option, $default = '') use ($cookie) {
            return isset($cookie[$option]) ? $cookie[$option] : $default;
        };

        setcookie(
            $cookie['name'],
            $get('value'),
            $get('expire', 0),
            $get('path'),
            $get('domain'),
            $get('is_secure', false),
            $get('is_httponly', false)
        );

        return $this;
    }

    public function header($name, $value = null)
    {
        if (is_null($value)) {
            header_remove($name);
            return $this;
        }

        $is_replace = true;

        foreach ((array)$value as $value_item) {
            header(sprintf('%s: %s', $name, $value_item), $is_replace);
            $is_replace = false;
        }

        return $this;
    }

    public function isHeadersSent()
    {
        return headers_sent();
    }

    public function payload($payload)
    {
        echo $payload;
        return $this;
    }
}

