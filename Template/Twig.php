<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Template;

class Twig
{
    use \NoFramework\Magic;

    protected $path;
    protected $extension = '.html.twig';

    protected $debug = false;
    protected $charset = 'UTF-8';
    protected $base_template_class = 'Twig_Template';
    protected $strict_variables = false;
    protected $autoescape = 'html';
    protected $cache = false;
    protected $auto_reload = null;
    protected $optimizations = -1;

    protected function __property_file_loader()
    {
        return new \Twig_Loader_Filesystem($this->path);
    }

    protected function __property_string_loader()
    {
        return new \Twig_Loader_String();
    }

    public function exists($template)
    {
        return $this->file_loader->exists($template . $this->extension);
    }

    public function load($template, $filters = [], $type = 'file')
    {
        $options = [
            'debug',
            'charset',
            'base_template_class',
            'strict_variables',
            'autoescape',
            'cache',
            'auto_reload',
            'optimizations',
        ];

        $options = array_map(function ($property) {
            return $this->$property;
        }, array_combine($options, $options));

        $environment = new \Twig_Environment(
            $this->{$type . '_loader'},
            $options
        );

        if ($options['debug']) {
            $environment->addExtension(new \Twig_Extension_Debug);
        }

        $environment->addExtension(new \Twig_Extension_StringLoader);

        foreach ($filters as $name => $filter) {
            $options = [];

            if (
                !is_callable($filter) and
                is_array($filter) and
                is_callable(reset($filter))
            ) {
                $options = $filter;
                $filter = array_shift($options);
            }

            $environment->addFilter(
                new \Twig_SimpleFilter($name, $filter, $options)
            );
        }

        return $environment->loadTemplate(
            $template . ('file' === $type ? $this->extension : '')
        );
    }
}

