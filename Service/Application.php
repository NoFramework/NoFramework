<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service;
use \NoFramework\Log;

class Application extends \NoFramework\Application
{
    protected $pidfile;
    protected $timeout = 3600;
    protected $action;

    protected function __property_run()
    {
        $run = $_SERVER['argv'];
        array_shift($run);
        return $run;
    }

    protected function __property_error_log()
    {
        return new Log\Error;
    }

    protected function main()
    {
       foreach ($this->run as $action) {
           $this->action->$action->run();
       } 
    }

    protected function logError($e)
    {
        if ( $this->error_log ) {
            $this->error_log->write((string)$e);
        }

        return $this;
    }

    public function start($main = false)
    {
        if ( $this->pidfile ) {
            if ( $this->pidfile->check($this->timeout) ) {
                exit;
            }

            $this->pidfile->write();
        }

        try {
            parent::start($main);

        } catch ( \Exception $e ) {
            $this->logError($e);
            exit;
        }

        if ( $this->pidfile ) {
            $this->pidfile->delete();
        }
    }
}

