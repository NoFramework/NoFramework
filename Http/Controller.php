<?php
namespace NoFramework\Http;
use NoFramework\Http\Exception\ErrorStatus;

class Controller extends \NoFramework\Factory
{
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

    protected function &localSession()
    {
        return $this->session['local'][$this->localId()];
    }

    protected function __property_view()
    {
        return $this->reuse('view');
    }

    protected function __action_index($option = [])
    {
        $template = $this->localId(DIRECTORY_SEPARATOR);

        if ($this->view->hasTemplate($template)) {
            return $this->view($template);
        } else {
            throw new ErrorStatus(404);
        }
    }

    protected function view($template = false, $option = [])
    {
        if (is_string($option)) {
            $option = ['template' => $option];
        }

        if (!isset($option['filters']['url'])) {
            $option['filters']['url'] = function ($query) {
                return $this->request->getUrl($query);
            };
        }

        return $this->view[$option];
    }

    public function route($path)
    {
        $path = str_replace('.', '/', trim($path, '/'));
        $action = $path ? str_replace('/', '_', $path) : 'index';
        $next = strtok($path, '/');

        if ($this->isAction($action)) {
            return [
                'controller' => $this,
                'action' => $action,
            ];
        } elseif (isset($this->$next) and $this->$next instanceof self) {
            return $this->$next->route(strtok(''));
        }

        return false;
    }

    protected function isAction($action, $option = [])
    {
        return method_exists($this, '__action_' . $action);
    }

    public function __call($method, $argument) {
        if ($this->isAction($method)) {
            return $this->{'__action_' . $method}(isset($argument[0]) ? $argument[0] : null);

        } else {
            trigger_error(sprintf(
                'Call to undefined method %s::%s()',
                static::class,
                $method
            ), E_USER_ERROR);
        }
    }
}

