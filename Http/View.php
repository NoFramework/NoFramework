<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http;

class View
{
    use \NoFramework\MagicProperties;

    public $status = 200;
    public $charset = 'utf-8';
    public $content_type = 'text/html';
    public $response;
    public $data = [];
    public $headers = [];
    public $render = false;
    public $template = false;
    public $data_properties = ['charset'];

    protected function payload()
    {
        $data = $this->data;

        if ($render = $this->render) {
            if ($this->data_properties) {
                foreach ($this->data_properties as $property) {
                    if (!isset($data[$property])) {
                        $data[$property] = $this->$property;
                    }
                }
            }

            if ($this->template) {
                $render->template = $this->template;
            }

            $data = $render($data);
        }

        $this->response->payload($data);

        return $this;
    }

    protected function headers()
    {
        if (!$this->response->isHeadersSent()) {
            $this->response
            -> status($this->status)
            -> header(
                'Content-Type',
                $this->content_type .
                    ($this->charset ? '; charset=' . $this->charset : '')
               )
            -> header('X-Powered-By');

            foreach ($this->headers as $name => $value) {
                $this->response->header($name, $value);
            }
        }

        return $this;
    }

    public function render()
    {
        return $this->headers()->payload();
    }
}

