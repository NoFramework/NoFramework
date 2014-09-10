<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class Redirect extends \Exception
{
    public function __construct ($location, $code = 302) {
        parent::__construct($location, $code);
    }

    public function getLocation()
    {
        return parent::getMessage();
    }
}

