<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Log;

class File extends \NoFramework\Log
{
    use \NoFramework\MagicProperties;

    public /*NoFramework\File\Path | string*/ $path;

    protected /*resource*/ function __property_handle()
    {
        return fopen($this->path, 'a');
    }

    public function __construct($path)
    {
        if (is_resource($path)) {
            $this->handle = $path;

        } else {
            $this->path = $path;
        }
    }

    protected function onWrite($message, $type)
    {
        return $this->handle and fwrite(
            $this->handle,
            $this->dateFormat() . ' ' .
            ($type ? '[' . $type . '] ' : '') . $message . PHP_EOL
        );
    }

    public function setPath( $path )
    {
        $this->path = $path;
    }

    public function __destruct()
    {
        if (isset($this->handle) and is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}

