<?php
namespace NoFramework\Log\File;

class Split extends \NoFramework\Log\File
{
    protected $period;
    public $format;

    protected function __property_postfix()
    {
        return sprintf(str_replace('*','%s',$this->format), date( 'YmdHis', floor(time() / $this->period) * $this->period ));
    }

    protected function __property_handle()
    {
        $_path = sprintf('%s/%s', $this->path, $this->postfix);

        if( ! is_dir(dirname($_path)) )
            mkdir(dirname($_path), 0755, true);

        $handle = fopen($_path, 'a');
        @chmod($_path, 0664);

        return $handle;
    }
}

