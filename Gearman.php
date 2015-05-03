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

class Gearman
{
    use Magic;

    protected $server = '127.0.0.1:4730';
    protected $prefix;

    protected function __property_client()
    {
        $return = new \GearmanClient();
        $return->addServers($this->server);

        return $return;
    }

    protected function __property_worker()
    {
        $return = new \GearmanWorker();
        $return->addServers($this->server);

        return $return;
    }

    public function addMethod($method, $callback)
    {
        return $this->worker->addFunction(
            $this->prefix . $method,
            function ($job) use ($callback) {
                return serialize($callback(unserialize($job->workload())));
            }
        );
    }

    public function work()
    {
        return $this->worker->work();
    }

    public function __call($method, $argument)
    {
        $argument = isset($argument[0]) ? $argument[0] : [];

        $option = array_merge([
            'is_background' => true,
            'priority' => 'normal', // normal, high, low
            'id' => null,
        ], isset($argument[1]) ? $argument[1] : []);

        if ($option['is_background']) {
            switch ($option['priority']) {
                case 'low':
                    return $this->client->doLowBackground(
                        $this->prefix . $method,
                        serialize($argument), $option['id']
                    );
                case 'high':
                    return $this->client->doHighBackground(
                        $this->prefix . $method,
                        serialize($argument), $option['id']
                    );
                default:
                    return $this->client->doBackground(
                        $this->prefix . $method,
                        serialize($argument), $option['id']
                    );
            }
        } else {
            switch ($option['priority']) {
                case 'low':
                    return unserialize($this->client->doLow(
                        $this->prefix . $method,
                        serialize($argument), $option['id'])
                    );
                case 'high':
                    return unserialize($this->client->doHigh(
                        $this->prefix . $method,
                        serialize($argument), $option['id'])
                    );
                default:
                    return unserialize($this->client->doNormal(
                        $this->prefix . $method,
                        serialize($argument), $option['id'])
                    );
            }
        }
    }
}

