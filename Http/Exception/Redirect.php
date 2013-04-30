<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http\Exception;

class Redirect extends \RuntimeException
{
    public function __construct ($location, $code = 302, \Exception $previous = NULL)
    {
        parent::__construct($location, $code, $previous);
    }

    final public function getLocation()
    {
        return $this->getMessage();
    }
}
