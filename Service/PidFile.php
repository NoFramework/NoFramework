<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service;

class PidFile
{
    public $path;

    const ERR_EPERM = 1; // EPERM/ESRCH values on most *nix systems.
                         // Checked on *BSD, Linux
    const ERR_ESRCH = 3; // No such process

    public function check($timeout = false)
    {
        if (!is_file($this->path)) {
            return false;
        }

        $pid = (int)file_get_contents($this->path);

        if (0 === $pid or $pid === $this->getCurrentPid()) {
            return false;
        }

        if (!posix_kill($pid, 0)) {
            $errno = posix_get_last_error();

            if (
                // if we have no permissions to kill() $pid,
                // the pid got owned by another uid so it is not me
                $errno == self::ERR_EPERM
                // no such process
            or  $errno == self::ERR_ESRCH
            ) {
                return false;
            }
        }

        if ($timeout) {
            $time = filemtime($this->path);

            if (time() - $time > $timeout) {
                if (!posix_kill($pid, 9)) {
                    $errno = posix_get_last_error();
                    throw new \RuntimeException(
                        'Could not kill overtimed process: %s',
                        posix_strerror($errno)
                    );
                } 

                return false;
            }
        }

        return $pid;
    }

    public function write()
    {
        $this->pid = $this->getCurrentPid();
        file_put_contents($this->path, $this->pid . PHP_EOL);
        return $this;
    }

    public function delete()
    {
        if (is_file($this->path) and !$this->isPidChanged()) {
            unlink($this->path);
        }

        return $this;
    }

    protected function isPidChanged()
    {
        return isset($this->pid) && $this->pid != $this->getCurrentPid();
    }

    protected function getCurrentPid() {
        return (int)posix_getpid();
    }
}

