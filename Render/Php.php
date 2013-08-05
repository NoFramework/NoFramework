<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Render; 
use \NoFramework\File\Path;

class Php extends \NoFramework\Render
{
    public $template;
    public $extension = 'html';
    public $is_short_open_tag = true;
    public $template_path = '';

    protected function __property_path()
    {
        $path = new Path([
            'dirname' => $this->template_path,
            'filename' => $this->template,
            'extension' => $this->extension,
        ]);

        if (!is_file($path) ) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to load template file %s. Template path: %s',
                $path->basename,
                $path->dirname
            ));
        }

        return $path;
    }

    public function __invoke($data)
    {
        if ($this->is_short_open_tag && !ini_get('short_open_tag')) {
            throw new \RuntimeException('short_open_tag=1 is required');
        }

        if (is_array($data)) {
            unset($data);
            extract(func_get_arg(0));
        }

        ob_start();
        require $this->path;
        return ob_get_clean();
    }
}

