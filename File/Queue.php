<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\File;

class Queue
{
    protected $path;
    protected $id_size = 6;
    protected $chmod_dir = 0777;
    protected $chmod_file = 0666;
    protected $files_per_dir = 1000;

    public function __construct($state = [])
    {
        foreach ([
            'path',
            'id_size',
            'chmod_dir',
            'chmod_file'
        ] as $property) {
            if (isset($state[$property])) {
                $this->$property = $state[$property];
            }
        }
    }

    public function ensurePath($path)
    {
        if ($path = str_replace("\0", '', $path)) {
            if (!is_dir($path)) {
                mkdir($path, $this->chmod_dir, true);
            }
        } else {
            throw new \InvalidArgumentException('Empty path');
        }

        return $path;
    }

    public function getPathById($id)
    {
        return $this->ensurePath(
            $this->path .
            DIRECTORY_SEPARATOR .
            $this->normalizeId($id - $id % $this->files_per_dir)
        );
    }

    public function getFilenameById($id) 
    {
        return $this->getPathById($id) . DIRECTORY_SEPARATOR. $this->normalizeId($id);
    }

    public function getIdByFilename($filename)
    {
        return is_file($filename) ? intval(pathinfo($filename, PATHINFO_BASENAME)) : 0;
    }

    public function getLastId()
    {
        $file_list = $this->getFileList();
        return $file_list ? $this->getIdByFilename(array_pop($file_list)) : 0;
    }

    public function withLockedFile($closure, $filename = false)
    {
        $is_chmod = false;

        if ( false === $filename ) {
            $filename = $this->getFilenameById($this->getLastId() + 1);
            $handle = fopen($filename, 'w');
            $is_chmod = true;

        } else {
            if ( ! is_file($filename) ) {
                throw new \RuntimeException(sprintf('No such file %s', $filename));
            }
            $handle = fopen($filename, 'r');
        }

        $is_processed = false;

        if ( flock($handle, LOCK_EX | LOCK_NB) ) {
            $closure($filename);
            if ( $is_chmod && is_file($filename) ) {
                chmod($filename, $this->chmod_file);
            }
            flock($handle, LOCK_UN);
            $is_processed = true;
        }

        fclose($handle);

        return $is_processed;
    }

    public function walk($closure)
    {
        $total = count($file_list = $this->getFileList());

        $count = 0;
        foreach ($file_list as $filename) {
            $count++;

            $is_processed = $this->withLockedFile(function ($filename) use (
                $closure,
                $count,
                $total
            ) {
                $closure($filename, $count, $total);
            }, $filename);

            if (!$is_processed) {
                break;
            }
        }

        return $this;
    }

    public function walkExclusive($closure, $lock_filename = 'walk.lck')
    {
        $lock_filename = $this->ensurePath($this->path) . DIRECTORY_SEPARATOR . $lock_filename;
        $count = 0;
        $handle = fopen($lock_filename, 'w');
        chmod($lock_filename, $this->chmod_file);

        if ( flock($handle, LOCK_EX | LOCK_NB) ) {
            $count = $this->walk($closure);
            flock($handle, LOCK_UN);
        }

        fclose($handle);
        unlink($lock_filename);

        return $count;
    }

    protected function normalizeId($id)
    {
        return substr(str_repeat('0', $this->id_size) . $id, -$this->id_size);
    }

    protected function getFileList()
    {
        return glob($this->path . str_repeat(DIRECTORY_SEPARATOR . '*', 2));
    }
}

