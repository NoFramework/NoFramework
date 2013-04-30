<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Application
{
    use MagicProperties;

    protected function main()
    {
        trigger_error(sprintf('%s::%s is not implemented', get_called_class(), __FUNCTION__), E_USER_WARNING);
    }

    public function start($main = false)
    {
        return $main ? $main($this) : $this->main();
    }
}

