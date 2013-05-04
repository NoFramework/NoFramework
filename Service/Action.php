<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service;

abstract class Action
{
    use \NoFramework\MagicProperties;

    protected function __property_log()
    {
        return new \NoFramework\Log\Output;
    }

    protected function __property_error_log()
    {
        return new \NoFramework\Log\Error;
    }

    abstract public function run();
}

