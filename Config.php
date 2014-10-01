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

class Config
{
    /**
     * service.pidfile: !local_path service.pid
     *
     * http.template.path: !start_path template
     * http.template.cache: !cache_path template_cache
     */
    protected $path = [
        'start' => '',
        'class' => 'class.php',
        'config' => '.config',
        'cache' => '.cache',
        'local' => '.local',
    ];

    public function __construct($path = [])
    {
        $this->path = array_filter(array_map(
            function ($path) {
                if (!$this->isRelativePath($path)) {
                    return realpath($path);
                }

                $start = dirname($_SERVER['SCRIPT_FILENAME']);

                do {
                    $start .= DIRECTORY_SEPARATOR;

                    if (is_dir($start . $path)) {
                        return realpath($start . $path);
                    }

                    $start .= '..';
                } while (
                    strtok(' ' . realpath($start), DIRECTORY_SEPARATOR) and
                    strtok(DIRECTORY_SEPARATOR)
                );
            },
            $path + $this->path
        ));
    }

    public function isRelativePath($path)
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        return
            0 !== strpos($path, '/') and
            ':' !== substr(strtok($path, '/'), -1)
        ;
    }

    public function path($name, $path = '')
    {
        $prefix = isset($this->path[$name]) ? $this->path[$name] : '';

        return
            $prefix .
            ($prefix && $path ? DIRECTORY_SEPARATOR : '') .
            $path
        ;
    }

    public function parse($source, $type = 'file', $is_object = true)
    {
        if ('file' === $type and $this->isRelativePath($source)) {
            $source = $this->path('config', $source);
        }

        $resolve = [];

        foreach ($this->path as $tag => $ignored) {
            $resolve['!' . $tag . '_path'] = function ($value) use ($tag) {
                return $this->path($tag, $value);
            };
        }

        foreach (get_class_methods($this) as $method) {
            if (0 === strpos($method, '__resolve_')) {
                $tag = substr($method, strlen('__resolve_'));
                $resolve['!' . $tag] = [$this, $method];
            }
        }

        $parse = [
            'file' => 'yaml_parse_file',
            'url' => 'yaml_parse_url',
            'string' => 'yaml_parse',
        ][$type];
        
        $out = $parse($source, 0, $ignored, $resolve);

        if ($is_object) {
            $class = &$out['class'];
            unset($out['class']);

            $out = $class ? new $class($out) : new Factory($out);
        }

        gc_collect_cycles();

        return $out;
    }

    public function parseLater($source, $type = 'file', $is_object = true)
    {
        yield $this->parse($source, $type, $is_object);
    }

    public function autoload($value = [])
    {
        $value = is_string($value) ? ['namespace' => $value] : $value;

        $namespace = &$value['namespace'];

        if (!$namespace or $namespace === __NAMESPACE__) {
            if (!class_exists(__NAMESPACE__ . '\Autoload', false)) {
                require __DIR__ . '/Autoload.php';
            }

            return (new Autoload)->register();
        }

        $path = &$value['path'];

        if (!isset($path)) {
            $path = str_replace(
                array_replace(['separator' => '\\'], $value)['separator'],
                DIRECTORY_SEPARATOR,
                $namespace
            );
        }
        
        if ($this->isRelativePath($path)) {
            $path = $this->path('class', $path);
        }

        $class = &$value['class'];
        unset($value['class']);

        $out = $class ? new $class($value) : new Autoload($value);

        return $out->register();
    }

    /**
     * format: 1d 1h 1m 1s
     */
    public function period($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        preg_match(
            '~^((\d+)d)?((\d+)h)?((\d+)m)?((\d+)s)?$~i',
            str_replace(' ', '', $value),
            $out
        );

        $out = array_pad($out, 9, null);

        return 
            ($out[2] * 24 * 60 * 60) +
            ($out[4] * 60 * 60) +
            ($out[6] * 60) +
            $out[8]
        ;
    }

    /**
     * ini_set: !ini_set
     *   date.timezone: UTC
     */
    protected function __resolve_ini_set($value)
    {
        foreach ($value as $key => $set) {
            ini_set($key, $set);
        }

        return $value;
    }

    /**
     * locale: !locale ru_RU.utf8
     */
    protected function __resolve_locale($value)
    {
        setlocale(LC_ALL, $value);

        return $value;
    }

    /**
     * time_limit: !time_limit 1h
     */
    protected function __resolve_time_limit($value)
    {
        set_time_limit($this->period($value));

        return $value;
    }

    /**
     * autoload: !autoload
     *   - NoFramework
     *   - namespace: Twig
     *     path: Twig/lib/Twig
     *     separator: _
     *   - Example
     *   - class: Example\Autoload\SomeNonPsr0
     */
    protected function __resolve_autoload($value)
    {
        return
            (
                is_array($value) and
                array_keys($value) === range(0, count($value) - 1)
            )
            ? array_map([$this, 'autoload'], $value)
            : $this->autoload($value)
        ;
    }

    /**
     * error_handler: !error_handler
     *   - recoverable error
     *   - user error
     *   - warning
     *   - user warning
     *   - notice
     *   - user notice
     *   - strict
     *   - deprecated
     *   - user deprecated
     */
    protected function __resolve_error_handler($value)
    {
        return (new ErrorHandler(
            $value

            ? array_reduce($value, function ($result, $item) {
                return $result | constant(
                    'E_' . str_replace(' ', '_', strtoupper($item))
                );
            }, 0)

            : -1
        ))->register();
    }

    /**
     * timeout: !period 30m
     */
    protected function __resolve_period($value)
    {
        return $this->period($value);
    }

    /**
     * config: !config
     */
    protected function __resolve_config($value)
    {
        return $this;
    }

    /**
     * model: !parse model.yaml
     *
     * data: !parse
     *   file: data.yaml
     *   is_object: false
     */
    protected function __resolve_parse($value)
    {
        $value = is_string($value) ? ['file' => $value] : $value;

        $out = $this->parseLater($value['file'], 'file', false);

        return
            array_replace(['is_object' => true], $value)['is_object']
            ? ['$newRoot' => $out]
            : $out
        ;
    }

    /**
     * db: !new
     *   class: NoFramework\Database\Mongo
     *   name: example
     *   username: example
     *   password: secret
     */
    protected function __resolve_new($value)
    {
        return ['$new' => $value];
    }

    /**
     * model: !newRoot
     *   class: NoFramework\Model\Collection
     *   db: !use global.db
     *   collection1.fs: !use collection2.fs  # store files in common gridfs
     */
    protected function __resolve_newRoot($value)
    {
        return ['$newRoot' => $value];
    }

    protected function __resolve_use($value)
    {
        return ['$use' => $value];
    }
}

