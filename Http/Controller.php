<?php
namespace NoFramework\Http;
use NoFramework\Http\Exception\ErrorStatus;

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
        $template = $this->localId(DIRECTORY_SEPARATOR);

        if ($this->view->hasTemplate($template)) {
            return $this->view($template);
        } else {
            throw new ErrorStatus(404);
        }
    }

    protected function view($option)
    {
        if (is_string($option)) {
            $option = ['template' => $option];
        }

        if (!isset($option['filters']['url'])) {
            $option['filters']['url'] = function ($query) {
                return $this->request->getUrl($query);
            };
        }

        return  $this->view[$option];
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

