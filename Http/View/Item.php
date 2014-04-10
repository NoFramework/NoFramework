<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http\View;

class Item extends \ArrayObject
{
    protected $charset = 'utf-8';
    protected $json_options = 0;
    protected $json_depth = 512;
    protected $template;

    public function __construct($option)
    {
        if (isset($option['data'])) {
            parent::__construct($option['data']);
            unset($option['data']);
        }

        foreach ($option as $property => $value) {
            $this->$property = $value;
        }
    }

    public function jsonEncode($data)
    {
        return json_encode($data, $this->json_options, $this->json_depth);
    }

    public function renderTemplate($blocks = false)
    {
        if (!$this->template) {
            throw new \LogicException('Template is not set');
        }

        $out = array_merge([
            'charset' => $this->charset
        ], (array)$this);

        return
            $blocks
            ? (
                is_array($blocks)
                ? array_map(function ($block) use ($out) {
                    return
                        'html' === $block
                        ? $this->template->render($out)
                        : $this->template->renderBlock($block, $out)
                    ;
                }, $blocks)
                : $this->template->renderBlock($blocks, $out)
            )
            : $this->template->render($out)
        ;
    }

    public function render($blocks = false)
    {
        if ($this->template) {
            if ($blocks and is_array($blocks)) {
                return $this->jsonEncode(array_merge(
                    ['success' => isset($this['success']) ? $this['success'] : true],
                    $this->renderTemplate($blocks)
                ));
            } else {
                return $this->renderTemplate($blocks);
            }
        } else {
            return $this->jsonEncode(array_merge(
                ['success' => true],
                (array)$this
            ));
        }
    }

    public function respondHeaders($response, $option = [])
    {
        if ($response->isHeadersSent()) {
            return $this;
        }

        $option = array_merge([
            'status' => 200,
            'content_type' => 'text/plain',
            'headers' => [],
        ], $option);

        $headers = array_merge([
            'Content-Type' => $option['content_type'] . ($this->charset ? '; charset=' . $this->charset : ''),
            'X-Powered-By' => null,
        ], $option['headers']);

        $response->status($option['status']);

        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        return $this;
    }

    /**
     * option:
     *   status (default: 200)
     *   content_type
     *   headers
     *   blocks
     */
    public function respond($response = false, $option = [])
    {
        $option = array_merge(['blocks' => false], $option);
        $payload = $this->render($option['blocks']);

        if ($response) {
            $this->respondHeaders($response, array_merge([
                'content_type' =>
                    ($this->template and (!$option['blocks'] or !is_array($option['blocks'])))
                    ? 'text/html'
                    : 'application/json'
                ,
            ], $option));
            $response->payload($payload);
        } else {
            echo $payload;
        }

        return $this;
    }
}

