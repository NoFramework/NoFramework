<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Render; 

class Smarty extends Base
{
    public $template;
    public $cache_path = false;
    public $caching = 0;
    public $cache_lifetime = -1;
    public $cache_modified_check = true;
    public $use_sub_dirs = true;
    public $extension = 'tpl';
    public $template_path;
    public $compile_path;

    protected function __property_smarty()
    {
        return $this->configure(new \Smarty);
    }

    protected function configure($smarty)
    {
        $smarty->setCompileDir($this->compile_path);
        $smarty->setCacheDir($this->cache_path);
        $smarty->setTemplateDir($this->template_path);
        $smarty->setCaching($this->caching);
        $smarty->setCacheLifetime($this->cache_lifetime);
        $smarty->setCacheModifiedCheck($this->cache_modified_check);
        $smarty->setUseSubDirs($this->use_sub_dirs);

        return $smarty;
    }

    public function __invoke($data)
    {
        $this->smarty->clearAllAssign();

        if ($this->data) {
            $this->smarty->assign(is_array($data) ? $data : compact('data'));
        }

        return $this->smarty->Fetch(
            $this->template . ($this->extension ? '.' . $this->extension : '')
        );
    }
}

