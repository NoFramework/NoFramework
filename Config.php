<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Config
{
    const MAGIC_PARSE_METHOD = '__parse_';

    protected $script_path;
    protected $config_path;
    protected $cache_path;
    protected $local_path;

    public function __construct($state = [])
    {
        $this->script_path =
            isset($state['script_path'])
            ? $state['script_path']
            : dirname(realpath($_SERVER['SCRIPT_FILENAME']));

        foreach ([
            'config_path' => '.config' . DIRECTORY_SEPARATOR .
                str_replace('\\', DIRECTORY_SEPARATOR,  __NAMESPACE__),
            'cache_path' => '.cache',
            'local_path' => '.local'
        ] as $property => $find_path) {
            $this->$property =
                isset($state[$property])
                ? $state[$property]
                : $this->findPath($this->script_path, $find_path);
        }
    }

    protected function findPath($start, $find)
    {
        $current = $start;
        $found = false;

        do {
            if (is_dir($current . DIRECTORY_SEPARATOR . $find)) {
                $found = $current . DIRECTORY_SEPARATOR . $find;
                break;
            }

            $current = realpath($current . DIRECTORY_SEPARATOR . '..');
        } while (DIRECTORY_SEPARATOR !== $current);

        return $found ?: $start;
    }

    protected function getCallbacks()
    {
        $return = [];

        foreach (get_class_methods($this) as $method) {
            if (0 === strpos($method, static::MAGIC_PARSE_METHOD)) {
                $return['!' . substr(
                    $method,
                    strlen(static::MAGIC_PARSE_METHOD)
                )] = [$this, $method];
            }
        }
        
        return $return;
    }

    public function withFile($filename, $closure, $offset = 0, $pos = 0)
    {
        if (0 !== strpos($filename, DIRECTORY_SEPARATOR)) {
            $filename = $this->config_path . DIRECTORY_SEPARATOR . $filename;
        }

        if ($offset) {
            $this->withString(
                file_get_contents($filename, false, null, $offset),
                $closure,
                $pos
            );
        } else {
            $closure(
                yaml_parse_file($filename, $pos, $ndocs, $this->getCallbacks()),
                $ndocs
            );
            gc_collect_cycles();
        }

        return $this;
    }

    public function withString($input, $closure, $pos = 0)
    {
        $closure(
            yaml_parse($input, $pos, $ndocs, $this->getCallbacks()),
            $ndocs
        );
        gc_collect_cycles();
        return $this;
    }

    public function __parse_ini_set($value, $tag, $flags)
    {
        if (is_array($value)) {
            foreach ($value as $ini_key => $ini_value) {
                ini_set($ini_key, $ini_value);
            }
        }

        return $value;
    }

    public function __parse_setTimeLimit($value, $tag, $flags)
    {
        set_time_limit($value);
        return $value;
    }

    public function __parse_period($value, $tag, $flags)
    {
        $interval = new \DateInterval(
            'P' . strtoupper(str_replace(' ', '', $value)));

        return ($interval->y * 365 * 24 * 60 * 60) +
            ($interval->m * 30 * 24 * 60 * 60) +
            ($interval->d * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;
    }

    public function __parse_script_path($value, $tag, $flags)
    {
        return $this->script_path .
            ($value ? DIRECTORY_SEPARATOR . $value : '');
    }

    public function __parse_cache_path($value, $tag, $flags)
    {
        return $this->cache_path .
            ($value ? DIRECTORY_SEPARATOR . $value : '');
    }

    public function __parse_local_path($value, $tag, $flags)
    {
        return $this->local_path .
            ($value ? DIRECTORY_SEPARATOR . $value : '');
    }

    public function __parse_read($value, $tag, $flags)
    {
        return ['$' => function($id = false) use ($value) {
            $offset = 0;

            if (isset($value['filename'])) {
                if (isset($value['offset'])) {
                    $offset = $value['offset'];
                }

                $value = $value['filename'];
            }

            $this->withFile($value, function ($state) use (&$return) {
                $return = $state;
            }, $offset);

            if (
                $id and
                isset($return['$new']) and
                !isset($return['$new']['local_reuse'])
            ) {
                $return['$new']['local_reuse'] = implode('.', $id);
            }

            return $return;
        }];
    }

    public function __parse_new($value, $tag, $flags)
    {
        return ['$new' => $value];
    }

    public function __parse_reuse($value, $tag, $flags)
    {
        return ['$reuse' => $value];
    }

    public function __parse_autoload($value, $tag, $flags)
    {
        $return = [];

        foreach ((array)$value as $state) {
            if (!$state) {
                $state = ['namespace' => __NAMESPACE__];

            } elseif (is_string($state)) {
                $state = [
                    'namespace' => $state,
                    'path' => str_replace('\\', DIRECTORY_SEPARATOR, $state)
                ];
            }

            $class = __NAMESPACE__ . '\Autoload';

            if (isset($state['class'])) {
                $class = $state['class'];
                unset($state['class']);
            }

            if (isset($state['path']) and
                0 !== strpos($state['path'], DIRECTORY_SEPARATOR)
            ) {
                $state['path'] = realpath(implode(DIRECTORY_SEPARATOR, [
                    __DIR__, '..', $state['path']
                ]));
            }

            if (!class_exists($class, false)) {
                require implode(DIRECTORY_SEPARATOR, [
                    __DIR__, '..',
                    str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php'
                ]);
            }

            $return[] = (new $class($state))->register();
        }

        return $return;
    }

    public function __parse_error_handler($value, $tag, $flags)
    {
        if (isset($value['error_types'])) {
            $error_types = (array)$value['error_types'];

        } elseif ($value) {
            $error_types = (array)$value;
        }

        $state = false;

        if (isset($error_types)) {
            $state = 0;

            foreach ($error_types as $error_type) {
                $state |= constant(
                    'E_' . str_replace(' ', '_', strtoupper($error_type))
                );
            }
        }

        $class = __NAMESPACE__ . '\Error\Handler';

        if (isset($value['class'])) {
            $class = $value['class'];
        }

        $error_handler = new $class($state);
        $error_handler->register();

        return $error_handler;
    }

    public static function __callStatic($name, $parameter)
    {
        $filename = $name . '.yaml';
        $offset = 0;

        if (isset($parameter[0])) {
            if (isset($parameter[1])) {
                $filename = $parameter[0];
                $offset = $parameter[1];

            } elseif (is_numeric($parameter[0])) {
                $offset = $parameter[0];

            } else {
                $filename = $parameter[0];
            }
        }

        (new static)->withFile($filename, function ($state) use ($name) {
            $class = isset($state['class'])
                ? $state['class']
                : __NAMESPACE__ . '\Factory';
            $class::$name($state);
        }, $offset);

        return Factory::$name();
    }
}

