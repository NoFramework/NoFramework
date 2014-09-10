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

    public function cookie($name, $value = [])
    {
        $value = is_array($value) ? $value : ['value' => $value];

        $value += [
            'value' => '',
            'expire' => 0,
            'path' => '',
            'domain' => '',
            'is_secure' => false,
            'is_httponly' => false
        ];

        setcookie(
            $name,
            $value['value'],
            $value['expire'],
            $value['path'],
            $value['domain'],
            $value['is_secure'],
            $value['is_httponly']
        );

        return $this;
    }

    public function header($name, $value = null)
    {
        if (!isset($value)) {
            header_remove($name);
            return $this;
        }

        $is_replace = true;

        foreach ((array)$value as $value_item) {
            header("$name: $value_item", $is_replace);
            $is_replace = false;
        }

        return $this;
    }

    public function isHeadersSent()
    {
        return headers_sent();
    }

    public function output($data)
    {
        echo $data;

        return $this;
    }
}

