<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Autoload
{
    protected $namespace = __NAMESPACE__;
    protected $path = __DIR__;
    protected $extension = '.php';
    protected $separator = '\\';

    public function __construct($state = [])
    {
        foreach ($state as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getFilenameByClass($class)
    {
        return
            0 === strpos(
                $class,
                $namespace = $this->namespace . $this->separator
            )
            ? str_replace("\0", '',
                $this->path . DIRECTORY_SEPARATOR .
                str_replace(
                    $this->separator,
                    DIRECTORY_SEPARATOR,
                    substr($class, strlen($namespace))
                ) .
                $this->extension
            )
            : false;
    }

    public function __invoke($class)
    {
        if ($filename = $this->getFilenameByClass($class)) {
            require $filename;
        }
    }

    public function register()
    {
        if (!spl_autoload_register($this)) {
            trigger_error(sprintf(
                'Could not register \'%s\' for namespace \'%s\'',
                static::class,
                $this->namespace
            ), E_USER_WARNING);
        }

        return $this;
    }

    public function unregister()
    {
        if (!spl_autoload_unregister($this)) {
            trigger_error(sprintf(
                'Could not unregister \'%s\' for namespace \'%s\''
                , static::class
                , $this->namespace
            ), E_USER_WARNING);
        }

        return $this;
    }

    public static function find()
    {
        foreach ((array)spl_autoload_functions() as $autoload) {
            if ($autoload instanceof self) {
                yield $autoload->namespace => $autoload;
            }
        }
    }
}

