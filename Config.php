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

    public function __construct($state = [])
    {
        $this->script_path =
            isset($state['script_path'])
            ? $state['script_path']
            : dirname(realpath($_SERVER['SCRIPT_FILENAME']));

        $this->config_path =
            isset($state['config_path'])
            ? $state['config_path']
            : $this->script_path;
    }

    protected function getCallbacks()
    {
        $out = [];

        foreach (get_class_methods($this) as $method) {
            if (0 === strpos($method, static::MAGIC_PARSE_METHOD)) {
                $out['!' . substr($method, strlen(static::MAGIC_PARSE_METHOD))]
                    = [$this, $method];
            }
        }
        
        return $out;
    }

    public function parseFile($input, $offset = 0, $pos = 0, &$ndocs = null)
    {
        if (0 !== strpos($input, DIRECTORY_SEPARATOR)) {
            $input = $this->config_path . DIRECTORY_SEPARATOR . $input;
        }

        $yaml_parse = 'yaml_parse';

        if ($offset) {
            $input = file_get_contents($input, false, null, $offset);

        } else {
            $yaml_parse .= '_file'; 
        }

        $out = $yaml_parse($input, $pos, $ndocs, $this->getCallbacks());
        gc_collect_cycles();
        return $out;
    }

    public function parseString($input, $pos = 0, &$ndocs = null)
    {
        $out = yaml_parse($input, $pos, $ndocs, $this->getCallbacks());
        gc_collect_cycles();
        return $out;
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

    public function __parse_setTimezone($value, $tag, $flags)
    {
        date_default_timezone_set($value);
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
        return $this->script_path . ($value ? DIRECTORY_SEPARATOR . $value : '');
    }

    public function __parse_read($value, $tag, $flags)
    {
        return ['$' => function() use ($value) {
            $offset = 0;

            if (isset($value['filename'])) {
                if (isset($value['offset'])) {
                    $offset = $value['offset'];
                }

                $value = $value['filename'];
            }

            return $this->parseFile($value, $offset);
        }];
    }

    public function __parse_new($value, $tag, $flags)
    {
        return ['$new' => is_string($value) ? ['class' => $value] : $value];
    }

    public function __parse_reuse($value, $tag, $flags)
    {
        return ['$reuse' => $value];
    }

    public function __parse_autoloadRegister($value, $tag, $flags)
    {
        $out = [];

        foreach ((array)$value as $state) {
            if (is_string($state)) {
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

            if (0 !== strpos($state['path'], DIRECTORY_SEPARATOR)) {
                $state['path'] =
                    realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') .
                    DIRECTORY_SEPARATOR .
                    $state['path'];
            }

            $out[] = (new $class($state))->register();
        }

        return $out;
    }

    public function __parse_errorHandlerRegister($value, $tag, $flags)
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

    public static function read($filename, $offset = 0, $script_path = null)
    {
        require_once __DIR__ . '/Autoload.php';
        (new Autoload)->register();

        return self::factory((new static([
            'script_path' => $script_path,
            'config_path' => dirname(realpath($filename)),
        ]))->parseFile($filename, $offset));
    }

    public static function factory($state = null)
    {
        return Factory::single($state);
    }
}

