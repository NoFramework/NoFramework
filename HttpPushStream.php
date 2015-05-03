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

class HttpPushStream
{
    protected $host;

    const TIMEOUT = 3;

    public function status($channel)
    {
        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL => 'http://localhost/push/' . $channel,
            CURLOPT_HTTPHEADER => ['Host: ' . $this->host],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => static::TIMEOUT,
        ]);

        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if (200 != $info['http_code']) {
            throw new \RunTimeException(sprintf(
                'code: %s %s',
                $info['http_code'],
                $response
            ));
        }

        return json_decode($response);
    }

    public function push($channel, $data)
    {
        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL => 'http://localhost/push/' . $channel,
            CURLOPT_HTTPHEADER => ['Host: ' . $this->host],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => static::TIMEOUT,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if (200 != $info['http_code']) {
            throw new \RunTimeException(sprintf(
                'code: %s %s',
                $info['http_code'],
                $response
            ));
        }

        return json_decode($response);
    }

    public function remove($channel)
    {
        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL => 'http://localhost/push/' . $channel,
            CURLOPT_HTTPHEADER => ['Host: ' . $this->host],
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => static::TIMEOUT,
        ]);

        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if (200 != $info['http_code']) {
            throw new \RunTimeException(sprintf(
                'code: %s %s',
                $info['http_code'],
                $response
            ));
        }

        return json_decode($response);
    }

    public function stat($channel = false)
    {
        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL => 'http://localhost/stat/' . $channel,
            CURLOPT_HTTPHEADER => ['Host: ' . $this->host],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => static::TIMEOUT,
        ]);

        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if (200 != $info['http_code']) {
            throw new \RunTimeException(sprintf(
                'code: %s %s',
                $info['http_code'],
                $response
            ));
        }

        return json_decode($response);
    }
}

