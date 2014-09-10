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
    protected $separator = '\\';
    protected $path = __DIR__;
    protected $extension = '.php';

    public function __construct($state = [])
    {
        foreach ($state as $key => $value) {
            $this->$key = $value;
        }
    }

    protected function getFilenameByClass($class)
    {
        $namespace = $this->namespace . $this->separator;

        if (0 !== strpos($class, $namespace)) {
            return false;
        }

        $class = substr($class, strlen($namespace));

        $out =
            ($this->path ? $this->path . DIRECTORY_SEPARATOR : '') .
            str_replace($this->separator, DIRECTORY_SEPARATOR, $class) .
            $this->extension
        ;

        return str_replace("\0", '', $out);
    }

    public function __invoke($class)
    {
        if (
            $filename = $this->getFilenameByClass($class) and
            is_file($filename)
        ) {
            require $filename;
        }
    }

    public function register()
    {
        spl_autoload_register($this);

        return $this;
    }

    public function unregister()
    {
        spl_autoload_unregister($this);

        return $this;
    }
}

