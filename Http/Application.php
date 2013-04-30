<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

abstract class Application extends \NoFramework\Application
{
    protected /*NoFramework\Factory*/ $controller;

    protected /*NoFramework\Http\Request*/ function __property_request()
    {
        return new Request;
    }

    protected /*NoFramework\Http\Response*/ function __property_response()
    {
        return new Response;
    }

    protected /*\NoFramework\Http\Session*/ function __property_session()
    {
        return (new Session)->start();
    }

    protected /*boolean*/ function __property_display_errors()
    {
        return ini_get('display_errors');
    }

    protected /*NoFramework\Http\View*/ function __property_error_view()
    {
        $view = new View;
        $view->response = $this->response;
        $view->data_properties = ['status'];
        $view->content_type = 'text/plain';
        $view->render = function ($data) {
            extract($data);

            return sprintf(
                '%1$s - %2$s' . PHP_EOL .
                'method: %3$s' . PHP_EOL .
                'url: %4$s' . PHP_EOL .
                '%5$s',
                $status,
                $message,
                $method,
                $url,
                $exception
            );
        };

        return $view;
    }

    protected function showError($exception)
    {
        $this->error_view->status = $exception->getCode();

        $this->error_view->data = [
            'message' => $exception->getMessage(),
            'method' => $this->request->method,
            'url' => (string)$this->request,
            'exception' => $this->display_errors ? $exception->getPrevious() : null
        ];

        $parameters = $exception->getParameters();

        if ( isset($parameters['headers']) ) {
            $this->error_view->headers = $parameters['headers'];
        }

        $this->error_view->render();

        return $this;
    }

    protected function redirect($location, $status)
    {
        $this->response->redirect($location, $status);
        return $this;
    }

    public function start($main = false)
    {
        try
        {
            return parent::start($main);
        }
        catch (Exception\Redirect $e)
        {
            $this->redirect($e->getLocation(), $e->getCode());
        }
        catch (Exception\ErrorStatus $e)
        {
            $this->showError($e);
        }
        catch (\Exception $e)
        {
            $this->showError(new Exception\ErrorStatus(500, [], $e));
        }
    }
}

