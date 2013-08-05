<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

abstract class Log
{
    protected $type_filter = false;

    abstract protected function onWrite($message, $type);

    protected function dateFormat() {
        return '[' . date('j-M-Y H:i:s') . ']';
    }

    public function write($message, $type = false)
    {
        if (false === $this->type_filter
        or false === $type
        or false !== array_search($type, $this->type_filter)
        ) {
            $this->onWrite(trim($message), $type);
        }

        return $this;
    }

    public function each($items, $format = false, $type = false)
    {
        foreach ($items as $key => $item) {
            if ($format) {
                $data = [$key];

                if (is_array($item) or $item instanceof \Traversable) {
                    foreach ($item as $value) {
                        $data[] = print_r($value, true);
                    }
                } else {
                    $data[] = print_r($item, true);
                }

                $message = vsprintf($format, $data);

            } else {
                $message = print_r(compact('key', 'item') , true);
            }

            $this->write($message,
                method_exists($type, '__invoke')
                ? $type($item, $key) ?: false
                : $type
            );
        }
    }
}

