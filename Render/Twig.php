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

    protected function __property_twig()
    {
        $loader = new \Twig_Loader_Filesystem($this->template_path);
        return new \Twig_Environment($loader, $this->configure());
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

        if ( $this->cache_path ) {
            $config['cache'] = $this->cache_path;
        }

        return $config;
    }

    public function block($block, $data)
    {
        return $this->loaded_template->renderBlock(
            $block,
            is_array($data) ? $data : compact('data')
        );
    }

    public function __invoke($data)
    {
        return $this->loaded_template->render(
            is_array($data) ? $data : compact('data')
        );
    }
}

