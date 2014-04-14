<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Model extends Factory
{
    public function __call($method, $argument)
    {
        return $this->reuse('storage')->$method(
            $this->localId(),
            isset($argument[0]) ? $argument[0] : []
        );
    }
}

