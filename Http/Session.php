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

class Session implements \ArrayAccess
{
    protected $id;

    public function start()
    {
        if ($this->id) {
            session_id($this->id);
        }

        session_start();

        if (!$this->id) {
            $this->id = session_id();
        }

        return $this;
    }

    public function offsetSet($offset, $value) {
        $_SESSION[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($_SESSION[$offset]);
    }

    public function offsetUnset($offset) {
        if (isset($_SESSION[$offset])) {
            unset($_SESSION[$offset]);
        }
    }

    public function &offsetGet($offset) {
        $return = &$_SESSION[$offset];
        return $return;
    }

    public function clear() {
        $walk = function (&$session) use (&$walk) {
            foreach ($session as $key => $value) {
                if (!isset($session[$key])) {
                    unset($session[$key]);
                } elseif (is_array($session[$key])) {
                    $walk($session[$key]);

                    if (!$session[$key]) {
                        unset($session[$key]);
                    }
                }
            }
        };

        $walk($_SESSION);

        return $this;
    }

    public function writeClose() {
        session_write_close();
    }

    public function getId()
    {
        return $this->id;
    }
}

