<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;
use NoFramework\Http\Exception\ErrorStatus;
use NoFramework\Http\Exception\Redirect;

abstract class Application
{
    use \NoFramework\MagicProperties;

    protected $redirect_index;
    protected $route;
    protected $controller;

    protected function __property_request()
    {
        return new Request;
    }

    protected function __property_response()
    {
        return new Response;
    }

    protected function __property_view()
    {
        return new View\Factory;
    }

    protected function __property_session()
    {
        return (new Session)->start();
    }

    protected function __property_display_errors()
    {
        return ini_get('display_errors');
    }

    protected function __property_is_cli()
    {
        return 'cli' === php_sapi_name();
    }

    protected function respond($view = null, $status = 200, $headers = [])
    {
        if (is_string($view)) {
            $this->view[null]->respondHeaders($this->response, [
                'status' => $status,
                'headers' => $headers,
            ]);

            $this->response->payload($view);
        } else {
            $this->view[$view]->respond($this->response, [
                'status' => $status,
                'headers' => $headers,
                'blocks' =>
                    is_string($this->request['blocks'])
                    ? array_combine(
                        explode(',', $this->request['blocks']),
                        explode(',', $this->request['blocks'])
                    )
                    : $this->request['blocks']
            ]);
        }

        return $this;
    }

    protected function respondError($exception)
    {
        $status = $exception->getCode();
        $parameters = $exception->getParameters();

        return $this->respond(
            sprintf(
                '%1$s - %2$s' . PHP_EOL .
                'method: %3$s' . PHP_EOL .
                'url: %4$s' . PHP_EOL .
                '%5$s',
                $status,
                $exception->getMessage(),
                $this->request->method,
                $this->request,
                $this->display_errors
                    ? $exception->getPrevious()
                    : null
            ),
            $status,
            isset($parameters['headers']) ? $parameters['headers'] : []
        );
    }

    public function route($method, $path)
    {
        foreach ((array)$this->route as $route) {
            if (is_string($route)) {
                $route = ['path' => $route];
            }

            $route = array_merge([
                'method' => ['GET', 'POST'],
                'command' => 'index',
                'controller' =>
                    str_replace('/', '.', trim($route['path'], '/')),
            ], $route);

            if (
                $path === $route['path'] and
                in_array($method, (array)$route['method']) and
                $controller = $this->controller->reuse($route['controller'])
            ) {
                return [
                    'controller' => $this->controller,
                    'command' => $route['command'],
                ];
            }
        }

        return false;
    }

    protected function main($emit_jobs)
    {
        $path = $this->request->path;
        $method = $this->request->method;

        if ('/' === $path and $this->redirect_index) {
            throw new Redirect($this->request->getUrl([
                'path' => $this->redirect_index,
                'is_only' => true,
            ]));
        }

        $route = $this->route($method, $path);

        if (!$route) {
            $route = $this->controller->route($path);
        }

        if ($route) {
            if ('GET' === $method and '/' !== substr($path, -1)) {
                throw new Redirect($this->request->getUrl($path . '/'));
            }
        } else {
            throw new ErrorStatus(404);
        }

        $this->respond($route['controller']->{$route['command']}([
            'emit_jobs' => $emit_jobs
        ]));
    }

    public function start($option = [])
    {
        if (isset($_SERVER['argv'][1]) and !isset($option['request'])) {
            $option['request'] = $_SERVER['argv'][1];
        }

        if (is_string($option)) {
            $option = ['request' => $option];
        }

        if (isset($option['request'])) {
            $this->__property['request'] = new Request($option['request']);
        }

        if (isset($option['session'])) {
            foreach ($option['session'] as $key => $value) {
                $this->session[$key] = $value;
            }
        }

        $jobs = [];

        try {
            $emit_jobs = function ($job) use (&$jobs) {
                $jobs[] = $job;
            };

            $this->main($emit_jobs);

        } catch (Redirect $e) {
            $this->response->redirect($e->getLocation(), $e->getCode());

        } catch (ErrorStatus $e) {
            $this->respondError($e);

        } catch (\Exception $e) {
            $this->respondError(new ErrorStatus(500, [], $e));

        }

        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        if ($this->session instanceof Session) {
            $this->session->writeClose();
        }

        foreach ($jobs as $job) {
            $job();
        }
    }
}

