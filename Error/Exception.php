<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Error;

abstract class Exception extends \Exception
{
    protected $context;

    public function __construct(
        $errno, $errstr, $errfile, $errline, $errcontext,
        \Exception $previous = null)
    {
        parent::__construct($errstr, $errno, $previous);
        $this->file = $errfile;
        $this->line = $errline;
        $this->context = $errcontext;
    }

    final public function getContext()
    {
        return $this->context;
    }
}

