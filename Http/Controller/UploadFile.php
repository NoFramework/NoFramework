<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http\Controller;

class UploadFile
{
    use \NoFramework\MagicProperties;

    protected $request;
    protected $view;
    protected $file_queue;
    protected $post_field = 'file';
    protected $is_base64 = true;
    protected $is_zip = false;
    protected $show_is_base64 = false;
    protected $show_is_zip = false;
    protected $show_title = 'Upload';

    const SUCCESS = 'OK';

    public function show() {
        $view = $this->view->upload;
        $view->data['title'] = $this->show_title;
        $view->data['is_base64'] = $this->show_is_base64;
        $view->data['is_zip'] = $this->show_is_zip;
        $view->data['post_field'] = $this->post_field;
        $view->render();
    }

    public function save()
    {
        ini_set('memory_limit', '2G');
        if ( ! ($uploaded_file = $this->request->files($this->post_field)) ) {
            $this->show();
            return;
        }

        if ( $uploaded_file['error'] ) {
            throw new \RuntimeException($uploaded_file['error']);
        }

        $is_base64 = $this->request->post('is_base64');
        $is_base64 = is_null($is_base64) ? $this->is_base64 : ( 1 == $is_base64 );
        $is_zip = $this->request->post('is_zip');
        $is_zip = is_null($is_zip) ? $this->is_zip : ( 1 == $is_zip );

        $this->file_queue->withLockedFile(function ($file) use ($uploaded_file, $is_base64, $is_zip)
        {
            if ( ! move_uploaded_file($uploaded_file['tmp_name'], $file) ) {
                throw new \RuntimeException ('Error moving uploaded file');
            }

            if ( $is_base64 ) {
                file_put_contents($file, base64_decode(file_get_contents($file)));
            }

            if ( $is_zip ) {
                if (
                    ! ($zip = zip_open($file)) ||
                    ! ($zip_entry = zip_read($zip)) ||
                    ! zip_entry_open($zip, $zip_entry, 'r')
                ) {
                    throw new \RuntimeException ('Error unziping file ' . $file);
                }

                $data = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                zip_entry_close($zip_entry);
                zip_close($zip);
                file_put_contents($file, $data);
            }
        });

        $this->view->plain->data = static::SUCCESS;
        $this->view->plain->render();
    }
}

