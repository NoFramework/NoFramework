<?php
namespace NoFramework\Http;
use Exception\ErrorStatus;

class Controller extends \NoFramework\Factory
{
    use \NoFramework\Command;

    protected function model($name = false)
    {
        $model = $this->reuse('model');

        if ($name) {
            $model = $model->reuse($name);
        }

        return $model;
    }

    protected function __property_request()
    {
        return $this->reuse('request');
    }

    protected function __property_session()
    {
        return $this->reuse('session');
    }

    protected function __property_view()
    {
        return $this->reuse('view');
    }

    protected function __command_index($option)
    {
        return $this->view[$this->localId()];
    }

    public function route($path)
    {
        $path = str_replace('.', '/', trim($path, '/'));
        $command = $path ? str_replace('/', '_', $path) : 'index';
        $next = strtok($path, '/');

        if ($this->commandExists($command)) {
            return [
                'controller' => $this,
                'command' => $command,
            ];
        } elseif (isset($this->$next) and $this->$next instanceof self) {
            return $this->$next->route(strtok(''));
        }

        return false;
    }
}

