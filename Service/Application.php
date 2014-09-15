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

declare(ticks = 1);

class Application
{
    use \NoFramework\Magic;

    protected $pidfile;

    protected function __property_log()
    {
        return new Log;
    }

    protected function __property_error_log()
    {
        return new ErrorLog;
    }

    public function start()
    {
        if ($this->workingPid()) {
            return;
        }

        if ($this->pidfile) {
            if (is_file($this->pidfile)) {
                unlink($this->pidfile);
            }

            file_put_contents($this->pidfile, $this->pid() . PHP_EOL);
        }

        try {
            foreach (get_class_methods($this) as $method) {
                if (0 === strpos($method, '__signal_')) {
                    pcntl_signal(
                        constant('SIG' . strtoupper(
                            substr($method, strlen('__signal_'))
                        )),
                        [$this, $method]
                    );
                }
            }

            $this->log('start');
            $this->main();
            $this->log('done');

        } catch (Stop $stop) {
            if ($message = $stop->getMessage()) {
                $this->log($message);
            }

        } catch (\Exception $e) {
            $this->error_log($e);

        } finally {
            if ($this->workingPid() === $this->pid()) {
                unlink($this->pidfile);
            }
        }
    }

    protected function stop($message = 'stop')
    {
        throw new Stop($message);
    }

    protected function log($message, $type = false)
    {
        $this->log->write($message, $type);
    }

    protected function error_log($message, $type = false)
    {
        $this->error_log->write($message, $type);
    }

    protected function workingPid()
    {
        return
            (
                $this->pidfile and
                is_file($this->pidfile) and
                $pid = (int)file_get_contents($this->pidfile) and
                (posix_kill($pid, 0) or 3 !== posix_get_last_error())
            )
            ? $pid
            : false
        ;
    }

    protected function pid()
    {
        return (int)posix_getpid();
    }

    public function __signal_int()
    {
        $this->stop('break');
    }

    public function __signal_term()
    {
        $this->stop('terminate');
    }

    protected function main() {}
}

