<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

