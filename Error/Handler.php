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
    protected $is_preload_exception = true;

    public function __construct($error_types = false)
    {
        if (false !== $error_types) {
            $this->error_types = $error_types;
        }
    }

    public function getErrorClass()
    {
        $return = [];

        foreach ([
            E_USER_ERROR => 'Recoverable',
            E_RECOVERABLE_ERROR => 'Recoverable',
            E_USER_WARNING => 'Warning',
            E_WARNING => 'Warning',
            E_USER_NOTICE => 'Notice',
            E_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_USER_DEPRECATED => 'Deprecated',
            E_DEPRECATED => 'Deprecated'
        ] as $errno => $errclass) {
            $return[$errno] = __NAMESPACE__ . '\Exception\\' . $errclass;
        }

        return $return;
    }

    public function __invoke($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (0 !== error_reporting()) {
            $class = $this->getErrorClass()[$errno];
            throw new $class($errno, $errstr, $errfile, $errline, $errcontext);
        }

        return true;
    }

    public function register()
    {
        if ($this->is_preload_exception) {
            foreach (array_unique($this->getErrorClass()) as $class) {
                spl_autoload_call($class);
            }
        }

        return set_error_handler($this, $this->error_types);
    }

    public static function restorePrevious()
    {
        return restore_error_handler();
    }

    public static function restoreBuiltin()
    {
        return set_error_handler(null);
    }
}

