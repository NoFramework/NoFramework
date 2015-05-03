<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Service

class CompileJs extends Application
{
    protected $pipe = 'cat';
    protected $source;
    protected $destination;

    public function main()
    {
        $out = [];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->source)
        ) as $file) {
            if ($file->isFile() and 'js' === $file->getExtension()) {
                $keys = explode(
                    DIRECTORY_SEPARATOR,
                    substr($file, strlen($this->source) + 1, -3)
                );

                foreach (range(1, count($keys)) as $level) {
                    $item = &$out[implode('.', array_slice($keys, 0, $level))];
                }
                
                $item = file_get_contents($file);
            }
        }

        ksort($out);

        $p = popen($this->pipe . ' >' . $this->destination, 'w');

        fwrite($p, 'window.jQuery(function ($) {' . PHP_EOL);
        fwrite($p, '"use strict";' . PHP_EOL . PHP_EOL);
        fwrite($p, 'var Trait = {};' . PHP_EOL . PHP_EOL);

        foreach ($out as $name => $trait) {
            $trait = str_replace('"use strict";' . PHP_EOL, '', $trait);
            fwrite($p,
                sprintf("Trait.%s = %s" . PHP_EOL, $name, $trait ?: '{}')
            );
        }

        fwrite($p, 'var application = Trait.Application.apply({})' . PHP_EOL . PHP_EOL);
        fwrite($p, 'application.main()' . PHP_EOL . PHP_EOL);
        fwrite($p, '})');

        pclose($p);

        $this->log(sprintf('Compiled %s', $this->destination));
    }
}

