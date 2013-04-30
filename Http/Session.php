<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class Session implements \ArrayAccess
{
    protected /*string*/ $id;

    public function start()
    {
        if ( $this->id ) {
            session_id($this->id);
        }

        session_start();

        if ( ! $this->id ) {
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

    public function offsetGet($offset) {
        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    public function writeClose() {
        session_write_close();
    }

    public function getId()
    {
        return $this->id;
    }
}

