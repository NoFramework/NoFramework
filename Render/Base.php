<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Render;

abstract class Base
{
    use \NoFramework\MagicProperties;

    abstract public function __invoke($data);
}

