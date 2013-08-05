<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\File;

class Path
{
    use \NoFramework\MagicProperties;

    protected $separator = DIRECTORY_SEPARATOR;

    protected function __property_split()
    {
        return explode($this->separator, $this->path);
    }

    protected function __property_dirname()
    {
        return $this->slice(0, -1) ?: '/';
    }

    protected function __property_basename()
    {
        return $this->slice(-1);
    }

    protected function __property_extension()
    {
        return substr(strrchr($this->basename, '.'), 1);
    }

    protected function __property_filename()
    {
        return
            $this->extension
            ? substr($this->basename, 0, -strlen($this->extension) - 1)
            : $this->basename;
    }

    protected function __property_path()
    {
        return
            ($this->dirname
                ? rtrim($this->dirname, $this->separator) . $this->separator
                : '') .
            $this->filename . ($this->extension ? '.' . $this->extension : '');
    }

    public function __construct($state = [])
    {
        if (isset($state['separator'])) {
            $this->separator = $state['separator'];
            unset($state['separator']);
        }

        $this->__property = $state;
    }

    public function __toString()
    {
        return $this->path;
    }

    public function slice($offset = 0, $length = null)
    {
        return implode($this->separator,
            array_slice($this->split, $offset, $length));
    }
}

