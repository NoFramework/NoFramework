<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

use NoFramework\Template\Twig as Template;
use NoFramework\Model\Collection as Db;

class Controller extends \NoFramework\Factory
{
    protected function __property_template()
    {
        return $this->setState(new Template, [
            'path' => dirname($_SERVER['SCRIPT_FILENAME']) . '/template'
        ]);
    }

    protected function __property_request()
    {
        return new Request;
    }

    protected function __property_session()
    {
        return $this->is_cli ? new \ArrayObject : new Session;
    }

    protected function __property_is_cli()
    {
        return 'cli' === php_sapi_name();
    }

    protected function __property_path()
    {
        return '/';
    }

    protected function __property_db()
    {
        return new Db;
    }

    public function __call($method, $argument)
    {
        if ($this->isAction($method)) {
            $option = &$argument[0];

            return $this->normalizeView(
                $this->{'__action_' . $method}($option)
            );
        }

        return parent::__call($method, $argument);
    }

    public function __filter_url($query)
    {
        return $this->url($query);
    }

    public function isAction($action)
    {
        return
            $this->isStrictName($action) and
            method_exists($this, '__action_' . $action)
        ;
    }

    public function route($path)
    {
        $path = trim($path, '/');
        $action = $path ?: 'index';

        if ($this->isAction($action)) {
            return (object)[
                'controller' => $this,
                'action' => $action,
            ];
        }

        $next = strtok($path, '/');
        $next_path = strtok('');

        if (
            $next and
            isset($this->$next) and
            $this->$next instanceof self
        ) {
            return $this->$next->route($next_path);
        }

        return false;
    }

    public function url($query)
    {
        return $this->request->url($query, $this->path);
    }

    protected function view($state = [], $data = [])
    {
        return new View($state, $data);
    }

    protected function normalizeView($view)
    {
        if ($view instanceof View) {
            return $view;
        }

        $state = [];
        $data = [];

        if (!isset($view)) {
            $state['is_silent'] = true;

        } elseif (is_bool($view)) {
            $data['success'] = $view;

        } elseif (is_callable($view)) {
            $state['template'] = $view;

        } elseif (is_string($view)) {
            $state['template'] = $view;
            $state['content_type'] = 'text/plain';

        } elseif (is_array($view)) {
            $data = $view;

            $state['template'] = &$data['template'];
            unset($data['template']);

            if (is_string($state['template'])) {
                $state['template'] = $this->template($state['template']);
            }
        } else {
            $state['template'] = $view;
        }

        return $this->view($state, $data);
    }
    
    protected function template($name, $filters = [], $type = 'file')
    {
        foreach (get_class_methods($this) as $method) {
            if (0 === strpos($method, '__filter_')) {
                $filter = substr($method, strlen('__filter_'));
                $filters[$filter] = [$this, $method];

            } elseif (0 === strpos($method, '__widget_')) {
                $filter = substr($method, strlen('__widget_'));

                $filters['widget_' . $filter] = [
                    [$this, $method],
                    'is_safe' => ['html']
                ];
            }
        }

        return $this->template->load($name, $filters, $type);
    }

    protected function error($code, $option = [])
    {
        throw new Error($code, $option);
    }

    protected function redirect($location, $code = 302)
    {
        throw new Redirect($location, $code);
    }

    protected function __resolve_new($value = null, $as = null)
    {
        $auto = $this->autoNamespace($as);

        if (!class_exists($auto)) {
            $auto = $this->use->normalizeClass('Controller');
        }

        $class =
            $this->popClass($value) ?:
            (class_exists($auto) ? $auto : self::class)
        ;

        if (is_a($class, self::class, true)) {
            if ($as and 0 !== strpos($as, '.')) {
                $value['path'] =
                    $this->path . str_replace('.' , '/', $as) . '/';
            }

            $value += [
                'template' => $this->{'$template'},
                'request' => $this->{'$request'},
                'session' => $this->{'$session'},
                'db' => $this->{'$db'},
            ];
        }

        $value['class'] = '\\' . $class;

        return parent::__resolve_new($value, $as);
    }

    protected function __action_index()
    {
        $path = (
            isset($this->template->search_path)
            ? $this->template->search_path
            : ''
        ) . $this->path;

        $template = $path . 'index';

        if (!$this->template->exists($template)) {
            $template = substr($path, 0, -1);
        }

        if (!$this->template->exists($template)) {
            $this->error(404);
        }

        if (
            'GET' === $this->request->method and
            $this->path !== $this->request->path
        ) {
            $this->redirect($this->request->url($this->path), 301);
        }

        return ['template' => $template];
    }
}

