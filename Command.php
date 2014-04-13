<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

trait Command
{
    public function command($command)
    {
        reset($command);
        $method = key($command);

        if (method_exists($this, '__command_' . $method)) {
            return $this->{'__command_' . $method}(
                $command[$method], 
                function ($option, $default = null) use ($command) {
                    return is_array($option)
                        ? array_intersect_key($command, array_flip($option))
                        : (isset($command[$option])
                            ? $command[$option]
                            : $default
                        );
                }
            );

        } elseif (method_exists($this, '__command')) {
            return $this->__command($command);

        } else {
            trigger_error(sprintf(
                'Call to undefined command %s::%s()',
                static::class,
                $method
            ), E_USER_ERROR);
        }
    }

    public function commandExists($command)
    {
        return
            method_exists($this, '__command_' . $command) or
            method_exists($this, '__command');
    }
    
    public function __call($command, $argument) {
        return $this->command(array_merge(
            [$command => isset($argument[0]) ? $argument[0] : null],
            isset($argument[1]) ? $argument[1] : []
        ));
    }
}

