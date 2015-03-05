<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Http;

class View extends \ArrayObject
{
    public $status = 200;
    public $content_type;
    public $charset = 'utf-8';
    public $headers = [];
    public $cookies = [];
    public $is_silent = false;
    public $template;
    public $block;
    public $success = true;

    public function __construct($state = [], $data = [])
    {
        foreach ($state as $key => $value) {
            $this->$key = $value;
        }

        parent::__construct($data);
    }

    public function render()
    {
        if ($this->is_silent) {
            return '';
        }

        $data = $this->getArrayCopy();

        if ($this->template and !is_array($this->block)) {
            return $this->template($data, $this->block);

        } elseif ($this->template) {
            return array_map(function ($block) use ($data) {
                return $this->template($data, $block);
            }, $this->block);
        }

        return $data;
    }

    public function respond($response)
    {
        $this->respondHead($response);

        $data = $this->render();

        if (!$data) {
            return $response;

        } elseif (is_array($data)) {
            return $response->output($this->json($data));
        }

        return $response->output($data);
    }

    public function respondHead($response)
    {
        if ($this->is_silent or $response->isHeadersSent()) {
            return $response;
        }

        if ($content_type = $this->getContentType()) {
            $headers['Content-Type'] = $content_type .
                ($this->charset ? '; charset=' . $this->charset : '');
        }

        $response->status($this->status);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        foreach ($this->cookies as $key => $value) {
            $response->cookie($key, $value);
        }

        return $response;
    }

    public function getContentType()
    {
        return   
            isset($this->content_type)
            ? $this->content_type
            : (
                ($this->template and !is_array($this->block))
                ? 'text/html'
                : 'application/json'
            )
        ;
    }

    public function template($data, $block = false)
    {
        if (is_callable($this->template)) {
            return call_user_func($this->template, $data, $block);

        } elseif (is_string($this->template)) {
            return $block ? '' : $this->template;

        } else {
            return
                $block
                ? $this->template->renderBlock($block, $data)
                : $this->template->render($data)
            ;
        }
    }

    public function json($data, $options = 0)
    {
        if (isset($this->success)) {
            $data += ['success' => $this->success];
        }

        return json_encode($data, $options);
    }
}

