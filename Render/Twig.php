<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Render;

class Twig extends \NoFramework\Render
{
    public $template;
    public $extension = 'html';
    public $template_path;
    public $cache_path;
    public $is_debug;
    public $is_auto_reload;
    public $is_strict_variables;
    public $charset;
    public $base_template_class;
    public $autoescape;
    public $optimizations;

    protected function __property_twig()
    {
        return new \Twig_Environment(
            new \Twig_Loader_Filesystem($this->template_path),
            $this->configure()
        );
    }

    protected function __property_loaded_template()
    {
        return $this->twig->loadTemplate(
            $this->template . ($this->extension ? '.' . $this->extension : '')
        );
    }

    protected function configure()
    {
        $config = [];

        foreach ([
            'cache_path' => 'cache',
            'is_debug' => 'debug',
            'is_auto_reload' => 'auto_reload',
            'is_strict_variables' => 'strict_variables',
            'charset' => 'charset',
            'base_template_class' => 'base_tempale_path',
            'autoescape' => 'autoescape',
            'optimizations' => 'optimizations',
        ] as $property => $option) {
            if (isset($this->$property)) {
                $config[$option] = $this->$property;
            }
        }

        return $config;
    }

    public function __invoke($data, $block = false)
    {
        $data = is_array($data) ? $data : compact('data');
        return false === $block
            ? $this->loaded_template->render($data)
            : $this->loaded_template->renderBlock($block, $data);
    }
}

