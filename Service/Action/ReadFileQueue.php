<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Service\Action;

abstract class ReadFileQueue extends \NoFramework\Service\Action
{
    protected $file_queue;
    protected $move_file_queue = false;
    protected $is_only_last = false;

    abstract protected function read($filename);

    public function __invoke($filename, $count, $total) {
        if (!$this->is_only_last or $count === $total) {
            $this->read($filename);
        }

        if ( $this->move_file_queue ) {
            if ( 'unlink' === $this->move_file_queue ) {
                unlink($filename);

            } else {
                $this->move_file_queue->withLockedFile(function ($log_filename) use ($filename) {
                    rename($filename, $log_filename);
                });
            }
        }
    }

    public function run($parameters = [])
    {
        return $this->file_queue->walkExclusive($this);
    }
}

