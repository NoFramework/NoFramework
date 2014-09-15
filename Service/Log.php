<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Service;

class Log
{
    protected $type_filter;
    protected $is_output_date = true;

    protected function out($message)
    {
        echo $message . PHP_EOL;
    }

    protected function dateFormat() {
        return '[' . date('j-M-Y H:i:s') . ']';
    }

    public function write($message, $type = false)
    {
        if (
            !$this->type_filter or
            !$type or
            false !== array_search($type, $this->type_filter)
        ) {
            $this->out(
                ($this->is_output_date ? $this->dateFormat() . ' ' : '') .
                ($type ? '[' . $type . '] ' : '') . trim($message)
            );
        }

        return $this;
    }
}

