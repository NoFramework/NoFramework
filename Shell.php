<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

class Shell extends Factory
{
    protected $prompt = '>';
    protected $history_path;
    protected $namespace = __NAMESPACE__;
    protected $shortcuts = [];

    public function __invoke($code)
    {
        if (is_string($code)) {
            if (0 === strpos(trim($code), 'ns ')) {
                $this->namespace = trim(substr(trim($code), 2));
                $is_registered = false;

                Autoload::walk(function ($autoload) use (&$is_registered) {
                    $is_registered = true;
                }, $this->namespace);

                if (!$is_registered) {
                    (new Autoload([
                        'namespace' => $this->namespace,
                        'path' => realpath(
                        __DIR__ . DIRECTORY_SEPARATOR . '..' .
                        DIRECTORY_SEPARATOR .
                        str_replace('\\', DIRECTORY_SEPARATOR, $this->namespace)
                        )
                    ]))->register();

                    printf('Autoload: %s' . PHP_EOL, $this->namespace);
                }

            } else {
                foreach (array_reverse($this->shortcuts) as $replace) {
                    $code = 0 === strpos($replace['from'], '#')
                        ? preg_replace($replace['from'], $replace['to'], $code)
                        : str_replace($replace['from'], $replace['to'], $code);
                }


                return eval(sprintf(
                    'namespace %s;' . PHP_EOL .
                    '%s;',
                    $this->namespace,
                    $code
                ));
            }
        } elseif (method_exists($code, '__invoke')) {
            $parameter = func_get_args();
            array_shift($parameter);
            return call_user_func_array($code, $parameter);

        } else {
            throw new \RuntimeException(sprintf(
                'First parameter must be a string or a callable'
            ));
        }
    }

    public function __call($name, $parameter)
    {
        if (method_exists($this->$name, '__invoke')) {
            return call_user_func_array($this->$name, $parameter);

        } else {
            throw new \InvalidArgumentException(sprintf(
                '%s is not callable',
                $name
            ));
        }
    }

    public function start()
    {
        static $pid;

        if ($pid) {
            printf('My pid: %d' . PHP_EOL, $pid);
            return;
        }

        if ($this->fork()) { // main parent
            pcntl_sigwaitinfo(array(SIGQUIT));

        } else { // main child
            $pid = posix_getppid();

            if (is_file($this->history_path)) {
                readline_read_history($this->history_path);
            }

            $previous_line = false;

            while(true) {
                $line = readline(sprintf('%s %s ',
                    $this->namespace,
                    $this->prompt
                ));

                if (false === $line) {
                    break;

                } elseif ($line) {
                    if ($line !== $previous_line) {
                        readline_add_history($line);
                        $previous_line = $line;
                    }

                    if (0 === strpos(trim($line), 'sh ')) {
                        system(sprintf(
                            '(%s) < /dev/tty > /dev/tty',
                            trim(substr($line, 2))
                        ));

                    } else {
                        if ($this->fork()) { // process parent
                            pcntl_wait($status);

                        } else { // process child
                            try {
                                $result = $this($line);

                                if (!is_null($result)) {
                                    var_dump($result);
                                }
                            } catch (\Exception $e) {
                                echo $e . PHP_EOL;
                            }

                            posix_kill(posix_getppid(), 9);
                        }
                    }
                }
            }

            if ($this->history_path) {
                readline_write_history($this->history_path);
            }

            echo PHP_EOL;

            posix_kill($pid, SIGQUIT);
        }
    }

    protected function fork()
    {
        $pid = pcntl_fork();

        if (-1 !== $pid) {
            return $pid;

        } else {
            throw new \RuntimeException('Could not fork');
        }
    }
}

