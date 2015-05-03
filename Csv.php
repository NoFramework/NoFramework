<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework;

class Csv
{
    public function export($data, $head = [])
    {
        $fp = fopen('php://temp/maxmemory:256000', 'w');

        if (!$fp) {
            throw new \RuntimeException('Could not open temporary memory data');
        }

        foreach ($data as $item) {
            $head += array_combine(array_keys($item), array_keys($item));
        }

        fputcsv($fp, array_values($head));

        foreach ($data as $item) {
            fputcsv($fp, array_values(
                array_replace(array_fill_keys(array_keys($head), null), $item)
            ));
        }

        $out = '';

        fseek($fp, 0);

        while (($line = fgets($fp)) !== false) {
            $out .= $line;
        }

        fclose($fp);

        return $out;
    }
}

