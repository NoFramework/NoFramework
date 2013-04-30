<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Error;

class Handler
{
    protected $error_types = -1;

    public function __construct($error_types = false)
    {
        if (false !== $error_types) {
            $this->error_types = $error_types;
        }
    }

    public function getErrorClass($errno)
    {
        $class = [
            E_USER_ERROR => 'Recoverable',
            E_RECOVERABLE_ERROR => 'Recoverable',
            E_USER_WARNING => 'Warning',
            E_WARNING => 'Warning',
            E_USER_NOTICE => 'Notice',
            E_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_USER_DEPRECATED => 'Deprecated',
            E_DEPRECATED => 'Deprecated'
        ];

        return isset($class[$errno]) ? $class[$errno] : false;
    }

    public function __invoke($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (0 !== error_reporting()) {
            $class = __NAMESPACE__ . '\Exception\\' .
                $this->getErrorClass($errno);
            throw new $class($errno, $errstr, $errfile, $errline, $errcontext);
        }

        return true;
    }

    public function register()
    {
        return set_error_handler($this, $this->error_types);
    }

    public static function restorePrevious()
    {
        return restore_error_handler();
    }

    public static function restoreBuiltin()
    {
        return set_error_handler(function() {
            return false;
        });
    }
}

