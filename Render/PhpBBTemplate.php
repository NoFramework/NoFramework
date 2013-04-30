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

class PhpBBTemplate extends \NoFramework\Render
{
    public $template;
    public $extension = 'tpl';
    public $template_path;

    protected function __property_path()
    {
        $path = new Path;
        $path->dirname = $this->template_path;
        $path->filename = $this->template;
        $path->extension = $this->extension;

        if ( ! is_file($path) ) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to load template file %s. Template path: %s',
                $path->basename,
                $path->dirname
            ));
        }

        return $path;
    }

    protected function __property_phpbb_template()
    {
        return new \PhpBBTemplate;
    }

    public function __invoke($data)
    {
        $this->phpbb_template->set_filenames([$this->template => $this->path]);

        foreach ( $data as $name => $value ) {
            if ( is_array($value) or $value instanceof Traversable ) {
                foreach ( $value as $block_name_value ) {
                    foreach ( $block_name_value as $block_name => $block_value ) {
                        $this->phpbb_template->assign_block_vars($block_name, $block_value);
                        #printf('%s = %s' . PHP_EOL, $block_name, print_r($block_value, true));
                    }
                }

            } else {
                $this->phpbb_template->assign_var($name, $value);
            }
        }

        return $this->phpbb_template->get_text_from_handle($this->template);
    }
}

