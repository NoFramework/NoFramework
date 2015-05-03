<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Model;

class Cursor implements \IteratorAggregate
{
    protected $data;
    protected $mapper;
    protected $map;
    protected $key;

    public function __construct($data, $mapper = false)
    {
        $this->data = $data;
        $this->mapper = $mapper;
    }

    public function __call($name, $arguments)
    {
        $return = call_user_func_array([$this->data, $name], $arguments);

        return $return === $this->data ? $this : $return;
    }

    public function getIterator()
    {
        foreach ($this->data as $_id => $item) {
            $key = $this->key ? $item[$this->key] : $_id;

            if ($this->map) {
                $item = $this->mapper->map($this->map, $item);
            }

            yield $key => $item;
        }
    }

    public function map($map = 'Item')
    {
        $this->map = $map;

        return $this;
    }

    public function column($column)
    {
        return $this->map('column:' . $column);
    }

    public function key($key)
    {
        $this->key = $key;

        return $this;
    }

    public function one()
    {
        foreach ($this as $item) {
            return $item;
        }

        return false;
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }

    public function reduce($callable, $initial = null)
    {
        $result = $initial;

        foreach ($this as $item) {
            $result = $callable($result, $item);
        }

        return $result;
    }

    public function print_r($return = false)
    {
        $out = '';

        foreach ($this as $key => $item) {
            $out .= print_r(['key' => $key, 'item' => $item], $return);
        }

        return $return ? $out : true;
    }

    public function pages($option = [])
    {
        $option = array_merge([
            'page' => 1,
            'page_size' => 10,
            'length' => 6,
            'page_size_list' => false,
        ], $option);

        $page_size_list = $option['page_size_list'] ?: [];
        $page_size_list = array_combine($page_size_list, $page_size_list);

        $is_show_all = false;
        $count = $this->count();

        foreach ($page_size_list as $page_size) {
            if ($page_size >= $count) {
                $is_show_all = true;
                unset($page_size_list[$page_size]);
            }
        }

        if ($page_size_list and $is_show_all) {
            $page_size_list[0] = 0;
        }

        $pages_total = $option['page_size'] ? ceil($count / $option['page_size']) : 0;

        if ($pages_total < 2) {
            if ($page_size_list) {
                return [
                    'page_size' => [
                        'list' => $page_size_list,
                        'active' => 0,
                    ]
                ];
            }

            return [];
        }

        $active = max((int)$option['page'], 1);
        $length = $option['length'];

        $out = [
            'active' => $active
        ];

        if ($page_size_list) {
            $out['page_size'] = [
                'list' => $page_size_list,
                'active' => $option['page_size'],
            ];
        }

        if ($active > 1) {
            $out['previous'] = $active - 1;
        }

        if ($active < $pages_total) {
            $out['next'] = $active + 1;
        }

        $start = min(max($active - floor($length / 2), 1), max($pages_total - $length + 1, 1));
        $end = $start + min($length, $pages_total) - 1;

        if ($start > 1) {
            $out['list'][] = 1;
            $out['list'][] = false;
        }

        foreach (range($start, $end) as $page) {
            $out['list'][] = $page;
        }

        if ($end < $pages_total) {
            $out['list'][] = false;
            $out['list'][] = $pages_total;
        }
        
        return $out;
    }
}

