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

class CompileLess extends Application
{
    protected $source;
    protected $destination;
    protected $is_compress = true;

    public function main()
    {
        $this->log(shell_exec(
            "lessc ' . ($this->is_compress ? '--compress ' : '') .
            $this->source . '/main.less > ' .
            $this->destination;
        ));

        $this->log(sprintf('Compiled %s', $this->destination));
    }
}

