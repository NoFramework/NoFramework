<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http\Controller;

class UploadInput
{
    use \NoFramework\MagicProperties;

    protected $view;
    protected $file_queue;
    protected $accept_method = 'POST';

    const SHOW = 'Accepts %s body as file.';
    const SUCCESS = 'OK';

    public function show()
    {
        $this->view->data = sprintf(static::SHOW, $this->accept_method);
        $this->view->render();
    }

    public function save()
    {
        ini_set('memory_limit', '2G');

        $this->file_queue->withLockedFile(function ($file)
        {
            $contents = file_get_contents('php://input');

            if ( ! $contents ) {
                throw new \RuntimeException('Empty body.');
            }

            file_put_contents($file, $contents);
        });

        $this->view->data = static::SUCCESS;
        $this->view->render();
    }
}

