<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class Application extends Controller
{
    protected function __property_response()
    {
        return new Response;
    }

    public function start($option = [])
    {
        $option = is_string($option) ? ['request' => $option] : $option;

        if ($this->is_cli and isset($_SERVER['argv'][1])) {
            $option['request'] = $_SERVER['argv'][1];
        }

        if ($request = &$option['request']) {
            $this->__property['request'] = new Request($request);
        }

        unset($option['request']);

        if ($this->session instanceof Session) {
            $this->session->start();
        }

        if ($session = &$option['session']) {
            foreach ($session as $key => $value) {
                $this->session[$key] = $value;
            }
        }

        unset($option['session']);

        $jobs = [];

        try {
            $option['job'] = function ($job) use (&$jobs) {
                $jobs[] = $job;
            };

            $this->main($option);

        } catch (Redirect $e) {
            $this->response->redirect($e->getLocation(), $e->getCode());

        } catch (Error $e) {
            $this->respondError($e);

        } catch (\Exception $e) {
            $this->respondError(new Error(500, [], $e));

        } finally {
            if ($this->session instanceof Session) {
                $this->session->clear();
                $this->session->writeClose();
            }
        }

        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        foreach ($jobs as $job) {
            $job();
        }
    }

    protected function respond($view)
    {
        return $view->respond($this->response);
    }

    protected function main($option)
    {
        $path = $this->request->path;
        $method = $this->request->method;

        if ($route = $this->route($path, $method)) {
            if (
                'GET' === $method and
                $route->correct_path and
                $route->correct_path !== $path
            ) {
                $this->redirect($this->request->url($route->correct_path), 301);
            }

            $this->respond($route->controller->{$route->action}($option));

        } else {
            $this->error(404);
        }
    }

    protected function respondError($exception)
    {
        $status = $exception->getCode();

        return $this->view([
            'status' => $status,
            'content_type' => 'text/plain',
            'headers' => $exception->option('headers') ?: [],
            'template' => sprintf(
                '%1$s - %2$s' . PHP_EOL .
                'method: %3$s' . PHP_EOL .
                'url: %4$s' . PHP_EOL .
                '%5$s',
                $status,
                $exception->getMessage(),
                $this->request->method,
                $this->request,
                ini_get('display_errors') ? $exception->getPrevious() : null
            ),
        ])->respond($this->response);
    }
}

