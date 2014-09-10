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
        if ($this->session instanceof Session) {
            $this->session->start();
        }

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

    protected function main($option)
    {
        if ($route = $this->route($this->request->path)) {
            $route
                ->controller
                ->{$route->action}($option)
                ->respond($this->response)
            ;
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

