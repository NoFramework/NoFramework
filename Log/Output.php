<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Log;

class Output extends \NoFramework\Log
{
    protected function onWrite($message, $type)
    {
        echo $this->dateFormat() . ' ' .
            ($type ? '[' . $type . '] ' : '') . $message . PHP_EOL;

        return true;
    }
}

