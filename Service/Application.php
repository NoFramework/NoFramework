<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service;

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

    protected function main()
    {
       foreach ($this->run as $action) {
           $this->action->$action->run();
       } 
    }

    public function start($main = false)
    {
        if ($this->pidfile) {
            if (!$this->pidfile->check($this->timeout)) {
                $this->pidfile->write();
                $result = parent::start($main);
                $this->pidfile->delete();
                return $result;
            }
        } else {
            return parent::start($main);
        }
    }
}

